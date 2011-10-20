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
 */
abstract class Tinebase_Import_Abstract implements Tinebase_Import_Interface
{
    /**
     * import result array
     * 
     * @var array
     */
    protected $_importResult = array(
        'results'           => NULL,
        'exceptions'        => NULL,
        'totalcount'        => 0,
        'failcount'         => 0,
        'duplicatecount'    => 0,
    );
    
    /**
     * possible configs with default values
     * 
     * @var array
     */
    protected $_options = array(
        'dryrun'            => FALSE,
        'createMethod'      => 'create',
        'model'             => '',
        'shared_tags'       => 'onlyexisting',
        'autotags'          => array(),
    );
    
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
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' Creating importer with following config: ' . print_r($this->_options, TRUE));
    }
    
    /**
     * import given filename
     * 
     * @param string $_filename
     * @param array $_clientRecordData
     * @return @see{Tinebase_Import_Interface::import}
     */
    public function importFile($_filename, $_clientRecordData = array())
    {
        if (! file_exists($_filename)) {
            throw new Tinebase_Exception_NotFound("File $_filename not found.");
        }
        $resource = fopen($_filename, 'r');
        
        $retVal = $this->import($resource, $_clientRecordData);
        fclose($resource);
        
        return $retVal;
    }
    
    /**
     * import from given data
     * 
     * @param string $_data
     * @param array $_clientRecordData
     * @return @see{Tinebase_Import_Interface::import}
     */
    public function importData($_data, $_clientRecordData = array())
    {
        $resource = fopen('php://memory', 'w+');
        fwrite($resource, $_data);
        rewind($resource);
        
        $retVal = $this->import($resource);
        fclose($resource);
        
        return $retVal;
    }
    
    /**
     * import the data
     *
     * @param  resource $_resource (if $_filename is a stream)
     * @param array $_clientRecordData
     * @return array with import data (imported records, failures, duplicates and totalcount)
     */
    public function import($_resource = NULL, $_clientRecordData = array())
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
            . ' Starting import of ' . ((! empty($this->_options['model'])) ? $this->_options['model'] . 's' : ' records'));
        
        $this->_initImportResult();
        $this->_beforeImport($_resource);
        $this->_doImport($_resource, $_clientRecordData);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
            . ' Import finished. (total: ' . $this->_importResult['totalcount'] 
            . ' fail: ' . $this->_importResult['failcount'] 
            . ' duplicates: ' . $this->_importResult['duplicatecount']. ')');
        
        return $this->_importResult;
    }
    
    /**
     * init import result data
     */
    protected function _initImportResult()
    {
        $this->_importResult['results']     = (! empty($this->_options['model'])) ? new Tinebase_Record_RecordSet($this->_options['model']) : array();
        $this->_importResult['exceptions']  = new Tinebase_Record_RecordSet('Tinebase_Model_ImportException');
    }
    
    /**
     * do something before the import
     * 
     * @param resource $_resource
     */
    protected function _beforeImport($_resource = NULL)
    {
        
    }
    
    /**
     * do import: loop data -> convert to records -> import records
     * 
     * @param resource $_resource
     * @param array $_clientRecordData
     */
    protected function _doImport($_resource = NULL, $_clientRecordData = array())
    {
        $recordIndex = 0;
        while (($recordData = $this->_getRawData($_resource)) !== FALSE) {
            $recordToImport = NULL;
            try {
                if (isset($_clientRecordData[$recordIndex])) {
                    // client record overwrites record in import data
                    $recordDataToImport = $_clientRecordData[$recordIndex]['recordData'];
                    $resolveStrategy = $_clientRecordData[$recordIndex]['resolveStrategy'];
                } else {
                    $recordDataToImport = $this->_processRawData($recordData);
                    $resolveStrategy = NULL;
                }
                    
                if (! empty($recordDataToImport) || $resolveStrategy === 'discard') {
                    $recordToImport = $this->_createRecordToImport($recordDataToImport);
                    $importedRecord = $this->_importRecord($recordToImport, $resolveStrategy, $recordDataToImport);
                }
                    
            } catch (Exception $e) {
                $this->_handleImportException($e, $recordIndex, $recordToImport);
            }
            
            $recordIndex++;
        }
    }
    
    /**
     * process raw data (mapping + conversions)
     * 
     * @param array $_data
     * @return array|NULL
     */
    protected function _processRawData($_data)
    {
        $result = NULL;
        $mappedData = $this->_doMapping($_data);
        
        if (! empty($mappedData)) {
            $convertedData = $this->_doConversions($mappedData);

            // merge additional values (like group id, container id ...)
            $result = array_merge($convertedData, $this->_addData($convertedData));
            
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                . ' Merged data: ' . print_r($result, true));
                
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Got empty record from mapping! Was: ' . print_r($_data, TRUE));
            $this->_importResult['failcount']++;
        }
        
        return $result;
    }
    
    /**
     * do the mapping and replacements
     *
     * @param array $_data
     * @return array
     */
    protected function _doMapping($_data)
    {
        return $_data;
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
     * add some more values (overwrite that if you need some special/dynamic fields)
     *
     * @param  array recordData
     */
    protected function _addData()
    {
        return array();
    }
    
    /**
     * create record from record data
     * 
     * @param array $_recordData
     * @return Tinebase_Record_Abstract
     */
    protected function _createRecordToImport($_recordData)
    {
        $record = new $this->_options['model']($_recordData, TRUE);
        
        return $record;
    }
    
    /**
     * import single record
     *
     * @param Tinebase_Record_Abstract $_record
     * @param string $_resolveStrategy
     * @param array $_recordData
     * @return void
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _importRecord($_record, $_resolveStrategy = NULL, $_recordData = array())
    {
        $_record->isValid(TRUE);
        
        if ($this->_options['dryrun']) {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        }
        
        $this->_handleTags($_record);
        $importedRecord = $this->_importAndResolveConflict($_record, $_resolveStrategy);
        
        $this->_importResult['results']->addRecord($importedRecord);
        
        if ($this->_options['dryrun']) {
            Tinebase_TransactionManager::getInstance()->rollBack();
        }
        
        $this->_importResult['totalcount']++;
    }
    
    /**
     * handle record tags
     * 
     * @param Tinebase_Record_Abstract $_record
     */
    protected function _handleTags($_record)
    {
        if (isset($_record->tags) && is_array($_record->tags)) {
            $_record->tags = $this->_addSharedTags($_record->tags);
        } else {
            $_record->tags = NULL;
        }
        
        if (! empty($this->_options['autotags'])) {
            $this->_addAutoTags($_record);
        }
    }
    
    /**
    * add/create shared tags if they don't exist
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
    
            $tagId = $this->_getSingleTag($tag);
            if ($tagId) {
                $result[] = $tagId;
            }
        }
    
        return $result;
    }
    
    /**
     * get tag / create on the fly
     * 
     * @param string $_name
     * @param string $_description
     * @param string $_type
     * 
     * @todo allow to set contexts / application / color / rights
     * @todo catch Tinebase_Exception_AccessDenied when trying to create shared tag
     */
    protected function _getSingleTag($_name, $_tagData = array())
    {
        $name = (strlen($_name) > 40) ? substr($_name, 0, 40) : $_name;
        
        $id = NULL;
        try {
            $existing = Tinebase_Tags::getInstance()->getTagByName($name, NULL, 'Tinebase', TRUE);
            $id = $existing->getId();
        
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
            	' Added existing tag ' . $name . ' to record.');
            
        } catch (Tinebase_Exception_NotFound $tenf) {
            $description  = substr((isset($_tagData['description'])) ? $_tagData['description'] : $_name . ' (imported)', 0, 50);
            $type         = (isset($_tagData['type'])) ? $_tagData['type'] : Tinebase_Model_Tag::TYPE_SHARED;
            
            if (isset($this->_options['shared_tags']) && $this->_options['shared_tags'] == 'create') {
                // create new tag
                $newTag = new Tinebase_Model_Tag(array(
                                    'name'          => $name,
                                    'description'   => $description,
                                    'type'          => $type,
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
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Do not create shared tag (option not set)');
            }
        }
        
        return $id;
    }
    
    /**
    * add auto tags from options
    *
    * @param Tinebase_Record_Abstract $_record
    */
    protected function _addAutoTags($_record)
    {
        $autotags = (array_key_exists('name', $this->_options['autotags']['tag'])) ? array($this->_options['autotags']['tag']) : $this->_options['autotags']['tag'];
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
        	' Trying to add ' . count($autotags) . ' autotag(s) to record.');
        
        $tags = $_record->tags;
        foreach ($autotags as $tagData) {
            $tagId = $this->_getSingleTag($tagData['name'], $tagData);
            if ($tagId !== NULL) {
                array_push($tags, $tagId);
            }
        }
        $_record->tags = $tags;
    }
    
    /**
     * import record and resolve possible conflicts
     * 
     * supports $_resolveStrategy(s): ['mergeTheirs', ('Merge, keeping existing details')],
     *                              ['mergeMine',   ('Merge, keeping my details')],
     *                              ['keep',        ('Keep both records')]
     * 
     * @param Tinebase_Record_Abstract $_record
     * @param string $_resolveStrategy
     * @return Tinebase_Record_Abstract
     */
    protected function _importAndResolveConflict(Tinebase_Record_Abstract $_record, $_resolveStrategy = NULL)
    {
        switch ($_resolveStrategy) {
            case 'mergeTheirs':
                $existing = $this->_controller->get($_record->getId());
                $record = $this->_mergeRecords($existing, $_record);
                break;
            case 'mergeMine':
                $existing = $this->_controller->get($_record->getId());
                $record = $this->_mergeRecords($_record, $existing);
                break;
            case 'keep':
                // do not check for duplicates
                $record = call_user_func(array($this->_controller, $this->_options['createMethod']), $_record, FALSE);
                break;
            default:
                $record = call_user_func(array($this->_controller, $this->_options['createMethod']), $_record);
        }
        
        return $record;
    }
    
    /**
     * merge two records
     * 
     * @param Tinebase_Record_Abstract $_recordKeep
     * @param Tinebase_Record_Abstract $_recordDiscard
     * @return Tinebase_Record_Abstract
     * 
     * @todo move this to Tinebase_Record_Abstract?
     */
    protected function _mergeRecords(Tinebase_Record_Abstract $_recordKeep, Tinebase_Record_Abstract $_recordDiscard)
    {
        $modlogFields = $_recordKeep->getModlogOmitFields();
        
        $diff = $_recordKeep->diff($_recordDiscard);
        foreach ($diff as $key => $value) {
            if (empty($_recordKeep->{$key}) && ! in_array($key, $modlogFields)) {
                $_recordKeep->{$key} = $value;
            }
        }
        
        return $_recordKeep;
    }
    
    /**
     * handle import exceptions
     * 
     * @param Exception $_e
     * @param integer $_recordIndex
     * @param Tinebase_Record_Abstract $_record
     * 
     * @todo use json converter for client record
     */
    protected function _handleImportException(Exception $_e, $_recordIndex, $_record = NULL)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' ' . $_e->getMessage());
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $_e->getTraceAsString());
        
        if ($_e instanceof Tinebase_Exception_Duplicate) {
            $this->_importResult['duplicatecount']++;
            $exception = $_e->toArray();
        } else {
            $this->_importResult['failcount']++;
            $exception = array(
                'code'		   => $_e->getCode(),
                'message'	   => $_e->getMessage(),
            	'clientRecord' => ($_record !== NULL) ? $_record->toArray() : array(),
            );
        }        

        $this->_importResult['exceptions']->addRecord(new Tinebase_Model_ImportException(array(
            'code'		    => $_e->getCode(),
            'message'	    => $_e->getMessage(),
        	'exception'     => $exception,
            'index'         => $_recordIndex,
        )));
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
     * set controller
     */
    protected function _setController()
    {
        $this->_controller = Tinebase_Core::getApplicationInstance($this->_options['model']);
    }
}
