<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  CustomField
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @todo        add join to cf config to value backend to get name
 * @todo        use recordset instead of array to store cfs of record
 * @todo        move custom field handling from sql backend to abstract record controller
 */

/**
 * the class provides functions to handle custom fields and custom field configs
 * 
 * @package     Tinebase
 * @subpackage  CustomField
 */
class Tinebase_CustomField implements Tinebase_Controller_SearchInterface
{
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
     * custom field values bakcend
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
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_CustomField;
        }
        
        return self::$_instance;
    }
    
    /**
     * add new custom field
     *
     * @param Tinebase_Model_CustomField_Config $_customField
     * @return Tinebase_Model_CustomField_Config
     */
    public function addCustomField(Tinebase_Model_CustomField_Config $_record)
    {
        $this->_clearCache();
        $result = $this->_backendConfig->create($_record);
        Tinebase_CustomField::getInstance()->setGrants($result, Tinebase_Model_CustomField_Grant::getAllGrants());
        return $result;
    }
    
    /**
     * update custom field
     *
     * @param Tinebase_Model_CustomField_Config $_customField
     * @return Tinebase_Model_CustomField_Config
     */
    public function updateCustomField(Tinebase_Model_CustomField_Config $_record)
    {
        $this->_clearCache();
        $result = $this->_backendConfig->update($_record);
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
        return $this->_backendConfig->get($_customFieldId);
    }

    /**
     * get custom fields for an application
     * - results are cached in class cache $_cfByApplicationCache
     * - results are cached if caching is active (with cache tag 'customfields')
     *
     * @param string|Tinebase_Model_Application $_applicationId
     * @param string                            $_modelName
     * @param string                            $_requiredGrant (read grant by default)
     * @return Tinebase_Record_RecordSet of Tinebase_Model_CustomField_Config records
     */
    public function getCustomFieldsForApplication($_applicationId, $_modelName = NULL, $_requiredGrant = Tinebase_Model_CustomField_Grant::GRANT_READ)
    {
        $applicationId = Tinebase_Model_Application::convertApplicationIdToInt($_applicationId);
        
        $cfIndex = $applicationId . (($_modelName !== NULL) ? $_modelName : '') . $_requiredGrant;
        
        if (isset($this->_cfByApplicationCache[$cfIndex])) {
            return $this->_cfByApplicationCache[$cfIndex];
        } 
        
        $cache = Tinebase_Core::getCache();
        $cacheId = convertCacheId('getCustomFieldsForApplication' . $cfIndex);
        $result = $cache->load($cacheId);
        
        if (!$result) {
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
            
            $filter = new Tinebase_Model_CustomField_ConfigFilter($filterValues);
            $filter->setRequiredGrants((array)$_requiredGrant);
            $result = $this->_backendConfig->search($filter);
        
            if (count($result) > 0) {
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                    . ' Got ' . count($result) . ' custom fields for app id ' . $applicationId
                    . print_r($result->toArray(), TRUE)
                );
            }
            
            $cache->save($result, $cacheId, array('customfields'));
        }
        
        $this->_cfByApplicationCache[$cfIndex] = $result;
            
        return $result;
    }
    
    /**
     * check if app has customfield configs
     * 
     * @param string $_applicationName
     * @return boolean 
     */
    public function appHasCustomFields($_applicationName)
    {
        $app = Tinebase_Application::getInstance()->getApplicationByName($_applicationName);
        $result = $this->getCustomFieldsForApplication($app);
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
            $config->account_grants = (array_key_exists($config->getId(), $cfAcl)) ? explode(',', $cfAcl[$config->getId()]) : array();
        }
    }
    
    /**
     * delete a custom field
     *
     * @param string|Tinebase_Model_CustomField_Config $_customField
     */
    public function deleteCustomField($_customField)
    {
        $cfId = ($_customField instanceof Tinebase_Model_CustomField_Config) ? $_customField->getId() : $_customField;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Deleting custom field config ' . $cfId . ' and values.');
        
        $this->_clearCache();
        $this->_backendConfig->delete($cfId);
        $this->_backendValue->deleteByProperty($cfId, 'customfield_id');
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
     * @param Array $_recordIds
     * @param Array $_customFieldValues
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
     */
    public function saveRecordCustomFields(Tinebase_Record_Interface $_record)
    {
        $applicationId = Tinebase_Application::getInstance()->getApplicationByName($_record->getApplication())->getId();
        $appCustomFields = $this->getCustomFieldsForApplication($applicationId, get_class($_record), Tinebase_Model_CustomField_Grant::GRANT_WRITE);
        $this->resolveConfigGrants($appCustomFields);
        
        $existingCustomFields = $this->_getCustomFields($_record->getId());
        $existingCustomFields->addIndices(array('customfield_id'));
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Updating custom fields for record of class ' . get_class($_record));
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($_record->customfields, TRUE));
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($appCustomFields->toArray(), TRUE));
        
        foreach ($appCustomFields as $customField) {
            if (is_array($_record->customfields) && array_key_exists($customField->name, $_record->customfields)) {
                $value = $_record->customfields[$customField->name];
                $filtered = $existingCustomFields->filter('customfield_id', $customField->id);
                
                switch (count($filtered)) {
                    case 1:
                        $cf = $filtered->getFirstRecord();
                        if ($customField->valueIsEmpty($value)) {
                            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Deleting cf value for ' . $customField->name);
                            $this->_backendValue->delete($cf);
                        } else {
                            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Updateing value for ' . $customField->name . ' to ' . $value);
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
    
    /**
     * get custom fields and add them to $_record->customfields arraay
     *
     * @param Tinebase_Record_Interface $_record
     * @param Tinebase_Record_RecordSet $_customFields
     * @param Tinebase_Record_RecordSet $_configs
     */
    public function resolveRecordCustomFields(Tinebase_Record_Interface $_record, $_customFields = NULL, $_configs = NULL)
    {
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Resolving custom fields for ' . get_class($_record));
        
        $customFields = ($_customFields === NULL) ? $this->_getCustomFields($_record->getId()) : $_customFields;
        
        if (count($customFields) == 0) {
            return;
        }
        
        if ($_configs === NULL) {
            $_configs = $this->getCustomFieldsForApplication(Tinebase_Application::getInstance()->getApplicationByName($_record->getApplication()));
        };
        
        $result = array();
        foreach ($customFields as $customField) {
            $idx = $_configs->getIndexById($customField->customfield_id);
            if ($idx !== FALSE) {
                $config = $_configs[$idx];
                if (strtolower($config->definition['type']) == 'record') {
                	try {
	                	$modelParts = explode('.', $config->definition['recordConfig']['value']['records']); // get model parts from saved record class e.g. Tine.Admin.Model.Group
	                	$controllerName = $modelParts[1] . '_Controller_' . $modelParts[3];	
	                	$controller = call_user_func(array($controllerName, 'getInstance'));
	                	$result[$config->name] = $controller->get($customField->value)->toArray();
                	} catch (Exception $e) {
                		if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' Error resolving custom field record: ' . $e->getMessage());
                		$result[$config->name] = $customField->value;
                	}
                }
                else {
                	$result[$config->name] = $customField->value;
                }
            }
        }
        $_record->customfields = $result;
    }
    
    /**
     * get all customfields of all given records
     * 
     * @param  Tinebase_Record_RecordSet $_records     records to get customfields for
     */
    public function resolveMultipleCustomfields(Tinebase_Record_RecordSet $_records)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Resolving custom fields for ' . count($_records) . ' records');
        
        $customFields = $this->_getCustomFields($_records->getArrayOfIds());
        $customFields->addIndices(array('record_id'));
        
        $config = NULL;
        foreach ($_records as $record) {
            $this->resolveRecordCustomFields($record, $customFields->filter('record_id', $record->getId()), $config);
        }
    }
    
    /**
     * get custom fields of record(s)
     *
     * @param string|array $_recordId
     * @return Tinebase_Record_RecordSet of Tinebase_Model_CustomField_Value
     */
    protected function _getCustomFields($_recordId)
    {
        $filterValues = array(array(
            'field'     => 'record_id', 
            'operator'  => 'in', 
            'value'     => (array) $_recordId
        ));
        $filter = new Tinebase_Model_CustomField_ValueFilter($filterValues);
        
        return $this->_backendValue->search($filter);
    }
    
    /**
     * set grants for custom field
     *
     * @param   string|Tinebase_Model_CustomField_Config $_customfieldId
     * @param   array $_grants list of grants to add
     * @param   string $_accountType
     * @param   int $_accountId
     * @return  void
     */
    public function setGrants($_customfieldId, $_grants = array(), $_accountType = NULL, $_accountId = NULL)
    {
        $cfId = ($_customfieldId instanceof Tinebase_Model_CustomField_Config) ? $_customfieldId->getId() : $_customfieldId;
        
        try {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Setting grants for custom field ' . $cfId . ' -> ' . print_r($_grants, TRUE));
            
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
            $this->_backendACL->deleteByProperty($cfId, 'customfield_id');
            
            foreach($_grants as $grant) {
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
     */
    protected function _clearCache() 
    {        
        $this->_cfByApplicationCache = array();
        
        $cache = Tinebase_Core::getCache();
        $cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('customfields'));
    }
    
    /******************** functions for Tinebase_Controller_SearchInterface / get custom field values ***************************/
    
    /**
     * get list of custom field values
     *
     * @param Tinebase_Model_Filter_FilterGroup|optional $_filter
     * @param Tinebase_Model_Pagination|optional $_pagination
     * @param bool $_getRelations (unused)
     * @param boolean $_onlyIds (unused)
     * @return Tinebase_Record_RecordSet
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE)
    {
        $result = $this->_backendValue->search($_filter, $_pagination);
        return $result;
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter) 
    {
        $count = $this->_backendValue->searchCount($_filter);
        return $count;
    }
}
