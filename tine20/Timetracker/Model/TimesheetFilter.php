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
class Timetracker_Model_TimesheetFilter extends Tinebase_Model_Filter_FilterGroup
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
        'query'          => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('description'))),
        'timeaccount_id' => array('filter' => 'Timetracker_Model_TimeaccountIdFilter', 'options' => array('useTimesheetAcl' => TRUE)),
        'account_id'     => array('filter' => 'Tinebase_Model_Filter_User'),
        'start_date'     => array('filter' => 'Tinebase_Model_Filter_Date'),
        'is_billable'    => array('filter' => 'Tinebase_Model_Filter_Bool'),
        'is_cleared'     => array('filter' => 'Tinebase_Model_Filter_Bool'),
        'tag'            => array('filter' => 'Tinebase_Model_Filter_Tag')
    );
    
    /**
     * appends sql to given select statement
     *
     * @param Zend_Db_Select $_select
     */
    public function appendFilterSql($_select)
    {
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . 'appending TimesheetfilterSql');
        
        parent::appendFilterSql($_select);
    }
}