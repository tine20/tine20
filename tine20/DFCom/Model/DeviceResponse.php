<?php
/**
 * @package     DFCom
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold DF Device Response
 *
 * @package     DFCom
 * @subpackage  Model
 */
class DFCom_Model_DeviceResponse //extends \Zend\Diactoros\Response
{
    const BEEP_OK = 1;
    const BEEP_ERROR = 2;
    const BEEP_LONG = 3;
    const BEEP_SHORTLONG = 4;
    const BEEP_SHORTSHORT = 5;
    const BEEP_LONGLONG = 6;
    const BEEP_SHORTSHORTSHORT = 7;
    const BEEP_LONGLONGLONG = 8;
    const BEEP_SHORTLONGSHORT = 9;
    const BEEP_LONGSHORTLONG = 10;
    const BEEP_SOS = 11;

    const FONT_STANDARD = 0;
    const FONT_7LINES = 1;
    const FONT_7LINES_FIXEDWIDTH = 2;
    const FONT_6LINES = 3;
    const FONT_6LINES_FIXEDWIDTH = 4;
    const FONT_5LINES = 5;
    const FONT_5LINES_FIXEDWIDTH = 6;


    protected $_responseData = [
        'df_api' => 1,
    ];

    protected $_headers = [];

    // Content-Type:  application/x-www-form-urlencoded; charset: iso-8859-1
//    protected $_df_api = 1;
//    protected $_df_time;
//    protected $_df_beep;
//    protected $_df_service;
//    protected $_df_var;
//    protected $_df_ek;
//    protected $_df_msg;
//    protected $_df_ac2;
//    protected $_df_setup_list;
//    protected $_df_ac2_list;

    /**
     * set device time
     *
     * @param DateTime $time
     * @return DFCom_Model_DeviceResponse $this
     */
    public function setTime(DateTime $time)
    {
        $this->_responseData['df_time'] = $time->format('Y-m-d\TH:i:s');

        return $this;
    }

    /**
     * beep on device
     *
     * @param int $signal
     * @throws Tinebase_Exception_InvalidArgument
     * @return DFCom_Model_DeviceResponse $this
     */
    public function beep($signal = self::BEEP_OK)
    {
        if (! is_int($signal) || $signal < 1 || $signal > 11) {
            throw new Tinebase_Exception_InvalidArgument('no such beep');
        }
        $this->_responseData['df_beep'] = $signal;

        return $this;
    }

    /**
     * @TODO: test: one var per response only????
     * 
     * @param  string $name
     * @param  string $value
     * @return DFCom_Model_DeviceResponse $this
     */
    public function setDeviceVariable($name, $value)
    {
        if ($name == 'setupVersion') {
            throw new Tinebase_Exception_InvalidArgument('setupVersion can only be set in setup!');
        }
        // NOTE: No urlencode here for some reason device doesn't decode
        $this->_responseData['df_var'] = "setup.$name," . $value;

        return $this;
    }

    /**
     * switch device to activeMode with optional service connection
     *
     * @param string $host
     * @param string $port
     * @return DFCom_Model_DeviceResponse $this
     */
    public function setService($host=null, $port=null)
    {
        $serviceString = '1';
        if ($host) {
            $serviceString .= ",$host";
        }
        if ($port) {
            $serviceString .= ",$port";
        }

        $this->_responseData['df_service'] = $serviceString;
        $this->_headers['Connection'] = 'close';

        return $this;
    }

    /**
     * trigger event chain
     *
     * @param $name
     * @return DFCom_Model_DeviceResponse $this
     */
    public function triggerEventChain($name)
    {
        $this->_responseData['df_ek'] = $name;
        return $this;
    }

    /**
     * display message
     * NOTE: only possible from user action
     *
     * @param string $message
     * @param int $duration
     * @param int $beep
     * @param int $font
     * @return $this
     */
    public function displayMessage($message, $duration=5, $beep=self::BEEP_SHORTLONG, $font=self::FONT_STANDARD)
    {
        $this->_responseData['df_msg'] = implode(',', [rawurlencode(utf8_decode($message)), $duration, $beep, $font]);
        return $this;
    }

    /**
     * direct device to update given list
     *
     * @param DFCom_Model_DeviceList $deviceList
     * @param DFCom_Model_Device $device
     */
    public function updateDeviceList(DFCom_Model_DeviceList $deviceList, DFCom_Model_Device $device, $query='')
    {
        $link = '/' . ltrim(Tinebase_Core::getUrl(Tinebase_Core::GET_URL_PATH) . "/DFCom/v1/device/{$device->getId()}/list/{$deviceList->getId()}/{$device->authKey}", '/');
        if ($query) {
            $link .= urlencode('?' . ltrim($query, '?'));
        }
        $this->_responseData['df_setup_list'] = $deviceList->name . ',' . ($link);
    }

    public function getHTTPResponse()
    {
        $body = implode('&', array_map(function($value, $key) {
            return $key.'='.$value;
        }, array_values($this->_responseData), array_keys($this->_responseData)));

        $headers = array_merge($this->_headers, [
            'Content-Type' => 'application/x-www-form-urlencoded; charset: iso-8859-1',
            'Content-Length' => strlen($body),
//            'Connection' => 'close',
        ]);

        $response = new \Zend\Diactoros\Response('php://memory', 200, $headers);
        $response->getBody()->write($body);

        return $response;
    }
}
