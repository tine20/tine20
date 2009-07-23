<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  CustomField
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo        add join to cf config to value backend to get name
 * @todo        add caching again?
 */

/**
 * the class provides functions to handle custom fields and custom field configs
 * 
 * @package     Tinebase
 * @subpackage  CustomField
 */
class Tinebase_CustomField
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
        $this->_backendValue = new Tinebase_Backend_Sql('Tinebase_Model_CustomField_Value', 'customfield');
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
        return $this->_backendConfig->create($_record);
        
        // invalidate cache (no memcached support yet)
        //Tinebase_Core::get(Tinebase_Core::CACHE)->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('customfields'));
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
     * - results are cached if caching is active (with cache tag 'customfields')
     *
     * @param string|Tinebase_Model_Application $_applicationId
     * @param string                            $_modelName
     * @return Tinebase_Record_RecordSet of Tinebase_Model_CustomField_Config records
     */
    public function getCustomFieldsForApplication($_applicationId, $_modelName = NULL)
    {
        $applicationId = Tinebase_Model_Application::convertApplicationIdToInt($_applicationId);
        
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
                //. print_r($result->toArray(), TRUE)
            );
        }
            
        return $result;
        
        /*
        $cache = Tinebase_Core::get(Tinebase_Core::CACHE);
        $cacheId = 'getCustomFieldsForApplication' . $applicationId . (($_modelName !== NULL) ? $_modelName : '');
        $result = $cache->load($cacheId);
        if (!$result) {        
            $cache->save($result, $cacheId, array('customfields'));
        }
        */
    }
    
    /**
     * delete a custom field
     *
     * @param string|Tinebase_Model_CustomField_Config $_customField
     */
    public function deleteCustomField($_customField)
    {
        $this->_backendConfig->delete($_customField);
        
        // invalidate cache (no memcached support yet)
        //Tinebase_Core::get(Tinebase_Core::CACHE)->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('customfields'));
    }
    
    /**
     * save custom fields of record in its custom fields table
     *
     * @param Tinebase_Record_Interface $_record
     * 
     * @todo use recordset instead of array?
     */
    public function saveRecordCustomFields(Tinebase_Record_Interface $_record)
    {
        $applicationId = Tinebase_Application::getInstance()->getApplicationByName($_record->getApplication())->getId();
        $appCustomFields = $this->getCustomFieldsForApplication($applicationId, get_class($_record));
        
        $existingCustomFields = $this->_getRecordCustomFields($_record->getId());
        $existingCustomFields->addIndices(array('customfield_id'));
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Updating custom fields for record of class ' . get_class($_record)
        );
        
        foreach ($appCustomFields as $customField) {
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
     * 
     * @todo use recordset instead of array?
     */
    public function resolveRecordCustomFields(Tinebase_Record_Interface $_record)
    {
        $customFields = $this->_getRecordCustomFields($_record->getId());

        $configs = $this->_backendConfig->getMultiple($customFields->customfield_id);
            
        $result = array();
        foreach ($customFields as $customField) {            
            $config = $configs[$configs->getIndexById($customField->customfield_id)];
            $result[$config->name] = $customField->value;
        }
        $_record->customfields = $result;
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Resolved custom fields for record of class ' . get_class($_record)
        );
    }
    
    /**
     * get custom fields of record
     *
     * @param string $_recordId
     * @return Tinebase_Record_RecordSet of Tinebase_Model_CustomField_Value
     */
    protected function _getRecordCustomFields($_recordId)
    {
        $filterValues = array(array(
            'field'     => 'record_id', 
            'operator'  => 'equals', 
            'value'     => $_recordId
        ));
        $filter = new Tinebase_Model_CustomField_ValueFilter($filterValues);
        
        return $this->_backendValue->search($filter);
    }
}
