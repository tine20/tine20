<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Application
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id:Abstract.php 5090 2008-10-24 10:30:05Z p.schuele@metaways.de $
 */


/**
 * Abstract class for an Tine 2.0 application with Json interface
 * Each tine application must extend this class to gain an native tine 2.0 user
 * interface.
 * 
 * @package     Tinebase
 * @subpackage  Application
 */
abstract class Tinebase_Application_Frontend_Json_Abstract extends Tinebase_Application_Frontend_Abstract implements Tinebase_Application_Frontend_Json_Interface
{
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
     * search for filters (by application, user, ...)
     *
     * @param string $filter
     * @return array
     * 
     * @todo add recordsToJson / use toJson from filter group or persistent filter model
     */
    public function searchFilters($filter)
    {
        $decodedFilter = Zend_Json::decode($filter);
        $filter = new Tinebase_Model_PersistentFilterFilter(!empty($decodedFilter) ? $decodedFilter : array());
        
        $persistentFilterBackend = new Tinebase_PersistentFilter();
        $result = $persistentFilterBackend->search($filter)->toArray();
        
        foreach ($result as &$record) {
            $record['filters'] = unserialize($record['filters']);
        }
        
        return array(
            'results'       => $result,
            'totalcount'    => $persistentFilterBackend->searchCount($filter)
        );
    }

    /**
     * delete persistent filter
     *
     * @param string $_filterId
     */
    public function deleteFilter($_filterId) 
    {
        $persistentFilterBackend = new Tinebase_PersistentFilter();
        $persistentFilterBackend->delete($_filterId); 
    }
    
    /************************** protected functions **********************/    
    
    /**
     * Return a single record
     *
     * @param   string $_uid
     * @param   Tinebase_Application_Controller_Record_Interface $_controller the record controller
     * @return  array record data
     */
    protected function _get($_uid, Tinebase_Application_Controller_Record_Interface $_controller)
    {
        $record = $_controller->get($_uid);
        return $this->_recordToJson($record);
    }

    /**
     * Returns all records
     *
     * @param   Tinebase_Application_Controller_Record_Interface $_controller the record controller
     * @return  array record data
     * 
     * @todo    add sort/dir params here?
     * @todo    add translation here? that is needed for example for getSalutations() in the addressbook
     */
    protected function _getAll(Tinebase_Application_Controller_Record_Interface $_controller)
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
     * @param string $_filter json encoded
     * @param string $_paging json encoded
     * @param Tinebase_Application_Controller_Record_Interface $_controller the record controller
     * @param string $_filterModel the class name of the filter model to use
     * @return array
     */
    protected function _search($_filter, $_paging, Tinebase_Application_Controller_Record_Interface $_controller, $_filterModel)
    {
        $decodedFilter = Zend_Json::decode($_filter);
        $filter = new $_filterModel(!empty($decodedFilter) ? $decodedFilter : array());
        $pagination = new Tinebase_Model_Pagination(Zend_Json::decode($_paging));
        
        $records = $_controller->search($filter, $pagination);
        
        $result = $this->_multipleRecordsToJson($records);
        
        return array(
            'results'       => $result,
            'totalcount'    => $_controller->searchCount($filter)
        );
    }
    
    /**
     * creates/updates a record
     *
     * @param   $_recordData
     * @param   Tinebase_Application_Controller_Record_Interface $_controller the record controller
     * @param   $_modelName for example: 'Task' for Tasks_Model_Task
     * @param   $_identifier of the record (default: id)
     * @return  array created/updated record
     */
    protected function _save($_recordData, Tinebase_Application_Controller_Record_Interface $_controller, $_modelName, $_identifier = 'id')
    {
        $modelClass = $this->_applicationName . "_Model_" . $_modelName;
        $record = new $modelClass(array(), TRUE);
        $record->setFromJsonInUsersTimezone($_recordData);
        
        $savedRecord = (empty($record->$_identifier)) ? 
            $_controller->create($record): 
            $_controller->update($record);

        return $this->_recordToJson($savedRecord);
    }

    /**
     * update multiple records
     *
     * @param string $_filter json encoded filter
     * @param string $_data json encoded key/value pairs 
     * @param Tinebase_Application_Controller_Record_Interface $_controller
     * @param string FilterGroup name
     * @return array with number of updated records
     */
    protected function _updateMultiple($_filter, $_data, Tinebase_Application_Controller_Record_Interface $_controller, $_filterModel)
    {
        $filter = new $_filterModel(Zend_Json::decode($_filter));
        
        $result = $_controller->updateMultiple($filter, Zend_Json::decode($_data));
        
        return array(
            'count'       => $result,
        );
    }
    
    /**
     * deletes existing records
     *
     * @param array $_ids 
     * @param Tinebase_Application_Controller_Record_Interface $_controller the record controller
     * @return string
     */
    protected function _delete($_ids, Tinebase_Application_Controller_Record_Interface $_controller)
    {
        if (strpos($_ids, '[') !== false) {
            $_ids = Zend_Json::decode($_ids);
        }
        $_controller->delete($_ids);
        return 'success';
    }
    
    /**
     * returns task prepared for json transport
     *
     * @param Tinebase_Record_Interface $_record
     * @return array record data
     * 
     * @todo move that to Tinebase_Record_Abstract
     */
    protected function _recordToJson($_record)
    {
        $_record->setTimezone(Tinebase_Core::get('userTimeZone'));
        $_record->bypassFilters = true;
        $recordArray = $_record->toArray();

        if ($_record->has('container_id')) {
            $recordArray['container_id'] = Tinebase_Container::getInstance()->getContainerById($_record->container_id)->toArray();
            $recordArray['container_id']['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(Tinebase_Core::getUser(), $_record->container_id)->toArray();
        }
        return $recordArray;
    }

    /**
     * returns multiple records prepared for json transport
     *
     * @param Tinebase_Record_RecordSet $_leads Crm_Model_Lead
     * @return array data
     * 
     * @todo move that to Tinebase_Record_RecordSet
     */
    protected function _multipleRecordsToJson(Tinebase_Record_RecordSet $_records)
    {       
        if (count($_records) == 0) {
            return array();
        }
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_records, true));
        
        // get acls for records
        if ($_records[0]->has('container_id')) {
            Tinebase_Container::getInstance()->getGrantsOfRecords($_records, Tinebase_Core::getUser());
        }
        
        $_records->setTimezone(Tinebase_Core::get('userTimeZone'));
        $_records->convertDates = true;
        
        $result = $_records->toArray();
        
        return $result;
    }

    /**
     * save persistent filter
     *
     * @param string $_filter
     * @param string $_name
     * @param string $_filterModel
     */
    protected function _saveFilter($_filter, $_name, $_filterModel) 
    {
        $decodedFilter = Zend_Json::decode($_filter);
        $filter = new $_filterModel(!empty($decodedFilter) ? $decodedFilter : array());
        $filter = $filter->save($_name);
        
        // @todo return something here?
        //return $filter->toJson();
    }
}
