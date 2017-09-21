<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

/**
 * Tinebase_Model_Filter_CustomField
 * 
 * filters by given customfield name/value
 * 
 * a custom field filter is constructed like this:
 * 
 *  array(
 *     'field' => 'customfield', 
 *     'operator' => 'contains', 
 *     'value' => array('cfId' => '1234', 'value' => 'searchstring')
 *  ),
 * 
 * @package     Tinebase
 * @subpackage  Filter
 *
 * @refactor! this has some issues with the different cf types and the parent class Tinebase_Model_Filter_ForeignRecord
 */
class Tinebase_Model_Filter_CustomField extends Tinebase_Model_Filter_ForeignRecord
{
    /**
     * possible operators
     * 
     * @var array
     */
    protected $_operators = NULL;
    
    /**
     * the customfield record
     * 
     * @var Tinebase_Model_CustomField_Config
     */
    protected $_cfRecord  = NULL;
    
    /**
     * get a new single filter action
     *
     * @param string|array $_fieldOrData
     * @param string $_operator
     * @param mixed  $_value    
     * @param array  $_options
     */
    public function __construct($_fieldOrData, $_operator = NULL, $_value = NULL, array $_options = array())
    {
        // no legacy handling
        if(!is_array($_fieldOrData)) {
            throw new Tinebase_Exception_InvalidArgument('$_fieldOrData must be an array!');
        }

        $valueFilter = null;
        $be = new Tinebase_CustomField_Config();
        $this->_cfRecord = $be->get($_fieldOrData['value']['cfId']);
        $type = $this->_cfRecord->definition['type'];
        if ($type == 'date' || $type == 'datetime') {
            $this->_filterGroup = new Tinebase_Model_CustomField_ValueFilter(array());
            $this->_filterGroup->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'customfield_id', 'operator' => 'equals', 'value' => $_fieldOrData['value']['cfId'])));
            $valueFilter = new Tinebase_Model_Filter_Date(array('field' => 'value', 'operator' => $_fieldOrData['operator'], 'value' => $_fieldOrData['value']['value']));
            $this->_filterGroup->addFilter($valueFilter);
        } elseif ($type == 'integer') {
            $valueFilter = new Tinebase_Model_Filter_Int($_fieldOrData, $_operator, $_value, $_options);
        } else if ($type == 'record' && is_array($_fieldOrData['value']['value'])) {
            $modelName = Tinebase_CustomField::getModelNameFromDefinition($this->_cfRecord->definition);
            $this->_controller = Tinebase_Core::getApplicationInstance($modelName);
            $this->_filterGroup = Tinebase_Model_Filter_FilterGroup::getFilterForModel($modelName);
        } else if ($type == 'records') {
            // TODO support recordset
            throw new Tinebase_Exception_NotImplemented('filter for records type not implemented yet');
        } else {
            $valueFilter = new Tinebase_Model_Filter_Text($_fieldOrData, $_operator, $_value, $_options);
        }

        if ($valueFilter) {
            $this->_valueFilter = $valueFilter;
            $this->_operators = $valueFilter->getOperators();
            $this->_opSqlMap = $this->_valueFilter->getOpSqlMap();
        } else {
            $this->_operators = array('AND', 'OR');
            $this->_opSqlMap = array();
        }
        parent::__construct($_fieldOrData, $_operator, $_value, $_options);
    }

    /**
     * get foreign controller
     *
     * @return Tinebase_Controller_Record_Abstract
     */
    protected function _getController()
    {
        if (! $this->_controller) {
            $modelName = Tinebase_CustomField::getModelNameFromDefinition($this->_cfRecord->definition);
            $this->_controller = Tinebase_Core::getApplicationInstance($modelName);
        }

        return $this->_controller;
    }
    
    /**
     * set options 
     *
     * @param  array $_options
     */
    protected function _setOptions(array $_options)
    {
        $_options['idProperty'] = isset($_options['idProperty']) ? $_options['idProperty'] : 'id';
        
        $this->_options = $_options;
    }
    
    /**
     * appends sql to given select statement
     *
     * @param  Zend_Db_Select                $_select
     * @param  Tinebase_Backend_Sql_Abstract $_backend
     * @throws Tinebase_Exception_UnexpectedValue
     */
    public function appendFilterSql($_select, $_backend)
    {
        // don't take empty filter into account
        if (     empty($this->_value)          || ! is_array($this->_value)    || ! isset($this->_value['cfId'])  || empty($this->_value['cfId']) 
            || ! isset($this->_value['value'])) 
        {
            return;

            // what is that?!? for record the operators AND / OR, in is not allowed for record?!?
        } else if ($this->_cfRecord->definition['type'] !== 'record' && strtolower($this->_operator) == 'in') {
            throw new Tinebase_Exception_UnexpectedValue('Operator "in" not supported.');
        }
        
        // make sure $correlationName is a string
        $correlationName = Tinebase_Record_Abstract::generateUID(30);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .
            ' Adding custom field filter: ' . print_r($this->_value, true));
        
        $db = Tinebase_Core::getDb();
        $idProperty = $db->quoteIdentifier($this->_options['idProperty']);
        
        // per left join we add a customfield column named as the customfield and filter this joined column
        // NOTE: we name the column we join like the customfield, to be able to join multiple customfield criteria (multiple invocations of this function)
        $what = array($correlationName => SQL_TABLE_PREFIX . 'customfield');
        $on = $db->quoteIdentifier("{$correlationName}.record_id")      . " = $idProperty AND " 
            . $db->quoteIdentifier("{$correlationName}.customfield_id") . " = " . $db->quote($this->_value['cfId']);
        $_select->joinLeft($what, $on, array());

        $valueIdentifier = $db->quoteIdentifier("{$correlationName}.value");
        $value = $this->_value['value'];
        $operator = $this->_operator;

        switch ($this->_cfRecord->definition['type']) {
            case 'date':
            case 'datetime':
                $customfields = Tinebase_CustomField::getInstance()->search($this->_filterGroup);
                if ($customfields->count()) {
                    $where = $db->quoteInto($idProperty . ' IN (?) ', $customfields->record_id);
                } else {
                    $where = '1=2';
                }
                break;
            default:
                if ($this->_cfRecord->definition['type'] === 'record' && is_array($value)) {
                    $this->_removePrefixesFromFilterValue($value);
                    $this->_filterGroup->setFromArray($value);
                    $ids = $this->_getController()->search($this->_filterGroup, null, /*relations */
                        false, /* only ids */
                        true);
                    if (count($ids)) {
                        $where = $db->quoteInto($valueIdentifier . ' IN (?) ', $ids);
                    } else {
                        $where = '1=2';
                    }
                } else if (! $value) {
                    $where = $db->quoteInto($valueIdentifier. ' IS NULL OR ' . $valueIdentifier . ' = ?', $value);
                } else {
                    $value = $this->_replaceWildcards($value);
                    if (($this->_cfRecord->definition['type'] == 'keyField' || $this->_cfRecord->definition['type'] == 'record')
                        && $operator == 'not') {
                        $where = $db->quoteInto($valueIdentifier . ' IS NULL OR ' . $valueIdentifier . $this->_opSqlMap[$operator]['sqlop'], $value);
                    } else {
                        $where = $db->quoteInto($valueIdentifier . $this->_opSqlMap[$operator]['sqlop'], $value);
                    }
                }
        }
        $_select->where($where);
    }
    
    /**
     * returns array with the filter settings of this filter
     *
     * @param  bool $valueToJson resolve value for json api?
     * @return array
     */
    public function toArray($valueToJson = false)
    {
        // TODO can't use direct parent - should be improved!
        //$result = parent::toArray($valueToJson);
        $result = array(
            'field'     => $this->_field,
            'operator'  => $this->_operator,
            'value'     => $this->_value
        );
        if ($this->_isImplicit) {
            $result['implicit'] = TRUE;
        }
        if ($this->_id) {
            $result['id'] = $this->_id;
        }
        if ($this->_label) {
            $result['label'] = $this->_label;
        }
        // above is code from \Tinebase_Model_Filter_Abstract::toArray

        if (strtolower($this->_cfRecord->definition['type']) == 'record') {
            try {
                $modelName = Tinebase_CustomField::getModelNameFromDefinition($this->_cfRecord->definition);
                $controller = Tinebase_Core::getApplicationInstance($modelName);
                if (is_string($result['value']['value'])) {
                    $result['value']['value'] = $controller->get($result['value']['value'])->toArray();
                } else if (is_array($result['value']['value'])) {
                    //  this is very bad - @refactor
                    foreach ($result['value']['value'] as $key => $subfilter) {
                        if (isset($result['value']['value'][$key]['value']) && is_string($result['value']['value'][$key]['value']))
                        $result['value']['value'][$key]['value'] = $controller->get($result['value']['value'][$key]['value'])->toArray();
                    }

                } else {
                    // TODO do we need to do something in this case?
                }
            } catch (Exception $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' Error resolving custom field record: ' . $e->getMessage());
            }
        }
        //$this->_returnPrefixes($result);
        
        return $result;
    }

    /**
     * get filter information for toArray()
     *
     * @return array
     */
    protected function _getGenericFilterInformation()
    {
        // not needed ...
        return array();
    }

    /**
     * get foreign filter group
     *
     * @return Tinebase_Model_Filter_FilterGroup
     */
    protected function _setFilterGroup()
    {
        // this is done in __construct (only for some types)
    }
}
