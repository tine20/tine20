<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  State
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
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
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_State', 
            'tableName' => 'state',
        ));
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
        if (! Tinebase_Core::getUser()->hasRight('Tinebase', Tinebase_Acl_Rights::MANAGE_OWN_STATE)) {
            throw new Tinebase_Exception_AccessDenied("You don't have the right to manage your client state");
        }
        
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
        if (! Tinebase_Core::getUser()->hasRight('Tinebase', Tinebase_Acl_Rights::MANAGE_OWN_STATE)) {
            throw new Tinebase_Exception_AccessDenied("You don't have the right to manage your client state");
        }
        
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
        if (! Tinebase_Core::getUser()->hasRight('Tinebase', Tinebase_Acl_Rights::MANAGE_OWN_STATE)) {
            throw new Tinebase_Exception_AccessDenied("You don't have the right to manage your client state");
        }
        
        
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
    
    /**
     * decoder for the extjs state encoder
     * 
     * @param  mixed $_value
     * @return mixed
     */
    public static function decode($_value)
    {
        $val = urldecode($_value);
        list ($type, $data) = explode(':', $val);
        
        switch ($type) {
            case 'a': //array
                $array = array();
                
                $entries = explode('^', $data);
                foreach ($entries as $entry) {
                    $array[] = self::decode($entry);
                }
                return $array;
                break;
                
            case 'n': // number
            case 's': //string
                return $data;
                break;
                
            case 'o': //object
                $object = array();
                
                $entries = explode('^', $data);
                foreach ($entries as $entry) {
                    list ($p, $v) = explode ('=', $entry);
                    $object[$p] = self::decode($v);
                }
                return $object;
                break;
        }
    }
}
