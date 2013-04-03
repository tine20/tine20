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
     * returns a filter for searching in backend by stateId and the current user
     * 
     * @param string $stateId
     * @param string $userId
     * @return Tinebase_Model_StateFilter
     */
    protected function _getFilter($stateId, $userId = NULL)
    {
        if (! $userId) {
            $userId = Tinebase_Core::getUser()->getId();
        }
        
        return new Tinebase_Model_StateFilter(array(
            array('field' => 'state_id', 'operator' => 'equals', 'value' => $stateId),
            array('field' => 'user_id',  'operator' => 'equals', 'value' => $userId)
        ));
    }
    
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
        
        $recordToDelete = $this->_backend->search($this->_getFilter($_name))->getFirstRecord();
        
        if ($recordToDelete) {
            $this->_backend->delete($recordToDelete->getId());
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
        
        $userId = Tinebase_Core::getUser()->getId();
        
        $results = $this->_backend->search($this->_getFilter($_name, $userId));
        
        if ($results->count() == 0) {
            $record = new Tinebase_Model_State(array(
                'user_id'   => $userId,
                'state_id'  => $_name,
                'data'      => $_value
            ));
            $this->_backend->create($record);
        } else {
            $record = $results->getFirstRecord();
            $record->data = $_value;
            $this->_backend->update($record);
        }
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
            $states = $this->_backend->search(new Tinebase_Model_StateFilter(array(
                array('field' => 'user_id', 'operator' => 'equals', 'value' => $userId)
            )));
            foreach ($states as $stateRecord) {
                $result[$stateRecord->state_id] = $stateRecord->data;
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
