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

class TimeZoneConvert_VTimeZoneTests extends PHPUnit_Framework_TestCase
{
    
    public static $rfc5545AmericaNewYork = <<<EOT
BEGIN:VTIMEZONE
TZID:America/New_York
LAST-MODIFIED:20050809T050000Z
BEGIN:DAYLIGHT
DTSTART:19670430T020000
RRULE:FREQ=YEARLY;BYMONTH=4;BYDAY=-1SU;UNTIL=19730429T070000Z
TZOFFSETFROM:-0500
TZOFFSETTO:-0400
TZNAME:EDT
END:DAYLIGHT
BEGIN:STANDARD
DTSTART:19671029T020000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU;UNTIL=20061029T060000Z
TZOFFSETFROM:-0400
TZOFFSETTO:-0500
TZNAME:EST
END:STANDARD
BEGIN:DAYLIGHT
DTSTART:19740106T020000
RDATE:19750223T020000
TZOFFSETFROM:-0500
TZOFFSETTO:-0400
TZNAME:EDT
END:DAYLIGHT
BEGIN:DAYLIGHT
DTSTART:19760425T020000
RRULE:FREQ=YEARLY;BYMONTH=4;BYDAY=-1SU;UNTIL=19860427T070000Z
TZOFFSETFROM:-0500
TZOFFSETTO:-0400
TZNAME:EDT
END:DAYLIGHT
BEGIN:DAYLIGHT
DTSTART:19870405T020000
RRULE:FREQ=YEARLY;BYMONTH=4;BYDAY=1SU;UNTIL=20060402T070000Z
TZOFFSETFROM:-0500
TZOFFSETTO:-0400
TZNAME:EDT
END:DAYLIGHT
BEGIN:DAYLIGHT
DTSTART:20070311T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
TZOFFSETFROM:-0500
TZOFFSETTO:-0400
TZNAME:EDT
END:DAYLIGHT
BEGIN:STANDARD
DTSTART:20071104T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
TZOFFSETFROM:-0400
TZOFFSETTO:-0500
TZNAME:EST
END:STANDARD
END:VTIMEZONE
EOT;
    
    /**
     * https://bugzilla.mozilla.org/show_bug.cgi?id=504299
     * STANDARD BUG FIXED 20100927T020000 -> 20100927T020000
     */
    public static $customAsiaJerusalem = <<<EOT
BEGIN:VTIMEZONE
TZID:Asia/Jerusalem
X-LIC-LOCATION:Asia/Jerusalem
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0200
TZNAME:IST
DTSTART:20081005T020000
RDATE;VALUE=DATE-TIME:20081005T020000,20090927T020000,20100912T020000,20111002T020000,
20120923T020000,20130908T020000,20140928T020000,20150920T020000,20161009T020000,
20170924T020000,20180916T020000,20191006T020000,20200927T020000,20210912T020000,
20221002T020000,20230924T020000,20241006T020000,20250928T020000,20260920T020000,
20271010T020000,20280924T020000,20290916T020000,20301006T020000,20310921T020000,
20320912T020000,20331002T020000,20340917T020000,20351007T020000,20360928T020000,
20370913T020000
END:STANDARD
BEGIN:DAYLIGHT
DTSTART:20090327T020000
RDATE;VALUE=DATE-TIME:20090327T020000,20100326T020000,20110401T020000,20120330T020000,
20130329T020000,20140328T020000,20150327T020000,20160401T020000,20170331T020000,
20180330T020000,20190329T020000,20200327T020000,20210326T020000,20220401T020000,
20230331T020000,20240329T020000,20250328T020000,20260327T020000,20270326T020000,
20280331T020000,20290330T020000,20300329T020000,20310328T020000,20320326T020000,
20330401T020000,20340331T020000,20350330T020000,20360328T020000,20370327T020000
TZOFFSETFROM:+0200
TZOFFSETTO:+0300
TZNAME:Jerusalem DST
END:DAYLIGHT
END:VTIMEZONE
EOT;
    
    /**
     * thunderbird starts most of its rrule 1970 (start of unix timestamp)
     */
    public static $thunderbirdEuropeBerlin = <<<EOT
BEGIN:VTIMEZONE
TZID:Europe/Berlin
X-LIC-LOCATION:Europe/Berlin
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=3
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=10
END:STANDARD
END:VTIMEZONE
EOT;
    
    public static $msexchange2007EuropeBerlin = <<<EOT
PRODID:Microsoft Exchange Server 2007
BEGIN:VTIMEZONE
TZID:W. Europe Standard Time
BEGIN:STANDARD
DTSTART:16010101T030000
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=-1SU;BYMONTH=10
END:STANDARD
BEGIN:DAYLIGHT
DTSTART:16010101T020000
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=-1SU;BYMONTH=3
END:DAYLIGHT
END:VTIMEZONE
EOT;
    
