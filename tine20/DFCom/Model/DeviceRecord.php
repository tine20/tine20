<?php
/**
 * @package     DFCom
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold DF Device Records
 *
 * @package     DFCom
 * @subpackage  Model
 *
 * @property    $device_id
 * @property    $device_table
 * @property    $data
 * @property    $processed
 */
class DFCom_Model_DeviceRecord extends Tinebase_Record_Abstract
{
    public const TABLE_NAME = 'dfcom_device_record';
    public const FLD_PROCESSED = 'processed';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;

    public static $cellularDataProps = ['countryCode', 'networkCode', 'locationAreaCode', 'cellId'];

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        'version' => 2,
        'recordName' => 'Device Record',
        'recordsName' => 'Device Records', // ngettext('Device Record', 'Device Records', n)
        'titleProperty' => 'device_table',

        // TODO needed?
        // 'containerProperty' => 'device_id',
        // 'containerName' => 'Device Records Container',
        // 'containersName' => 'Device Records Containers', // ngettext('Device Records Container', 'Device Records Containers', n)

        'hasRelations' => false,
        'hasCustomFields' => false,
        'hasNotes' => false,
        'hasTags' => false,
        'modlogActive' => true,
        'hasAttachments' => false,
        'isDependent'     => true,
        self::HAS_XPROPS => true,
        'createModule' => true,
        'appName' => 'DFCom',
        'modelName' => 'DeviceRecord',
        'exposeJsonApi'     => true,

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
            'name'    => self::TABLE_NAME,
            'indexes' => [
                'device_id' => [
                    'columns' => ['device_id'],
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
            'device_table' => [
                'type' => 'string',
                'length' => 255,
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'label' => 'Device Table', // _('Device Table')
                'queryFilter' => true
            ],
            'data' => [
                'type' => 'json',
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                'label' => 'Data', // _('Data')
                'queryFilter' => true
            ],
            self::FLD_PROCESSED => [
                'type' => 'json',
                self::NULLABLE => true,
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'label' => 'Processed by', // _('Processed by')
            ],
        ]
    ];

    /**
     * @param \Laminas\Stdlib\ParametersInterface $query
     * @return DFCom_Model_DeviceRecord
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
    public static function createFromDeviceQuery(\Laminas\Stdlib\ParametersInterface $query)
    {
        $prefix = 'df_col_';

        $recordData = [
            'device_table' => $query->df_table,
            'data' => []
        ];

        foreach($query as $key => $val) {
            $property = str_replace($prefix, '',  $key);
            if ($property != $key) {
                $recordData['data'][$property] = $val;
            }
        }

        $binaryFields = ['cellularData', 'GPRSData'];
        foreach ($binaryFields as $binaryField) {
            if (isset($recordData['data'][$binaryField])) {
                $recordData['data'][$binaryField] = base64_encode($recordData['data'][$binaryField]);
            }
        }

        return new self($recordData);
    }
}
