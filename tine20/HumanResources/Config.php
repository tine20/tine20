<?php
/**
 * @package     HumanResources
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * HumanResources config class
 *
 * @package     HumanResources
 * @subpackage  Config
 */
class HumanResources_Config extends Tinebase_Config_Abstract
{
    const APP_NAME = 'HumanResources';

    /**
     * FreeTime Type
     * @var string
     */
    const FREETIME_TYPE = 'freetimeType';

    /**
     * Vacation Status
     * @var string
     */
    const VACATION_STATUS = 'vacationStatus';

    /**
     * Sickness Status
     * @var string
     */
    const SICKNESS_STATUS = 'sicknessStatus';
    
    /**
     * Default Feast Calendar (used for tailoring datepicker)
     * @var string
     */
    const DEFAULT_FEAST_CALENDAR = 'defaultFeastCalendar';
    
    /**
     * Defines the date when vacation booked from last year can't be taken anymore
     * 
     * @var string
     */
    const VACATION_EXPIRES = 'vacationExpires';
    
    /**
     * types for extra free times
     * 
     * @var string
     */
    const EXTRA_FREETIME_TYPE = 'extraFreetimeType';

    /**
     * calculate daily reports
     *
     * @var string
     */
    const FEATURE_CALCULATE_DAILY_REPORTS = 'calculateDailyRepots';

    /**
     * enable working time tracking
     *
     * @string
     */
    const FEATURE_WORKING_TIME_ACCOUNTING = 'workingTimeAccounting';

    /**
     * id of (filsystem) container for vacation templates
     *
     * @var string
     */
    const REPORT_TEMPLATES_CONTAINER_ID = 'reportTemplatesContainerId';

