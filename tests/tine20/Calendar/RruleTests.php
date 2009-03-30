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
    
}
    

if (PHPUnit_MAIN_METHOD == 'Calendar_RruleTests::main') {
    Calendar_RruleTests::main();
}
