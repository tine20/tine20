<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  State
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Preference.php 7161 2009-03-04 14:27:07Z p.schuele@metaways.de $
 * 
 */

/**
 * controller for Extjs client State management
 *
 * @package     Tinebase
 * @subpackage  State
 */
class Tinebase_State
{
    /**
     * @var Tinebase_Backend_Sql
     */
    protected $_backend;
    
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_State
     */
    private static $instance = NULL;
    
    /**
     * the constructor
     *
     */
    private function __construct()
    {
        $this->_backend = new Tinebase_Backend_Sql('Tinebase_Model_State', 'state');
    }
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_State
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Tinebase_State();
        }
        return self::$instance;
    }
    
    /**************************** public functions *********************************/
    
    /**
     * clears a single state entry
     * 
     * @param string $_name
     * @return void
     */
    public function clearState($_name)
    {
        $stateInfo = $this->loadStateInfo();
        
        if (array_key_exists($_name, $stateInfo)) {
            unset($stateInfo[$_name]);
            $this->saveStateInfo($stateInfo);
        }
    }
    
    /**
     * saves a single state entry
     * 
     * @param string $_name
     * @param string $_value
     * @return void
     */
    public function setState($_name, $_value)
    {
        $stateInfo = $this->loadStateInfo();
        
    	$stateInfo[$_name] = $_value;
        $this->saveStateInfo($stateInfo);
    }
    
    /**
     * save state data
     *
     * @param JSONstring $_stateData
     */
    public function saveStateInfo($_stateData)
    {
        $userId = Tinebase_Core::getUser()->getId();
        
        try {
            $stateRecord = $this->_backend->getByProperty($userId, 'user_id');
        } catch (Tinebase_Exception_NotFound $tenf) {
            $stateRecord = new Tinebase_Model_State(array(
                'user_id'   => $userId,
                'data'      => Zend_Json::encode($_stateData)
            ));
            $this->_backend->create($stateRecord);
        }
        
        $stateRecord->data = Zend_Json::encode($_stateData);
        $this->_backend->update($stateRecord);
    }

    /**
     * load state data
     *
     * @return array
     */
    public function loadStateInfo()
    {
        $result = array();
        
        if (Tinebase_Core::getUser()) {
            $userId = Tinebase_Core::getUser()->getId();
            try {
                $state = $this->_backend->getByProperty($userId, 'user_id');
                $result = Zend_Json::decode($state->data);
            } catch (Tinebase_Exception_NotFound $tenf) {
                // no state found
            }
        }
        
        return $result;
    }
}
