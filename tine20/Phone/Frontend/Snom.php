<?php
/**
 * Tine 2.0
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * backend class for Zend_Http_Server
 *
 * This class handles all Http/XML requests for the Snom telephones
 *
 * @package     Phone
 */
class Phone_Frontend_Snom extends Voipmanager_Frontend_Snom_Abstract
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_applicationName = 'Phone';

    /**
     * public function to access the directory
     * 
     * @param string $mac
     */
    public function directory($mac)
    {
        $baseUrl = $this->_getBaseUrl();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <SnomIPPhoneInput>
                <Prompt>Prompt</Prompt>
                <URL>' . $baseUrl . '</URL>
                <InputItem>
                    <DisplayName>Enter search string:</DisplayName>
                    <QueryStringParam>method=Phone.searchContacts&mac=' . $mac . '&query</QueryStringParam>
                    <DefaultValue/>
                    <InputFlags>a</InputFlags>
                </InputItem>
            </SnomIPPhoneInput>
        ';
    
        header('Content-Type: text/xml');
        echo $xml;
    }
    
    /**
     * create the search results dialogue
     *
     * @param string $mac the mac address of the phone
     * @param string $query the string to search the contacts for
     */
    public function searchContacts($mac, $query)
    {
        // do nothing if no search string got entered
        if(empty($query)) {
            return;
        }
        
        $this->_authenticate();
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' phone ' . $mac. ' search for ' . $query);
            
        $phone = Voipmanager_Controller_Snom_Phone::getInstance()->getByMacAddress($mac);
        
        $contactsBackend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
        
        $tbContainer = Tinebase_Container::getInstance();
        
        $readAbleContainer = array();
        
        foreach($phone->rights as $right) {
            if($right->account_type == Tinebase_Acl_Rights::ACCOUNT_TYPE_USER) {
                $containers = $tbContainer->getContainerByACL($right->account_id, 'Addressbook', Tinebase_Model_Grants::READGRANT);
                $readAbleContainer = array_merge($readAbleContainer, $containers->getArrayOfIds());
            }
        }
        $readAbleContainer = array_unique($readAbleContainer);
        
        $filter = new Addressbook_Model_ContactFilter();
        $filter->container = $readAbleContainer;
        $filter->query = $query;
        
        $contacts = $contactsBackend->search($filter, new Tinebase_Model_Pagination());
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' found ' . count($contacts) . ' contacts');
        
        if(count($contacts) == 0) {
            $baseUrl = $this->_getBaseUrl();
            $xml = '<SnomIPPhoneText>
                <Title>Nothing found!</Title>
                <Text>Nothing found!</Text>
                <fetch mil="1000">' . $baseUrl . '?method=Phone.directory&mac=' . $mac . '</fetch>
            </SnomIPPhoneText>
            ';
        } else {        
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
                if(!empty($contact->tel_cell_private)) {
                    $directoryEntry = $xml->addChild('DirectoryEntry');
                    $directoryEntry->addChild('Name', $contact->n_fileas . ' CellP');
                    $directoryEntry->addChild('Telephone', $contact->tel_cell_private);
                }
            }
            
            $xml = $xml->asXML();
        }

        header('Content-Type: text/xml');
        echo $xml;
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
     * @param string $local the username of the asterisk sip peer
     * @param string $remote the remote number
     * 
     * @todo add correct line_id
     */
    public function callHistory($mac, $event, $callId, $local, $remote)
    {        
        $this->_authenticate();
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Event: $event CallId: $callId Local: $local Remote: $remote ");
        
        $vmController = Voipmanager_Controller_Snom_Phone::getInstance();
        $phone = $vmController->getByMacAddress($mac);
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
            'line_id'       => $local
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
    
}