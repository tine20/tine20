<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */


/**
 * BL break time config model
 *
 * @package     HumanResources
 * @subpackage  Model
 *
 * @property integer    time_worked
 * @property integer    break_time
 */
class HumanResources_Model_BLDailyWTReport_BreakTimeConfig extends Tinebase_Record_NewAbstract implements
    Tinebase_BL_ElementConfigInterface
{
    const MODEL_NAME_PART = 'BLDailyWTReport_BreakTimeConfig';

    const FLDS_TIME_WORKED = 'time_worked';
    const FLDS_BREAK_TIME = 'break_time';

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
        self::APP_NAME      => HumanResources_Config::APP_NAME,
        self::MODEL_NAME    => self::MODEL_NAME_PART,
        self::RECORD_NAME   => 'Break Time',
        self::RECORDS_NAME  => 'Break Times', // ngettext('Break Time', 'Break Times', n)
                          // _('After exceeding {{time_worked|date("H:i", "GMT")}} of working time {{break_time|date("H:i", "GMT")}} break time gets deducted automatically.')
        self::TITLE_PROPERTY=> 'After exceeding {{time_worked|date("H:i", "GMT")}} of working time {{break_time|date("H:i", "GMT")}} break time gets deducted automatically.',


        self::FIELDS        => [
            self::FLDS_TIME_WORKED      => [
                self::TYPE                  => self::TYPE_INTEGER,
                self::SPECIAL_TYPE          => self::SPECIAL_TYPE_DURATION_SEC,
                self::LABEL                 => 'Time worked', // _('Time worked')
                self::VALIDATORS            => [Zend_Filter_Input::PRESENCE_REQUIRED => true],
            ],
            self::FLDS_BREAK_TIME        => [
                self::TYPE                  => self::TYPE_INTEGER,
                self::SPECIAL_TYPE          => self::SPECIAL_TYPE_DURATION_SEC,
                self::LABEL                 => 'Break time', // _('Break time')
                self::VALIDATORS            => [Zend_Filter_Input::PRESENCE_REQUIRED => true],
            ],
        ],
    ];

    /**
     * @return Tinebase_BL_ElementInterface
     */
    public function getNewBLElement()
    {
        return new HumanResources_BL_DailyWTReport_BreakTime($this);
    }

    /**
     * The comparison function must return an integer less than, equal to, or
     * greater than zero if this is considered to be
     * respectively less than, equal to, or greater than the argument.
     */
    public function cmp(Tinebase_BL_ElementConfigInterface $_element)
    {
        if ($_element instanceof HumanResources_Model_BLDailyWTReport_LimitWorkingTimeConfig) {
            return 1;
        }
        if ($_element instanceof HumanResources_Model_BLDailyWTReport_PopulateReportConfig) {
            return -1;
        }
        if ($_element instanceof HumanResources_Model_BLDailyWTReport_BreakTimeConfig) {
            return $this->{self::FLDS_TIME_WORKED} < $_element->{self::FLDS_TIME_WORKED} ? -1 :
                ($this->{self::FLDS_TIME_WORKED} > $_element->{self::FLDS_TIME_WORKED} ? 1 : 0);
        }
        return 0;
    }
}