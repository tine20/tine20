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
 */

/**
 * Class for handling relations between application records.
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
        $relations = new Tinebase_Record_RecordSet('Tinebase_Relation_Model_Relation', $_relationData, true);
        // own id sanitising
        $relations->own_model   = $_model;
        $relations->own_backend = $_backend;
        $relations->own_id      = $_id;
        
        // create new records to relate to
        $this->_createNewAppRecords($relations);
        
        // compute relations to add/delete
        $currentRelations = $this->getRelations($_model, $_backend, $_id, $_ignoreAcl);
        $currentIds   = $currentRelations->getArrayOfIds();
        $relationsIds = $relations->getArrayOfIds();
        
        $toAdd = $relations->getIdLessIndexes();
        $toDel = array_diff($currentIds, $relationsIds);
        $toUpdate = array_intersect($currentIds, $relationsIds);
        
        if (!$relations->isValid()) {
            throw new Exception('relations not valid' . print_r($relations->getValidationErrors(),true));
        }
        
        foreach ($toAdd as $idx) {
        	$this->_addRelation($relations[$idx]);
        }
        foreach ($toDel as $relationId) {
            $this->_backend->breakRelation($relationId);
        }
        foreach ($toUpdate as $relationId) {
            $current = $currentRelations[$currentRelations->getIndexById($relationId)];
            $update = $relations[$relations->getIndexById($relationId)];
            
            if (!$current->isEqual($update, array('related_record'))) {
                $this->_updateRelation($update);
            }
            
        }
    }
    /**
     * get all relations of a given record
     * 
     * @todo support $_ignoreACL? we would need to implement this in app controllers
     * 
     * @param  string $_model     own model to get relations for
     * @param  string $_backend   own backend to get relations for
     * @param  string $_id        own id to get relations for 
     * @param  bool   $_ignoreAcl get relations without checking permissions
     * @return Tinebase_Record_RecordSet of Tinebase_Relation_Model_Relation
     */
    public function getRelations($_model, $_backend, $_id, $_ignoreAcl=false)
    {
        $relations = $this->_backend->getAllRelations($_model, $_backend, $_id);
        $this->resolveAppRecords($relations);
        return $relations;
    }
    /**
     * creates application records which do not exist
     * 
     * @param  Tinebase_Record_RecordSet of Tinebase_Relation_Model_Relation
     * @return void
     */
    protected function _createNewAppRecords($_relations)
    {
        foreach ($_relations as $relation) {
            if(empty($relation->related_id)) {
                switch ($relation->related_model) {
                    case 'Addressbook_Model_Contact':
                        $json = new Addressbook_Json();
                        $result = $json->saveContact(Zend_Json::encode($relation->related_record));
                        $relation->related_backend = Addressbook_Backend_Factory::SQL;
                        $relation->related_id = $result['updatedData']['id'];
                        break;
                    case 'Tasks_Model_Task':
                        $json = new Tasks_Json();
                        $task = $json->saveTask(Zend_Json::encode($relation->related_record));
                        $relation->related_backend = Tasks_Backend_Factory::SQL;
                        $relation->related_id = $task['id'];
                        break;
                    default:
                        throw new Exception('related model not supportet');
                        break;
                }
            }
        }
    }
    
    /**
     * resolved app records and filles the related_record property with the coresponding record
     * 
     * NOTE: With this, READ ACL is implicitly checked as non readable records woun't get retuned!
     * 
     * @param  Tinebase_Record_RecordSet of Tinebase_Relation_Model_Relation
     * @return void
     */
    protected function resolveAppRecords($_relations)
    {
        // seperate relations by model
        $modelMap = array();
        foreach ($_relations as $relation) {
            if (!array_key_exists($relation->related_model, $modelMap)) {
                $modelMap[$relation->related_model] = new Tinebase_Record_RecordSet('Tinebase_Relation_Model_Relation');
            }
            $modelMap[$relation->related_model]->addRecord($relation);
        }
        
        // fill related_record
        foreach ($modelMap as $modelName => $relations) {
            list($appName, $i, $modelName) = explode('_', $modelName);
            $appController = Tinebase_Controller::getInstance()->getApplicationInstance($appName);
            $getMultipleMethod = 'getMultiple' . $modelName . 's';
            $records = $appController->$getMultipleMethod($relations->related_id);
            
            foreach ($relations as $relation) {
                $recordIndex    = $records->getIndexById($relation->related_id);
                $relationIndex  = $_relations->getIndexById($relation->getId());
                if ($recordIndex !== false) {
                    $_relations[$relationIndex]->related_record = $records[$recordIndex];
                } else {
                    // delete relation from set, as READ ACL is abviously not granted
                    unset($_relations[$relationIndex]);
                }
            }
        }
    }
    
    /**
     * adds a new relation
     * 
     * @param  Tinebase_Relation_Model_Relation $_relation 
     * @return Tinebase_Relation_Model_Relation the new relation
     */
    protected function _addRelation($_relation)
    {
        $_relation->created_by = Zend_Registry::get('currentAccount')->getId();
        $_relation->creation_time = Zend_Date::now();
        
        return $this->_backend->addRelation($_relation);
    }
    
    /**
     * update an existing relation
     * 
     * @param  Tinebase_Relation_Model_Relation $_relation 
     * @return Tinebase_Relation_Model_Relation the updated relation
     */
    protected function _updateRelation($_relation)
    {
        $_relation->last_modified_by = Zend_Registry::get('currentAccount')->getId();
        $_relation->last_modified_time = Zend_Date::now();
        
        return $this->_backend->updateRelation($_relation);
    }
}