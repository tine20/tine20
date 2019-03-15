<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2019 Metaways Infosystems GmbH (http://www.metaways.de)
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
    /**
     * create favorites
     */
    protected function _initializeFavorites()
    {
        $pfe = Tinebase_PersistentFilter::getInstance();
        
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('HumanResources')->getId(),
            'model'             => 'HumanResources_Model_EmployeeFilter',
        );
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Currently employed employees", // _("Currently employed employees")
            'description'       => "Employees which are currently employed", // _("Employees which are currently employed")
            'filters'           => array(array('field' => 'is_employed', 'operator' => 'equals', 'value' => 1)),
        ))));
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "All employees", // _("All employees")
            'description'       => "All available employees", // _("All available employees")
            'filters'           => array(),
        ))));
        
        // Accounts
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('HumanResources')->getId(),
            'model'             => 'HumanResources_Model_AccountFilter',
        );
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "All accounts", // _("All accounts")
            'description'       => "All available accounts", // _("All available accounts")
            'filters'           => array(),
        ))));
    }
    
    /**
     * init example workingtime models
     */
    protected function _initializeWorkingTimeModels()
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
        $blPipe = $rs->toArray();

        $translate = Tinebase_Translation::getTranslation('HumanResources');
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
            'type'  => HumanResources_Model_WorkingTimeScheme::TYPES_SHARED,
            'json'  => ["days"=>[28800,28800,28800,28800,19800,0,0]],
            HumanResources_Model_WorkingTimeScheme::FLDS_BLPIPE => $blPipe,
        ));
        HumanResources_Controller_WorkingTimeScheme::getInstance()->create($_record);
        $_record = new HumanResources_Model_WorkingTimeScheme(array(
            'title' => $translate->_('Part-time 20 hours'),
            'working_hours' => '20',
            'type'  => HumanResources_Model_WorkingTimeScheme::TYPES_SHARED,
            'json'  => ["days"=>[14400,14400,14400,14400,14400,0,0]],
            HumanResources_Model_WorkingTimeScheme::FLDS_BLPIPE => $blPipe,
        ));
        HumanResources_Controller_WorkingTimeScheme::getInstance()->create($_record);
    }

    protected function _initializeWageTypes()
    {
        $wageTypes = [
            //['id' => '01', 'number' => '1000', 'name' => 'Default wage type', 'system' => true,  'wage_factor' => 100, 'additional_wage' => false], // _('Default wage type')
            ['id' => HumanResources_Model_WageType::ID_SALARY, 'number' => '2000', 'name' => 'Salary',            'system' => true,  'wage_factor' => 100, 'additional_wage' => false], // _('Salary')
            //['id' => '03', 'number' => '2500', 'name' => 'Business trip',     'system' => false, 'wage_factor' => 100, 'additional_wage' => false], // _('Business trip')
            //['id' => '04', 'number' => '3000', 'name' => 'Sunday bonus',      'system' => false, 'wage_factor' =>  50, 'additional_wage' => true ], // _('Sunday bonus')
            ['id' => HumanResources_Model_WageType::ID_FEAST, 'number' => '3100', 'name' => 'Feast day',      'system' => false, 'wage_factor' =>  100, 'additional_wage' => false], // _('Feast day')
            //['id' => '05', 'number' => '3200', 'name' => 'Feast day bonus',   'system' => false, 'wage_factor' => 125, 'additional_wage' => true ], // _('Feast day bonus')
            //['id' => '06', 'number' => '3400', 'name' => 'Overtime',          'system' => false, 'wage_factor' => 125, 'additional_wage' => false], // _('Overtime')
            //['id' => '07', 'number' => '3450', 'name' => 'Overtime bonus',    'system' => false, 'wage_factor' => 150, 'additional_wage' => false], // _('Overtime bonus')
            //['id' => '08', 'number' => '3600', 'name' => 'Late shift bonus',  'system' => false, 'wage_factor' => 105, 'additional_wage' => true ], // _('Late shift bonus')
            //['id' => '09', 'number' => '3800', 'name' => 'Night bonus',       'system' => false, 'wage_factor' => 115, 'additional_wage' => true ], // _('Night bonus')
            ['id' => HumanResources_Model_WageType::ID_VACATION, 'number' => '5000', 'name' => 'Vacation',          'system' => true,  'wage_factor' => 100, 'additional_wage' => false], // _('Vacation')
            ['id' => HumanResources_Model_WageType::ID_SICK, 'number' => '5500', 'name' => 'Sickness',          'system' => true,  'wage_factor' => 100, 'additional_wage' => false], // _('Sickness')
            //['id' => '11', 'number' => '6000', 'name' => 'Break',             'system' => true,  'wage_factor' =>   0, 'additional_wage' => false], // _('Break')
            //['id' => '12', 'number' => '7000', 'name' => 'Stand by',          'system' => false, 'wage_factor' =>  20, 'additional_wage' => false], // _('Stand by')
            //['id' => '13', 'number' => '9000', 'name' => 'Unpaid',            'system' => true,  'wage_factor' =>   0, 'additional_wage' => false], // _('Unpaid')
        ];

        $wtCntrl = HumanResources_Controller_WageType::getInstance();
        foreach ($wageTypes as $wt) {
            $wtCntrl->create(new HumanResources_Model_WageType($wt));
        }
    }

    protected function _initializeFreeTimeTypes()
    {
        $freeTimeTypes = [
            ['id' => '01', 'abbreviation' => '[S]', 'name' => '[S] Sickness',        'system' => true,  'wage_type' => HumanResources_Model_WageType::ID_SICK, 'allow_booking' => false, 'allow_planning' => true,  'enable_timetracking' => false],
            //['id' => '02', 'abbreviation' => '[D]', 'name' => '[D] Visit doctor',    'system' => false, 'wage_type' => '01', 'allow_booking' => true,  'allow_planning' => false, 'enable_timetracking' => true],
            ['id' => '03', 'abbreviation' => '[V]', 'name' => '[V] Vacation',        'system' => true,  'wage_type' => HumanResources_Model_WageType::ID_VACATION, 'allow_booking' => false, 'allow_planning' => true,  'enable_timetracking' => false],
            //['id' => '04', 'abbreviation' => '[BT]', 'name' => '[BT] Business trip',  'system' => false, 'wage_type' => '03', 'allow_booking' => true,  'allow_planning' => true,  'enable_timetracking' => true],
            //['id' => '05', 'abbreviation' => '[FD]', 'name' => '[FD] Feast day',      'system' => true,  'wage_type' => '01', 'allow_booking' => false, 'allow_planning' => true,  'enable_timetracking' => false],
            //['id' => '06', 'abbreviation' => '[FT]', 'name' => '[FT] Flex time',      'system' => true,  'wage_type' => '01', 'allow_booking' => true,  'allow_planning' => true,  'enable_timetracking' => false],
            //['id' => '07', 'abbreviation' => '[BK]', 'name' => '[BK] Break',          'system' => true,  'wage_type' => '13', 'allow_booking' => true,  'allow_planning' => false, 'enable_timetracking' => true],
            //['id' => '08', 'abbreviation' => '[T]', 'name' => '[T] Training',        'system' => true,  'wage_type' => '01', 'allow_booking' => false, 'allow_planning' => true,  'enable_timetracking' => false],
        ];

        $fttCntrl = HumanResources_Controller_FreeTimeType::getInstance();
        foreach ($freeTimeTypes as $ftt) {
            $fttCntrl->create(new HumanResources_Model_FreeTimeType($ftt));
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
        try {
            $basepath = Tinebase_FileSystem::getInstance()->getApplicationBasePath(
                'HumanResources',
                Tinebase_FileSystem::FOLDER_TYPE_SHARED
            );
            $node = Tinebase_FileSystem::getInstance()->createAclNode($basepath . '/Report Templates');
            HumanResources_Config::getInstance()->set(HumanResources_Config::REPORT_TEMPLATES_CONTAINER_ID, $node->getId());
        } catch (Tinebase_Exception_Backend $teb) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                . ' Could not create report template folder: ' . $teb);
        }
    }
}
