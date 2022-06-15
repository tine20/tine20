<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Timetracker initialization
 *
 * @package     Setup
 */
class Timetracker_Setup_DemoData extends Tinebase_Setup_DemoData_Abstract
{
    /**
     * required apps
     * 
     * @var array
     */
    protected static $_requiredApplications = array('Admin', 'Sales', 'HumanResources');
    
    /**
     * holds the instance of the singleton
     *
     * @var Timetracker_Setup_DemoData
     */
    private static $_instance = NULL;
    
    /**
     * the application name to work on
     * 
     * @var string
     */
    protected $_appName = 'Timetracker';
    
    /**
     * @var Timetracker_Controller_Timeaccount
     */
    protected $_taController;
    
    /**
     * 
     * @var Tinebase_DateTime
     */
    protected $_startDate;
    
    /**
     * @var Timetracker_Controller_Timesheet
     */
    protected $_tsController;
    
    /**
     * @var Tinebase_DateTime
     */
    protected $_clearedDate;
    
    /**
     * RecordSet with all employees
     *
     * @var Tinebase_Record_RecordSet
     */
    protected $_employees;
    
    /**
     * @var HumanResources_Controller_Employee
     */
    protected $_empController;
    
    /**
     * array of RecordSets with all timetracker.timeaccounts
     *
     * @var Tinebase_Record_RecordSet
     */
    protected $_timeAccounts;
    
    /**
     * array of RecordSets with all contracts having the development costcenter assigned
     *
     * @var Tinebase_Record_RecordSet
     */
    protected $_contractsDevelopment;

    /**
     * array of RecordSets with all contracts having the marketing costcenter assigned
     *
     * @var Tinebase_Record_RecordSet
     */
    protected $_contractsMarketing;
    
    /**
     * cc controller
     * 
     * @var Tinebase_Controller_CostCenter
     */
    protected $_ccController;
    
    /**
     * The contract controller
     * 
     * @var Sales_Controller_Contract
     */
    protected $_contractController = NULL;
    
    /**
     * models to work on
     * 
     * @var array
     */
    protected $_models = array('timeaccount', 'timesheet');
    
    /**
     * the constructor
     *
     */
    private function __construct()
    {
    }

    /**
     * the singleton pattern
     *
     * @return Timetracker_Setup_DemoData
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }

    /**
     * unsets the instance to save memory, be aware that hasBeenRun still needs to work after unsetting!
     *
     */
    public function unsetInstance()
    {
        if (self::$_instance !== NULL) {
            self::$_instance = null;
        }
    }

    /**
     * this is required for other applications needing demo data of this application
     * if this returns true, this demodata has been run already
     * 
     * @return boolean
     */
    public static function hasBeenRun()
    {
        $c = Timetracker_Controller_Timeaccount::getInstance();
        
        $f = new Timetracker_Model_TimeaccountFilter(array(
            array('field' => 'description', 'operator' => 'equals', 'value' => 'Created By Tine 2.0 DemoData'),
        ), 'AND');
        
        return ($c->search($f)->count() > 9) ? true : false;
    }
    
    /**
     * @see Tinebase_Setup_DemoData_Abstract
     * 
     */
    protected function _beforeCreate()
    {
        $this->_ccController  = Tinebase_Controller_CostCenter::getInstance();
        $this->_taController  = Timetracker_Controller_Timeaccount::getInstance();
        $this->_taController->sendNotifications(FALSE);
        $this->_tsController  = Timetracker_Controller_Timesheet::getInstance();
        $this->_tsController->sendNotifications(FALSE);
        $this->_tsController->doContainerACLChecks(false);
        
        $this->_contractController    = Sales_Controller_Contract::getInstance();
        $contracts = $this->_contractController->getAll();
        $developmentString = self::$_de ? 'Entwicklung' : 'Development';
        
        $this->_contractsDevelopment = $contracts->filter('title', '/.' . $developmentString . '/', TRUE);
        $this->_contractsMarketing = $contracts->filter('title', '/.Marketing/', TRUE);
        
        $this->_loadCostCentersAndDivisions();

        if (Tinebase_Application::getInstance()->isInstalled('HumanResources')) {
            $this->_empController = HumanResources_Controller_Employee::getInstance();
            $filter = new HumanResources_Model_EmployeeFilter(array());
            $this->_employees = $this->_empController->search($filter);

            HumanResources_Controller_DailyWTReport::getInstance()->suspendEvents();
        }
        
        // set start date to start date of june 1st before last year
        $date = Tinebase_DateTime::now();
        $this->_startDate = $date->subMonth(3)->setTime(8,0,0);

        // set clearedDate almost a month after
        $this->_clearedDate = clone $this->_startDate;
        $this->_clearedDate->addMonth(1)->subDay(2);
    }

