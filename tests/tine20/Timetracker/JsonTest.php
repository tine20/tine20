<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id:JsonTest.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 * 
 * @todo        add timeaccount tests and reactivate timesheet tests
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Timetracker_JsonTest::main');
}

/**
 * Test class for Tinebase_Group
 */
class Timetracker_JsonTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Timetracker_Frontend_Json
     */
    protected $_backend = array();
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Timetracker Json Tests');
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
        $this->_backend = new Timetracker_Frontend_Json();        
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
    
    /**
     * try to add a Timesheet
     *
     */
    public function testAddTimeaccount()
    {
        $timeaccount = $this->_getTimeaccount();
        $timeaccountData = $this->_backend->saveTimeaccount(Zend_Json::encode($timeaccount->toArray()));
        
        // checks
        $this->assertEquals($timeaccount->description, $timeaccountData['description']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timeaccountData['created_by']);
        $this->assertTrue(is_array($timeaccountData['container_id']));
        $this->assertEquals(Tinebase_Model_Container::TYPE_SHARED, $timeaccountData['container_id']['type']);
        
        // cleanup
        $this->_backend->deleteTimeaccounts($timeaccountData['id']);
    }
    
    /**
     * try to add a Timesheet
     *
     */
    public function testAddTimesheet()
    {
        /*
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_backend->saveTimesheet(Zend_Json::encode($timesheet->toArray()));
        
        // checks
        $this->assertEquals($timesheet->description, $timesheetData['description']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timesheetData['created_by']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timesheetData['account_id']);
        
        // cleanup
        $this->_backend->deleteTimesheets($timesheetData['id']);
        Erp_Controller_Contract::getInstance()->delete($timesheet->contract_id);
        */
    }
    
    /**
     * try to get a Timesheet
     *
     */
    public function testGetTimesheet()
    {
        /*
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_backend->saveTimesheet(Zend_Json::encode($timesheet->toArray()));
        $timesheetData = $this->_backend->getTimesheet($timesheetData['id']);
        
        // checks
        $this->assertEquals($timesheet->description, $timesheetData['description']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timesheetData['created_by']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timesheetData['account_id']);
                
        // cleanup
        $this->_backend->deleteTimesheets($timesheetData['id']);
        Erp_Controller_Contract::getInstance()->delete($timesheet->contract_id);
        */
    }

    /**
     * try to update a Timesheet (with relations)
     *
     */
    public function testUpdateTimesheet()
    {
        /*
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_backend->saveTimesheet(Zend_Json::encode($timesheet->toArray()));
        
        // update Timesheet
        $timesheetData['description'] = "blubbblubb";
        $timesheetUpdated = $this->_backend->saveTimesheet(Zend_Json::encode($timesheetData));
        
        // check
        $this->assertEquals($timesheetData['id'], $timesheetUpdated['id']);
        $this->assertEquals($timesheetData['description'], $timesheetUpdated['description']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timesheetUpdated['last_modified_by']);
        
        // cleanup
        $this->_backend->deleteTimesheets($timesheetData['id']);
        Erp_Controller_Contract::getInstance()->delete($timesheet->contract_id);
        */
    }
    
    /**
     * try to get a Timesheet
     *
     */
    public function testSearchTimesheets()
    {
        /*
        // create
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_backend->saveTimesheet(Zend_Json::encode($timesheet->toArray()));
        
        // search & check
        $search = $this->_backend->searchTimesheets(Zend_Json::encode($this->_getFilter()), Zend_Json::encode($this->_getPaging()));
        $this->assertEquals($timesheet->description, $search['results'][0]['description']);
        $this->assertEquals(1, $search['totalcount']);
        
        // cleanup
        $this->_backend->deleteTimesheets($timesheetData['id']);
        Erp_Controller_Contract::getInstance()->delete($timesheet->contract_id);
        */
    }


    /************ protected helper funcs *************/
    
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
        /*
        $contract = Erp_Controller_Contract::getInstance()->create(new Erp_Model_Contract(
            array(
                'title'         => 'phpunit timesheet contract',
                'description'   => 'blabla',
            ), TRUE)
        );
        */
        
        return new Timetracker_Model_Timesheet(array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
           // 'contract_id'   => $contract->getId(),
            'description'   => 'blabla',
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
     * get filter
     *
     * @return array
     */
    protected function _getFilter()
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
