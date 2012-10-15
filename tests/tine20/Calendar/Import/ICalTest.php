<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Calendar_Import_ICal
 */
class Calendar_Import_ICalTest extends Calendar_TestCase
{
    public function testImportSimpleFromString()
    {
        $importer = new Calendar_Import_Ical(array(
            'importContainerId' => $this->_testCalendar->getId(),
        ));

        $icalData = file_get_contents(dirname(__FILE__) . '/files/simple.ics');
        $importer->importData($icalData);
        
        $events = Calendar_Controller_Event::getInstance()->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId())
        )), NULL);
        
        $this->assertEquals(6, $events->count(), 'events was not imported');
        
        $startbucks = $events->find('uid', '3632597');
        $this->assertEquals('Calendar_Model_Event', get_class($startbucks));
        $this->assertEquals('2008-11-05 15:00:00', $startbucks->dtstart->format(Tinebase_Record_Abstract::ISO8601LONG));
    }
    
    public function testImportSimpleFromFile()
    {
        $importer = new Calendar_Import_Ical(array(
            'importContainerId' => $this->_testCalendar->getId(),
        ));
        
        $importer->importFile(dirname(__FILE__) . '/files/simple.ics');
        $events = Calendar_Controller_Event::getInstance()->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId())
        )), NULL);
        
        $this->assertEquals(6, $events->count(), 'events where not imported');
        
        $startbucks = $events->find('uid', '3632597');
        $this->assertEquals('Calendar_Model_Event', get_class($startbucks));
        $this->assertEquals('2008-11-05 15:00:00', $startbucks->dtstart->format(Tinebase_Record_Abstract::ISO8601LONG));
    }
    
    public function testImportRecur()
    {
        $importer = new Calendar_Import_Ical(array(
            'importContainerId' => $this->_testCalendar->getId(),
        ));
        
        $importer->importFile(dirname(__FILE__) . '/files/XMAS_DE.ics');
        
        $events = Calendar_Controller_Event::getInstance()->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId()),
            array('field' => 'period', 'operator' => 'within', 'value' => array(
                'from'  => '2015-12-23 22:00:00',
                'until' => '2015-12-26 22:00:00'
            )),
        )), NULL);
        
        $this->assertEquals(3, $events->count(), 'events where not imported');
    }
    
    public function testImportHorde()
    {
        $importer = new Calendar_Import_Ical(array(
            'importContainerId' => $this->_testCalendar->getId(),
        ));
        
        $importer->importFile(dirname(__FILE__) . '/files/horde.ics');
        $this->_checkHordeImport();
    }
    
    /**
     * asserts succesful horde import
     * 
     * @param string $failMessage
     */
    protected function _checkHordeImport($failMessage = '')
    {
        $events = Calendar_Controller_Event::getInstance()->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId())
        )), NULL);
        
        $this->assertEquals(1, $events->count(), 'events where not imported ' . $failMessage);
    }
    
    /**
     * test for gracefull shutdown if ical is malformated
     */
    public function testImportHordeBroken()
    {
        $importer = new Calendar_Import_Ical(array(
            'importContainerId' => $this->_testCalendar->getId(),
        ));
        
        $this->setExpectedException('Calendar_Exception_IcalParser');
        $importer->importFile(dirname(__FILE__) . '/files/horde_broken.ics');
    }
    
    public function testImportOutlook12()
    {
        $importer = new Calendar_Import_Ical(array(
            'importContainerId' => $this->_testCalendar->getId(),
        ));
        
        $importer->importFile(dirname(__FILE__) . '/files/outlook12.ics');
        $events = Calendar_Controller_Event::getInstance()->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId())
        )), NULL);
        
        $this->assertEquals(1, $events->count(), 'events where not imported');
    }
    
    /**
     * test ical cli import
     * 
     * @see 0007104: Calender Import Crashes
     */
    public function testCliImport()
    {
        $this->_testNeedsTransaction();
        
        $cmd = realpath(__DIR__ . "/../../../../tine20/tine20.php") . ' --method Calendar.import ' .
            'plugin=Calendar_Import_Ical importContainerId=' . $this->_testCalendar->getId() .
            ' ' . dirname(__FILE__) . '/files/horde.ics';
        
        $cmd = TestServer::assembleCliCommand($cmd, TRUE);
        
        exec($cmd, $output);
        $failMessage = print_r($output, TRUE);
        $this->_checkHordeImport($failMessage);
    }
}