    protected function _afterCreate()
    {
        parent::_afterCreate();
        if (Tinebase_Application::getInstance()->isInstalled('HumanResources')) {
            HumanResources_Controller_DailyWTReport::getInstance()->resumeEvents();
        }
    }

    /**
     * creates shared tas
     */
    protected function _createSharedTimeaccounts()
    {
        // create 2 timeaccounts for each cc
        $taNumber = 1;
        
        $userGroup = Tinebase_Group::getInstance()->getGroupByName('Users');
        $developmentString = self::$_de ? 'Entwicklung' : 'Development';
        if (! $userGroup) {
            die('Could not find userGroup "Users", stopping.');
        }
        
        $grants = array(array(
            'account_id' => $userGroup->getId(),
            'account_type' => 'group',
            'bookOwnGrant' => TRUE,
            'viewAllGrant' => TRUE,
            'bookAllGrant' => TRUE,
            // TODO: this shouldn't be neccessary, but is. why?:
            'manageBillableGrant' => TRUE,
            'exportGrant' => TRUE,
            'adminGrant' => TRUE
        ));
        
        $contractsIndex = 0;
        
        foreach($this->_costCenters as $costcenter) {
            
            $this->_timeAccounts[$costcenter->getId()] = new Tinebase_Record_RecordSet('Timetracker_Model_Timeaccount');
            $i=0;
            
            while ($i < 2) {
                $i++;
                
                $ta = new Timetracker_Model_Timeaccount(array(
                    'number'      => $taNumber,
                    'title'       => Tinebase_Record_Abstract::generateUID(3),
                    'grants'      => $grants,
                    'status'      => 'billed',
                    'cleared_at'  => $this->_clearedDate,
                    'budget'      => NULL,
                    'description' => 'Created By Tine 2.0 DEMO DATA'
                ));
                
                if (($costcenter->name == 'Marketing') || ($costcenter->name == $developmentString)) {
                    $contract = $costcenter->name == 'Marketing' ? $this->_contractsMarketing->getByIndex(rand(0, ($this->_contractsMarketing->count() -1))) : $this->_contractsDevelopment->getByIndex(rand(0, ($this->_contractsDevelopment->count() -1)));
                    
                    $ta->budget = $costcenter->name == 'Marketing' ? 100 : NULL;
                    $ta->relations = array(
                        array(
                            'own_model'              => 'Timetracker_Model_Timeaccount',
                            'own_backend'            => 'SQL',
                            'own_id'                 => NULL,
                            'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
                            'related_model'          => Tinebase_Model_CostCenter::class,
                            'related_backend'        => Tasks_Backend_Factory::SQL,
                            'related_id'             => $costcenter->getId(),
                            'type'                   => 'COST_CENTER'
                        ),
                        array(
                            'own_model'              => 'Timetracker_Model_Timeaccount',
                            'own_backend'            => 'SQL',
                            'own_id'                 => NULL,
                            'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
                            'related_model'          => 'Sales_Model_Contract',
                            'related_backend'        => Tasks_Backend_Factory::SQL,
                            'related_id'             => $contract->getId(),
                            'type'                   => 'TIME_ACCOUNT'
                        ));
                    $ta->title = (self::$_de ? 'Zeitkonto mit ' : 'Timeaccount for ') . $contract->getTitle();
                } else {
                    $ta->title = (self::$_de ? 'Zeitkonto mit KST ' : 'Timeaccount for CC ') . $costcenter->getTitle();
                    $ta->relations = array(
                        array(
                            'own_model'              => 'Timetracker_Model_Timeaccount',
                            'own_backend'            => 'SQL',
                            'own_id'                 => NULL,
                            'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
                            'related_model'          => Tinebase_Model_CostCenter::class,
                            'related_backend'        => Tasks_Backend_Factory::SQL,
                            'related_id'             => $costcenter->getId(),
                            'type'                   => 'COST_CENTER'
                        )
                    );
                }
                
                $this->_timeAccounts[$costcenter->getId()]->addRecord($this->_taController->create($ta));
                
                $taNumber++;
            }
        }
    }
    
    /**
     * creates and persists a timesheet
     * 
     * @param array $data
     * @return Tinebase_Record_Interface
     */
    protected function _createTimesheet($data)
    {
        $ts = new Timetracker_Model_Timesheet($data);
        return $this->_tsController->create($ts);
    }
    
