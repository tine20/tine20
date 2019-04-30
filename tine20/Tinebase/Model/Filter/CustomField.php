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
 *  [
 *     'field' => 'customfield', 
 *     'operator' => 'contains', 
 *     'value' => ['cfId' => '1234', 'value' => 'searchstring']
 *  ],
 *
 * to force full text search even for non text customfield types:
 * [
 *     'field' => 'customfield',
 *     'operator' => 'contains',
 *     'value' => ['cfId' => '1234', 'forceFullText' => true, 'value' => 'searchstring']
 *  ],
 *
 * record type customfield:
 * [
 *     'field' => 'customfield',
 *     'operator' => '{not}in',
 *     'value' => ['cfId' => '1234', 'value' => [
 *          filter arrays go here
 *      ]]
 *  ],
 * 
 * @package     Tinebase
 * @subpackage  Filter
 *
 */
class Tinebase_Model_Filter_CustomField extends Tinebase_Model_Filter_Abstract
{
    const OPT_FORCE_FULLTEXT = 'forceFullText';
    const OPT_FORCE_TYPE = 'forceType';

    /**
     * the filter used for querying the customfields table
     * 
     * @var Tinebase_Model_Filter_Abstract
     */
    protected $_valueFilter = null;

    /**
     * @var array
     */
    protected $_valueFilterOptions = null;
    
    /**
     * possible operators
     * 
     * @var array
     */
    protected $_operators = null;
    
    /**
     * the customfield record
     * 
     * @var Tinebase_Model_CustomField_Config
     */
    protected $_cfRecord  = null;

    /**
     * the table alias of the joined customfield table
     *
     * @var string
     */
    protected $_correlationName = null;

    /**
     * the subfilter to find record ids that should (not) be the value of customfield.value
     *
     * @var null|Tinebase_Model_Filter_FilterGroup
     */
    protected $_subFilter = null;
    /**
     * the controller to search in using the subFilter
     *
     * @var null|Tinebase_Controller_Record_Abstract
     */
    protected $_subFilterController = null;

    
    /**
     * get a new single filter action
     *
     * @param string|array $_fieldOrData
     * @param string $_operator
     * @param mixed  $_value
     * @param array  $_options
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotImplemented
     */
    public function __construct($_fieldOrData, $_operator = NULL, $_value = NULL, array $_options = [])
    {
        // no legacy handling
        if (!is_array($_fieldOrData) || !isset($_fieldOrData['value']['cfId']) || !array_key_exists('value',
                $_fieldOrData['value'])) {
            throw new Tinebase_Exception_InvalidArgument('$_fieldOrData must be an array see source comment!');
        }

        $this->_correlationName = Tinebase_Record_Abstract::generateUID(30);
        $this->_valueFilterOptions = $_options;
        $this->_valueFilterOptions['tablename'] = $this->_correlationName;
        $be = new Tinebase_CustomField_Config();
        $this->_cfRecord = $be->get($_fieldOrData['value']['cfId']);
        if (isset($_fieldOrData['value'][self::OPT_FORCE_TYPE])) {
            $type = $_fieldOrData['value'][self::OPT_FORCE_TYPE];
        } else {
            $type = $this->_cfRecord->definition['type'];
        }

switch ($type) {
            case 'string':
            case 'text':
                $filterClass = Tinebase_Model_Filter_Text::class;
                break;
            case 'textarea':
                // TODO is this still needed?
//        $forceFullText = isset($_fieldOrData['value'][self::OPT_FORCE_FULLTEXT]) ?
//            (bool)$_fieldOrData['value'][self::OPT_FORCE_FULLTEXT] : false;
//                if ($forceFullText) {
//        $filterClass = Tinebase_Model_Filter_FullText::class;
//                }

            $filterClass = Tinebase_Model_Filter_FullText::class;
                break;
            case 'date' :
            case 'datetime':
                $filterClass = Tinebase_Model_Filter_Date::class;
                break;
            case 'integer':
            case 'int':
                $filterClass = Tinebase_Model_Filter_Int::class;
                break;
            case 'bool':
            case 'boolean':
                $filterClass = Tinebase_Model_Filter_Id::class;
                $_fieldOrData['value']['value'] = $_fieldOrData['value']['value'] ? '1' : '0';
                break;
            case 'record':
                if (is_array($_fieldOrData['value']['value'])) {
                    $modelName = Tinebase_CustomField::getModelNameFromDefinition($this->_cfRecord->definition);
                    $this->_subFilterController = Tinebase_Core::getApplicationInstance($modelName);
                    $this->_subFilter = Tinebase_Model_Filter_FilterGroup::getFilterForModel($modelName);
                    $filterClass = null;
                    $this->_operators = array('AND', 'OR');
                } else {
                    $filterClass = Tinebase_Model_Filter_Id::class;
                }
                break;
            case 'records':
                // TODO support recordset
                throw new Tinebase_Exception_NotImplemented('filter for records type not implemented yet');
                break;
            default:
                // nothing here - parent is used
        }

        if (null !== $filterClass) {
            $this->_valueFilter = new $filterClass(
                [
                    'field' => 'value',
                    'operator' => $_fieldOrData['operator'],
                    'value' => $_fieldOrData['value']['value'],
                    'options' => $this->_valueFilterOptions
                ]);
            $this->_operators = $this->_valueFilter->getOperators();
        }

        parent::__construct($_fieldOrData, $_operator, $_value, $_options);
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
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .
            ' Adding custom field filter: ' . print_r($this->_value, true));
        
