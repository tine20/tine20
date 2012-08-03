<?php
/**
 * TimeZoneConvert
 *
 * @package     TimeZoneConvert
 * @subpackage  Tests
 * @license     MIT, BSD, and GPL
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 */

class TimeZoneConvert_TimeZoneConvertTests extends PHPUnit_Framework_TestCase
{
    public function testFromVTimeZone()
    {
        $vtimezone = TimeZoneConvert_VTimeZoneTests::$thunderbirdEuropeBerlin;
        $prodId = '-//Mozilla.org/NONSGML Mozilla Calendar V1.1//EN';
        $expectedTimezone = 'Europe/Berlin';
        
        $timezone = TimeZoneConvert::fromVTimeZone($vtimezone, $prodId, $expectedTimezone);
        
        $this->assertEquals('Europe/Berlin', $timezone->getName());
    }
    
    public function testFromVTimeZoneWithJunk()
    {
        $defaultTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Paris');
        $vtimezone = 'JUNK';
        $prodId = '-//Mozilla.org/NONSGML Mozilla Calendar V1.1//EN';
        $expectedTimezone = 'MoreJunk';
        
        $timezoneFromDefault = TimeZoneConvert::fromVTimeZone($vtimezone, $prodId, $expectedTimezone);
        $timezoneFromExpectation = TimeZoneConvert::fromVTimeZone($vtimezone, $prodId, 'Europe/Berlin');
        
        date_default_timezone_set($defaultTimezone);
        
        $this->assertEquals('Europe/Paris', $timezoneFromDefault->getName());
        $this->assertEquals('Europe/Berlin', $timezoneFromExpectation->getName());
    }
    public function testToVTimeZone()
    {
        $vtimezone = TimeZoneConvert::toVTimeZone('Europe/Paris');
        $this->assertTrue(strstr($vtimezone, 'RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU') !== FALSE);
    }
}