    /**
     * returns the cost center for the current account
     *
     * @return HumanResources_Model_CostCenter|Tinebase_Model_CostCenter
     */
    protected function _getCurrentUsersCostCenter()
    {
        $employee = $this->_getCurrentUsersEmployee();
        if (! $employee) {
            throw new Tinebase_Exception_UnexpectedValue('current employee not found! did you delete any contacts?');
        }

        $salesCC = HumanResources_Controller_CostCenter::getInstance()->getValidCostCenter($employee->getId(), NULL, TRUE);
        return $salesCC;
    }
    
    /**
     * returns the employee for the current account
     */
    protected function _getCurrentUsersEmployee()
    {
        return $this->_employees->filter('account_id', Tinebase_Core::getUser()->accountId)->getFirstRecord();
    }
    
    /**
     * creates timesheets
     */
    protected function _createTimesheets()
    {
        if (Tinebase_Application::getInstance()->isInstalled('HumanResources')) {
            $cc = $this->_getCurrentUsersCostCenter();
        } else {
            $cc = $this->_costCenters->filter('name', 'Management')->getFirstRecord();
        }
        
        
        $ownTimeAccountRecordSet = $this->_timeAccounts[$cc->getId()]->sort('number');
        $userId = Tinebase_Core::getUser()->accountId;
        
        $startDate = clone $this->_startDate;
        $endDate   = clone $startDate;
        $endDate->addMonth(3);
        
        $mkccId = $this->_marketingCostCenter->getId();
        $deccId = $this->_developmentCostCenter->getId();
        
        $allMkccTimeAccountRecordSet = $this->_timeAccounts[$mkccId]->sort('number');
        $allDeccTimeAccountRecordSet = $this->_timeAccounts[$deccId]->sort('number');
        
        $afterCleared = FALSE;
        $stopIndex = 8;
        
        while ($startDate < $endDate) {
            $wd = (int) $startDate->format('w');
            
            // create timesheets from tue -> fri, always a timeaccount of the users costcenter
            if (($wd < 6) && ($wd > 2)) {
                // 8 hrs
                $ta = $ownTimeAccountRecordSet->getByIndex(rand(0, ($ownTimeAccountRecordSet->count() - 1)));
                $ts = $this->_createTimesheet(array(
                    'account_id'     => $userId,
                    'timeaccount_id' => $ta->getId(),
                    'is_billable'    => false,
                    'start_date'     => $startDate,
                    'duration'       => 480,
                    'description'    => static::$_de ? 'DemoData eigenes Zeitkonto' : 'DemoData own timeaccount',
                    'is_cleared'     => (($ta->status == 'billed') ? true : false),
                 ));
            } else {
                
                // create timesheets on monday and tuesday
                $ta = $allMkccTimeAccountRecordSet->getByIndex(rand(0, ($allMkccTimeAccountRecordSet->count() - 1)));
                // 4 hrs for a marketing costcenter timeaccount
                $ts = $this->_createTimesheet(array(
                    'account_id'     => $userId,
                    'timeaccount_id' => $ta->getId(),
                    'is_billable'    => false,
                    'start_date'     => $startDate,
                    'duration'       => 240,
                    'description'    => static::$_de ? ('DemoData auf KST ' . $cc->number) : ('DemoData on costcenter ' . $cc->number),
                    'is_cleared'     => (($ta->status == 'billed') ? true : false),
                ));
                
                $sd = clone $startDate;
                $sd->addMinute(300);
                $ta = $allDeccTimeAccountRecordSet->getByIndex(rand(0, ($allDeccTimeAccountRecordSet->count() - 1)));
                
                // 4 hrs for a development costcenter timeaccount
                $ts = $this->_createTimesheet(array(
                    'account_id'     => $userId,
                    'timeaccount_id' => $ta->getId(),
                    'is_billable'    => false,
                    'start_date'     => $sd,
                    'duration'       => 240,
                    'description'    => static::$_de ? ('DemoData auf KST ' . $cc->number) : ('DemoData on costcenter ' . $cc->number),
                    'is_cleared'     => (($ta->status == 'billed') ? true : false),
                ));
            }
        
            $startDate->addDay(1);
        }
    }
    
    /**
     * @see parent
     */
    protected function _createTimesheetsForPwulf()
    {
        $this->_createTimesheets();
    }

    /**
     * @see parent
     */
    protected function _createTimesheetsForRwright()
    {
        $this->_createTimesheets();
    }

    /**
     * @see parent
     */
    protected function _createTimesheetsForSclever()
    {
        $this->_createTimesheets();
    }
    
    /**
     * @see parent
     */
    protected function _createTimesheetsForJmcblack()
    {
        $this->_createTimesheets();
    }
    
    /**
     * @see parent
     */
    protected function _createTimesheetsForJsmith()
    {
        $this->_createTimesheets();
    }
}
