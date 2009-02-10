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
 * Tinebase_Model_Filter_FilterGroup
 * 
 * @package     Timetracker
 * @subpackage  Filter
 */
class Timetracker_Model_TimesheetFilter extends Tinebase_Model_Filter_FilterGroup implements Tinebase_Model_Filter_AclFilter 
{
    
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Timetracker';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'ts.id'          => array('filter' => 'Tinebase_Model_Filter_Id'),
        'query'          => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('ts.description'))),
        'ts.description' => array('filter' => 'Tinebase_Model_Filter_Text'),
        'timeaccount_id' => array('filter' => 'Tinebase_Model_Filter_ForeignId', 'options' => array('filtergroup' => 'Timetracker_Model_TimeaccountFilter', 'controller' => 'Timetracker_Controller_Timeaccount', 'useTimesheetAcl' => TRUE)),
        'account_id'     => array('filter' => 'Tinebase_Model_Filter_Int'),
        'start_date'     => array('filter' => 'Tinebase_Model_Filter_Date'),
        'is_billable'    => array('filter' => 'Tinebase_Model_Filter_Bool'),
        'is_cleared'     => array('filter' => 'Tinebase_Model_Filter_Bool'),
        'tag'            => array('filter' => 'Tinebase_Model_Filter_Tag')
    );
    
    /**
     * is resolved
     *
     * @var boolean
     */
    protected $_isResolved = FALSE;
    
    /**
     * @var array one of theese grants must be met
     */
    protected $_requiredGrants = array(
        Timetracker_Model_TimeaccountGrants::BOOK_OWN
    );
    
/**
     * returns acl filter of this group or NULL if not set
     *
     * @return Tinebase_Model_Filter_AclFilter
     */
    public function getAclFilter()
    {
        return $this;
    }
    
    /**
     * sets the grants this filter needs to assure
     *
     * @param array $_grants
     */
    public function setRequiredGrants(array $_grants)
    {
        $this->_requiredGrants = $_grants;
        $this->_isResolved = FALSE;
    }
    
    /**
     * appends sql to given select statement
     *
     * @param Zend_Db_Select $_select
     */
    public function appendFilterSql($_select)
    {
        $this->_appendAclSqlFilter($_select);
        
        parent::appendFilterSql($_select);
    }
    
    /**
     * append acl filter
     *
     * @param Zend_Db_Select $_select
     */
    protected function _appendAclSqlFilter($_select)
    {
        if (Timetracker_Controller_Timesheet::getInstance()->checkRight(Timetracker_Acl_Rights::MANAGE_TIMEACCOUNTS, FALSE, FALSE)) {
            return;
        }
        
        if (! $this->_isResolved) {
            // get all timeaccounts user has required grants for
            $result = array();
            foreach ($this->_requiredGrants as $grant) {
                //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' value:' . $this->_value);
                if ($grant != Timetracker_Model_TimeaccountGrants::BOOK_OWN) {
                    $result = array_merge($result, Timetracker_Model_TimeaccountGrants::getTimeaccountsByAcl($grant, TRUE));
                }
            }
            $this->_validTimeaccounts = array_unique($result);
            $this->_isResolved = TRUE;
        }
        
        $db = Tinebase_Core::getDb();
        
        $field = $db->quoteIdentifier('timeaccount_id');
        $where = $db->quoteInto("$field IN (?)", empty($this->_validTimeaccounts) ? array('') : $this->_validTimeaccounts);
        
        // get timeaccounts with BOOK_OWN right (get only if no manual filter is set)
        $bookOwnTS = Timetracker_Model_TimeaccountGrants::getTimeaccountsByAcl(Timetracker_Model_TimeaccountGrants::BOOK_OWN, TRUE);
        if (! empty($bookOwnTS)) {
            $where .= ' OR (' . $db->quoteInto($field . ' IN (?)', $bookOwnTS)
                . ' AND ' . $db->quoteInto($db->quoteIdentifier('account_id'). ' = ?', Tinebase_Core::getUser()->getId()) .')';
        }
                
        $_select->where($where);
    }
}
