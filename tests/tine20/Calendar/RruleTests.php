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
            'originator_tz' => 'Europe/Berlin'
        ));
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        // note: 2009-03-29 Europe/Berlin switched to DST
        $from = new Zend_Date('2009-03-25 00:00:00', Tinebase_Record_Abstract::ISO8601LONG);
        $until = new Zend_Date('2009-04-01 23:59:59', Tinebase_Record_Abstract::ISO8601LONG);
        
        $recurSet = Calendar_Model_Rrule::computeRecuranceSet($event, $exceptions, $from, $until);
        
        $this->assertEquals('2009-03-25 08:00:00', $recurSet[0]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('2009-03-27 08:00:00', $recurSet[1]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('2009-03-29 07:00:00', $recurSet[2]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('2009-03-31 07:00:00', $recurSet[3]->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals(4, count($recurSet));
    }
    
}
    

if (PHPUnit_MAIN_METHOD == 'Calendar_RruleTests::main') {
    Calendar_RruleTests::main();
}
