<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Calendar_RruleTests::main');
}

/**
 * Test class for Calendar_Model_Rrule
 * 
 * @package     Calendar
 */
class Calendar_RruleTests extends PHPUnit_Framework_TestCase
{
    
    public function setUp()
    {
        
    }
    
    public function testSetFromString()
    {
        $rrule = new Calendar_Model_Rrule(array());
        $rrule->setFromString("FREQ=WEEKLY;INTERVAL=3;UNTIL=2009-06-05 00:00:00;WKST=SU;BYDAY=TU,TH");
        
        $this->assertEquals(Calendar_Model_Rrule::FREQ_WEEKLY, $rrule->freq);
        $this->assertTrue($rrule->until->equals(new Zend_Date('2009-06-05 00:00:00', Calendar_Model_Rrule::ISO8601LONG)));
    }
    
    public function testToString()
    {
        $rruleString = "FREQ=WEEKLY;INTERVAL=3;UNTIL=2009-06-05 00:00:00;WKST=SU;BYDAY=TU,TH";
        $rrule = new Calendar_Model_Rrule(array());
        $rrule->setFromString($rruleString);
        
        $this->assertEquals($rruleString, (string)$rrule);
    }
    
    public function testCalcDaily()
    {
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'change t-shirt',
            'dtstart'       => '1979-06-05 08:00:00',
            'dtend'         => '1979-06-05 08:05:00',
            'rrule'         => 'FREQ=DAILY;INTERVAL=2;UNTIL=2009-04-01 08:00:00',
            'exdate'        => '2009-03-31 07:00:00',
            'originator_tz' => 'Europe/Berlin'
        ));
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event', array(
            array(
                'uid'           => $event->uid,
                'summary'       => 'take the blue t-shirt',
                'dtstart'       => '2009-03-25 08:00:00',
                'dtend'         => '2009-03-25 08:05:00',
                'recurid'       => $event->uid . '-' . '2009-03-25 08:00:00'
            )
        ));
        
        // note: 2009-03-29 Europe/Berlin switched to DST
        $from = new Zend_Date('2009-03-23 00:00:00', Tinebase_Record_Abstract::ISO8601LONG);
        $until = new Zend_Date('2009-04-03 23:59:59', Tinebase_Record_Abstract::ISO8601LONG);
        
        $recurSet = Calendar_Model_Rrule::computeRecuranceSet($event, $exceptions, $from, $until);
        
        $this->assertEquals('2009-03-23 08:00:00', $recurSet[0]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('2009-03-27 08:00:00', $recurSet[1]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('2009-03-29 07:00:00', $recurSet[2]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
        //$this->assertEquals('2009-04-02 07:00:00', $recurSet[3]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals(3, count($recurSet));
        
        // lets also cover the case when recurevent start during calcualtion period:
        $from = new Zend_Date('1979-06-01 00:00:00', Tinebase_Record_Abstract::ISO8601LONG);
        $until = new Zend_Date('1979-06-14 23:59:59', Tinebase_Record_Abstract::ISO8601LONG);
        
        $recurSet = Calendar_Model_Rrule::computeRecuranceSet($event, $exceptions, $from, $until);
        $this->assertEquals(4, count($recurSet));
    }
    
    public function testGetNextWday()
    {
        $date = new Zend_Date('2009-04-08 00:00:00', Tinebase_Record_Abstract::ISO8601LONG);
        $this->assertEquals('2009-04-12 00:00:00', Calendar_Model_Rrule::getNextWday($date, Calendar_Model_Rrule::WDAY_SUNDAY)->toString(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('2009-04-09 00:00:00', Calendar_Model_Rrule::getNextWday($date, Calendar_Model_Rrule::WDAY_THURSDAY)->toString(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('2009-04-15 00:00:00', Calendar_Model_Rrule::getNextWday($date, Calendar_Model_Rrule::WDAY_WEDNESDAY)->toString(Tinebase_Record_Abstract::ISO8601LONG));
        
        $date = new Zend_Date('2009-04-05 00:00:00', Tinebase_Record_Abstract::ISO8601LONG);
        $this->assertEquals('2009-04-12 00:00:00', Calendar_Model_Rrule::getNextWday($date, Calendar_Model_Rrule::WDAY_SUNDAY)->toString(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('2009-04-09 00:00:00', Calendar_Model_Rrule::getNextWday($date, Calendar_Model_Rrule::WDAY_THURSDAY)->toString(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('2009-04-08 00:00:00', Calendar_Model_Rrule::getNextWday($date, Calendar_Model_Rrule::WDAY_WEDNESDAY)->toString(Tinebase_Record_Abstract::ISO8601LONG));
        
        $date = new Zend_Date('2009-04-04 00:00:00', Tinebase_Record_Abstract::ISO8601LONG);
        $this->assertEquals('2009-04-05 00:00:00', Calendar_Model_Rrule::getNextWday($date, Calendar_Model_Rrule::WDAY_SUNDAY)->toString(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('2009-04-09 00:00:00', Calendar_Model_Rrule::getNextWday($date, Calendar_Model_Rrule::WDAY_THURSDAY)->toString(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('2009-04-08 00:00:00', Calendar_Model_Rrule::getNextWday($date, Calendar_Model_Rrule::WDAY_WEDNESDAY)->toString(Tinebase_Record_Abstract::ISO8601LONG));
    }
    
    public function testCalcWeekly()
    {
        // note: 1979-06-05 was a tuesday
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'take a bath',
            'dtstart'       => '1979-06-05 17:00:00',
            'dtend'         => '1979-06-05 18:00:00',
            'rrule'         => 'FREQ=WEEKLY;INTERVAL=1;BYDAY=SU',
            'originator_tz' => 'Europe/Berlin'
        ));
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        $from = new Zend_Date('2009-06-01 00:00:00', Tinebase_Record_Abstract::ISO8601LONG);
        $until = new Zend_Date('2009-06-30 23:59:59', Tinebase_Record_Abstract::ISO8601LONG);
        
        $recurSet = Calendar_Model_Rrule::computeRecuranceSet($event, $exceptions, $from, $until);
        $this->assertEquals(4, count($recurSet));
        
        $from = new Zend_Date('2013-06-01 00:00:00', Tinebase_Record_Abstract::ISO8601LONG);
        $until = new Zend_Date('2013-06-30 23:59:59', Tinebase_Record_Abstract::ISO8601LONG);
        
        $recurSet = Calendar_Model_Rrule::computeRecuranceSet($event, $exceptions, $from, $until);
        $this->assertEquals(5, count($recurSet));
    }
    
    public function testCalcYearly()
    {
        /*
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'yearly 29.feb',
            'dtstart'       => '2008-02-29 08:00:00',
            'dtend'         => '2008-02-29 10:00:00',
            'rrule'         => 'FREQ=YEARLY;INTERVAL=1;BYMONTH=2',
            'originator_tz' => 'Europe/Berlin'
        ));
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        // note: 2009-03-29 Europe/Berlin switched to DST
        $from = new Zend_Date('2008-02-25 00:00:00', Tinebase_Record_Abstract::ISO8601LONG);
        $until = new Zend_Date('2013-03-01 23:59:59', Tinebase_Record_Abstract::ISO8601LONG);
        
        $recurSet = Calendar_Model_Rrule::computeRecuranceSet($event, $exceptions, $from, $until);
        */
    }
    
}
    

if (PHPUnit_MAIN_METHOD == 'Calendar_RruleTests::main') {
    Calendar_RruleTests::main();
}
