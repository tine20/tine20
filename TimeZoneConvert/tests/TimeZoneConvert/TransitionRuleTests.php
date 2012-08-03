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

class TimeZoneConvert_TransitionRuleTests extends PHPUnit_Framework_TestCase
{
    
    public function testComputeTransitionDateEuropeBerlinStandard()
    {
        // Europe/Berlin Standard valid since 1996
        $uit = new TimeZoneConvert_TransitionRule(array(
            'month'   => 10,
            'wkday'   => 0,
            'numwk'   => -1,
            'hour'    => 3,
            'minute'  => 0,
            'second'  => 0
        ));
        
        $transition = $uit->computeTransitionDate('2011');
        $this->assertEquals('2011-10-30 03:00:00', $transition->format('Y-m-d H:i:s'));
    }
    
    public function testComputeTransitionDateEuropeBerlinDaylight()
    {
        // Europe/Berlin Daylight valid since 1981
        $uit = new TimeZoneConvert_TransitionRule(array(
            'month'   => 3,
            'wkday'   => 0,
            'numwk'   => -1,
            'hour'    => 2,
            'minute'  => 0,
            'second'  => 0
        ));
        
        $transition = $uit->computeTransitionDate('2012');
        $this->assertEquals('2012-03-25 02:00:00', $transition->format('Y-m-d H:i:s'));
    }
    
    public function testComputeTransitionDateAmericaLosAngelesStandard()
    {
        // America/Los_Angeles Standard valid since ?
        $uit = new TimeZoneConvert_TransitionRule(array(
            'month'   => 11,
            'wkday'   => 0,
            'numwk'   => 1,
            'hour'    => 2,
            'minute'  => 0,
            'second'  => 0
        ));
        
        $transition = $uit->computeTransitionDate('2011');
        $this->assertEquals('2011-11-06 02:00:00', $transition->format('Y-m-d H:i:s'));
    }
    
    public function testComputeTransitionDateAmericaLosAngelesDaylight()
    {
        // America/Los_Angeles Daylight valid since 1981
        $uit = new TimeZoneConvert_TransitionRule(array(
            'month'   => 3,
            'wkday'   => 0,
            'numwk'   => 2,
            'hour'    => 2,
            'minute'  => 0,
            'second'  => 0
        ));
        
        $transition = $uit->computeTransitionDate('2012');
        $this->assertEquals('2012-03-11 02:00:00', $transition->format('Y-m-d H:i:s'));
    }
    
    public function testCreateFromTransition()
    {
        $timezone = new DateTimeZone('Asia/Jerusalem');
        $transitions = $timezone->getTransitions(date_create('2011-01-01', $timezone)->getTimestamp(), date_create('2012-12-31', $timezone)->getTimestamp());
        
        $transition = array(
            'ts'      => 1332637200,
            'time'    => '2012-03-25T01:00:00+0000',
            'offset'  => 7200,
            'isdst'   => TRUE,
            'abbr'    => 'CEST',
        );
        
        $uit = TimeZoneConvert_TransitionRule::createFromTransition($transition);
        
        $this->assertEquals(-1, $uit->numwk, 'numwk fails');
    }
}