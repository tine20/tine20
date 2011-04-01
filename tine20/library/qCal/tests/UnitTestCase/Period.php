<?php
class UnitTestCase_Period extends UnitTestCase {

	/**
	 * Set up test environment
	 */
	public function setUp() {
	
		
	
	}
	/**
	 * Tear down test environment
	 */
	public function tearDown() {
	
		
	
	}
	
	public function testPeriodConstructor() {
	
		$period = new qCal_DateTime_Period("2009-04-23 6:00pm", "2009-04-30 9:00pm");
		$this->assertEqual($period->getStart(), new qCal_DateTime(2009, 4, 23, 18, 0, 0));
		$this->assertEqual($period->getEnd(), new qCal_DateTime(2009, 4, 30, 21, 0, 0));
	
	}
	
	public function testPeriodWithDurationAsEndDate() {
	
		$period = new qCal_DateTime_Period("2009-04-23 6:00pm", new qCal_DateTime_Duration(array("weeks" => "3")));
		$this->assertEqual($period->getEnd(), new qCal_DateTime(2009, 5, 14, 18, 0, 0));
	
	}
	
	public function testToDuration() {
	
		$period = new qCal_DateTime_Period("2009-04-23 12:00", "2009-05-14 12:00");
		$this->assertEqual($period->toDuration(), new qCal_DateTime_Duration(array("weeks" => 3)));
	
	}

}