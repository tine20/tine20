<?php

/**
 * foreign id filter
 * 
 * Expects:
 * - a filtergroup in options->filtergroup
 * - a controller  in options->controller
 * 
 * Hands over all options to filtergroup
 * Hands over AclFilter functions to filtergroup
 *
 */
class Tinebase_Model_Filter_ForeignId extends Tinebase_Model_Filter_Abstract
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
     * creates corresponding filtergroup
     *
     * @param array $_value
     */
    public function setValue($_value) {
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_value, true));
        
        $this->_filterGroup = new $this->_options['filtergroup']((array)$_value, $this->_operator, $this->_options);
        $this->_controller = call_user_func($this->_options['controller'] . '::getInstance');
        
        $this->_foreignIds = NULL;
    }
    
    /**
     * set options 
     *
     * @param array $_options
     */
    protected function _setOptions(array $_options)
    {
        if (! array_key_exists('controller', $_options) || ! array_key_exists('filtergroup', $_options)) {
            throw new Tinebase_Exception_InvalidArgument('a controller and a filtergroup must be specified in the options');
        }
        $this->_options = $_options;
    }
    
    /**
     * appends sql to given select statement
     *
     * @param Zend_Db_Select                $_select
     * @param Tinebase_Backend_Sql_Abstract $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        $db = Tinebase_Core::getDb();
        
        if (! is_array($this->_foreignIds)) {
            $this->_foreignIds = $this->_controller->search($this->_filterGroup, new Tinebase_Model_Pagination(), FALSE, TRUE);
        }
        
        $_select->where($this->_getQuotedFieldName($_backend) . ' IN (?)', empty($this->_foreignIds) ? array('') : $this->_foreignIds);
    }
    
    public function setRequiredGrants(array $_grants)
    {
        $this->_filterGroup->setRequiredGrants($_grants);
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
            'value'     => $this->_filterGroup->toArray($_valueToJson)
        );
        
        return $result;
    }    
}