<?php
/**
 * This is a series of tests that ensure that data is property handled in the qCal_Value family of classes
 */
class UnitTestCase_Value extends UnitTestCase {

	/**
	 * Test that binary data is handled right
	 */
	public function testBinaryToString() {
	
		$value = new qCal_Value_Binary("TEST DATA");
		$this->assertEqual($value->__toString(), base64_encode("TEST DATA"));
	
	}
	/**
	 * Test that binary data is handled right
	 */
	public function testActualBinary() {
	
		$binary = file_get_contents('./files/me.jpg');
		$value = new qCal_Value_Binary($binary);
		$this->assertEqual($value->__toString(), base64_encode($binary));
		$this->assertEqual($value->getValue(), $binary);
	
	}
	/**
	 * Test that boolean data is handled right
	 */
	public function testBooleanToString() {
	
		$value = new qCal_Value_Boolean(true);
		$this->assertEqual($value->__toString(), "TRUE");
	
	}
	/**
	 * Test that boolean data is handled right
	 */
	public function testRawBoolean() {
	
		$value = new qCal_Value_Boolean(false);
		$this->assertEqual($value->getValue(), false);
	
	}
	/**
	 * Test that cal-address data is handled right
	 */
	public function testCalAddressToString() {
	
		$value = new qCal_Value_CalAddress('http://www.example.com/webcal');
		$this->assertEqual($value->__toString(), "http://www.example.com/webcal");
	
	}
	/**
	 * Test that cal-address data is handled right
	 */
	public function testRawCalAddress() {
	
		$value = new qCal_Value_CalAddress('http://www.example.com/webcal');
		$this->assertEqual($value->getValue(), 'http://www.example.com/webcal');
	
	}
	/**
	 * Test that uri data is handled right
	 */
	public function testUriToString() {
	
		$value = new qCal_Value_Uri('http://www.example.com/webcal');
		$this->assertEqual($value->__toString(), "http://www.example.com/webcal");
	
	}
	/**
	 * Test that uri data is handled right
	 */
	public function testRawUri() {
	
		$value = new qCal_Value_Uri('http://www.example.com/webcal');
		$this->assertEqual($value->getValue(), 'http://www.example.com/webcal');
	
	}
	/**
	 * Test that period data is handled right
	 */
	public function testPeriodToString() {
	
		$value = new qCal_Value_Period('19970101T180000Z/19970102T070000Z');
		$this->assertEqual($value->__toString(), '19970101T180000Z/19970102T070000Z');
		// start date + duration
		// 01-01-1997 at 12 am until plus two weeks five hours thirty minutes
		// should be 01-15-1997 at 5:30 am  but is 1997-01-14 21-30-00
		$value2 = new qCal_Value_Period('19970101T010000Z/PT5H');
		$this->assertEqual($value2->__toString(), '19970101T010000Z/19970101T060000Z');
	
	}
	/**
	 * Test that period data is handled right
	 */
	public function testRawPeriod() {
	
		$value = new qCal_Value_Period('19970101T180000Z/19970102T070000Z');
		$this->assertEqual($value->getValue(), new qCal_DateTime_Period('19970101T180000Z', '19970102T070000Z'));
	
	}
	/**
	 * Test that date data is handled right
	 */
	public function testDateToString() {
	
		$value = new qCal_Value_Date('2009-04-23');
		$this->assertEqual($value->__toString(), "20090423");
	
	}
	/**
	 * Test that date data is handled right
	 */
	public function testRawDate() {
	
		$value = new qCal_Value_Date('2009-04-23');
		$this->assertEqual($value->getValue(), qCal_Date::factory('2009-04-23'));
	
	}
	/**
	 * Test that date-time data is handled right
	 */
	public function testDateTimeToString() {
	
		$value = new qCal_Value_DateTime('2009-04-23 6:00');
		$this->assertEqual($value->__toString(), "20090423T060000");
	
	}
	/**
	 * Test that date-time data is handled right
	 */
	public function testRawDateTime() {
	
		$value = new qCal_Value_DateTime('2009-04-23 6:00');
		$this->assertEqual($value->getValue(), qCal_DateTime::factory('2009-04-23 6:00'));
	
	}
	/**
	 * Test that duration data is handled right
	 */
	public function testDurationToString() {
	
		$value = new qCal_Value_Duration('P2WT2H45M');
		$this->assertEqual($value->__toString(), 'P2WT2H45M');
	
	}
	/**
	 * Test that duration passed in in an "unnormalized"? format gets corrected
	 */
	public function testDurationToStringNormalizes() {
	
		$value = new qCal_Value_Duration('P18D');
		$this->assertEqual($value->__toString(), 'P2W4D'); // 18 days == 2 weeks and 4 days
	
		$value = new qCal_Value_Duration('P180D18938S'); // 180 days + 18938 seconds 
		$this->assertEqual($value->__toString(), 'P25W5DT5H15M38S'); // converts to 25 weeks, 5 days, 5 hours, 15 minutes, and 38 seconds
		// @todo: this doesn't handle negative values yet
		
		$value = new qCal_Value_Duration(-49200); 
		$this->assertEqual($value->__toString(), '-PT13H40M'); 
		
		$value = new qCal_Value_Duration('-P180D18938S'); // 180 days + 18938 seconds 
		$this->assertEqual($value->__toString(), '-P25W5DT5H15M38S'); // converts to - 25 weeks, 5 days, 5 hours, 15 minutes, and 38 seconds
	
	}
	/**
	 * If there is a zero-increment, it is removed
	 */
	public function testDurationRemovesZeroIncrement() {
	
		// @todo Actually I'm not sure about this, I'm not sure what the expected behavior is here
		// for instance, if we have a duration of P15DT5H0M20S, should the 0M part be removed?
	
	}
	/**
	 * Test that duration data is handled right
	 */
	public function testRawDuration() {
	
		$value = new qCal_Value_Duration('P1W3DT2H3M45S');
		$this->assertEqual($value->getValue(), qCal_DateTime_Duration::factory('P1W3DT2H3M45S')); // this is how many seconds are in the duration
	
	}
	/**
	 * Test that duration passed in in an "unnormalized"? format gets corrected
	 */
	public function testDurationIsCaseInsensitive() {
	
		$duration = new qCal_Value_Duration('p2w6d30s');
		$this->assertEqual($duration->__toString(), 'P2W6DT30S');
	
	}
	/**
	 * Test that float data is handled right
	 */
	public function testFloatToString() {
	
		$value = new qCal_Value_Float(5.667);
		$this->assertIdentical($value->__toString(), '5.667');
	
	}
	/**
	 * Test that float data is handled right
	 */
	public function testRawFloat() {
	
		$value = new qCal_Value_Float(5.667);
		$this->assertIdentical($value->getValue(), 5.667); 
	
	}
	/**
	 * Test that integer data is handled right
	 */
	public function testIntegerToString() {
	
		$value = new qCal_Value_Integer(5667);
		$this->assertIdentical($value->__toString(), '5667');
	
	}
	/**
	 * Test that integer data is handled right
	 */
	public function testRawInteger() {
	
		$value = new qCal_Value_Integer(5667);
		$this->assertIdentical($value->getValue(), 5667); 
	
	}
	/**
	 * Test that integer data is handled right
	 */
	public function testTextToString() {
	
		$value = new qCal_Value_Text('text');
		$this->assertIdentical($value->__toString(), 'text');
	
	}
	/**
	 * Test that integer data is handled right
	 */
	public function testRawText() {
	
		$value = new qCal_Value_Text('text');
		$this->assertIdentical($value->getValue(), 'text'); 
	
	}
	/*
	public function testPlayingAroundWithqCal() {
	
		$cal = new qCal();
		$journal = new qCal_Component_Journal(array(
			'uid' => '19970901T130000Z-123405@host.com',
			'dtstamp' => '19970901T1300Z',
			'dtstart' => new qCal_Property_Dtstart('19970317', array('value' => 'date')),
		));
		$cal->attach($journal);
		pre($cal->render());
	
	}
	*/
	/**
	 * FROM RFC 2445
	 */
	/**
	 * value type for a property will either be specified implicitly as the
	 * default value type 
	 */
	public function testPropertySpecifiedImplicitlyAsDefault() {
	
		// I need to learn how to do this iwthout being so specific... with mocks or something...
		$property = new qCal_Property_Dtstart('2008-12-31 5:00:00');
		$this->assertEqual($property->getType(), 'DATE-TIME');
	
	}
	/**
	 * or will be explicitly specified with the "VALUE"
	 * parameter. If the value type of a property is one of the alternate
	 * valid types, then it MUST be explicitly specified with the "VALUE"
	 * parameter.
	 */
	public function testPropertySpecifiedExplicitlyAsValue() {
	
		$property = new qCal_Property_Dtstart('2008-12-31 5:00:00');
		$property->setParam('value', 'date');
		$this->assertEqual($property->getType(), 'DATE');
	
	}
	/**
	 * Many values can have multiple values separated by commas
	 */
	public function testMultipleValues() {
	
		/*$value = new qCal_Value_Duration('P15DT5H0M20S');
		$value->addValue('P15D')
		      ->addValue('PT25M30S');
		$this->assertEqual($value->__toString(), 'P15DT5H0M20S,P15D,PT25M30S');*/
	
	}

}