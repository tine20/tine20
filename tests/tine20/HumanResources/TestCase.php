<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2012-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */

/**
 * Test class for Tinebase_Group
 */
class HumanResources_TestCase extends TestCase
{
    /**
     * Feast Calendar
     * @var Tinebase_Model_BankHolidayCalendar
     */
    protected $_feast_calendar = NULL;

    /**
     * @var HumanResources_Frontend_Json
     */
    protected $_json = array();

    protected $_lastEmployeeNumber = 0;
    
    /**
     * test department
     *
     * @var Tinebase_Model_Department
     */
    protected $_department = NULL;

    /**
     * @var HumanResources_Model_WorkingTimeScheme;
     */
    protected $_40hoursWorkingTimeScheme = null;

    /** @var HumanResources_Model_Employee */
    protected $employee;

    /** @var HumanResources_Model_Division */
    protected $division;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        // remove employees and costcenters, if there are some already
        $filter = new HumanResources_Model_EmployeeFilter(array());
        HumanResources_Controller_Employee::getInstance()->deleteByFilter($filter);

        Tinebase_Controller_CostCenter::getInstance()->deleteByFilter(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_CostCenter::class, [])
        );
        
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->_feast_calendar = null;
        $this->_department = null;
        $this->_40hoursWorkingTimeScheme = null;
        $this->employee = null;
        $this->division = null;

        parent::tearDown();
    }


    protected function _createBasicData($account = null)
    {
        // create employee & contract
        $this->employee = $this->_getEmployee($account ?: Tinebase_Core::getUser()->accountLoginName);
        //$this->employee->dfcom_id = '36118993923739652';

        $contractController = HumanResources_Controller_Contract::getInstance();
        $employeeController = HumanResources_Controller_Employee::getInstance();
        $this->employee = $employeeController->create($this->employee, false);
        $contract = $this->_getContract('2018-07-01 00:00:00');
        $contract->employee_id = $this->employee->getId();
        //  @todo add more contract properties ?
        $contract = $contractController->create($contract);
        $this->employee->contracts = new Tinebase_Record_RecordSet(HumanResources_Model_Contract::class, [$contract]);
    }
    
    protected function _getWorkingTimeScheme40()
    {
        if (null === $this->_40hoursWorkingTimeScheme) {
            $translate = Tinebase_Translation::getTranslation('HumanResources', new Zend_Locale(Tinebase_Config::getInstance()->{Tinebase_Config::DEFAULT_LOCALE}));
            $this->_40hoursWorkingTimeScheme = HumanResources_Controller_WorkingTimeScheme::getInstance()->search(
                new HumanResources_Model_WorkingTimeSchemeFilter([
                    ['field' => 'title', 'operator' => 'equals', 'value' => $translate->_('Full-time 40 hours')]
                ]))->getFirstRecord();

            if (!$this->_40hoursWorkingTimeScheme->{HumanResources_Model_WorkingTimeScheme::FLDS_BLPIPE} ||
                $this->_40hoursWorkingTimeScheme->{HumanResources_Model_WorkingTimeScheme::FLDS_BLPIPE}->count() < 1) {
                $this->_40hoursWorkingTimeScheme->{HumanResources_Model_WorkingTimeScheme::FLDS_BLPIPE} =
                    HumanResources_Setup_Initialize::getDefaultWTS_BL();
                HumanResources_Controller_WorkingTimeScheme::getInstance()->update($this->_40hoursWorkingTimeScheme);
            }
        }
        return $this->_40hoursWorkingTimeScheme;
    }
    /**
     * returns the current user or the user defined by the login name
     * @return Tinebase_Model_User
     */
    protected function _getAccount($loginName = NULL)
    {
        if ($loginName) {
            return Tinebase_User::getInstance()->getFullUserByLoginName($loginName);
        }
        
        return Tinebase_Core::getUser();
    }

    /**
     * returns one of the on setup created workingtimes
     * 
     * @return HumanResources_Model_WorkingTimeScheme
     */
    protected function _getWorkingTime()
    {
        $filter = new HumanResources_Model_WorkingTimeSchemeFilter();
        $wt = HumanResources_Controller_WorkingTimeScheme::getInstance()->search($filter);
        return $wt->getFirstRecord();
    }

    /**
     * returns the default feast calendar
     * 
     * @param boolean $anotherone if another than the default one should be returned
     * @return Tinebase_Model_BankHolidayCalendar
     */
    protected function _getFeastCalendar($anotherone = false)
    {
        if ($anotherone) {
            return Tinebase_Controller_BankHolidayCalendar::getInstance()->create(new Tinebase_Model_BankHolidayCalendar([
                'name'           => Tinebase_Record_Abstract::generateUID(),
            ]));
        }
        
        if(! $this->_feast_calendar) {
            $this->_feast_calendar = Tinebase_Controller_BankHolidayCalendar::getInstance()->create(new Tinebase_Model_BankHolidayCalendar([
                'name'           => 'Feast Calendar',
            ]));
        }

        return $this->_feast_calendar;
    }

    /**
     * returns the contact of the current user
     * @return Addressbook_Model_Contact
     */
    protected function _getContact($loginName = NULL)
    {
        if ($loginName) {
            $user = Tinebase_User::getInstance()->getFullUserByLoginName($loginName);
        } else {
            $user = Tinebase_Core::getUser();
        }
        
        return Addressbook_Controller_Contact::getInstance()->getContactByUserId($user->getId());
    }

    /**
     * get sales cost center
     * 
     * @param string
     * @return Tinebase_Model_CostCenter
     */
    protected function _getTinebaseCostCenter($number = NULL)
    {
        if ($number !== NULL) {
            $c = Tinebase_Controller_CostCenter::getInstance()->search(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_CostCenter::class, [[
                    'field' => 'number', 'operator' => 'equals', 'value' => $number,
                ], [
                    'field' => 'is_deleted', 'operator' => 'equals', 'value' => Tinebase_Model_Filter_Bool::VALUE_NOTSET,
                ]]))->getFirstRecord();
            
            if ($c) {
                if ($c->is_deleted) {
                    Tinebase_Controller_CostCenter::getInstance()->unDelete($c);
                    $c = Tinebase_Controller_CostCenter::getInstance()->get($c->getId());
                }
                /** @var Tinebase_Model_CostCenter $c */
                return $c;
            }
        }
        $c = new Tinebase_Model_CostCenter(array(
            'number' => $number ?: Tinebase_Record_Abstract::generateUID(),
            'name' => Tinebase_Record_Abstract::generateUID(),
        ));

        /** @var Tinebase_Model_CostCenter $c */
        $c = Tinebase_Controller_CostCenter::getInstance()->create($c, FALSE);
        
        return $c;
    }

    /**
     * get hr cost center 
     * @param Tinebase_DateTime $startDate
     * @return HumanResources_Model_CostCenter
     */
    protected function _getCostCenter($startDate = NULL)
    {
        if (! $startDate) {
            $startDate = new Tinebase_DateTime();
        }
        
        $tcs = $this->_getTinebaseCostCenter();
        return new HumanResources_Model_CostCenter(array(
            'start_date' => $startDate->toString(), 
            'cost_center_id' => $tcs->getId()
        ));
    }
    
    /**
     * returns a new contract
     * 
     * @param Tinebase_DateTime $sdate
     * @return HumanResources_Model_Contract
     */
    protected function _getContract($sdate = NULL)
    {
        if (! $sdate) {
            $sdate = new Tinebase_DateTime();
            $sdate->subMonth(1);
        }
        $c = new HumanResources_Model_Contract(array(
            'start_date' => $sdate,
            'end_date'   => null,
            'employee_id' => null,
            'vacation_days' => 30,
            'feast_calendar_id' => $this->_getFeastCalendar(),
            'working_time_scheme' => $this->_getWorkingTimeScheme40()
        ), true);

        return $c;
    }

    protected function _getDivision($name = 'unittest'): HumanResources_Model_Division
    {
        if (null === $this->division) {
            $this->division = HumanResources_Controller_Division::getInstance()->create(
                new HumanResources_Model_Division(['title' => $name]));
        }
        return $this->division;
    }

    /**
     * returns an employee with account_id
     * @return HumanResources_Model_Employee
     */
    protected function _getEmployee($loginName = NULL) 
    {
        $a = $this->_getAccount($loginName);

        $this->_lastEmployeeNumber++;
        
        $sdate = new Tinebase_DateTime();
        $sdate->subMonth(1);
        
        $ea = array(
            'number'     => $this->_lastEmployeeNumber,
            'n_fn'       => $a->accountFullName,
            'n_given'    => $a->accountFirstName,
            'n_family'   => $a->accountLastName,
            'account_id' => $a->getId(),
            'position'   => 'Photographer',
            'employment_begin' => $sdate,
            'division_id' => $this->_getDivision()->getId(),
            
        );

        return new HumanResources_Model_Employee($ea);
    }
    

    /**
     * adds a feast day to feast calendar
     *
     * @param Tinebase_DateTime $date
     */
    protected function _createFeastDay($date): Tinebase_Model_BankHoliday
    {
        if (! $this->_feast_calendar) {
            $this->_getFeastCalendar();
        }

        $dateStr = $date->format('Y-m-d');
        $this->_feast_calendar->{Tinebase_Model_BankHolidayCalendar::FLD_BANKHOLIDAYS}->addRecord(
            new Tinebase_Model_BankHoliday([
                Tinebase_Model_BankHoliday::FLD_NAME => 'Feast Day',
                Tinebase_Model_BankHoliday::FLD_DATE => $dateStr,
            ], true));

        $this->_feast_calendar = Tinebase_Controller_BankHolidayCalendar::getInstance()->update($this->_feast_calendar);

        return $this->_feast_calendar->{Tinebase_Model_BankHolidayCalendar::FLD_BANKHOLIDAYS}->find(
            function($bh) use ($dateStr) {
                return $bh->{Tinebase_Model_BankHoliday::FLD_DATE}->format('Y-m-d') === $dateStr;
            }, null
        );
    }

}
