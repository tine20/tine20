<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  CustomField
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @todo        add join to cf config to value backend to get name
 * @todo        use recordset instead of array to store cfs of record
 * @todo        move custom field handling from sql backend to abstract record controller
 * @todo        remove the memory logging
 */

/**
 * the class provides functions to handle custom fields and custom field configs
 * 
 * @package     Tinebase
 * @subpackage  CustomField
 */
class Tinebase_CustomField implements Tinebase_Controller_SearchInterface
{
    use Tinebase_Controller_Record_ModlogTrait;

    /**************************** protected vars *********************/
    
    /**
     * custom field config backend
     * 
     * @var Tinebase_CustomField_Config
     */
    protected $_backendConfig;
    
    /**
     * custom field acl backend
     * 
     * @var Tinebase_Backend_Sql
     */
    protected $_backendACL;
    
    /**
     * custom field values backend
     * 
     * @var Tinebase_Backend_Sql
     */
    protected $_backendValue;
    
    /**
     * custom fields by application cache
     * 
     * @var array (app id + modelname => Tinebase_Record_RecordSet with cfs)
     */
    protected $_cfByApplicationCache = array();

    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_CustomField
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */    
    private function __construct() 
    {
        $this->_backendConfig = new Tinebase_CustomField_Config();
        $this->_backendValue = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_CustomField_Value', 
            'tableName' => 'customfield',
        ));
        $this->_backendACL = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_CustomField_Grant', 
            'tableName' => 'customfield_acl',
        ));
        $this->_modelName = 'Tinebase_Model_CustomField_Config';
    }

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
    }

    /**
     * Returns instance of Tinebase_CustomField
     *
     * @return Tinebase_CustomField
     */
    public static function getInstance() 
    {
        if (static::$_instance === NULL) {
            static::$_instance = new Tinebase_CustomField();
        }
        
        return static::$_instance;
    }
    
    /**
     * add new custom field
     *
     * @param Tinebase_Model_CustomField_Config $_record
     * @return Tinebase_Record_Interface
     */
    public function addCustomField(Tinebase_Model_CustomField_Config $_record)
    {

        $transId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        try {
            try {
                if ($_record->is_system) {
                    $this->_backendConfig->setOnlySystemCFs();
                }
                $result = $this->_backendConfig->create($_record);
            } finally {
                $this->_backendConfig->setNoSystemCFs();
            }
            Tinebase_CustomField::getInstance()->setGrants($result, Tinebase_Model_CustomField_Grant::getAllGrants());
            $this->_writeModLog($result, null);

            Tinebase_TransactionManager::getInstance()->commitTransaction($transId);
            $transId = null;

            if ($_record->is_system) {
                $app = Tinebase_Application::getInstance()->getApplicationById($_record->application_id);
                /** @var Tinebase_Record_Interface $model */
                $model = $_record->model;
                // clear the MC cache
                $model::resetConfiguration();

                Setup_SchemaTool::updateAllSchema();
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Created new custom field ' . $_record->name . ' for application ' . $_record->application_id);
            }


        } finally {
            if (null !== $transId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }

        return $result;
    }
    
    /**
     * update custom field
     *
     * @param Tinebase_Model_CustomField_Config $_record
     * @return Tinebase_Model_CustomField_Config
     */
    public function updateCustomField(Tinebase_Model_CustomField_Config $_record)
    {
        $this->_clearCache();
        $this->_backendConfig->setAllCFs();
        try {
            $result = $this->_backendConfig->update($_record);
        } finally {
            $this->_backendConfig->setNoSystemCFs();
        }
        Tinebase_CustomField::getInstance()->setGrants($result, Tinebase_Model_CustomField_Grant::getAllGrants());
        return $result;
    }

    /**
     * get custom field by id
     *
     * @param string $_customFieldId
     * @return Tinebase_Model_CustomField_Config
     */
    public function getCustomField($_customFieldId)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->_backendConfig->get($_customFieldId);
    }

    /**
     * get custom field by name and app
     *
     * @param string|Tinebase_Model_Application $applicationId application object, id or name
     * @param string $customFieldName
     * @param string $modelName
     * @param bool $getSystemCFs (default false)
     * @param bool $ignoreAcl (default false)
     * @return Tinebase_Model_CustomField_Config|null
     */
    public function getCustomFieldByNameAndApplication($applicationId, $customFieldName, $modelName = null, $getSystemCFs = false, $ignoreAcl = false)
    {
        $allAppCustomfields = $this->getCustomFieldsForApplication($applicationId, $modelName,
            Tinebase_Model_CustomField_Grant::GRANT_READ, $getSystemCFs, $ignoreAcl);
        return $allAppCustomfields->find('name', $customFieldName);
    }
    
    /**
     * get custom fields for an application
     * - results are cached in class cache $_cfByApplicationCache
     * - results are cached if caching is active (with cache tag 'customfields')
     *
     * @param string|Tinebase_Model_Application $_applicationId application object, id or name
     * @param string                            $_modelName
     * @param string                            $_requiredGrant (read grant by default)
     * @param bool                              $_getSystemCFs (false by default)
     * @param bool                              $_ignoreAcl (default false)
     * @return Tinebase_Record_RecordSet|Tinebase_Model_CustomField_Config of Tinebase_Model_CustomField_Config records
     */
    public function getCustomFieldsForApplication($_applicationId,
                                                  $_modelName = NULL,
                                                  $_requiredGrant = Tinebase_Model_CustomField_Grant::GRANT_READ,
                                                  $_getSystemCFs = false,
                                                  $_ignoreAcl = false)
    {
        $applicationId = Tinebase_Model_Application::convertApplicationIdToInt($_applicationId);
        
        $userId = (is_object(Tinebase_Core::getUser())) ? Tinebase_Core::getUser()->getId() : 'nouser';
        $cfIndex = $applicationId . (($_modelName !== NULL) ? $_modelName : '') . $_requiredGrant . $userId .
            (int)$_getSystemCFs . (int)$_ignoreAcl;
        
        if (isset($this->_cfByApplicationCache[$cfIndex])) {
            return $this->_cfByApplicationCache[$cfIndex];
        } 
        
        $cache = Tinebase_Core::getCache();
        $cacheId = Tinebase_Helper::convertCacheId('getCustomFieldsForApplication' . $cfIndex);
        $result = $cache->load($cacheId);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Before - MEMORY: ' . memory_get_usage(TRUE)/1024/1024 . ' MBytes');
        
        if (! $result) {
            $filterValues = array(array(
                'field'     => 'application_id', 
                'operator'  => 'equals', 
                'value'     => $applicationId
            ));
            
            if ($_modelName !== NULL) {
                $filterValues[] = array(
                    'field'     => 'model', 
                    'operator'  => 'equals', 
                    'value'     => $_modelName
                );
            }
            
            $filter = new Tinebase_Model_CustomField_ConfigFilter($filterValues, '', [
                'ignoreAcl' => $_ignoreAcl
            ]);
            $filter->setRequiredGrants((array)$_requiredGrant);
            try {
                if ($_getSystemCFs) {
                    $this->_backendConfig->setOnlySystemCFs();
                }
                $result = $this->_backendConfig->search($filter);
            } finally {
                $this->_backendConfig->setNoSystemCFs();
            }

        
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Got ' . count($result) . ' uncached custom fields for app id ' . $applicationId . ' (cacheid: ' . $cacheId . ')');
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE) && (count($result) > 0)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . print_r($result->toArray(), TRUE));
            
            $cache->save($result, $cacheId, array('customfields'));
        }
        
        $this->_cfByApplicationCache[$cfIndex] = $result;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' After - MEMORY: ' . memory_get_usage(TRUE)/1024/1024 . ' MBytes');
        
        return $result;
    }
    
    /**
     * check if app has customfield configs
     * 
     * @param string $applicationName
     * @param string $modelName
     * @return boolean 
     */
    public function appHasCustomFields($applicationName, $modelName = NULL)
    {
        if (empty($applicationName)) {
            return FALSE;
        }
        $app = Tinebase_Application::getInstance()->getApplicationByName($applicationName);
        $result = $this->getCustomFieldsForApplication($app, $modelName);
        return (count($result) > 0);
    }
    
    /**
     * resolve config grants
     * 
     * @param Tinebase_Record_RecordSet $_cfConfigs
     */
    public function resolveConfigGrants($_cfConfigs)
    {
        $user = Tinebase_Core::getUser();
        if (! is_object($user)) {
            return; // do nothing
        }
        
        $cfAcl = $this->_backendConfig->getAclForIds($user->getId(), $_cfConfigs->getArrayOfIds());
        
        foreach ($_cfConfigs as $config) {
            $config->account_grants = ((isset($cfAcl[$config->getId()]) || array_key_exists($config->getId(), $cfAcl))) ? explode(',', $cfAcl[$config->getId()]) : array();
        }
    }
    
    /**
     * delete a custom field
     *
     * @param string|Tinebase_Model_CustomField_Config $_customField
     */
    public function deleteCustomField($_customField)
    {
        if ($_customField instanceof Tinebase_Model_CustomField_Config) {
            $cfId = $_customField->getId();
        } else {
            $cfId = $_customField;
            try {
                $_customField = $this->_backendConfig->get($cfId);
            } catch (Tinebase_Exception_NotFound $tenf) {
                try {
                    $this->_backendConfig->setOnlySystemCFs();
                    $_customField = $this->_backendConfig->get($cfId);
                } finally {
                    $this->_backendConfig->setNoSystemCFs();
                }
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Deleting custom field config ' . $cfId . ' and values.');
        
        $this->_clearCache();
        $this->_backendValue->deleteByProperty($cfId, 'customfield_id');
        $this->_backendACL->deleteByProperty($cfId, 'customfield_id');
        $this->_backendConfig->delete($cfId);

        $this->_writeModLog(null, $_customField);

        if ($_customField->is_system) {
            /** @var Tinebase_Record_Interface $model */
            $model = $_customField->model;
            $model::resetConfiguration();

            Setup_SchemaTool::updateAllSchema();
        }
    }
    
    /**
     * delete custom fields for an application
     *
     * @param string|Tinebase_Model_Application $_applicationId
     * @return integer numer of deleted custom fields
     */
    public function deleteCustomFieldsForApplication($_applicationId)
    {
        $this->_clearCache();
        $applicationId = Tinebase_Model_Application::convertApplicationIdToInt($_applicationId);
 
        $filterValues = array(array(
            'field'     => 'application_id', 
            'operator'  => 'equals',
            'value'     => $applicationId
        ));
            
          $filter = new Tinebase_Model_CustomField_ConfigFilter($filterValues);
          $filter->customfieldACLChecks(FALSE);
        $customFields = $this->_backendConfig->search($filter);
            
        $deletedCount = 0;
        foreach ($customFields as $customField) {
               $this->deleteCustomField($customField);
               $deletedCount++;
        }
        
        return $deletedCount;
    }
    
    /**
     * saves multiple Custom Fields
     * @param String $_modelName
     * @param array $_recordIds
     * @param array $_customFieldValues
     */
    
    public function saveMultipleCustomFields($_modelName, $_recordIds, $_customFieldValues) 
    {
        $expModelName = explode('_', $_modelName);
        $app = array_shift($expModelName);
        $app = Tinebase_Application::getInstance()->getApplicationByName($app);
        
        $cF = $this->getCustomFieldsForApplication($app->getId(), $_modelName, Tinebase_Model_CustomField_Grant::GRANT_WRITE);
        $fA = array();
         
        foreach($cF as $field) {
            $fA[$field->__get('name')] = $field->__get('id');
        }
        
        unset($cF);
        
        foreach($_recordIds as $recId) {
            foreach($_customFieldValues as $cfKey => $cfValue) {
                $filterValues = array(
                    array(
                        'field'     => 'record_id',
                        'operator'  => 'in',
                        'value'     => (array) $recId
                        ),
                    array(
                        'field'     => 'customfield_id',
                        'operator'  => 'in',
                        'value'     => (array) $fA[$cfKey]
                        )
                    );
                
                $filter = new Tinebase_Model_CustomField_ValueFilter($filterValues);
                
                $record = $this->_backendValue->search($filter)->getFirstRecord();
                
                if($record) {
                    // DELETE
                    if(empty($_customFieldValues[$cfKey])) {
                        $this->_backendValue->delete($record);
                    } else { // UPDATE
                        $record->value = $_customFieldValues[$cfKey];
                        $this->_backendValue->update($record);
                    }
                } else {
                    if(!empty($_customFieldValues[$cfKey])) {
                        $record = new Tinebase_Model_CustomField_Value(array(
                                'record_id'         => $recId,
                                'customfield_id'    => $fA[$cfKey],
                                'value'             =>  $_customFieldValues[$cfKey]
                            ));
                        $this->_backendValue->create($record);
                    }
                }
            }
        }
        $this->_clearCache();
      }

    /**
     * save custom fields of record in its custom fields table
     *
     * @param Tinebase_Record_Interface $_record
     * @throws Tinebase_Exception_Record_Validation
     */
    public function saveRecordCustomFields(Tinebase_Record_Interface $_record)
    {
        $applicationId = Tinebase_Application::getInstance()->getApplicationByName($_record->getApplication())->getId();
        $appCustomFields = $this->getCustomFieldsForApplication($applicationId, get_class($_record), Tinebase_Model_CustomField_Grant::GRANT_WRITE);
        $this->resolveConfigGrants($appCustomFields);
        
        $existingCustomFields = $this->_getCustomFields($_record->getId());
        $existingCustomFields->addIndices(array('customfield_id'));
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Updating custom fields for record of class ' . get_class($_record));
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Record cf values: ' . print_r($_record->customfields, TRUE));
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' App cf names: ' . print_r($appCustomFields->name, TRUE));
        
        foreach ($appCustomFields as $customField) {
            if (is_array($_record->customfields) && (isset($_record->customfields[$customField->name]) || array_key_exists($customField->name, $_record->customfields))) {
                $value = $_record->customfields[$customField->name];
                $filtered = $existingCustomFields->filter('customfield_id', $customField->id);
                
                // we need to resolve the modelName and the record value if array is given (e.g. on updating customfield)
                if (isset($customField->definition['type']) && (strtolower($customField->definition['type']) == 'record' || strtolower($customField->definition['type']) == 'recordlist')) {
                    $value = $this->_getValueForRecordOrListCf($_record, $customField, $value);
                }

                switch (count($filtered)) {
                    case 1:
                        $cf = $filtered->getFirstRecord();
                        if ($customField->valueIsEmpty($value)) {
                            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Deleting cf value for ' . $customField->name);
                            $this->_backendValue->delete($cf);
                        } else {
                            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Updating value for ' . $customField->name . ' to ' . $value);
                            $cf->value = $value;
                            $this->_backendValue->update($cf);
                        }
                        break;
                    case 0:
                        if (! $customField->valueIsEmpty($value)) {
                            $cf = new Tinebase_Model_CustomField_Value(array(
                                'record_id'         => $_record->getId(),
                                'customfield_id'    => $customField->getId(),
                                'value'             => $value
                            ));
                            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Creating value for ' . $customField->name . ' -> ' . $value);
                            $this->_backendValue->create($cf);
                        }
                        break;
                    default:
                        throw new Tinebase_Exception_UnexpectedValue('Oops, there should be only one custom field value here!');
                }
            }
        }
    }

    public static function getModelNameFromDefinition($definition)
    {
        $modelParts = explode('.', $definition[$definition['type'] . 'Config']['value']['records']);
        return $modelParts[1] . '_Model_' . $modelParts[3];
    }

    /**
     * @param $_record
     * @param $_customField
     * @param $_value
     * @return string
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _getValueForRecordOrListCf($_record, $_customField, $_value)
    {
        // get model parts from saved record class e.g. Tine.Admin.Model.Group
        $modelName = self::getModelNameFromDefinition($_customField->definition);
        $model = new $modelName(array(), true);
        $idProperty = $model->getIdProperty();
        if (is_array($_value)) {
            if (strtolower($_customField->definition['type']) == 'record') {
                /** @var Tinebase_Record_Interface $model */
                $value = $_value[$idProperty];

            } else {
                // recordlist
                $values = array();
                foreach ($_value as $record) {
                    if (is_array($record) || $record instanceof Tinebase_Record_Interface) {
                        $values[] = $record[$idProperty];
                    } else {
                        $values[] = $record;
                    }
                }

                // remove own record if in list
                sort($values);
                Tinebase_Helper::array_remove_by_value($_record->getId(), $values);
                $value = json_encode($values);
            }
        } else {
            $value = $_value;
        }

        // check if customfield value is the record itself
        if (get_class($_record) == $modelName && strpos($value, $_record->getId()) !== false) {
            throw new Tinebase_Exception_Record_Validation('It is not allowed to add the same record as customfield record!');
        }

        return $value;
    }
    
    /**
     * get custom fields and add them to $_record->customfields array
     *
     * @param Tinebase_Record_Interface $_record
     * @param Tinebase_Record_RecordSet $_customFields
     * @param Tinebase_Record_RecordSet $_configs
     */
    public function resolveRecordCustomFields(Tinebase_Record_Interface $_record, $_customFields = NULL, $_configs = NULL)
    {
        $customFields = ($_customFields === NULL) ? $this->_getCustomFields($_record->getId()) : $_customFields;
        
        if (count($customFields) == 0) {
            return;
        }
        
        if ($_configs === NULL) {
            $_configs = $this->getCustomFieldsForApplication(Tinebase_Application::getInstance()->getApplicationByName($_record->getApplication()));
        };

        $this->_resolveCustomFieldsValues($customFields, $_configs);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Adding ' . count($customFields) . ' customfields to record  ' . $_record->getId());

        foreach ($customFields as $customField) {
            $this->_setCfValueInRecord($_record, $customField, $_configs);
        }
    }
    
    /**
     * set customfield value in record
     * 
     * @param Tinebase_Record_Interface $record
     * @param Tinebase_Model_CustomField_Value $customField
     * @param Tinebase_Record_RecordSet $configs
     * @param bool $extendedResolving
     */
    protected function _setCfValueInRecord(Tinebase_Record_Interface $record, Tinebase_Model_CustomField_Value $customField, Tinebase_Record_RecordSet $configs, $extendedResolving = false)
    {
        $recordCfs = $record->customfields;
        $idx = $configs->getIndexById($customField->customfield_id);
        if ($idx !== FALSE) {
            /** @var Tinebase_Model_CustomField_Config $config */
            $config = $configs[$idx];
            $value = $customField->value;
            if (true === $extendedResolving) {
                //$definition = is_object($config->definition) ? $config->definition->toArray() : (array)$config->definition;
                $clonedConfig = clone $config;
                $clonedConfig->value = $value;
                
                if (isset($clonedConfig->definition) && $clonedConfig->definition instanceof Tinebase_Config_Struct) {
                    $clonedConfig->label = $clonedConfig->definition->label;   
                }
                $recordCfs[$config->name] = $clonedConfig;
            } else {
                $recordCfs[$config->name] = $value;
            }
        }

        // sort customfields by key
        if (is_array($recordCfs)) {
            ksort($recordCfs);
        }
        
        $record->customfields = $recordCfs;
    }

    /**
     * get all customfields of all given records
     * 
     * @param  Tinebase_Record_RecordSet $_records     records to get customfields for
     * @param  bool                      $_extendedResolving
     */
    public function resolveMultipleCustomfields(Tinebase_Record_RecordSet $_records, $_extendedResolving = false)
    {
        if (count($_records) == 0) {
            return;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Before resolving - MEMORY: ' . memory_get_usage(TRUE)/1024/1024 . ' MBytes');
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Resolving custom fields for ' . count($_records) . ' ' . $_records->getRecordClassName() . ' records.');
        
        $configs = $this->getCustomFieldsForApplication(Tinebase_Application::getInstance()->getApplicationByName($_records->getFirstRecord()->getApplication()));
        
        $customFields = $this->_getCustomFields($_records->getArrayOfIdsAsString(), $configs->getArrayOfIds());
        if ($customFields->count() === 0) return;
        $customFields->sort('record_id');
        $this->_resolveCustomFieldsValues($customFields, $configs);
        
        // NOTE: as filtering is currently very slow, we have to loop the customfields and add the value to the record.
        // @see 0007496: timeout when opening multiedit dlg and assigning records to events/projects/email
        // @see 0007558: reactivate indices in Tinebase_Record_RecordSet
        /** @var Tinebase_Record_Interface $record */
        $record = NULL;
        foreach ($customFields as $customField) {
            if (! $record || $record->getId() !== $customField->record_id) {
                $record = $_records->getById($customField->record_id);
            }
            $this->_setCfValueInRecord($record, $customField, $configs, $_extendedResolving);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' After resolving - MEMORY: ' . memory_get_usage(TRUE)/1024/1024 . ' MBytes');
    }

    /**
     * @param Tinebase_Record_RecordSet $_customFields
     * @param Tinebase_Record_RecordSet $_configs
     */
    protected function _resolveCustomFieldsValues(Tinebase_Record_RecordSet $_customFields, Tinebase_Record_RecordSet $_configs)
    {
        /** @var Tinebase_Model_CustomField_Config $config */
        foreach ($_configs as $config) {
            $type = strtolower($config->definition->type);
            if ($type === 'record' ||$type === 'recordlist') {
                $cFields = $_customFields->filter('customfield_id', $config->getId());
                if ($cFields->count() === 0) {
                    continue;
                }

                try {
                    $recordConfigIndex = $type === 'record' ? 'recordConfig' : 'recordListConfig';
                    $model = $config->definition[$recordConfigIndex]['value']['records'];
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                        . ' Fetching ' . $type . ' customfield of type ' . $model);

                    $controller = Tinebase_Core::getApplicationInstance($model);
                    $ids = $cFields->value;
                    if ($type === 'recordlist') {
                        $tmpIds = [];
                        foreach ($ids as $id) {
                            $tmpIds = array_merge($tmpIds, json_decode($id, true));
                        }
                        $ids = $tmpIds;
                    }
                    $ids = array_unique($ids);

                    // prevent recursion
                    $current = $controller->resolveCustomfields(false);
                    try {
                        $result = $controller->getMultiple($ids);
                    } finally {
                        $controller->resolveCustomfields($current);
                    }

                    // TODO why do we already convert to array here? should be done in converter!
                    if ($type === 'recordlist') {
                        foreach ($cFields as $cField) {
                            $r = [];
                            foreach (json_decode($cField->value, true) as $id) {
                                if (false !== ($rec = $result->getById($id))) {
                                     $r[] = $rec->toArray();
                                } else {
                                    $r[] = ['id' => $id];
                                }
                            }
                            $cField->value = $r;
                        }
                    } else {
                        foreach ($cFields as $cField) {
                            if (false !== ($rec = $result->getById($cField->value))) {
                                $cField->value = $rec->toArray();
                            }
                        }
                    }

                } catch (Tinebase_Exception_AccessDenied $tead) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                        . ' Could not resolve custom field. Message: ' . $tead);
                } catch (Exception $e) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                        . ' Error resolving custom field record: ' . $e->getMessage());
                    Tinebase_Exception::log($e);
                }
            }
        }
    }
    
    /**
     * get custom fields of record(s)
     *
     * @param string|array $_recordId
     * @param array|null $_configIds
     * @return Tinebase_Record_RecordSet of Tinebase_Model_CustomField_Value
     */
    protected function _getCustomFields($_recordId, $_configIds = NULL)
    {
        $recordIds = is_array($_recordId) ? $_recordId : array((string) $_recordId);
        
        $filterValues = array(array(
            'field'     => 'record_id', 
            'operator'  => 'in', 
            'value'     => $recordIds
        ));
        if ($_configIds) {
            $filterValues[] = array(
                'field'     => 'customfield_id', 
                'operator'  => 'in', 
                'value'     => (array) $_configIds
            );
        }
        $filter = new Tinebase_Model_CustomField_ValueFilter($filterValues);
        
        $result = $this->_backendValue->search($filter);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Fetched ' . count($result) . ' customfield values.');
        
        return $result;
    }
    
    /**
     * set grants for custom field
     *
     * @param   string|Tinebase_Model_CustomField_Config $_customfieldId
     * @param   array $_grants list of grants to add
     * @param   string $_accountType
     * @param   int $_accountId
     * @param   boolean $_removeOldGrants
     * @return  void
     * @throws Tinebase_Exception_Backend
     *
     * @todo let this function set all grants at once (like container grants)
     */
    public function setGrants($_customfieldId, $_grants = array(), $_accountType = NULL, $_accountId = NULL, $_removeOldGrants = true)
    {
        $cfId = ($_customfieldId instanceof Tinebase_Model_CustomField_Config) ? $_customfieldId->getId() : $_customfieldId;
        
        try {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Setting grants for custom field ' . $cfId . ' -> ' . implode(',', $_grants) . ' for '
                . ($_accountType !== NULL ? $_accountType : Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE) . ' (' . $_accountId . ')');
            
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
            if ($_removeOldGrants) {
                $this->_backendACL->deleteByProperty($cfId, 'customfield_id');
            }

            foreach ($_grants as $grant) {
                if (in_array($grant, Tinebase_Model_CustomField_Grant::getAllGrants())) {
                    $newGrant = new Tinebase_Model_CustomField_Grant(array(
                        'customfield_id'=> $cfId,
                        'account_type'  => ($_accountType !== NULL) ? $_accountType : Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                        'account_id'    => ($_accountId !== NULL) ? $_accountId : 0,
                        'account_grant' => $grant
                    ));
                    $this->_backendACL->create($newGrant);
                }
            }
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $this->_clearCache();
            
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw new Tinebase_Exception_Backend($e->getMessage());
        }
    }
    
    /**
     * get customfield config ids by grant
     * 
     * @param string $_grant
     * @return array of ids
     */
    public function getCustomfieldConfigIdsByAcl($_grant)
    {
        $user = Tinebase_Core::getUser();
        if (is_object($user)) {
            $result = $this->_backendConfig->getByAcl($_grant, $user->getId());
        } else {
            $result = array();
        }
        
        return $result;
    }
    
    /**
     * remove all customfield related entries from cache
     * 
     * @todo this needs to clear in a more efficient way
     */
    protected function _clearCache() 
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Clearing custom field cache.');
        
        $this->_cfByApplicationCache = array();
        
        $cache = Tinebase_Core::getCache();
        $cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('customfields'));
    }

    /**
    * remove related entries from cache for given cf config record
    * 
    * @param Tinebase_Model_CustomField_Config $record
    * 
    * @todo this needs to clear in a more efficient way
    */
    public function clearCacheForConfig(/** @noinspection PhpUnusedParameterInspection */
        Tinebase_Model_CustomField_Config $record)
    {
        $this->_clearCache();
        
        // NOTE: this does not work as we need the user id in the cacheId
        /*
        $cfIndexRead  = Tinebase_Model_CustomField_Grant::GRANT_READ;
        $cfIndexWrite = Tinebase_Model_CustomField_Grant::GRANT_WRITE;
        $cfIndexModelRead  = $record->model . Tinebase_Model_CustomField_Grant::GRANT_READ;
        $cfIndexModelWrite = $record->model . Tinebase_Model_CustomField_Grant::GRANT_WRITE;
        $idsToClear = array($cfIndexRead, $cfIndexModelRead, $cfIndexWrite, $cfIndexModelWrite);
        
        $cache = Tinebase_Core::getCache();
        foreach ($idsToClear as $id) {
            $cacheId = 'getCustomFieldsForApplication' . $record->application_id . $id;
            unset($this->_cfByApplicationCache[$record->application_id . $id]);
            $cache->remove($cacheId);
        }
        */
    }
    
    /******************** functions for Tinebase_Controller_SearchInterface / get custom field values ***************************/
    
    /**
     * get list of custom field values
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @param bool $_getRelations (unused)
     * @param boolean $_onlyIds (unused)
     * @param string $_action
     * @return Tinebase_Record_RecordSet|array
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get')
    {
        $result = $this->_backendValue->search($_filter, $_pagination, $_onlyIds ? Tinebase_Backend_Sql_Abstract::IDCOL
            : Tinebase_Backend_Sql_Abstract::ALLCOL);
        return $result;
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
    {
        $count = $this->_backendValue->searchCount($_filter);
        return $count;
    }

    /**
     * get list of custom field values
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Record_Interface $_pagination
     * @param bool $_getRelations (unused)
     * @param bool $_onlyIds (unused)
     * @return Tinebase_Record_RecordSet
     */
    public function searchConfig(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL, /** @noinspection PhpUnusedParameterInspection */ $_getRelations = FALSE, /** @noinspection PhpUnusedParameterInspection */ $_onlyIds = FALSE)
    {
        $result = $this->_backendConfig->search($_filter, $_pagination);
        return $result;
    }

    public function deleteCustomFieldValue($_ids)
    {
        return $this->_backendValue->delete($_ids);
    }

    public function saveCustomFieldValue(Tinebase_Model_CustomField_Value $_record)
    {
        $recordId = $_record->getId();
        if (! empty($recordId)) {
            return $this->_backendValue->update($_record);
        }
        return $this->_backendValue->create($_record);
    }
}
