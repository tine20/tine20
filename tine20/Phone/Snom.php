 l<?php
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
     * @todo not yet used -> move it from voipmanager to phone app
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
     * keeps track of the call history
     *
     * @param string $mac the mac address of the phone
     * @param string $event event can be connected, disconnected, incoming, outgoing, missed
     * @param string $callId the callid
     * @param string $local the local number
     * @param string $remote the remote number
     * 
     * @todo use authenticate() from voipmanager
     * @todo make it work!
     */
    public function callHistory($mac, $event, $callId, $local, $remote)
    {
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . " Event: $event CallId: $callId Local: $local Remote: $remote ");
        
        $this->_authenticate();
        
        return;
        
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
    
    
}