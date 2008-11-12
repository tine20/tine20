<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Timesheet
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Timesheet_JsonTest::main');
}

/**
 * Test class for Tinebase_Group
 */
class Timesheet_JsonTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Timesheet_Frontend_Json
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
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Timesheet Json Tests');
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
        $this->_backend = new Timesheet_Frontend_Json();        
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
    public function testAddTimesheet()
    {
        /*
        $Timesheet = $this->_getTimesheet();
        $TimesheetData = $this->_backend->saveTimesheet(Zend_Json::encode($Timesheet->toArray()));
        
        // checks
        //$this->assertEquals($TimesheetData['id'], $Timesheet->getId());
        $this->assertGreaterThan(0, $TimesheetData['number']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $TimesheetData['created_by']);
        
        // cleanup
        $this->_backend->deleteTimesheets($TimesheetData['id']);
        $this->_decreaseNumber();
        */
    }
    
    /**
     * try to get a Timesheet
     *
     */
    public function testGetTimesheet()
    {
        /*
        $Timesheet = $this->_getTimesheet();
        $TimesheetData = $this->_backend->saveTimesheet(Zend_Json::encode($Timesheet->toArray()));
        $TimesheetData = $this->_backend->getTimesheet($TimesheetData['id']);
        
        // checks
        //$this->assertEquals($TimesheetData['id'], $Timesheet->getId());
        $this->assertGreaterThan(0, $TimesheetData['number']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $TimesheetData['created_by']);
        
        // cleanup
        $this->_backend->deleteTimesheets($TimesheetData['id']);
        $this->_decreaseNumber();
        */
    }

    /**
     * try to update a Timesheet (with relations)
     *
     */
    public function testUpdateTimesheet()
    {
        /*
        $Timesheet = $this->_getTimesheet();
        $TimesheetData = $this->_backend->saveTimesheet(Zend_Json::encode($Timesheet->toArray()));
        $TimesheetData = $this->_backend->getTimesheet($TimesheetData['id']);
        
        // add account and contact + update Timesheet
        $TimesheetData['relations'] = $this->_getRelations();

        //print_r($TimesheetData);
        
        $TimesheetUpdated = $this->_backend->saveTimesheet(Zend_Json::encode($TimesheetData));
        
        //print_r($TimesheetUpdated);
        
        // check
        $this->assertEquals($TimesheetData['id'], $TimesheetUpdated['id']);
        $this->assertGreaterThan(0, count($TimesheetUpdated['relations']));
        $this->assertEquals('Addressbook_Model_Contact', $TimesheetUpdated['relations'][0]['related_model']);
        $this->assertEquals(Timesheet_Model_Timesheet::RELATION_TYPE_CUSTOMER, $TimesheetUpdated['relations'][0]['type']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $TimesheetUpdated['relations'][1]['related_id']);
        $this->assertEquals(Timesheet_Model_Timesheet::RELATION_TYPE_ACCOUNT, $TimesheetUpdated['relations'][1]['type']);
        
        // cleanup
        $this->_backend->deleteTimesheets($TimesheetData['id']);
        Addressbook_Controller_Contact::getInstance()->delete($TimesheetUpdated['relations'][0]['related_id']);
        $this->_decreaseNumber();
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
        $Timesheet = $this->_getTimesheet();
        $TimesheetData = $this->_backend->saveTimesheet(Zend_Json::encode($Timesheet->toArray()));
        
        // search & check
        $search = $this->_backend->searchTimesheets(Zend_Json::encode($this->_getFilter()), Zend_Json::encode($this->_getPaging()));
        $this->assertEquals($Timesheet->title, $search['results'][0]['title']);
        $this->assertEquals(1, $search['totalcount']);
        
        // cleanup
        $this->_backend->deleteTimesheets($TimesheetData['id']);
        $this->_decreaseNumber();
        */        
    }
    
    /************ protected helper funcs *************/
    
    /**
     * get Timesheet
     *
     * @return Timesheet_Model_Timesheet
     */
    protected function _getTimesheet()
    {
        return new Timesheet_Model_Timesheet(array(
            'title'         => 'phpunit Timesheet',
            'description'   => 'blabla',
            //'id'            => Tinebase_Record_Abstract::generateUID()
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
            'sort' => 'number',
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
            'query' => 'blabla'     
        );        
    }
    
}
