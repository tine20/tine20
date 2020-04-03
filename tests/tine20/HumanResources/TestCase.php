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
     * @var Tinebase_Model_Container
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

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        // remove employees and costcenters, if there are some already
        $filter = new HumanResources_Model_EmployeeFilter(array());
        HumanResources_Controller_Employee::getInstance()->deleteByFilter($filter);
        
        $filter = new Sales_Model_CostCenterFilter(array());
        Sales_Controller_CostCenter::getInstance()->deleteByFilter($filter);
        
        parent::setUp();
    }

    protected function _getWorkingTimeScheme40()
    {
        if (null === $this->_40hoursWorkingTimeScheme) {
            $translate = Tinebase_Translation::getTranslation('HumanResources', new Zend_Locale(Tinebase_Config::getInstance()->{Tinebase_Config::DEFAULT_LOCALE}));
            $this->_40hoursWorkingTimeScheme = HumanResources_Controller_WorkingTimeScheme::getInstance()->search(
                new HumanResources_Model_WorkingTimeSchemeFilter([
                    ['field' => 'title', 'operator' => 'equals', 'value' => $translate->_('Full-time 40 hours')]
                ]))->getFirstRecord();
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
     * @return Tinebase_Model_Container
     */
    protected function _getFeastCalendar($anotherone = false)
    {
        if ($anotherone) {
            return Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
                'name'           => Tinebase_Record_Abstract::generateUID(),
                'type'           => Tinebase_Model_Container::TYPE_SHARED,
                'owner_id'       => Tinebase_Core::getUser(),
                'backend'        => 'SQL',
                'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
                'color'          => '#00FF00',
                'model'          => Calendar_Model_Event::class,
            ), true));
        }
        
        if(! $this->_feast_calendar) {
            $this->_feast_calendar = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
                'name'           => 'Feast Calendar',
                'type'           => Tinebase_Model_Container::TYPE_SHARED,
                'owner_id'       => Tinebase_Core::getUser(),
                'backend'        => 'SQL',
                'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
                'color'          => '#00FF00',
                'model'          => Calendar_Model_Event::class,
            ), true));
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
     * @return Sales_Model_CostCenter
     */
    protected function _getSalesCostCenter($number = NULL)
    {
        if ($number !== NULL) {
            $c = Sales_Controller_CostCenter::getInstance()->search(new Sales_Model_CostCenterFilter([[
                'field' => 'number', 'operator' => 'equals', 'value' => $number,
            ], [
                'field' => 'is_deleted', 'operator' => 'equals', 'value' => Tinebase_Model_Filter_Bool::VALUE_NOTSET,
            ]]))->getFirstRecord();
            
            if ($c) {
                if ($c->is_deleted) {
                    $c = Sales_Controller_CostCenter::getInstance()->unDelete($c);
                }
                return $c;
            }
        }
        $c = new Sales_Model_CostCenter(array(
            'number' => ($number) ? $number : Tinebase_Record_Abstract::generateUID(),
            'remark' => Tinebase_Record_Abstract::generateUID(),
        ));
        
        $c = Sales_Controller_CostCenter::getInstance()->create($c, FALSE);
        
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
        
        $scs = $this->_getSalesCostCenter();
        return new HumanResources_Model_CostCenter(array(
            'start_date' => $startDate->toString(), 
            'cost_center_id' => $scs->getId()
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
            'employment_begin' => $sdate
            
        );

        return new HumanResources_Model_Employee($ea);
    }
    

    /**
     * adds feast days to feast calendar
     *
     * @param array|Tinebase_DateTime $date
     */
    protected function _createFeastDay($date)
    {
        if (! $this->_feast_calendar) {
            $this->_getFeastCalendar();
        }
        $organizer = Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId());
    
        if (is_array($date)) {
            $allDay = TRUE;
            if (count($date) == 1) {
                $dtstart = $date[0]->setTimezone(Tinebase_Core::getUserTimezone())->setTime(0,0,0);
                $dtend   = clone $dtstart;
            } else {
                $dtstart = $date[0]->setTimezone(Tinebase_Core::getUserTimezone())->setTime(0,0,0);
                $dtend   = clone $dtstart;
                $dtend   = $dtend->addDay(count($date))->subHour(5);
            }
        } else {
            $allDay = FALSE;
            $dtstart = $date->setTimezone(Tinebase_Core::getUserTimezone())->setTime(6,0,0);
            $dtend   = clone $dtstart;
            $dtend->addMinute(15);
        }
        
        $event = new Calendar_Model_Event(array(
            'summary'     => 'Feast Day',
            'dtstart'     => $dtstart->format('Y-m-d H:i:s'),
            'dtend'       => $dtend->format('Y-m-d H:i:s'),
            'description' => Tinebase_Record_Abstract::generateUID(10),
    
            'container_id' => $this->_feast_calendar->getId(),
            'organizer'    => $organizer->getId(),
            'uid'          => Calendar_Model_Event::generateUID(),
            'is_all_day_event' => $allDay,
            'attendee' => new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(array(
                'user_id'        => $organizer->getId(),
                'user_type'      => Calendar_Model_Attender::USERTYPE_USER,
                'role'           => Calendar_Model_Attender::ROLE_REQUIRED,
                'status_authkey' => Tinebase_Record_Abstract::generateUID(),
            ))),
    
            Tinebase_Model_Grants::GRANT_READ    => true,
            Tinebase_Model_Grants::GRANT_EDIT    => true,
            Tinebase_Model_Grants::GRANT_DELETE  => true,
        ));
    
        return Calendar_Controller_Event::getInstance()->create($event);
    }
    
    /**
     * creates a recurring event
     * 
     * @param Tinebase_DateTime $date
     * @return Calendar_Model_Event
     */
    protected function _createRecurringFeastDay($date)
    {
        $event = $this->_createFeastDay($date);
        $event->rrule = "FREQ=YEARLY;INTERVAL=1;BYMONTH=12;BYMONTHDAY=24";
        $event->dtstart->subYear(1);
        $event->dtend->subYear(1);
        
        return Calendar_Controller_Event::getInstance()->update($event);
    }
}
