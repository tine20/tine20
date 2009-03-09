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
 * @todo        add charset conversion (with iconv?)
 * @todo        add conditions (what to do when record already exists)
 * @todo        add generic mechanism for value pre/postfixes? (see accountLoginNamePrefix in Admin_User_Import)
 * 
 * @todo use fgetcsv!!!
 * 
 * @todo problmatic mapping
 * @todo add converstions e.g. date/accounts
 * @todo improve options handing
 * @todo $this->_db???
 * 
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
     * @var int max line length
     */
    protected $_maxLineLength = 8000;
    
    /**
     * @var char delimeter
     */
    protected $_delimiter = ',';
    
    /**
     * @var char enclosure
     */
    protected $_enclosure = '"';
    
    /**
     * @var char escape
     */
    protected $_escape = '\\';
    
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
     * name of create method for imported records
     *
     * @var string
     */
    protected $_createMethod = 'create';
    
    /**
     * the constructor
     *
     * @param Tinebase_Model_ImportExportDefinition $_definition
     * @param mixed $_controller
     * @param array $_options additional options
     */
    public function __construct(Tinebase_Model_ImportExportDefinition $_definition, $_controller = NULL, $_options = array())
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
        $this->_options = $this->_getConfig($_definition->plugin_options, $_options);
        $this->_db = Tinebase_Core::getDb();
    }
    
    /**
     * import the data
     *
     * @param  string $_filename
     * @param  resource $_resource (if $_filename is a stream)
     * @return Tinebase_Record_RecordSet the imported records // why?? this may become far to large!
     */
    public function import($_filename, $_resource = NULL)
    {
        // read file / stream
        if ($_resource === NULL) {
          if (! file_exists($_filename)) {
                throw new Tinebase_Exception_NotFound("File $_filename not found.");
          }
          
          $_resource = fopen($_filename, 'r');
        }
        
        // get headline
        if (isset($this->_options['headline']) && $this->_options['headline']) {
            $headline = $this->_getLine($_resource);
        } else {
            $headline = array();
        }

        $result = new Tinebase_Record_RecordSet($this->_modelName);
        while ($recordData = $this->_getLine($_resource)) {
            if (! empty($recordData)) {
                try {
                    $importedRecord = $this->_importRecord($recordData);
                    $result->addRecord($importedRecord);
                } catch (Exception $e) {
                    // don't add incorrect record (name missing for example)
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e->getTraceAsString());
                }
            }
        }
        
        return $result;
    }
    
    /**
     * get a line from csv
     * 
     * @todo convert charset
     *
     * @param  resource $_resource
     * @return array
     */
    protected function _getLine($_resource) {
        $lineData = fgetcsv($_resource, $this->_maxLineLength, $this->_delimiter, $this->_enclosure, $this->_escape);
        
        return $lineData;
    }
    
    /**
     * import single record
     *
     * @param array $_data
     * @return Tinebase_Record_Interface
     * 
     * @todo check conditions (duplicates, ...)
     */
    protected function _importRecord($_data)
    {
        $record = new $this->_modelName($_data);
        
        if (!isset($this->_options['dryrun']) || !$this->_options['dryrun']) {
            $record = call_user_func(array($this->_controller, $this->_createMethod), $record);
        }
        return $record;
    }
        
    /**
     * parse a csv line and create record data
     *
     * @param string $_line
     * @param string $_headline
     * @return array
     * 
     * @todo add encoding here
     */
    protected function _parseLine($_line, $_headline)
    {
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
        
        return $data;
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
     * @param array $_options additional options
     * @return array 
     */
    protected function _getConfig($_configString, $_options = array())
    {
        $tmpfname = tempnam(session_save_path(), "tine20");
        
        $handle = fopen($tmpfname, "w");
        fwrite($handle, $_configString);
        fclose($handle);
        
        // read file with Zend_Config_Xml
        $config = new Zend_Config_Xml($tmpfname, null, TRUE);
        $config->merge(new Zend_Config($_options));
        
        unlink($tmpfname);
        
        $this->_maxLineLength = $config->maxLineLength ? $config->maxLineLength : $this->_maxLineLength;
        $this->_delimiter = $config->delimiter ? $config->delimiter : $this->_delimiter;
        $this->_enclosure = $config->enclosure ? $config->enclosure : $this->_enclosure;
        $this->_escape = $config->escape ? $config->escape : $this->_escape;
        
        return $config->toArray();
    }
}
