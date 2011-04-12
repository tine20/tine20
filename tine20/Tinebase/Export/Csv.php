<?php
/**
 * Tinebase Csv Export class
 *
 * @package     Tinebase
 * @subpackage	Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        use export definitions
 */

/**
 * Tinebase Csv Export class
 * 
 * @package     Tinebase
 * @subpackage	Export
 */
class Tinebase_Export_Csv extends Tinebase_Export_Abstract
{
    /**
     * relation types
     * 
     * @var array
     */
    protected $_relationsTypes = array();
    
    /**
     * special fields
     * 
     * @var array
     */
    protected $_specialFields = array();
    
    /**
     * fields to skip
     * 
     * @var array
     */
    protected $_skipFields = array(
        'id'                    ,
        'created_by'            ,
        'creation_time'         ,
        'last_modified_by'      ,
        'last_modified_time'    ,
        'is_deleted'            ,
        'deleted_time'          ,
        'deleted_by'            ,
    );
    
    /**
     * write export to stdout?
     * 
     * @var boolean
     */
    protected $_toStdout = FALSE;
    
    /**
     * format strings
     * 
     * @var string
     */
    protected $_format = 'csv';
    
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
            if ($writeDelimiter) { 
                $string .= $delimiter;
            }
            $escapedDataElement = (! is_array($dataElement)) ? preg_replace("/$enclosure/", $escapeEnclosure . $enclosure , $dataElement) : '';
            $string .= $enclosure . $escapedDataElement . $enclosure;
            $writeDelimiter = true;
        } 
        $string .= "\n";
        
        fwrite($filePointer, $string);
    }

    /**
     * generate export
     * 
     * @return string|boolean filename
     */
    public function generate()
    {
        $records = $this->_getRecords();
        $records->setTimezone(Tinebase_Core::get('userTimeZone'));
        if (count($records) < 1) {
            return FALSE;
        }
                        
        $filename = $this->_getFilename();
        $filehandle = ($this->_toStdout) ? STDOUT : fopen($filename, 'w');
        
        $fields = $this->_getFields($records->getFirstRecord());
        self::fputcsv($filehandle, $fields);
        $this->_addBody($filehandle, $records, $fields);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Exported ' . count($records) . ' records to csv file.');
        
        if (!$this->_toStdout) {
            fclose($filehandle);
        }
        
        return $filename;
    }

    /**
     * get record / export fields
     * 
     * @param Tinebase_Record_Abstract $_record
     * @return array
     */
    protected function _getFields(Tinebase_Record_Abstract $_record)
    {
        $fields = array();
        foreach (array_keys($_record->toArray()) as $key) {
            $fields[] = $key;
            if (in_array($key, array_keys($this->_specialFields))) {
                $fields[] = $this->_specialFields[$key];
            }
        }
        
        if ($_record->has('tags')) {
            $fields[] = 'tags';
        }
        $fields = array_diff($fields, $this->_skipFields);
        $fields = array_merge($fields, $this->_relationsTypes);
        
        return $fields;
    }
    
    /**
     * add rows to csv body
     * 
     * @param handle $_filehandle
     * @param Tinebase_Record_RecordSet $_records
     * @param array $_fields
     */
    protected function _addBody($_filehandle, $_records, $_fields)
    {
        foreach ($_records as $record) {
            $csvArray = array();
            foreach ($_fields as $fieldName) {
                if (in_array($fieldName, $this->_relationsTypes)) {
                    $csvArray[] = $this->_addRelations($record, $fieldName);
                } else if (in_array($fieldName, $this->_specialFields)) {
                    $arrayFlipped = array_flip($this->_specialFields);
                    $csvArray[] = $this->_addSpecialValue($record, $arrayFlipped[$fieldName]);
                } else if ($fieldName == 'tags') {
                    $csvArray[] = $this->_getTags($record);
                } else if ($fieldName == 'notes') {
                    $csvArray[] = $this->_addNotes($record);
                } else if ($fieldName == 'container_id') {
                    $csvArray[] = $this->_getContainer($record, 'id');
                } else {
                    $csvArray[] = $record->{$fieldName};
                }
            }
            self::fputcsv($_filehandle, $csvArray);
        }
    }
    
    /**
     * get export filename
     * 
     * @return string filename
     */
    public function _getFilename()
    {
        return ($this->_toStdout) ? 'STDOUT' : Tinebase_Core::getTempDir() . DIRECTORY_SEPARATOR . md5(uniqid(rand(), true)) . '.csv';
    }
        
    /**
     * get export config / csv export does not use export definitions atm
     *
     * @param array $_additionalOptions additional options
     * @return Zend_Config_Xml
     */
    protected function _getExportConfig($_additionalOptions = array())
    {
        return new Zend_Config($_additionalOptions);
    }
    
    /**
     * get download content type
     * 
     * @return string
     */
    public function getDownloadContentType()
    {
        return 'text/csv';
    }
}
