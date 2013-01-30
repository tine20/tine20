<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Application
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * All models of this application (needed for application starter)
     * @var array
     */
    protected $_models = NULL;
    
    /**
     * default model (needed for application starter -> defaultContentType)
     * @var string
     */
    protected $_defaultModel = NULL;
    
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
        $ret = array();
        
        if (property_exists($this, '_relatableModels') && is_array($this->_relatableModels)) {
            $i = 0;
            foreach ($this->_relatableModels as $model) {
                if (! class_exists($model)) {
                    continue;
                }
                $cItems = $model::getRelatableConfig();
                $ownModel = explode('_Model_', $model);
                $ownModel = $ownModel[1];
                foreach($cItems as $cItem) {
                    $cItem['ownModel'] = $ownModel;
                    $ret[$this->_applicationName][$i] = $cItem;
                    $ret[$cItem['relatedApp']][$i] = array('reverted' => true, 'ownModel' => $cItem['relatedModel'], 'relatedModel' => $cItem['ownModel'], 'relatedApp' => $this->_applicationName);
                    // KeyfieldConfigs
                    if(array_key_exists('keyfieldConfig', $cItem)) {
                        $ret[$cItem['relatedApp']][$i]['keyfieldConfig'] = $cItem['keyfieldConfig'];
                        if($cItem['keyfieldConfig']['from']) $ret[$cItem['relatedApp']][$i]['keyfieldConfig']['from'] = $cItem['keyfieldConfig']['from'] == 'foreign' ? 'own' : 'foreign';
                    }
                    $j=0;
                    if (array_key_exists('config', $cItem)) {
                        foreach ($cItem['config'] as $conf) {
                            $max = explode(':',$conf['max']);
                            $ret[$this->_applicationName][$i]['config'][$j]['max'] = $max[0];
                            $ret[$cItem['relatedApp']][$i]['config'][$j] = $conf;
                            $ret[$cItem['relatedApp']][$i]['config'][$j]['max'] = $max[1];
                            if($conf['degree'] == 'sibling') {
                                $ret[$cItem['relatedApp']][$i]['config'][$j]['degree'] = $conf['degree'];
                            } else {
                                $ret[$cItem['relatedApp']][$i]['config'][$j]['degree'] = $conf['degree'] == 'parent' ? 'child' : 'parent';
                            }
                            $j++;
                        }
                    }
                    $i++;
                }
            }
        }
        return $ret;
    }
    
    /**
     * returns models for this application
     */
    public function getModels()
    {
        return $this->_models;
    }
    
    /**
     * returns model configurations for application starter
     * @return array
     */
    public function getModelsConfiguration()
    {
        if($this->_models) {
            foreach($this->_models as $model) {
                $mn = $this->_applicationName . '_Model_' . $model;
                $ret[$model] = $mn::getConfiguration();
            }
            return $ret;
        }
        return NULL;
    }
    
    /**
     * returns the default model
     * @return NULL
     */
    public function getDefaultModel()
    {
        if($this->_defaultModel) {
            return $this->_defaultModel;
        }
        if($this->_models) {
            return $this->_models[0];
        }
        return NULL;
    }
    
    /**
     * resolve containers and tags
     *
     * @param Tinebase_Record_RecordSet $_records
     * @param array $_resolveProperties
     */
    public static function resolveContainerTagsUsers(Tinebase_Record_RecordSet $_records, $_resolveProperties = array('container_id', 'tags'))
    {
        $firstRecord = $_records->getFirstRecord();
        
        if ($firstRecord) {
            if ($firstRecord->has('container_id') && in_array('container_id', $_resolveProperties)) {
                Tinebase_Container::getInstance()->getGrantsOfRecords($_records, Tinebase_Core::getUser());
            }
        
            if ($firstRecord->has('tags') && in_array('tags', $_resolveProperties)) {
                Tinebase_Tags::getInstance()->getMultipleTagsOfRecords($_records);
            }
        }
    }
    
    /************************** protected functions **********************/

    /**
     * Return a single record
     *
     * @param   string $_uid
     * @param   Tinebase_Controller_Record_Interface $_controller the record controller
     * @return  array record data
     */
    protected function _get($_uid, Tinebase_Controller_Record_Interface $_controller)
    {
        $record = $_controller->get($_uid);
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
     * @param bool                                $_getRelations
     * @param string                              $_totalCountMethod
     * @return array
     */
    protected function _search($_filter, $_paging, Tinebase_Controller_SearchInterface $_controller, $_filterModel, $_getRelations = FALSE, $_totalCountMethod = self::TOTALCOUNT_CONTROLLER)
    {
        $decodedPagination = is_array($_paging) ? $_paging : Zend_Json::decode($_paging);
        $pagination = new Tinebase_Model_Pagination($decodedPagination);
        $filter = $this->_decodeFilter($_filter, $_filterModel);

        $records = $_controller->search($filter, $pagination, $_getRelations);

        $result = $this->_multipleRecordsToJson($records, $filter);

        return array(
            'results'       => array_values($result),
            'totalcount'    => $_totalCountMethod == self::TOTALCOUNT_CONTROLLER ?
                $_controller->searchCount($filter) :
                count($result),
            'filter'        => $filter->toArray(TRUE),
        );
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
        $decodedFilter = is_array($_filter) || strlen($_filter) == 40 ? $_filter : Zend_Json::decode($_filter);

        if (is_array($decodedFilter)) {
            $filter = new $_filterModel(array());
            $filter->setFromArrayInUsersTimezone($decodedFilter);
        } else if (!empty($decodedFilter) && strlen($decodedFilter) == 40) {
            $filter = Tinebase_PersistentFilter::getFilterById($decodedFilter);
        } else if ($_throwExceptionIfEmpty) {
            throw new Tinebase_Exception_InvalidArgument('Filter must not be empty!');
        } else {
            $filter = new $_filterModel(array());
        }

        return $filter;
    }

    /**
     * creates/updates a record
     *
     * @param   $_recordData
     * @param   Tinebase_Controller_Record_Interface $_controller the record controller
     * @param   $_modelName for example: 'Task' for Tasks_Model_Task or the full model name (like 'Tinebase_Model_Container')
     * @param   $_identifier of the record (default: id)
     * @param   array $_additionalArguments
     * @return  array created/updated record
     */
    protected function _save($_recordData, Tinebase_Controller_Record_Interface $_controller, $_modelName, $_identifier = 'id', $_additionalArguments = array())
    {
        $modelClass = (preg_match('/_Model_/', $_modelName)) ? $_modelName : $this->_applicationName . "_Model_" . $_modelName;
        $record = new $modelClass(array(), TRUE);
        $record->setFromJsonInUsersTimezone($_recordData);

        $method = (empty($record->$_identifier)) ? 'create' : 'update';
        $args = array_merge(array($record), $_additionalArguments);
        $savedRecord = call_user_func_array(array($_controller, $method), $args);

        return $this->_recordToJson($savedRecord);
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
        $decodedData   = is_array($_data) ? $_data : Zend_Json::decode($_data);
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
        $oldMaxExcecutionTime = Tinebase_Core::setExecutionLifeTime(0);
        // close session to allow other requests
        Zend_Session::writeClose(true);
        
        return $oldMaxExcecutionTime;
    }
    
    /**
     * update properties of record by id
     *
     * @param string $_id record id
     * @param array  $_data key/value pairs with fields to update
     * @param Tinebase_Controller_Record_Interface $_controller
     * @return updated record
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
            $_ids = Zend_Json::decode($_ids);
        }
        $args = array_merge(array($_ids), $additionalArguments);
        $savedRecord = call_user_func_array(array($_controller, 'delete'), $args);

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
     * returns multiple records prepared for json transport
     *
     * @param Tinebase_Record_RecordSet $_records Tinebase_Record_Abstract
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
}
