<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */


/**
 * BL break time config model
 *
 * @package     HumanResources
 * @subpackage  Model
 */
class HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig extends Tinebase_Record_NewAbstract implements
    Tinebase_BL_ElementConfigInterface
{
    const MODEL_NAME_PART = 'BLAttendanceRecorder_TimeSheetConfig';

    const FLD_STATIC_TA = 'static_ta';
    const FLD_ALLOW_OTHER_TA = 'allow_other_ta';
    const FLD_FILL_GAPS_OF_DEVICES = 'fill_gaps_of_devices';

    protected static $_modelConfiguration = [
        self::APP_NAME      => HumanResources_Config::APP_NAME,
        self::MODEL_NAME    => self::MODEL_NAME_PART,

        self::FIELDS        => [
            self::FLD_STATIC_TA             => [
                self::TYPE                      => self::TYPE_RECORD,
                self::CONFIG                    => [
                    self::APP_NAME                  => Timetracker_Config::APP_NAME,
                    self::MODEL_NAME                => Timetracker_Model_Timeaccount::MODEL_NAME_PART,
                ],
            ],
            self::FLD_ALLOW_OTHER_TA        => [
                self::TYPE                      => self::TYPE_BOOLEAN,
            ],
            self::FLD_FILL_GAPS_OF_DEVICES  => [
                self::TYPE                      => self::TYPE_RECORDS,
                self::CONFIG                    => [
                    self::APP_NAME                  => HumanResources_Config::APP_NAME,
                    self::MODEL_NAME                => HumanResources_Model_AttendanceRecorderDevice::MODEL_NAME_PART,
                    self::STORAGE                   => self::TYPE_JSON_REFID,
                ],
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;

    /**
     * @return Tinebase_BL_ElementInterface
     */
    public function getNewBLElement()
    {
        return new HumanResources_BL_AttendanceRecorder_TimeSheet($this);
    }

    /**
     * The comparison function must return an integer less than, equal to, or
     * greater than zero if this is considered to be
     * respectively less than, equal to, or greater than the argument.
     */
    public function cmp(Tinebase_BL_ElementConfigInterface $_element)
    {
        return 0;
    }
}
