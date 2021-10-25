<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2014 Metaways Infosystems GmbH (http://www.metaways.de)
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

        $this->_deleteTimeSheets[] = $timesheetData['id'];
        $this->_deleteTimeAccounts[] = $timesheetData['timeaccount_id']['id'];

        Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
        $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        // export & check
        $csvExportClass = new Timetracker_Export_Csv(new Timetracker_Model_TimesheetFilter($this->_getTimesheetFilter()));
        $result = $csvExportClass->generate();
        
        $this->assertTrue(file_exists($result));
        
        $file = implode('', file($result));
        $this->assertEquals(1, preg_match("/". $timesheetData['description'] ."/", $file), 'no description');
        $this->assertEquals(1, preg_match("/description/", $file), 'no headline');
        
        // cleanup / delete file
        unlink($result);
        // cleanup, if the timeaccount is still in use,  need to set confirm request context for delete method
        Timetracker_Controller_Timeaccount::getInstance()->setRequestContext(['confirm' => true]);
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

        $this->_deleteTimeAccounts[] = $timeaccountData['id'];

        Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
        $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
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

        $this->_deleteTimeSheets[] = $timesheetData['id'];
        $this->_deleteTimeAccounts[] = $timesheetData['timeaccount_id']['id'];

        Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
        $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        // export & check
        $options = ($_definitionId === NULL) ? array('format' => 'ods') : array('definitionId' => $_definitionId);
        $odsExportClass = Tinebase_Export::factory(new Timetracker_Model_TimesheetFilter($this->_getTimesheetFilter()), $options);
        $result = $odsExportClass->generate();
        
        $this->assertTrue(file_exists($result));
        
        $xmlBody = $odsExportClass->getDocument()->asXML();
        
        $doc = $odsExportClass->getDocument()->getBody();
        
        $i18n = Tinebase_Translation::getTranslation('Timetracker');
        
        // the first line must not be empty
        foreach ($odsExportClass->getDocument()->getBody()->getTables() as $table) {
            $body = $table->getBody();

            foreach ($body->xpath('//table:table') as $tbl) {
                $cells = $tbl->xpath('//table:table-cell');
                foreach ($cells as $cell) {
                    $xpath = $cell->xpath('//text:p');
                    $this->assertEquals($i18n->_('Staff Member'), (string) $xpath[0]);
                    $this->assertEquals($i18n->_('Timeaccount Number'), (string) $xpath[1]);
                }
            }
        }
        
        $this->assertEquals(1, preg_match("/0.5/", $xmlBody), 'no duration');
        $this->assertEquals(1, preg_match("/". $timesheetData['description'] ."/", $xmlBody), 'no description');
        
        // test translation of headers
        $i18nValue = $i18n->_('Description');
        $match = preg_match("/".$i18nValue."/", $xmlBody);
        $this->assertEquals(1, $match, 'no headline');
        
        // cleanup / delete file
        unlink($result);
    }
}
