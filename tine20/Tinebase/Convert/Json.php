<?php
/**
 * convert functions for records from/to json (array) format
 * 
 * @package     Tinebase
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * convert functions for records from/to json (array) format
 *
 * @package     Tinebase
 * @subpackage  Convert
 */
class Tinebase_Convert_Json implements Tinebase_Convert_Interface
{
    /**
     * converts external format to Tinebase_Record_Abstract
     * 
     * @param  mixed                     $_blob   the input data to parse
     * @param  Tinebase_Record_Abstract  $_record  update existing record
     * @return Tinebase_Record_Abstract
     */
    public function toTine20Model($_blob, Tinebase_Record_Abstract $_record = NULL)
    {
        throw new Tinebase_Exception_NotImplemented('From json to record is not implemented yet');
    }
    
    /**
     * converts Tinebase_Record_Abstract to external format
     * 
     * @param  Tinebase_Record_Abstract $_record
     * @return mixed
     */
    public function fromTine20Model(Tinebase_Record_Abstract $_record)
    {
        if (! $_record) {
            return array();
        }
        
        // for resolving we'll use recordset
        $recordClassName = get_class($_record);
        $records = new Tinebase_Record_RecordSet($recordClassName, array($_record));
        $modelConfiguration = $recordClassName::getConfiguration();
        
        $this->_resolveBeforeToArray($records, $modelConfiguration, FALSE);
        
        $_record = $records->getFirstRecord();
        $_record->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
        $_record->bypassFilters = true;
        
        $result = $_record->toArray();
        
        $result = $this->_resolveAfterToArray($result, $modelConfiguration, FALSE);
        
        return $result;
    }

