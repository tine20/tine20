<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        make _importRecord work
 * @todo        add charset conversion (with iconv?)
 * @todo        add conditions (what to do when record already exists)
 * @todo        add 'dry run' functionality
 */

/**
 * abstract csv import class
 * 
 * @package     Tinebase
 * @subpackage  Import
 * 
 */
abstract class Tinebase_Import_Csv_Abstract implements Tinebase_Import_Interface
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;

    /**
     * @var array
     */
    protected $_options;
    
    /**
     * the record controller
     *
     * @var Tinebase_Application_Controller_Record_Interface
     */
    protected $_controller = NULL;
    
    /**
     * the record model
     *
     * @var string
     */
    protected $_modelName = '';
    
    /**
     * the constructor
     *
     * @param Tinebase_Model_ImportExportDefinition $_definition
     * @param mixed $_controller
     */
    public function __construct(Tinebase_Model_ImportExportDefinition $_definition, $_controller = NULL)
    {
        //print_r($_definition->toArray());
        
        if ($_controller === NULL) {
            list($appName, $ns, $modelName) = explode('_', $_definition->model);
            $controllerName = "{$appName}_Controller_{$modelName}";
            $this->_controller = call_user_func($controllerName . '::getInstance');
        } else {
            $this->_controller = $_controller;
        }
        
        $this->_modelName = $_definition->model;
        $this->_options = $this->_getConfig($_definition->plugin_options);
        $this->_db = Tinebase_Core::getDb();
    }
    
    /**
     * import the data
     *
     * @param string $_filename
     * @param resource $_resource (if $_filename is a stream)
     * @return Tinebase_Record_RecordSet the imported records
     */
    public function import($_filename, $_resource = NULL)
    {
        // read file / stream
        if ($_resource === NULL && !file_exists($_filename)) {
            throw new Tinebase_Exception_NotFound("File $_filename not found.");
        }
        $fileArray = file($_filename);
        
        // get headline
        if ($this->_options['headline']) {
            $headline = trim(array_shift($fileArray));
        } else {
            $headline = array();
        }

        $result = new Tinebase_Record_RecordSet($this->_modelName);
        foreach($fileArray as $line) {
            $record = $this->_createRecord(trim($line), $headline);
            if (!empty($record)) {
                try {
                    $importedRecord = $this->_importRecord($record);
                    $result->addRecord($importedRecord);
                } catch (Exception $e) {
                    //-- don't add incorrect record (name missing for example)
                }
            }
        }
        
        return $result;
    }
    
    /**
     * import single record
     *
     * @param Tinebase_Record_Interface $_record
     * @return Tinebase_Record_Interface
     * 
     * @todo finish implementation
     * @todo check conditions (duplicates, dry-run ...)
     */
    protected function _importRecord($_record)
    {
        $record = $_record;
        return $record;
    }
        
    /**
     * parse a csv line and create record
     *
     * @param string $_line
     * @param string $_headline
     * @return Tinebase_Record_Interface
     * 
     * @todo check mapping
     * @todo add encoding here
     */
    protected function _createRecord($_line, $_headline)
    {
        $delimiter = (isset($this->_options['delimiter'])) ? $this->_options['delimiter'] : ';';
        
        $headline = array_flip(explode($delimiter, $_headline));
        $values = explode($delimiter, $_line);
        $mapping = $this->_options['mapping']['field'];
        
        $data = array();
        foreach($mapping as $field) {
            
            // add single value to destination 
            if (isset($headline[$field['source']]) && isset($values[$headline[$field['source']]])) {
                $data[$field['destination']] = $values[$headline[$field['source']]];
            }
        
            /*
            if (is_array($source)) {
                
                $data[$destination] = '';                
                foreach ($source as $key => $value) {
                    if (is_array($value) || (isset($headline[$value]) && isset($values[$headline[$value]]) && !empty($values[$headline[$value]]))) {
                        if (is_array($value) && !empty($value)) {
                            // match to defined values (i.e. user -> container_id/container id)
                            $keyForValue = $values[$headline[$key]];
                            if (isset($value[$keyForValue])) {
                                $data[$destination] = $value[$keyForValue];
                            }
                        } elseif (!is_numeric($key)) {
                            // add multiple values to one destination field with $key added 
                            if (!empty($data[$destination])) {
                                $data[$destination] .= "\n";
                            }
                            $data[$destination] .= $key . ': ' . $values[$headline[$value]];
                        } else {
                            // add multiple values to one destination field (separated with spaces)
                            if (!empty($data[$destination])) {
                                $data[$destination] .= " ";
                            }
                            $data[$destination] .= $values[$headline[$value]];
                        }
                    }                    
                }
            } 
            */
        }

        // add more values
        $data = array_merge($data, $this->_addData());
        
        // create record and return it
        $record = new $this->_modelName($data);
        return $record;
    }

    /**
     * add some more values (overwrite that if you need some special/dynamic fields)
     *
     * @return array
     */
    protected function _addData()
    {
        return array();
    }
    
    /**
     * write to temp file and read with Zend_Config_Xml
     *
     * @param string $_configString
     * @return array 
     */
    protected function _getConfig($_configString)
    {
        $tmpfname = tempnam(session_save_path(), "tine20");
        
        $handle = fopen($tmpfname, "w");
        fwrite($handle, $_configString);
        fclose($handle);
        
        // read file with Zend_Config_Xml
        $config = new Zend_Config_Xml($tmpfname);
        
        unlink($tmpfname);
        
        return $config->toArray();
    }
}
