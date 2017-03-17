<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Tinebase_Model_Filter_Query
 * 
 * filters for all of the given filterstrings if it is contained in at least 
 * one of the defined fields
 * 
 * -> allow search for all Müllers who live in Munich but not all Müllers and all people who live in Munich
 * 
 * The fields to query in _must_ be defined in the options key 'fields'
 * The value string is space-exploded into multiple filterstrings
 * 
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_Filter_Query extends Tinebase_Model_Filter_FilterGroup
{
    use Tinebase_Model_Filter_AdvancedSearchTrait;

    protected $_field;
    protected $_value;
    protected $_operator;

    /**
     * constructs a new filter group
     *
     * @param  array $_data
     * @param  string $_condition {AND|OR}
     * @param  array $_options
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function __construct(array $_data = array(), $_condition = '', $_options = array())
    {
        $condition = (0 === strpos($_data['operator'], 'not')) ? Tinebase_Model_Filter_FilterGroup::CONDITION_AND : Tinebase_Model_Filter_FilterGroup::CONDITION_OR;

        parent::__construct(array(),
            $condition,
            $_data['options']);

        $this->_field = $_data['field'];
        $this->_value = $_data['value'];
        $this->_operator = $_data['operator'];

        if (!empty($this->_value)) {
            $queries = explode(' ', $this->_value);

            /** @var Tinebase_Model_Filter_FilterGroup $parentFilterGroup */
            $parentFilterGroup = $this->_options['parentFilter'];
            /** @var Tinebase_Model_Filter_FilterGroup $innerGroup */
            $innerGroup = new Tinebase_Model_Filter_FilterGroup(array(), Tinebase_Model_Filter_FilterGroup::CONDITION_AND);

            switch ($this->_operator) {
                case 'contains':
                case 'notcontains':
                case 'equals':
                case 'not':
                case 'startswith':
                case 'endswith':
                    foreach ($queries as $query) {
                        /** @var Tinebase_Model_Filter_FilterGroup $subGroup */
                        $subGroup = new Tinebase_Model_Filter_FilterGroup(array(), $condition);
                        foreach ($this->_options['fields'] as $field) {
                            $filter = $parentFilterGroup->createFilter($field, $this->_operator, $query);
                            if (in_array($this->_operator, $filter->getOperators())) {
                                $subGroup->addFilter($filter);
                            } else {
                                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ .
                                    ' field: ' . $field . ' => filter: ' . get_class($filter) . ' doesn\'t support operator: ' . $this->_operator . ' => not applying filter!');
                            }
                        }
                        $innerGroup->addFilterGroup($subGroup);
                    }
                    break;

                case 'notin':
                case 'in':
                    foreach ($this->_options['fields'] as $field) {
                        $filter = $parentFilterGroup->createFilter($field, $this->_operator, $queries);
                        if (in_array($this->_operator, $filter->getOperators())) {
                            $innerGroup->addFilter($filter);
                        } else {
                            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ .
                                ' field: ' . $field . ' => filter: ' . get_class($filter) . ' doesn\'t support operator: ' . $this->_operator . ' => not applying filter!');
                        }
                    }
                    break;
                default:
                    throw new Tinebase_Exception_InvalidArgument('Operator not defined: ' . $this->_operator);
            }

            $this->addFilterGroup($innerGroup);
        }

        if (isset($this->_options['relatedModels']) && isset($this->_options['modelName'])) {
            $relationFilter = $this->_getAdvancedSearchFilter($this->_options['modelName'], $this->_options['relatedModels']);
            if (null !== $relationFilter) {
                $this->addFilter($relationFilter);
            }
        }
    }

    /**
     * set options 
     *
     * @param  array $_options
     * @throws Tinebase_Exception_Record_NotDefined
     * @throws Tinebase_Exception_UnexpectedValue
     */
    protected function _setOptions(array $_options)
    {
        if (empty($_options['fields'])) {
            throw new Tinebase_Exception_Record_NotDefined('Fields must be defined in the options of a query filter');
        }
        if (!isset($_options['parentFilter']) || !is_object($_options['parentFilter'])) {
            throw new Tinebase_Exception_UnexpectedValue('parentFilter needs to be set in options (should be done by parent filter group)');
        }
        
        parent::_setOptions($_options);
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
            'value'     => $this->_value
        );

        if ($this->_id) {
            $result['id'] = $this->_id;
        }
        if ($this->_label) {
            $result['label'] = $this->_label;
        }

        return $result;
    }
}