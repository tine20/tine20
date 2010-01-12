<?php
/**
 * VoIP Monitor Deamon
 * 
 * @package     VoipMonitor
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * abstract class for VoipMonitor frontends
 * 
 * @package     VoipMonitor
 */
class VoipMonitor_Backend_Tine20 extends VoipMonitor_Backend_Abstract
{
    protected $_tine20;
    
    protected $_voipManager;
    
    protected $_peerIdFifo = array();
    
    /**
     * the constructor
     * 
     * @param string  $_host
     * @param int     $_port
     */
    public function __construct(Zend_Config $_config)
    {
        parent::__construct($_config);
                      
        $this->_getTine20Connection($_config);
    }
    
    public function update(array $_event)
    {
        switch($_event['Event']) {
            case 'PeerStatus':
                $this->_handlePeerStatus($_event);
                break;
                
            default:
                echo "Unknow Event: {$_event['Event']}" . PHP_EOL;
                print_r($_event);
        }
    }
    
    protected function _handlePeerStatus($_event)
    {
        $voipManager = $this->_tine20->getProxy('Voipmanager');

        $peerName = substr($_event['Peer'], 4);
        
        if(array_key_exists($peerName, $this->_peerIdFifo)) {
            $peerId = $this->_peerIdFifo[$peerName];
        } else {
            $filter = array('filter' => array(
                'field'    => 'name',
                'operator' => 'equals',
                'value'    => $peerName
            ));
            
            $result = $voipManager->searchAsteriskSipPeers($filter);
            
            if($result['totalcount'] == 1) {
                $sipPeer = $result['results'][0];
                $this->_peerIdFifo[$peerName] = $sipPeer['id'];
                $peerId = $this->_peerIdFifo[$peerName];
            } else {
                $this->_peerIdFifo[$peerName] = null;
                $peerId = null;
            }
        }
        
        if($peerId !== null) {
            $sipPeer = $result['results'][0];
            $update['regseconds'] = Zend_Date::now()->get('yyyy-MM-dd HH:mm:ss');
            $update['regserver']  = 'phonebox02';
            
            $result = $voipManager->updatePropertiesAsteriskSipPeer($peerId, $update);
            //var_dump($result);
        }
    }
    
    protected function _getTine20Connection(Zend_Config $_config)
    {
        $url      = $_config->get('url');
        $username = $_config->get('username');
        $password = $_config->get('password');
        
        $this->_tine20 = new Zend_Service_Tine20($url);
        $this->_tine20->login($username, $password);
    }
    
    public function logout()
    {
        $this->_tine20->logout();
    }
}

