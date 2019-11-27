<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Application
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Abstract class for an Tine 2.0 application with Json interface
 * Each tine application must extend this class to gain an native tine 2.0 user
 * interface.
 *
 * @package     Tinebase
 * @subpackage  Application
 */
abstract class Tinebase_Frontend_Json_Abstract extends Tinebase_Frontend_Abstract implements Tinebase_Frontend_Json_Interface
{
    /**
     * get totalcount from controller
     */
    const TOTALCOUNT_CONTROLLER  = 'controller';

    /**
     * get totalcount by just counting resultset
     */
    const TOTALCOUNT_COUNTRESULT = 'countresult';

    /**
     * default model (needed for application starter -> defaultContentType)
     * @var string
     */
    protected $_defaultModel = null;
    
    /**
     * All configured models
     * @var array
     */
    protected $_configuredModels = null;

    /**
     * default import definition name
     *
     * @var string
     */
    protected $_defaultImportDefinitionName = null;

    /**
     * the model from which the pagination object should read its configuration
     *
     * @var string
     */
    protected $_paginationModel = null;

    /**
     * Configured plugins for filter model
     * @var array
     */
    protected static $_filterPlugins = array();

    /**
     * Returns registry data of the application.
     *
     * Each application has its own registry to supply static data to the client.
     * Registry data is queried only once per session from the client.
     *
     * This registry must not be used for rights or ACL purposes. Use the generic
     * rights and ACL mechanisms instead!
     *
     * @return mixed array 'variable name' => 'data'
     */
    public function getRegistryData()
    {
        return array();
    }

