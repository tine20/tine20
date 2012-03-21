<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        generalize this
 */

/**
 *  preference filter class
 * 
 * @package     Tinebase
 * @subpackage  Filter 
 */
class Tinebase_Model_PreferenceAccountFilter extends Tinebase_Model_Filter_Abstract 
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'equals',
    );
    
    /**
     * @var string
     */
    protected $_accountId = NULL;
    
    /**
     * @var string
     */
    protected $_accountType = NULL;
    
    /**
     * sets value
     *
     * @param mixed $_value
     */
    public function setValue($_value)
    {
        if (is_array($_value) && (isset($_value['accountId']) && isset($_value['accountType']))) {
            $this->_accountId = $_value['accountId'];
            $this->_accountType = $_value['accountType'];
        } else {
            throw new Tinebase_Exception_UnexpectedValue('Account must be an array with "accountId" and "accountType" properties');
        }
    }
    
    /**
     * appends sql to given select statement
     *
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        if ($this->_accountId === '0') {
            // get anyones preferences
            $field = $_backend->getAdapter()->quoteIdentifier(
                $_backend->getTableName() . '.account_type'
            );
            $_select->where(Tinebase_Core::getDb()->quoteInto($field . '= ?', Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE));
            
        } else {
            $conditions = array(
                array('condition' => Tinebase_Model_Filter_FilterGroup::CONDITION_AND, 'filters' => array(
                   array('field' => 'account_id',   'operator' => 'equals',  'value' => $this->_accountId),
                   array('field' => 'account_type', 'operator' => 'equals',  'value' => $this->_accountType)
                )),
                array('field' => 'account_type', 'operator' => 'equals',  'value' => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE),
                /*
                array('condition' => Tinebase_Model_Filter_FilterGroup::CONDITION_AND, 'filters' => array(
                   //array('field' => 'account_id',   'operator' => 'equals',  'value' => '0'),
                   array('field' => 'account_type', 'operator' => 'equals',  'value' => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE),
                )),
                */            
            );
            
            // add groups if accountType is user
            if ($this->_accountType === Tinebase_Acl_Rights::ACCOUNT_TYPE_USER) {
                $groups = Tinebase_Group::getInstance()->getGroupMemberships($this->_accountId);
                $conditions[] = 
                    array('condition' => Tinebase_Model_Filter_FilterGroup::CONDITION_AND, 'filters' => array(
                       array('field' => 'account_id',   'operator' => 'in',     'value' => $groups),
                       array('field' => 'account_type', 'operator' => 'equals', 'value' => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP),
                    ));
            }
    
            $filter = new Tinebase_Model_PreferenceFilter($conditions, Tinebase_Model_Filter_FilterGroup::CONDITION_OR);
            
            Tinebase_Backend_Sql_Filter_FilterGroup::appendFilters($_select, $filter, $_backend);
        }
    }
    
    /**
     * returns account id and type
     * @see tine20/Tinebase/Model/Filter/Tinebase_Model_Filter_Abstract::toArray()
     * 
     * @param  bool $_valueToJson resolve value for json api?
     * @return array
     */
    public function toArray($_valueToJson = false) 
    {
        $result = parent::toArray($_valueToJson);
        
        $result['value'] = array(
            'accountId' => $this->_accountId,
            'accountType' => $this->_accountType,
        );
        
        return $result;
    }
}
