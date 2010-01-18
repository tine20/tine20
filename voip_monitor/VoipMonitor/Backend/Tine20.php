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
    
    /**
     * 
     * @var Zend_Cache_Core
     */
    protected $_cache;
    
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
        
        $this->_initializeCache($_config);
    }
    
    protected function _initializeCache(Zend_Config $_config)
    {
        $frontendOptions = array(
            'lifetime'                => 3600,
            'automatic_serialization' => true
        );
        $backendOptions  = array();
        
        if(($path = $_config->get('cachefile')) !== null) {
            $backendType     = 'Sqlite';
            $backendOptions  = array(
                'cache_db_complete_path' => $path
            );
        } elseif(($path = $_config->get('cachedir')) !== null) {
            $backendType     = 'File';
            $backendOptions  = array(
                'cache_dir' => $path
            );
        } else {
            $backendType     = 'Test';
            $frontendOptions = array(
                'caching' => false
            );
        }
        $this->_cache = Zend_Cache::factory('Core',
                             $backendType,
                             $frontendOptions,
                             $backendOptions);
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
        
        $peerId = null;
        $peerName = substr($_event['Peer'], 4);
        $cacheId  = md5('sipPeerId_' . $peerName);
        
        if($this->_cache->test($cacheId)) {
            $peerId = $this->_cache->load($cacheId);
        } else {
            $filter = array('filter' => array(
                'field'    => 'name',
                'operator' => 'equals',
                'value'    => $peerName
            ));
            
            $result = $voipManager->searchAsteriskSipPeers($filter);
            
            if($result['totalcount'] == 1) {
                $sipPeer = $result['results'][0];
                $peerId = $sipPeer['id'];
                
                echo "Cache miss $peerName => $peerId" . PHP_EOL;
                $this->_cache->save($peerId, $cacheId);
            }
        }
        
        if($peerId !== null) {
            $sipPeer = $result['results'][0];
            $update['regseconds'] = Zend_Date::now()->get('yyyy-MM-dd HH:mm:ss');
            $update['regserver']  = 'phonebox02';
            
            try {
                $result = $voipManager->updatePropertiesAsteriskSipPeer($peerId, $update);
            } catch(Zend_Json_Client_FaultException $e) {
                $this->_cache->remove($cacheId);
                fwrite($this->_stdErr, $e->getMessage() . PHP_EOL);
            }
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

