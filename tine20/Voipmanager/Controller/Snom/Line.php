<?php
/**
 * Snom_Line controller for Voipmanager Management application
 *
 * @package     Voipmanager
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Snom_Line controller class for Voipmanager Management application
 * 
 * @package     Voipmanager
 * @subpackage  Controller
 */
class Voipmanager_Controller_Snom_Line extends Voipmanager_Controller_Abstract
{
    /**
     * holds the instance of the singleton
     *
     * @var Voipmanager_Controller_Snom_Line
     */
    private static $_instance = NULL;
    
    /**
     * Voipmanager backend class
     *
     * @var Voipmanager_Backend_Snom_Line
     */
    protected $_backend;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        $this->_backend      = new Voipmanager_Backend_Snom_Line();
        $this->_cache        = Zend_Registry::get('cache');
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
     * @return Voipmanager_Controller_Snom_Line
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Voipmanager_Controller_Snom_Line();
        }
        
        return self::$_instance;
    }

    /**
     * delete lines(s) identified by phone id
     *
     * @param string|Voipmanager_Model_Snom_Phone $_phoneId
     * @return void
     */
    public function deletePhoneLines($_phoneId)
    {
        $this->_backend->deletePhoneLines($_phoneId);
    }
    
    /**
     * get snom_phone_line by id
     *
     * @param string $_id the id of the line
     * @return Voipmanager_Model_Snom_Line
     */
    public function get($_id)
    {
        $id = Voipmanager_Model_Snom_Line::convertSnomLineIdToInt($_id);
        if (($result = $this->_cache->load('snomPhoneLine_' . $id)) === false) {
            $result = $this->_backend->get($id);
            $this->_cache->save($result, 'snomPhoneLine_' . $id, array('snomPhoneLine'), 5);
        }
        
        return $result;
    }            
}
