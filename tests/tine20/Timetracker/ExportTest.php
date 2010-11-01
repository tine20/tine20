<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Timetracker_ExportTest::main');
}

/**
 * Test class for Timetracker_Export
 */
class Timetracker_ExportTest extends Timetracker_AbstractTest
{
    /**
     * try to export Timesheets
     * - this is no real json test
     * 
     * @todo move that to separate export test?
     */
    public function testExportTimesheetsCsv()
    {
        // create
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_json->saveTimesheet($timesheet->toArray());
        
        // export & check
        $csvExportClass = new Timetracker_Export_Csv(new Timetracker_Model_TimesheetFilter($this->_getTimesheetFilter()));
        $result = $csvExportClass->generate();
        
        $this->assertTrue(file_exists($result));
        
        $file = implode('', file($result));
        $this->assertEquals(1, preg_match("/". $timesheetData['description'] ."/", $file), 'no description'); 
        $this->assertEquals(1, preg_match("/description/", $file), 'no headline'); 
        
        // cleanup / delete file
        unlink($result);
        $this->_json->deleteTimeaccounts($timesheetData['timeaccount_id']['id']);
    }
    
    /**
     * try to export Timesheets (as ods)
     * - this is no real json test
     * 
     */
    public function testExportTimesheetsOds()
    {
        Tinebase_Core::getPreference('Timetracker')->setValue(Timetracker_Preference::TSODSEXPORTCONFIG, 'ts_default_ods');
        $this->_exportTsOds();
    }
    
    /**
     * try to export Timeaccounts (as ods)
     * - this is no real json test
     * 
     * @todo activate headline check again
     * @todo check if user is correctly resolved
     */
    public function testExportTimeaccountsOds()
    {
        // create
        $timeaccount = $this->_getTimeaccount();
        $timeaccountData = $this->_json->saveTimeaccount($timeaccount->toArray());
        
        // export & check
        $odsExportClass = Tinebase_Export::factory(new Timetracker_Model_TimeaccountFilter($this->_getTimeaccountFilter()), 'ods');
        $result = $odsExportClass->generate();
        
        $this->assertTrue(file_exists($result));
        
        $xmlBody = $odsExportClass->getDocument()->asXML();
        $this->assertEquals(1, preg_match("/". $timeaccountData['description'] ."/", $xmlBody), 'no description'); 
        
        // cleanup / delete file
        unlink($result);
        $this->_json->deleteTimeaccounts($timeaccountData['id']);
    }
    
    /**
     * do ods export
     * 
     * @return void
     * 
     * @todo check custom fields
     */
    protected function _exportTsOds()
    {
        // create
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_json->saveTimesheet($timesheet->toArray());
        $this->_toDeleteIds['ta'][] = $timesheetData['timeaccount_id']['id'];
        
        // export & check
        $odsExportClass = Tinebase_Export::factory(new Timetracker_Model_TimesheetFilter($this->_getTimesheetFilter()), 'ods');
        $result = $odsExportClass->generate();
        
        $this->assertTrue(file_exists($result));
        
        $xmlBody = $odsExportClass->getDocument()->asXML();    
        $this->assertEquals(1, preg_match("/0.5/", $xmlBody), 'no duration'); 
        $this->assertEquals(1, preg_match("/". $timesheetData['description'] ."/", $xmlBody), 'no description'); 
        $this->assertEquals(1, preg_match("/Description/", $xmlBody), 'no headline'); 
        
        // cleanup / delete file
        unlink($result);
    }
}
