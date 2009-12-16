<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * this test class mainly tests the timeaccount grants and the controller functions
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id:JsonTest.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 * 
 * @todo        add test for manage_billable
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Timetracker_ControllerTest::main');
}

/**
 * Test class for Tinebase_Group
 */
class Timetracker_ControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Timetracker_Controller_Timeaccount
     */
    protected $_timeaccountController = array();
    
    /**
     * @var Timetracker_Controller_Timesheet
     */
    protected $_timesheetController = array();
    
    /**
     * role rights
     *
     * @var array
     */
    protected $_roleRights = array();

    /**
     * objects
     *
     * @var array
     */
    protected $_objects = array();
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Timetracker Controller Tests');
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
        $this->_timeaccountController = Timetracker_Controller_Timeaccount::getInstance();        
        $this->_timesheetController = Timetracker_Controller_Timesheet::getInstance();     

        // get timesheet
        $this->_objects['timesheet'] = $this->_getTimesheet();
        $this->_objects['timeaccount'] = $this->_timeaccountController->get($this->_objects['timesheet']->timeaccount_id);
        
        // remove MANAGE_ALL & ADMIN for Timetracker right
        $role = Tinebase_Acl_Roles::getInstance()->getRoleByName('admin role');
        $app = Tinebase_Application::getInstance()->getApplicationByName('Timetracker');
        $this->_roleRights = Tinebase_Acl_Roles::getInstance()->getRoleRights($role->getId());
        $rightsWithoutManageAll = array();
        foreach ($this->_roleRights as $right) {
            if ($right['right'] != Timetracker_Acl_Rights::MANAGE_TIMEACCOUNTS 
                && !($right['application_id'] == $app->getId() && $right['right'] == 'admin')
            ) {
                $rightsWithoutManageAll[] = $right;
            }
        }
        Tinebase_Acl_Roles::getInstance()->setRoleRights($role->getId(), $rightsWithoutManageAll);
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        // reset old admin role rights        
        Tinebase_Acl_Roles::getInstance()->setRoleRights(Tinebase_Acl_Roles::getInstance()->getRoleByName('admin role')->getId(), $this->_roleRights);
        
        // delete timeaccount
        $this->_timeaccountController->delete($this->_objects['timeaccount']->getId());
    }
    
    /************ test functions follow **************/

    /**
     * test to create TS with book_own grant
     *
     */
    public function testNoGrantsValidatorDefaultValue()
    {
        $grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            'view_all'      => TRUE,
        )));
        
        $this->assertEquals($grants->view_all, array(TRUE));
        $this->assertEquals($grants->book_own, array(FALSE));
        $this->assertEquals($grants->book_all, array(FALSE));
    }

    /**
     * test to create TS with book_own grant
     *
     */
    public function testNoGrantsTS()
    {
        $grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            'view_all'      => TRUE,
            'manage_billable'      => TRUE,
        )));
        
        $this->_grantTestHelper($grants, 'create', 'Exception');
    }
    
    /**
     * test to create TS with book_own grant
     *
     */
    public function testBookOwnGrantTS()
    {
        $grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            'book_own'      => TRUE,
        )));        
        
        $this->_grantTestHelper($grants);        
    }
    
    /**
     * test to create TS with book_all grant
     *
     */
    public function testBookAllGrantTS()
    {
        $grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            'book_all'      => TRUE,
        )));        
        
        $this->_grantTestHelper($grants);
    }
    
    /**
     * test to create not billable TS with manage_billable grant
     *
     */
    public function testManageClearingGrantTS()
    {
        $grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
            'account_id'      => Tinebase_Core::getUser()->getId(),
            'account_type'    => 'user',
            //'manage_all'    => TRUE,
            'book_all'        => TRUE,
            'manage_billable' => TRUE,
        )));        
        
        $ts = clone $this->_objects['timesheet'];
        $ts->is_billable = 0;
        
        $this->_grantTestHelper($grants, 'create', NULL, $ts);
    }

    /**
     * test to create not billable TS without manage_billable grant
     *
     */
    public function testManageClearingGrantTSNotSet()
    {
        $grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
            'account_id'      => Tinebase_Core::getUser()->getId(),
            'account_type'    => 'user',
            //'manage_all'    => TRUE,
            'book_all'        => TRUE,
            'manage_billable' => FALSE,
        )));        
        
        $ts = clone $this->_objects['timesheet'];
        $ts->is_billable = 0;
        
        $this->_grantTestHelper($grants, 'create', 'Exception', $ts);
    }
    
    /**
     * test to create TS with manage_all grant
     *
     */
    public function testManageAllGrantTS()
    {
        $grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            'manage_all'    => TRUE,
        )));        
        
        $this->_grantTestHelper($grants);
    }
    
    /**
     * test to search TAs (view_all)
     *
     */
    public function testSearchTA()
    {
        $grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            'view_all'      => TRUE,
        )));        
        
        $this->_grantTestHelper($grants, 'searchTA', 1);
    }

    /**
     * test to search TAs (no grants)
     *
     */
    public function testSearchTAWithNoRights()
    {
        $grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
        )));        
        
        $this->_grantTestHelper($grants, 'searchTA', 0);
    }

    /**
     * test to search TAs (book_own)
     *
     */
    public function testSearchTABookable()
    {
        $grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            'book_own'      => TRUE,            
        )));        
        
        $this->_grantTestHelper($grants, 'search_bookable', 1);
    }

    /**
     * test to search TSs (view_all)
     *
     */
    public function testSearchTS()
    {
        $grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            'book_own'      => TRUE,
            'view_all'      => TRUE,
        )));        
        
        $this->_grantTestHelper($grants, 'searchTS', 1);
    }
    
    /**
     * try to add a Timesheet exceeding deadline
     *
     */
    public function testAddTimesheetExceedingDeadline()
    {
        $grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            'book_own'      => TRUE,
        )));        
        
        $this->_grantTestHelper($grants, 'create_deadline');
    }
    
    /************ protected helper funcs *************/
    
    /**
     * try to add a Timesheet with different grants
     * 
     * @param   Tinebase_Record_RecordSet $_grants
     * @param   string $_action
     * @param   mixed $_expect
     * @param   Timetracker_Model_Timesheet
     */
    protected function _grantTestHelper($_grants, $_action = 'create', $_expect = NULL, $_ts = NULL)
    {
        // take default ts?
        $ts = $_ts ? $_ts : $this->_objects['timesheet'];
        
        // remove BOOK_OWN + BOOK_ALL + ADMIN grant
        Timetracker_Model_TimeaccountGrants::setTimeaccountGrants(
            $this->_objects['timeaccount'],
            $_grants,
            TRUE
        );
        
        // try to create timesheet
        switch ($_action) {
            case 'create':
                if ($_expect === 'Exception') {
                    $this->setExpectedException('Tinebase_Exception_AccessDenied');
                    $this->_timesheetController->create($ts);
                } else {
                    $ts = $this->_timesheetController->create($ts);
                    $this->assertEquals(Tinebase_Core::getUser()->getId(), $ts->created_by);
                }
                break;
            case 'create_deadline':
                // date is before deadline
                $date = new Zend_Date();
                $date->sub(8, Zend_Date::DAY);
                $ts->start_date = $date->toString('yyyy-MM-dd');
                $this->setExpectedException('Timetracker_Exception_Deadline');
                $this->_timesheetController->create($ts);
                break;
            case 'search_bookable':
                $filter = $this->_getTimeaccountFilter(TRUE);
                $result = $this->_timeaccountController->search($filter);
                $this->assertEquals($_expect, count($result));                
                break;
            case 'searchTA':
                $filter = $this->_getTimeaccountFilter();
                $result = $this->_timeaccountController->search($filter);
                $this->assertEquals($_expect, count($result));                
                break;
            case 'searchTS':
                $filter = $this->_getTimesheetFilter();
                $ts = $this->_timesheetController->create($ts);
                $result = $this->_timesheetController->search($filter);
                $this->assertEquals($_expect, count($result));                
                break;
            default:
                echo "nothing tested.";
        }

        // delete (set delete grant first)
        $grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            'manage_billable'  => TRUE,
            'manage_all'      => TRUE,
            'book_all'      => TRUE,
            'book_own'      => TRUE,
            'view_all'      => TRUE,
        )));    
        Timetracker_Model_TimeaccountGrants::setTimeaccountGrants(
            $this->_objects['timeaccount'],
            $grants,
            TRUE
        ); 
    }
    
    /**
     * get Timesheet
     *
     * @return Timetracker_Model_Timeaccount
     */
    protected function _getTimeaccount()
    {
        return new Timetracker_Model_Timeaccount(array(
            'title'         => Tinebase_Record_Abstract::generateUID(),
            'description'   => 'blabla',
            'is_open'       => 1,
            'deadline'      => Timetracker_Model_Timeaccount::DEADLINE_LASTWEEK
        ), TRUE);
    }
    
    /**
     * get Timesheet (create timeaccount as well)
     *
     * @return Timetracker_Model_Timesheet
     */
    protected function _getTimesheet()
    {
        $timeaccount = Timetracker_Controller_Timeaccount::getInstance()->create($this->_getTimeaccount());
        
        return new Timetracker_Model_Timesheet(array(
            'account_id'        => Tinebase_Core::getUser()->getId(),
            'timeaccount_id'    => $timeaccount->getId(),
            'description'       => 'blabla',
            'start_date'        => Zend_Date::now()->toString('YYYY-MM-dd')
        ), TRUE);
    }

    /**
     * get Timeaccount filter
     *
     * @return Timetracker_Model_TimeaccountFilter
     */
    protected function _getTimeaccountFilter($bookable = FALSE)
    {
        $result = new Timetracker_Model_TimeaccountFilter(array(
            array(
                'field' => 'description', 
                'operator' => 'contains', 
                'value' => 'blabla'
            ),
            array(
                'field' => 'containerType', 
                'operator' => 'equals', 
                'value' => Tinebase_Model_Container::TYPE_SHARED
            ),  
        )); 
        
        if ($bookable) {
            $result->isBookable = TRUE;
        }
        
        return ($result);        
    }
    
    /**
     * get Timesheet filter
     *
     * @return array
     */
    protected function _getTimesheetFilter()
    {
        return new Timetracker_Model_TimesheetFilter(array(
            array(
                'field' => 'query', 
                'operator' => 'contains', 
                'value' => 'blabla'
            ),
        ));        
    }
}
