<?php
/**
 * Tine 2.0
 *
 * @package     DFCom
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * DFCom config class
 *
 * @package     DFCom
 * @subpackage  Config
 *
 *
 */
class DFCom_Config extends Tinebase_Config_Abstract
{
    const APP_NAME = 'DFCom';

    const PUBLIC_ROLE_NAME = 'DFCom_AnonymousRole';

    const DEVICE_LIST_STATUS = 'deviceListStaus';

    const SETUP_AUTH_KEY = 'setupAuthKey';

    const DEFAULT_DEVICE_CONTAINER = 'defaultDeviceContainer';

    const DEFAULT_DEVICE_LISTS = 'defaultDeviceLists';

    const DEVICE_RECORD_HANDLERS = 'deviceRecordHandlers';

    const DFCOM_ID_TYPE = 'dfcomIdType';

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = [
        self::DFCOM_ID_TYPE             => [
            self::LABEL                     => 'Employee Transponder Id Type',
            self::DESCRIPTION               => 'Employee Transponder Id Type',
            self::TYPE                      => self::TYPE_STRING,
            self::SETBYSETUPMODULE          => true,
            self::SETBYADMINMODULE          => true,
            self::DEFAULT_STR               => Tinebase_ModelConfiguration_Const::TYPE_BIGINT,
        ],
        self::DEVICE_LIST_STATUS => [
            //_('Device List Status')
            'label'                 => 'Device List Status',
            //_('Possible list status from device list feedback records.')
            'description'           => 'Possible list status from device list feedback records.',
            'type'                  => Tinebase_Config_Abstract::TYPE_KEYFIELD_CONFIG,
            'clientRegistryInclude' => true,
            'setByAdminModule'      => false,
            'default'               => [
                'records' => array(
                    ['id' =>   -1, 'value' => 'List was send to device', 'system' => true], //_('List was send to device')
                    ['id' =>    0, 'value' => 'List was taken over', 'system' => true], //_('List was taken over')
                    ['id' =>    1, 'value' => 'Generic error', 'system' => true], //_('Generic error')
                    ['id' => 1001, 'value' => 'Invalid label', 'system' => true], //_('Invalid label')
                    ['id' => 1002, 'value' => 'Unknown list', 'system' => true], //_('Unknown list')
                    ['id' => 1003, 'value' => 'Parameter missing', 'system' => true], //_('Parameter missing')
                    ['id' => 1004, 'value' => 'Error in list line', 'system' => true], //_('Error in list line')
                    ['id' => 1005, 'value' => 'List is ignored', 'system' => true], //_('List is ignored')
                    ['id' => 1006, 'value' => 'Duplicate list definition', 'system' => true], //_('Duplicate list definition')
                    ['id' => 1007, 'value' => 'An other list upgrade is in progress', 'system' => true], //_('An other list upgrade is in progress')
                ),
                'default' => 0
            ]
        ],
        self::SETUP_AUTH_KEY => [
            //_('Initial AuthKey for Device Setup')
            'label'                 => 'Initial AuthKey for Device Setup',
            //_('Must be set as default for global variable authKey (max 20 chars) in DataFox Studio for the device setup.')
            'description'           => 'Must be set as default for global variable authKey (max 20 chars) in DataFox Studio for the device setup.',
            'type'                  => Tinebase_Config_Abstract::TYPE_STRING,
            'clientRegistryInclude' => false,
            'setByAdminModule'      => true,
        ],
        self::DEFAULT_DEVICE_CONTAINER => [
            //_('Default Container for Devices')
            'label'                 => 'Default Container for Devices',
            //_('The container where new devices are created in.')
            'description'           => 'The container where new devices are created in.',
            'type'                  => Tinebase_Config_Abstract::TYPE_STRING,
            'clientRegistryInclude' => true,
            'setByAdminModule'      => true,
        ],
        self::DEFAULT_DEVICE_LISTS => [
            //_('Default Devices Lists')
            'label'                 => 'Default Devices Lists',
            //_('List export definition names to load into devices per default.')
            'description'           => 'List export definition names to load into devices per default.',
            'type'                  => Tinebase_Config_Abstract::TYPE_ARRAY,
            'clientRegistryInclude' => true,
            'setByAdminModule'      => true,
        ],
        self::DEVICE_RECORD_HANDLERS => [
            //_('Device Record Handlers')
            'label'                 => 'Device Record Handlers',
            //_('Handler class for given device record.')
            'description'           => 'Handler class for given device record.',
            'type'                  => Tinebase_Config_Abstract::TYPE_ARRAY,
            'clientRegistryInclude' => false,
            'setByAdminModule'      => true,
            'default'               => [
                'timeAccounting'    => DFCom_RecordHandler_TimeAccounting::class,
                //'accessControll'    => DFCom_RecordHandler_AccessControll::class,
            ],
        ],
    ];

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'DFCom';

    /**
     * holds the instance of the singleton
     *
     * @var DFCom_Config
     */
    private static $_instance = NULL;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
    }

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __clone()
    {
    }

    /**
     * Returns instance of DFCom_Config
     *
     * @return DFCom_Config
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::getProperties()
     */
    public static function getProperties()
    {
        return self::$_properties;
    }
}
