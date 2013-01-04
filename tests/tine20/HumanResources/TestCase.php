<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Group
 */
class HumanResources_TestCase extends PHPUnit_Framework_TestCase
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

    /**
     * test department
     *
     * @var Tinebase_Model_Department
     */
    protected $_department = NULL;

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 HumanResources Json Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
    }

    /**
     * returns the current user or the user defined by the login name
     * @return Tinebase_Model_User
     */
    protected function _getAccount($loginName = NULL)
    {
        if($loginName) {
            return Tinebase_User::getInstance()->getFullUserByLoginName($loginName);
        }
        return Tinebase_Core::getUser();
    }

    /**
     * returns one of the un setup created workintimes
     * @return HumanResources_Model_WorkingTime
     */
    protected function _getWorkingTime()
    {
        $filter = new HumanResources_Model_WorkingTimeFilter();
        $wt = HumanResources_Controller_WorkingTime::getInstance()->search($filter);
        return $wt->getFirstRecord();
    }

    /**
     * returns the default feast calendar
     * @return Tinebase_Model_Container
     */
    protected function _getFeastCalendar()
    {
        if(!$this->_feast_calendar) {
            $this->_feast_calendar = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
                'name'           => 'Feast Calendar',
                'type'           => Tinebase_Model_Container::TYPE_SHARED,
                'owner_id'       => Tinebase_Core::getUser(),
                'backend'        => 'SQL',
                'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
                'color'          => '#00FF00'
            ), true));
        }

        return $this->_feast_calendar;
    }

    /**
     * returns the contact of the current user
     * @return Addressbook_Model_Contact
     */
    protected function _getContact()
    {
        return Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId());
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
        
        $c = Sales_Controller_CostCenter::getInstance()->create($c);
        $startDate = new Tinebase_DateTime('2012-12-12');
        $hrc = array('cost_center_id' => $c->getId(), 'start_date' => $startDate);
        
        return $hrc;
    }

    /**
     * returns a new contract
     * return HumanResources_Model_Contract
     */
    protected function _getContract()
    {
        $sdate = new Tinebase_DateTime();
        $sdate->subMonth(1);

        $c = new HumanResources_Model_Contract(array(
            'start_date' => $sdate,
            'end_date'   => null,
            'employee_id' => null,
            'vacation_days' => 30,
            'feast_calendar_id' => $this->_getFeastCalendar(),
            'workingtime_id' => $this->_getWorkingTime()
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
        $c = $this->_getContact();

        $e = new HumanResources_Model_Employee(
            array(
                'number' => 1,
                'n_fn' => $c->n_fn,
                'account_id' => $a->getId(),
                'costcenters' => array($this->_getCostCenter())
            )
        );

        return $e;
    }
}
