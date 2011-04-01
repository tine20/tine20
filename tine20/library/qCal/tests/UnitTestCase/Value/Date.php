<?php
/**
 * I think eventually I'll add tests like this for all value types
 */
class UnitTestCase_Value_Date extends UnitTestCase {

	/**
	 * If the property permits, multiple "date" values are
	 * specified as a COMMA character (US-ASCII decimal 44) separated list
	 * of values. One such property is "EXDATE", which specifies dates to exclude
	 * from a recurring rule.
	 * @todo This should probably be moved into the property unit test case
	 */
	public function testMultipleDateValuesSeparatedByCommaChar() {
	
		$property = new qCal_Property_Exdate('2008-04-23');
		$property->addValue('2008-04-24')
		         ->addValue('2008-04-25');
		$this->assertEqual('20080423T000000,20080424T000000,20080425T000000', $property->__toString());
		
		$property2 = new qCal_Property_Exdate('2008-12-30', array('value' => 'date'));
		$property2->addValue('2008-12-31');
		$this->assertEqual($property2->__toString(), "20081230,20081231");
		
		//$value = new qCal_Value_Date('')
	
	}
	/**
     * The format for the value type is expressed as the [ISO
	 * 8601] complete representation, basic format for a calendar date. The
	 * textual format specifies a four-digit year, two-digit month, and
	 * two-digit day of the month. There are no separator characters between
	 * the year, month and day component text.
	 */
	public function testFormatsToISO8601() {
	
		$date = new qCal_Value_Date('Jan 15 2009');
		$this->assertEqual($date->__toString(), '20090115');
	
	}

}