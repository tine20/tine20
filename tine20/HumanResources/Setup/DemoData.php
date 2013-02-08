<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for HumanResources initialization
 *
 * @package     Setup
 */
class HumanResources_Setup_DemoData extends Tinebase_Setup_DemoData_Abstract
{
    /**
     * holds the instance of the singleton
     *
     * @var HumanResources_Setup_DemoData
     */
    private static $_instance = NULL;

    /**
     * the application name to work on
     * 
     * @var string
     */
    protected $_appName         = 'HumanResources';
    protected $_costcenters     = NULL;
    protected $_feastCalendar   = NULL;
    protected $_workingtimes    = NULL;
    /**
     * models to work on
     * @var unknown_type
     */
    protected $_models = array('employee');
    
    protected $_dataMapping = array(
        'pwulf' => array(
            'health_insurance' => 'NHS', 'bank_name' => 'Barclays', 'supervisor_id' => NULL,
            'bank_code_number' => '15464684', 'bank_account_number' => '029784164'
        ),
        'jsmith' => array(
            'health_insurance' => 'NHS', 'bank_name' => 'Bank of England',
            'bank_code_number' => '23563473535', 'bank_account_number' => '2038957221'
        ),
        'rwright' => array(
            'health_insurance' => 'NHS', 'bank_name' => 'RBS',
            'bank_code_number' => '25367345624', 'bank_account_number' => '253872543'
        ),
        'sclever' => array(
            'health_insurance' => 'NHS', 'bank_name' => 'Lloyds',
            'bank_code_number' => '25464543', 'bank_account_number' => '130897782'
        ),
        'jmcblack' => array(
            'health_insurance' => 'NHS', 'bank_name' => 'Barclays',
            'bank_code_number' => '15464684', 'bank_account_number' => '345160934'
        ),
        'unittest' => array()
    );
    
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
     * @return HumanResources_Setup_DemoData
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new HumanResources_Setup_DemoData;
        }

        return self::$_instance;
    }
    
    /**
     * create some costcenters
     * 
     * @see Tinebase_Setup_DemoData_Abstract
     */
    protected function _onCreate()
    {
        $controller = Sales_Controller_CostCenter::getInstance();
        $this->_costcenters = new Tinebase_Record_RecordSet('Sales_Model_CostCenter');
        $ccs = (static::$_de)
            ? array('Management', 'Marketing', 'Public Relations', 'Produktion', 'Verwaltung',     'Controlling')
            : array('Management', 'Marketing', 'Public Relations', 'Production', 'Administration', 'Controlling')
        ;
        
        $id = 1;
        foreach($ccs as $title) {
            $cc = new Sales_Model_CostCenter(
                array('remark' => $title, 'number' => $id)
            );
            try {
                $record = $controller->create($cc);
                $this->_costcenters->addRecord($record);
            } catch (Zend_Db_Statement_Exception $e) {
                $this->_costcenters = $controller->search(new Sales_Model_CostCenterFilter(array()));
            }

            $id++;
        }
        
        $divisionsArray = (static::$_de)
            ? array('Management', 'EDV', 'Marketing', 'Public Relations', 'Produktion', 'Verwaltung')
            : array('Management', 'IT', 'Marketing', 'Public Relations', 'Production', 'Administration')
        ;
        
        $this->_divisions = new Tinebase_Record_RecordSet('Sales_Model_Division');
        
        foreach($divisionsArray as $divisionName) {
            $this->_divisions->addRecord(Sales_Controller_Division::getInstance()->create(new Sales_Model_Division(array('title' => $divisionName))));
        }
    }
    
    /**
     * creates for each account an employee
     */
    protected function _createSharedEmployees()
    {
        $controller = HumanResources_Controller_Employee::getInstance();
        $this->_feastCalendar = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'           => 'Feast Calendar',
            'type'           => Tinebase_Model_Container::TYPE_SHARED,
            'owner_id'       => Tinebase_Core::getUser(),
            'backend'        => 'SQL',
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'color'          => '#00FF00'
        ), TRUE));
        
        $controller->transferUserAccounts(FALSE, $this->_feastCalendar->getId(), NULL, 27, TRUE);
        
        $employees = $controller->search(new HumanResources_Model_EmployeeFilter(array()));

        // get pwulf as supervisor
        $pwulf = $employees->filter('n_family', 'Wulf')->getFirstRecord();
        
        $i = 0;
        
        $defaultData = array(
            'supervisor_id' => $pwulf->getId(), 'countryname' => 'GB', 'region' => 'East Sussex', 'locality' => 'Brighton'
            );
            
        foreach($employees as $employee) {
            
            if (! $employee->account_id) {
                echo '=== SKIPPING ===' . PHP_EOL;
                echo 'There is no account_id for this employee: ' . PHP_EOL;
                echo print_r($employee->toArray(), 1);
                echo 'This employee won\'t have costcenters, contracts, etc.' . PHP_EOL;
                echo '=== SKIPPING ===' . PHP_EOL;
            }
            
            $user = Tinebase_User::getInstance()->getUserByProperty('accountId', $employee->account_id, 'Tinebase_Model_FullUser');

            foreach(array_merge($defaultData, $this->_dataMapping[$user->accountLoginName]) as $key => $data) {
                $employee->{$key} = $data;
            }
            
            // add costcenter
            $scs = $this->_costcenters->getByIndex($i);
            $sd = Tinebase_DateTime::now()->subMonth(6);
            $hrc = array('cost_center_id' => $scs->getId(), 'start_date' => $sd);
            $employee->costcenters = array($hrc);
            
            // add contract
            $contract = $this->_getContract()->toArray();
            $employee->contracts = array($contract);
            
            // add division
            $division = $this->_divisions[$i];
            $employee->division_id = $division->getId();
            
            // update and increment counter
            $controller->update($employee);
            $i++;
        }
    }
    
    /**
     * get cost center
     * 
     * @param string
     * @return Sales_Model_CostCenter
     */
    protected function _getCostCenter($number = NULL)
    {
        if ($number !== NULL) {
            $c = Sales_Controller_CostCenter::getInstance()->search(new Sales_Model_CostCenterFilter(array(array(
                'field'    => 'number',
                'operator' => 'equals',
                'value'    => $number,
            ))))->getFirstRecord();
            if ($c) {
                return $c;
            }
        }
        $c = new Sales_Model_CostCenter(array(
            'number' => ($number) ? $number : Tinebase_Record_Abstract::generateUID(),
            'remark' => Tinebase_Record_Abstract::generateUID(),
        ));
        
        return $hrc;
    }
    
    /**
     * returns a workingtime
     */
    protected function _getWorkingTime()
    {
        if (! $this->_workingtimes) {
            $filter = new HumanResources_Model_WorkingTimeFilter();
            $this->_workingtimes = HumanResources_Controller_WorkingTime::getInstance()->search($filter);
        }
        $count = $this->_workingtimes->count();
        
        $wt = $this->_workingtimes->getByIndex(rand(0, $count-1));
        
        return $wt;
    }
    /**
     * returns a new contract
     * return HumanResources_Model_Contract
     */
    protected function _getContract()
    {
        $sdate = new Tinebase_DateTime();
        $sdate->subMonth(6);

        $c = new HumanResources_Model_Contract(array(
            'start_date' => $sdate,
            'end_date'   => null,
            'employee_id' => null,
            'vacation_days' => 30,
            'feast_calendar_id' => $this->_feastCalendar,
            'workingtime_json'  => $this->_getWorkingTime()->json
        ), TRUE);

        return $c;
    }
}
