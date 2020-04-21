<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Tinebase_Model_Filter_ForeignRecord
 * 
 * filters own ids match result of foreign filter
 * 
 * @package     Tinebase
 * @subpackage  Filter
 */
abstract class Tinebase_Model_Filter_ForeignRecord extends Tinebase_Model_Filter_Abstract
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'AND',
        1 => 'OR',
        2 => 'equals', //expects ID as value
        3 => 'in', //expects IDs as value
        4 => 'not', //expects ID as value
        5 => 'notin', //expects IDs as value
        6 => 'notDefinedBy:AND',
        7 => 'notDefinedBy:OR',
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
     * if the value was null
     */
    protected $_valueIsNull = false;
        
    /**
     * creates corresponding filtergroup
     *
     * @param array $_value
     */
    public function setValue($_value)
    {
        $this->_foreignIds = NULL;
        $this->_valueIsNull = empty($_value) || (is_array($_value) && count($_value) === 1 && isset($_value[0]) &&
                is_array($_value[0]) && array_key_exists('value', $_value[0]) && empty($_value[0]['value']));

        // id(s) is/are to be provided directly as value
        if ($this->_operator === 'equals' || $this->_operator === 'in' || $this->_operator === 'not' ||
                $this->_operator === 'notin') {
            $this->_foreignIds = (array) $_value;
            $this->_value = null;

        } else {
            // (not)definedBy filter, value contains the subfilter
            $this->_value = (array)$_value;
            $this->_removePrefixes();
            if (!$this->_valueIsNull) {
                $this->_setFilterGroup();
            }
        }
    }
    
    /**
     * remove prefixes from filter fields
     */
    protected function _removePrefixes()
    {
        $this->_prefixedFields = $this->_removePrefixesFromFilterValue($this->_value);
    }

    protected function _removePrefixesFromFilterValue(&$value)
    {
        $prefixedFields = array();
        foreach ($value as $idx => $filterData) {
            if (! isset($filterData['field'])) {
                continue;
            }

            if (strpos($filterData['field'], ':') !== FALSE) {
                $value[$idx]['field'] = str_replace(':', '', $filterData['field']);
                $prefixedFields[] = $value[$idx]['field'];
            }
        }
        return $prefixedFields;
    }

    /**
     * get foreign filter group
     * 
     * @return Tinebase_Model_Filter_FilterGroup
     */
    protected function _setFilterGroup()
    {
        $this->_filterGroup = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            $this->_options['filtergroup'],
            $this->_value,
            strpos($this->_operator, 'OR') !== false ? 'OR' : 'AND',
            $this->_options
        );
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
        if (! (isset($_options['isGeneric']) || array_key_exists('isGeneric', $_options))) {
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

        if (null !== $this->_filterGroup) {
            $filters = $this->_getForeignFiltersForToArray($_valueToJson);

            if ($this->_options && isset($this->_options['isGeneric']) && $this->_options['isGeneric']) {
                $result['value'] = $this->_getGenericFilterInformation();
                $result['value']['filters'] = $filters;
            } else {
                $result['value'] = $filters;
            }
        } else {
            if ($_valueToJson && !empty($this->_foreignIds)) {
                if (count($this->_foreignIds) > 1) {
                    foreach ($this->_foreignIds as $key => $value) {
                        $result['value'][$key] = $this->_resolveRecord($value);
                    }
                } else {
                    $result['value'] = $this->_resolveRecord($this->_foreignIds[0]);
                }
            } else {
                if ($this->_operator === 'equals' || $this->_operator === 'in' || $this->_operator === 'not' ||
                        $this->_operator === 'notin') {
                    $result['value'] = $this->_foreignIds;
                } else {
                    // (not)definedBy filter, value contains the subfilter
                    $result['value'] = $this->_value;
                    foreach ($result['value'] as $idx => $filterData) {
                        if (! isset($filterData['field'])) {
                            continue;
                        }

                        if (in_array($filterData['field'], $this->_prefixedFields)) {
                            $result['value'][$idx]['field'] = ':' . $result['value'][$idx]['field'];
                        }
                    }
                }

            }
        }
        
        return $result;
    }

    /**
     * resolves a record
     *
     * @param string $value
     * @return array|string
     */
    protected function _resolveRecord($value)
    {
        $controller = $this->_getController();
        if ($controller === NULL) {
            return $value;
        }

        try {
            if (method_exists($controller, 'get')) {
                $recordArray = $controller->get($value, /* $_containerId = */ null, /* $_getRelatedData = */ false)->toArray();
            } else {
                Tinebase_Core::getLogger()->NOTICE(__METHOD__ . '::' . __LINE__ . ' Controller ' . get_class($controller) . ' has no get method');
                return $value;
            }
        } catch (Exception $e) {
            $recordArray = $value;
        }

        return $recordArray;
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
        /** @var Tinebase_Model_Filter_Abstract $filter */
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
