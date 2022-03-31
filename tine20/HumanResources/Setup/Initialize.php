<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_ModelConfiguration_Const as TMCC;

/**
 * class for Tinebase initialization
 *
 * @package     HumanResources
 */
class HumanResources_Setup_Initialize extends Setup_Initialize
{
    public static $freeTimeTypes = [
        // NOTE: no feastday type as feastdays are treated via feastday cal which is shared and not per user
        ['name' => '[S] Sickness',                               'system' => true,  'color' => '#F4D03F', 'wage_type' => HumanResources_Model_WageType::ID_SICK,          'allow_booking' => false, 'allow_planning' => true,  'enable_timetracking' => false, 'id' => HumanResources_Model_FreeTimeType::ID_SICKNESS], // gettext('[S] Sickness')
        ['name' => '[C] Sickness of Child',                      'system' => false, 'color' => '#F4D03F', 'wage_type' => HumanResources_Model_WageType::ID_SICK_CHILD,    'allow_booking' => false, 'allow_planning' => true,  'enable_timetracking' => false], // gettext('[C] Sickness of Child')
        ['name' => '[7] Sick pay - Sickness from 7nth week on',  'system' => false, 'color' => '#F4D03F', 'wage_type' => HumanResources_Model_WageType::ID_SICK_SICKPAY,  'allow_booking' => false, 'allow_planning' => true,  'enable_timetracking' => false], // gettext('[7] Sick pay - Sickness from 7nth week on')
        ['name' => '[V] Vacation',                               'system' => true,  'color' => '#2ECC71', 'wage_type' => HumanResources_Model_WageType::ID_VACATION,      'allow_booking' => false, 'allow_planning' => true,  'enable_timetracking' => false, 'id' => HumanResources_Model_FreeTimeType::ID_VACATION], // gettext('[V] Vacation')
        ['name' => '[P] Special Vacation',                       'system' => false, 'color' => '#58D68D', 'wage_type' => HumanResources_Model_WageType::ID_VACATION,      'allow_booking' => false, 'allow_planning' => true,  'enable_timetracking' => false], // gettext('[P] Special Vacation')
        ['name' => '[U] Unpaid Vacation',                        'system' => false, 'color' => '#82E0AA', 'wage_type' => HumanResources_Model_WageType::ID_VACATION,      'allow_booking' => false, 'allow_planning' => true,  'enable_timetracking' => false], // gettext('[U] Unpaid Vacation')
        ['name' => '[H] Short Business trip',                    'system' => false, 'color' => '#3498DB', 'wage_type' => HumanResources_Model_WageType::ID_SALARY,        'allow_booking' => true,  'allow_planning' => false, 'enable_timetracking' => true], // gettext('[S] Short Business trip')
        ['name' => '[B] Business trip',                          'system' => false, 'color' => '#2E86C1', 'wage_type' => HumanResources_Model_WageType::ID_SALARY,        'allow_booking' => true,  'allow_planning' => true,  'enable_timetracking' => false], // gettext('[B] Business trip')
        ['name' => '[D] Visit doctor',                           'system' => false, 'color' => '#F7DC6F', 'wage_type' => HumanResources_Model_WageType::ID_SALARY,        'allow_booking' => true,  'allow_planning' => false, 'enable_timetracking' => true], // gettext('[D] Visit doctor')
        ['name' => '[F] Flex time reduction',                    'system' => true,  'color' => '#27AE60', 'wage_type' => HumanResources_Model_WageType::ID_NO_WAGE,       'allow_booking' => false, 'allow_planning' => true,  'enable_timetracking' => false], // gettext('[F] Flex time reduction')
        ['name' => '[R] Break',                                  'system' => true,  'color' => '#ABEBC6', 'wage_type' => HumanResources_Model_WageType::ID_NO_WAGE,       'allow_booking' => true,  'allow_planning' => false, 'enable_timetracking' => true], // gettext('[R] Break')
        ['name' => '[T] Training',                               'system' => true,  'color' => '#5DADE2', 'wage_type' => HumanResources_Model_WageType::ID_SALARY,        'allow_booking' => false, 'allow_planning' => true,  'enable_timetracking' => true], // gettext('[T] Training')
    ];

