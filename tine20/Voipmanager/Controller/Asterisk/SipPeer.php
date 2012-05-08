<?php
/**
 * Asterisk_SipPeer controller for Voipmanager Management application
 *
 * @package     Voipmanager
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
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
    * prefix for cache id
    *
    * @var string
    */
    protected $_cacheIdPrefix = 'asteriskSipPeer';
    
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
     * (non-PHPdoc)
     * @see Tinebase/Controller/Record/Tinebase_Controller_Record_Abstract#create($_record)
     */
    public function create(Tinebase_Record_Interface $_record)
    {
        $this->_cache->clean('all', array('asteriskSipPeer'));
        
        $result =  parent::create($_record);
        
        if(isset(Tinebase_Core::getConfig()->asterisk)) {
            $this->publishConfiguration();
        }
        
        return $result;
    }

    /**
     * (non-PHPdoc)
     * @see Tinebase/Controller/Record/Tinebase_Controller_Record_Abstract#delete($_ids)
     */
    public function delete($_ids)
    {
        $this->_cache->clean('all', array('asteriskSipPeer'));
        
        $result = parent::delete($_ids);
        
        if(isset(Tinebase_Core::getConfig()->asterisk)) {
            $this->publishConfiguration();
        }
        
        return $result;
    }
    
    /**
    * inspect update of one record (after update)
     *
    * @param   Tinebase_Record_Interface $_updatedRecord   the just updated record
    * @param   Tinebase_Record_Interface $_record          the update record
    * @return  void
    */
    protected function _inspectAfterUpdate($_updatedRecord, $_record)
    {
        if (isset(Tinebase_Core::getConfig()->asterisk)) {
            self::publishConfiguration();
        }
    }
    
    /**
     * create sip.conf and upload to asterisk server
     * 
     * @return void
     */
    public static function publishConfiguration()
    {
        if (isset(Tinebase_Core::getConfig()->asterisk)) {
            $asteriskConfig = Tinebase_Core::getConfig()->asterisk;
            
            $url        = $asteriskConfig->managerbaseurl;
            $username   = $asteriskConfig->managerusername;
            $password   = $asteriskConfig->managerpassword;
        } else {
            throw new Voipmanager_Exception_NotFound('No settings found for asterisk backend in config file!');
        }
        
        /*
        $filter = new Voipmanager_Model_Asterisk_SipPeerFilter(array());
        
        $sipPeers = $controller = Voipmanager_Controller_Asterisk_SipPeer::getInstance()->search($filter);
        
        $fieldsToSkip = array('id', 'name', 'fullcontact', 'username');
        
        $fp = fopen("php://temp", 'r+');
        foreach($sipPeers as $sipPeer) {
            fputs($fp, "[" . $sipPeer->name . "]\n");
            foreach($sipPeer as $key => $value) {
                if(empty($value) || in_array($key, $fieldsToSkip)) {
                    continue;
                }
                fputs($fp, " $key = $value\n");
            }
            fputs($fp, "\n");
        }
        rewind($fp);
        */
        try {
            $ajam = new Ajam_Connection($url);
            $ajam->login($username, $password);
            #$ajam->upload($url . '/tine20config', 'sip.conf', stream_get_contents($fp));
            $ajam->command('sip reload');
            $ajam->logout();
        } catch (Exception $e) {
            throw new Tinebase_Exception_Backend($e->getMessage());
        }
    }
}
