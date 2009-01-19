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
 * $filterModel = array (
 *     'name'       => array('filter' => Tinebase_Model_Filter_Text),
 *     'container'  => array('filter' => Tinebase_Model_Filter_Container, 'type' => Tinebase_Model_Container),
 *     'created_by' => array('filter' => Tinebase_Model_Filter_User, 'type' => Tinebase_Model_User)
 * );
 * $filterData = array(
 *     array('field' => 'name','operator' => 'beginsWith', 'value' => 'Hugo'),
 *     array('condition' => 'OR' => 'filters' => array(
 *         'field' => 'created_by'  => 'operator' => 'equals', 'value' => 2,
 *         'field' => 'modified_by' => 'operator' => 'equals', 'value' => 2
 *     ),
 *     array(field => 'container_id', 'operator' => isIn, 'value' => array(2,4,6,7)
 * );
 * 
 * $filterGroup = new Tinebase_Model_Filter_FilterGroup($filterData, 'AND', $filterModel);
 * 
 */
class Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array();
    
    /**
     * @var array list of filter objects this filter represents
     */
    protected $_filterObjects = array();
    
    /**
     * @var string condition the filters this filter represents
     */
    protected $_concatationCondition = NULL;
    
    /**
     * constructs a new filter group
     *
     * @param  array $_data
     * @param  string $_condition {AND|OR}
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function __construct($_data, $_condition='AND', array $_filterModel=array())
    {
        $this->_concatationCondition = $_condition == 'OR' ? 'OR' : 'AND';
        
        // we do this to work around static late binding limitation ;-(
        if (! empty($_filterModel)) {
            $this->_filterModel = $_filterModel;
        }
        
        foreach ($_data as $filterData) {
            if (isset($filterData['condition'])) {
                $this->addFilterGroup(new Tinebase_Model_Filter_FilterGroup($filterData['filters'], $filterData['condition'], $this->_filterModel));
            } elseif (in_array($filterData['field'], $this->_filterModel)) {
                $this->addFilter($this->createFilter($filterData['field'], $filterData['operator'], $filterData['value']));
            }
        }
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
        if (! empty($this->_filterModel[$_field])) {
            $definition = $this->_filterModel[$_field];
            $options = isset($definition['options']) ? $definition['options'] : NULL;
            
            $filter = new $definition['filter']($_field, $_operator, $_value, $options);
            
            return $filter;
        }
    }
    
    /**
     * returns concetationOperator / condition of this filtergroup
     *
     * @return string {AND|OR}
     */
    public function getCondition()
    {
        return $this->_concatationCondition;
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
    public function appendSql($_select)
    {
        foreach ($this->_filterObjects as $filter) {
            if ($filter instanceof Tinebase_Model_Filter_FilterGroup) {
                $groupSelect = new Tinebase_Model_Filter_DbGroupSelect($_select, $this->_concatationCondition);
                $filter->appendSql($groupSelect);
            } else {
                $filter->appendSql($_select);
            }
        }
    }
}