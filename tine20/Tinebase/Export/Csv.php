<?php
/**
 * csv generation class
 *
 * @package     Tinebase
 * @subpackage	Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * defines the datatype for simple registration object
 * 
 * @package     Tinebase
 * @subpackage	Export
 * 
 */
class Tinebase_Export_Csv
{
    /**
     * relation types
     * 
     * @var array
     */
    protected $_relationsTypes = array();
    
    /**
     * The php build in fputcsv function is buggy, so we need an own one :-(
     *
     * @param resource $filePointer
     * @param array $dataArray
     * @param char $delimiter
     * @param char $enclosure
     * @param char $escapeEnclosure
     */
    public static function fputcsv($filePointer, $dataArray, $delimiter=',', $enclosure='"', $escapeEnclosure='"'){
        $string = "";
        $writeDelimiter = false;
        foreach($dataArray as $dataElement) {
            if($writeDelimiter) $string .= $delimiter;
            $escapedDataElement = preg_replace("/$enclosure/", $escapeEnclosure . $enclosure , $dataElement);
            $string .= $enclosure . $escapedDataElement . $enclosure;
            $writeDelimiter = true;
        } 
        $string .= "\n";
        
        fwrite($filePointer, $string);
    }

    /**
     * export records to csv file
     *
     * @param Tinebase_Record_RecordSet $_records
     * @param boolean $_toStdout
     * @param array $_skipFields
     * @return string filename
     * 
     * @todo add specific export values
     * @todo save in special download path
     */
    public function exportRecords(Tinebase_Record_RecordSet $_records, $_toStdout = FALSE, $_skipFields = array()) {
        
        $filename = ($_toStdout) ? 'STDOUT' : Tinebase_Core::getTempDir() . DIRECTORY_SEPARATOR . md5(uniqid(rand(), true)) . '.csv';
        
        if (count($_records) < 1) {
            return FALSE;
        }
                
        // to ensure the order of fields we need to sort it ourself!
        $fields = array();
        if (empty($_skipFields)) {
            $skipFields = array(
                'id'                    ,
                'created_by'            ,
                'creation_time'         ,
                'last_modified_by'      ,
                'last_modified_time'    ,
                'is_deleted'            ,
                'deleted_time'          ,
                'deleted_by'            ,
            );
        } else {
            $skipFields = $_skipFields;
        }
        
        foreach ($_records[0] as $fieldName => $value) {
            if (! in_array($fieldName, $skipFields)) {
                if ($fieldName == 'relations') {
                    $this->_addRelationTypes($fields);
                } else {
                    $fields[] = $fieldName;
                }
            }
        }
        
        $filehandle = ($_toStdout) ? STDOUT : fopen($filename, 'w');
        
        self::fputcsv($filehandle, $fields);
        
        // fill file with records
        $_records->setTimezone(Tinebase_Core::get('userTimeZone'));
        foreach ($_records as $record) {
            $recordArray = $record->toArray();
            $csvArray = array();
            foreach ($fields as $fieldName) {
                if (in_array($fieldName, $this->_relationsTypes)) {
                    $csvArray[] = $this->_addRelations($record, $fieldName);
                } else {
                    $csvArray[] = $recordArray[$fieldName];
                }
            }
            self::fputcsv($filehandle, $csvArray);
        }
        
        if (!$_toStdout) {
            fclose($filehandle);
        }
        
        return $filename;
    }
    
    /**
     * add relation types
     * 
     * @param array $_fields
     * @return void
     */
    protected function _addRelationTypes(array &$_fields)
    {
        if (count($this->_relationsTypes) > 0) {
            foreach ($this->_relationsTypes as $type) {
                $_fields[] = $type;
            }
        } else {
            $_fields[] = 'relations';
        }
    }

    /**
     * add relation values from related records
     * 
     * @param Tinebase_Record_Abstract $_record
     * @param string $_fieldName
     * @return string
     * 
     * @todo    add index to recordset for type/fieldname?
     */
    protected function _addRelations(Tinebase_Record_Abstract $_record, $_fieldName)
    {
        $matchingRelations = $_record->relations->filter('type', $_fieldName);
        
        $result = '';
        foreach ($matchingRelations as $relation) {
            $result .= $this->_getSummary($relation->related_record);
        }
        
        return $result;
    }
    
    /**
     * add relation summary (such as n_fileas, title, ...)
     * 
     * @param Tinebase_Record_Abstract $_record
     * @param string $_type
     * @return string
     */
    protected function _getSummary(Tinebase_Record_Abstract $_record)
    {
        $result = '';
        switch(get_class($_record)) {
            case 'Addressbook_Model_Contact':
                $result = $_record->n_fileas . "\n";
                break;
            case 'Tasks_Model_Task':
                $result = $_record->summary . "\n";
                break;
        }
        
        return $result;
    }
}