    public static function addAttendanceRecorderDevices()
    {
        $arDeviceCtrl = HumanResources_Controller_AttendanceRecorderDevice::getInstance();

        $tineWorkingTimeDevice = $arDeviceCtrl->create(new HumanResources_Model_AttendanceRecorderDevice([
            'id' => HumanResources_Model_AttendanceRecorderDevice::SYSTEM_WORKING_TIME_ID,
            HumanResources_Model_AttendanceRecorderDevice::FLD_NAME => 'tine system working time',
        ]));

        $tineProjectTimeDevice = $arDeviceCtrl->create(new HumanResources_Model_AttendanceRecorderDevice([
            'id' => HumanResources_Model_AttendanceRecorderDevice::SYSTEM_PROJECT_TIME_ID,
            HumanResources_Model_AttendanceRecorderDevice::FLD_NAME => 'tine system project time',
            HumanResources_Model_AttendanceRecorderDevice::FLD_ALLOW_MULTI_START => true,
            HumanResources_Model_AttendanceRecorderDevice::FLD_STARTS => [
                new HumanResources_Model_AttendanceRecorderDeviceRef([
                    HumanResources_Model_AttendanceRecorderDeviceRef::FLD_DEVICE_ID => $tineWorkingTimeDevice->getId(),
                ], true),
            ],
            HumanResources_Model_AttendanceRecorderDevice::FLD_BLPIPE => new Tinebase_Record_RecordSet(
                HumanResources_Model_BLAttendanceRecorder_Config::class, [
                    new HumanResources_Model_BLAttendanceRecorder_Config([
                        HumanResources_Model_BLAttendanceRecorder_Config::FLDS_CLASSNAME => HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::class,
                        HumanResources_Model_BLAttendanceRecorder_Config::FLDS_CONFIG_RECORD => new HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig([
                            HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::FLD_ALLOW_OTHER_TA => true,
                        ])
                    ]),
                ]),
        ]));

        $tineWorkingTimeDevice->{HumanResources_Model_AttendanceRecorderDevice::FLD_STOPS} =
            new Tinebase_Record_RecordSet(HumanResources_Model_AttendanceRecorderDeviceRef::class, [
                new HumanResources_Model_AttendanceRecorderDeviceRef([
                    HumanResources_Model_AttendanceRecorderDeviceRef::FLD_DEVICE_ID => $tineProjectTimeDevice->getId(),
                ], true),
            ]);
        $tineWorkingTimeDevice->{HumanResources_Model_AttendanceRecorderDevice::FLD_PAUSES} =
            new Tinebase_Record_RecordSet(HumanResources_Model_AttendanceRecorderDeviceRef::class, [
                new HumanResources_Model_AttendanceRecorderDeviceRef([
                    HumanResources_Model_AttendanceRecorderDeviceRef::FLD_DEVICE_ID => $tineProjectTimeDevice->getId(),
                ], true),
            ]);
        $tineWorkingTimeDevice->{HumanResources_Model_AttendanceRecorderDevice::FLD_UNPAUSES} =
            new Tinebase_Record_RecordSet(HumanResources_Model_AttendanceRecorderDeviceRef::class, [
                new HumanResources_Model_AttendanceRecorderDeviceRef([
                    HumanResources_Model_AttendanceRecorderDeviceRef::FLD_DEVICE_ID => $tineProjectTimeDevice->getId(),
                ], true),
            ]);
        $tineWorkingTimeDevice->{HumanResources_Model_AttendanceRecorderDevice::FLD_BLPIPE} = new Tinebase_Record_RecordSet(
            HumanResources_Model_BLAttendanceRecorder_Config::class, [
                new HumanResources_Model_BLAttendanceRecorder_Config([
                    HumanResources_Model_BLAttendanceRecorder_Config::FLDS_CLASSNAME => HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::class,
                    HumanResources_Model_BLAttendanceRecorder_Config::FLDS_CONFIG_RECORD => new HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig([
                        HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::FLD_ALLOW_OTHER_TA => false,
                        HumanResources_Model_BLAttendanceRecorder_TimeSheetConfig::FLD_FILL_GAPS_OF_DEVICES => [
                            $tineProjectTimeDevice->getId()
                        ],
                    ])
                ]),
            ]);
        $arDeviceCtrl->update($tineWorkingTimeDevice);
    }

