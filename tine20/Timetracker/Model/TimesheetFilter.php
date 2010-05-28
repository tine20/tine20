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
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Timetracker_Model_Timesheet';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'             => array('filter' => 'Tinebase_Model_Filter_Id'),
        'query'          => array('filter' => 'Tinebase_Model_Filter_Query',        'options' => array('fields' => array('description'))),
        'description'    => array('filter' => 'Tinebase_Model_Filter_Text'),
        'timeaccount_id' => array('filter' => 'Tinebase_Model_Filter_ForeignId', 
            'options' => array(
                'filtergroup'       => 'Timetracker_Model_TimeaccountFilter', 
                'controller'        => 'Timetracker_Controller_Timeaccount', 
                'useTimesheetAcl'   => TRUE,
                'showClosed'        => TRUE
            )
        ),
        'account_id'     => array('filter' => 'Tinebase_Model_Filter_User'),
        'start_date'     => array('filter' => 'Tinebase_Model_Filter_Date'),
        'is_billable'    => array('filter' => 'Tinebase_Model_Filter_Bool',         'options' => array('fields' => array('timetracker_timesheet.is_billable','ta.is_billable'))),
        //'is_cleared'     => array('filter' => 'Tinebase_Model_Filter_Bool'),
        'is_cleared'     => array('custom' => TRUE),
        'tag'            => array('filter' => 'Tinebase_Model_Filter_Tag',          'options' => array('idProperty' => 'timetracker_timesheet.id')),
        'customfield'    => array('filter' => 'Tinebase_Model_Filter_CustomField',  'options' => array('idProperty' => 'timetracker_timesheet.id')),
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
     */
    public function appendFilterSql($_select, $_backend)
    {
        $this->_appendAclSqlFilter($_select);

        $db = $_backend->getAdapter();
        foreach ($this->_customData as $customData) {
            $value = $customData['value'];
            if ($customData['field'] == 'is_cleared') {
                $opStatus = $value ? '=' : '<>';
                $op = $value ? ' OR ' : ' AND ';
                $_select->where(
                    $db->quoteInto($customData['field']  . ' = ?', $value) . $op .
                    $db->quoteInto('ta.status' . $opStatus . ' ?', 'billed')
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
        if (Timetracker_Controller_Timesheet::getInstance()->checkRight(Timetracker_Acl_Rights::MANAGE_TIMEACCOUNTS, FALSE, FALSE)) {
            return;
        }
        
        if (! $this->_isResolved) {
            // get all timeaccounts user has required grants for
            $result = array();
            foreach ($this->_requiredGrants as $grant) {
                //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' value:' . $this->_value);
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
