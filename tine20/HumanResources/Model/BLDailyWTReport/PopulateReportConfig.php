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
 * BL populate report config model
 *
 * @package     HumanResources
 * @subpackage  Model
 *
 */
class HumanResources_Model_BLDailyWTReport_PopulateReportConfig extends Tinebase_Record_NewAbstract implements
    Tinebase_BL_ElementConfigInterface
{
    const MODEL_NAME_PART = 'BLDailyWTReport_PopulateReportConfig';

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


        self::FIELDS        => [
            /*self::FLDS_START_TIME       => [
                self::TYPE                  => self::TYPE_TIME,
                self::LABEL                 => 'Start time', // _('Start time')
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],*/
        ],
    ];

    /**
     * @return Tinebase_BL_ElementInterface
     */
    public function getNewBLElement()
    {
        return new HumanResources_BL_DailyWTReport_PopulateReport($this);
    }

    /**
     * The comparison function must return an integer less than, equal to, or
     * greater than zero if this is considered to be
     * respectively less than, equal to, or greater than the argument.
     */
    public function cmp(Tinebase_BL_ElementConfigInterface $_element)
    {
        return 1;
    }
}