    protected function _initializeAttendanceRecorderDevices()
    {
        static::addAttendanceRecorderDevices();
    }

    /**
     * create favorites
     */
    protected function _initializeFavorites()
    {
        $pfe = Tinebase_PersistentFilter::getInstance();

        $translate = Tinebase_Translation::getDefaultTranslation(HumanResources_Config::APP_NAME);
        
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('HumanResources')->getId(),
            'model'             => 'HumanResources_Model_EmployeeFilter',
        );
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => $translate->_("Currently employed employees"),
            'description'       => $translate->_("Employees which are currently employed"),
            'filters'           => array(array('field' => 'is_employed', 'operator' => 'equals', 'value' => 1)),
        ))));
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => $translate->_("All employees"),
            'description'       => $translate->_("All available employees"),
            'filters'           => array(),
        ))));
        
        // Accounts
        $commonValues['model'] = 'HumanResources_Model_AccountFilter';
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => $translate->_("All accounts"),
            'description'       => $translate->_("All available accounts"),
            'filters'           => array(),
        ))));

        // Divisions
        $commonValues['model'] = HumanResources_Model_Division::class . 'Filter';

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "All Divisions", // _('All Divisions')
                'description' => "All division records", // _('All division records')
                'filters' => array(),
            ))
        ));

        // FreeTime
        static::addFreeTimePersistenFilter();

        // Daily/Monthly WTR
        static::addWTRCorrectionPersistentFilter();
    }

    public static function addWTRCorrectionPersistentFilter()
    {
        $pfe = Tinebase_PersistentFilter::getInstance();
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('HumanResources')->getId(),
            'model'             => HumanResources_Model_DailyWTReport::class,
        );

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "Daily WTR Corretions Requested", // _('Daily WTR Corretions Requested')
                'description' => "Daily WTR Corretions Requested", // _('Daily WTR Corretions Requested')
                'filters' => [
                    ['field' => HumanResources_Model_MonthlyWTReport::FLDS_CORRECTIONS, 'operator' => 'definedBy', 'value' => [
                        ['field' => 'status', 'operator' => 'equals', 'value' => HumanResources_Config::WTR_CORRECTION_STATUS_REQUESTED],
                    ]],
                ],
            ))
        ));

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "Monthly WTR Corretions Requested", // _('Monthly WTR Corretions Requested')
                'description' => "Monthly WTR Corretions Requested", // _('Monthly WTR Corretions Requested')
                'filters' => [
                    ['field' => HumanResources_Model_MonthlyWTReport::FLDS_CORRECTIONS, 'operator' => 'definedBy', 'value' => [
                        ['field' => 'status', 'operator' => 'equals', 'value' => HumanResources_Config::WTR_CORRECTION_STATUS_REQUESTED],
                    ]],
                ],
            ))
        ));
    }

    public static function addFreeTimePersistenFilter()
    {
        $pfe = Tinebase_PersistentFilter::getInstance();
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('HumanResources')->getId(),
            'model'             => HumanResources_Model_FreeTime::class . 'Filter',
        );

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "All Free Times", // _('All Free Times')
                'description' => "All free time records", // _('All free time records')
                'filters' => [],
            ))
        ));

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "All Free Times this year", // _('All Free Times this year')
                'description' => "All Free Times this year", // _('All Free Times this year')
                'filters' => [
                    'condition' => Tinebase_Model_Filter_FilterGroup::CONDITION_OR,
                    'filters' => [
                        ['field' => 'firstday_date', 'operator' => 'within', 'value' => 'yearThis'],
                        ['field' => 'lastday_date', 'operator' => 'within', 'value' => 'yearThis'],
                    ],
                ],
            ))
        ));

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "All Free Times next year", // _('All Free Times next year')
                'description' => "All Free Times next year", // _('All Free Times next year')
                'filters' => [
                    'condition' => Tinebase_Model_Filter_FilterGroup::CONDITION_OR,
                    'filters' => [
                        ['field' => 'firstday_date', 'operator' => 'within', 'value' => 'yearNext'],
                        ['field' => 'lastday_date', 'operator' => 'within', 'value' => 'yearNext'],
                    ],
                ],
            ))
        ));

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "All Free Times requested", // _('All Free Times requested')
                'description' => "All Free Times requested", // _('All Free Times requested')
                'filters' => [
                    ['field' => HumanResources_Model_FreeTime::FLD_PROCESS_STATUS, 'operator' => 'equals', 'value' => HumanResources_Config::FREE_TIME_PROCESS_STATUS_REQUESTED],
                ],
            ))
        ));
    }

    /**
     * @return Tinebase_Record_RecordSet
     * @throws Tinebase_Exception_InvalidArgument
     */
    public static function getDefaultWTS_BL()
    {
        $rs = new Tinebase_Record_RecordSet(HumanResources_Model_BLDailyWTReport_Config::class, [
            [
                Tinebase_Model_BLConfig::FLDS_CLASSNAME     =>
                    HumanResources_Model_BLDailyWTReport_LimitWorkingTimeConfig::class,
                Tinebase_Model_BLConfig::FLDS_CONFIG_RECORD => [
                    HumanResources_Model_BLDailyWTReport_LimitWorkingTimeConfig::FLDS_START_TIME    => '07:00:00',
                    HumanResources_Model_BLDailyWTReport_LimitWorkingTimeConfig::FLDS_END_TIME      => '21:00:00'
                ]
            ], [
                Tinebase_Model_BLConfig::FLDS_CLASSNAME     =>
                    HumanResources_Model_BLDailyWTReport_BreakTimeConfig::class,
                Tinebase_Model_BLConfig::FLDS_CONFIG_RECORD => [
                    HumanResources_Model_BLDailyWTReport_BreakTimeConfig::FLDS_TIME_WORKED          => 4 * 3600,
                    HumanResources_Model_BLDailyWTReport_BreakTimeConfig::FLDS_BREAK_TIME           => 1800,
                ]
            ], [
                Tinebase_Model_BLConfig::FLDS_CLASSNAME     =>
                    HumanResources_Model_BLDailyWTReport_BreakTimeConfig::class,
                Tinebase_Model_BLConfig::FLDS_CONFIG_RECORD => [
                    HumanResources_Model_BLDailyWTReport_BreakTimeConfig::FLDS_TIME_WORKED          => 6 * 3600,
                    HumanResources_Model_BLDailyWTReport_BreakTimeConfig::FLDS_BREAK_TIME           => 900,
                ]
            ]
        ]);
        $rs->runConvertToRecord();

        return $rs;
    }

    public static function createWorkingTimeModels()
    {

        $blPipe = static::getDefaultWTS_BL()->toArray();

        $translate = Tinebase_Translation::getDefaultTranslation(HumanResources_Config::APP_NAME);
        $_record = new HumanResources_Model_WorkingTimeScheme(array(
            'title' => $translate->_('Full-time 40 hours'),
            'working_hours' => '40',
            'json'  => ["days"=>[28800,28800,28800,28800,28800,0,0]],
            'type'  => HumanResources_Model_WorkingTimeScheme::TYPES_SHARED,
            HumanResources_Model_WorkingTimeScheme::FLDS_BLPIPE => $blPipe,
        ));
        HumanResources_Controller_WorkingTimeScheme::getInstance()->create($_record);
        $_record = new HumanResources_Model_WorkingTimeScheme(array(
            'title' => $translate->_('Full-time 37.5 hours'),
            'working_hours' => '37.5',
            'type'  => HumanResources_Model_WorkingTimeScheme::TYPES_TEMPLATE,
            'json'  => ["days"=>[28800,28800,28800,28800,19800,0,0]],
            HumanResources_Model_WorkingTimeScheme::FLDS_BLPIPE => $blPipe,
        ));
        HumanResources_Controller_WorkingTimeScheme::getInstance()->create($_record);
        $_record = new HumanResources_Model_WorkingTimeScheme(array(
            'title' => $translate->_('Part-time 20 hours'),
            'working_hours' => '20',
            'type'  => HumanResources_Model_WorkingTimeScheme::TYPES_TEMPLATE,
            'json'  => ["days"=>[14400,14400,14400,14400,14400,0,0]],
            HumanResources_Model_WorkingTimeScheme::FLDS_BLPIPE => $blPipe,
        ));
        HumanResources_Controller_WorkingTimeScheme::getInstance()->create($_record);
    }

    /**
     * init example workingtime models
     */
    protected function _initializeWorkingTimeModels()
    {
        static::createWorkingTimeModels();
    }

    protected function _initializeWageTypes()
    {
        static::createtWageTypes();
    }

    public static function createtWageTypes($throw = true)
    {
        $translate = Tinebase_Translation::getDefaultTranslation(HumanResources_Config::APP_NAME);
        $wageTypes = [
            ['number' => '1000', 'name' => $translate->_('No wage'),                                'system' => false,  'wage_factor' => 0,   'additional_wage' => false, 'id' => HumanResources_Model_WageType::ID_NO_WAGE],
            ['number' => '2000', 'name' => $translate->_('Salary'),                                 'system' => true,   'wage_factor' => 100, 'additional_wage' => false, 'id' => HumanResources_Model_WageType::ID_SALARY],
            //['number' => '2500', 'name' => $translate->_('Business trip'),     'system' => false, 'wage_factor' => 100, 'additional_wage' => false],
            //['number' => '3000', 'name' => $translate->_('Sunday bonus'),      'system' => false, 'wage_factor' =>  50, 'additional_wage' => true ],
            ['number' => '3100', 'name' => $translate->_('Feast day'),                              'system' => true, 'wage_factor' =>  100, 'additional_wage' => false, 'id' => HumanResources_Model_WageType::ID_FEAST],
            //['number' => '3200', 'name' => $translate->_('Feast day bonus'),   'system' => false, 'wage_factor' => 125, 'additional_wage' => true ],
            //['number' => '3400', 'name' => $translate->_('Overtime'),          'system' => false, 'wage_factor' => 125, 'additional_wage' => false],
            //['number' => '3450', 'name' => $translate->_('Overtime bonus'),    'system' => false, 'wage_factor' => 150, 'additional_wage' => false],
            //['number' => '3600', 'name' => $translate->_('Late shift bonus'),  'system' => false, 'wage_factor' => 105, 'additional_wage' => true ],
            //['number' => '3800', 'name' => $translate->_('Night bonus'),       'system' => false, 'wage_factor' => 115, 'additional_wage' => true ],
            ['number' => '5000', 'name' => $translate->_('Vacation'),                               'system' => true,   'wage_factor' => 100, 'additional_wage' => false, 'id' => HumanResources_Model_WageType::ID_VACATION],
            ['number' => '5100', 'name' => $translate->_('Special Vacation'),                       'system' => false,  'wage_factor' => 0,   'additional_wage' => false, 'id' => HumanResources_Model_WageType::ID_SPECIAL_VACATION],
            ['number' => '5200', 'name' => $translate->_('Unpaid Vacation'),                        'system' => false,  'wage_factor' => 0,   'additional_wage' => false, 'id' => HumanResources_Model_WageType::ID_UNPAID_VACATION],
            ['number' => '5500', 'name' => $translate->_('Sickness'),                               'system' => true,   'wage_factor' => 100, 'additional_wage' => false, 'id' => HumanResources_Model_WageType::ID_SICK],
            ['number' => '5600', 'name' => $translate->_('Sick pay - Sickness of Child'),           'system' => false,  'wage_factor' => 0,   'additional_wage' => false, 'id' => HumanResources_Model_WageType::ID_SICK_CHILD],
            ['number' => '5700', 'name' => $translate->_('Sick pay - Sickness from 7nth week on'),  'system' => false,  'wage_factor' => 0,   'additional_wage' => false, 'id' => HumanResources_Model_WageType::ID_SICK_SICKPAY],
            //['number' => '6000', 'name' => $translate->_('Break'),             'system' => true,  'wage_factor' =>   0, 'additional_wage' => false],
            //['number' => '7000', 'name' => $translate->_('Stand by'),          'system' => false, 'wage_factor' =>  20, 'additional_wage' => false],
            ['number' => '9000', 'name' => $translate->_('Special Payment'),                        'system' => false,  'wage_factor' => 50,  'additional_wage' => true,  'id' => HumanResources_Model_WageType::ID_SPECIAL_PAYMENT],
        ];

        $wtCntrl = HumanResources_Controller_WageType::getInstance();
        foreach ($wageTypes as $wt) {
            try {
                $wtCntrl->create(new HumanResources_Model_WageType($wt));
            } catch (Exception $e) {
                if ($throw) throw $e;
            }
        }
    }

    protected function _initializeFreeTimeTypes()
    {
        static::createFreeTimeTypes();
    }

    public static function createFreeTimeTypes($throw = true)
    {
        $translate = Tinebase_Translation::getTranslation(HumanResources_Config::APP_NAME);
        $fttCntrl = HumanResources_Controller_FreeTimeType::getInstance();
        foreach (self::$freeTimeTypes as $ftt) {
            $ftt['name'] = $translate->_($ftt['name']);
            $ftt['abbreviation'] = preg_replace(['/.*\[/', '/\].*/'], '', $ftt['name']);
            try {
                $fttCntrl->create(new HumanResources_Model_FreeTimeType($ftt));
            } catch (Exception $e) {
                if ($throw) throw $e;
            }
        }
    }
    

    /**
     * init application folders
     */
    protected function _initializeFolders()
    {
        self::createReportTemplatesFolder();
    }

    /**
     * init scheduler tasks
     */
    protected function _initializeSchedulerTasks()
    {
        $scheduler = Tinebase_Core::getScheduler();
        HumanResources_Scheduler_Task::addCalculateDailyWorkingTimeReportsTask($scheduler);
    }

    protected function _initializeCORSystemCustomField()
    {
        static::addCORSystemCustomField();
    }

    public static function addCORSystemCustomField()
    {
        $appId = Tinebase_Application::getInstance()->getApplicationByName(Timetracker_Config::APP_NAME)->getId();

        Tinebase_CustomField::getInstance()->addCustomField(new Tinebase_Model_CustomField_Config([
            'name' => HumanResources_Model_FreeTimeType::TT_TS_SYSCF_CLOCK_OUT_REASON,
            'application_id' => $appId,
            'model' => Timetracker_Model_Timesheet::class,
            'is_system' => true,
            'definition' => [
                Tinebase_Model_CustomField_Config::DEF_FIELD => [
                    TMCC::LABEL             => 'Clock out reason',
                    TMCC::TYPE              => TMCC::TYPE_RECORD,
                    TMCC::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => true,],
                    TMCC::NULLABLE          => true,
                    TMCC::CONFIG            => [
                        TMCC::APP_NAME          => HumanResources_Config::APP_NAME,
                        TMCC::MODEL_NAME        => HumanResources_Model_FreeTimeType::MODEL_NAME_PART,
                    ],
                ],
            ]
        ], true));
    }

    /**
     * create reporting templates folder
     */
    public static function createReportTemplatesFolder()
    {
        $basepath = Tinebase_FileSystem::getInstance()->getApplicationBasePath(
            'HumanResources',
            Tinebase_FileSystem::FOLDER_TYPE_SHARED
        );
        if (Tinebase_FileSystem::getInstance()->isDir($basepath . '/Report Templates')) {
            $node = Tinebase_FileSystem::getInstance()->stat($basepath . '/Report Templates');
        } else {
            $node = Tinebase_FileSystem::getInstance()->createAclNode($basepath . '/Report Templates');
        }
        HumanResources_Config::getInstance()->set(HumanResources_Config::REPORT_TEMPLATES_CONTAINER_ID, $node->getId());
    }
}
