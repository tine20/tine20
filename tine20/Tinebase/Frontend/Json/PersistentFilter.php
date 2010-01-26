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
        
        $result = $this->_backend->search(
            $filter,
            new Tinebase_Model_Pagination(array(
                'dir'       => 'ASC',
                'sort'      => array('name', 'creation_time')
            ))
        )->toArray();
        
        foreach ($result as &$record) {
            $record['filters'] = Zend_Json::decode($record['filters']);
        }
        
        return array(
            'results'       => $result,
            'totalcount'    => $this->_backend->searchCount($filter)
        );
    }

    /**
     * loads a persistent filter
     *
     * @param  string $filterId
     * @return Tinebase_Model_Filter_FilterGroup
     */
    public function get($filterId)
    {
        $persistentFilter = $this->_backend->get($filterId);
        
        $filter = new $persistentFilter->model(Zend_Json::decode($persistentFilter->filters));
        
        //$result = $filter->toArray(TRUE);
        
        return $filter;
    }
    
    /**
     * save persistent filter
     *
     * @param  array  $filterData
     * @param  string $name
     * @param  string $model model name (Application_Model_Record)
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function save($filterData, $name, $model) 
    {
        $decodedFilterData = is_array($filterData) ? $filterData : Zend_Json::decode($filterData);
        
        list($appName, $ns, $modelName) = explode('_', $model);
        
        $filterModel = "{$appName}_Model_{$modelName}Filter";
        $filter = new $filterModel(array());
        
        if (!is_subclass_of($filter, 'Tinebase_Model_Filter_FilterGroup')) {
            throw new Tinebase_Exception_InvalidArgument('Filter Model has to be subclass of Tinebase_Model_Filter_FilterGroup.');
        }
        
        // set filter data und create persistent filter record
        $filter->setFromArrayInUsersTimezone($decodedFilterData);
        $applicationId = Tinebase_Application::getInstance()->getApplicationByName($appName)->getId();
        $persistentFilter = new Tinebase_Model_PersistentFilter(array(
            'account_id'        => Tinebase_Core::getUser()->getId(),
            'application_id'    => $applicationId,
            'model'             => get_class($filter),
            'filters'           => Zend_Json::encode($filter->toArray()),
            'name'              => $name
        ));
        
        // check if exists
        $searchFilter = new Tinebase_Model_PersistentFilterFilter(array(
            array('field' => 'name',            'operator' => 'equals', 'value' => $name),
            array('field' => 'application_id',  'operator' => 'equals', 'value' => $applicationId),
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
        
        return $persistentFilter->toArray();
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
