<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * employee filter Class
 * @package     HumanResources
 */
class HumanResources_Model_FreeTimeFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'HumanResources';

    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'HumanResources_Model_FreeTime';

    /**
     * @var string class name of this filter group
     */
    protected $_className = 'HumanResources_Model_FreeTimeFilter';

    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'             => array( 'filter' => 'Tinebase_Model_Filter_Id', 'options' => array('modelName' => 'HumanResources_Model_FreeTime')),
        'firstday_date'  => array( 'filter' => 'Tinebase_Model_Filter_Date'),
        'type'           => array( 'filter' => 'Tinebase_Model_Filter_Text'),
        'status'         => array( 'filter' => 'Tinebase_Model_Filter_Text'),
        'remark'         => array( 'filter' => 'Tinebase_Model_Filter_Text'),
        'employee_id'    => array( 'filter' => 'Tinebase_Model_Filter_ForeignId',
            'options' => array(
                'filtergroup'       => 'HumanResources_Model_EmployeeFilter',
                'controller'        => 'HumanResources_Controller_Employee',
            )
        ),
        'last_modified_time'   => array('filter' => 'Tinebase_Model_Filter_Date'),
        'deleted_time'         => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'creation_time'        => array('filter' => 'Tinebase_Model_Filter_Date'),
        'last_modified_by'     => array('filter' => 'Tinebase_Model_Filter_User'),
        'created_by'           => array('filter' => 'Tinebase_Model_Filter_User'),
        );
}
