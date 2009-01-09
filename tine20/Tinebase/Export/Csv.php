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
     * download path for csv file
     *
     * @var string
     */
    protected $_downloadPath = '/tmp';

    /**
     * The php build in fputcsv function is buggy, so we need an own one :-(
     *
     * @param resource $filePointer
     * @param array $dataArray
     * @param char $delimiter
     * @param char $enclosure
     */
    public static function fputcsv($filePointer, $dataArray, $delimiter=',', $enclosure=''){
        $string = "";
        $writeDelimiter = false;
        foreach($dataArray as $dataElement) {
            if($writeDelimiter) $string .= $delimiter;
            $string .= $enclosure . $dataElement . $enclosure;
            $writeDelimiter = true;
        } 
        $string .= "\n";
        
        fwrite($filePointer, $string);
    }

    /**
     * export timesheets to csv file
     *
     * @param Tinebase_Record_RecordSet $_records
     * @return string filename
     * 
     * @todo add specific export values
     * @todo save in special download path
     * @todo save skipped fields elsewhere (preferences?)
     */
    public function exportRecords(Tinebase_Record_RecordSet $_records, $_filename = NULL, $_skipFields = array()) {
        
        $filename = ($_filename !== NULL) ? $_filename : $this->_downloadPath . DIRECTORY_SEPARATOR . date('Y-m-d') . '_timesheet_export_' . time() . '.csv';
        
        /*
        if (count($_records) < 1) {
            throw new Tinebase_Exception_NotFound('No records found.');
        }
        */
                
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
                $fields[] = $fieldName;
            }
        }
        
        $filehandle = ($filename == 'STDOUT') ? STDOUT : fopen($filename, 'w');
        
        self::fputcsv($filehandle, $fields);
        
        // fill file with records
        foreach ($_records as $record) {
            $recordArray = array();
            foreach ($fields as $fieldName) {
                $recordArray[] = '"' . $record->$fieldName . '"';
            }
            self::fputcsv($filehandle, $recordArray);
        }
        
        if ($filename != 'STDOUT') {
            fclose($filehandle);
        }
        
        return $filename;
    }
}
