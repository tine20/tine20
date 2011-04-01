<?php
class UnitTestCase_Component_Timezone extends UnitTestCase {

	/**
	 * Test that each component gets initialized in accordance with the RFC
	 * conformance rules
	 */
	public function testTimeZoneInitializeConformance() {
	
		// test that action is required to initialize an alarm
		$this->expectException(new qCal_Exception_MissingProperty('VTIMEZONE component requires TZID property'));
		$component = new qCal_Component_Vtimezone();
		$component->validate();
	
	}
	/**
	 *                ; 'tzid' is required, but MUST NOT occur more
	 *                ; than once
	 */
	public function testTzidIsRequiredButMustNotOccurMoreThanOnce() {
	
		$standard = new qCal_Component_Standard(array(
			'tzoffsetto' => '-0500',
			'tzoffsetfrom' => '-0400',
			'dtstart' => '19971026T020000'
		));
		$tz = new qCal_Component_Vtimezone(array(
			'tzid' => 'California-Los_Angeles',
		), array($standard));
		$tz->addProperty(new qCal_Property_Tzid('New_York-New_York'));
		$tzid = $tz->getProperty('tzid');
		$this->assertEqual(count($tzid), 1);
		$this->assertEqual($tzid[0]->getValue(), 'New_York-New_York');
	
	}
	/**
	 *                ; 'last-mod' and 'tzurl' are optional,
	 *              but MUST NOT occur more than once
	 */
	public function testLastModAndTzurlMustNotOccurMoreThanOnce() {
	
		$standard = new qCal_Component_Standard(array(
			'tzoffsetto' => '-0500',
			'tzoffsetfrom' => '-0400',
			'dtstart' => '19971026T020000'
		));
		$tz = new qCal_Component_Vtimezone(array(
			'tzid' => 'California-Los_Angeles',
			'last-modified' => qCal_DateTime::factory("now", "America/Los_Angeles"),
			'tzurl' => 'http://www.example.com/tz1',
		), array($standard));
		$newtime = time();
		$tz->addProperty('last-modified', $newtime);
		$tz->addProperty(new qCal_Property_Tzurl('http://www.example.com/tz2'));
		$tzlm = $tz->getProperty('last-modified');
		$tzurl = $tz->getProperty('tzurl');
		$this->assertEqual(count($tzlm), 1);
		$this->assertEqual(count($tzurl), 1);
		//@todo this probably isn't right... gmdate shouldn't work here... but I don't know for sure...
		$this->assertEqual($tzlm[0]->getValue(), gmdate('Ymd\THis', $newtime));
		$this->assertEqual($tzurl[0]->getValue(), 'http://www.example.com/tz2');
	
	}
	/**
	 * The "VTIMEZONE" calendar component MUST include the "TZID" property
	 * and at least one definition of a standard or daylight component. The
	 * standard or daylight component MUST include the "DTSTART",
	 * "TZOFFSETFROM" and "TZOFFSETTO" properties.
	 */
	public function testOneOfStandardOrDaylightMustOccurAndMayOccurMoreThanOnce() {
	
		$this->expectException(new qCal_Exception_MissingComponent('Either a STANDARD or DAYLIGHT component is required within a VTIMEZONE component'));
		$tz = new qCal_Component_Vtimezone(array(
			'tzid' => 'US-Eastern',
		), array(
			// $standard
		));
		$tz->validate();
		$standard = new qCal_Component_Standard(array(
			'tzoffsetto' => '-0500',
			'tzoffsetfrom' => '-0400',
			'dtstart' => '19971026T020000'
		));
		$standard->validate();
		$standard2 = new qCal_Component_Standard(array(
			'tzoffsetto' => '-0600',
			'tzoffsetfrom' => '-0500',
			'dtstart' => '19981026T020000'
		));
		$standard2->validate();
		$tz->attach($standard);
		$tz->attach($standard2);
		$tz->validate(); // shouldn't throw an exception now that standard was attached
		$chidren = $tz->getChildren();
		$this->assertEqual(count($children), 2);
	
	}
	/**
	 * The vcalendar component should be capable of retrieving all of the available time zones
	 */
	public function testGetTimeZonesFromCalendar() {
	
		$cal = new qCal_Component_Vcalendar;
		$useastern = new qCal_Component_Vtimezone(array(
			'tzid' => 'US-Eastern',
		));
		// fake us eastern timezone
		$useastern->attach(new qCal_Component_Standard(array(
			'dtstart' => qCal_DateTime::factory('20090913T000000Z'),
			'offsetto' => new qCal_Property_Tzoffsetto('0200'),
			'offsetfrom' => new qCal_Property_Tzoffsetfrom('0400'),
		)));
		$uswestern = new qCal_Component_Vtimezone(array(
			'tzid' => 'US-Western',
		));
		// fake us western timezone
		$uswestern->attach(new qCal_Component_Standard(array(
			'dtstart' => qCal_DateTime::factory('20090913T000000Z'),
			'offsetto' => new qCal_Property_Tzoffsetto('0100'),
			'offsetfrom' => new qCal_Property_Tzoffsetfrom('0300'),
		)));
		$cal->attach($useastern);
		$cal->attach($uswestern);
		$timezones = $cal->getTimeZones();
		$this->assertEqual(count($timezones), 2);
		$this->assertIdentical($timezones['US-EASTERN'], $useastern);
		$this->assertIdentical($timezones['US-WESTERN'], $uswestern);
	
	}
	/**
	 * Timezones should be accessible individually by getTimezone()
	 */
	public function testGetTimezone() {
	
		$cal = new qCal_Component_Vcalendar;
		$useastern = new qCal_Component_Vtimezone(array(
			'tzid' => 'US-Eastern',
		));
		// fake us eastern timezone
		$useastern->attach(new qCal_Component_Standard(array(
			'dtstart' => qCal_DateTime::factory('20090913T000000Z'),
			'offsetto' => new qCal_Property_Tzoffsetto('0200'),
			'offsetfrom' => new qCal_Property_Tzoffsetfrom('0400'),
		)));
		$uswestern = new qCal_Component_Vtimezone(array(
			'tzid' => 'US-Western',
		));
		// fake us western timezone
		$uswestern->attach(new qCal_Component_Standard(array(
			'dtstart' => qCal_DateTime::factory('20090913T000000Z'),
			'offsetto' => new qCal_Property_Tzoffsetto('0100'),
			'offsetfrom' => new qCal_Property_Tzoffsetfrom('0300'),
		)));
		$cal->attach($useastern);
		$cal->attach($uswestern);
		$this->assertIdentical($cal->getTimezone('us-eastern'), $useastern);
	
	}
	/**
	 * An individual "VTIMEZONE" calendar component MUST be specified for
	 * each unique "TZID" parameter value specified in the iCalendar object.
	 * @todo Finish this when you are more sure how timezones will work
	 */
	public function testEachTzidParameterMustHaveCorrespondingVTimezone() {
	
		$cal = new qCal_Component_Vcalendar();
		$todo1 = new qCal_Component_Vtodo(array(
			'summary' => 'Make the monkey wash the cat',
			'description' => 'Make the monkey wash the cat with a cloth. Make sure to also video-tape it.',
			new qCal_Property_Dtstart('20090815T050000', array('tzid' => 'US-Eastern')),
		));
		$todo2 = new qCal_Component_Vtodo(array(
			'summary' => 'Make the cat wash the monkey',
			'description' => 'Make the cat wash the monkey with a sponge. Make sure to audio-tape it.',
			new qCal_Property_Dtstart('20090816T050000', array('tzid' => 'US-Pacific')),
		));
		$this->expectException(new qCal_Exception_MissingComponent('TZID "US-Eastern" not defined'));
		$cal->attach($todo1);
		$cal->attach($todo2);
		// $cal->validate();
		
		// $this->expectException(new qCal_Exception_MissingComponent('TZID "US-Pacific" not defined'));
	
	}
	/**
	 * Each "VTIMEZONE" calendar component consists of a collection of one
	 * or more sub-components that describe the rule for a particular
	 * observance (either a Standard Time or a Daylight Saving Time
	 * observance). The "STANDARD" sub-component consists of a collection of
	 * properties that describe Standard Time. The "DAYLIGHT" sub-component
	 * consists of a collection of properties that describe Daylight Saving
	 * Time. In general this collection of properties consists of:
	 * 
	 *      - the first onset date-time for the observance
	 * 
	 *      - the last onset date-time for the observance, if a last onset
	 *        is known.
	 * 
	 *      - the offset to be applied for the observance
	 * 
	 *      - a rule that describes the day and time when the observance
	 *        takes effect
	 * 
	 *      - an optional name for the observance
	 * 
	 * For a given time zone, there may be multiple unique definitions of
	 * the observances over a period of time. Each observance is described
	 * using either a "STANDARD" or "DAYLIGHT" sub-component. The collection
	 * of these sub-components is used to describe the time zone for a given
	 * period of time. The offset to apply at any given time is found by
	 * locating the observance that has the last onset date and time before
	 * the time in question, and using the offset value from that
	 * observance.
	 */
	public function zzztestAllTheStuffAbove() {
	
		// this cannot be tested until the recurrence property is unit tested and working...
		$tz = new qCal_Component_Vtimezone(array(
			'tzid' => 'America/Los_Angeles'
		), array(
			new qCal_Component_Standard(array(
				'dtstart' => '19701101T020000',
				'tzoffsetfrom' => '-0800',
				'tzoffsetto' => '-0700',
				'tzname' => 'PST',
				new qCal_Property_Rrule('', array(
					'freq' => 'yearly',
					'bymonth' => '3',
					'byday' => '2su'
				)),
				new qCal_Property_Rrule('', array(
					'freq' => 'monthly',
					'bymonth' => '3',
					'byday' => '1su'
				))
			)),
			new qCal_Component_Daylight(array(
				'dtstart' => '19701101T020000',
				'tzoffsetfrom' => '-0800',
				'tzoffsetto' => '-0700',
				'tzname' => 'PDT',
				new qCal_Property_Rrule('', array(
					'freq' => 'yearly',
					'bymonth' => '11',
					'byday' => '1su'
				))
			)),
		));
	
	}
	/**
	 * The optional "TZURL" property is url value that points to a published
	 * VTIMEZONE definition. TZURL SHOULD refer to a resource that is
	 * accessible by anyone who might need to interpret the object. This
	 * SHOULD NOT normally be a file: URL or other URL that is not widely-
	 * accessible.
	 * @todo WTF does "should not normally be a file" mean??
	 */
	public function zzztestTzurlPropertyIsUrl() {
	
		// @todo Finish this when you can
	
	}
	/**
	 * The collection of properties that are used to define the STANDARD and
	 * DAYLIGHT sub-components include:
	 * 
	 * The mandatory "DTSTART" property gives the effective onset date and
	 * local time for the time zone sub-component definition. "DTSTART" in
	 * this usage MUST be specified as a local DATE-TIME value.
	 * 
	 * The mandatory "TZOFFSETFROM" property gives the UTC offset which is
	 * in use when the onset of this time zone observance begins.
	 * "TZOFFSETFROM" is combined with "DTSTART" to define the effective
	 * onset for the time zone sub-component definition. For example, the
	 * following represents the time at which the observance of Standard
	 * Time took effect in Fall 1967 for New York City:
	 * 
	 *   DTSTART:19671029T020000
	 * 
	 *   TZOFFSETFROM:-0400
	 * 
	 * The mandatory "TZOFFSETTO " property gives the UTC offset for the
	 * time zone sub-component (Standard Time or Daylight Saving Time) when
	 * this observance is in use.
	 * 
	 * The optional "TZNAME" property is the customary name for the time
	 * zone. It may be specified multiple times, to allow for specifying
	 * multiple language variants of the time zone names. This could be used
	 * for displaying dates.
	 * 
	 * If specified, the onset for the observance defined by the time zone
	 * sub-component is defined by either the "RRULE" or "RDATE" property.
	 * If neither is specified, only one sub-component can be specified in
	 * the "VTIMEZONE" calendar component and it is assumed that the single
	 * observance specified is always in effect.
	 * 
	 * The "RRULE" property defines the recurrence rule for the onset of the
	 * observance defined by this time zone sub-component. Some specific
	 * requirements for the usage of RRULE for this purpose include:
	 * 
	 *      - If observance is known to have an effective end date, the
	 *      "UNTIL" recurrence rule parameter MUST be used to specify the
	 *      last valid onset of this observance (i.e., the UNTIL date-time
	 *      will be equal to the last instance generated by the recurrence
	 *      pattern). It MUST be specified in UTC time.
	 * 
	 *      - The "DTSTART" and the "TZOFFSETTO" properties MUST be used
	 *      when generating the onset date-time values (instances) from the
	 *      RRULE.
	 * 
	 * Alternatively, the "RDATE" property can be used to define the onset
	 * of the observance by giving the individual onset date and times.
	 * "RDATE" in this usage MUST be specified as a local DATE-TIME value in
	 * UTC time.
	 * 
	 * The optional "COMMENT" property is also allowed for descriptive
	 * explanatory text.
	 */
	public function zzztestStandardAndDaylightConformance() {
	
		// @todo This needs to be done, but it should probably go in its own unit test case
	
	}

}
?>