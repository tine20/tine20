<?php
/**
 * Tine 2.0

 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold CostCenter data
 *
 * @package     HumanResources
 * @subpackage  Model
 */
class HumanResources_Model_CostCenter extends Tinebase_Record_Abstract
{
    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
    
    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = array(
        'recordName'        => 'Cost Center', // ngettext('Cost Center', 'Cost Centers', n)
        'recordsName'       => 'Cost Centers',
        'hasRelations'      => FALSE,
        'hasCustomFields'   => FALSE,
        'hasNotes'          => FALSE,
        'hasTags'           => TRUE,
        'modlogActive'      => TRUE,
    
        'createModule'      => FALSE,
        'containerProperty' => NULL,
        'isDependent'       => TRUE,
        'titleProperty'     => 'cost_center_id.remark',
        'appName'           => 'HumanResources',
        'modelName'         => 'CostCenter',
    
        'fields'            => array(
            'employee_id'       => array(
                'label'      => 'Employee',    // _('Employee')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => FALSE),
                'type'       => 'record',
                'config' => array(
                    'appName'     => 'HumanResources',
                    'modelName'   => 'Employee',
                    'idProperty'  => 'id',
                    'isParent'    => TRUE
                )
            ),
            'cost_center_id'       => array(
                'label'      => 'Cost Center',    // _('Cost Center')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => FALSE),
                'type'       => 'record',
                'config' => array(
                    'appName'     => 'Sales',
                    'modelName'   => 'CostCenter',
                    'idProperty'  => 'id',
                    'isParent'    => FALSE
                )
            ),
            'start_date' => array(
                'label' => 'Start Date', //_('Start Date')
                'type'  => 'date',
            ),
        )
    );
}