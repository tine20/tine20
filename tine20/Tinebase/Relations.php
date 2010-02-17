<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Relations
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 * 
 * @todo        re-enable the caching (but check proper invalidation first) -> see task #232
 */

/**
 * Class for handling relations between application records.
 * @todo move json api specific stuff into the model
 * 
 * @package     Tinebase
 * @subpackage  Relations 
 */
class Tinebase_Relations
{
    /**
     * @var Tinebase_Relation_Backend_Sql
     */
    protected $_backend;
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Relations
     */
    private static $instance = NULL;
    
    /**
     * the constructor
     *
     */
    private function __construct()
    {
        $this->_backend = new Tinebase_Relation_Backend_Sql();
    }
    /**
     * the singleton pattern
     *
     * @return Tinebase_Relations
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Tinebase_Relations();
        }
        return self::$instance;
    }
    /**
     * set all relations of a given record
     * 
     * NOTE: given relation data is expected to be an array atm.
     * @todo check read ACL for new relations to existing records.
     * 
     * @param  string $_model        own model to get relations for
     * @param  string $_backend      own backend to get relations for
     * @param  string $_id           own id to get relations for 
     * @param  array  $_relationData data for relations to create
     * @param  bool   $_ignoreACL    create relations without checking permissions
     * @return void
     */
    public function setRelations($_model, $_backend, $_id, $_relationData, $_ignoreACL = FALSE)
    {
        $relations = new Tinebase_Record_RecordSet('Tinebase_Model_Relation', $_relationData, true);
        // own id sanitising
        $relations->own_model   = $_model;
        $relations->own_backend = $_backend;
        $relations->own_id      = $_id;
        
        // convert related_record to record objects
        // @todo move this to a relation json class / or to model->setFromJson
        $this->_relatedRecordToObject($relations);
        
        // compute relations to add/delete
        $currentRelations = $this->getRelations($_model, $_backend, $_id, NULL, array(), $_ignoreACL);
        $currentIds   = $currentRelations->getArrayOfIds();
        $relationsIds = $relations->getArrayOfIds();
        
        $toAdd = $relations->getIdLessIndexes();
        $toDel = array_diff($currentIds, $relationsIds);
        $toUpdate = array_intersect($currentIds, $relationsIds);
        
        // add new relations
        foreach ($toAdd as $idx) {
            if(empty($relations[$idx]->related_id)) {
                $this->_setAppRecord($relations[$idx]);
            }
        	$this->_addRelation($relations[$idx]);
        }
        
        // break relations
        foreach ($toDel as $relationId) {
            $this->_backend->breakRelation($relationId);
        }
        
        // update relations
        foreach ($toUpdate as $relationId) {
            $current = $currentRelations[$currentRelations->getIndexById($relationId)];
            $update = $relations[$relations->getIndexById($relationId)];
            
            // @todo do we need to ommit so many fields?
            if (! $current->related_record->isEqual(
                $update->related_record, 
                array(
                    'jpegphoto', 
                    'creation_time', 
                    'last_modified_time',
                    'created_by',
                    'last_modified_by',
                    'is_deleted',
                    'deleted_by',
                    'deleted_time',
                    'tags',
                    'notes',
                )
            )) {
                //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($current->related_record->toArray(), true));
                //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($update->related_record->toArray(), true));
                $this->_setAppRecord($update);
            }
            
            if (!$current->isEqual($update, array('related_record'))) {
                $this->_updateRelation($update);
            }
            
        }
        
        // remove relations from cache
        #$cache = Tinebase_Core::get('cache');
        #$result = $cache->remove('getRelations' . $_model . $_backend . $_id);
    }
    
