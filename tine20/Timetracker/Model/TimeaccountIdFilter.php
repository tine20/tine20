<?php
/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:TimesheetFilter.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 *
 */

/**
 * timeaccount_id filter Class
 * @package     Timetracker
 * 
 * @todo move orWhere 'owner' to TS filter
 */
class Timetracker_Model_TimeaccountIdFilter extends Tinebase_Model_Filter_Abstract implements Tinebase_Model_Filter_AclFilter 
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'equals',
        1 => 'in',
        2 => 'all'
    );
    
    /**
     * @var array one of theese grants must be met
     */
    protected $_requiredGrants = array(
        Timetracker_Model_TimeaccountGrants::BOOK_OWN
    );
    
    /**
     * is resolved
     *
     * @var boolean
     */
    protected $_isResolved = FALSE;
    
    /**
     * sets the grants this filter needs to assure
     *
     * @param array $_grants
     */
    public function setRequiredGrants(array $_grants)
    {
        // as grants are or'd, book own must not be set when this filter is used
        // as timesheet filter
        if ($this->_options['useTimesheetAcl'] == TRUE) {
            $bookOwnIdx = array_search(Timetracker_Model_TimeaccountGrants::BOOK_OWN, $_grants);
            if ($bookOwnIdx !== FALSE) {
                unset ($_grants[$bookOwnIdx]);
            }
        }
        
        $this->_requiredGrants = $_grants;
        $this->_isResolved = FALSE;
    }
    
    /**
     * set operator
     *
     * @param string $_operator
     */
    public function setOperator($_operator)
    {
        parent::setOperator($_operator);
        $this->_isResolved = FALSE;
    }
    
    /**
     * set value
     *
     * @param mixed $_value
     */
    public function setValue($_value)
    {
        parent::setValue($_value);
        $this->_isResolved = FALSE;
    }
    
    /**
     * set options
     *
     * @param array $_options
     */
    protected function _setOptions(array $_options)
    {
        $_options['useTimesheetAcl'] = array_key_exists('useTimesheetAcl', $_options) ? $_options['useTimesheetAcl'] : FALSE;
        parent::_setOptions($_options);
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
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' op:' . $this->_operator);
        
        // don't filter by timeaccount_id if the user has the MANAGE_TIMEACCOUNTS right and don't want to filter by specific id's
        if (Timetracker_Controller_Timesheet::getInstance()->checkRight(Timetracker_Acl_Rights::MANAGE_TIMEACCOUNTS, FALSE, FALSE) 
                && $this->_operator == 'all') {
            return;
        }
        
        $this->_resolve();
        
        $db = Tinebase_Core::getDb();
        $field = $db->quoteIdentifier($this->_field);
        $where = $db->quoteInto("$field IN (?)", empty($this->_value) ? array('') : $this->_value);
        
        if ($this->_options['useTimesheetAcl'] == TRUE) {
            // get timeaccounts with BOOK_OWN right (get only if no manual filter is set)
            $bookOwnTS = Timetracker_Model_TimeaccountGrants::getTimeaccountsByAcl(Timetracker_Model_TimeaccountGrants::BOOK_OWN, TRUE);
            if (! empty($bookOwnTS)) {
                $where .= ' OR (' . $db->quoteInto($field . ' IN (?)', $bookOwnTS)
                    . ' AND ' . $db->quoteInto($db->quoteIdentifier('account_id'). ' = ?', Tinebase_Core::getUser()->getId()) .')';
            }
                
        } 
        
        $_select->where($where);
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
        
        if ($_valueToJson == true) {
            $result['value'] = Timetracker_Controller_Timeaccount::getInstance()->get($result['value'])->toArray();
        }
        
        return $result;
    }
    
    /**
     * resolve timeaccount ids
     *
     */
    protected function _resolve()
    {
        if ($this->_isResolved) {
            //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' already resolved');
            return;
        }

        $this->_value = (array)$this->_value;
        
        // we only need to resolve the timaccount ids if user has no MANAGE_TIMEACCOUNTS grant
        if (! Timetracker_Controller_Timesheet::getInstance()->checkRight(Timetracker_Acl_Rights::MANAGE_TIMEACCOUNTS, FALSE, FALSE)) {
            // get all timeaccounts user has required grants for
            $result = array();
            foreach ($this->_requiredGrants as $grant) {
                //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' value:' . $this->_value);
                $result = array_merge($result, Timetracker_Model_TimeaccountGrants::getTimeaccountsByAcl($grant, TRUE));
            }
            $result = array_unique($result);
            
            //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($result, TRUE));
            
            // finally compute timeaccount_ids which match the filter and required grants
            switch ($this->_operator) {
                case 'equals':
                case 'in':
                    $this->_value = array_intersect($this->_value, $result);
                    break;
                case 'all':
                    $this->_value = $result;
                    break;
            }
        }
        
        $this->_isResolved = TRUE;
    }
}