    public static $msexchangeCDO = <<<EOT
PRODID:Microsoft CDO for Microsoft Exchange
BEGIN:VTIMEZONE
TZID:(GMT+01.00) Sarajevo/Warsaw/Zagreb
X-MICROSOFT-CDO-TZID:2
BEGIN:STANDARD
DTSTART:16010101T030000
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
RRULE:FREQ=YEARLY;WKST=MO;INTERVAL=1;BYMONTH=10;BYDAY=-1SU
END:STANDARD
BEGIN:DAYLIGHT
DTSTART:16010101T020000
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
RRULE:FREQ=YEARLY;WKST=MO;INTERVAL=1;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
END:VTIMEZONE
EOT;
    
    public static $appleical5 = <<<EOT
BEGIN:VTIMEZONE
TZID:Europe/Berlin
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
DTSTART:19810329T020000
TZNAME:CEST
TZOFFSETTO:+0200
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
DTSTART:19961027T030000
TZNAME:CET
TZOFFSETTO:+0100
END:STANDARD
END:VTIMEZONE
EOT;
    
    public static $novellgroupwise8 = <<<EOT
PRODID:-//Novell Inc//Groupwise 8.0.2
BEGIN:VTIMEZONE
TZID:(GMT+0100) Westeuropäische Normalzeit
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
DTSTART:20001028T020000
RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=-1SU;BYMONTH=10
TZNAME:Westeuropäische Normalzeit
END:STANDARD
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
DTSTART:20000325T010000
RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=-1SU;BYMONTH=3
TZNAME:Westeuropäische Sommerzeit
END:DAYLIGHT
END:VTIMEZONE
EOT;
    
    /**
     * @var TimeZoneConvert_VTimeZone
     */
    public $uit;
    
    public function setUp()
    {
        $this->uit = new TimeZoneConvert_VTimeZone();
    }
    
    public function testGetTransitionRules()
    {
         $transitionRules = $this->uit->getTransitionRules(self::$rfc5545AmericaNewYork);
         
         $this->assertEquals(7, count($transitionRules), '7 transitions rules should be found');
         
         $this->assertEquals(TRUE, $transitionRules[0]->isdst, 'first rule is dst');
         $this->assertEquals(-4*60*60, $transitionRules[0]->offset, 'offset is 4 hours');
         $this->assertEquals('EDT', $transitionRules[0]->abbr, 'abbr failed');
         $this->assertEquals(4, $transitionRules[0]->month, 'month failed');
         $this->assertEquals(0, $transitionRules[0]->wkday, 'wkday failed');
         $this->assertEquals(-1, $transitionRules[0]->numwk, 'numwk failed');
         $this->assertEquals(new DateTime('19670430T070000', new DateTimeZone('UTC')), $transitionRules[0]->from, 'from failed NOTE the offset!');
         $this->assertEquals(new DateTime('19730429T070000', new DateTimeZone('UTC')), $transitionRules[0]->until, 'first rule until fails');
         
         $this->assertEquals(FALSE, $transitionRules[1]->isdst, 'second rule is non dst');
         $this->assertEquals('EST', $transitionRules[1]->abbr, 'abbr failed');
         $this->assertEquals(TRUE, $transitionRules[1]->isRecurringRule(), 'second rule is per rrule');
         $this->assertEquals(new DateTime('20061029T060000', new DateTimeZone('UTC')), $transitionRules[1]->until, 'second rule until fails');
         
         $transitionDates = $transitionRules[2]->getTransitionDates();
         $this->assertEquals(1, count($transitionDates), 'thrird rule should have one rdate transition');
         $this->assertEquals(FALSE, $transitionRules[2]->isRecurringRule(), 'thrird rule has no rrule');
         $this->assertEquals(new DateTime('19750223T070000', new DateTimeZone('UTC')), $transitionDates[0], 'thrird rule transitions date fails');
        
        
        $transitionRules = $this->uit->getTransitionRules(self::$customAsiaJerusalem);
        $this->assertEquals(2, count($transitionRules), '2 transitions rules should be found in $customAsiaJerusalem');
        $transitionDates = $transitionRules[0]->getTransitionDates();
        $this->assertEquals(30, count($transitionDates));
        
        $transitions = $this->uit->getTransitions($transitionRules);
    }
    
    public function testGetTransitionsRfc5545AmericaNewYork()
    {
        $transitionRules = $this->uit->getTransitionRules(self::$rfc5545AmericaNewYork);
        $transitions = $this->uit->getTransitions($transitionRules);
        
        $this->assertTrue(count($transitions) > 7, 'min. 7 transitions should be computed');
        
        $this->assertEquals(-84387600, $transitions[0]['ts']);
        $this->assertEquals('1967-04-30T07:00:00+0000', $transitions[0]['date']);
        $this->assertEquals(-14400, $transitions[0]['offset']);
        $this->assertEquals(TRUE, $transitions[0]['isdst']);
        $this->assertEquals('EDT', $transitions[0]['abbr']);
    }
    
