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
    protected $_backendValues;
    
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
        $this->_backendConfig = new Tinebase_Backend_Sql('Tinebase_Model_CustomFieldConfig', 'customfield_config');
        $this->_backendValues = new Tinebase_Backend_Sql('Tinebase_Model_CustomField', 'customfield');
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
     * @param Tinebase_Model_CustomFieldConfig $_customField
     * @return Tinebase_Model_CustomFieldConfig
     */
    public function addCustomField(Tinebase_Model_CustomFieldConfig $_record)
    {
        return $this->_backendConfig->create($_record);
        
        // invalidate cache (no memcached support yet)
        //Tinebase_Core::get(Tinebase_Core::CACHE)->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('customfields'));
    }

    /**
     * get custom field by id
     *
     * @param string $_customFieldId
     * @return Tinebase_Model_CustomFieldConfig
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
     * @return Tinebase_Record_RecordSet of Tinebase_Model_CustomFieldConfig records
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
        
        $filter = new Tinebase_Model_CustomFieldConfigFilter($filterValues);
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
     * @param string|Tinebase_Model_CustomFieldConfig $_customField
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
     * @todo implement
     */
    public function saveRecordCustomFields(Tinebase_Record_Interface $_record)
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' not implemented');
        
        /*
        $customFieldsTableName = $this->_tablePrefix . $this->_tableName . '_' . 'custom';
        
        // delete all custom fields for this record first
        $this->_deleteCustomFields($_record->getId());
        
        // save custom fields
        $applicationId = Tinebase_Application::getInstance()->getApplicationByName($_record->getApplication())->getId();
        $customFields = Tinebase_Config::getInstance()->getCustomFieldsForApplication($applicationId, $this->_modelName)->name;
        foreach ($customFields as $customField) {
            if (!empty($_record->customfields[$customField])) {
                $data = array(
                    'record_id' => $_record->getId(),
                    'name'      => $customField,
                    'value'     => $_record->customfields[$customField]
                );
                $this->_db->insert($customFieldsTableName, $data);
            }
        }
        */
    }
    
    /**
     * get custom fields and add them to $_record->customfields arraay
     *
     * @param Tinebase_Record_Interface $_record
     * 
     * @todo implement
     */
    public function getRecordCustomFields(Tinebase_Record_Interface $_record)
    {
        /*
        $customFieldsTableName = $this->_tablePrefix . $this->_tableName . '_' . 'custom';

        $select = $this->_db->select()
            ->from(array('cftable' => $customFieldsTableName))
            ->where($this->_db->quoteInto($this->_db->quoteIdentifier('cftable.record_id') . ' = ?', $_record->getId()));
        $stmt = $this->_db->query($select);
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        $customFields = array();
        foreach ($rows as $row) {            
            $customFields[$row['name']] = $row['value'];
        }
        $_record->customfields = $customFields;
        */ 
    }
    
    /**
     * delete custom fields of record
     *
     * @param string $_recordId
     * 
     * @todo implement (is this needed?)
     */
    public function deleteRecordCustomFields($_recordId)
    {
        /*
        $customFieldsTableName = $this->_tablePrefix . $this->_tableName . '_' . 'custom';

        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('record_id') . ' = ?', $_recordId)
        );        
        $this->_db->delete($customFieldsTableName, $where);
        */
    }
}