    /**
     * id of timeaccount for workingtime timesheets
     */
    const WORKING_TIME_TIMEACCOUNT = 'workingTimeTimeAccount';
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        self::FREETIME_TYPE => array(
            //_('Freetime Type')
            'label'                 => 'Freetime Type',
            //_('Possible free time definitions')
            'description'           => 'Possible free time definitions',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'HumanResources_Model_FreeTimeType'),
            'clientRegistryInclude' => TRUE,
            'default'               => array(
                'records' => array(
                    array('id' => 'SICKNESS',             'value' => 'Sickness',           'icon' => 'images/icon-set/icon_sick.svg',  'system' => TRUE),  //_('Sickness')
                    array('id' => 'VACATION',             'value' => 'Vacation',           'icon' => 'images/icon-set/icon_vacation.svg', 'system' => TRUE),  //_('Vacation')
                ),
                'default' => 'VACATION'
            )
        ),
        self::VACATION_STATUS => array(
            //_('Vacation Status')
            'label'                 => 'Vacation Status',
            //_('Possible vacation status definitions')
            'description'           => 'Possible vacation status definitions',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'HumanResources_Model_FreeTimeStatus'),
            'clientRegistryInclude' => TRUE,
            'default'               => array(
                'records' => array(
                    array('id' => 'REQUESTED',  'value' => 'Requested',  'icon' => 'images/icon-set/icon_invite.svg', 'system' => TRUE),  //_('Requested')
                    array('id' => 'IN-PROCESS', 'value' => 'In process', 'icon' => 'images/icon-set/icon_reload.svg', 'system' => TRUE),  //_('In process')
                    array('id' => 'ACCEPTED',   'value' => 'Accepted',   'icon' => 'images/icon-set/icon_ok.svg', 'system' => TRUE),  //_('Accepted')
                    array('id' => 'DECLINED',   'value' => 'Declined',   'icon' => 'images/icon-set/icon_stop.svg', 'system' => TRUE),  //_('Declined')

                ),
                'default' => 'REQUESTED'
            )
        ),
        self::SICKNESS_STATUS => array(
            //_('Sickness Status')
            'label'                 => 'Sickness Status',
            //_('Possible sickness status definitions')
            'description'           => 'Possible sickness status definitions',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'HumanResources_Model_FreeTimeStatus'),
            'clientRegistryInclude' => TRUE,
            'default'               => array(
                'records' => array(
                    array('id' => 'EXCUSED',   'value' => 'Excused',   'icon' => 'images/icon-set/icon_ok.svg', 'system' => TRUE),  //_('Excused')
                    array('id' => 'UNEXCUSED', 'value' => 'Unexcused', 'icon' => 'images/icon-set/icon_stop.svg', 'system' => TRUE),  //_('Unexcused')

                ),
                'default' => 'EXCUSED'
            )
        ),
        self::DEFAULT_FEAST_CALENDAR => array(
            // _('Default Feast Calendar')
            'label'                 => 'Default Feast Calendar',
            // _('Here you can define the default public holiday calendar used to set public holidays and other free days in datepicker')
            'description'           => 'Here you can define the default public holiday calendar used to set public holidays and other free days in datepicker',
            'type'                  => Tinebase_Config_Abstract::TYPE_STRING,
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
        ),
        self::ENABLED_FEATURES => [
            //_('Enabled Features')
            self::LABEL                 => 'Enabled Features',
            //_('Enabled Features in HumanResources Application.')
            self::DESCRIPTION           => 'Enabled Features in HumanResources Application.',
            self::TYPE                  => self::TYPE_OBJECT,
            self::CLASSNAME             => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => true,
            self::CONTENT               => [
                self::FEATURE_CALCULATE_DAILY_REPORTS => [
                    self::LABEL                 => 'Calculate Daily Reports',
                    //_('Calculate Daily Reports')
                    self::DESCRIPTION           => 'Activate to calculate daily reports with a nightly scheduler job',
                    //_('Activate to calculate daily reports with a nightly scheduler job')
                    self::TYPE                  => self::TYPE_BOOL,
                    self::DEFAULT_STR           => true,
                ],
                self::FEATURE_WORKING_TIME_ACCOUNTING => [
                    self::LABEL                 => 'Enable Working Time Tracking',
                    //_('Enable Working Time Tracking')
                    self::DESCRIPTION           => 'Activate to enable working time tracking and reporting',
                    //_('Activate to enable working time tracking and reporting')
                    self::TYPE                  => self::TYPE_BOOL,
                    self::DEFAULT_STR           => false,
                ],

            ],
            self::DEFAULT_STR => [],
        ],
        self::EXTRA_FREETIME_TYPE => array(
            //_('Extra freetime type')
            'label'                 => 'Extra freetime type',
            //_('Possible extra free time definitions')
            'description'           => 'Possible extra free time definitions',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'HumanResources_Model_ExtraFreeTimeType'),
            'clientRegistryInclude' => TRUE,
            'default'               => array(
                'records' => array(
                    array('id' => 'PAYED',     'value' => 'Payed',     'icon' => NULL, 'system' => TRUE),  //_('Payed')
                    array('id' => 'NOT_PAYED', 'value' => 'Not payed', 'icon' => NULL, 'system' => TRUE),  //_('Not payed')
                ),
                'default' => 'PAYED'
            )
        ),
        self::VACATION_EXPIRES => array(
            // _('Vacation expires')
            'label'                 => 'Vacation expires',
            // _('Here you can define the day, when the vacation days taken from last year expires, the format is MM-DD.')
            'description'           => 'Here you can define the day, when the vacation days taken from last year expires, the format is MM-DD.',
            'type'                  => 'string',
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'default' => '03-15'
        ),
        self::REPORT_TEMPLATES_CONTAINER_ID => array(
        //_('Report Templates Node ID')
            'label'                 => 'Report Templates Node ID',
            'description'           => 'Report Templates Node ID',
            'type'                  => Tinebase_Config_Abstract::TYPE_STRING,
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => FALSE,
        ),
        self::WORKING_TIME_TIMEACCOUNT => array(
            //_('Timetracker Timeaccount for Workingtime Tracking')
            'label'                 => 'Timetracker Timeaccount for Workingtime Tracking',
            'description'           => 'Timetracker Timeaccount for Workingtime Tracking',
            'type'                  => Tinebase_Config_Abstract::TYPE_STRING,
            'clientRegistryInclude' => true,
            'setByAdminModule'      => true,
            'setBySetupModule'      => false,
        ),
    );

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'HumanResources';

    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Config
     */
    private static $_instance = NULL;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() {
    }

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __clone() {
    }

    /**
     * Returns instance of Tinebase_Config
     *
     * @return Tinebase_Config
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
    
    /**
     * returns the date of vacation expiration for the given year 
     * or for the current year, if no year is given or null, if no expiration is defined
     * 
     * @param string $year
     * @return Tinebase_DateTime|NULL
     */
    public function getVacationExpirationDate($year)
    {
        if (! $year) {
            $year = Tinebase_DateTime::now()->format('Y');
        }
        
        $expires = self::getInstance()->get(self::VACATION_EXPIRES, 0);
        
        if ($expires != 0) {
            $split = explode('-', $expires);
            $date = Tinebase_DateTime::now();
            $date->setDate($year, (int) $split[0], (int) $split[1]);
        } else {
            return null;
        }
    }
}
