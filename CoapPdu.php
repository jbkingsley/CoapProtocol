<?php
namespace CoapProtocol;

class CoapPdu
{
    public $version = 1;
    public $type = 0;
    public $code;
    public $messageId = 0;
    public $token = "";
    public $options = array();
    public $payload = "";

    public $byteBuffer = array();

    const COAP_LEN = 4;
    const CON = 0;
    const NON = 1;
    const ACK = 2;
    const RST = 3;

    const GET = '0.01';
    const POST = '0.02';
    const PUT = '0.03';
    const DELETE = '0.04';

    function __construct(){
        $this->messageId = self::genMessageId();
    }

    public function isAck(){
        return ( $this->type == 0x02 );
    }

    public function getType(){
        return $this->type;
    }

    public function setType( $type ){
        $this->type = $type;
    }

    public function setCode( $string ){
        $this->code = $string;
    }

    public function getCode(){
        return $this->code;
    }

    public function getMessageId(){
        return $this->messageId;
    }

    public function setMessageId( $id ){
        $this->messageId = (int) $id;
    }

    public function addOption( CoapOption $opt ){
        array_push( $this->options, $opt );
    }

    public function GetOptions(){
        return  $this->options;
    }

    public function getMessage(){
        $rv = '';
        foreach ( $this->byteBuffer as $value) {
            $rv .= pack( 'C', $value );
        }
        $coapLength = pack('N', strlen($rv));
        $rv = $coapLength . $rv;
        return $rv;
    }

    public function genCode( $class, $detail ){
        $class = intval( $class ) & ( (1 << 3) - 1 );
        $detail = intval( $detail ) & ( (1 << 5) - 1 );
        return ( $class << 5 ) | ( $detail );
    }


    static function genMessageId(){
        list( $usec, $sec ) = explode( " ", microtime() );
        return intval( substr( $usec, 2 ) ) & ( pow( 2, 16 ) - 1);
    }


    public function readPayload($buf, $start){
        $this->payload = '';
        for( $i = $start; $i<=count($buf); $i++ )
        {
            $this->payload .= pack( 'C', $buf[$i] );
        }
    }

    public function setPayload($string){
        $this->payload = $string;
    }

    public function compile(){
        $i = 0;
        // Header
        $this->byteBuffer[$i] = $this->version << 6;
        $this->byteBuffer[$i] |= $this->type << 4;
        $this->byteBuffer[$i] |= strlen( $this->token );
        $i++;

        // Code
        list( $class, $detail ) = explode( '.', $this->code );
        $this->byteBuffer[$i++] = $this->genCode( $class, $detail );

        // Message Id
        $this->byteBuffer[$i++] = $this->messageId >> 8;
        $this->byteBuffer[$i++] = $this->messageId & (( 1 << 8 ) - 1 );

        // Token
        if( strlen( $this->token ) != 0 ) {
            throw new \Exception( 'Not Implemented!' );
        }

        CoapOption::sort( $this->options );
        $prevNo = 0;
        foreach($this->options as $opt){
            $delta = $opt->getOptionNumber() - $prevNo;

            if($delta < 13) {
                $this->byteBuffer[$i] = $delta << 4;
                if ( $opt->length() < 13 )
                {
                    $this->byteBuffer[$i] |= $opt->length();
                    $lenExt = false;
                }
                else
                {
                    $lenExt = ( $opt->length() > 255 ) ? 14 : 13;
                    $this->byteBuffer[$i] |= $lenExt;
                }
                $i++;
            }else {
                throw new \Exception( "Not Implemented!" );
            }

            if ($lenExt == 13){
                $this->byteBuffer[$i++] = $opt->length() - 13;
            }

            foreach($opt->getByteArray() as $byte) {
                $this->byteBuffer[$i++] = $byte;
            }

            $prevNo = $opt->getOptionNumber();
        }

        if ( $this->payload !== "" ){
            $this->byteBuffer[$i++] = (1 << 8 ) - 1;

            foreach(unpack( 'C*', $this->payload ) as $byte) {
                $this->byteBuffer[$i++] = $byte;
            }
        }
        return $this->getMessage();
    }

    static function fromBinString($binString){
        $pdu = new self();
        $buf = unpack( 'C*', $binString);

        //get coap version and type
        $i = 5;
        $pdu->version = $buf[$i] >> 6;
        $pdu->type = ($buf[$i] >> 4) & 0x03;

        $tkl = $buf[$i] & 15;

        $i++;
        $pdu->code = sprintf( '%01d', $buf[$i] >> 5 );
        $pdu->code .= '.' . sprintf( '%02d', $buf[$i] & 0x07 );

        $i+= 2;
        $pdu->messageId = ( $buf[$i-1] << 8 ) | ( $buf[$i] );

        //token length
        if($tkl > 0){
            for($j=$i; $j<=$tkl; $j++){
                $pdu->token .= unpack( 'C', $buf[$j] );
            }
        }

        $i += $tkl + 1;
        if(isset($buf[$i])){
            $prev = 0;
            while($i <= count($buf) && $buf[$i] != 0xFF){
                $prev = $pdu->parseOption($buf, $i, $prev);
            }
            if($i <= count($buf) && $buf[$i] == 0xFF){
                $pdu->readPayload( $buf, $i+1 );
            }
        }
        return $pdu;
    }

    public function parseOption($buf, &$start, $prevNo){
        $i = $start;
        $optNo = ($buf[$i] >> 4);
        $optLen = $buf[$i] & 0x0F;

        $i++;

        if($optNo == 13)
        {
            $optNo = 13 + $buf[$i];
            $i++;
        }

        if($optNo == 14)
        {
            $optNo = 269 + ( $buf[$i] << 8 ) + $buf[ $i + 1 ];
            $i++;
        }

        $optNo += $prevNo;

        if($optLen == 13)
        {
            $optLen = 13 + $buf[$i];
            $i++;
        }

        if($optLen == 14)
        {
            $optLen = 269 + ( $buf[$i] << 8 ) + $buf[ $i + 1 ];
            $i++;
        }

        $value = "";

        for($j = 0; $j < $optLen; $j++)
        {
            $value .= sprintf('%c', $buf[$i + $j]);
        }
        $i += $j;

        $start = $i;

        $opt = new CoapOption( $optNo, $value );
        array_push( $this->options, $opt );

        return $optNo;
    }
}