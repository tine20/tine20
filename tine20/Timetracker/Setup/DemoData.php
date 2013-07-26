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
     * 
     * required apps
     * @var array
     */
    protected static $_requiredApplications = array('Admin', 'HumanResources', 'Sales');
    
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
     * array of RecordSets with all contracts
     *
     * @var Tinebase_Record_RecordSet
     */
    protected $_contracts;
    
    /**
     * cc controller
     * 
     * @var Sales_Controller_CostCenter
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
        $this->_ccController  = Sales_Controller_CostCenter::getInstance();
        $this->_taController  = Timetracker_Controller_Timeaccount::getInstance();
        $this->_tsController  = Timetracker_Controller_Timesheet::getInstance();
        $this->_tsController->doContainerACLChecks(false);
        $this->_empController = HumanResources_Controller_Employee::getInstance();
        $this->_contractController    = Sales_Controller_Contract::getInstance();
        $this->_contracts = $this->_contractController->getAll();
        
        $this->_loadCostCenters();
        
        $filter = new HumanResources_Model_EmployeeFilter(array());
        $this->_employees = $this->_empController->search($filter);
        
        if (! $this->_costCenters || ! $this->_costCenters->count()) {
            die('No costcenters are available. Please call HumanResources.createDemoData before running this!');
        }
        
        // set start date to start date of june 1st before last year
        $date = Tinebase_DateTime::now();
        $this->_startDate = $date->setDate($date->format('Y') - 2, 6, 1)->setTime(8,0,0);

        // set clearedDate almost a month after
        $this->_clearedDate = clone $this->_startDate;
        $this->_clearedDate->addMonth(1)->subDay(2);
    }
    
    /**
     * creates shared tas
     */
    protected function _createSharedTimeaccounts()
    {
        // create 5 timeaccounts for each cc
        $taNumber = 1;
        
        $userGroup = Tinebase_Group::getInstance()->getGroupByName('Users');
        
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
            
            while ($i < 10) {
                $i++;
                
                $contract = $this->_contracts->getByIndex($contractsIndex);
                $title = (self::$_de ? 'Zeitkonto mit Kostenstelle ' : 'Timeaccount for costcenter ') . $costcenter->number . ' - ' . $costcenter->remark . (self::$_de ? ' mit Vertrag ' : ' with contract ') . $contract->number;
                
                
                if ($contractsIndex >= ($this->_contracts->count() - 1)) {
                    $contractsIndex = 0;
                } else {
                    $contractsIndex++;
                }
                
                $ta = new Timetracker_Model_Timeaccount(array(
                    'number' => $taNumber, 
                    'title'  => $title . ' ' . Tinebase_Record_Abstract::generateUID(3),
                    'grants' => $grants,
                    'status' => ($i%2 == 1) ? 'billed' : 'not yet billed',
                    'cleared_at' => ($i%2 == 1) ? $this->_clearedDate : NULL,
                    'budget' =>  ($i%3 == 1) ? 100 : NULL,
                    'description' => 'Created By Tine 2.0 DEMO DATA'
                ));
                
                $ta->relations = array(
                    array(
                        'own_model'              => 'Timetracker_Model_Timeaccount',
                        'own_backend'            => 'SQL',
                        'own_id'                 => NULL,
                        'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
                        'related_model'          => 'Sales_Model_CostCenter',
                        'related_backend'        => Tasks_Backend_Factory::SQL,
                        'related_id'             => $costcenter->getId(),
                        'type'                   => 'COST_CENTER'
                    ),
                    array(
                        'own_model'              => 'Timetracker_Model_Timeaccount',
                        'own_backend'            => 'SQL',
                        'own_id'                 => NULL,
                        'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
                        'related_model'          => 'Sales_Model_Contract',
                        'related_backend'        => Tasks_Backend_Factory::SQL,
                        'related_id'             => $contract->getId(),
                        'type'                   => 'TIME_ACCOUNT'
                    )
                );
                
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
     */
    protected function _getCurrentUsersCostCenter()
    {
        $employee = $this->_getCurrentUsersEmployee();
        $salesCC =  HumanResources_Controller_CostCenter::getInstance()->getValidCostCenter($employee->getId(), NULL, TRUE);
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
        $cc = $this->_getCurrentUsersCostCenter();
        $ownTimeAccountRecordSet = $this->_timeAccounts[$cc->getId()];
        $userId = Tinebase_Core::getUser()->accountId;
        
        $startDate = clone $this->_startDate;
        $endDate   = clone $startDate;
        $endDate->addMonth(3);
        
        $index     = 0;
        
        $mkccId = $this->_marketingCostCenter->getId();
        $deccId = $this->_developmentCostCenter->getId();
        
        $allMkccTimeAccountRecordSet = $this->_timeAccounts[$mkccId];
        $allDeccTimeAccountRecordSet = $this->_timeAccounts[$deccId];
        
        $afterCleared = FALSE;
        $stopIndex = 8;
        
        while ($startDate < $endDate) {
            $wd = (int) $startDate->format('w');
            
            if (! $afterCleared) {
                if ($startDate > $this->_clearedDate) {
                    $ownTimeAccountRecordSet = $ownTimeAccountRecordSet->filter('status', 'not yet billed');
                    $allMkccTimeAccountRecordSet = $allMkccTimeAccountRecordSet->filter('status', 'not yet billed');
                    $allDeccTimeAccountRecordSet = $allDeccTimeAccountRecordSet->filter('status', 'not yet billed');
                    
                    $stopIndex = 4;
                    if ($index > $stopIndex) {
                        $index = 0;
                    }
                    $afterCleared = TRUE;
                }
                
            }
            
            // create timesheets from tue -> fri, always a timeaccount of the users costcenter
            
            if (($wd < 6) && ($wd > 1)) {
                // 8 hrs
                $ta = $ownTimeAccountRecordSet->getByIndex($index);
                $ts = $this->_createTimesheet(array(
                    'account_id'     => $userId,
                    'timeaccount_id' => $ta->getId(),
                    'is_billable'    => false,
                    'start_date'     => $startDate,
                    'duration'       => 480,
                    'description'    => static::$_de ? 'DemoData eigenes Zeitkonto' : 'DemoData own timeaccount',
                    'is_cleared'     => (($ta->status == 'billed') ? true : false),
                    'cleared_time'   => (($ta->status == 'billed') ? $startDate : NULL)
                 ));
            } elseif ($wd == 1) {
                
                // create timesheets on monday
                $ta = $allMkccTimeAccountRecordSet->getByIndex($index);
                // 4 hrs for a marketing costcenter timeaccount
                $ts = $this->_createTimesheet(array(
                    'account_id'     => $userId,
                    'timeaccount_id' => $ta->getId(),
                    'is_billable'    => false,
                    'start_date'     => $startDate,
                    'duration'       => 240,
                    'description'    => static::$_de ? ('DemoData auf KST ' . $cc->number) : ('DemoData on costcenter ' . $cc->number),
                    'is_cleared'     => (($ta->status == 'billed') ? true : false),
                    'cleared_time'   => (($ta->status == 'billed') ? $startDate : NULL)
                ));
                
                $sd = clone $startDate;
                $sd->addMinute(300);
                $ta = $allDeccTimeAccountRecordSet->getByIndex($index);
                
                // 4 hrs for a development costcenter timeaccount
                $ts = $this->_createTimesheet(array(
                    'account_id'     => $userId,
                    'timeaccount_id' => $ta->getId(),
                    'is_billable'    => false,
                    'start_date'     => $sd,
                    'duration'       => 240,
                    'description'    => static::$_de ? ('DemoData auf KST ' . $cc->number) : ('DemoData on costcenter ' . $cc->number),
                    'is_cleared'     => (($ta->status == 'billed') ? true : false),
                    'cleared_time'   => (($ta->status == 'billed') ? $startDate : NULL)
                ));
            }
            
            $startDate->addDay(1);
            $index = ($index == $stopIndex) ? 0 : $index + 1;
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
