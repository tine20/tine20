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
 * Tinebase_Model_Filter_FilterGroup
 * 
 * @package     Timetracker
 * @subpackage  Filter
 * 
 * @todo refactor this to comply to default acl filtering (@see Tinebase_Controller_Record_Abstract::checkFilterACL)
 */
class Timetracker_Model_TimesheetFilter extends Tinebase_Model_Filter_FilterGroup implements Tinebase_Model_Filter_AclFilter 
{
    /**
     * if this is set, the filtergroup will be created using the configurationObject for this model
     *
     * @var string
     */
    protected $_configuredModel = 'Timetracker_Model_Timesheet';
    
    /**
     * is resolved
     *
     * @var boolean
     */
    protected $_isResolved = FALSE;
    
    /**
     * @var array one of these grants must be met
     */
    protected $_requiredGrants = array(
        Timetracker_Model_TimeaccountGrants::BOOK_OWN,
        // NOTE: this is needed to make the search for other users timesheets work
        Timetracker_Model_TimeaccountGrants::VIEW_ALL,
    );
    
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
     * appends custom filters to a given select object
     * 
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     * @return void
     * 
     * @todo replace custom filter!
     */
    public function appendFilterSql($_select, $_backend)
    {
        if (count($this->_requiredGrants)) {
            $this->_appendAclSqlFilter($_select);
        }

        $db = $_backend->getAdapter();
        foreach ($this->_customData as $customData) {
            $value = $customData['value'];
            if ($customData['field'] == 'is_cleared') {
                $_select->joinLeft(
                    /* table  */ array('ta' => $db->table_prefix . 'timetracker_timeaccount'), 
                    /* on     */ $db->quoteIdentifier('ta.id') . ' = ' . $db->quoteIdentifier('timetracker_timesheet.timeaccount_id'),
                    /* select */ array()
                );
                
                $opStatus = $value ? '=' : '<>';
                $op = $value ? ' OR ' : ' AND ';
                $_select->where(
                    $db->quoteInto($customData['field']  . ' = ?', $value) . $op .
                    $db->quoteInto('ta.status' . $opStatus . ' ? ', 'billed')
                );
            }
        }
    }
    
    /**
     * append acl filter
     *
     * @param Zend_Db_Select $_select
     */
    protected function _appendAclSqlFilter($_select)
    {
        if ($this->getCondition() === self::CONDITION_OR) {
            // ACL filter with OR condition is useless and delivers wrong results!
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' '
                . ' No ACL filter for OR condition!');
            return;
        }
        
        if (Timetracker_Controller_Timesheet::getInstance()->checkRight(Timetracker_Acl_Rights::MANAGE_TIMEACCOUNTS, FALSE, FALSE)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' '
                . ' No ACL filter for MANAGE_TIMEACCOUNTS right!');
            return;
        }
        
        if (! $this->_isResolved) {
            // get all timeaccounts user has required grants for
            $result = array();
            foreach ($this->_requiredGrants as $grant) {
                if ($grant != Timetracker_Model_TimeaccountGrants::BOOK_OWN) {
                    $result = array_merge($result, Timetracker_Controller_Timeaccount::getInstance()->getRecordsByAcl($grant, TRUE));
                }
            }
            $this->_validTimeaccounts = array_unique($result);
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                . ' valid timeaccounts' . print_r($this->_validTimeaccounts, TRUE) . ' for required grants: ' . print_r($this->_requiredGrants, TRUE));
            $this->_isResolved = TRUE;
        }
        
        $db = Tinebase_Core::getDb();
        
        $field = $db->quoteIdentifier('timeaccount_id');
        $where = $db->quoteInto("$field IN (?)", empty($this->_validTimeaccounts) ? array('') : $this->_validTimeaccounts);
        
        // get timeaccounts with *_OWN right
        $bookOwnTS = [];
        foreach ([
                    Timetracker_Model_TimeaccountGrants::BOOK_OWN,
                    Timetracker_Model_TimeaccountGrants::READ_OWN,
                    Timetracker_Model_TimeaccountGrants::REQUEST_OWN,
                ] as $grant) {
            $bookOwnTS = array_merge($bookOwnTS, Timetracker_Controller_Timeaccount::getInstance()->getRecordsByAcl($grant, true));
        }
        $bookOwnTS = array_unique($bookOwnTS);
        if (! empty($bookOwnTS)) {
            $where .= ' OR (' . $db->quoteInto($field . ' IN (?)', $bookOwnTS)
                . ' AND ' . $db->quoteInto($db->quoteIdentifier('account_id'). ' = ?', Tinebase_Core::getUser()->getId()) .')';
        }
        
        $_select->where($where);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' ACL filter: ' . $where);
    }
}
