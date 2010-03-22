<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

/**
 * Json Persistent Filter class
 * 
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Frontend_Json_PersistentFilter
{
    /**
     * persistent filter backend
     *
     * @var Tinebase_PersistentFilter
     */
    protected $_backend;
    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_backend = new Tinebase_PersistentFilter();
    }
    
    /**
     * search for persistent filters (by application, user, ...)
     *
     * @param  array $filter
     * @return array
     */
    public function search($filter)
    {
        $decodedFilter = is_array($filter) ? $filter : Zend_Json::decode($filter);
        $filter = new Tinebase_Model_PersistentFilterFilter(!empty($decodedFilter) ? $decodedFilter : array());
        
        $persistentFilters = $this->_backend->search($filter, new Tinebase_Model_Pagination(array(
            'dir'       => 'ASC',
            'sort'      => array('name', 'creation_time')
        )));
        
        $persistentFiltersData = array();
        foreach ($persistentFilters as $persistentFilter) {
            $persistentFilterData = $persistentFilter->toArray(FALSE);
            $persistentFilterData['filters'] = $persistentFilterData['filters']->toArray(TRUE);
            $persistentFiltersData[] = $persistentFilterData;
        }
        
        return array(
            'results'       => $persistentFiltersData,
            'totalcount'    => $this->_backend->searchCount($filter)
        );
    }

    /**
     * loads a persistent filter
     *
     * @param  string $filterId
     * @return array persistent filter
     */
    public function get($filterId)
    {
        $persistentFilter = $this->_backend->get($filterId);
        
        $persistentFilterData = $persistentFilter->toArray(FALSE);
        $persistentFilterData['filters'] = $persistentFilterData['filters']->toArray(TRUE);
        
        return $persistentFilterData;
    }
    
    /**
     * rename a saved filter
     * 
     * @param $filterId
     * @param $newName
     * @return array persistent filter
     */
    public function rename($filterId, $newName)
    {
        $persistentFilter = $this->_backend->get($filterId);
        $persistentFilter->name = $newName;
        $this->_backend->update($persistentFilter);
        
        return $this->get($filterId);
    }
    
    /**
     * save persistent filter
     *
     * @param  array  $filterData
     * @param  string $name
     * @param  string $model model name (Application_Model_Record)
     * @return array persistent filter
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function save($filterData, $name, $model) 
    {
        $filterData = is_array($filterData) ? $filterData : Zend_Json::decode($filterData);
        
        
        list($appName, $ns, $modelName) = explode('_', $model);
        $filterModel = "{$appName}_Model_{$modelName}Filter";
        
        $persistentFilter = new Tinebase_Model_PersistentFilter();
        $persistentFilter->setFromJsonInUsersTimezone(array(
            'account_id'        => Tinebase_Core::getUser()->getId(),
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName($appName)->getId(),
            'model'             => $filterModel,
            'filters'           => Tinebase_Model_PersistentFilter::getFilterGroup($filterModel, $filterData, TRUE),
            'name'              => $name
        ));
        
        // check if exists
        $searchFilter = new Tinebase_Model_PersistentFilterFilter(array(
            array('field' => 'name',            'operator' => 'equals', 'value' => $name),
            array('field' => 'application_id',  'operator' => 'equals', 'value' => $persistentFilter->application_id),
        ));
        $existing = $this->_backend->search($searchFilter);
        
        if (count($existing) > 0) {
            $persistentFilter->setId($existing[0]->getId());

            Tinebase_Timemachine_ModificationLog::setRecordMetaData($persistentFilter, 'update', $existing[0]);            
            $persistentFilter = $this->_backend->update($persistentFilter);
            
        } else {
            Tinebase_Timemachine_ModificationLog::setRecordMetaData($persistentFilter, 'create');
            $persistentFilter = $this->_backend->create($persistentFilter);
        }
        
        return $this->get($persistentFilter->getId());
    }
    
    /**
     * delete persistent filter
     *
     * @param string $filterId
     */
    public function delete($filterId) 
    {
        $persistentFilter = $this->_backend->get($filterId);
        if (Tinebase_Core::getUser()->getId() != $persistentFilter->account_id) {
            throw new Tinebase_Exception_AccessDenied('You are not allowed to delete this filter.');
        }
        
        $this->_backend->delete($filterId);

        return array(
            'status'    => 'success'
        );
    }
}
