<?php
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
 * BL convert timesheets composed of PT and WT to timeslot config model
 *
 * @see explanation in convertTsPtWtToTimeSlotExplainer.js
 *
 * @package     HumanResources
 * @subpackage  Model
 */
class HumanResources_Model_BLDailyWTReport_ConvertTsPtWtToTimeSlot extends Tinebase_Record_NewAbstract implements
    Tinebase_BL_ElementConfigInterface
{
    const MODEL_NAME_PART = 'BLDailyWTReport_ConvertTsPtWtToTimeSlot';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME      => HumanResources_Config::APP_NAME,
        self::MODEL_NAME    => self::MODEL_NAME_PART,
        self::RECORD_NAME   => 'Workingtime Converter',
        self::RECORDS_NAME  => 'Workingtime Converter', // ngettext('Workingtime Converter', 'Workingtime Converter', n)
        self::TITLE_PROPERTY=> 'Prefer workingtime over projecttime timesheets on a daily bases.', // _('Prefer workingtime over projecttime timesheets on a daily bases.')

        self::FIELDS        => [
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
        return new HumanResources_BL_DailyWTReport_ConvertTsPtWtToTimeSlot($this);
    }

    /**
     * The comparison function must return an integer less than, equal to, or
     * greater than zero if this is considered to be
     * respectively less than, equal to, or greater than the argument.
     */
    public function cmp(Tinebase_BL_ElementConfigInterface $_element)
    {
        if ($_element instanceof self) {
            return 0;
        }
        return -1;
    }
}