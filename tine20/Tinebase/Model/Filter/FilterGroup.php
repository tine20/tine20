<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 * 
 * @todo        finish implementation of to/from json functions
 */

/**
 * Tinebase_Model_Filter_FilterGroup
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * 
 * A filter group represents a number of individual filters and a condition between
 * all of them. Each filter group requires a filter model where the allowed filters
 * and options for them are specified on the one hand, and concrete filter data for
 * this concrete filter on the other hand.
 * 
 * NOTE: To define a filter model only once, it might be usefull to extend this 
 *       class and only overwrite $this->_filterModel
 * NOTE: The first filtergroup is _allways_ a AND condition filtergroup!
 *       This is due to the fact that the first filtergroup operates on the 
 *       'real' select object (@see $this->appendSql)
 * NOTE: The ACL relevant filters _must_ be checked and set by the controllers!
 * 
 * @example 
 * class myFilterGroup {
 *     protected $_className = 'myFilterGroup';
 *     protected $_applicationName = 'myapp';
 *     protected $_filterModel = array (
 *         'name'       => array('filter' => 'Tinebase_Model_Filter_Text'),
 *         'container'  => array('filter' => 'Tinebase_Model_Filter_Container', 'options' => array('applicationName' => 'myApp')),
 *         'created_by' => array('filter' => 'Tinebase_Model_Filter_User'),
 *         'some_id'    => array('filter' => 'Tinebase_Model_Filter_ForeignId', 'options' => array('filtergroup' => 'Someapp_Model_SomeFilter', 'controller' => 'Myapp_Controller_Some')),
 *         'custom'     => array('custom' => true),  // will be ignored and you must handle this filter your own!
 *     );
 * }
 * 
 * $filterData = array(
 *     array('field' => 'name','operator' => 'beginswith', 'value' => 'Hugo'),
 *     array('condition' => 'OR' => 'filters' => array(
 *         'field' => 'created_by'  => 'operator' => 'equals', 'value' => 2,
 *         'field' => 'modified_by' => 'operator' => 'equals', 'value' => 2
 *     ),
 *     array('field' => 'container_id', 'operator' => 'in', 'value' => array(2,4,6,7)
 *     array('field' => 'foreign_id',  'operator' => 'AND', value => array(
 *         array('field' => 'foreignfieldname',  'operator' => 'contains', 'value' => 'test'),
 *     )
 * );
 * 
 * $filterGroup = new myFilterGroup($filterData);
 * 
 */
class Tinebase_Model_Filter_FilterGroup
{
    /*************** config options for inheriting filter groups ***************/
    
    /**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = '';
    
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = NULL;
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array();
    
    /**
     * @var string fieldName of acl filter
     */
    protected $_aclFilterField = NULL;
    
    
    /******************************* properties ********************************/
    
    /**
     * @var array holds filter objects of this filter
     */
    protected $_filterObjects = array();
    
    /**
     * @var array spechial options
     */
    protected $_options = NULL;
    
    /**
     * @var Tinebase_Model_Filter_AclFilter
     */
    protected $_aclFilter = NULL;
    
    /**
     * @var array holds data of all custom filters
     */
    protected $_customData = array();
    
    /**
     * @var string holds condition between this filters
     */
    protected $_concatenationCondition = NULL;
    
    
    /******************************** functions ********************************/
    
    /**
     * constructs a new filter group
     *
     * @param  array $_data
     * @param  string $_condition {AND|OR}
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function __construct(array $_data, $_condition='AND', $_options = array())
    {
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_data, true));
        $this->_setOptions($_options);
        
        $this->_concatenationCondition = $_condition == 'OR' ? 'OR' : 'AND';
        
        // legacy container handling
        Tinebase_Model_Filter_Container::_transformLegacyData($_data);
        
        foreach ($_data as $filterData) {
            
            // if a condition is given, we create a new filtergroup from this class
            if (isset($filterData['condition'])) {
                $this->addFilterGroup(new $this->_className($filterData['filters'], $filterData['condition']));
            
            } else {
                $fieldModel = (isset($this->_filterModel[$filterData['field']])) ? $this->_filterModel[$filterData['field']] : '';
                
                if (empty($fieldModel)) {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' skipping filter (no filter model defined) ' . print_r($filterData, true));
                
                } elseif (array_key_exists('filter', $fieldModel)) {
                    // create a 'single' filter
                    $this->addFilter($this->createFilter($filterData['field'], $filterData['operator'], $filterData['value']));
                
                } elseif (array_key_exists('custom', $fieldModel) && $fieldModel['custom'] == true) {
                    // silently skip data, as they will be evaluated by the concreate filtergroup
                    $this->_customData[] = $filterData;
                
                } else {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' skipping filter (filter syntax problem)' . print_r($filterData, true));
                }
            }
        }
        
    }
    
    /**
     * set options 
     *
     * @param array $_options
     */
    protected function _setOptions(array $_options)
    {
        $this->_options = $_options;
    }
    
