<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * Calendar Acl Filter
 * 
 * Manages calnedar grant for search actions
 * 
 * 
 * Assuring event grants is a two stage process for search operations.
 *  1. limiting the query (mixture of grants and filter)
 *  2. transform event set (all events user has only free/busy grant for need to be cleaned)
 * 
 * NOTE: The effective grants of the events get dynamically appended to the
 *       event rows in SQL (at the moment by the sql backend class.)
 *       As such there is no need to compute the effective grants in stage 2
 * 
 * NOTE: If the required grant is other than GRANT_READ, we skip all free/busy 
 *       grants. With this, also stage 2 is obsolete!
 * 
 * @package Calendar
 */
class Calendar_Model_EventAclFilter extends Tinebase_Model_Filter_FilterGroup implements Tinebase_Model_Filter_AclFilter
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Calendar';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'container_id'   => array('filter' => 'Tinebase_Model_Container', 'options' => array('applicationName' => 'Calendar')),
    );
    
    /**
     * @var array one of theese grants must be met
     */
    protected $_requiredGrants = array(
        Tinebase_Model_Container::GRANT_READ
    );
    
    /**
     * is acl filter resolved?
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
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $_select->__toString());
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
                //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' value:' . print_r($filterData['value'], true));
                $filterData['value'] = Timetracker_Controller_Timeaccount::getInstance()->get($filterData['value'])->toArray();
            }
        }
        
        return $result;
    }
}