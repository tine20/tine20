<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
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
     * @var string filter id [optional]
     */
    protected $_id = NULL;
    
    /**
     * @var string filter label [optional]
     */
    protected $_label = NULL;
    
    /**
     * @var array special options
     */
    protected $_options = NULL;

    /**
     * @var Tinebase_Backend_Sql_Command_Interface
     */
    protected $_dbCommand;

    /**
     * filter is implicit, this is returned in toArray
     * - this is only needed to detect acl filters that have been added by a controller
     * 
     * @var boolean
     * @todo move this to acl filter?
     */
    protected $_isImplicit = FALSE;
    
    /**
     * get a new single filter action
     *
     * @param string|array $_fieldOrData
     * @param string $_operator
     * @param mixed  $_value    
     * @param array  $_options
     * 
     * @todo remove legacy code + obsolete params sometimes
     */
    public function __construct($_fieldOrData, $_operator = NULL, $_value = NULL, array $_options = array())
    {
        $this->_db = Tinebase_Core::getDb();
        $this->_dbCommand = Tinebase_Backend_Sql_Command::factory($this->_db);

        if (is_array($_fieldOrData)) {
            $data = $_fieldOrData;
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' Using deprecated constructor syntax. Please pass all filter data in one array (filter field: ' . $_fieldOrData . ').');
            
            $data = array(
                'field'     => $_fieldOrData,
                'operator'  => $_operator,
                'value'     => $_value,
                'options'   => $_options,
            );
        }

        foreach (array('field', 'operator', 'value') as $requiredKey) {
            if (! array_key_exists($requiredKey, $data)) {
                throw new Tinebase_Exception_InvalidArgument('Filter object needs ' . $requiredKey);
            }
        }
        
        $this->_setOptions((isset($data['options'])) ? $data['options'] : array());
        $this->setField($data['field']);
        $this->setOperator($data['operator']);
        $this->setValue($data['value']);
        
        if (isset($data['id'])) {
            $this->setId($data['id']);
        }
        if (isset($data['label'])) {
            $this->setLabel($data['label']);
        }
    }
    
    /**
     * returns operators of this filter model
     * @return array
     */
    public function getOperators()
    {
        return $this->_operators;
    }
    
    /**
     * returns operator sql mapping
     * @return array
     */
    public function getOpSqlMap()
    {
        if($this->_opSqlMap) {
            return $this->_opSqlMap;
        }
        return NULL;
    }
    
    /**
     * set options 
     *
     * @param array $_options
     */
    protected function _setOptions(array $_options)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' '
            . ' ' . print_r($_options, TRUE));
        
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
        if (empty($_operator) && isset($this->_operators[0])) {
            // try to use default/first operator
            $_operator = $this->_operators[0];
        }
        
        if (! in_array($_operator, $this->_operators)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' 
                . ' Allowed operators: ' . print_r($this->_operators, TRUE));
            throw new Tinebase_Exception_UnexpectedValue("operator $_operator is not defined");
        }
        
        $this->_operator = $_operator;
    }
    
    /**
     * gets operator
     *
     * @return string
     */
    public function getOperator()
    {
        return $this->_operator;
    }
    
    /**
     * sets value
     *
     * @param string $_value
     */
    public function setValue($_value)
    {
        // cope with resolved records
        if (is_array($_value) && array_key_exists('id', $_value)) {
            $_value = $_value['id'];
        }
        
        //@todo validate value before setting it!
        $this->_value = $_value;
    }

    /**
     * sets id
     *
     * @param string $_id
     */
    public function setId($_id)
    {
        $this->_id = $_id;
    }
    
    /**
     * remove id of filter object
     */
    public function removeId()
    {
        $this->_id = NULL;
    }

    /**
     * set label
     *
     * @param string $_label
     */
    public function setLabel($_label)
    {
        $this->_label = $_label;
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
     * set implicit
     * @deprecated use isImplicit()
     *
     * @param boolean $_isImplicit
     */
    public function setIsImplicit($_isImplicit)
    {
        $this->_isImplicit = ($_isImplicit === TRUE);
    }
    
    /**
     * set implicit
     *
     * @param  boolean optional
     * @return boolean
     */
    public function isImplicit()
    {
        $value = (func_num_args() === 1) ? (bool) func_get_arg(0) : NULL;
        $currValue = $this->_isImplicit;
        if ($value !== NULL) {
            $this->_isImplicit = $value;
        }
        
        return $currValue;
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
        $tablename = (isset($this->_options['tablename'])) ? $this->_options['tablename'] : $_backend->getTableName();
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' '
            . 'Using tablename: ' . $tablename);
        
        return $_backend->getAdapter()->quoteIdentifier(
            $tablename . '.' . $this->_field
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
        
        if ($this->_isImplicit) {
            $result['implicit'] = TRUE;
        }

        if ($this->_id) {
            $result['id'] = $this->_id;
        }
        if ($this->_label) {
            $result['label'] = $this->_label;
        }
        
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
            $date = new Tinebase_DateTime();
            $result = $date->toString(Tinebase_Record_Abstract::ISO8601LONG);
        } elseif (isset($this->_options['timezone']) && $this->_options['timezone'] !== 'UTC') {
            $date = new Tinebase_DateTime($_string, $this->_options['timezone']);
            $date->setTimezone('UTC');
            $result = $date->toString(Tinebase_Record_Abstract::ISO8601LONG);
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
        $returnValue = str_replace(array('*', '_'),  $this->_dbCommand->setDatabaseJokerCharacters(), $value);
        
        // add wildcard to value according to operator
        $returnValue = str_replace('?', $returnValue, $action['wildcards']);
        
        return $returnValue;
    }
}
