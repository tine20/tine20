<?php
/**
 * Tine 2.0
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * backend class for Zend_Http_Server
 *
 * This class handles all Http/XML requests for the Snom telephones
 *
 * @package     Phone
 */
class Phone_Snom extends Tinebase_Application_Json_Abstract
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_appname = 'Phone';

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
            
            $session->phone = $phone;
        }
        
        header('Content-Type: text/xml');
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
        $baseUrl = $this->_getBaseUrl();
                
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <SnomIPPhoneInput>
                <Prompt>Prompt</Prompt>
                <URL>' . $baseUrl . '</URL>
                <InputItem>
                    <DisplayName>Search for</DisplayName>
                    <QueryStringParam>' . SID . '&method=Phone.directory&query</QueryStringParam>
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
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' phone ' . $_phone->getId(). ' search for ' . $_query);
        
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
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' found ' . count($contacts) . ' contacts');
        
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
     * keeps track of the call history
     * 
     * the callId can be 3c3b966053be-phxdiv27t9gm or 7027a58643aeb25149e4861076f1b0a9@xxx.xxx.xxx.xxx
     * we strip everything after the @ character
     *
     * @param string $mac the mac address of the phone
     * @param string $event event can be connected, disconnected, incoming, outgoing, missed
     * @param string $callId the callid
     * @param string $local the local number
     * @param string $remote the remote number
     * 
     * @todo add correct line_id
     */
    public function callHistory($mac, $event, $callId, $local, $remote)
    {        
        $this->_authenticate();
        
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . " Event: $event CallId: $callId Local: $local Remote: $remote ");
        
        $vmController = Voipmanager_Controller::getInstance();
        $phone = $vmController->getSnomPhoneByMacAddress($mac);
        $controller = Phone_Controller::getInstance();
        
        $pos = strpos($callId, '@');
        if ($pos !== false) {
            $callId = substr($callId, 0 , $pos);
        };
        
        $pos = strpos($local, '@');
        if ($pos !== false) {
            $local = substr($local, 0 , $pos);
        };
        
        $pos = strpos($remote, '@');
        if ($pos !== false) {
            $remote = substr($remote, 0 , $pos);
        };
        
        $call = new Phone_Model_Call(array(
            'id'            => $callId,
            'phone_id'      => $phone->getId(),
            'line_id'       => 'xxx'
        ));    
        
        switch($event) {
            case 'outgoing':
                $call->source = $local;
                $call->destination = $remote;
                $call->direction = Phone_Model_Call::TYPE_OUTGOING;
                $controller->callStarted($call);
                break;
                
            case 'incoming':
                $call->source = $local;
                $call->destination = $remote;
                $call->direction = Phone_Model_Call::TYPE_INCOMING;
                $controller->callStarted($call);
                break;
                
            case 'connected':
                $controller->callConnected($call);
                break;
                
            case 'disconnected':
                $controller->callDisconnected($call);
                break;
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
    
    /**
     * generate URL with query parameters to access this installation again
     *
     * @return string the complete URI http://hostname/path/index.php
     */
    protected function _getBaseUrl()
    {
        $protocol = !empty($_SERVER['HTTPS']) ? 'https://' : 'http://';
        $name = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
        $port = $_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443' ? ':' . $_SERVER['SERVER_PORT'] : '' ;
        
        $baseURL = $protocol . $name . $port . $_SERVER['PHP_SELF'];
        
        return $baseURL;
    }
}