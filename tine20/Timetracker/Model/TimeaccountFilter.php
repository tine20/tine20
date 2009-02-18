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
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'             => array('filter' => 'Tinebase_Model_Filter_Id'),
        'query'          => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('number', 'title'))),
        'title'          => array('filter' => 'Tinebase_Model_Filter_Text'),
        'number'         => array('filter' => 'Tinebase_Model_Filter_Text'),
        'description'    => array('filter' => 'Tinebase_Model_Filter_Text'),
        'status'         => array('filter' => 'Tinebase_Model_Filter_Text'),
        'tag'            => array('filter' => 'Tinebase_Model_Filter_Tag'),
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
        $_options['useTimesheetAcl'] = array_key_exists('useTimesheetAcl', $_options) ? $_options['useTimesheetAcl'] : FALSE;
        parent::_setOptions($_options);
    }
    
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
     * appends current filters to a given select object
     * 
     * @param  Zend_Db_Select
     * @return void
     */
    public function appendFilterSql($_select)
    {
        // ensure acl policies
        $this->_appendAclSqlFilter($_select);
        
        // manage show closed
        $this->_appendShowClosedSql($_select);
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $_select->__toString());
        
        parent::appendFilterSql($_select);
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
        if($showClosed){
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
                //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' value:' . $this->_value);
                $result = array_merge($result, Timetracker_Model_TimeaccountGrants::getTimeaccountsByAcl($grant, TRUE));
            }
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' value:' . $result);
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
            if ($filterData['field'] == 'id' && $_valueToJson == true) {
                //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' value:' . print_r($filterData['value'], true));
                $filterData['value'] = Timetracker_Controller_Timeaccount::getInstance()->get($filterData['value'])->toArray();
            }
        }
        
        return $result;
    }
}
