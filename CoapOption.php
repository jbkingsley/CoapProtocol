<?php
namespace CoapProtocol;

class CoapOption
{
    private $no;
    private $value;

    private static $typeMap = array(
        1 => array(
            'name' => 'If-Match',
            'format' =>'opaque'
        ),
        3 => array(
            'name' => 'Uri-Host',
            'format' => 'string'
        ),
        4 => array(
            'name' => 'ETag',
            'format' => 'opaque'
        ),
        5 => array(
            'name' => 'If-None-Watch',
            'format' => 'empty'
        ),
        7 => array(
            'name' => 'Uri-Port',
            'format' => 'uint'
        ),
        8 => array(
            'name' => 'Location-Path',
            'format' => 'string'
        ),
        11 => array(
            'name' => 'Uri-Path',
            'format' => 'string'
        ),
        12 => array(
            'name' => 'Content-Format',
            'format' => 'uint'
        ),
        14 => array(
            'name' => 'Max-Age',
            'format' => 'uint'
        ),
        15 => array(
            'name' => 'Uri-Query',
            'format' => 'string',
        ),
        17 => array(
            'name' => 'Accept',
            'format' => 'uint'
        ),
        20 => array(
            'name' => 'Location-Query',
            'format' => 'string'
        ),
        35 => array(
            'name' => 'Proxy-Uri',
            'format' => 'string'
        ),
        39 => array(
            'name' => 'Proxy-Scheme',
            'format' => 'string'
        ),
        60 => array(
            'name' => 'Size1',
            'format' => 'uint'
        )
    );

    public function __construct( $no, $value )
    {
        $this->no = $no;
        $this->parseValue( $value );
    }

    public function getOptionNumber()
    {
        return $this->no;
    }

    public function getDelta( $prevNo )
    {
        return $this->no - $prevNo;
    }

    public function length()
    {
        switch (self::$typeMap[ $this->no ]['format'])
        {
            case 'uint':
                return ( $this->value >= pow( 2, 8 ) ) ? 2 : 1;
                break;
            case 'string':
                return strlen( $this->value );
                break;
            case 'opaque':
                throw new Exception( "Not Implemented!" );
                break;
            case 'empty':
                return 0;
                break;
        }
    }

    public function parseValue( $value )
    {
        switch (self::$typeMap[ $this->no ]['format'])
        {
            case 'uint':
                $this->parseUintValue( $value );
                break;
            case 'string':
                $this->value = $value;
                break;
            case 'opaque':
                throw new Exception( "Not Implemented!" );
                break;
            case 'empty':
                $this->value = null;
                break;
        }

        return $this->value;
    }

    public function parseUintValue( $value )
    {
        $this->value = 0;
        $bytes = unpack( 'C*', $value );

        for ($i = 0; $i < count( $bytes ); $i++ )
        {
            $this->value += $bytes[ $i + 1 ] << ( 8 * ( count( $bytes ) - 1 - $i ) );
        }

    }

    public function getValue()
    {
        return $this->value;
    }

    public function getByteArray()
    {
        switch (self::$typeMap[ $this->no ]['format'])
        {
            case 'uint':
                $rv = array();
                for ( $i = $this->length() - 1; $i >= 0; $i-- )
                {
                    $rv[] = ( 0xFF & ( $this->value >> $i ) );
                }
                return $rv;
            case 'string':
                return unpack( 'C*', $this->value );
            case 'opaque':
                throw new Exception( "Not Implemented!" );
            case 'empty':
                return array();
        }
    }

    static function sort( Array &$options )
    {
        usort( $options, function( CoapOption $a, CoapOption $b ) {
            if ( $a->getOptionNumber() == $b->getOptionNumber() )
                return 0;
            return ( $a->getOptionNumber() > $b->getOptionNumber() ) ? 1 : -1;
        });
    }
}