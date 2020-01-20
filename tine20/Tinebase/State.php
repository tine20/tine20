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
     * @throws Tinebase_Exception_AccessDenied
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
     * @throws Tinebase_Exception_AccessDenied
     * @throws Exception
     */
    public function setState($_name, $_value)
    {
        if (! Tinebase_Core::getUser()->hasRight('Tinebase', Tinebase_Acl_Rights::MANAGE_OWN_STATE)) {
            throw new Tinebase_Exception_AccessDenied("You don't have the right to manage your client state");
        }
        
        $userId = Tinebase_Core::getUser()->getId();
        $db = Tinebase_Core::getDb();

        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);

            $results = $this->_backend->search($this->_getFilter($_name, $userId));

            if ($results->count() == 0) {
                $record = new Tinebase_Model_State(array(
                    'user_id' => $userId,
                    'state_id' => $_name,
                    'data' => $_value
                ));
                $this->_backend->create($record);
            } else {
                $record = $results->getFirstRecord();
                $record->data = $_value;
                $this->_backend->update($record);
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
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
     * @param $raw Ext encoded state
     * @return array|bool|Tinebase_DateTime
     */
    public static function decode($raw)
    {
        if (preg_match('/^(a|n|d|b|s|o)\:(.*)$/', urldecode($raw), $matches)) {
            $type = $matches[1];
            $v = $matches[2];

            switch($type){
                case "n":
                    return floatval($v);
                case "d":
                    return new Tinebase_DateTime($v);
                case "b":
                    return ($v == "1");
                case "a":
                    $all = [];
                    if($v != ''){
                        foreach (explode('^', $v) as $val) {
                            $all[] = self::decode($val);
                        }
                    }
                    return $all;
                case "o":
                    $all = [];
                    if($v != ''){
                        foreach (explode('^', $v) as $val) {
                            $kv = explode('=', $val);
                            $all[$kv[0]] = self::decode($kv[1]);
                        }
                    }
                    return $all;
                default:
                    return $v;
            }
        }
    }

    /**
     * @param $state
     * @return string
     */
    public static function encode($state)
    {
        $enc = '';
        if(is_numeric($state)) {
            $enc = "n:" . $state;
        } else if(is_bool($state)) {
            $enc = "b:" . ($state ? "1" : "0");
        } else if($state instanceof DateTime){
            $enc = "d:" . $state->format('D, d M Y H:i:s') . ' GMT';
        } else if(is_array($state)) {
            $flat = "";
            if (! count(array_filter(array_keys($state), 'is_string'))) {
                // numeric keys
                foreach($state as $key => $val) {
                    $flat .= self::encode($val) . '^';
                }
                $enc = "a:" . $flat;
            } else {
                // string keys
                foreach($state as $key => $val) {
                    if ($val) {
                        $flat .= $key . '=' . self::encode($val) . '^';
                    }
                }
                $enc = "o:" . $flat;
            }

            $enc = substr($enc, 0, -1);
        } else {
            $enc = "s:". $state;
        }

        return urlencode($enc);
    }
}
