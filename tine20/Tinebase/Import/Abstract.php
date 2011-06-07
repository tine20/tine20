<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * abstract Tinebase Import
 * 
 * @package Tinebase
 * @subpackage  Import
 * 
 */
abstract class Tinebase_Import_Abstract implements Tinebase_Import_Interface
{
    /**
     * possible configs with default values
     * 
     * @var array
     */
    protected $_options = array();
    
    /**
     * additional config options (to be added by child classes)
     * 
     * @var array
     */
    protected $_additionalOptions = array();
    
    /**
     * the record controller
     *
     * @var Tinebase_Controller_Record_Interface
     */
    protected $_controller = NULL;
    
    /**
     * constructs a new importer from given config
     * 
     * @param array $_options
     */
    public function __construct(array $_options = array())
    {
        $this->_options = array_merge($this->_options, $this->_additionalOptions);
        
        foreach($_options as $key => $cfg) {
            if (array_key_exists($key, $this->_options)) {
                $this->_options[$key] = $cfg;
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Creating importer with following config: ' . print_r($this->_options, TRUE));
    }
    
    /**
     * import given filename
     * 
     * @param string $_filename
     * @return @see{Tinebase_Import_Interface::import}
     */
    public function importFile($_filename)
    {
        if (! file_exists($_filename)) {
            throw new Tinebase_Exception_NotFound("File $_filename not found.");
        }
        $resource = fopen($_filename, 'r');
        
        $retVal = $this->import($resource);
        fclose($resource);
        
        return $retVal;
    }
    
    /**
     * import from given data
     * 
     * @param string $_data
     * @return @see{Tinebase_Import_Interface::import}
     */
    public function importData($_data)
    {
        $resource = fopen('php://memory', 'w+');
        fwrite($resource, $_data);
        rewind($resource);
        
        $retVal = $this->import($resource);
        fclose($resource);
        
        return $retVal;
    }
    
    /**
     * returns config from definition
     * 
     * @param Tinebase_Model_ImportExportDefinition $_definition
     * @param array                                 $_options
     * @return array
     */
    public static function getOptionsArrayFromDefinition($_definition, $_options)
    {
        $options = Tinebase_ImportExportDefinition::getOptionsAsZendConfigXml($_definition, $_options);
        $optionsArray = $options->toArray();
        if (! isset($optionsArray['model'])) {
            $optionsArray['model'] = $_definition->model;
        }
        
        return $optionsArray;
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
        
        $record = new $this->_options['model']($_recordData, TRUE);
        
        if ($record->isValid()) {
            if (! $this->_options['dryrun']) {
                
                // check for duplicate
                if ($this->_options['duplicates']) {
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
                if (isset($_recordData['tags']) && is_array($_recordData['tags'])) {
                    $record->tags = $this->_addSharedTags($_recordData['tags']);
                }

                $record = call_user_func(array($this->_controller, $this->_options['createMethod']), $record);
            } else {
                $_result['results']->addRecord($record);
            }
            
            $_result['totalcount']++;
            
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($record->toArray(), true));
            throw new Tinebase_Exception_Record_Validation('Imported record is invalid (' . print_r($record->getValidationErrors(), TRUE) . ')');
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
     *  add/create shared tags if they don't exist
     *
     * @param   array $_tags array of tag strings
     * @return  array with valid tag ids
     */
    protected function _addSharedTags($_tags)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Adding tags: ' . print_r($_tags, TRUE));
        
        $result = array();
        foreach ($_tags as $tag) {
            $tag = trim($tag);
            
            // only check non-empty tags
            if (empty($tag)) {
                continue; 
            }
            
            $name = (strlen($tag) > 40) ? substr($tag, 0, 40) : $tag;
            
            $id = NULL;
            try {
                $existing = Tinebase_Tags::getInstance()->getTagByName($name, NULL, 'Tinebase', TRUE);
                $id = $existing->getId();
                
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . 
                    ' Added existing tag ' . $name . ' to record.');
            } catch (Tinebase_Exception_NotFound $tenf) {
                if (isset($this->_options['shared_tags']) && $this->_options['shared_tags'] == 'create') {
                    // create shared tag
                    $newTag = new Tinebase_Model_Tag(array(
                        'name'          => $name,
                        'description'   => $tag . ' (imported)',
                        'type'          => Tinebase_Model_Tag::TYPE_SHARED,
                        'color'         => '#000099'
                    ));
                    
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Creating new shared tag: ' . $name);
                    
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
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Do not create shared tag (option not set)');
                }
            }
            
            if ($id !== NULL) {
                $result[] = $id;
            }
        }
        
        return $result;
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
                    $result[] = @iconv($this->_options['encoding'], $this->_options['encodingTo'], $singleValue);
                }
                $data[$key] = $result;
            } else {
                $data[$key] = @iconv($this->_options['encoding'], $this->_options['encodingTo'], $value);
            }
        }
        
        return $data;
    }

    /**
     * set controller
     */
    protected function _setController()
    {
        list($appName, $ns, $modelName) = explode('_', $this->_options['model']);
        $this->_controller = Tinebase_Core::getApplicationInstance($appName, $modelName);
    }
}
