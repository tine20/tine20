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
     * returns the current user
     * @return Tinebase_Model_User
     */
    protected function _getAccount()
    {
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
        $fc = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'           => 'Feast Calendar',
            'type'           => Tinebase_Model_Container::TYPE_SHARED,
            'owner_id'       => Tinebase_Core::getUser(),
            'backend'        => 'SQL',
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'color'          => '#00FF00'
        ), true));

        return $fc;
    }
    /**
     * returns the contact of the current user
     * @return Addressbook_Model_Contact
     */
    protected function _getContact()
    {
        return Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId());
    }
    
    protected function _getCostCenter()
    {
        $c = new Sales_Model_CostCenter(array(
            'number' => 'wks-1',
            'remark' => 'Production'
            ));
        $c = Sales_Controller_CostCenter::getInstance()->create($c);
        return $c;
    }
    /**
     * returns a new contract
     * return HumanResources_Model_Contract
     */
    protected function _getContract()
    {
        $sdate = new Tinebase_DateTime();
        $edate = new Tinebase_DateTime();
        $sdate->subMonth(1);
        $edate->addMonth(5);
        
        $c = new HumanResources_Model_Contract(array(
            'start_date' => $sdate,
            'end_date'   => $edate,
            'employee_id' => null,
            'cost_center_id' => $this->_getCostCenter(),
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
    protected function _getEmployee() {
        $a = $this->_getAccount();
        $c = $this->_getContact();
        
        $e = new HumanResources_Model_Employee(
            array(
                'number' => 1,
                'n_fn' => $c->n_fn,
                'account_id' => $a->getId()
                )
            );
        
        return $e;
    }
}
