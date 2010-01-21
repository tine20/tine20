<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  CustomField
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
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
     * @var Tinebase_Backend_Sql
     */
    protected $_backendConfig;
    
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
        $this->_backendConfig = new Tinebase_Backend_Sql('Tinebase_Model_CustomField_Config', 'customfield_config');
        $this->_backendValue = new Tinebase_Backend_Sql('Tinebase_Model_CustomField_Value', 'customfield', NULL, NULL, NULL, TRUE);
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
        // invalidate cache (no memcached support yet)
        Tinebase_Core::get(Tinebase_Core::CACHE)->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('customfields'));
        $this->_cfByApplicationCache = array();
        
        return $this->_backendConfig->create($_record);
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
     * @return Tinebase_Record_RecordSet of Tinebase_Model_CustomField_Config records
     */
    public function getCustomFieldsForApplication($_applicationId, $_modelName = NULL)
    {
        $applicationId = Tinebase_Model_Application::convertApplicationIdToInt($_applicationId);
        
        $cfIndex = $applicationId . (($_modelName !== NULL) ? $_modelName : '');
        if (isset($this->_cfByApplicationCache[$cfIndex])) {
            return $this->_cfByApplicationCache[$cfIndex];
        } 
        
        $cache = Tinebase_Core::get(Tinebase_Core::CACHE);
        $cacheId = 'getCustomFieldsForApplication' . $cfIndex;
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
            $result = $this->_backendConfig->search($filter);
        
            if (count($result) > 0) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
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
     * delete a custom field
     *
     * @param string|Tinebase_Model_CustomField_Config $_customField
     */
    public function deleteCustomField($_customField)
    {
        // invalidate caches (no memcached support yet)
        Tinebase_Core::get(Tinebase_Core::CACHE)->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('customfields'));
        $this->_cfByApplicationCache = array();

        $this->_backendConfig->delete($_customField);
    }
    
    /**
     * save custom fields of record in its custom fields table
     *
     * @param Tinebase_Record_Interface $_record
     */
    public function saveRecordCustomFields(Tinebase_Record_Interface $_record)
    {
        $applicationId = Tinebase_Application::getInstance()->getApplicationByName($_record->getApplication())->getId();
        $appCustomFields = $this->getCustomFieldsForApplication($applicationId, get_class($_record));
        
        $existingCustomFields = $this->_getCustomFields($_record->getId());
        $existingCustomFields->addIndices(array('customfield_id'));
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Updating custom fields for record of class ' . get_class($_record)
        );
        
        foreach ($appCustomFields as $customField) {
            
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . print_r($_record->customfields, TRUE)
            );
                
            $value = (is_array($_record->customfields) && array_key_exists($customField->name, $_record->customfields) )
                ? $_record->customfields[$customField->name]
                : '';
                
            $filtered = $existingCustomFields->filter('customfield_id', $customField->id);
            if (count($filtered) == 1) {
                // update
                $cf = $filtered->getFirstRecord();
                $cf->value = $value;
                $this->_backendValue->update($cf);
                
            } else if (count($filtered) == 0) {
                // create
                $cf = new Tinebase_Model_CustomField_Value(array(
                    'record_id'         => $_record->getId(),
                    'customfield_id'    => $customField->getId(),
                    'value'             => $value
                ));
                $this->_backendValue->create($cf);

            } else {
                throw new Tinebase_Exception_UnexpectedValue('Oops, there should be only one custom field value here!');
            }
        }
    }
    
    /**
     * get custom fields and add them to $_record->customfields arraay
     *
     * @param Tinebase_Record_Interface $_record
     * @param Tinebase_Record_RecordSet $_customFields
     * @param Tinebase_Record_RecordSet $configs
     */
    public function resolveRecordCustomFields(Tinebase_Record_Interface $_record, $_customFields = NULL, $_configs = NULL)
    {
        $customFields = ($_customFields === NULL) ? $this->_getCustomFields($_record->getId()) : $_customFields;
        if ($_configs === NULL) {
            $_configs = $this->_backendConfig->getMultiple($customFields->customfield_id);  
        };
            
        $result = array();
        foreach ($customFields as $customField) {            
            $config = $_configs[$_configs->getIndexById($customField->customfield_id)];
            $result[$config->name] = $customField->value;
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
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Resolving custom fields for ' . count($_records) . ' records');
        
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
    
    /******************** functions for Tinebase_Controller_SearchInterface / get custom field values ***************************/
    
    /**
     * get list of custom field values
     *
     * @param Tinebase_Model_Filter_FilterGroup|optional $_filter
     * @param Tinebase_Model_Pagination|optional $_pagination
     * @param bool $_getRelations (unused)
     * @param boolean $_onlyIds (unused)
     * @return Tinebase_Record_RecordSet
     * 
     * @todo    check if cf id is set in filter?
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
