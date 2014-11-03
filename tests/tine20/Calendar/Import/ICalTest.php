<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * 
 */

/**
 * Test class for Calendar_Import_ICal
 */
class Calendar_Import_ICalTest extends Calendar_TestCase
{
    /**
     * testImportSimpleFromString
     */
    public function testImportSimpleFromString()
    {
        $importEvents = $this->_importHelper('simple.ics', 6, /* $fromString = */ true);
        
        $startbucks = $importEvents->find('uid', '3632597');
        $this->assertEquals('Calendar_Model_Event', get_class($startbucks));
        $this->assertEquals('2008-11-05 15:00:00', $startbucks->dtstart->format(Tinebase_Record_Abstract::ISO8601LONG));
    }
    
    /**
     * testImportSimpleFromFile
     */
    public function testImportSimpleFromFile()
    {
        $importEvents = $this->_importHelper('simple.ics', 6);
        
        $startbucks = $importEvents->find('uid', '3632597');
        $this->assertEquals('Calendar_Model_Event', get_class($startbucks));
        $this->assertEquals('2008-11-05 15:00:00', $startbucks->dtstart->format(Tinebase_Record_Abstract::ISO8601LONG));
    }
    
    /**
     * testImportRecur
     */
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
        
        $this->assertEquals(3, $events->count(), 'Events were not imported');
    }
    
    public function testImportHorde()
    {
        $importer = new Calendar_Import_Ical(array(
            'importContainerId' => $this->_testCalendar->getId(),
        ));
        
        $importer->importFile(dirname(__FILE__) . '/files/horde.ics');
        $this->_checkImport();
    }
    
    /**
     * asserts succesful horde import
     * 
     * @param string $failMessage
     * @param integer $assertNumber
     */
    protected function _checkImport($failMessage = '', $assertNumber = 1)
    {
        $events = Calendar_Controller_Event::getInstance()->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId())
        )), NULL);
        
        $this->assertEquals($assertNumber, $events->count(), 'Events were not imported ' . $failMessage);
    }
    
    /**
     * test for graceful shutdown if ical is malformatted
     */
    public function testImportHordeBroken()
    {
        $importer = new Calendar_Import_Ical(array(
            'importContainerId' => $this->_testCalendar->getId(),
        ));
        
        try {
            $importer->importFile(dirname(__FILE__) . '/files/horde_broken.ics');
            $this->fail('expected Calendar_Exception_IcalParser');
        } catch (Calendar_Exception_IcalParser $ceip) {
            $this->assertEquals('Sabre\VObject\EofException', get_class($ceip->getParseError()));
        }
    }
    
    /**
     * testImportOutlook12
     */
    public function testImportOutlook12()
    {
        $importEvents = $this->_importHelper('outlook12.ics');
    }
    
    /**
     * testImportExchange2007
     */
    public function testImportExchange2007()
    {
        $importEvents = $this->_importHelper('exchange2007_mail_anhang.ics');
        $event = $importEvents->getFirstRecord();
        $this->assertEquals('Blocker Wie bleibe ich gesund? Ernährung', $event->summary);
    }
    
    /**
     * import helper
     * 
     * @param string $filename
     * @param integer $expectedNumber of imported events
     * @param boolean $fromString
     * @return Tinebase_Record_RecordSet
     */
    protected function _importHelper($filename, $expectedNumber = 1, $fromString = false)
    {
        $importer = new Calendar_Import_Ical(array(
            'importContainerId' => $this->_testCalendar->getId(),
        ));
        
        if ($fromString) {
            $icalData = file_get_contents(dirname(__FILE__) . '/files/' . $filename);
            $importer->importData($icalData);
        } else {
            $importer->importFile(dirname(__FILE__) . '/files/' . $filename);
        }
        
        $events = Calendar_Controller_Event::getInstance()->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId())
        )), NULL);
        
        $this->assertEquals($expectedNumber, $events->count(), 'Events were not imported');
        return $events;
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
        $this->_checkImport($failMessage);
    }
    
    /**
     * testImportTwice (forceUpdateExisting)
     * 
     * @see 0008652: Import von .ics-Dateien in Kalender schlägt fehl
     */
    public function testImportTwice()
    {
        $this->_testNeedsTransaction();
        
        $cmd = realpath(__DIR__ . "/../../../../tine20/tine20.php") . ' --method Calendar.import ' .
            'plugin=Calendar_Import_Ical forceUpdateExisting=1 importContainerId=' . $this->_testCalendar->getId() .
            ' ' . dirname(__FILE__) . '/files/termine.ics';
        
        $cmd = TestServer::assembleCliCommand($cmd, TRUE);
        
        exec($cmd, $output);
        $failMessage = print_r($output, TRUE);
        $this->_checkImport($failMessage);
        
        // second time
        exec($cmd, $output);
        $failMessage = print_r($output, TRUE);
        $this->_checkImport($failMessage);
    }
    
    /**
     * testImportRruleNormalize
     * 
     * @see 0009856: ics import: recurring events one day earlier
     */
    public function testImportRruleNormalize()
    {
        $importer = new Calendar_Import_Ical(array(
            'importContainerId' => $this->_testCalendar->getId(),
        ));
        
        $importer->importFile(dirname(__FILE__) . '/files/ni-zsk.ics');
        
        // fetch first of may in 2014
        $from = new Tinebase_DateTime('2014-04-23 22:00:00');
        $until = new Tinebase_DateTime('2014-05-23 22:00:00');
        $events = Calendar_Controller_Event::getInstance()->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId()),
            array('field' => 'period', 'operator' => 'within', 'value' => array(
                'from'  => $from->toString(),
                'until' => $until->toString()
            )),
        )), NULL);
        Calendar_Model_Rrule::mergeRecurrenceSet($events, $from, $until);
        $firstOfMay2014 = $events[1];
        
        $this->assertEquals('2014-04-30 22:00:00', $firstOfMay2014->dtstart);
    }
}