    /**
     * get all relations of a given record
     * - cache result if caching is activated
     * 
     * @param  string       $_model     own model to get relations for
     * @param  string       $_backend   own backend to get relations for
     * @param  string|array $_id        own id to get relations for
     * @param  string       $_degree    only return relations of given degree
     * @param  array        $_type      only return relations of given type
     * @param  bool         $_ignoreACL get relations without checking permissions
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Relation
     */
    public function getRelations($_model, $_backend, $_id, $_degree = NULL, array $_type = array(), $_ignoreACL = FALSE)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . "  model: '$_model' backend: '$_backend' " 
            // . 'ids: ' . print_r((array)$_id, true)
        );
    
        $result = $this->_backend->getAllRelations($_model, $_backend, $_id, $_degree, $_type);
        $this->resolveAppRecords($result, $_ignoreACL);
            
        return $result;
    }
    
    /**
     * get all relations of all given records
     * 
     * @param  string $_model     own model to get relations for
     * @param  string $_backend   own backend to get relations for
     * @param  array  $_ids       own ids to get relations for
     * @param  string $_degree    only return relations of given degree
     * @param  array  $_type      only return relations of given type
     * @param  bool   $_ignoreACL get relations without checking permissions
     * @return array  key from $_ids => Tinebase_Record_RecordSet of Tinebase_Model_Relation
     */
    public function getMultipleRelations($_model, $_backend, $_ids, $_degree = NULL, array $_type = array(), $_ignoreACL = FALSE)
    {
        // prepare a record set for each given id
        $result = array();
        foreach ($_ids as $key => $id) {
            $result[$key] = new Tinebase_Record_RecordSet('Tinebase_Model_Relation', array(),  true);
        }
        
        // fetch all relations in a single set
        $relations = $this->getRelations($_model, $_backend, $_ids, $_degree, $_type, $_ignoreACL);
        
        // sort relations into corrensponding sets
        foreach ($relations as $relation) {
            $keys = array_keys($_ids, $relation->own_id);
            foreach ($keys as $key) {
                $result[$key]->addRecord($relation);
            }
        }
        
        return $result;
    }
    
    /**
     * converts related_records into their appropriate record objects
     * @todo move to model->setFromJson
     * 
     * @param  Tinebase_Model_Relation|Tinebase_Record_RecordSet
     * @return void
     */
    protected function _relatedRecordToObject($_relations)
    {
        if(! $_relations instanceof Tinebase_Record_RecordSet) {
            $_relations = new Tinebase_Record_RecordSet('Tinebase_Model_Relation', array($_relations));
        }
        
        foreach ($_relations as $relation) {
            if (empty($relation->related_record) || $relation->related_record instanceof  $relation->related_model) {
                continue;
            }
            
            // records need to be presented as JSON strings
            if (is_array($relation->related_record)) {
                $json = Zend_Json::encode($relation->related_record);
            }
            $relation->related_record = new $relation->related_model();
            $relation->related_record->setFromJsonInUsersTimezone($json);
        }
    }
    
    /**
     * creates application records which do not exist
     * 
     * @param   Tinebase_Record_RecordSet of Tinebase_Model_Relation
     * @throws  Tinebase_Exception_UnexpectedValue
     * 
     * @todo    allowed related models should not be defined here
     */
    protected function _setAppRecord($_relation)
    {
        list($appName, $i, $itemName) = explode('_', $_relation->related_model);
        $appController = Tinebase_Core::getApplicationInstance($appName, $itemName);
        
        if (!$_relation->related_record->getId()) {
            $method = 'create';
        } else {
            $method = 'update';
        }

        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' ' . ucfirst($method) . ' ' . $_relation->related_model . ' record.');
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_relation->toArray(), TRUE));
        
        $record = $appController->$method($_relation->related_record);
        $_relation->related_id = $record->getId();
        
        switch ($_relation->related_model) {
            case 'Addressbook_Model_Contact':
                $_relation->related_backend = Addressbook_Backend_Factory::SQL;
                break;
            case 'Tasks_Model_Task':
                $_relation->related_backend = Tasks_Backend_Factory::SQL;
                break;
            case 'Sales_Model_Product':
                $_relation->related_backend = Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND;
                break;
            default:
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Unsupported related model ' . $_relation->related_model . '. Using default backend (Sql).');
                $_relation->related_backend = Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND;
                //throw new Tinebase_Exception_UnexpectedValue('Related model not supported.');
                break;
        }
    }
    
    /**
     * resolved app records and filles the related_record property with the coresponding record
     * 
     * NOTE: With this, READ ACL is implicitly checked as non readable records woun't get retuned!
     * 
     * @param  Tinebase_Record_RecordSet $_relations of Tinebase_Model_Relation
     * @param  boolean $_ignoreACL 
     * @return void
     * 
     * @todo    make getApplicationInstance work for tinebase record (Tinebase_Model_User for example)
     */
    protected function resolveAppRecords($_relations, $_ignoreACL = FALSE)
    {
        // seperate relations by model
        $modelMap = array();
        foreach ($_relations as $relation) {
            if (!array_key_exists($relation->related_model, $modelMap)) {
                $modelMap[$relation->related_model] = new Tinebase_Record_RecordSet('Tinebase_Model_Relation');
            }
            $modelMap[$relation->related_model]->addRecord($relation);
        }
        
        // fill related_record
        foreach ($modelMap as $modelName => $relations) {
            if ($modelName === 'Tinebase_Model_User') {
                // @todo add related backend here
                //$appController = Tinebase_User::factory($relations->related_backend);
                $appController = Tinebase_User::factory(Tinebase_User::getConfiguredBackend());
            } else {
                list($appName, $i, $itemName) = explode('_', $modelName);
                $appController = Tinebase_Core::getApplicationInstance($appName, $itemName);
            }
            
            $getMultipleMethod = 'getMultiple';
            
            $records = $appController->$getMultipleMethod($relations->related_id, $_ignoreACL);
            
            foreach ($relations as $relation) {
                $recordIndex    = $records->getIndexById($relation->related_id);
                $relationIndex  = $_relations->getIndexById($relation->getId());
                if ($recordIndex !== false) {
                    $_relations[$relationIndex]->related_record = $records[$recordIndex];
                } else {
                    // delete relation from set, as READ ACL is abviously not granted 
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                        " removing $relation->related_model $relation->related_backend $relation->related_id (ACL)");
                    unset($_relations[$relationIndex]);
                }
            }
        }
    }
    
    /**
     * get list of relations
     *
     * @param Tinebase_Model_Filter_FilterGroup|optional $_filter
     * @param Tinebase_Model_Pagination|optional $_pagination
     * @param boolean $_onlyIds
     * @return Tinebase_Record_RecordSet|array
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL, $_onlyIds = FALSE)
    {
        return $this->_backend->search($_filter, $_pagination, $_onlyIds);
    }
    
    /**
     * adds a new relation
     * 
     * @param   Tinebase_Model_Relation $_relation 
     * @return  Tinebase_Model_Relation|NULL the new relation
     * @throws  Tinebase_Exception_Record_Validation
     */
    protected function _addRelation($_relation)
    {
        $_relation->created_by = Tinebase_Core::getUser()->getId();
        $_relation->creation_time = Zend_Date::now();
        if (!$_relation->isValid()) {
            throw new Tinebase_Exception_Record_Validation('Relation is not valid' . print_r($_relation->getValidationErrors(),true));
        }
        
        try {
            $result = $this->_backend->addRelation($_relation);            
        } catch(Zend_Db_Statement_Exception $zse) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not add relation: ' . $zse->getMessage());
            $result = NULL;
        }
        
        return $result;
    }
    
    /**
     * update an existing relation
     * 
     * @param  Tinebase_Model_Relation $_relation 
     * @return Tinebase_Model_Relation the updated relation
     */
    protected function _updateRelation($_relation)
    {
        $_relation->last_modified_by = Tinebase_Core::getUser()->getId();
        $_relation->last_modified_time = Zend_Date::now();
        
        return $this->_backend->updateRelation($_relation);
    }
}