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

    const FREE_TIME_TYPE_STATUS = 'freeTimeTypeStatus';
    const FREE_TIME_TYPE_STATUS_EXCUSED = 'EXCUSED';
    const FREE_TIME_TYPE_STATUS_UNEXCUSED = 'UNEXCUSED';
    const FREE_TIME_PROCESS_STATUS = 'freeTimeProcessStatus';
    const FREE_TIME_PROCESS_STATUS_REQUESTED = 'REQUESTED';
    const FREE_TIME_PROCESS_STATUS_ACCEPTED = 'ACCEPTED';
    const FREE_TIME_PROCESS_STATUS_DECLINED = 'DECLINED';

    const WTR_CORRECTION_STATUS = 'wtrCorrectionStatus';
    const WTR_CORRECTION_STATUS_REQUESTED = 'REQUESTED';
    const WTR_CORRECTION_STATUS_ACCEPTED = 'ACCEPTED';
    const WTR_CORRECTION_STATUS_DECLINED = 'DECLINED';

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
    const FEATURE_STREAMS = 'featureStreams';

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
     * key field for streams interval (week, month, quarter, year)
     */
    const STREAM_INTERVAL = 'streamInterval';

    /**
     * key field for stream types (velocity stream, working stream, etc.)
     */
    const STREAM_TYPE = 'streamType';
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        self::FREE_TIME_TYPE_STATUS => array(
            //_('Sickness Status')
            'label'                 => 'Sickness Status',
            //_('Possible sickness status definitions')
            'description'           => 'Possible sickness status definitions',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => HumanResources_Model_FreeTimeStatus::class),
            'clientRegistryInclude' => TRUE,
            'default'               => array(
                'records' => array(
                    array('id' => self::FREE_TIME_TYPE_STATUS_EXCUSED,   'value' => 'Excused',   'icon' => 'images/icon-set/icon_ok.svg', 'system' => TRUE),  //_('Excused')
                    array('id' => self::FREE_TIME_TYPE_STATUS_UNEXCUSED, 'value' => 'Unexcused', 'icon' => 'images/icon-set/icon_stop.svg', 'system' => TRUE),  //_('Unexcused')

                ),
                'default' => 'EXCUSED'
            )
        ),
        self::FREE_TIME_PROCESS_STATUS => array(
            //_('Vacation Status')
            'label'                 => 'Vacation Status',
            //_('Possible vacation status definitions')
            'description'           => 'Possible vacation status definitions',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => HumanResources_Model_FreeTimeStatus::class),
            'clientRegistryInclude' => TRUE,
            'default'               => array(
                'records' => array(
                    array('id' => self::FREE_TIME_PROCESS_STATUS_REQUESTED,  'value' => 'Requested',  'icon' => 'images/icon-set/icon_invite.svg', 'system' => TRUE),  //_('Requested')
                    array('id' => self::FREE_TIME_PROCESS_STATUS_ACCEPTED,   'value' => 'Accepted',   'icon' => 'images/icon-set/icon_ok.svg', 'system' => TRUE),  //_('Accepted')
                    array('id' => self::FREE_TIME_PROCESS_STATUS_DECLINED,   'value' => 'Declined',   'icon' => 'images/icon-set/icon_stop.svg', 'system' => TRUE),  //_('Declined')

                ),
                'default' => self::FREE_TIME_PROCESS_STATUS_REQUESTED
            )
        ),
        self::WTR_CORRECTION_STATUS => array(
            //_('Working Time Correction Status')
            'label'                 => 'Working Time Correction Status',
            //_('Working Time Correction Status')
            'description'           => 'Working Time Correction Status',
            'type'                  => 'keyFieldConfig',
            'clientRegistryInclude' => TRUE,
            'default'               => array(
                'records' => array(
                    array('id' => self::WTR_CORRECTION_STATUS_REQUESTED,  'value' => 'Requested',  'icon' => 'images/icon-set/icon_invite.svg', 'system' => TRUE),  //_('Requested')
                    array('id' => self::WTR_CORRECTION_STATUS_ACCEPTED,   'value' => 'Accepted',   'icon' => 'images/icon-set/icon_ok.svg', 'system' => TRUE),  //_('Accepted')
                    array('id' => self::WTR_CORRECTION_STATUS_DECLINED,   'value' => 'Declined',   'icon' => 'images/icon-set/icon_stop.svg', 'system' => TRUE),  //_('Declined')
                ),
                'default' => self::WTR_CORRECTION_STATUS_REQUESTED,
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
                self::FEATURE_STREAMS => [
                    self::LABEL                 => 'Enable Streams',
                    //_('Enable Streams')
                    self::DESCRIPTION           => 'Activate working streams',
                    //_('Activate working streams')
                    self::TYPE                  => self::TYPE_BOOL,
                    self::DEFAULT_STR           => false,
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
        self::VACATION_EXPIRES => array(
            // _('Vacation expires')
            'label'                 => 'Vacation expires',
            // _('Here you can define the day, when the vacation days taken from last year expires, the format is MM-DD.')
            'description'           => 'Here you can define the day, when the vacation days taken from last year expires, the format is MM-DD.',
            'type'                  => 'string',
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'default' => '03-31'
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
        self::STREAM_INTERVAL => [
            //_('Stream Intervals available')
            self::LABEL                     => 'Stream Intervals available',
            self::DESCRIPTION               => 'Stream Intervals available',
            self::TYPE                      => 'keyFieldConfig',
            self::CLIENTREGISTRYINCLUDE     => true,
            self::SETBYADMINMODULE          => false,
            self::DEFAULT_STR               => [
                self::RECORDS                   => [
                    ['system' => 1, 'id' => HumanResources_Model_StreamModality::INT_WEEKLY,      'value' => 'Weekly'], //_('Weekly')
                    ['system' => 1, 'id' => HumanResources_Model_StreamModality::INT_MONTHLY,     'value' => 'Monthly'], //_('Monthly')
                    ['system' => 1, 'id' => HumanResources_Model_StreamModality::INT_QUARTERLY,   'value' => 'Quarterly'], //_('Quarterly')
                    ['system' => 1, 'id' => HumanResources_Model_StreamModality::INT_YEARLY,      'value' => 'Yearly'], //_('Yearly')
                ],
            ],
        ],
        self::STREAM_TYPE => [
            //_('Stream Types available')
            self::LABEL                     => 'Stream Types available',
            self::DESCRIPTION               => 'Stream Types available',
            self::TYPE                      => 'keyFieldConfig',
            self::CLIENTREGISTRYINCLUDE     => true,
            self::SETBYADMINMODULE          => true,
            self::DEFAULT_STR               => [
                self::RECORDS                   => [
                    ['id' => 'velocity stream',     'value' => 'Velocity Stream'], //_('Velocity Stream')
                    ['id' => 'working stream',      'value' => 'Working Stream'], //_('Working Stream')
                    ['id' => 'daily business',      'value' => 'Daily Business'], //_('Daily Business')
                ],
            ],
        ],
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
     * 
     * @param string $accountYear
     * @return Tinebase_DateTime|NULL
     */
    public function getVacationExpirationDate($accountYear=null)
    {
        $accountYear = $accountYear ?: Tinebase_DateTime::now()->format('Y');
        $year = $accountYear+1;
        
        [$month, $day] = preg_split('/-/', $this->{self::VACATION_EXPIRES});
        return new Tinebase_DateTime("{$year}-{$month}-{$day} 00:00:00");
    }
}
