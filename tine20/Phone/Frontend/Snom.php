<?php
/**
 * Tine 2.0
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * authenticate and store result in session to avoid sending any request
     * twice. The SSL handshake for SNOM 320 takes very long
     */
    protected function _authenticate()
    {
        if (Zend_Session::isStarted()) {
            $snomSession = new Zend_Session_Namespace('snomPhone');
            
            if (isset($snomSession->phoneIsAuthenticated)) {
                return;
            }
        }
        
        parent::_authenticate();
        
        if (!Zend_Session::isStarted()) {
            Tinebase_Core::startSession('snomPhone');
        }
        
        $snomSession = new Zend_Session_Namespace('snomPhone');
        $snomSession->phoneIsAuthenticated = 1;
    }
    /**
     * public function to access the directory
     * 
     * @param string $mac
     */
    public function directory($mac)
    {
        $this->_authenticate();
        
        # get the phone
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' phone ' . $mac);
        $phone = Voipmanager_Controller_Snom_Phone::getInstance()->getByMacAddress($mac);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' phone ' . $phone->template_id);
        $template = Voipmanager_Controller_Snom_Template::getInstance()->get($phone->template_id);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' phone ' . $template->setting_id);
        $settings = Voipmanager_Controller_Snom_Setting::getInstance()->get($template->setting_id);
        
        
        $language = $settings->language ? $settings->language : 'en';
        $translate = Tinebase_Translation::getTranslation($this->_applicationName, new Zend_Locale('de'));
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' phone ' . $language);
        
        $baseUrl = $this->_getBaseUrl();
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <SnomIPPhoneInput>
                <Prompt>Prompt</Prompt>
                <URL>' . $baseUrl . '</URL>
                <InputItem>
                    <DisplayName>' . $translate->_('Enter search') . ':</DisplayName>
                    <QueryStringParam>method=Phone.searchContacts&TINE20SESSID=' . Zend_Session::getId() . '&mac=' . $mac . '&query</QueryStringParam>
                    <DefaultValue/>
                    <InputFlags>a</InputFlags>
                </InputItem>
            </SnomIPPhoneInput>
        ';
        
        header('Content-Type: text/xml');
        
        echo $xml;
    }
    
    /**
     * public function to access the directory
     * 
     * @param string $mac
     */
    public function menu($mac, $activeLine)
    {
        $this->_authenticate();
        
        # get the phone
        $phone = Voipmanager_Controller_Snom_Phone::getInstance()->getByMacAddress($mac);
        
        # get the asterisk line
        $sipPeer = Voipmanager_Controller_Asterisk_SipPeer::getInstance()->get($phone->lines->find('linenumber', $activeLine)->asteriskline_id);
        
        $baseUrl = $this->_getBaseUrl($phone);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <SnomIPPhoneMenu>
                <Title>Menu</Title>
                <MenuItem>
                    <Name>Call Forward</Name>
                    <URL>' . $baseUrl . '?method=Phone.getCallForward&TINE20SESSID=' . Zend_Session::getId() . '&activeLine=' . $activeLine . '&mac=' . $mac . '</URL>
                </MenuItem>
                <MenuItem>
                    <Name>Addressbook</Name>
                    <URL>' . $baseUrl . '?method=Phone.directory&TINE20SESSID=' . Zend_Session::getId() . '&mac=' . $mac . '</URL>
                </MenuItem>
            </SnomIPPhoneMenu>
        ';
    
        header('Content-Type: text/xml');
        
        echo $xml;
    }
    
    /**
     * get current call forward settings
     * 
     * @param string $mac
     * @param string $activeLine
     */
    public function getCallForward($mac, $activeLine)
    {
        $this->_authenticate();
        
        # get the phone
        $phone = Voipmanager_Controller_Snom_Phone::getInstance()->getByMacAddress($mac);
        
        # get the asterisk line
        $sipPeer = Voipmanager_Controller_Asterisk_SipPeer::getInstance()->get($phone->lines->find('linenumber', $activeLine)->asteriskline_id);
                
        $baseUrl = $this->_getBaseUrl();
        
        $ipPhoneInput = new Voipmanager_Snom_XML_IPPhoneInput();
        $ipPhoneInput->setText('Call Forward');

        // cfi off key
        $softKeyItem = $ipPhoneInput->addSoftKeyItem('F1');
        if ($sipPeer->cfi_mode == Voipmanager_Model_Asterisk_SipPeer::CFMODE_OFF) {
            $softKeyItem->setLabel('*Off');
            $softKeyItem->setSoftKey('F_ABORT');
        } else {
            $softKeyItem->setLabel('Off');
            $softKeyItem->setURL($baseUrl . '?method=Phone.setCallForward&TINE20SESSID=' . Zend_Session::getId() . '&number=&mode=' . Voipmanager_Model_Asterisk_SipPeer::CFMODE_OFF . '&activeLine=' . $activeLine . '&mac=' . $mac);
        }
        
        // cfi number key
        $softKeyItem = $ipPhoneInput->addSoftKeyItem('F3');
        $softKeyItem->setLabel( (($sipPeer->cfi_mode == Voipmanager_Model_Asterisk_SipPeer::CFMODE_NUMBER) ? '*' : null) . 'Number' );
        $softKeyItem->setURL($baseUrl . '?method=Phone.setCallForward&TINE20SESSID=' . Zend_Session::getId() . '&number=&mode=' . Voipmanager_Model_Asterisk_SipPeer::CFMODE_NUMBER . '&activeLine=' . $activeLine . '&mac=' . $mac);        
        
        // cfi mailbox key
        $softKeyItem = $ipPhoneInput->addSoftKeyItem('F4');
        if ($sipPeer->cfi_mode == Voipmanager_Model_Asterisk_SipPeer::CFMODE_VOICEMAIL) {
            $softKeyItem->setLabel('*Mailbox');
            $softKeyItem->setSoftKey('F_ABORT');
        } else {
            $softKeyItem->setLabel('Mailbox');
            $softKeyItem->setURL($baseUrl . '?method=Phone.setCallForward&TINE20SESSID=' . Zend_Session::getId() . '&number=&mode=' .  Voipmanager_Model_Asterisk_SipPeer::CFMODE_VOICEMAIL . '&activeLine=' . $activeLine . '&mac=' . $mac);
        }
        
        header('Content-Type: text/xml');
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' phone ' . $ipPhoneInput->saveXML());
        echo $ipPhoneInput->saveXML();
    }
    
    /**
     * set call forwarding immediate
     * 
     * @param string $mac
     * @param string $activeLine
     */
    public function setCallForward($mac, $activeLine, $mode, $number)
    {
        $this->_authenticate();
        
        # get the phone
        $phone = Voipmanager_Controller_Snom_Phone::getInstance()->getByMacAddress($mac);
        
        # get the asterisk line
        $sipPeer = Voipmanager_Controller_Asterisk_SipPeer::getInstance()->get($phone->lines->find('linenumber', $activeLine)->asteriskline_id);
        
        $baseUrl = $this->_getBaseUrl();
        $doc = new DOMDocument('1.0', 'utf-8');
        
        if ($mode == Voipmanager_Model_Asterisk_SipPeer::CFMODE_NUMBER) {
            if (empty($number)) {
                $doc->appendChild($doc->createElement('SnomIPPhoneInput'));
                
                $doc->documentElement->appendChild($doc->createElement('Prompt', 'Prompt'));
                $urlElement = $doc->createElement('URL');
                $urlElement->appendChild($doc->createTextNode($baseUrl));
                $doc->documentElement->appendChild($urlElement);
                
                $inputItem = $doc->documentElement->appendChild($doc->createElement('InputItem'));
                $inputItem->appendChild($doc->createElement('DisplayName', 'Number:'));
                $inputItem->appendChild($doc->createElement('DefaultValue', $sipPeer->cfi_number));
                $inputItem->appendChild($doc->createElement('InputFlags', 't'));
                
                $queryStringParamElement = $doc->createElement('QueryStringParam');
                $queryStringParamElement->appendChild($doc->createTextNode('method=Phone.setCallForward&TINE20SESSID=' . Zend_Session::getId() . '&mode=' . Voipmanager_Model_Asterisk_SipPeer::CFMODE_NUMBER . '&activeLine=' . $activeLine . '&mac=' . $mac . '&number'));
                $inputItem->appendChild($queryStringParamElement);
                
            } else {
                $sipPeer->cfi_mode   = $mode;
                $sipPeer->cfi_number = $number;
                
                # update the asterisk line
                Voipmanager_Controller_Asterisk_SipPeer::getInstance()->update($sipPeer);
                
                $doc = new Voipmanager_Snom_XML_Exit();
            }
        } else if ($mode == Voipmanager_Model_Asterisk_SipPeer::CFMODE_OFF || $mode == Voipmanager_Model_Asterisk_SipPeer::CFMODE_VOICEMAIL) {
            $sipPeer->cfi_mode = $mode;
            
            # update the asterisk line
            Voipmanager_Controller_Asterisk_SipPeer::getInstance()->update($sipPeer);
            
            $doc = new Voipmanager_Snom_XML_Exit();
        }
        
        header('Content-Type: text/xml');
        #Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' phone ' . $doc->saveXML());
        echo $doc->saveXML();
    }
    
    /**
     * create the search results dialogue
     *
     * @param string $mac the mac address of the phone
     * @param string $query the string to search the contacts for
     */
    public function searchContacts($mac, $query)
    {
        $baseUrl = $this->_getBaseUrl();
        
        // do nothing if search string is empty
        if(empty($query)) {
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
                <SnomIPPhoneText>
                <Title>Nothing found!</Title>
                <Text>Nothing found!</Text>
                <fetch mil="1000">' . $baseUrl . '?method=Phone.directory&TINE20SESSID=' . Zend_Session::getId() . '&mac=' . $mac . '</fetch>
                </SnomIPPhoneText>
            ');
            
            header('Content-Type: text/xml');
            
            echo $xml->asXML();
            
            return;
        }
        
        $this->_authenticate();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' phone ' . $mac. ' search for ' . $query);
            
        $phone = Voipmanager_Controller_Snom_Phone::getInstance()->getByMacAddress($mac);
        
        $contactsBackend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
        
        $tbContainer = Tinebase_Container::getInstance();
        
        $readAbleContainer = array();
        
        foreach($phone->rights as $right) {
            if($right->account_type == Tinebase_Acl_Rights::ACCOUNT_TYPE_USER) {
                $containers = $tbContainer->getContainerByACL($right->account_id, 'Addressbook', Tinebase_Model_Grants::GRANT_READ);
                $readAbleContainer = array_merge($readAbleContainer, $containers->getArrayOfIds());
            }
        }
        $readAbleContainer = array_unique($readAbleContainer);
        
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'query', 'operator' => 'contains', 'value' => $query),
            array('field' => 'container', 'operator' => 'in', 'value' => $readAbleContainer)
        ));
        
        $contacts = $contactsBackend->search($filter, new Tinebase_Model_Pagination());
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' found ' . count($contacts) . ' contacts');
        
        if(count($contacts) == 0) {
            $baseUrl = $this->_getBaseUrl();
            $xml = '<SnomIPPhoneText>
                <Title>Nothing found!</Title>
                <Text>Nothing found!</Text>
                <fetch mil="1000">' . $baseUrl . '?method=Phone.directory&TINE20SESSID=' . Zend_Session::getId() . '&mac=' . $mac . '</fetch>
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
        // there is no need to start session for call history
        // it's a single shot request
        parent::_authenticate();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Event: $event CallId: $callId Local: $local Remote: $remote ");
        
        $phone = Voipmanager_Controller_Snom_Phone::getInstance()->getByMacAddress($mac);
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
