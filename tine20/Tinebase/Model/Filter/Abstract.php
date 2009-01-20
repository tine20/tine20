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
     * appeds sql to given select statement
     *
     * @param Zend_Db_Select $_select
     */
    abstract public function appendFilterSql($_select);
    
}