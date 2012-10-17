<?php
/**
 * Tinebase Csv Export class
 *
 * @package     Tinebase
 * @subpackage    Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        use export definitions
 */

/**
 * Tinebase Csv Export class
 * 
 * @package     Tinebase
 * @subpackage    Export
 */
class Tinebase_Export_Csv extends Tinebase_Export_Abstract implements Tinebase_Record_IteratableInterface
{
    /**
     * relation types
     * 
     * @var array
     */
    protected $_relationsTypes = array();
    
    /**
     * relation subfields
     * 
     * @var array
     */
    protected $_relationFields = array();
    
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
     * csv filehandle resource
     * 
     * @var resource
     */
    protected $_filehandle = NULL;
    
    /**
     * fields
     * 
     * @var array
     */
    protected $_fields = NULL;
    
    /**
     * The php build in fputcsv function is buggy, so we need an own one :-(
     *
     * @param resource $filePointer
     * @param array $dataArray
     * @param char $delimiter
     * @param char $enclosure
     * @param char $escapeEnclosure
     */
    public static function fputcsv($filePointer, $dataArray, $delimiter = ',', $enclosure = '"', $escapeEnclosure = '"')
    {
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
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Generating new csv export of ' . $this->_modelName);
        
        $filename = $this->_getFilename();
        $this->_filehandle = ($this->_toStdout) ? STDOUT : fopen($filename, 'w');
        
        $fields = $this->_getFields();
        self::fputcsv($this->_filehandle, $fields);
        
        $this->_exportRecords();
        
        if (!$this->_toStdout) {
            fclose($this->_filehandle);
        }
        
        return $filename;
    }

    /**
     * get record / export fields
     * 
     * @return array
     */
    protected function _getFields()
    {
        if ($this->_fields === NULL) {
            $record = new $this->_modelName(array(), TRUE);
            
            $fields = array();
            foreach ($record->getFields() as $key) {
                if ($key === 'customfields') {
                    foreach ($this->_getCustomFieldNames() as $cfName) {
                        $fields[] = $cfName;
                    }
                } else {
                    $fields[] = $key;
                    if (in_array($key, array_keys($this->_specialFields))) {
                        $fields[] = $this->_specialFields[$key];
                    }
                }
            }
            
            if ($record->has('tags')) {
                $fields[] = 'tags';
            }
            $fields = array_diff($fields, $this->_skipFields);
            $fields = array_merge($fields, $this->_getRelationFields());
            $this->_fields = $fields;
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' fields to export: ' . implode(', ', $fields));
        }
        
        return $this->_fields;
    }
    
    /**
     * get relation fields
     * 
     * @return array
     */
    protected function _getRelationFields()
    {
        $result = array();
        foreach ($this->_relationsTypes as $relationType) {
            if (isset($this->_relationFields[$relationType])) {
                foreach ($this->_relationFields[$relationType] as $relationField) {
                    $result[] = $relationType . '-' . $relationField;
                }
            } else {
                $result[] = $relationType;
            }
        }
        
        return $result;
    }
    
    /**
     * add rows to csv body
     * 
     * @param Tinebase_Record_RecordSet $_records
     */
    public function processIteration($_records)
    {
        if (count($_records) === 0) {
            return;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Exporting ' . count($_records) . ' records ...');
        
        $this->_resolveRecords($_records);
    
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_records->toArray(), TRUE));
        
        foreach ($_records as $record) {
            $csvArray = array();
            foreach ($this->_getFields() as $fieldName) {
                if (in_array($fieldName, $this->_relationsTypes)
                    || (! empty($this->_relationsTypes) && preg_match('/^' . implode('|', $this->_relationsTypes) . '-/', $fieldName))
                ) {
                    if (strpos($fieldName, '-') !== FALSE) {
                        list($relationType, $recordField) = explode('-', $fieldName);
                    } else {
                        $relationType = $fieldName;
                        $recordField = NULL;
                    }
                    $csvArray[] = $this->_addRelations($record, $relationType, $recordField, TRUE);
                } else if (in_array($fieldName, $this->_specialFields)) {
                    $arrayFlipped = array_flip($this->_specialFields);
                    $csvArray[] = $this->_addSpecialValue($record, $arrayFlipped[$fieldName]);
                } else if ($fieldName == 'tags') {
                    $csvArray[] = $this->_getTags($record);
                } else if ($fieldName == 'notes') {
                    $csvArray[] = $this->_addNotes($record);
                } else if ($fieldName == 'container_id') {
                    $csvArray[] = $this->_getContainer($record, 'id');
                } else if (in_array($fieldName, $this->_getCustomFieldNames())) {
                    $csvArray[] = $record->customfields[$fieldName];
                } else {
                    $csvArray[] = $record->{$fieldName};
                }
            }
            self::fputcsv($this->_filehandle, $csvArray);
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
