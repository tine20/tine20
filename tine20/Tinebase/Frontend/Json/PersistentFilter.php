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
     * @param string $filter
     * @return array
     */
    public function search($filter)
    {
        $decodedFilter = Zend_Json::decode($filter);
        $filter = new Tinebase_Model_PersistentFilterFilter(!empty($decodedFilter) ? $decodedFilter : array());
        
        $result = $this->_backend->search($filter)->toArray();
        
        foreach ($result as &$record) {
            $record['filters'] = unserialize($record['filters']);
        }
        
        return array(
            'results'       => $result,
            'totalcount'    => $this->_backend->searchCount($filter)
        );
    }

    /**
     * loads a persistent filter
     *
     * @param string $_filterId
     * @return Tinebase_Model_Filter_FilterGroup
     */
    public function get($_filterId)
    {
        $persistentFilter = $this->_backend->get($_filterId);
        
        $filter = new $persistentFilter->model(unserialize($persistentFilter->filters));
        
        //$result = $filter->toArray(TRUE);
        
        return $filter;
    }
    
    /**
     * save persistent filter
     *
     * @param string $_filter
     * @param string $_name
     * @param string $_filterModel
     * 
     * @todo check if filter model is filter group
     */
    public function save($_filter, $_name, $_filterModel, $_applicationId) 
    {
        $filter = new $_filterModel(array());
        $filter->setFromJsonInUsersTimezone($_filter);

        $persistentFilter = new Tinebase_Model_PersistentFilter(array(
            'account_id'        => Tinebase_Core::getUser()->getId(),
            'application_id'    => $_applicationId,
            'model'             => get_class($filter),
            'filters'           => serialize($filter->toArray()),
            'name'              => $_name
        ));
        
        // check if exists
        $searchFilter = new Tinebase_Model_PersistentFilterFilter(array(
            array('field' => 'name',            'operator' => 'equals', 'value' => $_name),
            array('field' => 'application_id',  'operator' => 'equals', 'value' => $_applicationId),
        ));
        $existing = $this->_backend->search($searchFilter);
        
        if (count($existing) > 0) {
            $persistentFilter->setId($existing[0]->getId());
            
            $modLog = Tinebase_Timemachine_ModificationLog::getInstance();
            $modLog->manageConcurrentUpdates($persistentFilter, $existing[0], 'Tinebase_Model_PersistentFilter', 'Sql', $persistentFilter->getId());
            $modLog->setRecordMetaData($persistentFilter, 'update', $existing[0]);
            $currentMods = $modLog->writeModLog($persistentFilter, $existing[0],'Tinebase_Model_PersistentFilter', 'Sql', $persistentFilter->getId());
            
            $persistentFilter = $this->_backend->update($persistentFilter);
            
        } else {
            Tinebase_Timemachine_ModificationLog::setRecordMetaData($persistentFilter, 'create');
            $persistentFilter = $this->_backend->create($persistentFilter);
        }
        
        // return something here?
        //return $filter->toJson();
    }
    
    /**
     * delete persistent filter
     *
     * @param string $_filterId
     */
    public function delete($_filterId) 
    {
        $persistentFilter = $this->_backend->get($_filterId);
        if (Tinebase_Core::getUser()->getId() != $persistentFilter->account_id) {
            throw new Tinebase_Exception_AccessDenied('You are not allowed to delete this filter.');
        }
        
        $this->_backend->delete($_filterId); 
    }
}
