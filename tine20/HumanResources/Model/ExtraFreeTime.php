<?php
/**
 * Tine 2.0

 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold ExtraFreeTime data
 *
 * @package     HumanResources
 * @subpackage  Model
 */
class HumanResources_Model_ExtraFreeTime extends Tinebase_Record_Abstract
{
    /**
     * holds the configuration object (must be set in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject;
    
    /**
     * Holds the model configuration
     *
     * @var array
     */
    protected static $_modelConfiguration = array(
        'recordName'        => 'Extra free time', // _('Extra free time')
        'recordsName'       => 'Extra free times', // _('Extra free times')
        'hasRelations'      => FALSE,
        'hasCustomFields'   => FALSE,
        'hasNotes'          => TRUE,
        'hasTags'           => TRUE,
        'modlogActive'      => TRUE,
        'containerProperty' => NULL,
        'createModule'      => FALSE,
        'isDependent'       => TRUE,
        'appName'           => 'HumanResources',
        'modelName'         => 'ExtraFreeTime',
        'fields'            => array(
            'account_id'       => array(
                'label'      => 'Account',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => FALSE),
                'type'       => 'record',
                'config' => array(
                    'appName'     => 'HumanResources',
                    'modelName'   => 'Account',
                    'idProperty'  => 'id',
                    'isParent'    => TRUE
                )
            ),
            'type'            => array(
                'label' => 'Type', // _('Type')
                'type'  => 'keyfield',
                'name'  => HumanResources_Config::EXTRA_FREETIME_TYPE,
                'queryFilter' => TRUE,
            ),
            'description'          => array(
                'label' => 'Description', // _('Description')
                'type'  => 'text',
                'queryFilter' => TRUE,
            ),
            'days' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => NULL),
                'label' => 'Days', // _('Days')
                'type'       => 'integer'
            ),
            'expires' => array(
                'label' => 'Expiration date', //_('Expiration date')
                'type'  => 'date',
            ),
        )
    );
}
