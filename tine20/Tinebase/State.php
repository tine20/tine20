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
 * controller for State management
 *
 * @package     Tinebase
 * @subpackage  State
 */
class Tinebase_State
{
    /**
     * @var Tinebase_State_Backend
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
        $this->_backend = new Tinebase_State_Backend();
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
                'data'      => $_stateData
            ));
            $this->_backend->create($stateRecord);
        }
        
        $stateRecord->data = $_stateData;
        $this->_backend->update($stateRecord);
    }

    /**
     * load state data
     *
     * @return array
     */
    public function loadStateInfo()
    {
        $userId = Tinebase_Core::getUser()->getId();
        
        try {
            $state = $this->_backend->getByProperty($userId, 'user_id');
            $result = Zend_Json::decode($state->data);
        } catch (Tinebase_Exception_NotFound $tenf) {
            $result = array();
        }
        
        return $result;
    }
}
