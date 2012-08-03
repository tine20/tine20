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

class TimeZoneConvert_TransitionTests extends PHPUnit_Framework_TestCase
{
    public function testMatchTimezoneRfc5545AmericaNewYork()
    {
        $transitions = self::getTransitions('rfc5545AmericaNewYork');
        
        $this->assertTrue(TimeZoneConvert_Transition::matchTimezone($transitions, new DateTimeZone('America/New_York')));
    }
    
    public function testGetMatchingTimezoneRfc5545AmericaNewYork()
    {
        $transitions = self::getTransitions('rfc5545AmericaNewYork');
        
        $timezone = TimeZoneConvert_Transition::getMatchingTimezone($transitions);
        
        $this->assertTrue($timezone instanceof DateTimeZone, 'timezone not found');
        $this->assertEquals('America/New_York', $timezone->getName());
    }
    
    public function testGetMatchingTimezoneCustomAsiaJerusalem()
    {
        $transitions = self::getTransitions('customAsiaJerusalem');
        
        $timezone = TimeZoneConvert_Transition::getMatchingTimezone($transitions);
        
        $this->assertTrue($timezone instanceof DateTimeZone, 'timezone not found');
        $this->assertEquals('Asia/Jerusalem', $timezone->getName());
    }
    
    public function testGetMatchingTimezoneThunderbirdEuropeBerlin()
    {
        $transitions = self::getTransitions('thunderbirdEuropeBerlin');
        
        $timezone = TimeZoneConvert_Transition::getMatchingTimezone($transitions);
        
        $this->assertTrue($timezone instanceof DateTimeZone, 'timezone not found');
        // NOTE: this is the first matching TZ
        $this->assertEquals('Africa/Ceuta', $timezone->getName());
    }
    
    public function testGetMatchingTimezoneThunderbirdEuropeBerlinWithExpectation()
    {
        $transitions = self::getTransitions('thunderbirdEuropeBerlin');
        
        $timezone = TimeZoneConvert_Transition::getMatchingTimezone($transitions, new DateTimeZone('Europe/Berlin'));
        
        $this->assertTrue($timezone instanceof DateTimeZone, 'timezone not found');
        $this->assertEquals('Europe/Berlin', $timezone->getName());
    }
    
