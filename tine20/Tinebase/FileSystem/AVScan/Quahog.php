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
     * @var \Socket\Raw\Factory
     */
    protected $_socket = null;

    /**
     * @var \Xenolope\Quahog\Client
     */
    protected $_quahog = null;

    protected function _connect()
    {
        if (null === $this->_socket) {
            $avUrl = Tinebase_Config::getInstance()
                ->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_AVSCAN_URL};
            $this->_socket = (new \Socket\Raw\Factory())->createClient($avUrl);
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .
                    ' Socket client created for url ' . $avUrl);
            }
        }
        if (null === $this->_quahog) {
            $this->_quahog = new \Xenolope\Quahog\Client($this->_socket->assertAlive(), 30, PHP_NORMAL_READ);
        } else {
            $this->_socket->assertAlive();
        }
    }

    /**
     * @param resource $handle
     * @return Tinebase_FileSystem_AVScan_Result
     */
    public function scan($handle)
    {
        try {
            $this->_connect();

            rewind($handle);
            $result = $this->_quahog->scanResourceStream($handle);

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                    ' Scanning done. Result: ' . print_r($result, true));
            }

        } catch (\Socket\Raw\Exception $e) {
            Tinebase_Exception::log($e);

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
        try {
            $this->_connect();

            $result = $this->_quahog->reload();
        } catch (\Socket\Raw\Exception $e) {
            Tinebase_Exception::log($e);

            return false;
        }

        return $result === Tinebase_FileSystem_AVScan_Result::RESULT_RELOADING;
    }
}