<?php
/**
 * convert functions for records from/to json (array) format
 * 
 * @package     Tinebase
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
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
        $records = new Tinebase_Record_RecordSet(get_class($_record), array($_record));
        
        Tinebase_Frontend_Json_Abstract::resolveContainerTagsUsers($records);
        
        // use modern record resolving, if the model was configured using Tinebase_ModelConfiguration
        // at first, resolve all single record fields
        $this->_resolveSingleRecordFields($records);
        // next step, resolve all multiple records fields
        $this->_resolveMultipleRecordFields($records);
        
        // resolve the traditional way, if model hasn't been configured with Tinebase_ModelConfiguration
        $this->_resolveMultipleIdFields($records);
        
        $_record = $records->getFirstRecord();
        $_record->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
        $_record->bypassFilters = true;
        
        return $_record->toArray();
    }

    /**
     * resolves single record fields (Tinebase_ModelConfiguration._recordsFields)
     * 
     * @param Tinebase_Record_RecordSet $_records the records
     */
    protected function _resolveSingleRecordFields(Tinebase_Record_RecordSet $_records)
    {
        $ownRecordClass = $_records->getRecordClassName();
        
        if (! $cfg = $ownRecordClass::getConfiguration()) {
            return;
        }
        $resolveFields = $cfg->recordFields;
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
                    $controller = array_key_exists('controllerClassName', $cfg['config']) ? $cfg['config']['controllerClassName']::getInstance() : Tinebase_Core::getApplicationInstance($foreignRecordClassName);
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
    
    /**
     * resolves multiple records (fallback)
     * 
     * @deprecated use Tinebase_ModelConfiguration to configure your models, so this won't be used anymore 
     * @param Tinebase_Record_RecordSet $_records the records
     */
    protected function _resolveMultipleIdFields(Tinebase_Record_RecordSet $_records)
    {
        $ownRecordClass = $_records->getRecordClassName();
        if (! $resolveFields = $ownRecordClass::getResolveForeignIdFields()) {
            return;
        }
        
        foreach ($resolveFields as $foreignRecordClassName => $fields) {
            $foreignIds = array();
            $fields = (array) $fields;
    
            foreach ($fields as $field) {
                $foreignIds = array_unique(array_merge($foreignIds, $_records->{$field}));
            }
    
            if (! Tinebase_Core::getUser()->hasRight(substr($foreignRecordClassName, 0, strpos($foreignRecordClassName, "_")), Tinebase_Acl_Rights_Abstract::RUN))
                continue;
    
            $controller = Tinebase_Core::getApplicationInstance($foreignRecordClassName);
    
            if (method_exists($controller, 'modlogActive')) {
                $modlogActive = $controller->modlogActive(FALSE);
            }
            $foreignRecords = $controller->getMultiple($foreignIds);
            $foreignRecords->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
            $foreignRecords->convertDates = true;
            
            if ($foreignRecords->count()) {
                foreach ($_records as $record) {
                    foreach ($fields as $field) {
                        $idx = $foreignRecords->getIndexById($record->{$field});
                        if (isset($idx) && $idx !== FALSE) {
                            $record->{$field} = $foreignRecords[$idx];
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
     */
    protected function _resolveMultipleRecordFields(Tinebase_Record_RecordSet $_records)
    {
        // show if there is something to resolve
        $ownRecordClass = $_records->getRecordClassName();
        
        if (! $_records->count()) {
            return;
        }
        
        if (! (($config = $ownRecordClass::getConfiguration()) && ($resolveFields = $config->recordsFields))) {
            return;
        }
        
        $ownIds = $_records->{$config->idProperty};
        
        // iterate fields to resolve
        foreach ($resolveFields as $fieldKey => $c) {
            $config = $c['config'];
            // fetch the fields by the refIfField
            $controller = array_key_exists('controllerClassName', $config) ? $config['controllerClassName']::getInstance() : Tinebase_Core::getApplicationInstance($foreignRecordClassName);
            $filterName = $config['filterClassName'];
            
            $filterArray = array();
            
            // addFilters can be added and must be added if the same model resides in more than one records fields
            if (array_key_exists('addFilters', $config) && is_array($config['addFilters'])) {
                $useaddFilters = true;
                $filterArray = $config['addFilters'];
            }
            
            $filter = new $filterName($filterArray);
            $filter->addFilter(new Tinebase_Model_Filter_Id(array('field' => $config['refIdField'], 'operator' => 'in', 'value' => $ownIds)));
            
            $paging = NULL;
            if (array_key_exists('paging', $config) && is_array($config['paging'])) {
                $paging = new Tinebase_Model_Pagination($config['paging']);
            }
            
            $foreignRecords = $controller->search($filter, $paging);
            $foreignRecords->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
            $foreignRecords->convertDates = true;
            Tinebase_Frontend_Json_Abstract::resolveContainerTagsUsers($foreignRecords);
            $fr = $foreignRecords->getFirstRecord();
            if ($fr && $fr->has('notes')) {
                Tinebase_Notes::getInstance()->getMultipleNotesOfRecords($foreignRecords);
            }
            if ($foreignRecords->count() > 0) {
                foreach ($_records as $record) {
                    $filtered = $foreignRecords->filter($config['refIdField'], $record->getId());
                    $record->{$fieldKey} = $filtered->toArray();
                }
            } else {
                $_records->{$fieldKey} = NULL;
            }
        }
        
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
    public function fromTine20RecordSet(Tinebase_Record_RecordSet $_records, $_filter = NULL, $_pagination = NULL)
    {
        if (count($_records) == 0) {
            return array();
        }
        
        Tinebase_Frontend_Json_Abstract::resolveContainerTagsUsers($_records);

        $this->_resolveMultipleIdFields($_records);
        $this->_resolveSingleRecordFields($_records);
        $this->_resolveMultipleRecordFields($_records);

        $_records->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
        $_records->convertDates = true;

        $result = $_records->toArray();

        return $result;
    }
}
