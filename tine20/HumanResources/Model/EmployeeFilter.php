<?php
/**
 * Tine 2.0
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * employee filter Class
 * @package     HumanResources
 */
class HumanResources_Model_EmployeeFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'HumanResources';

    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'HumanResources_Model_Employee';

    /**
     * @var string class name of this filter group
     */
    protected $_className = 'HumanResources_Model_EmployeeFilter';
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'         => array('filter' => 'Tinebase_Model_Filter_Id', 'options' => array('modelName' => 'HumanResources_Model_Employee')),
        'account_id' => array('filter' => 'Tinebase_Model_Filter_User'),
        'number'     => array('filter' => 'Tinebase_Model_Filter_Int'),
        'query'      => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('n_fn', 'bank_account_holder', 'email'))),

        'n_given'    => array('filter' => 'Tinebase_Model_Filter_Text'),
        'n_family'   => array('filter' => 'Tinebase_Model_Filter_Text'),
        'title'      => array('filter' => 'Tinebase_Model_Filter_Text'),
        'salutation' => array('filter' => 'Tinebase_Model_Filter_Text'),
        'profession' => array('filter' => 'Tinebase_Model_Filter_Text'),
        'health_insurance' => array('filter' => 'Tinebase_Model_Filter_Text'),
        'employment_begin'  => array('filter' => 'Tinebase_Model_Filter_Date'),
        'employment_end'    => array('filter' => 'Tinebase_Model_Filter_Date'),
            
        'last_modified_time'   => array('filter' => 'Tinebase_Model_Filter_Date'),
        'deleted_time'         => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'creation_time'        => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'last_modified_by'     => array('filter' => 'Tinebase_Model_Filter_User'),
        'created_by'           => array('filter' => 'Tinebase_Model_Filter_User'),
        'division_id' => array('filter' => 'Tinebase_Model_Filter_ForeignId',
            'options' => array(
                'filtergroup'       => 'Sales_Model_DivisionFilter',
                'controller'        => 'Sales_Controller_Division'
            )
        ),
        
        'is_employed' => array('filter' => 'HumanResources_Model_EmployeeEmployedFilter')
    );
}
