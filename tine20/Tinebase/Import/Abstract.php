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
        'updateMethod'      => 'update',
        'createMethod'      => 'create',
        'model'             => '',
        'shared_tags'       => 'create', //'onlyexisting',
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
        $clientRecordData = $this->_sortClientRecordsByIndex($_clientRecordData);
        
        $recordIndex = 0;
        while (($recordData = $this->_getRawData($_resource)) !== FALSE) {
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Importing record ' . $recordIndex . ' ...');
            
            $recordToImport = NULL;
            try {
                if (isset($clientRecordData[$recordIndex])) {
                    // client record overwrites record in import data (only if set)
                    $recordDataToImport = (isset($clientRecordData[$recordIndex]['recordData'])) 
                        ? $clientRecordData[$recordIndex]['recordData'] : $this->_processRawData($recordData);
                    $resolveStrategy = $clientRecordData[$recordIndex]['resolveStrategy'];
                } else {
                    $recordDataToImport = $this->_processRawData($recordData);
                    $resolveStrategy = NULL;
                }
                
                if (empty($recordDataToImport)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        . ' Empty record data.');
                    continue;
                }
                    
                $recordToImport = $this->_createRecordToImport($recordDataToImport);
                if ($resolveStrategy !== 'discard') {
                    $importedRecord = $this->_importRecord($recordToImport, $resolveStrategy, $recordDataToImport);
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                        . ' Discarding record ' . $recordIndex);
                    
                    // just add autotags to record (if id is available)
                    if ($recordToImport->getId()) {
                        $this->_addAutoTags($recordToImport);
                        call_user_func(array($this->_controller, $this->_options['updateMethod']), $recordToImport);
                    }
                }
                    
            } catch (Exception $e) {
                $this->_handleImportException($e, $recordIndex, $recordToImport);
            }
            
            $recordIndex++;
        }
    }
    
    /**
     * sort client data array
     * 
     * @param array $_clientRecordData
     * @return array
     */
    protected function _sortClientRecordsByIndex($_clientRecordData)
    {
        $result = array();
        
        foreach ($_clientRecordData as $data) {
            if (isset($data['index'])) {
                $result[$data['index']] = $data;
            }
        }
        
        return $result;
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
     * do conversions (transformations, charset, replacements ...)
     *
     * @param array $_data
     * @return array
     * 
     * @todo add date and other conversions
     * @todo add generic mechanism for value pre/postfixes? (see accountLoginNamePrefix in Admin_User_Import)
     */
    protected function _doConversions($_data)
    {
        if (isset($this->_options['mapping'])) {
            $data = $this->_doMappingConversion($_data);
        } else {
            $data = $_data;
        }

        foreach ($data as $key => $value) {
            $data[$key] = $this->_encode($value);
        }
                
        return $data;
    }
    
    /**
     * do the mapping conversions defined in field configs
     *
     * @param array $_data
     * @return array
     */
    protected function _doMappingConversion($_data)
    {
        $data = $_data;
        foreach ($this->_options['mapping']['field'] as $index => $field) {
            if (! array_key_exists('destination', $field) || $field['destination'] == '' || ! isset($_data[$field['destination']])) {
                continue;
            }
        
            $key = $field['destination'];
        
            if (isset($field['replace'])) {
                if ($field['replace'] === '\n') {
                    $data[$key] = str_replace("\\n", "\r\n", $_data[$key]);
                }
            } else if (isset($field['separator'])) {
                $data[$key] = preg_split('/\s*' . $field['separator'] . '\s*/', $_data[$key]);
            } else if (isset($field['fixed'])) {
                $data[$key] = $field['fixed'];
            } else if (isset($field['append'])) {
                $data[$key] .= $field['append'] . $_data[$key];
            } else {
                $data[$key] = $_data[$key];
            }
        }
        
        return $data;
    }
    
    /**
     * encode values
     * 
     * @param string|array $_value
     * @return string|array
     */
    protected function _encode($_value)
    {
        if (! isset($this->_options['encoding']) || ! isset($this->_options['encodingTo']) || $this->_options['encoding'] === $this->_options['encodingTo']) {
            return $_value;
        }
        
        if (is_array($_value)) {
            $result = array();
            foreach ($_value as $singleValue) {
                $result[] = @iconv($this->_options['encoding'], $this->_options['encodingTo'], $singleValue);
            }
        } else {
            $result = @iconv($this->_options['encoding'], $this->_options['encodingTo'], $_value);
        }
        
        return $result;
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
        $record = new $this->_options['model'](array(), TRUE);
        $record->setFromJsonInUsersTimezone($_recordData);
        
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
        
        $this->_handleTags($_record, $_resolveStrategy);
        $importedRecord = $this->_importAndResolveConflict($_record, $_resolveStrategy);
        
        $this->_importResult['results']->addRecord($importedRecord);
        
        if ($this->_options['dryrun']) {
            Tinebase_TransactionManager::getInstance()->rollBack();
        } else if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Successfully imported record with id ' . $importedRecord->getId());
        }
        
        $this->_importResult['totalcount']++;
    }
    
    /**
     * handle record tags
     * 
     * @param Tinebase_Record_Abstract $_record
     * @param string $_resolveStrategy
     */
    protected function _handleTags($_record, $_resolveStrategy = NULL)
    {
        if (isset($_record->tags) && is_array($_record->tags)) {
            $_record->tags = $this->_addSharedTags($_record->tags);
        } else {
            $_record->tags = NULL;
        }
        
        if ($_resolveStrategy === NULL && ! empty($this->_options['autotags'])) {
            // only add autotags for "new" records
            $this->_addAutoTags($_record);
        }
    }
    
    /**
    * add/create shared tags if they don't exist
    *
    * @param   array $_tags array of tag strings
    * @return  Tinebase_Record_RecordSet with Tinebase_Model_Tag
    */
    protected function _addSharedTags($_tags)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Adding tags: ' . print_r($_tags, TRUE));
    
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Tag');
        foreach ($_tags as $tagData) {
            $tagData = (is_array($tagData)) ? $tagData : array('name' => $tagData);
            $tagName = trim($tagData['name']);
    
            // only check non-empty tags
            if (empty($tagName)) {
                continue;
            }
    
            $createTag = (isset($this->_options['shared_tags']) && $this->_options['shared_tags'] == 'create');
            $tagToAdd = $this->_getSingleTag($tagName, $tagData, $createTag);
            if ($tagToAdd) {
                $result->addRecord($tagToAdd);
            }
        }
    
        return $result;
    }
    
    /**
     * get tag / create on the fly
     * 
     * @param string $_name
     * @param array $_tagData
     * @param boolean $_create
     * @return Tinebase_Model_Tag
     */
    protected function _getSingleTag($_name, $_tagData = array(), $_create = TRUE)
    {
        $name = (strlen($_name) > 40) ? substr($_name, 0, 40) : $_name;
        
        $tag = NULL;
        try {
            $tag = Tinebase_Tags::getInstance()->getTagByName($name, NULL, 'Tinebase', TRUE);
        
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
            	' Added existing tag ' . $name . ' to record.');
            
        } catch (Tinebase_Exception_NotFound $tenf) {
            if ($_create) {
                $tagData = (! empty($_tagData)) ? $_tagData : array(
                    'name'          => $name,
                );
                $tag = $this->_createTag($tagData);
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                    . ' Do not create shared tag (option not set)');
            }
        }
        
        return $tag;
    }
    
    /**
     * create new tag
     * 
     * @param array $_tagData
     * @return Tinebase_Model_Tag
     * 
     * @todo allow to set contexts / application / rights
     * @todo only ignore acl for autotags that are present in import definition
     */
    protected function _createTag($_tagData)
    {
        $description  = substr((isset($_tagData['description'])) ? $_tagData['description'] : $_tagData['name'] . ' (imported)', 0, 50);
        $type         = (isset($_tagData['type']) && ! empty($_tagData['type'])) ? $_tagData['type'] : Tinebase_Model_Tag::TYPE_SHARED;
        $color        = (isset($_tagData['color'])) ? $_tagData['color'] : '#ffffff';
                
        $newTag = new Tinebase_Model_Tag(array(
            'name'          => $_tagData['name'],
            'description'   => $description,
            'type'          => strtolower($type),
            'color'         => $color,
        ));
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Creating new shared tag: ' . $_tagData['name']);
        
        $tag = Tinebase_Tags::getInstance()->createTag($newTag, TRUE);
        
        $right = new Tinebase_Model_TagRight(array(
            'tag_id'        => $newTag->getId(),
            'account_type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
            'account_id'    => 0,
            'view_right'    => TRUE,
            'use_right'     => TRUE,
        ));
        Tinebase_Tags::getInstance()->setRights($right);
        Tinebase_Tags::getInstance()->setContexts(array('any'), $newTag->getId());
        
        return $tag;
    }
    
    /**
    * add auto tags from options
    *
    * @param Tinebase_Record_Abstract $_record
    */
    protected function _addAutoTags($_record)
    {
        $autotags = $this->_sanitizeAutotagsOption();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
        	' Trying to add ' . count($autotags) . ' autotag(s) to record.');
        
        $tags = ($_record->tags instanceof Tinebase_Record_RecordSet) ? $_record->tags : new Tinebase_Record_RecordSet('Tinebase_Model_Tag');
        foreach ($autotags as $tagData) {
            $tagData = $this->_doAutoTagReplacements($tagData);
            $tag = $this->_getSingleTag($tagData['name'], $tagData);
            if ($tag !== NULL) {
                $tags->addRecord($tag);
            }
        }
        $_record->tags = $tags;
    }
    
    /**
     * replace some strings in autotags (name + description)
     * 
     * @param array $_tagData
     * @return array
     */
    protected function _doAutoTagReplacements($_tagData)
    {
        $result = $_tagData;
        
        $search = array(
        	'###CURRENTDATE###', 
        	'###CURRENTTIME###', 
        	'###USERFULLNAME###'
        );
        $now = Tinebase_DateTime::now();
        $replacements = array(
            Tinebase_Translation::dateToStringInTzAndLocaleFormat($now, NULL, NULL, 'date'),
            Tinebase_Translation::dateToStringInTzAndLocaleFormat($now, NULL, NULL, 'time'),
            Tinebase_Core::getUser()->accountDisplayName
        );
        $fields = array('name', 'description');
        
        foreach ($fields as $field) {
            if (isset($result[$field])) {
                $result[$field] = str_replace($search, $replacements, $result[$field]);
            }
        }
        
        return $result;
    }
    
    /**
     * sanitize autotag option
     * 
     * @return array
     */
    protected function _sanitizeAutotagsOption()
    {
        $autotags = (array_key_exists('tag', $this->_options['autotags']) && count($this->_options['autotags']) == 1) 
            ? $this->_options['autotags']['tag'] : $this->_options['autotags'];
        $autotags = (array_key_exists('name', $autotags)) ? array($autotags) : $autotags;
        
        if (array_key_exists('tag', $autotags)) {
            unset($autotags['tag']);
        }
        
        return $autotags;
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
     * 
     * @todo replace mergeTheirs + mergeMine (=merge)
     */
    protected function _importAndResolveConflict(Tinebase_Record_Abstract $_record, $_resolveStrategy = NULL)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' resolveStrategy: ' . $_resolveStrategy);
        
        switch ($_resolveStrategy) {
            case 'mergeTheirs':
            case 'mergeMine':
                $record = call_user_func(array($this->_controller, $this->_options['updateMethod']), $_record, FALSE);
                break;
            case 'keep':
                // do not check for duplicates
                $record = call_user_func(array($this->_controller, $this->_options['createMethod']), $_record, FALSE);
                break;
            default:
                $record = call_user_func(array($this->_controller, $this->_options['createMethod']), $_record);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($record->toArray(), TRUE));
        
        return $record;
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
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $_e->getTraceAsString());
        
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
