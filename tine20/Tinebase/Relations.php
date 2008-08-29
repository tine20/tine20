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
     * holdes the instance of the singleton
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
        $this->_backend = new Tinebase_Relation_Backend_Sql;
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
     * @param  bool   $_ignoreAcl    create relations without checking permissions
     * @return void
     */
    public function setRelations($_model, $_backend, $_id, $_relationData, $_ignoreAcl=false)
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
        $currentRelations = $this->getRelations($_model, $_backend, $_id, $_ignoreAcl);
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
            
            if (! $current->related_record->isEqual($update->related_record)) {
                $this->_setAppRecord($update);
            }
            
            if (!$current->isEqual($update, array('related_record'))) {
                $this->_updateRelation($update);
            }
            
        }
        
        // remove relations from cache
        #$cache = Zend_Registry::get('cache');
        #$result = $cache->remove('getRelations' . $_model . $_backend . $_id);
    }
    
    /**
     * get all relations of a given record
     * - cache result if caching is activated
     * 
     * @todo support $_ignoreACL? we would need to implement this in app controllers
     * 
     * @param  string $_model     own model to get relations for
     * @param  string $_backend   own backend to get relations for
     * @param  string $_id        own id to get relations for 
     * @param  bool   $_ignoreAcl get relations without checking permissions
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Relation
     */
    public function getRelations($_model, $_backend, $_id, $_ignoreAcl=false)
    {
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . "  get relations $_model, $_backend, $_id");
        #$cache = Zend_Registry::get('cache');
        #$cacheId = 'getRelations' . $_model . $_backend . $_id;
        #$result = $cache->load($cacheId);
        
        #if (!$result) {
    
            $result = $this->_backend->getAllRelations($_model, $_backend, $_id);
            $this->resolveAppRecords($result);

            // save result and tag it with 'container'
            #$cache->save($result, $cacheId, array('relations'));
        #}
            
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
            $relation->related_record->setFromJson($json);
        }
    }
    
    /**
     * creates application records which do not exist
     * 
     * @param  Tinebase_Record_RecordSet of Tinebase_Model_Relation
     * @return void
     */
    protected function _setAppRecord($_relation)
    {
        list($appName, $i, $modelName) = explode('_', $_relation->related_model);
        $appController = Tinebase_Controller::getInstance()->getApplicationInstance($appName);
        
        if (!$_relation->related_record->getId()) {
            $method = 'create' . $modelName;
        } else {
            $method = 'update' . $modelName;
        }
        
        $record = $appController->$method($_relation->related_record);
        $_relation->related_id = $record->getId();
        
        switch ($_relation->related_model) {
            case 'Addressbook_Model_Contact':
                $_relation->related_backend = Addressbook_Backend_Factory::SQL;
                break;
            case 'Tasks_Model_Task':
                $_relation->related_backend = Tasks_Backend_Factory::SQL;
                break;
            default:
                throw new Exception('related model not supportet');
                break;
        }
    }
    
    /**
     * resolved app records and filles the related_record property with the coresponding record
     * 
     * NOTE: With this, READ ACL is implicitly checked as non readable records woun't get retuned!
     * 
     * @param  Tinebase_Record_RecordSet of Tinebase_Model_Relation
     * @return void
     */
    protected function resolveAppRecords($_relations)
    {
        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . "  resolve app records for " . count($_relations) . " relations");
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
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . "  resolving " . count($relations) . " relation(s) of $modelName");
            list($appName, $i, $itemName) = explode('_', $modelName);
            $appController = Tinebase_Controller::getInstance()->getApplicationInstance($appName);
            $getMultipleMethod = 'getMultiple' . $itemName . 's';
            //Zend_Registry::get('logger')->debug('Tinebase_Relations: ' . print_r($relations->related_id, true));
            $records = $appController->$getMultipleMethod($relations->related_id);
            Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . " $appName returned " . count($records) . " record(s)");
            
            foreach ($relations as $relation) {
                $recordIndex    = $records->getIndexById($relation->related_id);
                $relationIndex  = $_relations->getIndexById($relation->getId());
                if ($recordIndex !== false) {
                    $_relations[$relationIndex]->related_record = $records[$recordIndex];
                } else {
                    // delete relation from set, as READ ACL is abviously not granted
                    Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . 
                        " removing $relation->related_model $relation->related_backend $relation->related_id (ACL)");
                    unset($_relations[$relationIndex]);
                }
            }
        }
    }
    
    /**
     * adds a new relation
     * 
     * @param  Tinebase_Model_Relation $_relation 
     * @return Tinebase_Model_Relation the new relation
     */
    protected function _addRelation($_relation)
    {
        $_relation->created_by = Zend_Registry::get('currentAccount')->getId();
        $_relation->creation_time = Zend_Date::now();
        if (!$_relation->isValid()) {
            throw new Exception('relation is not valid' . print_r($_relation->getValidationErrors(),true));
        }
        return $this->_backend->addRelation($_relation);
    }
    
    /**
     * update an existing relation
     * 
     * @param  Tinebase_Model_Relation $_relation 
     * @return Tinebase_Model_Relation the updated relation
     */
    protected function _updateRelation($_relation)
    {
        $_relation->last_modified_by = Zend_Registry::get('currentAccount')->getId();
        $_relation->last_modified_time = Zend_Date::now();
        
        return $this->_backend->updateRelation($_relation);
    }
}