<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     New BSD License
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * This object appends all contained filters at once concated by the concatenation
 * operator of the filtergroup, in order to archive nested filters.
 * 
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Backend_Sql_Filter_GroupSelect
{
    /**
     * @var Zend_Db_Select
     */
    protected $_select = NULL;
    
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_adapter = NULL;
    
    /**
     * @var array
     */
     protected $_parts = array();
     
    /**
     * save an instance of the select object
     *
     * @param  Zend_Db_Select|Tinebase_Backend_Sql_Filter_GroupSelect $_select
     * @return void
     */
    public function __construct($_select)
    {
        $this->_select = $_select;
        $this->_adapter = Tinebase_Core::getDb();
    }
    
    /**
     * route all function calls besides the fuctions declared here directly
     * to the original select object
     *
     * @param  string $_name
     * @param  array  $_arguments
     * @return  mixed
     */
    public function __call($_name, $_arguments)
    {
        return call_user_func_array(array($this->_select, $_name), $_arguments);
    }
    
    /**
     * Adds a WHERE condition to the query by AND.
     * 
     * @param string   $cond  The WHERE condition.
     * @param string   $value OPTIONAL A single value to quote into the condition.
     * @param constant $type  OPTIONAL The type of the given value
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    public function where($cond, $value = null, $type = null)
    {
        $this->_parts[Zend_Db_Select::WHERE][] = $this->_where($cond, $value, $type, true);
        
        return $this;
    }
    
    /**
     * Adds a WHERE condition to the query by OR.
     *
     * @param string   $cond  The WHERE condition.
     * @param string   $value OPTIONAL A single value to quote into the condition.
     * @param constant $type  OPTIONAL The type of the given value
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    public function orWhere($cond, $value = null, $type = null)
    {
        $this->_parts[Zend_Db_Select::WHERE][] = $this->_where($cond, $value, $type, false);
        
        return $this;
    }
    
    /**
     * Internal function for creating the where clause
     *
     * @param string   $condition
     * @param string   $value  optional
     * @param string   $type   optional
     * @param boolean  $bool  true = AND, false = OR
     * @return string  clause
     */
    protected function _where($condition, $value = null, $type = null, $bool = true)
    {
        if ($value !== null) {
            $condition = $this->_adapter->quoteInto($condition, $value, $type);
        }
        
        $cond = "";
        if (! empty($this->_parts[Zend_Db_Select::WHERE])) {
            if ($bool === true) {
                $cond = Zend_Db_Select::SQL_AND . ' ';
            } else {
                $cond = Zend_Db_Select::SQL_OR . ' ';
            }
        }
        
        return $cond . "($condition)";
    }
    
    /**
     * appends where buffer at once to original select obj.
     * 
     * @param string $_concatenationCondition AND|OR
     */
    public function appendWhere($_concatenationCondition = Zend_Db_Select::SQL_AND)
    {
        if (! empty($this->_parts[Zend_Db_Select::WHERE])) {
            $method = $_concatenationCondition == Zend_Db_Select::SQL_OR ? 'orWhere' : 'where';
            
            $this->_select->$method(implode(' ', $this->_parts[Zend_Db_Select::WHERE]));
        }
    }
}
