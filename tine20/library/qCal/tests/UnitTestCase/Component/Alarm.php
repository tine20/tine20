<?php
class UnitTestCase_Component_Alarm extends UnitTestCase {

	public function setUp() {
	
		
	
	}

	public function tearDown() {
	
		
	
	}
	/**
	 * Test that each component gets initialized in accordance with the RFC
	 * conformance rules
	 */
	public function testAlarmInitializeConformance() {
	
		// test that action is required to initialize an alarm
		$this->expectException(new qCal_Exception_MissingProperty('VALARM component requires ACTION property'));
		$component = new qCal_Component_Valarm();
		$component->validate();
		// test that trigger is required to initialize an alarm
		$this->expectException(new qCal_Exception_MissingProperty('VALARM component requires TRIGGER property'));
		$component = new qCal_Component_Valarm('AUDIO');
		$component->validate();
	
	}
	/**
	 * Test the various types of alarms that are possible
	 */
	public function testAudioAlarm() {
	
		// audio alarm
		$this->expectException(new qCal_Exception_MissingProperty('VALARM component requires TRIGGER property'));
		$alarm = new qCal_Component_Valarm(array(
			'action' => 'audio',
			//'trigger' => '15m'
		));
		$alarm->validate();
		
	}
	/**
	 * Test the various types of alarms that are possible
	 */
	public function testDisplayAlarm() {
	
		// display alarm
		$this->expectException(new qCal_Exception_MissingProperty('DISPLAY VALARM component requires DESCRIPTION property'));
		$alarm = new qCal_Component_Valarm(array(
			'action' => 'display',
			'trigger' => 'P1W3DT2H3M45S',
			//'description' => 'Feed your fish'
		));
		$alarm->validate();
		
	}
	/**
	 * Test the various types of alarms that are possible
	 */
	public function testEmailAlarm() {
	
		// email alarm
		$this->expectException(new qCal_Exception_MissingProperty('EMAIL VALARM component requires DESCRIPTION property'));
		$alarm = new qCal_Component_Valarm(array(
			'action' => 'email',
			'trigger' => 'P1W3DT2H3M45S',
			'summary' => 'Feed your fish!',
			//'description' => 'Don\'t forget to feed your poor fishy, Pedro V'
		));
		$alarm->validate();
		
	}
	/**
	 * Test the various types of alarms that are possible
	 */
	public function testProcedureAlarm() {

		// email alarm
		$this->expectException(new qCal_Exception_MissingProperty('PROCEDURE VALARM component requires ATTACH property'));
		$alarm = new qCal_Component_Valarm(array(
			'action' => 'procedure',
			'trigger' => 'P1W3DT2H3M45S',
			//'attach' => 'http://www.somewebsite.com/387592/alarm/5/',
		));
		$alarm->validate();
	
	}
	/**
	 *             ; 'action' and 'trigger' are both REQUIRED,
	 *             ; but MUST NOT occur more than once
	 */
	public function testActionAndTriggerRequiredButCannotOccurMoreThanOnce() {
	
		$alarm = new qCal_Component_Valarm(array(
			'action' => 'audio',
			'trigger' => 'p15m'
		));
		// @todo Should this throw an exception since display requires description?
		$alarm->addProperty('action', 'display');
		$action = $alarm->getProperty('action');
		$this->assertEqual(count($action), 1);
		$alarm->addProperty('trigger', 'p30d');
		$trigger = $alarm->getProperty('trigger');
		$this->assertEqual(count($trigger), 1);
	
	}
	/**
	 *             ; 'duration' and 'repeat' are both optional,
	 *             ; and MUST NOT occur more than once each,
	 *             ; but if one occurs, so MUST the other
	 */
	public function testIfDurationOccursSoMustRepeat() {
	
		$this->expectException(new qCal_Exception_MissingProperty('VALARM component with a DURATION property requires a REPEAT property'));
		$alarm = new qCal_Component_Valarm(array(
			'action' => 'audio',
			'duration' => 'p30m',
			'trigger' => 'p20d'
		));
		$alarm->validate();
		$this->expectException(new qCal_Exception_MissingProperty('VALARM component with a REPEAT property requires a DURATION property'));
		$alarm2 = new qCal_Component_Valarm(array(
			'action' => 'audio',
			'repeat' => 'p30m',
			'trigger' => 'p20d'
		));
		$alarm2->validate();
	
	}
	/**
	 *             ; the following are optional,
	 *             ; and MAY occur more than once
	 * 
	 *             attach / x-prop
	 * 
	 *   The RFC specifies these as examples:
	 *   ATTACH:CID:jsmith.part3.960817T083000.xyzMail@host1.com
	 * 
	 *   ATTACH;FMTTYPE=application/postscript:ftp://xyzCorp.com/pub/
	 *    reports/r-960812.ps
	 * @todo I'm not sure how the first one is suppose to work... :(
	 */
	public function testAttachAndNonStandardCanOccurMultipleTimes() {
	
		$alarm = new qCal_Component_Valarm(array(
			'action' => 'audio',
			'trigger' => 'P1M'
		));
		$attach1 = new qCal_Property_Attach('ftp://xyzCorp.com/pub/reports/r-960812.ps', array(
			'fmttype' => 'application/postscript'
		));
		$alarm->addProperty($attach1);
		$attach2 = new qCal_Property_Attach('ftp://xyzCorp.com/pub/reports/r-960813.ps', array(
			'fmttype' => 'application/postscript'
		));
		$alarm->addProperty($attach2);
		$attaches = $alarm->getProperty('attach');
		$this->assertEqual(count($attaches), 2);
		
		// now try non-standard properties
		$alarm2 = new qCal_Component_Valarm(array(
			'action' => 'audio',
			'trigger' => 'P1M'
		));
		$ns1 = new qCal_Property_NonStandard('foobar', array(
			'x-foo' => 'baz'
		), 'x-lv-email');
		$alarm2->addProperty($ns1);
		$ns2 = new qCal_Property_NonStandard('luke.visinoni@gmail.com', array(
			'altrep' => 'lvisinoni@foobar.com'
		), 'x-lv-email');
		$alarm2->addProperty($ns2);
		$ns = $alarm2->getProperty('x-lv-email');
		$this->assertEqual(count($ns), 2);
	
	}
	/**
	 *             ; 'description' is optional,
	 *             ; and MUST NOT occur more than once
	 */
	public function testDescriptionIsOptionalAndCannotOccurMoreThanOnce() {
	
		$alarm = new qCal_Component_Valarm(array(
			'action' => 'procedure',
			'trigger' => 'P15M',
			'attach' => 'http://www.example.com/foo'
		));
		$alarm->addProperty(new qCal_Property_Description('This is a description'));
		$alarm->addProperty(new qCal_Property_Description('This is another description'));
		$this->assertEqual(count($alarm->getProperty('description')), 1);
	
	}
	/**
	 * When the action is "AUDIO", the alarm can also include one and only
	 * one "ATTACH" property, which MUST point to a sound resource, which is
	 * rendered when the alarm is triggered.
	 * @todo I'm still not really sure when validation should occur. I called it manually here.
	 */
	public function testAudioAlarmCanIncludeOneAndOnlyOneAttachProperty() {
	
		$this->expectException(new qCal_Exception_InvalidProperty('VALARM audio component can contain one and only one ATTACH property'));
		$alarm = new qCal_Component_Valarm(array(
			'action' => 'audio',
			'trigger' => 'P45Y',
			'attach' => 'http://www.example.com/foobar.mp3'
		));
		$alarm->addProperty('attach', 'http://www.example.com/boofar.mp3');
		$alarm->validate();
	
	}
	/**
	 * When the action is "PROCEDURE", the alarm MUST include one and only
	 * one "ATTACH" property, which MUST point to a procedure resource,
	 * which is invoked when the alarm is triggered.
	 */
	public function testProcedureAlarmCanIncludeOneAndOnlyOneAttachProperty() {
	
		$this->expectException(new qCal_Exception_InvalidProperty('VALARM procedure component can contain one and only one ATTACH property'));
		$alarm = new qCal_Component_Valarm(array(
			'action' => 'procedure',
			'trigger' => 'P45Y',
			'attach' => 'http://www.example.com/foobar.mp3'
		));
		$alarm->addProperty('attach', 'http://www.example.com/boofar.mp3');
		$alarm->validate();
	
	}
	/**
	 * The "VALARM" calendar component MUST only appear within either a
	 * "VEVENT" or "VTODO" calendar component.
	 */
	public function testValarmCanOnlyAppearInVeventOrVtodo() {
	
		$alarm = new qCal_Component_Valarm(array(
			'action' => 'audio',
			'trigger' => 'P15M'
		));
		$alarm2 = new qCal_Component_Valarm(array(
			'action' => 'audio',
			'trigger' => 'P25M'
		));
		$journal = new qCal_Component_Vjournal(array(
			'summary' => 'Some silly entry',
			'description' => 'Some silly description'
		));
		$this->expectException(new qCal_Exception_InvalidComponent('VALARM cannot be attached to VJOURNAL'));
		$journal->attach($alarm);
	
	}
	/**
	 * "VALARM" calendar components
	 * cannot be nested. Multiple mutually independent "VALARM" calendar
	 * components can be specified for a single "VEVENT" or "VTODO" calendar
	 * component.
	 */
	public function testValarmCannotBeNestedAndCanOccurMultipleTimes() {
	
		$alarm = new qCal_Component_Valarm(array(
			'action' => 'audio',
			'trigger' => 'P15M'
		));
		$alarm2 = new qCal_Component_Valarm(array(
			'action' => 'audio',
			'trigger' => 'P25M'
		));
		$todo = new qCal_Component_Vtodo();
		$this->expectException(new qCal_Exception_InvalidComponent('VALARM cannot be attached to VALARM'));
		$alarm2->attach($alarm);
		$todo->attach($alarm);
		$todo->attach($alarm2);
	
	}
	/**
	 * In an alarm set to trigger on the "START" of an event or to-do, the
	 * "DTSTART" property MUST be present in the associated event or to-do.
	 * In an alarm in a "VEVENT" calendar component set to trigger on the
	 * "END" of the event, either the "DTEND" property MUST be present, or
	 * the "DTSTART" and "DURATION" properties MUST both be present. In an
	 * alarm in a "VTODO" calendar component set to trigger on the "END" of
	 * the to-do, either the "DUE" property MUST be present, or the
	 * "DTSTART" and "DURATION" properties MUST both be present.
	 * 
	 * @todo I don't know how this should work. Does the associated event or
	 * todo have to be related with the relatedto property? I need to work out
	 * how related components should work before I try to write a test for this.
	 * After reading the "related-to" property, I realize that I'll need to have
	 * some kind of framework for related components because when components are
	 * related, certain properties should change their related components when
	 * they are changed. For instance, if an alarm is related to an event, and the
	 * event start date is updated, the alarm's trigger should be changed as well.
	 * 
	 * I just realized after typing all of the above that alarms are nested within
	 * their parent components, so I can test it that way. The above is still true
	 * for related components though.
	 * 
	 * @todo After typing what I just typed above, I realize again that I don't know
	 * how to test this. How do I know whether or not an alarm is set to trigger on
	 * the start of an event if the event doesn't have a dtstart?
	 */
	public function testAlarmTriggerWithParentComponent() {
	/*
		$todo = new qCal_Component_Vtodo(array(
			
		));
		$alarm = new qCal_Component_Valarm(array(
			
		));
		$this->expectException(new qCal_Exception_MissingProperty(''));
	*/
	}
	/**
	 * The alarm can be defined such that it triggers repeatedly. A
	 * definition of an alarm with a repeating trigger MUST include both the
	 * "DURATION" and "REPEAT" properties. The "DURATION" property specifies
	 * the delay period, after which the alarm will repeat. The "REPEAT"
	 * property specifies the number of additional repetitions that the
	 * alarm will triggered. This repitition count is in addition to the
	 * initial triggering of the alarm. Both of these properties MUST be
	 * present in order to specify a repeating alarm. If one of these two
	 * properties is absent, then the alarm will not repeat beyond the
	 * initial trigger.
	 * 
	 * @todo I'm not sure how to test this.
	 */
	
	/**
	 * In an EMAIL alarm, the intended alarm effect is for an email message
	 * to be composed and delivered to all the addresses specified by the
	 * "ATTENDEE" properties in the "VALARM" calendar component. The
	 * "DESCRIPTION" property of the "VALARM" calendar component MUST be
	 * used as the body text of the message, and the "SUMMARY" property MUST
	 * be used as the subject text. Any "ATTACH" properties in the "VALARM"
	 * calendar component SHOULD be sent as attachments to the message.
	 */
	public function testEmailAlarmShouldBeCapableOfFindingAllAttendeesAndAttachments() {
	
		
	
	}

}