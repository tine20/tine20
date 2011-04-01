<?php
Mock::generate('qCal_Component', 'Mock_qCal_Component');
Mock::generate('qCal_Property', 'Mock_qCal_Property');
/**
 * @todo Instead of returning an array of properties from getProperty(), it would be
 * nicer to return like a qCal_Property_Collection() object. It would look something like this:
 * 
 * $event = new qCal_Component_Vevent(array(
 *     'dtstart' => 'foo',
 *     'dtend' => 'bar',
 *     'location' => 'foobar',
 *     // etc...
 * ));
 * $event->addProperty(new qCal_Property_Attendee('john.stamos@gmail.com', array(
 *     'CUTYPE' => 'INDIVIDUAL',
 *     'ROLE' => 'REQ-PARTICIPANT',
 *     'PARTSTAT' => 'ACCEPTED',
 *     'CN' => 'John Stamos'
 * )));
 * $event->addProperty(new qCal_Property_Attendee('rebecca.stamos@gmail.com', array(
 *     'CUTYPE' => 'INDIVIDUAL',
 *     'ROLE' => 'REQ-PARTICIPANT',
 *     'PARTSTAT' => 'ACCEPTED',
 *     'CN' => 'Rebecca Stamos'
 * )));
 * $attendees = $event->getProperty('attendee');
 * $john = $attendees->current();
 * $rebecca = $attendees->next();
 * $dtstart = $event->getProperty('dtstart');
 * $dtstart = $dtstart->current()->getValue();
 * // there's also this
 * $dtstart = $dtstart->getDtstart(); // facade methods
 */
class UnitTestCase_Component extends UnitTestCase {

	/**
	 * A nice simple test to start things off...
	 */
	public function testClassTypes() {
	
		$property = new Mock_qCal_Property;
		$component = new Mock_qCal_Component;
		$this->assertTrue($property instanceof qCal_Property);
		$this->assertTrue($component instanceof qCal_Component);
	
	}
	/**
	 * Test that validation happens at render time
	 */
	public function testValidationHappensAtRenderTime() {
	
		$valarm = new qCal_Component_Valarm(array(
		));
		$this->expectException(new qCal_Exception_MissingProperty('VALARM component requires ACTION property'));
		$valarm->render();
	
	}
	/**
	 * Test facade methods
	 */
	public function testFacadeMethods() {
	
		$calendar = new qCal_Component_Vcalendar();
		$calendar->setProdId('// Test //');
		$this->assertEqual($calendar->getProdid(), '// Test //');
		// try something that has multiple instances
		$event = new qCal_Component_Vevent();
		$event->addAttendee('luke.visinoni@gmail.com');
		$event->addAttendee('john.stamos@gmail.com');
		$attendees = $event->getAttendee();
		$this->assertEqual(count($attendees), 2);
	
	}
	/**
	 * These are examples from other icalendar libraries I've found in various other languages
	 */
	public function testExamplesFromOtherLibraries() {
	
		$cal = new qCal;
		$cal->addProperty('prodid', '-//My calendar product//mxm.dk//');
		$cal->addProperty('version', '2.0');
		// $prodid = $cal->getProperty('prodid');
		// $this->assertEqual($prodid[0]->getValue(), '-//My calendar product//mxm.dk//');
		// $version = $cal->getProperty('version');
		// $this->assertEqual($version[0]->getValue(), '2.0');
		
	}
	
	/**
	 * Test that non-standard properties can be set on a component.
	 */
	public function testNonStandardProperties() {
	
		$cal = new qCal(array('X-IS-FOO' => array('foo','bar')));
		$xisfoos = $cal->getProperty('x-is-foo');
		$this->assertEqual($xisfoos[0]->getValue(), 'foo');
		$this->assertEqual($xisfoos[1]->getValue(), 'bar');
	
	}
	/**
	 * Test that if there is a class for a non-standard property available, it will be used
	 * instaead of the qCal_Property_NonStandard class
	 * @todo This should probably be in the property unit test case
	 * @todo I'm not sure I like how this turned out. What if somebody wants their non-standard
	 * class to be named "Lv_Property_XLvFoo"? Should this be so constricting?
	 */
	public function testClassIsUsedInsteadOfNonStandardClassIfAvailable() {
	
		$calendar = new qCal_Component_Vtodo();
		$calendar->addProperty('X-LV-FOO', 'bar');
		$xlvfoo = $calendar->getProperty('X-LV-FOO');
		$this->assertIsA($xlvfoo[0], 'qCal_Property_XLvFoo');
	
	}
	/**
	 * ATTACHING COMPONENTS
	 */
	/**
	 * only certain components can be attached to eachother
	 */
	public function testInvalidAttaching() {
	
		$this->expectException(new qCal_Exception_InvalidComponent('VCALENDAR cannot be attached to VEVENT'));
		// calendars cannot be attached to anything (except perhaps other calendars)
		$cal = new qCal;
		$event = new qCal_Component_Vevent();
		$event->attach($cal);
	
	}
	/**
	 * Component constructor should accept an array of properties
	 */
	public function testConstructorAcceptsInitializingArray() {
	
		$cal = new qCal(array(
			'version' => '2.0',
			'prodid' => '-//foo/bar//NONFOO v1.0//EN'
		));
		$properties = array_keys($cal->getProperties());
		$this->assertEqual($properties, array('VERSION','PRODID'));
	
	}
	/**
	 * Component constructor should accept an array of properties and
	 * if it needs several instances of the same property it should be able to accept
	 * an array inside of the array.
	 */
	public function testConstructorAcceptsInitializingArrayOfArrays() {
	
		$journal = new qCal_Component_Vjournal(array(
			'attach' => array(
				'http://www.example.com/foo/bar.mp3',
				'http://www.example.com/foo/baz.mp3'
			)
		));
		$this->assertEqual(count($journal->getProperty('attach')), 2);
	
	}
	/**
	 * The factory method is used in the parser. It may eventually be used in the facade methods as well
	 * The factory should accept the name of the component as the first param and the properties as the second
	 * It should also be completely case-insensitive
	 */
	public function testFactoryMethod() {
	
		$component = qCal_Component::factory('VALARM', array('action' => 'audio', 'TriggER' => 'P1w3Dt2H3M45S'));
		$this->assertIsA($component, 'qCal_Component_Valarm');
	
	}
	/**
	 * Test that children can access their parents
	 * @todo I'm not sure that this is even necessary. See comments for qCal_Component::attach() for reasoning
	 * behind this.
	 */
	public function testAccessToParent() {
	
		$vtodo = new qCal_Component_Vtodo(array(
			'summary' => 'Foo',
			'description' => 'Foobar'
		));
		$valarm = new qCal_Component_Valarm(array(
			'trigger' => '-P15M',
			'action' => 'display',
			'summary' => 'Foo',
			'description' => 'Foobar'
		));
		$this->assertNull($valarm->getParent());
		$vtodo->attach($valarm);
		$this->assertIdentical($valarm->getParent(), $vtodo);
	
	}
	/**
	 * Test that all components have access to their root component
	 */
	public function testComponentsHaveAccessToRootComponent() {
	
		$cal = new qCal_Component_Vcalendar();
		$todo = new qCal_Component_Vtodo();
		$alarm = new qCal_Component_Valarm();
		$todo->attach($alarm);
		$cal->attach($todo);
		$this->assertIdentical($alarm->getRootComponent(), $cal);
	
	}

}