    /**
     * Add a filter to this group
     *
     * @param  Tinebase_Model_Filter_Abstract $_filter
     * @return Tinebase_Model_Filter_FilterGroup this
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function addFilter($_filter)
    {
        if ($_filter->getField() == $this->_aclFilterField || $_filter instanceof Tinebase_Model_Filter_AclFilter) {
            if (! $this->_aclFilter) {
                $this->_aclFilter = $_filter;
            } else {
                throw new Tinebase_Exception('only one acl filter could be set!');
            }
        }
        
        if (! $_filter instanceof Tinebase_Model_Filter_Abstract) {
            throw new Tinebase_Exception_InvalidArgument('Filters must be of instance Tinebase_Model_Filter_Abstract');
        }
        
        $this->_filterObjects[] = $_filter;
        return $this;
    }
    
    /**
     * Add a filter group to this group
     *
     * @param  Tinebase_Model_Filter_FilterGroup $_filtergroup
     * @return Tinebase_Model_Filter_FilterGroup this
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function addFilterGroup($_filtergroup)
    {
        if (! $_filtergroup instanceof Tinebase_Model_Filter_FilterGroup) {
            throw new Tinebase_Exception_InvalidArgument('Filters must be of instance Tinebase_Model_Filter_FilterGroup');
        }
        
        $this->_filterObjects[] = $_filtergroup;
        return $this;
    }
    
    /**
     * creates a new filter based on the definition of this filtergroup
     *
     * @param  string $_field
     * @param  string $_operator
     * @param  mixed  $_value
     * @return Tinebase_Model_Filter_Abstract
     */
    public function createFilter($_field, $_operator, $_value)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " creating filter: $_field $_operator " . print_r($_value, true));
        
        if (empty($this->_filterModel[$_field])) {
            throw new Tinebase_Exception_NotFound('no such field in this filter model');
        }
        
        $definition = $this->_filterModel[$_field];
            
        if (isset($definition['custom']) && $definition['custom']) {
            $this->_customData[] = array(
                'field'     => $_field,
                'operator'  => $_operator,
                'value'     => $_value
            );
            $filter = NULL;
        } else {
            $filter = new $definition['filter']($_field, $_operator, $_value, (isset($definition['options']) ? (array)$definition['options'] : array()));
        }
            
        return $filter;
    }
    
    /**
     * returns acl filter of this group or NULL if not set
     *
     * @return Tinebase_Model_Filter_AclFilter
     */
    public function getAclFilter()
    {
        return $this->_aclFilter;
    }
    
    /**
     * returns concetationOperator / condition of this filtergroup
     *
     * @return string {AND|OR}
     */
    public function getCondition()
    {
        return $this->_concatenationCondition;
    }
    
    /**
     * returns model of this filtergroup
     *
     * @return array
     */
    public function getFilterModel()
    {
        return $this->_filterModel;
    }
    
    /**
     * appends tenor of this filterdata to given sql select object
     * 
     * NOTE: In order to archive nested filters we use the extended 
     *       Tinebase_Model_Filter_FilterGroup select object. This object
     *       appends all contained filters at once concated by the concetation
     *       operator of the filtergroup
     *
     * @param  Zend_Db_Select $_select
     */
    public function appendFilterSql($_select)
    {
        foreach ($this->_filterObjects as $filter) {
            if ($filter instanceof Tinebase_Model_Filter_FilterGroup) {
                //$groupSelect = new Tinebase_Model_Filter_DbGroupSelect($_select, $this->_concatenationCondition);
                //$filter->appendFilterSql($groupSelect);
                //$groupSelect->assemble();
            } else {
                $filter->appendFilterSql($_select);
            }
        }
    }

    /**
     * returns array with the filter settings of this filter group 
     *
     * @return array
     */
    public function toArray()
    {
        $result = array();
        foreach ($this->_filterObjects as $filter) {
            $result[] = $filter->toArray();
            /*
            if ($filter instanceof Tinebase_Model_Filter_FilterGroup) {                
                $result[''] = $filter->toArray();
            } else {
                $result[''] = '';
            }
            */
        }
        
        // add custom fields
        foreach ($this->_customData as $custom) {
            $result[] = $custom;
        }
        
        return $result;
    }

    /**
     * wrapper for setFromJson which expects datetimes in array to be in
     * users timezone and converts them to UTC
     *
     * @param  string $_data json encoded data
     * 
     * @todo implement
     */
    public function setFromJsonInUsersTimezone($_data)
    {
        /*
        // change timezone of current php process to usertimezone to let new dates be in the users timezone
        // NOTE: this is neccessary as creating the dates in UTC and just adding/substracting the timeshift would
        //       lead to incorrect results on DST transistions 
        date_default_timezone_set(Tinebase_Core::get('userTimeZone'));

        // NOTE: setFromArray creates new Zend_Dates of $this->datetimeFields
        $this->setFromJson($_data);
        
        // convert $this->_datetimeFields into the configured server's timezone (UTC)
        //$this->setTimezone('UTC');
        
        // finally reset timzone of current php process to the configured server timezone (UTC)
        date_default_timezone_set('UTC');
        */
    }
    
    /**
     * Sets timezone
     * 
     * @see Zend_Date::setTimezone()
     * @param  string $_timezone
     * @param  bool   $_recursive
     * @return  void
     * 
     * @todo implement
     */
    public function setTimezone($_timezone, $_recursive = TRUE)
    {
        /*
        foreach ($this->_datetimeFields as $field) {
            if (!isset($this->_properties[$field])) continue;
            
            if(!is_array($this->_properties[$field])) {
                $toConvert = array($this->_properties[$field]);
            } else {
                $toConvert = $this->_properties[$field];
            }

            foreach ($toConvert as $field => &$value) {
                if (! $value instanceof Zend_Date) {
                    throw new Tinebase_Exception_Record_Validation($toConvert[$field] . 'must be an Zend_Date'); 
                }
                $value->setTimezone($_timezone);
            } 
        }
        
        if ($_recursive) {
            foreach ($this->_properties as $property => $value) {
                if (is_object($value) && 
                        (in_array('Tinebase_Record_Interface', class_implements($value)) || 
                        $value instanceof Tinebase_Record_Recordset) ) {
                    $value->setTimezone($_timezone, TRUE);
                }
            }
        }
        */
    }
    
    /**
     * fills a filter group from json data
     *
     * @param string $_data json encoded data
     * @return void
     * 
     * @todo implement
     */
    public function setFromJson($_data)
    {
        /*
        $recordData = Zend_Json::decode($_data);
        
        $this->setFromArray($recordData);
        */
    }

    /**
     * converts filter group to json/array and resolves some filter data if necessary
     *
     * @return array
     * 
     * @todo add resolving of filters?
     */
    public function toJson()
    {
        return $this->toArray();
    }
    
    /**
     * loads a persistent filter
     *
     * @param string $_filterId
     * @return Tinebase_Model_Filter_FilterGroup
     */
    public static function loadPersistentFilter($_filterId)
    {
        $persistentFilterBackend = new Tinebase_PersistentFilter();
        $persistentFilter = $persistentFilterBackend->get($_filterId);
        
        $filter = new $persistentFilter->model(unserialize($persistentFilter->filters));
        
        return $filter;
    }

    /**
     * saves a persistent filter
     *
     * @param string $_filterId
     * @return Tinebase_Model_Filter_FilterGroup
     */
    public function save($_name)
    {
        $persistentFilterBackend = new Tinebase_PersistentFilter();
        $persistentFilter = new Tinebase_Model_PersistentFilter(array(
            'account_id'        => Tinebase_Core::getUser()->getId(),
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName)->getId(),
            'model'             => get_class($this),
            'filters'           => serialize($this->toArray()),
            'name'              => $_name
        ));
        
        $persistentFilterBackend->get($_filterId);
    }
    
    /**
     * return filter object
     *
     * @param string $_field
     * @return Tinebase_Model_Filter_Abstract
     */
    protected function _findFilter($_field)
    {
        foreach ($this->_filterObjects as $object) {
            if ($object->getField() == $_field) {
                return $object;        
            }
        }
        
        return NULL;
    }
}
