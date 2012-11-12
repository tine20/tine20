<?php
/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Timeaccount filter Class
 * 
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
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'Timetracker_Model_TimeaccountFilter';
        
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'             => array('filter' => 'Tinebase_Model_Filter_Id', 'options' => array('modelName' => 'Timetracker_Model_Timeaccount')),
        'query'          => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('number', 'title'))),
        'title'          => array('filter' => 'Tinebase_Model_Filter_Text'),
        'number'         => array('filter' => 'Tinebase_Model_Filter_Text'),
        'description'    => array('filter' => 'Tinebase_Model_Filter_Text'),
        'status'         => array('filter' => 'Tinebase_Model_Filter_Text'),
        'deadline'       => array('filter' => 'Tinebase_Model_Filter_Text'),
        'tag'            => array('filter' => 'Tinebase_Model_Filter_Tag', 'options' => array(
            'idProperty' => 'timetracker_timeaccount.id',
            'applicationName' => 'Timetracker',
        )),
        'created_by'     => array('filter' => 'Tinebase_Model_Filter_User'),
        'last_modified_time'   => array('filter' => 'Tinebase_Model_Filter_Date'),
        'deleted_time'         => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'creation_time'        => array('filter' => 'Tinebase_Model_Filter_Date'),
        'last_modified_by'     => array('filter' => 'Tinebase_Model_Filter_User'),
        'is_open'              => array('filter' => 'Tinebase_Model_Filter_Bool'),
        'contract'    => array('filter' => 'Tinebase_Model_Filter_ExplicitRelatedRecord', 'options' => array(
            'controller' => 'Sales_Controller_Contract',
            'filtergroup' => 'Sales_Model_ContractFilter',
            'own_filtergroup' => 'Timetracker_Model_TimeaccountFilter',
            'own_controller' => 'Timetracker_Controller_Timeaccount',
            'related_model' => 'Sales_Model_Contract',
        ))
    );
    
    /**
     * @var array one of these grants must be met
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
     * appends custom/acl filters to a given select object
     * 
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     * @return void
     */
    public function appendFilterSql($_select, $_backend)
    {
        if (Timetracker_Controller_Timesheet::getInstance()->checkRight(Timetracker_Acl_Rights::MANAGE_TIMEACCOUNTS, FALSE, FALSE)) {
            return;
        }
        
        if (! $this->_isResolved) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . " Get all timeaccounts for user with required grants: " . print_r($this->_requiredGrants, TRUE));
            
            $result = Timetracker_Model_TimeaccountGrants::getTimeaccountsByAcl($this->_requiredGrants, TRUE);
            $this->_validTimeaccounts = $result;
            $this->_isResolved = TRUE;
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . " Got " . count($this->_validTimeaccounts) . ' valid timeaccounts');
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
