<?php
/**
 * Asterisk_SipPeer controller for Voipmanager Management application
 *
 * @package     Voipmanager
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Asterisk_SipPeer controller class for Voipmanager Management application
 * 
 * @package     Voipmanager
 * @subpackage  Controller
 */
class Voipmanager_Controller_Asterisk_SipPeer extends Voipmanager_Controller_Abstract
{
    /**
     * holds the instance of the singleton
     *
     * @var Voipmanager_Controller_Asterisk_SipPeer
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        $this->_modelName   = 'Voipmanager_Model_Asterisk_SipPeer';
        $this->_backend     = new Voipmanager_Backend_Asterisk_SipPeer();
        $this->_cache       = Zend_Registry::get('cache');        
    }
        
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {        
    }
            
    /**
     * the singleton pattern
     *
     * @return Voipmanager_Controller_Asterisk_SipPeer
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Voipmanager_Controller_Asterisk_SipPeer();
        }
        
        return self::$_instance;
    }

    /**
     * get asterisk sip peer by id
     *
     * @param string $_id the id of the peer
     * @return Voipmanager_Model_Asterisk_SipPeer
     * 
     * @todo move that to Voipmanager_Controller_Abstract ?
     */
    public function get($_id)
    {
        $id = Voipmanager_Model_Asterisk_SipPeer::convertAsteriskSipPeerIdToInt($_id);
        if (($result = $this->_cache->load('asteriskSipPeer_' . $id)) === false) {
            $result = $this->_backend->get($id);
            $this->_cache->save($result, 'asteriskSipPeer_' . $id, array('asteriskSipPeer'), 5);
        }
        
        return $result;    
    }
    
    public function update(Tinebase_Record_Interface $_record)
    {
        $this->_cache->clean('all', array('asteriskSipPeer'));
        
        $result =  parent::update($_record);
        
        $this->publishConfiguration();
        
        return $result;
    }
    
    public function publishConfiguration()
    {
        $filter = new Voipmanager_Model_Asterisk_SipPeerFilter(array(
            array(
                'field'     => 'name',
                'operator'  => 'equals',
                'value'     => $name
            )
        ));
        $sipPeers = $controller = Voipmanager_Controller_Asterisk_SipPeer::getInstance()->search($filter);     
        
        $fp = fopen("php://temp/maxmemory:$fiveMBs", 'r+');
        foreach($sipPeers as $sipPeer) {
            fputs($fp, "[" . $sipPeer->name . "]\n");
            foreach($sipPeer as $key => $value) {
                fputs($fp, " $key = $value\n");
            }
            fputs($fp, "\n");
        }
        rewind($fp);
        
        $ajam = new Ajam_Connection('http://phonebox01.hh.metaways.de:8088/mxml');
        $ajam->login('tine20', 'tine20');
        $ajam->upload('http://phonebox01.hh.metaways.de:8088/config', 'ssip.conf', stream_get_contents($fp));
        $ajam->logout();
    }
}
