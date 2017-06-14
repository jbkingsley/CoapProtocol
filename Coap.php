<?php
namespace CoapProtocol;

use Workerman\Connection\TcpConnection;
use Workerman\Worker;

use Exception;

/**
 * Coap Protocol.
 */
class Coap
{
    /**
     * head length of CoAP protocol.
     *
     * @var int
     */
    const COAP_LEN = 4;


    /**
     * Check the integrity of the package.
     * Please return the length of package.
     * If length is unknow please return 0 that mean wating more data.
     * If the package has something wrong please return false the connection will be closed.
     *
     * @param TcpConnection $connection
     * @param string        $buffer
     * @return int|false
     */
    public static function input($buffer, TcpConnection $connection)
    {
        // Receive length.
        $recv_len = strlen($buffer);
        // We need more data.
        if ($recv_len < self::COAP_LEN) {
            return 0;
        }

        $pack     = unpack('Ndata_length', $buffer);
        $data_length = $pack['data_length'];
        $package_length = self::COAP_LEN + $data_length;

        if($recv_len<$package_length){
            return 0;
        }else{
            return $package_length;
        }

        /*
         *         if ($connection->coapCurrentFrameLength) {
            // We need more frame data.
            if ($connection->coapCurrentFrameLength > $recv_len) {
                // Return 0, because it is not clear the full packet length, waiting for the frame of fin=1.
                return 0;
            }
        }else{
            $pack     = unpack('Ndata_length', $buffer);
            $data_length = $pack['data_length'];
            $current_frame_length = self::COAP_LEN + $data_length;
            $connection->coapCurrentFrameLength = $current_frame_length;
        }

        // Received just a frame length data.
        if ($connection->coapCurrentFrameLength === $recv_len) {
            self::decode($buffer, $connection);
            $connection->consumeRecvBuffer($connection->coapCurrentFrameLength);
            $connection->coapCurrentFrameLength = 0;
            return 0;
        } // The length of the received data is greater than the length of a frame.
        elseif ($connection->coapCurrentFrameLength < $recv_len) {
            self::decode(substr($buffer, 0, $connection->coapCurrentFrameLength), $connection);
            $connection->consumeRecvBuffer($connection->coapCurrentFrameLength);
            $current_frame_length                    = $connection->coapCurrentFrameLength;
            $connection->coapCurrentFrameLength = 0;
            // Continue to read next frame.
            return self::input(substr($buffer, $current_frame_length), $connection);
        } // The length of the received data is less than the length of a frame.
        else {
            return 0;
        }
         * */

    }


    /**
     * Decode package and emit onMessage($message) callback, $message is the result that decode returned.
     *
     * @param string              $buffer
     * @return CoapPdu
     */
    public static function decode($buffer)
    {
        return $buffer;
    }

    /**
     * Encode package brefore sending to client.
     *
     * @param string $buffer
     * @return string
     * @throws
     */
    public static function encode($buffer)
    {

    }

}






