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
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {        
    }
    
    /************ test functions follow **************/

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
            'manage_clearing'      => TRUE,
        )));
        
        $this->_grantTestHelper($grants, 'create', TRUE);
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
     * test to create TS with manage_clearing grant
     *
     * @todo change this test later
     */
    public function testManageClearingGrantTS()
    {
        /*
        $grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            'manage_clearing'      => TRUE,
        )));        
        
        $this->_grantTestHelper($grants);
        */
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
    
    /************ protected helper funcs *************/
    
    /**
     * try to add a Timesheet with different grants
     * 
     * @param Tinebase_Record_RecordSet $_grants
     */
    protected function _grantTestHelper($_grants, $_action = 'create', $_expect = FALSE)
    {
        // get timesheet
        $timesheet = $this->_getTimesheet();
        $timeaccount = $this->_timeaccountController->get($timesheet->timeaccount_id);
        
        // remove BOOK_OWN + BOOK_ALL + ADMIN grant
        Timetracker_Model_TimeaccountGrants::setTimeaccountGrants(
            $timeaccount,
            $_grants,
            TRUE
        );
        
        // try to create timesheet
        if ($_action === 'create') {
            if ($_expect) {
                $this->setExpectedException('Tinebase_Exception_AccessDenied');
                $this->_timesheetController->create($timesheet);
            } else {
                $ts = $this->_timesheetController->create($timesheet);
                $this->assertEquals(Tinebase_Core::getUser()->getId(), $ts->created_by);
            }
        } else {
            echo "nothing tested.";
        }

        // delete (set delete grant first)
        $grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', array(array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            'manage_clearing'  => TRUE,
            'manage_all'      => TRUE,
            'book_all'      => TRUE,
            'book_own'      => TRUE,
            'view_all'      => TRUE,
        )));    
        Timetracker_Model_TimeaccountGrants::setTimeaccountGrants(
            $timeaccount,
            $grants,
            TRUE
        ); 
        $this->_timeaccountController->delete($timeaccount->getId());
    }
    
    // @todo check if we need all of these
    
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
     * get paging
     *
     * @return array
     */
    protected function _getPaging()
    {
        return array(
            'start' => 0,
            'limit' => 50,
            'sort' => 'creation_time',
            'dir' => 'ASC',
        );
    }

    /**
     * get Timeaccount filter
     *
     * @return array
     */
    protected function _getTimeaccountFilter()
    {
        return array(
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
        );        
    }
    
    /**
     * get Timesheet filter
     *
     * @return array
     */
    protected function _getTimesheetFilter()
    {
        return array(
            array(
                'field' => 'query', 
                'operator' => 'contains', 
                'value' => 'blabla'
            ),
        );        
    }
}
