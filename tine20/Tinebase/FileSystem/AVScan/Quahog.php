<?php
/**
 * @package     Tinebase
 * @subpackage  FileSystem
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2019-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * av scanner class encapsulating \Xenolope\Quahog / avclamd
 */
class Tinebase_FileSystem_AVScan_Quahog implements Tinebase_FileSystem_AVScan_Interface
{
    /**
     * @var \Socket\Raw\Socket
     */
    protected $_socket = null;

    /**
     * @var \Xenolope\Quahog\Client
     */
    protected $_quahog = null;

    /**
     * @throws \Xenolope\Quahog\Exception\ConnectionException
     * @throws \Socket\Raw\Exception
     */
    protected function _connect()
    {
        if (null === $this->_socket) {
            $avUrl = Tinebase_Config::getInstance()
                ->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_AVSCAN_URL};
            $this->_socket = (new \Socket\Raw\Factory())->createClient($avUrl);
            $this->_quahog = new \Xenolope\Quahog\Client($this->_socket, 30, PHP_NORMAL_READ);
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .
                    ' Socket client created for url ' . $avUrl);
            }
        } else {
            try {
                $this->_socket->assertAlive();
            } catch (\Socket\Raw\Exception $sre) {
                $this->_socket->close();
                $this->_socket = null;
                $this->_connect();
            }
        }
    }

    /**
     * @param resource $handle
     * @return Tinebase_FileSystem_AVScan_Result
     */
    public function scan($handle)
    {
        $e = null;
        try {
            $this->_connect();

            rewind($handle);
            $result = $this->_quahog->scanResourceStream($handle);

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                    ' Scanning done. Result: ' . print_r($result, true));
            }

        } catch (\Socket\Raw\Exception $e) {
        } catch (\Xenolope\Quahog\Exception\ConnectionException $e) {}

        if (null !== $e) {
            Tinebase_Exception::log($e);

            $this->_socket->close();
            $this->_socket = null;

            $result = [
                'status' => Tinebase_FileSystem_AVScan_Result::RESULT_ERROR,
                'reason' => $e->getMessage()
            ];
        }

        return new Tinebase_FileSystem_AVScan_Result($result['status'], $result['reason']);
    }

    /**
     * @return bool
     */
    public function update()
    {
        $e = null;

        try {
            $this->_connect();

            $result = $this->_quahog->reload();
        } catch (\Socket\Raw\Exception $e) {
        } catch (\Xenolope\Quahog\Exception\ConnectionException $e) {}

        if (null !== $e) {
            Tinebase_Exception::log($e);

            $this->_socket->close();
            $this->_socket = null;

            return false;
        }

        return $result === Tinebase_FileSystem_AVScan_Result::RESULT_RELOADING;
    }
}