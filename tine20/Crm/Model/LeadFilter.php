<?php
/**
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
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
        'id'             => array('filter' => 'Tinebase_Model_Filter_Id'),
        'query'          => array('filter' => 'Crm_Model_LeadQueryFilter'),
        'description'    => array('filter' => 'Tinebase_Model_Filter_Text'),
        'lead_name'      => array('filter' => 'Tinebase_Model_Filter_Text'),
        'tag'            => array('filter' => 'Tinebase_Model_Filter_Tag', 'options' => array('idProperty' => 'metacrm_lead.id')),
        'probability'    => array('filter' => 'Tinebase_Model_Filter_Int'),
        'turnover'       => array('filter' => 'Tinebase_Model_Filter_Int'),
        'leadstate_id'   => array('filter' => 'Tinebase_Model_Filter_Int'),
        'container_id'   => array('filter' => 'Tinebase_Model_Filter_Container', 'options' => array('applicationName' => 'Crm')),
        'showClosed'     => array('filter' => 'Crm_Model_LeadClosedFilter'),
        
    // relation filters
        'contact'        => array('filter' => 'Tinebase_Model_Filter_Relation', 'options' => array(
            'related_model'     => 'Addressbook_Model_Contact',
            'related_filter'    => 'Addressbook_Model_ContactFilter'
        )),
        'product'        => array('filter' => 'Tinebase_Model_Filter_Relation', 'options' => array(
            'related_model'     => 'Sales_Model_Product',
            'related_filter'    => 'Sales_Model_ProductFilter'
        )),
    );
}
