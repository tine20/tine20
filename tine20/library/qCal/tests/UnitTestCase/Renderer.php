<?php
class UnitTestCase_Renderer extends UnitTestCase {

    public function setUp() {
    
        
    
    }
    
    public function tearDown() {
    
        if (file_exists("./files/me2.jpg")) unlink("./files/me2.jpg");
    
    }
    
    public function NOSHOWtestRenderer() {
    
    	$calendar = new qCal;
    	$calendar->attach(new qCal_Component_Todo());
    	$alarm = new qCal_Component_Alarm(array('action' => 'audio', 'trigger' => '15m'));
    	$todo_w_alarm = new qCal_Component_Todo();
    	$todo_w_alarm->setDescription("Remember to eat a pile of bacon soda", array('altrep' => 'This is an alternate representation'));
    	$todo_w_alarm->setDue("2008-12-25 8:00");
    	$todo_w_alarm->attach($alarm);
    	$calendar->attach($todo_w_alarm);
        $ical = $calendar->render(); // can pass it a renderer, otherwise it uses ical format
        // pre($ical);
    
    }
    /**
     * @todo Remove the binary attach stuff from here and put it in the test below.
     */
    public function testLongLinesFolded() {
    
    	$cal = new qCal;
    	$todo = new qCal_Component_Vtodo(array(
	    	'description' => 'This is a really long line that will of course need to be folded. I mean, we can\'t just have long lines laying around in an icalendar file. That would be like not ok. So, let\'s find out if this folded properly!',
			'summary' => 'This is a short summary, which I think is like a title',
			'dtstart' => '2008-04-23 1:00am',
    	));
    	$cal->attach($todo);
		$lines = explode("\r\n", $cal->render());
		$long = false;
		foreach ($lines as $line) {
			if (strlen($line) > 76) $long = true;
		}
		$this->assertFalse($long);
    
    }
    /**
     * Test that binary data can be encoded as text and then decoded to be put back together.
     */
    public function testBinaryData() {
    
	    $cal = new qCal;
    	$journal = new qCal_Component_Vjournal(array(
	    	'description' => 'This is a really long line that will of course need to be folded. I mean, we can\'t just have long lines laying around in an icalendar file. That would be like not ok. So, let\'s find out if this folded properly!',
			'summary' => 'This is a short summary, which I think is like a title',
			'dtstamp' => '2008-04-23 1:00am',
			new qCal_Property_Attach(file_get_contents('./files/me.jpg'), array(
				'encoding' => 'base64',
				'fmtype' => 'image/basic',
				'value' => 'binary',
			)),
    	));
    	$cal->attach($journal);
		$attach = $journal->getProperty('attach');
		$jpg = base64_decode($attach[0]->getValue());
		file_put_contents('./files/me2.jpg', $jpg);
		$this->assertEqual(file_get_contents('./files/me2.jpg'), file_get_contents('./files/me.jpg'));
    
    }
	/**
	 * Test that all of the right characters are escaped when rendered
	 * @todo Need to make sure that when parsing the escape characters are removed.
	 */
	public function testCharactersAreEscaped() {
	
		$journal = new qCal_Component_Vjournal(array(
			'summary' => 'The most interesting, but non-interesting journal entry ever.',
			'description' => 'This is a sentence that ends with a semi-colon, which I\'m not sure needs to be escaped; I will read the RFC a bit and find out what, exactly, needs to escaped. I know commas do though, and this entry has plenty of those.',
			'dtstart' => qCal_DateTime::factory('20090809T113500')
		));
		$this->assertEqual($journal->render(), "BEGIN:VJOURNAL\r\nSUMMARY:The most interesting\, but non-interesting journal entry ever.\r\nDESCRIPTION:This is a sentence that ends with a semi-colon\, which I'm not \r\n sure needs to be escaped; I will read the RFC a bit and find out what\, exa\r\n ctly\, needs to escaped. I know commas do though\, and this entry has plent\r\n y of those.\r\nDTSTART:20090809T113500\r\nEND:VJOURNAL\r\n");
	
	}

}