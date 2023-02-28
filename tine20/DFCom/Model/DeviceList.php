<?php
/**
 * @package     DFCom
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold Device data
 *
 * @package     DFCom
 * @subpackage  Model
 *
 * @property    $device_id
 * @property    $name
 * @property    $export_definition_id
 * @property    $list_version
 * @property    $list_status
 */
class DFCom_Model_DeviceList extends Tinebase_Record_Abstract
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
        'recordName' => 'Device list',
        'recordsName' => 'Device lists', // ngettext('Device list', 'Device lists', n)
        'containerProperty' => null,
        'titleProperty' => 'name',
        'hasRelations' => false,
        'hasCustomFields' => false,
        'hasNotes' => false,
        'hasTags' => false,
        'modlogActive' => true,
        'hasAttachments' => false,
        'createModule'    => true,
        'exposeJsonApi' => true,
        'isDependent'     => true,
        'appName' => 'DFCom',
        'modelName' => 'DeviceList',

        // why do i have to define this -> autodefine???
        'associations' => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                'device_id' => [
                    'targetEntity' => DFCom_Model_Device::class,
                    'fieldName' => 'device_id',
                    'joinColumns' => [[
                        'name' => 'device_id',
                        'referencedColumnName'  => 'id'
                    ]],
                ],
            ],
        ],

        // why do i have to define this -> autodefine???
        'table'             => [
            'name'    => 'dfcom_device_list',
            'indexes' => [
                'device_id' => [
                    'columns' => ['device_id', 'name'],
                ],
            ],
        ],

        'fields' => [
            'device_id'       => [
                'label'      => 'Device',    // _('Device')
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => false],
                'type'       => 'record',
                'sortable'   => false,
                'config' => [
                    'appName'     => 'DFCom',
                    'modelName'   => 'Device',
                    'idProperty'  => 'id',
                    'isParent'    => true
                ]
            ],
            // list name on device
            'name' => [
                'type' => 'string',
                'length' => 255,
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                'label' => 'Name', // _('Name')
                'queryFilter' => true
            ],
            'export_definition_id' => [
                'type' => 'string',
                'length' => 255,
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                'label' => 'Export Definition', // _('Export Definition')
                'queryFilter' => true
            ],
            // update version when list is assembled and send to device
            'list_version' => [
                'type' => 'string',
                'length' => 255,
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'label' => 'Active list version', // _('Active list version')
                'queryFilter' => true,
                'nullable' => true,
            ],
            // update status on device feedback
            'list_status' => [
                'type' => 'keyfield',
                'name'  => DFCom_Config::DEVICE_LIST_STATUS,
                'length' => 4,
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'label' => 'List status', // _('List status')
                'nullable' => true,
            ],
            'controlCommands' => [
                'type' => 'text',
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'label' => 'Control Commands on List Feedback', // _('Control Commands on List Feedback')
                'nullable' => true,
                self::UI_CONFIG => [
                    'emptyText' => "setDeviceVariable('TAListLoaded', 1);
triggerEventChain('projectTime');
"
                ],
            ],
        ]
    ];

}
