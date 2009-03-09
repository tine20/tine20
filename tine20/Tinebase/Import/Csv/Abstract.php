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
 * @todo        add conditions (what to do when record already exists)
 * @todo        add generic mechanism for value pre/postfixes? (see accountLoginNamePrefix in Admin_User_Import)
 * @todo        add more conversions e.g. date/accounts
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
     * @var array
     */
    protected $_options;
    
    /**
     * @var int max line length
     */
    protected $_maxLineLength = 8000;
    
    /**
     * @var char delimiter
     */
    protected $_delimiter = ',';
    
    /**
     * special delimiter
     *
     * @var array
     */
    protected $_specialDelimiter = array(
        'TAB'   => "\t"
    );
    
    /**
     * @var char enclosure
     */
    protected $_enclosure = '"';
    
    /**
     * @var char escape
     */
    protected $_escape = '\\';

    /**
     * @var default input encoding
     */
    protected $_encoding = 'UTF-8';
    
    /**
     * @var int dryrun record count
     */
    protected $_dryrunCount = 20;
    
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
        if ($_controller === NULL) {
            list($appName, $ns, $modelName) = explode('_', $_definition->model);
            $controllerName = "{$appName}_Controller_{$modelName}";
            $this->_controller = call_user_func($controllerName . '::getInstance');
        } else {
            $this->_controller = $_controller;
        }
        
        $this->_modelName = $_definition->model;
        $this->_options = $this->_getConfig($_definition->plugin_options, $_options);
    }
    
    /**
     * import the data
     *
     * @param  string $_filename
     * @param  resource $_resource (if $_filename is a stream)
     * @return array with Tinebase_Record_RecordSet the imported records (if dryrun) and totalcount 
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
            $headline = $this->_getRawData($_resource);
        } else {
            $headline = array();
        }

        $result = array(
            'results'       => new Tinebase_Record_RecordSet($this->_modelName),
            'totalcount'    => 0
        );

        while (
            ($recordData = $this->_getRawData($_resource)) !== FALSE && 
            (!$this->_options['dryrun'] || $result['totalcount'] < $this->_dryrunCount)
        ) {
            if (is_array($recordData)) {
                try {
                    $recordData = $this->_doMapping($recordData, $headline);
                    $recordData = $this->_doConversions($recordData);
                    // merge additional values (like group id, container id ...)
                    $recordData = array_merge($recordData, $this->_addData());
                    
                    //print_r($recordData);
                    
                    // import record into tine!
                    $importedRecord = $this->_importRecord($recordData);
                    
                    if ($this->_options['dryrun']) {
                        $result['results']->addRecord($importedRecord);
                    }
                    $result['totalcount']++;
                    
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
     * get raw data of a single record
     * 
     * @param  resource $_resource
     * @return array
     */
    protected function _getRawData($_resource) 
    {
        $lineData = fgetcsv(
            $_resource, 
            $this->_options['maxLineLength'], 
            $this->_options['delimiter'], 
            $this->_options['enclosure'] 
            // escape param is only available in PHP >= 5.3.0
            // $this->_options['escape']
        );
        
        return $lineData;
    }
    
    /**
     * do the mapping
     *
     * @param array $_data
     * @param array $_headline [optional]
     * @return array
     * 
     * @todo add headline parsing again?
     */
    protected function _doMapping($_data, $_headline = array())
    {
        $data = array();
        foreach ($this->_options['mapping']['field'] as $field) {
            if ($field['destination'] == '' || !isset($_data[$field['index']])) {
                continue;
            }
            //$data[$field['destination']] = $_data[$headline[$field['source']]];
            $data[$field['destination']] = $_data[$field['index']];
        }
        
        return $data;
    }
    
    /**
     * do conversions (transformations, charset, ...)
     *
     * @param array $_data
     * @return array
     * 
     * @todo add date and other conversions
     */
    protected function _doConversions($_data)
    {
        $data = array();
        foreach ($_data as $key => $value) {
            $data[$key] = @iconv($this->_options['encoding'], $this->_encoding, $value);
        }
        
        return $data;
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
        
        if (!$this->_options['dryrun']) {
            $record = call_user_func(array($this->_controller, $this->_createMethod), $record);
        }
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
        
        $config->maxLineLength = $config->maxLineLength ? $config->maxLineLength : $this->_maxLineLength;
        $config->enclosure = $config->enclosure ? $config->enclosure : $this->_enclosure;
        $config->escape = $config->escape ? $config->escape : $this->_escape;
        $config->dryrun = $config->dryrun ? $config->dryrun : 0;
        $config->encoding = $config->encoding ? $config->encoding : $this->_encoding;

        if ($config->delimiter) {
            $config->delimiter = (isset($this->_specialDelimiter[$config->delimiter])) 
                ? $this->_specialDelimiter[$config->delimiter] 
                : $config->delimiter;
        } else {
            $config->delimiter = $this->_delimiter;      
        }
        
        return $config->toArray();
    }
}