    public function testGetTransitionsThunderbirdEuropeBerlin()
    {
        $transitionRules = $this->uit->getTransitionRules(self::$thunderbirdEuropeBerlin);
        $transitions = $this->uit->getTransitions($transitionRules);
        
        $this->assertEquals(2, count($transitions), '2 transitions should be found');
    }
    
    public function testGetTransitionsAppleical5()
    {
        $transitionRules = $this->uit->getTransitionRules(self::$appleical5);
        $transitions = $this->uit->getTransitions($transitionRules);
        
        $timezone = TimeZoneConvert_Transition::getMatchingTimezone($transitions);
        
        $this->assertTrue($timezone instanceof DateTimeZone, 'timezone not found');
        $this->assertEquals('Arctic/Longyearbyen', $timezone->getName());
    }
    
    public function testGetTransitionsMsexchange2007EuropeBerlin()
    {
        $transitionRules = $this->uit->getTransitionRules(self::$msexchange2007EuropeBerlin);
        $transitions = $this->uit->getTransitions($transitionRules);
        
        $timezone = TimeZoneConvert_Transition::getMatchingTimezone($transitions);
        
        $this->assertTrue($timezone instanceof DateTimeZone, 'timezone not found');
        $this->assertEquals('Africa/Ceuta', $timezone->getName());
    }
    
    public function testComputeTimezoneRfc5545AmericaNewYork()
    {
        $timezone = $this->uit->computeTimezone(self::$rfc5545AmericaNewYork);
        
        $this->assertEquals('America/New_York', $timezone->getName());
    }
    
    public function testGetTZIdentifierAppleical5()
    {
        $tzid = $this->uit->getTZIdentifier(self::$appleical5);
        
        $this->assertEquals('Europe/Berlin', $tzid);
    }
    
    public function testGetTZIdentifierMsexchange2007EuropeBerlin()
    {
        $tzid = $this->uit->getTZIdentifier(self::$msexchange2007EuropeBerlin);
        
        $this->assertEquals('Europe/Berlin', $tzid);
    }
    
    public function testGetTZIdentifierMsexchangeCDOSarajevo()
    {
        $tzid = $this->uit->getTZIdentifier(self::$msexchangeCDO);
        
        $this->assertEquals('Europe/Lisbon', $tzid);
    }
    
    public function testGetTZIdentifierThunderbirdEuropeBerlin()
    {
        $tzid = $this->uit->getTZIdentifier(str_replace('TZID:Europe/Berlin', 'TZID:SOMEUNKNOWNID', self::$thunderbirdEuropeBerlin));
        
        $this->assertEquals('Europe/Berlin', $tzid);
    }
    
    public function testGetTZIdentifierNovellgroupwise8EuropeBerlin()
    {
        $tzid = $this->uit->getTZIdentifier(preg_replace('/PRODID:.*/', '', self::$novellgroupwise8), '-//Novell Inc//Groupwise 8.0.2');
        
        $this->assertEquals('Europe/Berlin', $tzid);
    }
    
    public function testGetVTimezoneUTC()
    {
        $VTimeZone = $this->uit->getVTimezone('UTC');

        // make shure it don't fails
        // UTC should not be represented as VTIMEZONE normally
        $UTCTZ = <<<EOT
BEGIN:VTIMEZONE
TZID:UTC
END:VTIMEZONE
EOT;
        foreach(explode(TimeZoneConvert_VTimeZone::EOL, $VTimeZone) as $line) {
            if (! $line) continue;
             $this->assertTrue(strstr($UTCTZ, $line) !== FALSE, "$line failed");
        }
    }

    public function testGetVTimezoneEuropeBerlin()
    {
        $VTimeZone = $this->uit->getVTimezone('Europe/Berlin');
        foreach(explode(TimeZoneConvert_VTimeZone::EOL, $VTimeZone) as $line) {
            if (! $line) continue;
             $this->assertTrue(strstr(self::$appleical5, $line) !== FALSE, "$line failed");
        }
    }
    
    public function testGetVTimezoneAsiaJerusalem()
    {
        $from = new DateTime('2008-06-01T00:00:00', new DateTimeZone('UTC'));
        $until = new DateTime('2037-12-31T00:00:00', new DateTimeZone('UTC'));
        
        $VTimeZone = $this->uit->getVTimezone('Asia/Jerusalem', $from, $until);

        foreach(explode(TimeZoneConvert_VTimeZone::EOL, $VTimeZone) as $line) {
            if (! $line || strstr($line, 'TZNAME') !== FALSE) continue; // different TZNAME
             $this->assertTrue(strstr(self::$customAsiaJerusalem, $line) !== FALSE, "$line failed");
        }
        
    }
}
