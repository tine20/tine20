<?php declare(strict_types=1);
/**
 * class to hold AttendanceRecorderClockInOutResult data
 *
 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold AttendanceRecorderClockInOutResult data
 *
 * @package     HumanResources
 * @subpackage  Model
 */
class HumanResources_Model_AttendanceRecorderClockInOutResult extends Tinebase_Record_NewAbstract
{
    const MODEL_NAME_PART = 'AttendanceRecorderClockInOutResult';

    const FLD_CLOCK_INS = 'clock_ins';
    const FLD_CLOCK_OUTS = 'clock_outs';
    const FLD_CLOCK_PAUSES = 'clock_pauses';
    const FLD_FAULTY_CLOCKS = 'faulty_clocks';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                  => HumanResources_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,

        self::FIELDS                    => [
            self::FLD_CLOCK_INS             => [
                self::TYPE                      => self::TYPE_RECORDS,
                self::CONFIG                    => [
                    self::APP_NAME                  => HumanResources_Config::APP_NAME,
                    self::MODEL_NAME                => HumanResources_Model_AttendanceRecord::MODEL_NAME_PART,
                ],
            ],
            self::FLD_CLOCK_OUTS            => [
                self::TYPE                      => self::TYPE_RECORDS,
                self::CONFIG                    => [
                    self::APP_NAME                  => HumanResources_Config::APP_NAME,
                    self::MODEL_NAME                => HumanResources_Model_AttendanceRecord::MODEL_NAME_PART,
                ],
            ],
            self::FLD_CLOCK_PAUSES          => [
                self::TYPE                      => self::TYPE_RECORDS,
                self::CONFIG                    => [
                    self::APP_NAME                  => HumanResources_Config::APP_NAME,
                    self::MODEL_NAME                => HumanResources_Model_AttendanceRecord::MODEL_NAME_PART,
                ],
            ],
            self::FLD_FAULTY_CLOCKS     => [
                self::TYPE                      => self::TYPE_RECORDS,
                self::CONFIG                    => [
                    self::APP_NAME                  => HumanResources_Config::APP_NAME,
                    self::MODEL_NAME                => HumanResources_Model_AttendanceRecord::MODEL_NAME_PART,
                ],
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
