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
class HumanResources_Model_CostCenterFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'HumanResources';

    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'HumanResources_Model_CostCenter';

    /**
     * @var string class name of this filter group
     */
    protected $_className = 'HumanResources_Model_CostCenterFilter';

    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'          => array('filter' => 'Tinebase_Model_Filter_Id', 'options' => array('modelName' => 'HumanResources_Model_CostCenter')),
        'employee_id' => array('filter' => 'Tinebase_Model_Filter_Id', 'options' => array('modelName' => 'HumanResources_Model_Employee')),
        'cost_center_id' => array('filter' => 'Tinebase_Model_Filter_Id', 'options' => array('modelName' => 'HumanResources_Model_Employee')),
        'start_date'  => array('filter' => 'Tinebase_Model_Filter_Date'),
    );
}
