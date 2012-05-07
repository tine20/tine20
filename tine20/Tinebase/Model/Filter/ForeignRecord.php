<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Tinebase_Model_Filter_ForeignRecord
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * 
 * filters own ids match result of foreign filter
 */
abstract class Tinebase_Model_Filter_ForeignRecord extends Tinebase_Model_Filter_Abstract
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'AND',
        1 => 'OR',
    );
    
    /**
     * @var Tinebase_Model_Filter_FilterGroup
     */
    protected $_filterGroup = NULL;
    
    /**
     * @var Tinebase_Controller_Record_Abstract
     */
    protected $_controller = NULL;
    
    /**
     * @var array
     */
    protected $_foreignIds = NULL;
        
    /**
     * the prefixed ("left") fields sent by the client
     * 
     * @var array
     */
    protected $_prefixedFields = array();
        
    /**
     * creates corresponding filtergroup
     *
     * @param array $_value
     */
    public function setValue($_value) {
        $this->_foreignIds = NULL;
        $this->_value = (array)$_value;
        $this->_removePrefixes();
        
        // @todo move this to another place?
        $this->_setFilterGroup();
    }
    
    /**
     * remove prefixes from filter fields
     */
    protected function _removePrefixes()
    {
        foreach ($this->_value as $idx => $filterData) {
            if (! isset($filterData['field'])) {
                continue;
            }
            
            if (strpos($filterData['field'], ':') !== FALSE) {
                $this->_value[$idx]['field'] = str_replace(':', '', $filterData['field']);
                $this->_prefixedFields[] = $this->_value[$idx]['field'];
            }
        }
    }
    
    /**
     * get foreign filter group
     * 
     * @return Tinebase_Model_Filter_FilterGroup
     */
    protected function _setFilterGroup()
    {
        $this->_filterGroup = new $this->_options['filtergroup']($this->_value, $this->_operator, $this->_options);
    }
    
    /**
     * get foreign controller
     * 
     * @return Tinebase_Controller_Record_Abstract
     */
    abstract protected function _getController();
    
    /**
     * set options 
     *
     * @param array $_options
     */
    protected function _setOptions(array $_options)
    {
        if (! array_key_exists('isGeneric', $_options)) {
            $_options['isGeneric'] = FALSE;
        }
        
        $this->_options = $_options;
    }
    
    /**
     * returns array with the filter settings of this filter
     *
     * @param  bool $_valueToJson resolve value for json api?
     * @return array
     */
    public function toArray($_valueToJson = false)
    {
        $result = array(
            'field'     => $this->_field,
            'operator'  => $this->_operator,
        );
        
        if ($this->_id) {
            $result['id'] = $this->_id;
        }
        
        $filters = $this->_getForeignFiltersForToArray($_valueToJson);
        
        if ($this->_options && $this->_options['isGeneric']) {
            $result['value'] = $this->_getGenericFilterInformation();
            $result['value']['filters'] = $filters;
        } else {
            $result['value'] = $filters;
        }
        
        return $result;
    }
    
    /**
     * returns filter group filters
     * 
     * @param  bool $_valueToJson resolve value for json api?
     * @param  array $_additionalFilters
     * @return array
     * 
     * @todo think about allowing {condition: ...., filters: ....} syntax and just use $this->_filterGroup->toArray
     */
    protected function _getForeignFiltersForToArray($_valueToJson, $_additionalFilters = array())
    {
        $result = $_additionalFilters;
        // we can't do this as we do not want the condition/filters syntax
        // $result = $this->_filterGroup->toArray($_valueToJson);
        $this->_filterGroupToArrayWithoutCondition($result, $this->_filterGroup, $_valueToJson);
        $this->_returnPrefixes($result);
        
        return $result;
    }
    
    /**
     * return prefixes to foreign filters
     * 
     * @param array $_filters
     */
    protected function _returnPrefixes(&$_filters)
    {
        if (! empty($this->_prefixedFields)) {
            foreach ($_filters as $idx => $filterData) {
                if (isset($filterData['field']) && in_array($filterData['field'], $this->_prefixedFields)) {
                    $_filters[$idx]['field'] = ':' . $filterData['field'];
                }
            }
        }
    }
    
    /**
     * the client cannot handle {condition: ...., filters: ....} syntax
     * 
     * @param  array $result
     * @param  Tinebase_Model_Filter_FilterGroup $_filtergroup
     * @param  bool $_valueToJson resolve value for json api?
     * 
     * @todo move this to filtergroup?
     */
    protected function _filterGroupToArrayWithoutCondition(&$result, Tinebase_Model_Filter_FilterGroup $_filtergroup, $_valueToJson)
    {
        $filterObjects = $_filtergroup->getFilterObjects();
        foreach ($filterObjects as $filter) {
            if ($filter instanceof Tinebase_Model_Filter_FilterGroup) {
                $this->_filterGroupToArrayWithoutCondition($result, $filter, $_valueToJson);
            } else {
                $result[] = $filter->toArray($_valueToJson);
            }
        }
    }
    
    /**
     * get filter information for toArray()
     * 
     * @return array
     */
    abstract protected function _getGenericFilterInformation();
}
