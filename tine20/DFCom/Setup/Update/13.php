<?php
/**
 * Tine 2.0
 *
 * @package     DFCom
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 * this ist 2020.11 (ONLY!)
 */
class DFCom_Setup_Update_13 extends Setup_Update_Abstract
{
    const RELEASE013_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE013_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE013_UPDATE003 = __CLASS__ . '::update003';

    static protected $_allUpdates = [
        self::PRIO_TINEBASE_BEFORE_STRUCT => [
            self::RELEASE013_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
        ],
        self::PRIO_NORMAL_APP_STRUCTURE => [
            self::RELEASE013_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE => [
            self::RELEASE013_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
        ],
    ];

    public function update001()
    {
        Setup_SchemaTool::updateSchema([DFCom_Model_DeviceRecord::class]);
        $this->addApplicationUpdate(DFCom_Config::APP_NAME, '1.1', self::RELEASE013_UPDATE001);
    }

    public function update002()
    {
        $this->getDb()->query('UPDATE ' . SQL_TABLE_PREFIX . DFCom_Model_DeviceRecord::TABLE_NAME . ' SET `' .
            DFCom_Model_DeviceRecord::FLD_PROCESSED . '` = "[\"' . DFCom_RecordHandler_TimeAccounting::class . '\"]"');
        $this->addApplicationUpdate(DFCom_Config::APP_NAME, '1.2', self::RELEASE013_UPDATE002);
    }

    public function update003()
    {
        // convert dfcom_id empty string to NULL
        $this->getDb()->query('UPDATE ' . SQL_TABLE_PREFIX . "humanresources_employee set dfcom_id = NULL where dfcom_id = ''");

        $initalize = new DFCom_Setup_Initialize();
        $method = new ReflectionMethod(DFCom_Setup_Initialize::class, '_initializeSystemCFs');
        $method->setAccessible(true);
        $method->invoke($initalize);

        $this->addApplicationUpdate(DFCom_Config::APP_NAME, '1.3', self::RELEASE013_UPDATE002);
    }
}
