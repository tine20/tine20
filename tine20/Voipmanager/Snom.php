<?php
/**
 * Tine 2.0
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * backend class for Zend_Http_Server
 *
 * This class handles all Http/XML requests for the Snom telephones
 *
 * @package     Voipmanager Management
 */
class Voipmanager_Snom extends Tinebase_Application_Json_Abstract
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_appname = 'Voipmanager';

    /**
     * Search Contacts
     */
    
    /**
     * public function to access the directory
     *
     * @deprecated 
     * @todo move that to Phone application
     */
    public function directory()
    {
        $session = new Zend_Session_Namespace('SnomDirectory');
        
        if (!$session->phone instanceof Voipmanager_Model_SnomPhone) {
            $this->_authenticate();
            
            $vmController = Voipmanager_Controller::getInstance();
            
            $phone = $vmController->getSnomPhoneByMacAddress($_REQUEST['mac']);
            
            $session->phone = $phone;
        }
        
        if(!isset($_REQUEST['query'])) {
            echo $this->_getSearchDialogue();
        } else {
            if(!empty($_REQUEST['query'])) {
                echo $this->_searchContacts($session->phone, $_REQUEST['query']);
            }
        }
    }
    
    /**
     * create the search dialogue
     *
     * @return string
     */
    protected function _getSearchDialogue()
    {
        $protocol = !empty($_SERVER['HTTPS']) ? 'https://' : 'http://';
        $name = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
        $port = $_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443' ? ':' . $_SERVER['SERVER_PORT'] : '' ;
        
        $baseURL = $protocol . $name . $port . $_SERVER['PHP_SELF'];
                
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <SnomIPPhoneInput>
                <Prompt>Prompt</Prompt>
                <URL>' . $baseURL . '</URL>
                <InputItem>
                    <DisplayName>Search for</DisplayName>
                    <QueryStringParam>' . SID . '&method=Voipmanager.directory&query</QueryStringParam>
                    <DefaultValue/>
                    <InputFlags>a</InputFlags>
                </InputItem>
            </SnomIPPhoneInput>
        ';
    
        return $xml;
    }
    
    /**
     * create the search results dialogue
     *
     * @param Voipmanager_Model_SnomPhone $_phone
     * @param string $_query
     * @return string
     */
    protected function _searchContacts(Voipmanager_Model_SnomPhone $_phone, $_query)
    {
        $contactsBackend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
        
        $tbContainer = Tinebase_Container::getInstance();
        
        $readAbleContainer = array();
        
        foreach($_phone->rights as $right) {
            if($right->account_type == 'user') {
                $containers = $tbContainer->getContainerByACL($right->account_id, 'Addressbook', Tinebase_Container::GRANT_READ);
                $readAbleContainer = array_merge($readAbleContainer, $containers->getArrayOfIds());
            }
        }
        $readAbleContainer = array_unique($readAbleContainer);
        
        $filter = new Addressbook_Model_ContactFilter();
        $filter->container = $readAbleContainer;
        $filter->query = $_query;
        
        $contacts = $contactsBackend->search($filter, new Tinebase_Model_Pagination());
        
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
          <SnomIPPhoneDirectory>
            <Title>Directory</Title>
            <Prompt>Dial</Prompt>
          </SnomIPPhoneDirectory>
        ');
        
        foreach($contacts as $contact) {
            if(!empty($contact->tel_work)) {
                $directoryEntry = $xml->addChild('DirectoryEntry');
                $directoryEntry->addChild('Name', $contact->n_fileas . ' Work');
                $directoryEntry->addChild('Telephone', $contact->tel_work);
            }
            if(!empty($contact->tel_cell)) {
                $directoryEntry = $xml->addChild('DirectoryEntry');
                $directoryEntry->addChild('Name', $contact->n_fileas . ' Cell');
                $directoryEntry->addChild('Telephone', $contact->tel_cell);
            }
            if(!empty($contact->tel_home)) {
                $directoryEntry = $xml->addChild('DirectoryEntry');
                $directoryEntry->addChild('Name', $contact->n_fileas . ' Home');
                $directoryEntry->addChild('Telephone', $contact->tel_home);
            }
        }
        
        return $xml->asXML();
    }    
    
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
        
        $vmController = Voipmanager_Controller::getInstance();
        
        $phone = $vmController->getSnomPhoneByMacAddress($mac);

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
        
        $vmController->updateSnomPhoneRedirect($phone);
    }

    /**
     * retrieve settings
     *
     * @param string $mac
     */
    public function settings($mac)
    {
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' get settings for ' . $mac);
        $controller = Voipmanager_Controller::getInstance();
        
        $phone = $controller->getSnomPhoneByMacAddress($mac);
        
        if($phone->http_client_info_sent == true) {
            $this->_authenticate();
        } else {
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' skipped authentication');
        }
        
        $phone = $this->_setStatus($phone, 'settings');
        
        $xmlBackend = new Voipmanager_Backend_Snom_Xml($controller->getDBInstance());        
        
        header('Content-Type: text/xml');
        echo $xmlBackend->getConfig($phone);
        
        if($phone->http_client_info_sent == false) {
            Zend_Registry::get('logger')->debug('set http_client_info_sent to true again');
            $phone->http_client_info_sent = true;
            $controller->updateSnomPhone($phone);
        }
    }    
    
    /**
     * retrieve firmware settings
     *
     * @param string $mac
     */
    public function firmware($mac)
    {
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' get firmware for ' . $mac);
        
        $this->_authenticate();
        
        $controller = Voipmanager_Controller::getInstance();
        
        $phone = $controller->getSnomPhoneByMacAddress($mac);
        
        $phone = $this->_setStatus($phone, 'firmware');
        
        $xmlBackend = new Voipmanager_Backend_Snom_Xml($controller->getDBInstance());        
                
        header('Content-Type: text/xml');
        echo $xmlBackend->getFirmware($phone);        
    }    
    
    protected function _setStatus($_phone, $_type)
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        #$userAgent = 'Mozilla/4.0 (compatible; snom320-SIP 7.1.30';
        
        if(preg_match('/^Mozilla\/4\.0 \(compatible; (snom...)\-SIP (\d+\.\d+\.\d+)/i', $userAgent, $matches)) {
            $_phone->current_model = $matches[1];
            $_phone->current_software = $matches[2];
        } else {
            throw new Exception('unparseable useragent string');
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
        
        $controller = Voipmanager_Controller::getInstance();
        
        $controller->updateSnomPhone($_phone);
    
        return $_phone;
    }
    
    /**
     * authenticate the phone against the database
     *
     */
    protected function _authenticate()
    {
        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            header('WWW-Authenticate: Basic realm="Tine 2.0"');
            header('HTTP/1.0 401 Unauthorized');
            exit;
        }
        
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' authenticate ' . $_SERVER['PHP_AUTH_USER']);
        
        $vmController = Voipmanager_Controller::getInstance();
        
        $authAdapter = new Zend_Auth_Adapter_DbTable($vmController->getDBInstance());
        $authAdapter->setTableName(SQL_TABLE_PREFIX . 'snom_phones')
            ->setIdentityColumn('http_client_user')
            ->setCredentialColumn('http_client_pass')
            ->setIdentity($_SERVER['PHP_AUTH_USER'])
            ->setCredential($_SERVER['PHP_AUTH_PW']);

        // Perform the authentication query, saving the result
        $authResult = $authAdapter->authenticate();
        
        if (!$authResult->isValid()) {
            Zend_Registry::get('logger')->warning(__METHOD__ . '::' . __LINE__ . ' authentication failed for ' . $_SERVER['PHP_AUTH_USER']);
            header('WWW-Authenticate: Basic realm="Tine 2.0"');
            header('HTTP/1.0 401 Unauthorized');
            exit;
        }                
    }
    
}