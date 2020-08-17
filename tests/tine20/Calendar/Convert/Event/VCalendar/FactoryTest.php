<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test class for Calendar_Convert_Event_VCalendar_Factory
 */
class Calendar_Convert_Event_VCalendar_FactoryTest extends TestCase
{
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
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
     * test factory with useragent string from iPhone OS 3
     */
    public function testUserAgentIPhoneOS3()
    {
        list($backend, $version) = Calendar_Convert_Event_VCalendar_Factory::parseUserAgent('DAVKit/4.0 (728.4); iCalendar/1 (42.1); iPhone/3.1.3 7E18');
        
        $this->assertEquals(Calendar_Convert_Event_VCalendar_Factory::CLIENT_IPHONE, $backend);
        $this->assertEquals('3.1.3', $version);
    }            
    
    /**
     * test factory with useragent string from iPhone OS 4
     */
    public function testUserAgentIPhoneOS4()
    {
        list($backend, $version) = Calendar_Convert_Event_VCalendar_Factory::parseUserAgent('DAVKit/5.0 (767); iCalendar/5.0 (79); iPhone/4.2.1 8C148');
        
        $this->assertEquals(Calendar_Convert_Event_VCalendar_Factory::CLIENT_IPHONE, $backend);
        $this->assertEquals('4.2.1', $version);
    }            
    
    /**
     * test factory with useragent string from iPhone OS 5
     */
    public function testUserAgentIPhoneOS5()
    {
        list($backend, $version) = Calendar_Convert_Event_VCalendar_Factory::parseUserAgent('iOS/5.0.1 (9A405) dataaccessd/1.0');
        
        $this->assertEquals(Calendar_Convert_Event_VCalendar_Factory::CLIENT_IPHONE, $backend);
        $this->assertEquals('5.0.1', $version);
    }            
    
    /**
     * test factory with useragent string from MacOS X
     */
    public function testUserAgentMacOSX()
    {
        $agents = array(
            array('version' => '10.6.8', 'identifier' => 'DAVKit/4.0.3 (732.2); CalendarStore/4.0.4 (997.7); iCal/4.0.4 (1395.7); Mac OS X/10.6.8 (10K549)'),
            array('version' => '10.7.1', 'identifier' => 'CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)'),
            array('version' => '10.8',   'identifier' => 'Mac OS X/10.8 (12A269) CalendarAgent/47'),
            array('version' => '10.9',   'identifier' => 'Mac_OS_X/10.9 (13A603) CalendarAgent/174'),
            array('version' => '11.0',   'identifier' => 'macOS/11.0 (20A5343i) CalendarAgent/950'),
        );
        
        foreach($agents as $agent) {
            list($backend, $version) = Calendar_Convert_Event_VCalendar_Factory::parseUserAgent($agent["identifier"]);
        
            $this->assertEquals(Calendar_Convert_Event_VCalendar_Factory::CLIENT_MACOSX, $backend, $agent["identifier"]);
            $this->assertEquals($agent["version"], $version, $agent["identifier"]);
        }
    }
    
    /**
     * test factory with useragent string from thunderbird 
     */
    public function testUserAgentThunderbird()
    {
        list($backend, $version) = Calendar_Convert_Event_VCalendar_Factory::parseUserAgent('Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13');
        
        $this->assertEquals(Calendar_Convert_Event_VCalendar_Factory::CLIENT_THUNDERBIRD, $backend);
        $this->assertEquals('1.0b2', $version);
    }            

    /**
     * test factory with useragent string from thunderbird 
     */
    public function testUserAgentThunderbird2()
    {
        list($backend, $version) = Calendar_Convert_Event_VCalendar_Factory::parseUserAgent('Mozilla/5.0 (Windows; U; Windows NT 5.1; de; rv:1.9.2.24) Gecko/20111103 Lightning/1.0b2 Thunderbird/3.1.16');
        
        $this->assertEquals(Calendar_Convert_Event_VCalendar_Factory::CLIENT_THUNDERBIRD, $backend);
        $this->assertEquals('1.0b2', $version);
    }
                
    /**
     * test factory with useragent string from thunderbird 
     */
    public function testUserAgentIceowl()
    {
        list($backend, $version) = Calendar_Convert_Event_VCalendar_Factory::parseUserAgent('Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.1.16) Gecko/20111110 Iceowl/1.0b1 Icedove/3.0.11');
        
        $this->assertEquals(Calendar_Convert_Event_VCalendar_Factory::CLIENT_THUNDERBIRD, $backend);
        $this->assertEquals('1.0b1', $version);
    }

    /**
     * test factory with useragent string from thunderbird
     */
    public function testUserAgentThunderbird78()
    {
        list($backend, $version) = Calendar_Convert_Event_VCalendar_Factory::parseUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:78.0) Gecko/20100101 Thunderbird/78.0');

        $this->assertEquals(Calendar_Convert_Event_VCalendar_Factory::CLIENT_THUNDERBIRD, $backend);
        $this->assertEquals('78.0', $version);
    }
}
