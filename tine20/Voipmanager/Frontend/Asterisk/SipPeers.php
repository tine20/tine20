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
 * backend class for Zend_Http_Server to handle Asterisk realtime sippeers curl backend
 *
 * @package     Voipmanager Management
 */
class Voipmanager_Frontend_Asterisk_SipPeers
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

        $skipItems = array('id');
        
        $resultParts = array();
        
        foreach($sipPeer as $key => $value) {
            if(in_array($key, $skipItems)) {
                continue;
            }
            $resultParts[] = $key . '=' . urlencode($value);  
        }
        
        $result = implode('&', $resultParts);
        
        #Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' result ' . $result);
        
        echo $result . "\n";
    }
    
    /**
     * handle require
     * 
     * just return 0 as we support all needed fields
     *
     */
    public function handleRequire()
    {
        #Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' $_REQUEST = ' . print_r($_REQUEST, true));
        
        echo "0\n";
    }
    
}