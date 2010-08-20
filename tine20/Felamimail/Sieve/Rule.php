<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Sieve
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * class to store Sieve rule setting and to generate Sieve code for rule
 * 
 * @package     Felamimail
 * @subpackage  Sieve
 */
class Felamimail_Sieve_Rule
{
    /**
     * id of the rule
     * 
     * @var string
     */
    protected $_id;
    
    /**
     * array of Felamimail_Sieve_Rule_Condition
     * 
     * @var array
     */
    protected $_conditions = array();
    
    /**
     * the action to execute
     * 
     * @var Felamimail_Sieve_Rule_Action
     */
    protected $_action = NULL;
    
    /**
     * status of rule (enabled or disabled)
     * 
     * @var boolean
     */
    protected $_enabled = false;
    
    /**
     * add Felamimail_Sieve_Rule_Condition
     * 
     * @param   Felamimail_Sieve_Rule_Condition     $condition  the condition to match for
     * @return  Felamimail_Sieve_Rule
     */
    public function addCondition(Felamimail_Sieve_Rule_Condition $condition)
    {
        $this->_conditions[] = $condition;
        
        return $this;
    }
    
    /**
     * add Felamimail_Sieve_Rule_Action
     * 
     * @param   Felamimail_Sieve_Rule_Action    $action     the action to execute
     * @return  Felamimail_Sieve_Rule
     */
    public function setAction(Felamimail_Sieve_Rule_Action $action)
    {
        $this->_action = $action;
        
        return $this;
    }
    
    /**
     * set rule id
     * 
     * @param   integer     $id     the rule id
     * @return  Felamimail_Sieve_Rule
     */
    public function setId($id)
    {
        $this->_id = $id;
        
        return $this;
    }
    
    /**
     * get id of rule
     * 
     * @return string
     */
    public function getId()
    {
        return $this->_id;
    }
    
    /**
     * set status
     * 
     * @param   boolean $status     the status
     * @return Felamimail_Sieve_Rule
     */
    public function setEnabled($status)
    {
        $this->_enabled = (bool) $status;
        
        return $this;
    }
    
    /**
     * return if vacation is enabled
     * 
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->_enabled;
    }
    
    /**
     * return conditions as Sieve formated string
     * 
     * @return string
     */
    protected function _getSieveConditions()
    {
        $conditions = implode(', ', $this->_conditions);
        
        return $conditions;
    }
    
    /**
     * return action
     * 
     * @return Felamimail_Sieve_Rule_Action
     */
    protected function _getSieveAction()
    {
        return $this->_action;
    }
    
    /**
     * return the rule Sieve code
     * 
     * @return string
     */
    public function __toString() 
    {
        $rule = sprintf("%s (%s) {\r\n%s\r\n}\r\n",
            'allof',
            $this->_getSieveConditions(),
            $this->_getSieveAction()
        );
        
        return $rule;
    }
    
    /**
     * return values as array
     * 
     * @return array
     */
    public function toArray()
    {
        $conditions = array();
        foreach ($this->_conditions as $condition) {
            $conditions[] = $condition->toArray();
        }
        
        $action = $this->_action->toArray();
        
        return array(
            'conditions'            => $conditions,
            'action_type'           => $action['type'],
            'action_argument'       => $action['argument'],
            'enabled'               => (integer) $this->_enabled,
            'id'                    => $this->_id,
        );
    }
}
