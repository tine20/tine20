<?php
/**
 * @package     DFCom
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold DF Device data
 *
 * @package     DFCom
 * @subpackage  Model
 *
 * @property    $deviceString
 * @property    $serialNumber
 * @property    $authKey
 * @property    $name
 * @property    $description
 * @property    $location
 * @property    $fwVersion
 * @property    $setupVersion
 * @property    $lastSeen
 * @property    $cellularData
 * @property    $GPRSAliveCounter
 * @property    $GPRSData
 * @property    $digitalStatus
 * @property    $lists
 * @property    $controlCommands
 */
class DFCom_Model_Device extends Tinebase_Record_Abstract
{
    /**
     * @var array list of runtime/status fields in this record
     */
    public static $statusFields = [
        'fwVersion',
        'setupVersion',
        'setupStatus',
        'cellularData',
        'GPRSAliveCounter',
        'GPRSData',
        'digitalStatus'
    ];

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
        'version' => 2,
        'recordName' => 'Device',
        'recordsName' => 'Devices', // ngettext('Device', 'Devices', n)
        'containerProperty' => 'container_id',
        'titleProperty' => 'name',
        'containerName' => 'Devices',
        'containersName' => 'Devices',
        'hasRelations' => true,
        'hasCustomFields' => true,
        'hasNotes' => true,
        'hasTags' => true,
        'modlogActive' => true,
        'hasAttachments' => true,
        'exposeJsonApi' => true,
        'exposeHttpApi' => true,

        'singularContainerMode' => false,
        'hasPersonalContainer' => false,

        'copyEditAction' => true,
        'multipleEdit' => true,
        
        'createModule' => true,
        'appName' => 'DFCom',
        'modelName' => 'Device',

        'table' => [
            'name' => 'dfcom_device'
        ],

        'fields' => [
            'deviceString' => [
                'type' => 'string',
                'length' => 255,
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                'label' => 'Type', // _('Type')
                'queryFilter' => true
            ],
            'serialNumber' => [
                'type' => 'integer',
                'length' => 4,
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                'label' => 'Serial number', // _('Serial number')
                'queryFilter' => true
            ],
            'authKey' => [
                'type' => 'string',
                'length' => 20,
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'label' => 'Auth key', // _('Auth key')
                'shy' => true,
            ],
            'name' => [
                'type' => 'string',
                'length' => 255,
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'label' => 'Name', // _('Name')
                'queryFilter' => true,
                'nullable' => true,
            ],
            'description' => [
                'type' => 'fulltext',
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'label' => 'Description', // _('Description')
                'queryFilter' => true,
                'nullable' => true,
            ],
            'location' => [
                'type' => 'string',
                'length' => 255,
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'label' => 'Location', // _('Location')
                'queryFilter' => true,
                'nullable' => true,
            ],
            'timezone' => [
                'type' => 'string',
                'length' => 40,
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                'label' => 'Timezone', // _('Timezone')
                'queryFilter' => true,
                'nullable' => true,
            ],
            'fwVersion' => [
                'type' => 'string',
                'length' => 11,
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                'label' => 'Firmware', // _('Firmware')
            ],
            'setupVersion' => [
                'type' => 'string',
                'length' => 20,
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                'label' => 'Setup Version', // _('Setup Version')
            ],
            'setupStatus' => [
                'type' => 'string',
                'length' => 16,
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'label' => 'Setup Status', // _('Setup Status')
            ],
            'lastSeen' => [
                'type' => 'datetime',
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'label' => 'Last seen', // _('Last seen')
                'nullable' => true,
            ],
            'cellularData' => [
                'type' => 'string',
                'length' => 255,
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'label' => 'Cellular data', // _('Cellular data')
                'nullable' => true,
            ],
            'GPRSAliveCounter' => [
                'type' => 'integer',
                'length' => 10,
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'label' => 'GPRS alive counter', // _('GPRS alive counter')
                'nullable' => true,
            ],
            'GPRSData' => [
                'type' => 'string',
                'length' => 255,
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'label' => 'GPRS data', // _('GPRS data')
                'nullable' => true,
            ],
            'digitalStatus' => [
                'type' => 'integer',
                'length' => 4,
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'label' => 'Digital status', // _('Digital status')
                'nullable' => true,
            ],
            'lists' => [
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => NULL],
                'label'      => 'Lists', // _('Lists')
                'type'       => 'records',
                'config'     => [
                    'dependentRecords' => true,
                    'omitOnSearch' => false,
                    'appName'     => 'DFCom',
                    'modelName'   => 'DeviceList',
                    'refIdField'  => 'device_id',
                    'paging'      => ['sort' => 'name', 'dir' => 'ASC'],
                ],
            ],
            'controlCommands' => [
                'type' => 'text',
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'label' => 'Control Commands', // _('Control Commands')
                'nullable' => true,
            ],
        ]
    ];

    /**
     * merge status data from given deviceRecord into this record
     *
     * @param  DFCom_Model_DeviceRecord $deviceRecord
     * @return DFCom_Model_Device $this
     */
    public function mergeStatusData(DFCom_Model_DeviceRecord $deviceRecord) {
        $deviceData = $deviceRecord->xprops('data');
        $mc = static::getConfiguration();

        foreach(self::$statusFields as $fieldName) {
            if (array_key_exists($fieldName, $deviceData)) {
                if (Tinebase_ModelConfiguration::TYPE_INTEGER === $mc->_fields[$fieldName][Tinebase_ModelConfiguration::TYPE]) {
                    $this->{$fieldName} = $deviceData[$fieldName] === '' ? null : $deviceData[$fieldName];
                } else {
                    $this->{$fieldName} = $deviceData[$fieldName];
                }
            }
        }
    }
}
