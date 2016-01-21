<?php
/**
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Crm_Model_LeadQueryFilter
 * 
 * @package     Crm
 */
class Crm_Model_LeadQueryFilter extends Tinebase_Model_Filter_Abstract
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        'contains',
    );
    
    /**
     * appends sql to given select statement
     *
     * @param Zend_Db_Select                $_select
     * @param Tinebase_Backend_Sql_Abstract $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        if (empty($this->_value)) {
            // nothing to filter
            return;
        }
        
        $filterData = array(
            array('field' => 'lead_name',   'operator' => 'contains', 'value' => $this->_value),
            array('field' => 'description', 'operator' => 'contains', 'value' => $this->_value),
            
            // hack to suppress custom stuff
            array('field' => 'showClosed',  'operator' => 'equals',   'value' => TRUE),
        );
        
        $filter = new Crm_Model_LeadFilter($filterData, 'OR');
        $this->_advancedSearch($filter);
        
        Tinebase_Backend_Sql_Filter_FilterGroup::appendFilters($_select, $filter, $_backend);
    }

    /**
     * append relation filter
     *
     * @param Tinebase_Model_Filter_FilterGroup $filter
     */
    protected function _advancedSearch(Tinebase_Model_Filter_FilterGroup $filter)
    {
        $relationFilter = $this->_getAdvancedSearchFilter('Crm_Model_Lead', array(
            'Addressbook_Model_Contact',
            'Sales_Model_Product',
            'Tasks_Model_Task'
        ));

        if ($relationFilter) {
            $filter->addFilter($relationFilter);
        }
    }
}