    public static function getTransitions($tzid)
    {
        switch ($tzid) {
            case 'rfc5545AmericaNewYork':
                $transitionsData = unserialize('a:13:{i:0;O:26:"TimeZoneConvert_Transition":5:{s:2:"ts";i:-84387600;s:4:"date";s:24:"1967-04-30T07:00:00+0000";s:6:"offset";i:-14400;s:5:"isdst";b:1;s:4:"abbr";s:3:"EDT";}i:1;O:26:"TimeZoneConvert_Transition":5:{s:2:"ts";i:104914800;s:4:"date";s:24:"1973-04-29T07:00:00+0000";s:6:"offset";i:-14400;s:5:"isdst";b:1;s:4:"abbr";s:3:"EDT";}i:2;O:26:"TimeZoneConvert_Transition":5:{s:2:"ts";i:-68666400;s:4:"date";s:24:"1967-10-29T06:00:00+0000";s:6:"offset";i:-18000;s:5:"isdst";b:0;s:4:"abbr";s:3:"EST";}i:3;O:26:"TimeZoneConvert_Transition":5:{s:2:"ts";i:1162101600;s:4:"date";s:24:"2006-10-29T06:00:00+0000";s:6:"offset";i:-18000;s:5:"isdst";b:0;s:4:"abbr";s:3:"EST";}i:4;O:26:"TimeZoneConvert_Transition":5:{s:2:"ts";i:162370800;s:4:"date";s:24:"1975-02-23T07:00:00+0000";s:6:"offset";i:-14400;s:5:"isdst";b:1;s:4:"abbr";s:3:"EDT";}i:5;O:26:"TimeZoneConvert_Transition":5:{s:2:"ts";i:199263600;s:4:"date";s:24:"1976-04-25T07:00:00+0000";s:6:"offset";i:-14400;s:5:"isdst";b:1;s:4:"abbr";s:3:"EDT";}i:6;O:26:"TimeZoneConvert_Transition":5:{s:2:"ts";i:514969200;s:4:"date";s:24:"1986-04-27T07:00:00+0000";s:6:"offset";i:-14400;s:5:"isdst";b:1;s:4:"abbr";s:3:"EDT";}i:7;O:26:"TimeZoneConvert_Transition":5:{s:2:"ts";i:544604400;s:4:"date";s:24:"1987-04-05T07:00:00+0000";s:6:"offset";i:-14400;s:5:"isdst";b:1;s:4:"abbr";s:3:"EDT";}i:8;O:26:"TimeZoneConvert_Transition":5:{s:2:"ts";i:1143961200;s:4:"date";s:24:"2006-04-02T07:00:00+0000";s:6:"offset";i:-14400;s:5:"isdst";b:1;s:4:"abbr";s:3:"EDT";}i:9;O:26:"TimeZoneConvert_Transition":5:{s:2:"ts";i:1173596400;s:4:"date";s:24:"2007-03-11T07:00:00+0000";s:6:"offset";i:-14400;s:5:"isdst";b:1;s:4:"abbr";s:3:"EDT";}i:10;O:26:"TimeZoneConvert_Transition":5:{s:2:"ts";i:1331449200;s:4:"date";s:24:"2012-03-11T07:00:00+0000";s:6:"offset";i:-14400;s:5:"isdst";b:1;s:4:"abbr";s:3:"EDT";}i:11;O:26:"TimeZoneConvert_Transition":5:{s:2:"ts";i:1194156000;s:4:"date";s:24:"2007-11-04T06:00:00+0000";s:6:"offset";i:-18000;s:5:"isdst";b:0;s:4:"abbr";s:3:"EST";}i:12;O:26:"TimeZoneConvert_Transition":5:{s:2:"ts";i:1352008800;s:4:"date";s:24:"2012-11-04T06:00:00+0000";s:6:"offset";i:-18000;s:5:"isdst";b:0;s:4:"abbr";s:3:"EST";}}');
                break;
            case 'customAsiaJerusalem':
                $transitionsData = unserialize('a:59:{i:0;a:5:{s:2:"ts";i:1223161200;s:4:"date";s:24:"2008-10-04T23:00:00+0000";s:6:"offset";i:7200;s:5:"isdst";b:0;s:4:"abbr";s:3:"IST";}i:1;a:5:{s:2:"ts";i:1254006000;s:4:"date";s:24:"2009-09-26T23:00:00+0000";s:6:"offset";i:7200;s:5:"isdst";b:0;s:4:"abbr";s:3:"IST";}i:2;a:5:{s:2:"ts";i:1284246000;s:4:"date";s:24:"2010-09-11T23:00:00+0000";s:6:"offset";i:7200;s:5:"isdst";b:0;s:4:"abbr";s:3:"IST";}i:3;a:5:{s:2:"ts";i:1317510000;s:4:"date";s:24:"2011-10-01T23:00:00+0000";s:6:"offset";i:7200;s:5:"isdst";b:0;s:4:"abbr";s:3:"IST";}i:4;a:5:{s:2:"ts";i:1348354800;s:4:"date";s:24:"2012-09-22T23:00:00+0000";s:6:"offset";i:7200;s:5:"isdst";b:0;s:4:"abbr";s:3:"IST";}i:5;a:5:{s:2:"ts";i:1378594800;s:4:"date";s:24:"2013-09-07T23:00:00+0000";s:6:"offset";i:7200;s:5:"isdst";b:0;s:4:"abbr";s:3:"IST";}i:6;a:5:{s:2:"ts";i:1411858800;s:4:"date";s:24:"2014-09-27T23:00:00+0000";s:6:"offset";i:7200;s:5:"isdst";b:0;s:4:"abbr";s:3:"IST";}i:7;a:5:{s:2:"ts";i:1442703600;s:4:"date";s:24:"2015-09-19T23:00:00+0000";s:6:"offset";i:7200;s:5:"isdst";b:0;s:4:"abbr";s:3:"IST";}i:8;a:5:{s:2:"ts";i:1475967600;s:4:"date";s:24:"2016-10-08T23:00:00+0000";s:6:"offset";i:7200;s:5:"isdst";b:0;s:4:"abbr";s:3:"IST";}i:9;a:5:{s:2:"ts";i:1506207600;s:4:"date";s:24:"2017-09-23T23:00:00+0000";s:6:"offset";i:7200;s:5:"isdst";b:0;s:4:"abbr";s:3:"IST";}i:10;a:5:{s:2:"ts";i:1537052400;s:4:"date";s:24:"2018-09-15T23:00:00+0000";s:6:"offset";i:7200;s:5:"isdst";b:0;s:4:"abbr";s:3:"IST";}i:11;a:5:{s:2:"ts";i:1570316400;s:4:"date";s:24:"2019-10-05T23:00:00+0000";s:6:"offset";i:7200;s:5:"isdst";b:0;s:4:"abbr";s:3:"IST";}i:12;a:5:{s:2:"ts";i:1601161200;s:4:"date";s:24:"2020-09-26T23:00:00+0000";s:6:"offset";i:7200;s:5:"isdst";b:0;s:4:"abbr";s:3:"IST";}i:13;a:5:{s:2:"ts";i:1631401200;s:4:"date";s:24:"2021-09-11T23:00:00+0000";s:6:"offset";i:7200;s:5:"isdst";b:0;s:4:"abbr";s:3:"IST";}i:14;a:5:{s:2:"ts";i:1664665200;s:4:"date";s:24:"2022-10-01T23:00:00+0000";s:6:"offset";i:7200;s:5:"isdst";b:0;s:4:"abbr";s:3:"IST";}i:15;a:5:{s:2:"ts";i:1695510000;s:4:"date";s:24:"2023-09-23T23:00:00+0000";s:6:"offset";i:7200;s:5:"isdst";b:0;s:4:"abbr";s:3:"IST";}i:16;a:5:{s:2:"ts";i:1728169200;s:4:"date";s:24:"2024-10-05T23:00:00+0000";s:6:"offset";i:7200;s:5:"isdst";b:0;s:4:"abbr";s:3:"IST";}i:17;a:5:{s:2:"ts";i:1759014000;s:4:"date";s:24:"2025-09-27T23:00:00+0000";s:6:"offset";i:7200;s:5:"isdst";b:0;s:4:"abbr";s:3:"IST";}i:18;a:5:{s:2:"ts";i:1789858800;s:4:"date";s:24:"2026-09-19T23:00:00+0000";s:6:"offset";i:7200;s:5:"isdst";b:0;s:4:"abbr";s:3:"IST";}i:19;a:5:{s:2:"ts";i:1823122800;s:4:"date";s:24:"2027-10-09T23:00:00+0000";s:6:"offset";i:7200;s:5:"isdst";b:0;s:4:"abbr";s:3:"IST";}i:20;a:5:{s:2:"ts";i:1853362800;s:4:"date";s:24:"2028-09-23T23:00:00+0000";s:6:"offset";i:7200;s:5:"isdst";b:0;s:4:"abbr";s:3:"IST";}i:21;a:5:{s:2:"ts";i:1884207600;s:4:"date";s:24:"2029-09-15T23:00:00+0000";s:6:"offset";i:7200;s:5:"isdst";b:0;s:4:"abbr";s:3:"IST";}i:22;a:5:{s:2:"ts";i:1917471600;s:4:"date";s:24:"2030-10-05T23:00:00+0000";s:6:"offset";i:7200;s:5:"isdst";b:0;s:4:"abbr";s:3:"IST";}i:23;a:5:{s:2:"ts";i:1947711600;s:4:"date";s:24:"2031-09-20T23:00:00+0000";s:6:"offset";i:7200;s:5:"isdst";b:0;s:4:"abbr";s:3:"IST";}i:24;a:5:{s:2:"ts";i:1978556400;s:4:"date";s:24:"2032-09-11T23:00:00+0000";s:6:"offset";i:7200;s:5:"isdst";b:0;s:4:"abbr";s:3:"IST";}i:25;a:5:{s:2:"ts";i:2011820400;s:4:"date";s:24:"2033-10-01T23:00:00+0000";s:6:"offset";i:7200;s:5:"isdst";b:0;s:4:"abbr";s:3:"IST";}i:26;a:5:{s:2:"ts";i:2042060400;s:4:"date";s:24:"2034-09-16T23:00:00+0000";s:6:"offset";i:7200;s:5:"isdst";b:0;s:4:"abbr";s:3:"IST";}i:27;a:5:{s:2:"ts";i:2075324400;s:4:"date";s:24:"2035-10-06T23:00:00+0000";s:6:"offset";i:7200;s:5:"isdst";b:0;s:4:"abbr";s:3:"IST";}i:28;a:5:{s:2:"ts";i:2106169200;s:4:"date";s:24:"2036-09-27T23:00:00+0000";s:6:"offset";i:7200;s:5:"isdst";b:0;s:4:"abbr";s:3:"IST";}i:29;a:5:{s:2:"ts";i:2136409200;s:4:"date";s:24:"2037-09-12T23:00:00+0000";s:6:"offset";i:7200;s:5:"isdst";b:0;s:4:"abbr";s:3:"IST";}i:30;a:5:{s:2:"ts";i:1238112000;s:4:"date";s:24:"2009-03-27T00:00:00+0000";s:6:"offset";i:10800;s:5:"isdst";b:1;s:4:"abbr";s:13:"Jerusalem DST";}i:31;a:5:{s:2:"ts";i:1269561600;s:4:"date";s:24:"2010-03-26T00:00:00+0000";s:6:"offset";i:10800;s:5:"isdst";b:1;s:4:"abbr";s:13:"Jerusalem DST";}i:32;a:5:{s:2:"ts";i:1301616000;s:4:"date";s:24:"2011-04-01T00:00:00+0000";s:6:"offset";i:10800;s:5:"isdst";b:1;s:4:"abbr";s:13:"Jerusalem DST";}i:33;a:5:{s:2:"ts";i:1333065600;s:4:"date";s:24:"2012-03-30T00:00:00+0000";s:6:"offset";i:10800;s:5:"isdst";b:1;s:4:"abbr";s:13:"Jerusalem DST";}i:34;a:5:{s:2:"ts";i:1364515200;s:4:"date";s:24:"2013-03-29T00:00:00+0000";s:6:"offset";i:10800;s:5:"isdst";b:1;s:4:"abbr";s:13:"Jerusalem DST";}i:35;a:5:{s:2:"ts";i:1395964800;s:4:"date";s:24:"2014-03-28T00:00:00+0000";s:6:"offset";i:10800;s:5:"isdst";b:1;s:4:"abbr";s:13:"Jerusalem DST";}i:36;a:5:{s:2:"ts";i:1427414400;s:4:"date";s:24:"2015-03-27T00:00:00+0000";s:6:"offset";i:10800;s:5:"isdst";b:1;s:4:"abbr";s:13:"Jerusalem DST";}i:37;a:5:{s:2:"ts";i:1459468800;s:4:"date";s:24:"2016-04-01T00:00:00+0000";s:6:"offset";i:10800;s:5:"isdst";b:1;s:4:"abbr";s:13:"Jerusalem DST";}i:38;a:5:{s:2:"ts";i:1490918400;s:4:"date";s:24:"2017-03-31T00:00:00+0000";s:6:"offset";i:10800;s:5:"isdst";b:1;s:4:"abbr";s:13:"Jerusalem DST";}i:39;a:5:{s:2:"ts";i:1522368000;s:4:"date";s:24:"2018-03-30T00:00:00+0000";s:6:"offset";i:10800;s:5:"isdst";b:1;s:4:"abbr";s:13:"Jerusalem DST";}i:40;a:5:{s:2:"ts";i:1553817600;s:4:"date";s:24:"2019-03-29T00:00:00+0000";s:6:"offset";i:10800;s:5:"isdst";b:1;s:4:"abbr";s:13:"Jerusalem DST";}i:41;a:5:{s:2:"ts";i:1585267200;s:4:"date";s:24:"2020-03-27T00:00:00+0000";s:6:"offset";i:10800;s:5:"isdst";b:1;s:4:"abbr";s:13:"Jerusalem DST";}i:42;a:5:{s:2:"ts";i:1616716800;s:4:"date";s:24:"2021-03-26T00:00:00+0000";s:6:"offset";i:10800;s:5:"isdst";b:1;s:4:"abbr";s:13:"Jerusalem DST";}i:43;a:5:{s:2:"ts";i:1648771200;s:4:"date";s:24:"2022-04-01T00:00:00+0000";s:6:"offset";i:10800;s:5:"isdst";b:1;s:4:"abbr";s:13:"Jerusalem DST";}i:44;a:5:{s:2:"ts";i:1680220800;s:4:"date";s:24:"2023-03-31T00:00:00+0000";s:6:"offset";i:10800;s:5:"isdst";b:1;s:4:"abbr";s:13:"Jerusalem DST";}i:45;a:5:{s:2:"ts";i:1711670400;s:4:"date";s:24:"2024-03-29T00:00:00+0000";s:6:"offset";i:10800;s:5:"isdst";b:1;s:4:"abbr";s:13:"Jerusalem DST";}i:46;a:5:{s:2:"ts";i:1743120000;s:4:"date";s:24:"2025-03-28T00:00:00+0000";s:6:"offset";i:10800;s:5:"isdst";b:1;s:4:"abbr";s:13:"Jerusalem DST";}i:47;a:5:{s:2:"ts";i:1774569600;s:4:"date";s:24:"2026-03-27T00:00:00+0000";s:6:"offset";i:10800;s:5:"isdst";b:1;s:4:"abbr";s:13:"Jerusalem DST";}i:48;a:5:{s:2:"ts";i:1806019200;s:4:"date";s:24:"2027-03-26T00:00:00+0000";s:6:"offset";i:10800;s:5:"isdst";b:1;s:4:"abbr";s:13:"Jerusalem DST";}i:49;a:5:{s:2:"ts";i:1838073600;s:4:"date";s:24:"2028-03-31T00:00:00+0000";s:6:"offset";i:10800;s:5:"isdst";b:1;s:4:"abbr";s:13:"Jerusalem DST";}i:50;a:5:{s:2:"ts";i:1869523200;s:4:"date";s:24:"2029-03-30T00:00:00+0000";s:6:"offset";i:10800;s:5:"isdst";b:1;s:4:"abbr";s:13:"Jerusalem DST";}i:51;a:5:{s:2:"ts";i:1900972800;s:4:"date";s:24:"2030-03-29T00:00:00+0000";s:6:"offset";i:10800;s:5:"isdst";b:1;s:4:"abbr";s:13:"Jerusalem DST";}i:52;a:5:{s:2:"ts";i:1932422400;s:4:"date";s:24:"2031-03-28T00:00:00+0000";s:6:"offset";i:10800;s:5:"isdst";b:1;s:4:"abbr";s:13:"Jerusalem DST";}i:53;a:5:{s:2:"ts";i:1963872000;s:4:"date";s:24:"2032-03-26T00:00:00+0000";s:6:"offset";i:10800;s:5:"isdst";b:1;s:4:"abbr";s:13:"Jerusalem DST";}i:54;a:5:{s:2:"ts";i:1995926400;s:4:"date";s:24:"2033-04-01T00:00:00+0000";s:6:"offset";i:10800;s:5:"isdst";b:1;s:4:"abbr";s:13:"Jerusalem DST";}i:55;a:5:{s:2:"ts";i:2027376000;s:4:"date";s:24:"2034-03-31T00:00:00+0000";s:6:"offset";i:10800;s:5:"isdst";b:1;s:4:"abbr";s:13:"Jerusalem DST";}i:56;a:5:{s:2:"ts";i:2058825600;s:4:"date";s:24:"2035-03-30T00:00:00+0000";s:6:"offset";i:10800;s:5:"isdst";b:1;s:4:"abbr";s:13:"Jerusalem DST";}i:57;a:5:{s:2:"ts";i:2090275200;s:4:"date";s:24:"2036-03-28T00:00:00+0000";s:6:"offset";i:10800;s:5:"isdst";b:1;s:4:"abbr";s:13:"Jerusalem DST";}i:58;a:5:{s:2:"ts";i:2121724800;s:4:"date";s:24:"2037-03-27T00:00:00+0000";s:6:"offset";i:10800;s:5:"isdst";b:1;s:4:"abbr";s:13:"Jerusalem DST";}}');
                break;
            case 'thunderbirdEuropeBerlin':
                $transitionsData = unserialize('a:2:{i:0;a:5:{s:2:"ts";i:1332637200;s:4:"date";s:24:"2012-03-25T01:00:00+0000";s:6:"offset";i:7200;s:5:"isdst";b:1;s:4:"abbr";s:4:"CEST";}i:1;a:5:{s:2:"ts";i:1351386000;s:4:"date";s:24:"2012-10-28T01:00:00+0000";s:6:"offset";i:3600;s:5:"isdst";b:0;s:4:"abbr";s:3:"CET";}}');
                break;
            default:
                throw new Exception("no such test data $tzid");
                break;
        }
        
        return new TimeZoneConvert_Set($transitionsData);
    }
}