    /**
     * resolves single record fields (Tinebase_ModelConfiguration._recordsFields)
     * 
     * @param Tinebase_Record_RecordSet $_records the records
     * @param Tinebase_ModelConfiguration
     */
    protected function _resolveSingleRecordFields(Tinebase_Record_RecordSet $_records, $modelConfig = NULL)
    {
        if (! $modelConfig) {
            return;
        }
        
        $resolveFields = $modelConfig->recordFields;
        
        if ($resolveFields && is_array($resolveFields)) {
            // don't search twice if the same recordClass gets resolved on multiple fields
            foreach ($resolveFields as $fieldKey => $fieldConfig) {
                $resolveRecords[$fieldConfig['config']['recordClassName']][] = $fieldKey;
            }
            
            foreach ($resolveRecords as $foreignRecordClassName => $fields) {
                $foreignIds = array();
                $fields = (array) $fields;
                
                foreach($fields as $field) {
                    $foreignIds = array_unique(array_merge($foreignIds, $_records->{$field}));
                }
                
                if (! Tinebase_Core::getUser()->hasRight(substr($foreignRecordClassName, 0, strpos($foreignRecordClassName, "_")), Tinebase_Acl_Rights_Abstract::RUN)) {
                    continue;
                }
                
                $cfg = $resolveFields[$fields[0]];
                
                if ($cfg['type'] == 'user') {
                    $foreignRecords = Tinebase_User::getInstance()->getUsers();
                } elseif ($cfg['type'] == 'container') {
                    $foreignRecords = new Tinebase_Record_RecordSet('Tinebase_Model_Container');
                    $foreignRecords->addRecord(Tinebase_Container::getInstance()->get($_id));
                // TODO: resolve recursive records of records better in controller
                // TODO: resolve containers
                } else {
                    $controller = (isset($cfg['config']['controllerClassName']) || array_key_exists('controllerClassName', $cfg['config'])) ? $cfg['config']['controllerClassName']::getInstance() : Tinebase_Core::getApplicationInstance($foreignRecordClassName);
                    $foreignRecords = $controller->getMultiple($foreignIds);
                }
                
                $foreignRecords->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
                $foreignRecords->convertDates = true;
                Tinebase_Frontend_Json_Abstract::resolveContainerTagsUsers($foreignRecords);
                $fr = $foreignRecords->getFirstRecord();
                if ($fr && $fr->has('notes')) {
                    Tinebase_Notes::getInstance()->getMultipleNotesOfRecords($foreignRecords);
                }
                
                if ($foreignRecords->count()) {
                    foreach ($_records as $record) {
                        foreach ($fields as $field) {
                            // don't try to resolve already resolved or empty fields
                            if (is_string($record->{$field}) && ! empty($record->{$field})) {
                                $idx = $foreignRecords->getIndexById($record->{$field});
                                if (isset($idx) && $idx !== FALSE) {
                                    $record->{$field} = $foreignRecords[$idx];
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    /**
     * resolves multiple records (fallback)
     * 
     * @deprecated use Tinebase_ModelConfiguration to configure your models, so this won't be used anymore 
     * @param Tinebase_Record_RecordSet $_records the records
     * @param array $resolveFields
     */
    public static function resolveMultipleIdFields($records, $resolveFields = NULL)
    {
        if (! $records instanceof Tinebase_Record_RecordSet) {
            return;
        }
        
        $ownRecordClass = $records->getRecordClassName();
        if ($resolveFields === NULL) {
            $resolveFields = $ownRecordClass::getResolveForeignIdFields();
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' Resolving ' . $ownRecordClass . ' fields: ' . print_r($resolveFields, TRUE));
        
        foreach ((array) $resolveFields as $foreignRecordClassName => $fields) {
            if ($foreignRecordClassName === 'recursive') {
                foreach ($fields as $field => $model) {
                    foreach ($records->$field as $subRecords) {
                        self::resolveMultipleIdFields($subRecords);
                    }
                }
            } else {
                self::_resolveForeignIdFields($records, $foreignRecordClassName, (array) $fields);
            }
        }
    }
    
    /**
     * resolve foreign fields for records
     * 
     * @param Tinebase_Record_RecordSet $records
     * @param string $foreignRecordClassName
     * @param array $fields
     */
    protected static function _resolveForeignIdFields($records, $foreignRecordClassName, $fields)
    {
        $options = (isset($fields['options']) || array_key_exists('options', $fields)) ? $fields['options'] : array();
        $fields = (isset($fields['fields']) || array_key_exists('fields', $fields)) ? $fields['fields'] : $fields;
        
        $foreignIds = array();
        foreach ($fields as $field) {
            $foreignIds = array_unique(array_merge($foreignIds, $records->{$field}));
        }
        
        if (! Tinebase_Core::getUser()->hasRight(substr($foreignRecordClassName, 0, strpos($foreignRecordClassName, "_")), Tinebase_Acl_Rights_Abstract::RUN)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Not resolving ' . $foreignRecordClassName . ' records because user has no right to run app.');
            return;
        }
        
        try {
            $controller = Tinebase_Core::getApplicationInstance($foreignRecordClassName);
        } catch (Tinebase_Exception_AccessDenied $tead) {
            return;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' Fetching ' . $foreignRecordClassName . ' by id: ' . print_r($foreignIds, TRUE));
        
        if ((isset($options['ignoreAcl']) || array_key_exists('ignoreAcl', $options)) && $options['ignoreAcl']) {
            // @todo make sure that second param of getMultiple() is $ignoreAcl
            $foreignRecords = $controller->getMultiple($foreignIds, TRUE);
        } else {
            $foreignRecords = $controller->getMultiple($foreignIds);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' Foreign records found: ' . print_r($foreignRecords->toArray(), TRUE));
        
        if (count($foreignRecords) === 0) {
            return;
        }
        
        foreach ($records as $record) {
            foreach ($fields as $field) {
                if (is_scalar($record->{$field})) {
                    $idx = $foreignRecords->getIndexById($record->{$field});
                    if (isset($idx) && $idx !== FALSE) {
                        $record->{$field} = $foreignRecords[$idx];
                    } else {
                        switch ($foreignRecordClassName) {
                            case 'Tinebase_Model_User':
                            case 'Tinebase_Model_FullUser':
                                $record->{$field} = Tinebase_User::getInstance()->getNonExistentUser();
                                break;
                            default:
                                // skip
                        }
                    }
                }
            }
        }
    }
    
    /**
     * resolve multiple record fields (Tinebase_ModelConfiguration._recordsFields)
     * 
     * @param Tinebase_Record_RecordSet $_records
     * @param Tinebase_ModelConfiguration $modelConfiguration
     * @param boolean $multiple
     */
    protected function _resolveMultipleRecordFields(Tinebase_Record_RecordSet $_records, $modelConfiguration = NULL, $multiple = false)
    {
        if (! $modelConfiguration || (! $_records->count())) {
            return;
        }
        
        if (! ($resolveFields = $modelConfiguration->recordsFields)) {
            return;
        }
        
        $ownIds = $_records->{$modelConfiguration->idProperty};
        
        // iterate fields to resolve
        foreach ($resolveFields as $fieldKey => $c) {
            $config = $c['config'];
            
            // resolve records, if omitOnSearch is definitively set to FALSE (by default they won't be resolved on search)
            if ($multiple && !(isset($config['omitOnSearch']) && $config['omitOnSearch'] === FALSE)) {
                continue;
            }
            
            // fetch the fields by the refIfField
            $controller = isset($config['controllerClassName']) ? $config['controllerClassName']::getInstance() : Tinebase_Core::getApplicationInstance($foreignRecordClassName);
            $filterName = $config['filterClassName'];
            
            $filterArray = array();
            
            // addFilters can be added and must be added if the same model resides in more than one records fields
            if (isset($config['addFilters']) && is_array($config['addFilters'])) {
                $useaddFilters = true;
                $filterArray = $config['addFilters'];
            }
            
            $filter = new $filterName($filterArray);
            $filter->addFilter(new Tinebase_Model_Filter_Id(array('field' => $config['refIdField'], 'operator' => 'in', 'value' => $ownIds)));
            
            $paging = NULL;
            if (isset($config['paging']) && is_array($config['paging'])) {
                $paging = new Tinebase_Model_Pagination($config['paging']);
            }
            
            $foreignRecords = $controller->search($filter, $paging);
            $foreignRecordClass = $foreignRecords->getRecordClassName();
            $foreignRecordModelConfiguration = $foreignRecordClass::getConfiguration();
            
            $foreignRecords->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
            $foreignRecords->convertDates = true;
            
            $fr = $foreignRecords->getFirstRecord();

            // @todo: resolve alarms?
            // @todo: use parts parameter?
            if ($foreignRecordModelConfiguration->resolveRelated && $fr) {
                if ($fr->has('notes')) {
                    Tinebase_Notes::getInstance()->getMultipleNotesOfRecords($foreignRecords);
                }
                if ($fr->has('tags')) {
                    Tinebase_Tags::getInstance()->getMultipleTagsOfRecords($foreignRecords);
                }
                if ($fr->has('relations')) {
                    $relations = Tinebase_Relations::getInstance()->getMultipleRelations($foreignRecordClass, 'Sql', $foreignRecords->{$fr->getIdProperty()} );
                    $foreignRecords->setByIndices('relations', $relations);
                }
                if ($fr->has('customfields')) {
                    Tinebase_CustomField::getInstance()->resolveMultipleCustomfields($foreignRecords);
                }
                if ($fr->has('attachments') && Setup_Controller::getInstance()->isFilesystemAvailable()) {
                    Tinebase_FileSystem_RecordAttachments::getInstance()->getMultipleAttachmentsOfRecords($foreignRecords);
                }
            }
            
            if ($foreignRecords->count() > 0) {
                foreach ($_records as $record) {
                    $filtered = $foreignRecords->filter($config['refIdField'], $record->getId())->toArray();
                    $filtered = $this->_resolveAfterToArray($filtered, $foreignRecordModelConfiguration, TRUE);
                    $record->{$fieldKey} = $filtered;
                }
                
            } else {
                $_records->{$fieldKey} = NULL;
            }
        }
        
    }
    
    /**
     * resolves virtual fields, if a function has been defined in the field definition
     * 
     * @param array $resultSet
     * @param Tinebase_ModelConfiguration $modelConfiguration
     * @param boolean $multiple
     */
    protected function _resolveVirtualFields($resultSet, $modelConfiguration = NULL, $multiple = false)
    {
        if (! $modelConfiguration || ! ($virtualFields = $modelConfiguration->virtualFields)) {
            return $resultSet;
        }
        
        if ($modelConfiguration->resolveVFGlobally === TRUE) {
            
            $controller = $modelConfiguration->getControllerInstance();
            
            if ($multiple) {
                return $controller->resolveMultipleVirtualFields($resultSet);
            }
            return $controller->resolveVirtualFields($resultSet);
        }
        
        foreach($virtualFields as $field) {
            // resolve virtual relation record from relations property
            if (! $multiple && isset($field['type']) && $field['type'] == 'relation') {
                $fc = $field['config'];
                if (isset($resultSet['relations']) && (is_array($resultSet['relations']))) {
                    foreach($resultSet['relations'] as $relation) {
                        if (($relation['type'] == $fc['type']) && ($relation['related_model'] == ($fc['appName'] . '_Model_' . $fc['modelName']))) {
                            $resultSet[$field['key']] = $relation['related_record'];
                        }
                    }
                }
            // resolve virtual field by function
            } elseif ((isset($field['function']) || array_key_exists('function', $field))) {
                if (is_array($field['function'])) {
                    if (count($field['function']) > 1) { // static method call
                        $class  = $field['function'][0];
                        $method = $field['function'][1];
                        $resultSet = $class::$method($resultSet);

                    } else { // use key as classname and value as method name
                        $ks = array_keys($field['function']);
                        $class  = array_pop($ks);
                        $vs = array_values($field['function']);
                        $method = array_pop($vs);
                        $class = $class::getInstance();
                        
                        $resultSet = $class->$method($resultSet);
                        
                    }
                // if no array has been given, this should be a function name
                } else {
                    $resolveFunction = $field['function'];
                    $resultSet = $resolveFunction($resultSet);
                }
            }
        }
        
        return $resultSet;
    }
    
    /**
     * resolves child records before converting the record set to an array
     * 
     * @param Tinebase_Record_RecordSet $records
     * @param Tinebase_ModelConfiguration $modelConfiguration
     * @param boolean $multiple
     */
    protected function _resolveBeforeToArray($records, $modelConfiguration, $multiple = false)
    {
        Tinebase_Frontend_Json_Abstract::resolveContainerTagsUsers($records);
        
        self::resolveMultipleIdFields($records);
        
        // use modern record resolving, if the model was configured using Tinebase_ModelConfiguration
        // at first, resolve all single record fields
        if ($modelConfiguration) {
            $this->_resolveSingleRecordFields($records, $modelConfiguration);
        
            // resolve all multiple records fields
            $this->_resolveMultipleRecordFields($records, $modelConfiguration, $multiple);
        }
    }
    
    /**
     * resolves child records after converting the record set to an array
     * 
     * @param array $result
     * @param Tinebase_ModelConfiguration $modelConfiguration
     * @param boolean $multiple
     * 
     * @return array
     */
    protected function _resolveAfterToArray($result, $modelConfiguration, $multiple = false)
    {
        $result = $this->_resolveVirtualFields($result, $modelConfiguration, $multiple);
        return $result;
    }
    
    /**
     * converts Tinebase_Record_RecordSet to external format
     * 
     * @param  Tinebase_Record_RecordSet  $_records
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * 
     * @return mixed
     */
    public function fromTine20RecordSet(Tinebase_Record_RecordSet $_records = NULL, $_filter = NULL, $_pagination = NULL)
    {
        if (! $_records || count($_records) == 0) {
            return array();
        }
        
        // find out if there is a modelConfiguration
        $ownRecordClass = $_records->getRecordClassName();
        $config = $ownRecordClass::getConfiguration();
        
        $this->_resolveBeforeToArray($_records, $config, TRUE);
        
        $_records->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
        $_records->convertDates = true;

        $result = $_records->toArray();
        
        // resolve all virtual fields after converting to array, so we can add these properties "virtually"
        $result = $this->_resolveAfterToArray($result, $config, TRUE);

        return $result;
    }
}
