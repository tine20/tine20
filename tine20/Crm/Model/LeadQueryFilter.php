<?php
/**
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 * 
 * @todo        add tasks / products
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
    
    //, 'options' => array('fields' => array('lead_name', 'description'))
    
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
            
            // hack to supress custom stuff
            array('field' => 'showClosed',  'operator' => 'equals',   'value' => TRUE),
        );
        
        $filter = new Crm_Model_LeadFilter($filterData, 'OR');
        
        /*** also filter for related contacts ***/
        $contactFilter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'query',   'operator' => 'contains', 'value' => $this->_value),
        ));
        $contactIds = Addressbook_Controller_Contact::getInstance()->search($contactFilter, NULL, FALSE, TRUE);
        
        $relationFilter = new Tinebase_Model_RelationFilter(array(
            array('field' => 'own_model',     'operator' => 'equals', 'value' => 'Crm_Model_Lead'),
            array('field' => 'related_model', 'operator' => 'equals', 'value' => 'Addressbook_Model_Contact'),
            array('field' => 'related_id',    'operator' => 'in'    , 'value' => $contactIds)
        ));
        $leadIds = Tinebase_Relations::getInstance()->search($relationFilter, NULL)->own_id;
        
        $filter->addFilter(new Tinebase_Model_Filter_Id('id', 'in', $leadIds));
        
        
        Tinebase_Backend_Sql_Filter_FilterGroup::appendFilters($_select, $filter, $_backend);
    }
}