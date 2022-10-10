<?php
/**
 * class to hold WorkingTime Calculation Strategy for FreeTimeTypes
 *
 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
class HumanResources_Model_WTCalcStrategy extends Tinebase_Record_Abstract
{
    public const MODEL_NAME_PART = 'WTCalcStrategy';

    public const FLD_FILL_DAILY_TARGET = 'fillDailyTarget';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::RECORD_NAME               => 'Working Time Calculation Strategy', // gettext('GENDER_Working Time Calculation Strategy')
        self::RECORDS_NAME               => 'Working Time Calculation Strategies', // ngettext('Working Time Calculation Strategy', 'Working Time Calculation Strategies', n)
        self::APP_NAME                  => HumanResources_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::TITLE_PROPERTY            => "{% if fillDailyTarget %}Fill to daily target time{% else %}none{% endif %}",


        self::FIELDS                    => [
            self::FLD_FILL_DAILY_TARGET     => [
                self::TYPE                      => self::TYPE_BOOLEAN,
                self::LABEL                     => 'Fill to daily target time' // _('Fill to daily target time')
            ],
        ],
    ];

    public function apply(HumanResources_Model_DailyWTReport $dailyWTReport): int
    {
        //$add = 0;
        if ($this->{self::FLD_FILL_DAILY_TARGET}) {
            $add = ((int)$dailyWTReport->working_time_target + (int)$dailyWTReport->working_time_target_correction)
                - ((int)$dailyWTReport->working_time_actual + (int)$dailyWTReport->working_time_correction);
        } else {
            $add = (int)$dailyWTReport->working_time_target + (int)$dailyWTReport->working_time_target_correction;
        }
        if ($add > 0) {
            $dailyWTReport->working_time_total = $dailyWTReport->working_time_total + $add;
            $dailyWTReport->working_time_actual = $dailyWTReport->working_time_actual + $add;
        }
        return $add;
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}