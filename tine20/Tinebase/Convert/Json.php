<?php
/**
 * convert functions for records from/to json (array) format
 * 
 * @package     Tinebase
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
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
        $this->_resolveMultipleIdFields($records);
        
        $_record = $records->getFirstRecord();
        
        $_record->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
        $_record->bypassFilters = true;
        
        return $_record->toArray();
    }

    /**
     * resolves multiple records
     * @param Tinebase_Record_RecordSet $_records the records
     */
    protected function _resolveMultipleIdFields(Tinebase_Record_RecordSet $_records)
    {
        $ownRecordClass = $_records->getRecordClassName();
        if(! $resolveFields = $ownRecordClass::getResolveForeignIdFields()) {
            return;
        }
        
        foreach($resolveFields as $foreignRecordClassName => $fields) {
            $foreignIds = array();
            $fields = (array) $fields;
            
            foreach($fields as $field) {
                $foreignIds = array_unique(array_merge($foreignIds, $_records->{$field}));
            }

            $controller = Tinebase_Core::getApplicationInstance($foreignRecordClassName);
            
            $foreignRecords = $controller->getMultiple($foreignIds);
            if($foreignRecords->count()) {
                foreach ($_records as $record) {
                    foreach($fields as $field) {
                        $idx = $foreignRecords->getIndexById($record->{$field});
                        if(isset($idx) && $idx !== FALSE) {
                            $record->{$field} = $foreignRecords[$idx];
                        } else {
                            $record->{$field} = NULL;
                        }
                    }
                }
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

        $_records->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
        $_records->convertDates = true;

        $result = $_records->toArray();

        return $result;
    }
}
