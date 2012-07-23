<?php
/**
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Leads Filter Class
 * @package Crm
 */
class Crm_Model_LeadFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'Crm_Model_LeadFilter';
    
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Crm';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Crm_Model_Lead';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'                    => array('filter' => 'Tinebase_Model_Filter_Id'),
        'query'                 => array('filter' => 'Crm_Model_LeadQueryFilter'),
        'description'           => array('filter' => 'Tinebase_Model_Filter_Text'),
        'lead_name'             => array('filter' => 'Tinebase_Model_Filter_Text'),
        'tag'                   => array('filter' => 'Tinebase_Model_Filter_Tag', 'options' => array(
            'idProperty' => 'metacrm_lead.id',
            'applicationName' => 'Crm',
        )),
        'probability'           => array('filter' => 'Tinebase_Model_Filter_Int'),
        'turnover'              => array('filter' => 'Tinebase_Model_Filter_Int'),
        'leadstate_id'          => array('filter' => 'Tinebase_Model_Filter_Int'),
        'container_id'          => array('filter' => 'Tinebase_Model_Filter_Container', 'options' => array('applicationName' => 'Crm')),
        'showClosed'            => array('filter' => 'Crm_Model_LeadClosedFilter'),
        'last_modified_by'      => array('filter' => 'Tinebase_Model_Filter_User'),
        'last_modified_time'    => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'deleted_time'          => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'created_by'            => array('filter' => 'Tinebase_Model_Filter_User'),
        'creation_time'         => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'customfield'           => array('filter' => 'Tinebase_Model_Filter_CustomField', 'options' => array('idProperty' => 'metacrm_lead.id')),
        
    // relation filters
        'contact'        => array('filter' => 'Tinebase_Model_Filter_Relation', 'options' => array(
            'related_model'     => 'Addressbook_Model_Contact',
            'filtergroup'    => 'Addressbook_Model_ContactFilter'
        )),
        'product'        => array('filter' => 'Tinebase_Model_Filter_Relation', 'options' => array(
            'related_model'     => 'Sales_Model_Product',
            'filtergroup'    => 'Sales_Model_ProductFilter'
        )),
        'task'           => array('filter' => 'Tinebase_Model_Filter_Relation', 'options' => array(
            'related_model'     => 'Tasks_Model_Task',
            'filtergroup'    => 'Tasks_Model_TaskFilter'
        )),
    );
}
