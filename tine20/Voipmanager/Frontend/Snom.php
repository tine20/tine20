<?php
/**
 * Tine 2.0
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @deprecated
 */

/**
 * backend class for Zend_Http_Server
 *
 * This class handles all Http/XML requests for the Snom telephones
 *
 * @package     Voipmanager Management
 */
class Voipmanager_Frontend_Snom extends Voipmanager_Frontend_Snom_Abstract
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_applicationName = 'Voipmanager';

    /**
     * set redirect
     *
     * @param string $mac
     * @param string $event
     * @param string $number
     * @param string $time
     */
    public function redirect($mac, $event, $number, $time)
    {
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' set redirect for ' . $mac . " to $event, $number, $time");
        
        $this->_authenticate();
        
        $vmController = Voipmanager_Controller_Snom_Phone::getInstance();
        
        $phone = $vmController->getByMacAddress($mac);

        $phone->redirect_event = $event;
        if($phone->redirect_event != 'none') {
            $phone->redirect_number = $number;
        } else {
            $phone->redirect_number = NULL;
        }
        
        if($phone->redirect_event == 'time') {
            $phone->redirect_time = $time;
        } else {
            $phone->redirect_time = NULL;
        }
        
        $vmController->updateRedirect($phone);
    }

    /**
     * retrieve settings
     *
     * @param string $mac
     */
    public function settings($mac)
    {
        $controller = Voipmanager_Controller_Snom_Phone::getInstance();
        
        $phone = $controller->getByMacAddress($mac);
        
        if($phone->http_client_info_sent == true) {
            $this->_authenticate();
        } else {
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' skipped authentication');
        }
        
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' get settings for ' . $mac);
        
        $phone = $this->_setStatus($phone, 'settings');
        
        $xmlConfig = Voipmanager_Controller_Snom_Xml::getInstance()->getConfig($phone);
        
        header('Content-Type: application/xml');
        // we must sent this header, as the snom phones can't work with chunked encoding
        header('Content-Length: ' . strlen($xmlConfig));
        echo $xmlConfig;
        
        if($phone->http_client_info_sent == false) {
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' set http_client_info_sent to true again');
            $phone->http_client_info_sent = true;
            $controller->update($phone);
        }
    }    
    
    /**
     * retrieve firmware settings
     *
     * @param string $mac
     */
    public function firmware($mac)
    {
        $this->_authenticate();
        
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' get firmware for ' . $mac);
        
        $controller = Voipmanager_Controller_Snom_Phone::getInstance();
        
        $phone = $controller->getByMacAddress($mac);
        
        $phone = $this->_setStatus($phone, 'firmware');
        
        $xmlFirmware = Voipmanager_Controller_Snom_Xml::getInstance()->getFirmware($phone);
        
        header('Content-Type: application/xml');
        // we must sent this header, as the snom phones can't work with chunked encoding
        header('Content-Length: ' . strlen($xmlFirmware));
        echo $xmlFirmware;        
    }    
    
    /**
     * set status
     *
     * @param Voipmanager_Model_Snom_Phone $_phone
     * @param unknown_type $_type
     * @return Voipmanager_Model_Snom_Phone
     * @throws  Voipmanager_Exception_UnexpectedValue
     */
    protected function _setStatus($_phone, $_type)
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        #$userAgent = 'Mozilla/4.0 (compatible; snom320-SIP 7.1.30';
        
        if(preg_match('/^Mozilla\/4\.0 \(compatible; (snom...)\-SIP (\d+\.\d+\.\d+)/i', $userAgent, $matches)) {
            $_phone->current_model = $matches[1];
            $_phone->current_software = $matches[2];
        } else {
            throw new Voipmanager_Exception_UnexpectedValue('unparseable useragent string');
        }
        
        $_phone->ipaddress = $_SERVER["REMOTE_ADDR"];
        
        switch($_type) {
            case 'settings':
                $_phone->settings_loaded_at = Zend_Date::now();
                break;
                
            case 'firmware':
                $_phone->firmware_checked_at = Zend_Date::now();
                break;
        }
        
        Voipmanager_Controller_Snom_Phone::getInstance()->update($_phone);
    
        return $_phone;
    }    
}