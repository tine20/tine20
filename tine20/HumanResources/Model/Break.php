<?php
/**
 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2017-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 *
 * @package     HumanResources
 * @subpackage  Model
 * @property    $name
 * @property    $initiator
 */
class HumanResources_Model_Break extends Tinebase_Record_Abstract
{
    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        'version' => 1,
        'recordName' => 'Break', // _('Break')
        'recordsName' => 'Breaks', // ngettext('Break', 'Breaks', n)
        
        'titleProperty' => 'hours',
        'hasRelations' => false,
        'hasCustomFields' => false,
        'hasNotes' => false,
        'hasTags' => false,
        'modlogActive' => false,
        'hasdepeneAttachments' => false,
        'exposeJsonApi' => true,
        'exposeHttpApi' => true,

        'copyEditAction' => false,

        'createModule' => false,
        'appName' => 'HumanResources',
        'modelName' => 'Break',

        'table' => [
            'name' => 'humanresources_breaks',
            'indexes' => [],
        ],

        'fields' => [
            'workingtime_id'       => [
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => FALSE),
                'type'       => 'record',
                'sortable'   => FALSE,
                'config' => array(
                    'appName'     => 'HumanResources',
                    'modelName'   => 'WorkingTime',
                    'idProperty'  => 'id',
                    'isParent'    => TRUE
                )
            ],

            'hours'              => array(
                'label'                 => 'Hours', // _('Hours')
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
                'type'                  => 'integer',
                'specialType'           => 'hours',
                'default'               => '1'
            ),

            'break_duration'              => array(
                'label'                 => 'Break duration', // _('Break duration')
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
                'type'                  => 'integer',
                'specialType'           => 'minutes',
                'default'               => '30'
            ),
        ]
    ];
}
