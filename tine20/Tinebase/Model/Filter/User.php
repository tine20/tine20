<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Tinebase_Model_Filter_User
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * 
 * filters for user id
 * 
 * adds a inGroup operator
 */
class Tinebase_Model_Filter_User extends Tinebase_Model_Filter_Text
{
    protected $_userOperator = NULL;
    protected $_userValue = NULL;
    
    /**
     * sets operator
     *
     * @param string $_operator
     */
    public function setOperator($_operator)
    {
        if ($_operator == 'inGroup') {
            $this->_userOperator = $_operator;
            $_operator = 'in';
        }
        
        parent::setOperator($_operator);
    }
    
    /**
     * sets value
     *
     * @param mixed $_value
     */
    public function setValue($_value)
    {
        // cope with resolved records
        if (is_array($_value) && array_key_exists('accountId', $_value)) {
            $_value = $_value['accountId'];
        }
        
        if ($this->_userOperator && $this->_userOperator == 'inGroup' && $this->_userValue) {
            $this->_userValue = $_value;
            $_value = Tinebase_Group::getInstance()->getGroupMembers($this->_userValue);
        }
        
        parent::setValue($_value);
    }
    
    /**
     * returns array with the filter settings of this filter
     *
     * @param  bool $_valueToJson resolve value for json api?
     * @return array
     */
    public function toArray($_valueToJson = false)
    {
        $result = parent::toArray($_valueToJson);
        
        if ($this->_userOperator && $this->_userOperator == 'inGroup') {
            $result['operator'] = $this->_userOperator;
            $result['value']    = $this->_userValue;
        }
        
        if ($_valueToJson == true ) {
            if ($this->_userOperator && $this->_userOperator == 'inGroup' && $this->_userValue) {
                $result['value'] = Tinebase_Group::getInstance()->getGroupById($this->_userValue)->toArray();
            } else {
                switch ($this->_operator) {
                    case 'equals':
                        $result['value'] = $result['value'] ? Tinebase_User::getInstance()->getUserById($this->_value)->toArray() : $result['value'];
                        break;
                    case 'in':
                        $result['value'] = array();
                        foreach ($this->_value as $userId) {
                            $result['value'][] = Tinebase_User::getInstance()->getUserById($userId)->toArray();
                        }
                        break;
                    default:
                        break;
                }
            }
        }
        return $result;
    }
}