    /**
     * Returns all relatable models for this app
     * 
     * @return array
     */
    public function getRelatableModels()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' This method is deprecated and will be removed. Please use Tinebase_ModelFactory with Tinebase_ModelConfiguration!');
        
        if (property_exists($this, '_relatableModels') && is_array($this->_relatableModels)) {
            return Tinebase_Relations::getConstraintsConfigs($this->_relatableModels);
        } else {
            return array();
        }
    }
    
    /**
     * returns model configurations for application starter
     *
     * @return array
     */
    public function getModelsConfiguration()
    {
        $ret = NULL;
        if ($this->_configuredModels) {
            return Tinebase_ModelConfiguration::getFrontendConfigForModels($this->_configuredModels, $this->_applicationName);
        }
        
        return $ret;
    }

    /**
     * Return a single record
     *
     * @param   string $_uid
     * @param   Tinebase_Controller_Record_Interface $_controller the record controller
     * @return  array record data
     */

    /************************** protected functions **********************/

    /**
     * resolve containers and tags
     *
     * @param Tinebase_Record_RecordSet $_records
     * @param Tinebase_ModelConfiguration $modelConfiguration
     * @param array $_resolveProperties
     */
    public static function resolveContainersAndTags(Tinebase_Record_RecordSet $_records, $modelConfiguration = null, $_resolveProperties = array('container_id', 'tags'))
    {
        $firstRecord = $_records->getFirstRecord();

        if ($firstRecord) {
            if ($firstRecord->has('container_id') && in_array('container_id', $_resolveProperties)) {
                Tinebase_Container::getInstance()->getGrantsOfRecords(
                    $_records,
                    Tinebase_Core::getUser(),
                    $modelConfiguration ? $modelConfiguration->containerProperty : 'container_id'
                );
            }

            if ($firstRecord->has('tags') && in_array('tags', $_resolveProperties) && ! $firstRecord->tags instanceof Tinebase_Record_RecordSet) {
                Tinebase_Tags::getInstance()->getMultipleTagsOfRecords($_records);
            }

        }
    }

    /**
     * @param string $_uid
     * @param Tinebase_Controller_Record_Interface $_controller
     * @return array
     * @throws Tinebase_Exception_NotFound
     */
    protected function _get($_uid, Tinebase_Controller_Record_Interface $_controller)
    {
        $record = $_controller->get($_uid);
        if (! $record) {
            throw new Tinebase_Exception_NotFound('record not found');
        }
        return $this->_recordToJson($record);
    }

    /**
     * Returns all records
     *
     * @param   Tinebase_Controller_Record_Interface $_controller the record controller
     * @return  array record data
     *
     * @todo    add sort/dir params here?
     * @todo    add translation here? that is needed for example for getSalutations() in the addressbook
     */
    protected function _getAll(Tinebase_Controller_Record_Interface $_controller)
    {
        $records = $_controller->getAll();

        return array(
            'results'       => $records->toArray(),
            'totalcount'    => count($records)
        );
    }

    /**
     * Search for records matching given arguments
     *
     * @param string|array                        $_filter json encoded / array
     * @param string|array                        $_paging json encoded / array
     * @param Tinebase_Controller_SearchInterface $_controller the record controller
     * @param string                              $_filterModel the class name of the filter model to use
     * @param bool|array|Tinebase_Record_Expander $_getRelations
     * @param string                              $_totalCountMethod
     * @return array
     */
    protected function _search($_filter, $_paging, Tinebase_Controller_SearchInterface $_controller, $_filterModel, $_getRelations = FALSE, $_totalCountMethod = self::TOTALCOUNT_CONTROLLER)
    {
        $filter = $this->_decodeFilter($_filter, $_filterModel);
        $decodedPagination = $this->_prepareParameter($_paging);
        if (null !== $this->_paginationModel) {
            $decodedPagination['model'] = $this->_paginationModel;
        } else {
            $decodedPagination['model'] = $filter->getModelName();
        }
        $pagination = new Tinebase_Model_Pagination($decodedPagination);
        $records = $_controller->search($filter, $pagination, $_getRelations);

        $result = $this->_multipleRecordsToJson($records, $filter);
        $totalCountResult = $this->_getSearchTotalCount($filter, $pagination, $_controller, $_totalCountMethod, count($result));

        return array_merge($totalCountResult, [
            'results'       => array_values($result),
            'filter'        => $filter->toArray(true),
        ]);
    }

    /**
     * do search count request only when resultset is equal
     * to $pagination->limit or we are not on the first page
     *
     * @param $filter
     * @param $pagination
     * @param Tinebase_Controller_SearchInterface $controller the record controller
     * @param $totalCountMethod
     * @param integer $resultCount
     * @return array
     */
    protected function _getSearchTotalCount($filter, $pagination, $controller, $totalCountMethod, $resultCount)
    {
         if (   $totalCountMethod === self::TOTALCOUNT_CONTROLLER
            && $pagination->limit
            && ($pagination->start != 0 || $resultCount == $pagination->limit)
         ) {
             $totalCount = $controller->searchCount($filter);
         } else {
             $totalCount = $resultCount;
         }
         return [
             'totalcount' => $totalCount
         ];
    }

    /**
     * decodes the filter string
     *
     * @param string|array $_filter
     * @param string $_filterModel the class name of the filter model to use
     * @param boolean $_throwExceptionIfEmpty
     * @return Tinebase_Model_Filter_FilterGroup
     */
    protected function _decodeFilter($_filter, $_filterModel, $_throwExceptionIfEmpty = FALSE)
    {
        if ($_filter instanceof Tinebase_Model_Filter_FilterGroup) {
            return $_filter;
        }

        $filterModel = $this->_getPluginForFilterModel($_filterModel);
        $decodedFilter = is_array($_filter) || strlen($_filter) == 40 ? $_filter : $this->_prepareParameter($_filter);

        if (is_array($decodedFilter)) {
            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel($filterModel);
            $filter->setFromArrayInUsersTimezone($decodedFilter);
        } else if (!empty($decodedFilter) && strlen($decodedFilter) == 40) {
            $filter = Tinebase_PersistentFilter::getFilterById($decodedFilter);
        } else if ($_throwExceptionIfEmpty) {
            throw new Tinebase_Exception_InvalidArgument('Filter must not be empty!');
        } else {
            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel($filterModel);
        }

        return $filter;
    }

    /**
     * creates/updates a record
     *
     * @param   array $_recordData
     * @param   Tinebase_Controller_Record_Interface $_controller the record controller
     * @param   string $_modelName for example: 'Task' for Tasks_Model_Task or the full model name (like 'Tinebase_Model_Container')
     * @param   string $_identifier of the record (default: id)
     * @param   array $_additionalArguments
     * @return  array created/updated record
     */
    protected function _save($_recordData, Tinebase_Controller_Record_Interface $_controller, $_modelName, $_identifier = 'id', $_additionalArguments = array())
    {
        $modelClass = (preg_match('/_Model_/', $_modelName)) ? $_modelName : $this->_applicationName . "_Model_" . $_modelName;
        $record = $this->_jsonToRecord($_recordData, $modelClass);

        // if there are dependent records, set the timezone of them and add them to a recordSet
        $this->_dependentRecordsFromJson($record);

        // ATTENTION for identifier != 'id' I guess the sql backend doesnt know about that... has() will not work
        // will lead to duplicate key exception, so not sooo bad, should be easily found by unittesting or testing
        // or so, or customers
        $method = empty($record->$_identifier) || empty($_controller->has([$record->$_identifier], true)) ? 'create' :
            'update';
        $args = array_merge(array($record), $_additionalArguments);
        $savedRecord = call_user_func_array(array($_controller, $method), $args);

        return $this->_recordToJson($savedRecord);
    }

    /**
     * creates recordsets for depedent records or records instead of arrays for records on record fields
     * and sets timezone of these records to utc
     *
     * @param Tinebase_Record_Interface $record
     */
    protected function _dependentRecordsFromJson(&$record)
    {
        $config = $record::getConfiguration();
        if ($config) {
            
            $recordsFields = $config->recordsFields;
            
            if ($recordsFields) {
                foreach ($recordsFields as $property => $fieldDef) {

                    $rcn = $fieldDef['config']['recordClassName'];
                    if ($record->has($property) && $record->{$property} && is_array($record->{$property})) {
                        $recordSet = new Tinebase_Record_RecordSet($rcn);
                        foreach ($record->{$property} as $recordArray) {
                            if (is_array($recordArray)) {
                                $srecord = new $rcn(array(), true);
                                $srecord->setFromJsonInUsersTimezone($recordArray);
                                $recordSet->addRecord($srecord);
                            } else {
                                if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                                    . ' Record array expected, got: ' . $recordArray);
                                throw new Tinebase_Exception_InvalidArgument('Record array expected');
                            }
                            $record->{$property} = $recordSet;
                        }
                    }
                }
            }
        }
    }
    
    /**
     * update multiple records
     *
     * @param string $_filter json encoded filter
     * @param string $_data json encoded key/value pairs
     * @param Tinebase_Controller_Record_Interface $_controller
     * @param string $_filterModel FilterGroup name
     * @return array with number of updated records
     */
    protected function _updateMultiple($_filter, $_data, Tinebase_Controller_Record_Interface $_controller, $_filterModel)
    {
        $this->_longRunningRequest();
        $decodedData   = $this->_prepareParameter($_data);
        $filter = $this->_decodeFilter($_filter, $_filterModel, TRUE);
        
        $result = $_controller->updateMultiple($filter, $decodedData);
        
        $result['results']     = $this->_multipleRecordsToJson($result['results']);
        $result['exceptions']  = $this->_multipleRecordsToJson($result['exceptions']);
        
        return $result;
    }
    
    /**
     * prepare long running request
     * - execution time
     * - session write close
     *
     * @param integer $executionTime
     * @return integer old max execution time
     */
    protected function _longRunningRequest($executionTime = 0)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Close session to allow other requests and set max execution time to ' . $executionTime);

        $oldMaxExcecutionTime = Tinebase_Core::setExecutionLifeTime($executionTime);
        Tinebase_Session::writeClose(true);
        
        return $oldMaxExcecutionTime;
    }
    
    /**
     * update properties of record by id
     *
     * @param string $_id record id
     * @param array  $_data key/value pairs with fields to update
     * @param Tinebase_Controller_Record_Interface $_controller
     * @return Tinebase_Record_Interface record
     */
    protected function _updateProperties($_id, $_data, Tinebase_Controller_Record_Interface $_controller)
    {
        $record = $_controller->get($_id);

        // merge with new properties
        foreach ($_data as $field => $value) {
            $record->$field = $value;
        }

        $savedRecord = $_controller->update($record);

        return $this->_recordToJson($savedRecord);
    }
    
    /**
     * deletes existing records
     *
     * @param array|string $_ids
     * @param Tinebase_Controller_Record_Interface $_controller the record controller
     * @param array $_additionalArguments
     * @return array
     */
    protected function _delete($_ids, Tinebase_Controller_Record_Interface $_controller, $additionalArguments = array())
    {
        if (! is_array($_ids) && strpos($_ids, '[') !== false) {
            $_ids = $this->_prepareParameter($_ids);
        }
        $args = array_merge(array($_ids), $additionalArguments);
        call_user_func_array(array($_controller, 'delete'), $args);

        return array(
            'status'    => 'success'
        );
    }
    
    /**
     * import records
     *
     * @param string $_tempFileId to import
     * @param string $_importDefinitionId
     * @param array $_options additional import options
     * @param array $_clientRecordData
     * @return array
     * @throws Tinebase_Exception_NotFound
     */
    protected function _import($_tempFileId, $_importDefinitionId, $_options = array(), $_clientRecordData = array())
    {
        $definition = Tinebase_ImportExportDefinition::getInstance()->get($_importDefinitionId);
        $importer = call_user_func($definition->plugin . '::createFromDefinition', $definition, $_options);

        if (! is_object($importer)) {
            throw new Tinebase_Exception_NotFound('No importer found for ' . $definition->name);
        }

        // extend execution time to 30 minutes
        $this->_longRunningRequest(1800);

        $file = Tinebase_TempFile::getInstance()->getTempFile($_tempFileId);
        $importResult = $importer->importFile($file->path, $_clientRecordData);

        $importResult['results']    = $importResult['results']->toArray();
        $importResult['exceptions'] = $importResult['exceptions']->toArray();
        $importResult['status']     = 'success';

        return $importResult;
    }

    /**
     * deletes existing records by filter
     *
     * @param string $_filter json encoded filter
     * @param Tinebase_Controller_Record_Interface $_controller the record controller
     * @param string $_filterModel the class name of the filter model to use
     * @return array
     */
    protected function _deleteByFilter($_filter, Tinebase_Controller_Record_Interface $_controller, $_filterModel)
    {
        $filter = $this->_decodeFilter($_filter, $_filterModel, TRUE);
        
        // extend execution time to 30 minutes
        $this->_longRunningRequest(1800);

        $_controller->deleteByFilter($filter);
        return array(
            'status'    => 'success'
        );
    }

    /**
     * returns record prepared for json transport
     *
     * @param Tinebase_Record_Interface $_record
     * @return array record data
     */
    protected function _recordToJson($_record)
    {
        $converter = Tinebase_Convert_Factory::factory($_record);
        $result = $converter->fromTine20Model($_record);

        return $result;
    }

    /**
     * returns record from json data
     *
     * @param array $_recordData
     * @param string $modelName
     * @return Tinebase_Record_Interface
     */
    protected function _jsonToRecord($_recordData, $modelName)
    {
        $converter = Tinebase_Convert_Factory::factory($modelName);
        $result = $converter->toTine20Model($_recordData);

        return $result;
    }

    /**
     * returns multiple records prepared for json transport
     *
     * @param Tinebase_Record_RecordSet $_records Tinebase_Record_Interface
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @return array data
     */
    protected function _multipleRecordsToJson(Tinebase_Record_RecordSet $_records, $_filter = NULL, $_pagination = NULL)
    {
        $result = array();

        if ($_records->getFirstRecord()) {
            $converter = Tinebase_Convert_Factory::factory($_records->getFirstRecord());
            $result = $converter->fromTine20RecordSet($_records, $_filter, $_pagination);
        }

        return $result;
    }

    /**
     * get available templates by containerId
     *
     * @param string $nodeId
     * @return array
     */
    public function getTemplates($nodeId = NULL)
    {
        if (! $nodeId) {
            return array(
                'totalcount' => 0,
                'results'    => array(),
            );
        }

        $result = array();
        try {
            $node = Tinebase_FileSystem::getInstance()->get($nodeId);
            if (Tinebase_Core::getUser()->hasGrant($node, Tinebase_Model_Grants::GRANT_READ)) {
                $nodes = Tinebase_FileSystem::getInstance()->getTreeNodeChildren($nodeId);
                $result = $this->_multipleRecordsToJson($nodes);
            }
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                . ' Could not get template files: ' . $e);
        }
    
        return array(
            'totalcount' => count($result),
            'results'    => $result,
        );
    }

    /**
     * fetch import definition data
     *
     * @param Tinebase_Record_RecordSet $definitions
     * @param Tinebase_Model_Application $application
     * @return array
     */
    protected function _getImportDefinitionRegistryData(Tinebase_Record_RecordSet $definitions = null, Tinebase_Model_Application $application = null)
    {
        if (! $application) {
            $application = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);
        }
        if (! $definitions) {
            $definitions = $this->_getImportDefinitions($application);
        }
        $definitionConverter = new Tinebase_Convert_ImportExportDefinition_Json();
        $importDefinitions = $definitions->filter('application_id', $application->getId());
        if (count($importDefinitions) === 0) {
            return [];
        }
        $defaultDefinition = $this->_getDefaultImportDefinition($importDefinitions, $application);

        try {
            $defaultDefinitionArray = $definitionConverter->fromTine20Model($defaultDefinition);
        } catch (Exception $e) {
            Tinebase_Exception::log($e);
            $defaultDefinitionArray = array();
        }

        try {
            $definitionsArray = $definitionConverter->fromTine20RecordSet($importDefinitions);
        } catch (Exception $e) {
            Tinebase_Exception::log($e);
            $definitionsArray = array();
        }

        $definitionData = array(
            'defaultImportDefinition'   => $defaultDefinitionArray,
            'importDefinitions'         => array(
                'results'    => $definitionsArray,
                'totalcount' => count($definitionsArray),
            ),
        );

        return $definitionData;
    }

    /**
     * get application import definitions
     *
     * @param Tinebase_Model_Application $application
     * @return Tinebase_Record_RecordSet
     */
    protected function _getImportDefinitions(Tinebase_Model_Application $application = null)
    {
        $filterData = [[
            'field' => 'type',
            'operator' => 'equals',
            'value' => 'import'
        ], [
            'field' => 'scope',
            'operator' => 'not',
            'value' => Tinebase_Model_ImportExportDefinition::SCOPE_HIDDEN
        ]];
        if ($application) {
            $filterData[] = [
                'field' => 'application_id',
                'operator' => 'equals',
                'value' => $application->getId()
            ];
        }
        $filter = new Tinebase_Model_ImportExportDefinitionFilter($filterData);

        return Tinebase_ImportExportDefinition::getInstance()->search($filter);
    }

    /**
     * get default definition
     *
     * @param Tinebase_Record_RecordSet $_importDefinitions
     * @param Tinebase_Model_Application $application
     * @return Tinebase_Model_ImportExportDefinition
     */
    protected function _getDefaultImportDefinition($_importDefinitions, Tinebase_Model_Application $application = null)
    {
        if (! $application) {
            $application = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);
        }
        try {
            $defaultName = $this->_defaultImportDefinitionName
                ? $this->_defaultImportDefinitionName
                : strtolower($application->name . '_tine_import_csv');
            $defaultDefinition = Tinebase_ImportExportDefinition::getInstance()->getByName($defaultName);
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (count($_importDefinitions) > 0) {
                $defaultDefinition = $_importDefinitions->getFirstRecord();
            } else {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ .
                    ' No import definitions found for ' . $application->name);
                $defaultDefinition = NULL;
            }
        }

        return $defaultDefinition;
    }

    /**
     * get configured plugin for filter model
     * @param string $filterModel
     * @return string
     */
    protected function _getPluginForFilterModel($filterModel)
    {
        if (isset(self::$_filterPlugins[$filterModel])) {
            return self::$_filterPlugins[$filterModel];
        }
        return $filterModel;
    }

    /**
     * Add a plugin for a former filter
     * @param string $formerFilter
     * @param string $plugin
     */
    public static function addFilterModelPlugin($formerFilter, $plugin)
    {
        if (class_exists($plugin)) {
            self::$_filterPlugins[$formerFilter] = $plugin;
        } else {
            Tinebase_Core::getLogger()->err(__METHOD__."::".__LINE__.":: Filter model plugin \"$plugin\" doesn't exists");
        }
    }

    /**
     * magic method for json api
     *
     * @param string $method
     * @param array  $args
     * @return mixed
     */
    public function __call($method, array $args)
    {
        // provides api for default application methods
        if (preg_match('/^(get|save|search|delete|import)([a-z0-9]+)/i', $method, $matches)) {
            $apiMethod = $matches[1];
            $model = in_array($apiMethod, array('search', 'delete', 'import')) ? substr($matches[2],0,-1) : $matches[2];
            $modelController = Tinebase_Core::getApplicationInstance($this->_applicationName, $model);
            // resolve custom fields by default
            $modelController->resolveCustomfields(true);
            switch ($apiMethod) {
                case 'get':
                    return $this->_get($args[0], $modelController);
                    break;
                case 'save':
                    return $this->_save($args[0], $modelController, $model);
                    break;
                case 'search':
                    $filterName = $this->_applicationName . '_Model_' . $model . 'Filter';
                    return $this->_search($args[0], $args[1], $modelController, $filterName, /* $_getRelations */ true);
                    break;
                case 'delete':
                    return $this->_delete($args[0], $modelController);
                    break;
                case 'import':
                    // model controller is not needed - but we fetch it for checking if model exists and user has run right
                    // $tempFileId, $definitionId, $importOptions, $clientRecordData
                    return $this->_import($args[0], $args[1], $args[2], $args[3]);
                    break;
            }
        }
    }
}
