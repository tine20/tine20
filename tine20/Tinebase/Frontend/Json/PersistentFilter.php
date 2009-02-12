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
    protected $_backend;
    
    public function __construct()
    {
        $this->_backend = new Tinebase_PersistentFilter();
    }
    
    /**
     * search for persistent filters (by application, user, ...)
     *
     * @param string $filter
     * @return array
     * 
     * @todo add recordsToJson / use toJson from filter group or persistent filter model
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
        
        $result = $filter->toJson();
        
        return $filter;
    }
    
    /**
     * save persistent filter
     *
     * @param string $_filter
     * @param string $_name
     * @param string $_filterModel
     * 
     * @todo use set/createFromJson
     * @todo check if filter model is filter group
     * @todo add update functionality
     */
    public function save($_filter, $_name, $_filterModel, $_applicationId) 
    {
        $decodedFilter = Zend_Json::decode($_filter);
        $filter = new $_filterModel(!empty($decodedFilter) ? $decodedFilter : array());

        $persistentFilter = new Tinebase_Model_PersistentFilter(array(
            'account_id'        => Tinebase_Core::getUser()->getId(),
            'application_id'    => $_applicationId,
            'model'             => get_class($filter),
            'filters'           => serialize($filter->toArray()),
            'name'              => $_name
        ));
        
        $persistentFilter = $this->_backend->create($persistentFilter);
        
        // @todo return something here?
        //return $filter->toJson();
    }
    
    /**
     * delete persistent filter
     *
     * @param string $_filterId
     */
    public function delete($_filterId) 
    {
        $this->_backend->delete($_filterId); 
    }
}
