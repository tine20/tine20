<?php
class UnitTestCase_Component_Event extends UnitTestCase {

	/**
	 *              ; either 'dtend' or 'duration' may appear in
	 *              ; a 'eventprop', but 'dtend' and 'duration'
	 *              ; MUST NOT occur in the same 'eventprop'
	 */
	public function testDtendOrDurationMayAppearButNotBoth() {
	
		$this->expectException(new qCal_Exception_InvalidProperty('DTEND and DURATION cannot both occur in the same VEVENT component'));
		$event = new qCal_Component_Vevent(array(
			'dtend' => '20090101T040000Z',
			'duration' => 'P1D'
		));
		$event->validate();
	
	}
	/**
	 * Description: A "VEVENT" calendar component is a grouping of component
	 * properties, and possibly including "VALARM" calendar components, that
	 * represents a scheduled amount of time on a calendar. For example, it
	 * can be an activity; such as a one-hour long, department meeting from
	 * 8:00 AM to 9:00 AM, tomorrow. Generally, an event will take up time
	 * on an individual calendar. Hence, the event will appear as an opaque
	 * interval in a search for busy time. Alternately, the event can have
	 * its Time Transparency set to "TRANSPARENT" in order to prevent
	 * blocking of the event in searches for busy time.
	 * 
	 * Example: The following is an example of the "VEVENT" calendar
	 * component used to represent a meeting that will also be opaque to
	 * searches for busy time:
	 * 
	 *   BEGIN:VEVENT
	 *   UID:19970901T130000Z-123401@host.com
	 *   DTSTAMP:19970901T1300Z
	 *   DTSTART:19970903T163000Z
	 *   DTEND:19970903T190000Z
	 *   SUMMARY:Annual Employee Review
	 *   CLASS:PRIVATE
	 *   CATEGORIES:BUSINESS,HUMAN RESOURCES
	 *   END:VEVENT
 	 * 
	 * The following is an example of the "VEVENT" calendar component used
	 * to represent a reminder that will not be opaque, but rather
	 * transparent, to searches for busy time:
	 * 
	 *   BEGIN:VEVENT
	 *   UID:19970901T130000Z-123402@host.com
	 *   DTSTAMP:19970901T1300Z
	 *   DTSTART:19970401T163000Z
	 *   DTEND:19970402T010000Z
	 *   SUMMARY:Laurel is in sensitivity awareness class.
	 *   CLASS:PUBLIC
	 *   CATEGORIES:BUSINESS,HUMAN RESOURCES
	 *   TRANSP:TRANSPARENT
	 *   END:VEVENT
	 * 
	 * The following is an example of the "VEVENT" calendar component used
	 * to represent an anniversary that will occur annually. Since it takes
	 * up no time, it will not appear as opaque in a search for busy time;
	 * no matter what the value of the "TRANSP" property indicates:
	 * 
	 *   BEGIN:VEVENT
	 *   UID:19970901T130000Z-123403@host.com
	 *   DTSTAMP:19970901T1300Z
	 *   DTSTART:19971102
	 *   SUMMARY:Our Blissful Anniversary
	 *   CLASS:CONFIDENTIAL
	 *   CATEGORIES:ANNIVERSARY,PERSONAL,SPECIAL OCCASION
	 *   RRULE:FREQ=YEARLY
	 *   END:VEVENT
	 * 
	 */
	public function zzztestMeetingThatWillBeOpaqueToSearchesForBusyTime() {
	
		$event = new qCal_Component_Vevent(array(
			'uid' => '19970901T130000Z-123401@host.com',
			'dtstamp' => '19970901T1300Z',
			'dtstart' => '19970903T163000Z',
			'dtend' => '19970903T190000Z',
			'summary' => 'Annual Employee Review',
			'class' => 'PRIVATE',
			'categories' => array('BUSINESS','HUMAN RESOURCES', 'SOMETHING, COOL'),
		));
		$cal = new qCal();
		$cal->attach($event);
		$freebusytime = $cal->getFreeBusyTime();
	
	}
	/**
	 * The "VEVENT" is also the calendar component used to specify an
	 * anniversary or daily reminder within a calendar. These events have a
	 * DATE value type for the "DTSTART" property instead of the default
	 * data type of DATE-TIME. If such a "VEVENT" has a "DTEND" property, it
	 * MUST be specified as a DATE value also. The anniversary type of
	 * "VEVENT" can span more than one date (i.e, "DTEND" property value is
	 * set to a calendar date after the "DTSTART" property value).
	 */
	public function testVeventDtstartDateWithDtendDate() {
	
		$this->expectException(new qCal_Exception_InvalidProperty('If DTSTART property is specified as a DATE property, so must DTEND'));
		$dtstart = new qCal_Property_Dtstart('09/09/2009', array('value' => 'date'));
		$dtend = new qCal_Property_Dtend('09/19/2009');
		$event = new qCal_Component_Vevent(array(
			'uid' => '20090909T130000Z-123401@host.com',
			'dtstart' => $dtstart,
			'dtend' => $dtend
		));
		$event->validate();
	
	}
	public function testVeventDtstartMustComeBeforeDtend() {
	
		$this->expectException(new qCal_Exception_InvalidProperty('DTSTART property must come before DTEND'));
		$event = new qCal_Component_Vevent(array(
			'dtstart' => new qCal_Property_Dtstart('09/09/2009'),
			'dtend' => new qCal_Property_Dtend('09/08/2009')
		));
		$event->validate();
	
	}
	/**
	 * The "DTSTART" property for a "VEVENT" specifies the inclusive start
	 * of the event. For recurring events, it also specifies the very first
	 * instance in the recurrence set. The "DTEND" property for a "VEVENT"
	 * calendar component specifies the non-inclusive end of the event. For
	 * cases where a "VEVENT" calendar component specifies a "DTSTART"
	 * property with a DATE data type but no "DTEND" property, the events
	 * non-inclusive end is the end of the calendar date specified by the
	 * "DTSTART" property. For cases where a "VEVENT" calendar component
	 * specifies a "DTSTART" property with a DATE-TIME data type but no
	 * "DTEND" property, the event ends on the same calendar date and time
	 * of day specified by the "DTSTART" property.
	 */
	public function zzztestRecurringEvent() {
	
		// test it!
	
	}
	/**
	 * The "VEVENT" calendar component cannot be nested within another
	 * calendar component. However, "VEVENT" calendar components can be
	 * related to each other or to a "VTODO" or to a "VJOURNAL" calendar
	 * component with the "RELATED-TO" property.
	 */
	public function zzztestVeventCannotBeNestedButCanBeRelatedToVeventOrVtodoOrVjournal() {
	
		// test it!
	
	}

}
?>