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
     * public function to access the directory
     *
     */
    public function directory()
    {
        $session = new Zend_Session_Namespace('SnomDirectory');
        
        if (!$session->phone instanceof Voipmanager_Model_SnomPhone) {
            $this->_authenticate();
            
            $vmController = Voipmanager_Controller::getInstance();
            
            $phone = $vmController->getSnomPhoneByMacAddress($_REQUEST['mac']);
            $phone->rights = $phoneBackend->getPhoneRights($phone);
            
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
            header('WWW-Authenticate: Basic realm="Tine 2.0"');
            header('HTTP/1.0 401 Unauthorized');
            exit;
        }                
    }
    
    /**
     * create the search dialogue
     *
     * @return string
     */
    protected function _getSearchDialogue()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <SnomIPPhoneInput>
                <Prompt>Prompt</Prompt>
                <URL>http://phonebox01.hh.metaways.de/tine20/index.php</URL>
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
                $directoryEntry->addChild('Name', $contact->n_fileas);
                $directoryEntry->addChild('Telephone', $contact->tel_work);
            }
        }
        
        return $xml->asXML();
    }    
}