<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Relations
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
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
     *
     * @param  array  $_relationData    data for relations to create

     * @return void
     */
    public function undeleteRelations($_relationData)
    {
        foreach((array) $_relationData as $relationData) {
            if ($relationData instanceof Tinebase_Model_Relation) {
                $relation = $relationData;
            } else {
                $relation = new Tinebase_Model_Relation($relationData, true);
            }

            $relation->related_record = null;

            try {
                $appController = Tinebase_Core::getApplicationInstance($relation->related_model);
                try {
                    $appController->getBackend()->get($relation->related_id);
                } catch (Tinebase_Exception_NotFound $tenf) {
                    continue;
                }
            } catch(Tinebase_Exception_AccessDenied $tead) {
                // we just undelete it...
            }

            Tinebase_Timemachine_ModificationLog::setRecordMetaData($relation, 'undelete', $relation);
            $this->_updateRelation($relation);
        }
    }
    
    /**
     * set all relations of a given record
     * 
     * NOTE: given relation data is expected to be an array atm.
     * @todo check read ACL for new relations to existing records.
     * 
     * @param  string $_model           own model to get relations for
     * @param  string $_backend         own backend to get relations for
     * @param  string $_id              own id to get relations for 
     * @param  array|Tinebase_Record_RecordSet  $_relationData    data for relations to create
     * @param  bool   $_ignoreACL       create relations without checking permissions
     * @param  bool   $_inspectRelated  do update/create related records on the fly
     * @param  bool   $_doCreateUpdateCheck do duplicate/freebusy/... checking for relations
     * @return void
     */
    public function setRelations($_model,
                                 $_backend,
                                 $_id,
                                 $_relationData,
                                 $_ignoreACL = false,
                                 $_inspectRelated = false,
                                 $_doCreateUpdateCheck = false)
    {
        if ($_relationData instanceof Tinebase_Record_RecordSet) {
            $relations = $_relationData;
        } else {
            $relations = new Tinebase_Record_RecordSet('Tinebase_Model_Relation');
            foreach ((array)$_relationData as $relationData) {
                if ($relationData instanceof Tinebase_Model_Relation) {
                    $relations->addRecord($relationData);
                } else {
                    $relation = new Tinebase_Model_Relation(NULL, TRUE);
                    $relation->setFromJsonInUsersTimezone($relationData);
                    $relations->addRecord($relation);
                }
            }
        }
        
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
        $relationsIds = $this->_getRelationIds($relations, $currentRelations);
        
        $toAdd = $relations->getIdLessIndexes();
        $toDel = $this->_getToDeleteIds($currentRelations, $relationsIds);
        $toUpdate = array_intersect($currentIds, $relationsIds);
        foreach ($relations as $key => $relation) {
            if (!empty($id = $relation->getId()) && !in_array($id, $toDel) && !in_array($id, $toUpdate)) {
                $toAdd[] = $key;
            }
        }

        // prevent two empty related_ids of the same relation type
        $emptyRelatedId = array();
        foreach ($toAdd as $idx) {
            if (empty($relations[$idx]->related_id)) {
                $relations[$idx]->related_id = Tinebase_Record_Abstract::generateUID();
                $emptyRelatedId[$idx] = true;
            }
        }
        $this->_validateConstraintsConfig($_model, $relations, $toDel, $toUpdate);
        
        // break relations
        foreach ($toDel as $relationId) {
            $this->_backend->breakRelation($relationId);
        }
        
        // add new relations
        foreach ($toAdd as $idx) {
            $relation = $relations[$idx];
            if (isset($emptyRelatedId[$idx])) {
                // create related record
                $relation->related_id = null;
                $this->_setAppRecord($relation, $_doCreateUpdateCheck);
            } else if ($_inspectRelated && ! empty($relation->related_id) && ! empty($relation->related_record)) {
                // update related record
                $this->_setAppRecord($relation, $_doCreateUpdateCheck);
            }
            $this->_addRelation($relation);
        }
        
        // update relations
        foreach ($toUpdate as $relationId) {
            $current = $currentRelations->getById($relationId);
            $update = $relations->getById($relationId);
            
            // update related records if explicitly needed
            if ($_inspectRelated && isset($current->related_record) && isset($update->related_record)) {
                // @todo do we need to omit so many fields?
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
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                        . ' Related record diff: ' . print_r($current->related_record->diff($update->related_record)->toArray(), true));

                    if ( !$update->related_record->has('container_id') ||
                        Tinebase_Container::getInstance()->hasGrant(Tinebase_Core::getUser()->getId(), $update->related_record->container_id,
                            array(Tinebase_Model_Grants::GRANT_EDIT, Tinebase_Model_Grants::GRANT_ADMIN)) ) {
                        $this->_setAppRecord($update, $_doCreateUpdateCheck);
                    } else {
                        if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ .
                            ' Permission denied to update related record');
                    }
                }
            }
            
            if (! $current->isEqual($update, array('related_record', 'record_removed_reason'))) {
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                    . ' Relation diff: ' . print_r($current->diff($update)->toArray(), true));
                
                $this->_updateRelation($update);
            }
        }
    }

    /**
     * @param $currentRelations
     * @param array $relationsIds
     * @return array
     */
    protected function _getToDeleteIds($currentRelations, $relationsIds)
    {
        $deleteIds = [];
        foreach ($currentRelations as $relation) {
            if (! in_array($relation->getId(), $relationsIds) && empty($relation->record_removed_reason)) {
                $deleteIds[] = $relation->getId();
            }
        }

        return $deleteIds;
    }

    /**
     * appends missing relation ids if related records + type match
     *
     * @param Tinebase_Record_RecordSet $relations
     * @param Tinebase_Record_RecordSet $currentRelations
     * @return mixed
     */
    protected function _getRelationIds($relations, $currentRelations)
    {
        $clonedRelations = clone $relations;

        if (count($currentRelations) > 0) {
            foreach ($clonedRelations as $relation) {
                if ($relation->getId()) {
                    continue;
                }

                // if relation has no id, maybe we have the same relation already in current relations
                $subset = $currentRelations->filter('own_id', $relation->own_id)
                    ->filter('related_id', $relation->related_id)
                    ->filter('type', $relation->type);

                if (count($subset) === 1) {
                    // remove and add to make sure index is updated in record set
                    $relations->removeRecord($relation);
                    $relation->setId($subset->getFirstRecord()->getId());
                    $relations->addRecord($relation);
                    //$result[] = $subset->getFirstRecord()->getId();
                }
            }
        }

        $result = $relations->getArrayOfIds();

        return $result;
    }

    /**
     * returns the constraints config for the given models and their mirrored values (seen from the other side
     * 
     * @param array $models
     * @return array
     */
    public static function getConstraintsConfigs($models)
    {
        if (! is_array($models)) {
            $models = array($models);
        }
        $allApplications = Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED)->name;
        $ret = array();
        
        foreach ($models as $model) {
        
            $ownModel = explode('_Model_', $model);
        
            if (! class_exists($model) || ! in_array($ownModel[0], $allApplications)) {
                continue;
            }
            $cItems = $model::getRelatableConfig();
            
            $ownApplication = $ownModel[0];
            $ownModel = $ownModel[1];
        
            if (is_array($cItems)) {
                foreach($cItems as $cItem) {
        
                    if (! array_key_exists('config', $cItem)) {
                        continue;
                    }
        
                    // own side
                    $ownConfigItem = $cItem;
                    $ownConfigItem['ownModel'] = $ownModel;
                    $ownConfigItem['ownApp'] = $ownApplication;
                    $ownConfigItem['ownRecordClassName'] = $ownApplication . '_Model_' . $ownModel;
                    $ownConfigItem['relatedRecordClassName'] = $cItem['relatedApp'] . '_Model_' . $cItem['relatedModel'];
                    
                    $foreignConfigItem = array(
                        'reverted'     => true,
                        'ownApp'       => $cItem['relatedApp'],
                        'ownModel'     => $cItem['relatedModel'],
                        'relatedModel' => $ownModel,
                        'relatedApp'   => $ownApplication,
                        'default'      => array_key_exists('default', $cItem) ? $cItem['default'] : NULL,
                        'ownRecordClassName' => $cItem['relatedApp'] . '_Model_' . $cItem['relatedModel'],
                        'relatedRecordClassName' => $ownApplication . '_Model_' . $ownModel
                    );
        
                    // KeyfieldConfigs
                    if (array_key_exists('keyfieldConfig', $cItem)) {
                        $foreignConfigItem['keyfieldConfig'] = $cItem['keyfieldConfig'];
                        if ($cItem['keyfieldConfig']['from']){
                            $foreignConfigItem['keyfieldConfig']['from'] = $cItem['keyfieldConfig']['from'] == 'foreign' ? 'own' : 'foreign';
                        }
                    }
        
                    $j=0;
                    foreach ($cItem['config'] as $conf) {
                        $max = explode(':',$conf['max']);
                        $ownConfigItem['config'][$j]['max'] = intval($max[0]);
        
                        $foreignConfigItem['config'][$j] = $conf;
                        $foreignConfigItem['config'][$j]['max'] = intval($max[1]);
                        if ($conf['degree'] == 'sibling') {
                            $foreignConfigItem['config'][$j]['degree'] = $conf['degree'];
                        } else {
                            $foreignConfigItem['config'][$j]['degree'] = $conf['degree'] == 'parent' ? 'child' : 'parent';
                        }
                        $j++;
                    }
                    
                    $ret[] = $ownConfigItem;
                    $ret[] = $foreignConfigItem;
                }
            }
        }
        
        return $ret;
    }

    /**
     * validate constraints from the own and the other side.
     * this may be very expensive, if there are many constraints to check.
     *
     * @param string $ownModel
     * @param Tinebase_Record_RecordSet $relations
     * @param array $toDelete
     * @param array $toUpdate
     * @throws Tinebase_Exception_InvalidRelationConstraints
     */
    protected function _validateConstraintsConfig($ownModel, $relations, $toDelete = array(), $toUpdate = array())
    {
        if (! $relations->count()) {
            return;
        }
        $relatedModels = array_unique($relations->related_model);
        $relatedIds    = array_unique($relations->related_id);
        
        $toDelete      = is_array($toDelete) ? $toDelete : array();
        $toUpdate      = is_array($toUpdate) ? $toUpdate : array();
        $excludeCount  = array_merge($toDelete, $toUpdate);

        $ownId         = $relations->getFirstRecord()->own_id;

        // find out all models having a constraints config
        $allModels = $relatedModels;
        $allModels[] = $ownModel;
        $allModels = array_unique($allModels);

        $constraintsConfigs = self::getConstraintsConfigs($allModels);
        $relatedConstraints = $this->_backend->countRelatedConstraints($ownModel, $relations, $excludeCount);
        
        $groups = array();
        foreach($relations as $relation) {
            $groups[] = $relation->related_model . '--' . $relation->type . '--' . $relation->own_id;
        }
        
        $myConstraints = array_count_values($groups);

        $groups = array();
        foreach($relations as $relation) {
            if (! in_array($relation->getId(), $excludeCount)) {
                $groups[] = $relation->own_model . '--' . $relation->type . '--' . $relation->related_id;
            }
        }
        
        foreach($relatedConstraints as $relC) {
            for ($i = 0; $i < $relC['count']; $i++) {
                $groups[] = $relC['id'];
            }
        }
        
        $allConstraints = array_count_values($groups);

        foreach ($constraintsConfigs as $cc) {
            if (! isset($cc['config'])) {
                continue;
            }
            foreach($cc['config'] as $config) {
                
                $group = $cc['relatedRecordClassName'] . '--' . $config['type'];
                $idGroup = $group . '--' . $ownId;

                if (isset($myConstraints[$idGroup]) && ($config['max'] > 0 && $config['max'] < $myConstraints[$idGroup])) {
                
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Constraints validation failed from the own side! ' . print_r($cc, 1));
                    }
                    throw new Tinebase_Exception_InvalidRelationConstraints();
                }
                
                // TODO: if the other side gets the config reverted here, validating constrains failes here on multiple update 
                foreach($relatedIds as $relatedId) {
                    $idGroup = $group . '--' . $relatedId;
                    
                    if (isset($allConstraints[$idGroup]) && ($config['max'] > 0 && $config['max'] < $allConstraints[$idGroup])) {
                        
                        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Constraints validation failed from the other side! ' . print_r($cc, 1));
                        }

                        throw new Tinebase_Exception_InvalidRelationConstraints();
                    }
                }
            }
        }
    }
    
    /**
     * get all relations of a given record
     * - cache result if caching is activated
     * 
     * @param  string       $_model         own model to get relations for
     * @param  string       $_backend       own backend to get relations for
     * @param  string|array $_id            own id to get relations for
     * @param  string       $_degree        only return relations of given degree
     * @param  array        $_type          only return relations of given type
     * @param  bool         $_ignoreACL     get relations without checking permissions
     * @param  array        $_relatedModels only return relations having this related models
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Relation
     */
    public function getRelations($_model, $_backend, $_id, $_degree = NULL, array $_type = array(), $_ignoreACL = FALSE, $_relatedModels = NULL)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . "  model: '$_model' backend: '$_backend' " 
            // . 'ids: ' . print_r((array)$_id, true)
        );
        
        $result = $this->_backend->getAllRelations($_model, $_backend, $_id, $_degree, $_type, FALSE, $_relatedModels);
        $this->resolveAppRecords($result, $_ignoreACL);
        
        return $result;
    }
    
    /**
     * get all relations of all given records
     * 
     * @param  string $_model         own model to get relations for
     * @param  string $_backend       own backend to get relations for
     * @param  array  $_ids           own ids to get relations for
     * @param  string $_degree        only return relations of given degree
     * @param  array  $_type          only return relations of given type
     * @param  bool   $_ignoreACL     get relations without checking permissions
     * @param  array  $_relatedModels only return relations having this related model
     * @return array  key from $_ids => Tinebase_Record_RecordSet of Tinebase_Model_Relation
     */
    public function getMultipleRelations($_model, $_backend, $_ids, $_degree = NULL, array $_type = array(), $_ignoreACL = FALSE, $_relatedModels = NULL)
    {
        $flippedIds = array_flip($_ids);

        // prepare a record set for each given id
        $result = array();
        foreach ($flippedIds as $key) {
            $result[$key] = new Tinebase_Record_RecordSet('Tinebase_Model_Relation', array(),  true);
        }
        
        // fetch all relations in a single set
        $relations = $this->getRelations($_model, $_backend, $_ids, $_degree, $_type, $_ignoreACL, $_relatedModels);
        
        // sort relations into corrensponding sets
        foreach ($relations as $relation) {
            if (isset($flippedIds[$relation->own_id])) {
                $result[$flippedIds[$relation->own_id]]->addRecord($relation);
            }
        }
        
        return $result;
    }

    /**
     * converts related_records into their appropriate record objects
     * @todo move to model->setFromJson
     *
     * @param  Tinebase_Model_Relation|Tinebase_Record_RecordSet
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _relatedRecordToObject($_relations)
    {
        if(! $_relations instanceof Tinebase_Record_RecordSet) {
            $_relations = new Tinebase_Record_RecordSet('Tinebase_Model_Relation', array($_relations));
        }
        
        foreach ($_relations as $relation) {
            if (! is_string($relation->related_model)) {
                throw new Tinebase_Exception_InvalidArgument('missing relation model');
            }

            if (empty($relation->related_record) || $relation->related_record instanceof $relation->related_model) {
                continue;
            }
            
            $data = Zend_Json::encode($relation->related_record);
            $relation->related_record = new $relation->related_model();
            $relation->related_record->setFromJsonInUsersTimezone($data);
        }
    }
    
    /**
     * creates/updates application records
     * 
     * @param   Tinebase_Record_RecordSet $_relation of Tinebase_Model_Relation
     * @param   bool $_doCreateUpdateCheck
     * @throws  Tinebase_Exception_UnexpectedValue
     */
    protected function _setAppRecord($_relation, $_doCreateUpdateCheck = false)
    {
        if (! $_relation->related_record instanceof Tinebase_Record_Interface) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Relation: ' . print_r($_relation->toArray(), TRUE));
            throw new Tinebase_Exception_UnexpectedValue('Related record is missing from relation.');
        }

        $appController = Tinebase_Core::getApplicationInstance($_relation->related_model);

        if (! $_relation->related_record->getId()) {
            $method = 'create';
        } else {
            $method = 'update';
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' ' . ucfirst($method) . ' ' . $_relation->related_model . ' record.');
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Relation: ' . print_r($_relation->toArray(), TRUE));

        if ($method === 'update' && $appController->doContainerACLChecks()
            && ! Tinebase_Core::getUser()->hasGrant($_relation->related_record->container_id, Tinebase_Model_Grants::GRANT_EDIT)
        ) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . ' Don\'t update related record because user has no update grant');
        } else {
            try {
                /** @var Tinebase_Record_Interface $record */
                $record = $appController->$method($_relation->related_record,
                    $_doCreateUpdateCheck && $this->_doCreateUpdateCheck($_relation));
                $_relation->related_id = $record->getId();
            } catch (Tinebase_Exception_AccessDenied $tead) {
                // some right might prevent the update ... skipping update
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' Don\'t update related record: ' . $tead->getMessage());
            }
        }

        switch ($_relation->related_model) {
            case 'Addressbook_Model_Contact':
                $_relation->related_backend = ucfirst(Addressbook_Backend_Factory::SQL);
                break;
            case 'Tasks_Model_Task':
                $_relation->related_backend = Tasks_Backend_Factory::SQL;
                break;
            default:
                $_relation->related_backend = Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND;
                break;
        }
    }

    /**
     * get configuration for duplicate/freebusy checks from relatable config
     *
     * @param $relation
     *
     * TODO relatable config should be an object with functions to get the needed information...
     * @return bool
     */
    protected function _doCreateUpdateCheck($relation)
    {
        $relatableConfig = call_user_func($relation->own_model . '::getRelatableConfig');
        foreach ($relatableConfig as $config) {
            if ($relation->related_model === $config['relatedApp'] . '_Model_' . $config['relatedModel']
                && isset($config['createUpdateCheck'])
            ) {
                return $config['createUpdateCheck'];
            }
        }
        return false;
    }
    
    /**
     * resolved app records and fills the related_record property with the corresponding record
     * 
     * NOTE: With this, READ ACL is implicitly checked as non readable records won't get retuned!
     * 
     * @param  Tinebase_Record_RecordSet $_relations of Tinebase_Model_Relation
     * @param  boolean $_ignoreACL 
     * @return void
     * 
     * @todo    make getApplicationInstance work for tinebase record (Tinebase_Model_User for example)
     */
    protected function resolveAppRecords($_relations, $_ignoreACL = FALSE)
    {
        // separate relations by model
        $modelMap = array();
        foreach ($_relations as $relation) {
            if (!(isset($modelMap[$relation->related_model]) || array_key_exists($relation->related_model, $modelMap))) {
                $modelMap[$relation->related_model] = new Tinebase_Record_RecordSet('Tinebase_Model_Relation');
            }
            $modelMap[$relation->related_model]->addRecord($relation);
        }

        /** @var Tinebase_Record_RecordSet $records */

        // fill related_record
        foreach ($modelMap as $modelName => $relations) {
            
            // check right
            $split = explode('_Model_', $modelName);
            $rightClass = $split[0] . '_Acl_Rights';
            $rightName = 'manage_' . strtolower($split[1]) . 's';
            
            if (class_exists($rightClass)) {
                
                $ref = new ReflectionClass($rightClass);
                $u = Tinebase_Core::getUser();
                
                // if a manage right is defined and the user has no manage_record or admin right, remove relations having this record class as related model
                if (is_object($u) && $ref->hasConstant(strtoupper($rightName)) && (! $u->hasRight($split[0], $rightName)) && (! $u->hasRight($split[0], Tinebase_Acl_Rights::ADMIN))) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                        $_relations->removeRecords($relations);
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Skipping relation due to no manage right: ' . $modelName);
                    }
                    continue;
                }
            }
            
            $getMultipleMethod = 'getMultiple';

            $records = null;
            $removeReason = Tinebase_Model_Relation::REMOVED_BY_OTHER;
            if ($modelName === 'Tinebase_Model_User') {
                // @todo add related backend here
                //$appController = Tinebase_User::factory($relations->related_backend);

                $appController = Tinebase_User::factory(Tinebase_User::getConfiguredBackend());
                $records = $appController->$getMultipleMethod($relations->related_id);
            } else {
                try {
                    $appController = Tinebase_Core::getApplicationInstance($modelName);
                    if (method_exists($appController, $getMultipleMethod)) {
                        $records = $appController->$getMultipleMethod($relations->related_id, $_ignoreACL);
                        
                        // resolve record alarms
                        if (count($records) > 0 && $records->getFirstRecord()->has('alarms')) {
                            $appController->getAlarms($records);
                        }
                    } else {
                        throw new Tinebase_Exception_AccessDenied('Controller ' . get_class($appController)
                            . ' has no method ' . $getMultipleMethod);
                    }
                } catch (Tinebase_Exception_AccessDenied $tea) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                        __METHOD__ . '::' . __LINE__
                        . ' Removing relations from result. Got exception: ' . $tea->getMessage());
                    $removeReason = Tinebase_Model_Relation::REMOVED_BY_ACL;
                } catch (Tinebase_Exception_NotFound $tenf) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                        __METHOD__ . '::' . __LINE__ . ' Could not find controller for model: ' . $modelName
                        . '! you have broken relations: ' . join(',', $relations->id));
                    $_relations->removeRecords($relations);
                    continue;
                } catch (Tinebase_Exception_AreaLocked $teal) {
                    $removeReason = Tinebase_Model_Relation::REMOVED_BY_AREA_LOCK;
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                        __METHOD__ . '::' . __LINE__
                        . ' AreaLocked for model: ' . $modelName);
                }
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . " Resolving " . count($relations) . " relations");

            /** @var Tinebase_Model_Relation $relation */
            foreach ($relations as $relation) {
                $recordIndex    = $records instanceof Tinebase_Record_RecordSet
                    ? $records->getIndexById($relation->related_id)
                    : false;
                $relationIndex = $_relations->getIndexById($relation->getId());
                if ($recordIndex !== false) {
                    $_relations[$relationIndex]->related_record = $records[$recordIndex];
                } else if (isset($_relations[$relationIndex])) {
                    $_relations[$relationIndex]->record_removed_reason = $removeReason;
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                        " don't show related record in set, as READ ACL is obviously not granted $relation->related_model $relation->related_backend $relation->related_id");
                }
            }
        }
    }
    
    /**
     * get list of relations
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @param boolean $_onlyIds
     * @return Tinebase_Record_RecordSet|array
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_onlyIds = FALSE)
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
    protected function _addRelation(Tinebase_Model_Relation $_relation)
    {
        $_relation->created_by = Tinebase_Core::getUser()->getId();
        $_relation->creation_time = Tinebase_DateTime::now();
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
        $_relation->last_modified_time = Tinebase_DateTime::now();
        
        return $this->_backend->updateRelation($_relation);
    }

    /**
     * replaces all relations to or from a record with $sourceId to a record with $destinationId
     *
     * @param string $sourceId
     * @param string $destinationId
     * @param string $model
     * @return array
     * @throws Tinebase_Exception_AccessDenied
     */
    public function transferRelations($sourceId, $destinationId, $model)
    {
        if (! Tinebase_Core::getUser()->hasRight('Tinebase', Tinebase_Acl_Rights::ADMIN)) {
            throw new Tinebase_Exception_AccessDenied('Only Admins are allowed to perform his operation!');
        }
        
        return $this->_backend->transferRelations($sourceId, $destinationId, $model);
    }

    /**
     * Deletes entries
     *
     * @param string|integer|Tinebase_Record_Interface|array $_id
     * @return int The number of affected rows.
     */
    public function delete($_id)
    {
        return $this->_backend->delete($_id);
    }

    /**
     * remove all relations for application
     *
     * @param string $applicationName
     *
     * @return void
     */
    public function removeApplication($applicationName)
    {
        $this->_backend->removeApplication($applicationName);
    }

    /**
     * @param Tinebase_Record_Interface $record
     * @param string $degree
     * @param bool $ignoreACL
     * @return Tinebase_Record_RecordSet
     */
    public function getRelationsOfRecordByDegree(Tinebase_Record_Interface $record, $degree, $ignoreACL = FALSE)
    {
        // get relations if not yet present OR use relation search here
        if (empty($record->relations)) {
            $backendType = 'Sql';
            $modelName = get_class($record);
            $record->relations = $this->getRelations($modelName, $backendType, $record->getId(), NULL, array(), $ignoreACL);
        }


        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Relation');
        foreach ($record->relations as $relation) {
            if ($relation->related_degree === $degree) {
                $result->addRecord($relation);
            }
        }

        return $result;
    }

    /**
     * @return Tinebase_Relation_Backend_Sql
     */
    public function getBackend()
    {
        return $this->_backend;
    }
}
