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
 * Tinebase_Model_Filter_Abstract
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * 
 * Abstract filter
 * 
 * @todo validate value!
 */
abstract class Tinebase_Model_Filter_Abstract
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array();
    
    /**
     * @var string property this filter is applied to
     */
    protected $_field = NULL;
    
    /**
     * @var string operator
     */
    protected $_operator = NULL;
    
    /**
     * @var mixed value to filter with
     */
    protected $_value = NULL;
    
    /**
     * @var array spechial options
     */
    protected $_options = NULL;
    
    /**
     * get a new single filter action
     *
     * @param string $_field
     * @param string $_operator
     * @param mixed  $_value    
     * @param array  $_options
     */
    public function __construct($_field, $_operator, $_value, array $_options = array())
    {
        $this->_setOptions($_options);
        $this->setField($_field);
        $this->setOperator($_operator);
        $this->setValue($_value);
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
     * set field 
     *
     * @param string $_field
     */
    public function setField($_field)
    {
        $this->_field = $_field;
    }
    
    /**
     * returns fieldname of this filter
     *
     * @return string
     */
    public function getField()
    {
        return $this->_field;
    }
    
    /**
     * sets operator
     *
     * @param string $_operator
     */
    public function setOperator($_operator)
    {
        if (! in_array($_operator, $this->_operators)) {
            throw new Tinebase_Exception_UnexpectedValue("operator $_operator is not defined");
        }
        
        $this->_operator = $_operator;
    }
    
    /**
     * gets operator
     *
     * @return  string
     */
    public function getOperator()
    {
        return $this->_operator;
    }
    
    /**
     * sets value
     *
     * @param mixed $_value
     */
    public function setValue($_value)
    {
        //@todo validate value before setting it!
        $this->_value = $_value;
    }

    /**
     * gets value
     *
     * @return  mixed 
     */
    public function getValue()
    {
        return $this->_value;
    }
    
    /**
     * appends sql to given select statement
     *
     * @param Zend_Db_Select                $_select
     * @param Tinebase_Backend_Sql_Abstract $_backend
     * 
     * @todo to be removed once we split filter model / backend
     */
    abstract public function appendFilterSql($_select, $_backend);
    
    /**
     * returns quoted column name for sql backend
     *
     * @param  Tinebase_Backend_Sql_Interface $_backend
     * @return string
     * 
     * @todo to be removed once we split filter model / backend
     */
    protected function _getQuotedFieldName($_backend) {
        return $_backend->getAdapter()->quoteIdentifier(
            $_backend->getTableName() . '.' . $this->_field
        );
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
        
        return $result;
    }

    /**
     * convert string in user time to UTC
     *
     * @param string $_string
     * @return string
     */
    protected function _convertStringToUTC($_string)
    {
        if (empty($_string)) {
            $date = new Zend_Date();
            $result = $date->toString(Tinebase_Record_Abstract::ISO8601LONG);
        } elseif (isset($this->_options['timezone']) && $this->_options['timezone'] !== 'UTC') {
            date_default_timezone_set($this->_options['timezone']);
            $date = new Zend_Date($_string, Tinebase_Record_Abstract::ISO8601LONG);
            $date->setTimezone('UTC');
            $result = $date->toString(Tinebase_Record_Abstract::ISO8601LONG);
            date_default_timezone_set('UTC');
        } else {
            $result = $_string;
        }
        
        return $result;
    }
    
    /**
     * replaces wildcards
     * 
     * @param  string $value
     * @return string
     */
    protected function _replaceWildcards($value)
    {
        if (is_array($value)) {
            $returnValue = array();
            foreach ($value as $idx => $val) {
                $returnValue[$idx] = $this->_replaceWildcardsSingleValue($val);
            }
        } else {
            $returnValue = $this->_replaceWildcardsSingleValue($value);
        }
        
        return $returnValue;
    }
    
    /**
     * replaces wildcards of a single value
     * 
     * @param  string $value
     * @return string
     */
    protected function _replaceWildcardsSingleValue($value)
    {
        $action = $this->_opSqlMap[$this->_operator];
        
        // replace wildcards from user ()
        $returnValue = str_replace(array('*', '_'), array('%', '\_'), $value);
        
        // add wildcard to value according to operator
        $returnValue = str_replace('?', $returnValue, $action['wildcards']);
        
        return $returnValue;
    }
}
