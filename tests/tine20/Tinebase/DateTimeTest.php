<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_DateTimeTest::main');
}

/**
 * Test class for Tinebase_AsyncJob
 */
class Tinebase_DateTimeTest extends PHPUnit_Framework_TestCase
{
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp() {}

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown() {}
    
    public function testConstructFromIsoString()
    {
        $dt = new Tinebase_DateTime('2010-06-25 18:04:00');
        $this->assertEquals('1277489040', $dt->getTimestamp());
        
        // after 32 Bit timestamp overflow (2038-01-19 03:14:07)
        $dt = new Tinebase_DateTime('2040-06-25 18:04:00');
        $this->assertEquals('2224260240', $dt->getTimestamp());
    }
    
    /**
     * check if exception is thrown when trying to compare against an not DateTime
     */
    public function testCompareExceptionByNoDateTime()
    {
        $dt = Tinebase_DateTime::now();
        
        $this->setExpectedException('Tinebase_Exception_Date');
        $dt->compare('2010-11-13 09:36:00');
    }
    
    public function testModifyReturnValue()
    {
        $dt = new Tinebase_DateTime('2010-11-25 12:11:00');
        $instance = $dt->addDay(-1);
        
        $this->assertEquals('Tinebase_DateTime', get_class($instance), 'wrong type');
    }
    
    public function testSleepWakeup()
    {
        $dt = new Tinebase_DateTime('2010-11-29 14:14:00', new DateTimeZone('Indian/Mauritius'));
        $sdt = serialize($dt);
        
        $wdt = unserialize($sdt);
        
        // toy with it -> see if bug http://bugs.php.net/bug.php?id=46891 comes:
        //  "The DateTime object has not been correctly initialized by its constructor"
        $wdt->addHour(1);
        
        $this->assertEquals('2010-11-29 15:14:00', $wdt->format(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('Indian/Mauritius', $wdt->getTimezone()->getName());
    }
    
    public function testHasTime()
    {
        $date = new Tinebase_DateTime('2011-11-11 11:11:11', 'Europe/Berlin');
        $this->assertTrue($date->hasTime(), 'date must have the hasTime flag');
        
        $date->hasTime(FALSE);
        $this->assertFalse($date->hasTime(), 'date must not have the hasTime flag');
        $this->assertEquals('00:00:00', $date->format('H:i:s'), 'time info has not been reset');
        
        $date->setTimezone('Asia/Tehran');
        $this->assertEquals('2011-11-11', $date->format('Y-m-d'), 'date must not chage');
        $this->assertEquals('00:00:00', $date->format('H:i:s'), 'time must not chage');
    }
    
    /**
     * test create from DateTime
     * @see http://forge.tine20.org/mantisbt/view.php?id=5020
     */
    public function testFromDateTime()
    {
        $dt = new DateTime('2012-01-16 14:30:00', new DateTimeZone('UTC'));
        
        $tdt = new Tinebase_DateTime($dt);
        
        $this->assertTrue($tdt instanceof Tinebase_DateTime);
        $this->assertEquals('2012-01-16 14:30:00', $tdt->toString());
        
        $dtz = new DateTimeZone('Europe/Berlin');
        $tdt = new Tinebase_DateTime($dt, $dtz);
        
        $this->assertEquals('UTC', $dt->getTimezone()->getName(), 'original timzone changed');
        $this->assertEquals('2012-01-16 15:30:00', $tdt->toString());
    }
}

if (PHPUnit_MAIN_METHOD == 'Tinebase_DateTimeTest::main') {
    Tinebase_DateTimeTest::main();
}

