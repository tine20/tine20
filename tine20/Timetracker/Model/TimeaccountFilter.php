<?php
/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:TimeaccountFilter.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 *
 * @todo        created_by filter should be replaced by a 'responsible/organizer' filter like in tasks
 */

/**
 * timeaccount filter Class
 * @package     Timetracker
 */
class Timetracker_Model_TimeaccountFilter extends Tinebase_Model_Filter_FilterGroup implements Tinebase_Model_Filter_AclFilter 
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Timetracker';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Timetracker_Model_Timeaccount';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'             => array('filter' => 'Tinebase_Model_Filter_Id'),
        'query'          => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('number', 'title'))),
        'title'          => array('filter' => 'Tinebase_Model_Filter_Text'),
        'number'         => array('filter' => 'Tinebase_Model_Filter_Text'),
        'description'    => array('filter' => 'Tinebase_Model_Filter_Text'),
        'status'         => array('filter' => 'Tinebase_Model_Filter_Text'),
        'tag'            => array('filter' => 'Tinebase_Model_Filter_Tag', 'options' => array('idProperty' => 'timetracker_timeaccount.id')),
        'created_by'     => array('filter' => 'Tinebase_Model_Filter_User'),
        'showClosed'     => array('custom' => true),
        'isBookable'     => array('custom' => true),
    );
    
    /**
     * @var array one of theese grants must be met
     */
    protected $_requiredGrants = array(
        Timetracker_Model_TimeaccountGrants::BOOK_OWN
    );
    
    /**
     * is acl filter resolved?
     *
     * @var boolean
     */
    protected $_isResolved = FALSE;
    
    /**
     * set options
     *
     * @param array $_options
     */
    protected function _setOptions(array $_options)
    {
        $_options['useTimesheetAcl']    = array_key_exists('useTimesheetAcl', $_options) ? $_options['useTimesheetAcl'] : FALSE;
        $_options['showClosed']         = array_key_exists('showClosed', $_options)      ? $_options['showClosed']      : FALSE;
        parent::_setOptions($_options);
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
     * appends custom filters to a given select object
     * 
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     * @return void
     */
    public function appendFilterSql($_select, $_backend)
    {
        // ensure acl policies
        $this->_appendAclSqlFilter($_select);
        
        // manage show closed
        $this->_appendShowClosedSql($_select);
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $_select->__toString());
    }
    
    /**
     * append show closed filter
     *
     * @param Zend_Db_Select $_select
     */
    protected function _appendShowClosedSql($_select)
    {
        $showClosed = false;
        foreach ($this->_customData as $customData) {
            if ($customData['field'] == 'showClosed' && $customData['value'] == true) {
                $showClosed = true;
            }
        }
        if($showClosed || $this->_options['showClosed']){
            // nothing to filter
        } else {
            $_select->where(Tinebase_Core::getDb()->quoteIdentifier('is_open') . ' = 1');
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
                $result = array_merge($result, Timetracker_Model_TimeaccountGrants::getTimeaccountsByAcl($grant, TRUE));
            }
            //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' value:' . $result);
            $this->_validTimeaccounts = array_unique($result);
            $this->_isResolved = TRUE;
        }
        
        $db = Tinebase_Core::getDb();
        
        $field = $db->quoteIdentifier('id');
        $where = $db->quoteInto("$field IN (?)", empty($this->_validTimeaccounts) ? array('') : $this->_validTimeaccounts);
        
        
        $_select->where($where);
    }
    
    /**
     * returns array with the filter settings of this filter group 
     *
     * @param  bool $_valueToJson resolve value for json api?
     * @return array
     */
    public function toArray($_valueToJson = false)
    {
        $result = parent::toArray($_valueToJson);
        
        foreach ($result as &$filterData) {
            if ($filterData['field'] == 'id' && $_valueToJson == true && ! empty($filterData['value'])) {
                //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' value:' . print_r($filterData['value'], true));
                try {
                    $filterData['value'] = Timetracker_Controller_Timeaccount::getInstance()->get($filterData['value'])->toArray();
                } catch (Tinebase_Exception_NotFound $nfe) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->INFO(__METHOD__ . '::' . __LINE__ . " could not find and resolve timeaccount {$filterData['value']}");
                }
            }
        }
        
        return $result;
    }
}
