<?php
/**
 * Tine 2.0
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * backend class for Zend_Http_Server to handle Asterisk realtime sipregs curl backend
 *
 * @package     Voipmanager
 */
class Voipmanager_Frontend_Asterisk_SipRegs
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_applicationName = 'Voipmanager';

    /**
     * handle request for single line
     *
     * @param string $name
     */
    public function handleSingle($name)
    {
        #Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' handle single request for ' . print_r($_REQUEST, true));
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' handle single request for ' . $name);
                
        $filter = new Voipmanager_Model_Asterisk_SipPeerFilter(array(
            array(
                'field'     => 'name',
                'operator'  => 'equals',
                'value'     => $name
            )
        ));
        $sipPeers = Voipmanager_Controller_Asterisk_SipPeer::getInstance()->search($filter);
        
        if(count($sipPeers) == 0) {
            Zend_Registry::get('logger')->warn(__METHOD__ . '::' . __LINE__ . ' sip registration not found name: ' . $name);
            return;
        }
        
        $sipPeer = $sipPeers[0];

        #Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' found sip peer ' . print_r($sipPeer->toArray(), true));

        $items = array('name', 'ipaddr', 'port', 'regseconds', 'defaultuser', 'fullcontact', 'regserver', 'useragent', 'lastms');
        
        $resultParts = array();
        
        foreach($items as $item) {
            if($sipPeer->has($item)) {
                $resultParts[] = $item . '=' . urlencode($sipPeer->$item);
            }  
        }
        
        $result = implode('&', $resultParts);
        
        #Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' result ' . $result);
        
        echo $result . "\n";
    }
    
    /**
     * handle update for sip registration
     *
     * @param string $name
     */
    public function handleUpdate($name)
    {
        #Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . " handle update request " . print_r($_REQUEST, true));
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . " handle update request for " . $name);
        
        $valuesToUpdate = $_POST;
        
        $filter = new Voipmanager_Model_Asterisk_SipPeerFilter(array(
            array(
                'field'     => 'name',
                'operator'  => 'equals',
                'value'     => $name
            )
        ));
        $sipPeers = Voipmanager_Controller_Asterisk_SipPeer::getInstance()->search($filter);
        
        if(count($sipPeers) == 0) {
            Zend_Registry::get('logger')->warn(__METHOD__ . '::' . __LINE__ . ' sip registration not found name: ' . $name);
            return;
        }
        
        $sipPeer = $sipPeers[0];

        #Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' found sip peer ' . print_r($sipPeer->toArray(), true));

        foreach($valuesToUpdate as $key => $value) {
            if($sipPeer->has($key)) {
                $sipPeer->$key = $value;
            }
        }
        
        $sipPeer->dtmfmode= 'rfc2833';
        
        #Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' found sip peer ' . print_r($sipPeer->toArray(), true));
        
        try {
            Voipmanager_Controller_Asterisk_SipPeer::getInstance()->update($sipPeer);
        } catch (Zend_Db_Exception $e) {
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' update failed' . $e->getMessage());
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' update failed' . $e->getTraceAsString());
        }
        
        #Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' update done');
        
        echo "1\n";
    }
    
    /**
     * handle require
     * 
     * just return 0 as we support all needed fields
     * 
     * [name] => char:10ipaddr=char:15port=uinteger2:5regseconds=integer4:11defaultuser=char:10fullcontact=char:35regserver=char:20useragent=char:20lastms=integer4:11
     *
     */
    public function handleRequire()
    {
        #Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' $_REQUEST = ' . print_r($_REQUEST, true));
        
        echo "0\n";
    }    
}