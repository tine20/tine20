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
        
        $filename = ($_toStdout) ? 'STDOUT' : $this->_downloadPath . DIRECTORY_SEPARATOR . md5(uniqid(rand(), true)) . '.csv';
        
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
                $fields[] = $fieldName;
            }
        }
        
        $filehandle = ($_toStdout) ? STDOUT : fopen($filename, 'w');
        
        self::fputcsv($filehandle, $fields);
        
        // fill file with records
        foreach ($_records as $record) {
            $recordArray = array();
            foreach ($fields as $fieldName) {
                $recordArray[] = $record->$fieldName;
            }
            self::fputcsv($filehandle, $recordArray);
        }
        
        if (!$_toStdout) {
            fclose($filehandle);
        }
        
        return $filename;
    }
}