        $db = Tinebase_Core::getDb();
        $idProperty = $db->quoteIdentifier($this->_options['idProperty']);
        
        // per left join we add a customfield column named as the customfield and filter this joined column
        // NOTE: we name the column we join like the customfield, to be able to join multiple customfield criteria (multiple invocations of this function)
        $what = array($this->_correlationName => SQL_TABLE_PREFIX . 'customfield');
        $on = $db->quoteIdentifier("{$this->_correlationName}.record_id")      . " = $idProperty AND "
            . $db->quoteIdentifier("{$this->_correlationName}.customfield_id") . " = " . $db->quote($this->_value['cfId']);
        $_select->joinLeft($what, $on, array());

        if (null !== $this->_subFilterController && null !== $this->_subFilter) {
            $value = $this->_value['value'];
            array_walk($value, function(&$val) {
                if (isset($val['field']) && strpos($val['field'], ':') === 0) {
                    $val['field'] = substr($val['field'], 1);
                }
            });
            $this->_subFilter->setFromArray($value);
            $ids = $this->_subFilterController->search($this->_subFilter, null, false, true);
            if (count($ids)) {
                $this->_valueFilter = new Tinebase_Model_Filter_Id(
                    [
                        'field' => 'value',
                        'operator' => 'in',
                        'value' => $ids,
                        'options' => $this->_valueFilterOptions
                    ]);
            } else {
                $_select->where('1=2');
            }
        }
        if (null !== $this->_valueFilter) {
            $valueIdentifier = $db->quoteIdentifier("{$this->_correlationName}.value");
            if (!$this->_value['value']) {
                $_select->where($db->quoteInto($valueIdentifier . ' IS NULL OR ' . $valueIdentifier . ' = ?',
                    $this->_value['value']));
            } else {
                if (strpos($this->_operator, 'not') === 0) {
                    $groupSelect = new Tinebase_Backend_Sql_Filter_GroupSelect($_select);
                    $this->_valueFilter->appendFilterSql($groupSelect, $_backend);
                    $groupSelect->orWhere($valueIdentifier . ' IS NULL');
                    $groupSelect->appendWhere(Zend_Db_Select::SQL_OR);
                } else {
                    $this->_valueFilter->appendFilterSql($_select, $_backend);
                }
            }
        }
    }
    
    /**
     * returns array with the filter settings of this filter
     *
     * @param  bool $valueToJson resolve value for json api?
     * @return array
     */
    public function toArray($valueToJson = false)
    {
        $result = parent::toArray($valueToJson);
        if (strtolower($this->_cfRecord->definition['type']) == 'record') {
            if ($valueToJson) {
                try {
                    $modelName = Tinebase_CustomField::getModelNameFromDefinition($this->_cfRecord->definition);
                    $controller = Tinebase_Core::getApplicationInstance($modelName);
                    if (is_string($result['value']['value'])) {
                        $result['value']['value'] = $controller->get($result['value']['value'])->toArray();
                    } else if (is_array($result['value']['value'])) {
                        //  this is very bad - @refactor
                        foreach ($result['value']['value'] as $key => $subfilter) {
                            if (isset($subfilter['field']) && $subfilter['field'] === ':id' && isset($subfilter['value']) &&
                                is_string($subfilter['value'])) {
                                $result['value']['value'][$key]['value'] = $controller->get($subfilter['value'])->toArray();
                            }
                        }

                    } else {
                        // TODO do we need to do something in this case?
                    }
                } catch (Exception $e) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' Error resolving custom field record: ' . $e->getMessage());
                    Tinebase_Exception::log($e);
                }
            } else if (is_array($result['value']['value'])) {
                // return hydrated value
                foreach ($result['value']['value'] as $key => $subfilter) {
                    if (isset($subfilter['field'])
                        && $subfilter['field'] === ':id'
                        && isset($subfilter['value'])
                        && is_array($subfilter['value'])
                    ) {
                        if (isset($subfilter['value']['id'])) {
                            $result['value']['value'][$key]['value'] = $subfilter['value']['id'];
                        } else {
                            // set empty id value
                            $result['value']['value'][$key]['value'] = '';
                        }
                    }
                }
            }
        }
        
        return $result;
    }
}
