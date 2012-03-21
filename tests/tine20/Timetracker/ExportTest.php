<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Timetracker_Export
 */
class Timetracker_ExportTest extends Timetracker_AbstractTest
{
    /**
     * try to export Timesheets
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
     */
    public function testExportTimesheetsOds()
    {
        Tinebase_Core::getPreference('Timetracker')->setValue(Timetracker_Preference::TSODSEXPORTCONFIG, 'ts_default_ods');
        $this->_exportTsOds();
    }

    /**
     * try to export Timesheets (as ods) with definition id
     */
    public function testExportTimesheetsOdsWithDefId()
    {
        $definitions = Tinebase_ImportExportDefinition::getInstance()->getExportDefinitionsForApplication(Tinebase_Application::getInstance()->getApplicationByName('Timetracker'));
        $defId = '';
        foreach ($definitions as $definition) {
            if ($definition->plugin == 'Timetracker_Export_Ods_Timesheet') {
                $defId = $definition->getId();
            }
        }
        
        $this->_exportTsOds($defId);
    }
    
    /**
     * try to export Timeaccounts (as ods)
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
    protected function _exportTsOds($_definitionId = NULL)
    {
        // create
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_json->saveTimesheet($timesheet->toArray());
        
        // export & check
        $options = ($_definitionId === NULL) ? array('format' => 'ods') : array('definitionId' => $_definitionId);
        $odsExportClass = Tinebase_Export::factory(new Timetracker_Model_TimesheetFilter($this->_getTimesheetFilter()), $options);
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
