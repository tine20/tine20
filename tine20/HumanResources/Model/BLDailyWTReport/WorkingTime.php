<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */


/**
 * virtual DailyWTReport WorkingTime Model
 *
 * @package     Tinebase
 * @subpackage  Model
 *
 * @property string                                                         classname
 * @property Tinebase_Record_Interface|Tinebase_BL_ElementConfigInterface   configRecord
 */
class HumanResources_Model_BLDailyWTReport_WorkingTime extends Tinebase_Record_NewAbstract
{
    const MODEL_NAME_PART = 'BLDailyWTReport_WorkingTime';
    const FLDS_WAGE_TYPE = 'wage_type';
    const FLDS_DURATION = 'duration';
    const FLDS_START = 'start';
    const FLDS_END = 'end';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME      => HumanResources_Config::APP_NAME,
        self::MODEL_NAME    => self::MODEL_NAME_PART,

        self::TITLE_PROPERTY=> "{# {{start - sorting! #}{{ duration |date('H:i', 'GMT')}}{% if start and end %} ({{ start |date('H:i', 'GMT')}} - {{ end |date('H:i', 'GMT')}}){% endif %} - {{wage_type.name}}",

        self::FIELDS        => [
            self::FLDS_WAGE_TYPE        => [
                self::TYPE                  => self::TYPE_RECORD,
                self::CONFIG                => [
                    self::APP_NAME              => HumanResources_Config::APP_NAME,
                    self::MODEL_NAME            => HumanResources_Model_WageType::MODEL_NAME_PART,
                ],
            ],
            self::FLDS_DURATION         => [
                self::TYPE                  => self::TYPE_INTEGER,
            ],
            self::FLDS_START            => [
                self::TYPE                  => self::TYPE_TIME,
            ],
            self::FLDS_END              => [
                self::TYPE                  => self::TYPE_TIME,
            ]
        ],
    ];
}
