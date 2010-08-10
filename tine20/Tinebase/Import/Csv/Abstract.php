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
 * @todo        add generic mechanism for value pre/postfixes? (see accountLoginNamePrefix in Admin_User_Import)
 * @todo        add more conversions e.g. date/accounts
 * @todo        add tests for tags + notes
 * @todo        add more documentation
 * @todo        make it possible to import custom fields?
 */

/**
 * abstract csv import class
 * 
 * @package     Tinebase
 * @subpackage  Import
 * 
 * some documentation for the xml import definition:
 * 
 * <config> main tags
 * <container_id>34</container_id>:     container id for imported records (required)
 * <encoding>UTF-8</encoding>:          encoding of input file
 * <duplicates>1<duplicates>:           check for duplicates
 * <use_headline>0</use_headline>:      just remove the headline/first line but do not use it for mapping
 * 
 * <mapping><field> special tags:
 * <append>glue</append>:               value is appended to destination field with 'glue' as glue
 * <replace>\n</replace>:               replace \r\n with \n
 * <fixed>fixed</fixed>:                the field has a fixed value ('fixed' in this example)
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
     * @var int duplicate record count
     */
    protected $_duplicateCount = 0;
    
    /**
     * the record controller
     *
     * @var Tinebase_Controller_Record_Interface
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
    
    /************************* public functions **************************/
    
    /**
     * the constructor
     *
     * @param Tinebase_Model_ImportExportDefinition $_definition
     * @param mixed $_controller
     * @param array $_options additional options
     * 
     * @todo use Tinebase_Core::getApplicationInstance() to get controller
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
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Importing from file ' . $_filename);
        
        // get headline
        if (isset($this->_options['headline']) && $this->_options['headline']) {
            $headline = $this->_getRawData($_resource);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Got headline: ' . implode(', ', $headline));
            if (isset($this->_options['use_headline']) && $this->_options['use_headline'] == 0) {
                // just read headline but do not use it
                $headline = array();
            }
        } else {
            $headline = array();
        }

        $result = array(
            'results'           => new Tinebase_Record_RecordSet($this->_modelName),
            'totalcount'        => 0,
            'failcount'         => 0,
            'duplicatecount'    => 0,
        );

        while (
            ($recordData = $this->_getRawData($_resource)) !== FALSE && 
            (! $this->_options['dryrun'] 
                || ! (isset($this->_options['dryrunLimit']) && $this->_options['dryrunLimit'] && $result['totalcount'] >= $this->_dryrunCount)
            )
        ) {
            if (is_array($recordData)) {
                try {
                    $recordData = $this->_doMapping($recordData, $headline);
                    
                    if (!empty($recordData)) {
                        $recordData = $this->_doConversions($recordData);

                        // merge additional values (like group id, container id ...)
                        $recordData = array_merge($recordData, $this->_addData($recordData));
                        
                        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($recordData, true));
                        
                        // import record into tine!
                        $importedRecord = $this->_importRecord($recordData, $result);
                    }
                    
                } catch (Exception $e) {
                    // don't add incorrect record (name missing for example)
                    Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e->getTraceAsString());
                    $result['failcount']++;
                }
            }
        }
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
            . ' Import finished. (total: ' . $result['totalcount'] 
            . ' fail: ' . $result['failcount'] . ' duplicates: ' . $result['duplicatecount']. ')');
        
        return $result;
    }
    
    /*************************** protected functions ********************************/
    
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
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($lineData, TRUE));
        if (is_array($lineData) && count($lineData) == 1) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Only got 1 field in line. Wrong delimiter?');
        }
        
        return $lineData;
    }
    
    /**
     * do the mapping and replacements
     *
     * @param array $_data
     * @param array $_headline [optional]
     * @return array
     */
    protected function _doMapping($_data, $_headline = array())
    {
        $data = array();
        $_data_indexed = array();

        if (! empty($_headline) && sizeof($_headline) == sizeof($_data)) {
            $_data_indexed = array_combine($_headline, $_data);
        }

        foreach ($this->_options['mapping']['field'] as $index => $field) {
            if (empty($_data_indexed)) {
                // use import definition order
                
                if ($field['destination'] == '' || !isset($_data[$index])) {
                    continue;
                }
            
                if (isset($field['replace'])) {
                    if ($field['replace'] === '\n') {
                        $_data[$index] = str_replace("\\n", "\r\n", $_data[$index]);
                    }
                }
            
                if (isset($field['separator'])) {
                    $data[$field['destination']] = explode($field['separator'], $_data[$index]);
                } else if (isset($field['fixed'])) {
                    $data[$field['destination']] = $field['fixed'];
                } else {
                    $data[$field['destination']] = $_data[$index];
                }
            } else {
                // use order defined by headline
                
                if ($field['destination'] == '' || !isset($field['source']) || !isset($_data_indexed[$field['source']])) {
                    continue;
                }
            
                if (isset($field['replace'])) {
                    if ($field['replace'] === '\n') {
                        $_data_indexed[$field['source']] = str_replace("\\n", "\r\n", $_data_indexed[$field['source']]);
                    }
                }
            
                if (isset($field['separator'])) {
                    $data[$field['destination']] = explode($field['separator'], $_data_indexed[$field['source']]);
                } else if (isset($field['fixed'])) {
                    $data[$field['destination']] = $field['fixed'];
                } else if (isset($field['append'])) {
                    $data[$field['destination']] .= $field['append'] . $_data_indexed[$field['source']];
                } else {
                    $data[$field['destination']] = $_data_indexed[$field['source']];
                }
            }
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
            if (is_array($value)) {
                $result = array();
                foreach ($value as $singleValue) {
                    $result[] = @iconv($this->_options['encoding'], $this->_encoding, $singleValue);
                }
                $data[$key] = $result;
            } else {
                $data[$key] = @iconv($this->_options['encoding'], $this->_encoding, $value);
            }
        }
        
        return $data;
    }
    
    /**
     * import single record
     *
     * @param array $_recordData
     * @param array $_result
     * @return void
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _importRecord($_recordData, &$_result)
    {
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_recordData, true));
        
        $record = new $this->_modelName($_recordData, TRUE);
        
        if ($record->isValid()) {
            if (! $this->_options['dryrun']) {
                
                // check for duplicate
                if (isset($this->_options['duplicates']) && $this->_options['duplicates']) {
                    // search for record in container and print log message
                    $existingRecords = $this->_controller->search($this->_getDuplicateSearchFilter($record), NULL, FALSE, TRUE);
                    if (count($existingRecords) > 0) {
                        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Duplicate found: ' . $existingRecords[0]);
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($record->toArray(), true));
                        $_result['duplicatecount']++;
                        return;
                    }
                }
                
                // create/add shared tags
                if (isset($_recordData['tags'])) {
                    $record->tags = $this->_addSharedTags($_recordData['tags']);
                }

                $record = call_user_func(array($this->_controller, $this->_createMethod), $record);
            } else {
                $_result['results']->addRecord($record);
            }
            
            $_result['totalcount']++;
            
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($record->toArray(), true));
            throw new Tinebase_Exception_Record_Validation('Imported record is invalid.');
        }
    }
    
    /**
     * get filter for duplicate check
     * 
     * @param Tinebase_Record_Interface $_record
     * @return Tinebase_Model_Filter_FilterGroup
     */
    protected function _getDuplicateSearchFilter(Tinebase_Record_Interface $_record)
    {
        throw new Tinebase_Exception_NotImplemented('You need to implement this function if you want to use the duplicate check.');
    }
        
    /**
     * add some more values (overwrite that if you need some special/dynamic fields)
     *
     * @param  array recordData
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
     * 
     * @todo use Tinebase_ImportExportDefinition::getOptionsAsZendConfigXml()
     */
    protected function _getConfig($_configString, $_options = array())
    {
        $tmpfname = tempnam(Tinebase_Core::getTempDir(), "tine_tempfile_");
        
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

    /**
     *  add/create shared tags if they don't exist
     *
     * @param   array $_tags array of tag strings
     * @return  array with valid tag ids
     */
    protected function _addSharedTags($_tags)
    {
        $result = array();
        foreach ($_tags as $tag) {
            // only check non-empty tags
            if (empty($tag)) {
                continue; 
            }
            
            $name = (strlen($tag) > 20) ? substr($tag, 0, 20) : $tag;
            
            try {
                $existing = Tinebase_Tags::getInstance()->getTagByName($name, NULL, 'Tinebase', TRUE);
                $id = $existing->getId();
            } catch (Tinebase_Exception_NotFound $tenf) {
                if (isset($this->_options['shared_tags']) && $this->_options['shared_tags'] == 'create') {
                    // create shared tag
                    $newTag = new Tinebase_Model_Tag(array(
                        'name'          => $name,
                        'description'   => $tag . ' (imported)',
                        'type'          => Tinebase_Model_Tag::TYPE_SHARED,
                        'color'         => '#000099'
                    ));
                    
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' create new tag: ' . print_r($newTag->toArray(), true));
                    
                    $newTag = Tinebase_Tags::getInstance()->createTag($newTag);
                    
                    $right = new Tinebase_Model_TagRight(array(
                        'tag_id'        => $newTag->getId(),
                        'account_type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                        'account_id'    => 0,
                        'view_right'    => TRUE,
                        'use_right'     => TRUE,
                    ));
                    Tinebase_Tags::getInstance()->setRights($right);
                    Tinebase_Tags::getInstance()->setContexts(array('any'), $newTag->getId());
                    
                    $id = $newTag->getId();
                }
            }
            $result[] = $id;
        }
        
        return $result;
    }
}
