<?php
/**
 * Test qCal_Parser subpackage
 */
class UnitTestCase_Parser extends UnitTestCase {

	protected $testpath;
	/**
	 * Create some test files to play with
	 */
    public function setUp() {
    
        $this->testpath = TESTFILE_PATH . DIRECTORY_SEPARATOR . 'testpath';
		if (!file_exists($this->testpath)) mkdir($this->testpath, 0777);
		else {
			@chmod($this->testpath, 0777);
		}
		$file = $this->testpath . DIRECTORY_SEPARATOR . 'foo.ics';
		$content = file_get_contents(TESTFILE_PATH . DIRECTORY_SEPARATOR . 'simple.ics');
		file_put_contents($file, $content);
    
    }
    /**
     * Delete test files
	 */
    public function tearDown() {
    
		$dir = dir($this->testpath);
		if (is_resource($dir->handle)) {
			while (false !== ($entry = $dir->read())) {
				if ($entry != "." && $entry != "..") {
					@chmod($this->testpath . DIRECTORY_SEPARATOR . $entry, 0777);
					@unlink($this->testpath . DIRECTORY_SEPARATOR . $entry);
				}
			}
	        @rmdir($this->testpath);	
		}
    
    }
	public function testParserAcceptsRawData() {
	
		$data = file_get_contents(TESTFILE_PATH . DIRECTORY_SEPARATOR . 'simple.ics');
		$parser = new qCal_Parser(array(
			// options!
		));
		$ical = $parser->parse($data);
		$this->assertIsA($ical, 'qCal_Component_Vcalendar');
	
	}
	/**
	 * Parses a file 
	 */
	public function testParserAcceptsFilename() {
	
		$parser = new qCal_Parser(array(
			// options!
		));
		$ical = $parser->parseFile(TESTFILE_PATH . DIRECTORY_SEPARATOR . 'simple.ics');
		$this->assertIsA($ical, 'qCal_Component_Vcalendar');
	
	}
	/**
	 * The parser accepts an array of options in its constructor. One option is "searchpath",
	 * which, if provided, will be used to search for the file that is provided in parseFile()
	 * If no searchpath is provided, it will use the include path. Paths should be separated by
	 * PATH_SEPARATOR.
	 * @todo I think I want to change this to "search_path" instead of "searchpath"
	 */
	public function testParserAcceptsSearchPath() {
	
		$paths = array($this->testpath);
		$parser = new qCal_Parser(array(
			'searchpath' => implode(PATH_SEPARATOR, $paths)
		));
		$ical = $parser->parseFile('foo.ics');
		$this->assertIsA($ical, 'qCal_Component_Vcalendar');
	
	}
	public function testFileNotFound() {
	
		$filename = "foobar.ics";
		$this->expectException(new qCal_Exception_FileNotFound('File cannot be found: "' . $filename . '"'));
		$parser = new qCal_Parser();
		$parser->parseFile($filename);
	
	}
	
	/**
	 * Tell the parser to ignore validation errors (things like valarm missing its action property)
	 * @todo I will implement this if there is a need for it.
	public function testIgnoreValidationErrors() {
	
		$invalidcal = <<<CAL
BEGIN:VCALENDAR
PRODID://foo//bar
CALSCALE:GREGORIAN
BEGIN:VTODO
SUMMARY:Some todo item I don't care about
DESCRIPTION:The description is silly
BEGIN:VALARM
END:VALARM
END:VTODO
END:VCALENDAR

CAL;
		// with validation off, this should not throw any exceptions
		$parser = new qCal_Parser(array(
			'validation' => 'off'
		));
		$ical = $parser->parse($invalidcal);
	
	}
	 */
	/**
	 * Test that it is possible to not use property defaults. For instance, CALSCALE defaults to "GREGORIAN", but if 
	 * defaults are turned off, it shouldn't default to anything.
	 * @todo I will implement this if there is a need for it

	public function testNoDefaults() {
		
	}
	 */
	public function testInitParser() {
	
		$parser = new qCal_Parser(array(
			
		));
		// $ical = $parser->parse(TESTFILE_PATH . '/lvisinoni.ics');
		// pre($ical->render());
	
	}
	
	/**
	public function testParserNoValidationOption() {
	
		$parser = new qCal_Parser(array(
			'validation' => 'off'
		));
		$calendarcontent = <<<CAL
BEGIN:VCALENDAR
PRODID:-//Nothing//Nobody//EN
VERSION:2.0
BEGIN:VEVENT
DTSTART:20090909T043000Z
DTEND:20090909T063000Z
CLASS:PRIVATE
ATTENDEE;CUTYPE=INDIVIDUAL;ROLE=REQ-PARTICIPANT;PARTSTAT=ACCEPTED;CN=Luke V
 isinoni;X-NUM-GUESTS=0:mailto:luke.visinoni@gmail.com
SUMMARY:Meeting to discuss nothing
DESCRIPTION:This is a meeting where we will discuss absolutely nothing. Fun
  for everybody involved!
LOCATION:My House
SEQUENCE:0
STATUS:CONFIRMED
TRANSP:OPAQUE
BEGIN:VALARM
TRIGGER;VALUE=DURATION:-P1D
ACTION:DISPLAY
DESCRIPTION:Event reminder
END:VALARM
END:VEVENT
END:VCALENDAR
CAL;
		$ical = $parser->parse($calendarcontent);
	
	}
	**/
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	public function hideOldCode() {
	
		// I hid the old test code for now, I'll add it back later
		$oldcode = <<<OLDCODE

    public function testParseRawData() {
    
    	$fn = './files/simple.ics';
    	$fh = fopen($fn, 'r');
    	$data = fread($fh, filesize($fn));
    	$parser = new qCal_Parser_iCal();
    	$ical = $parser->parse($data);
    	$this->assertIsA($ical, 'qCal_Component');
    
    }
    
    public function NOSHOWtestParser() {
    
        $parser = new qCal_Parser_iCal('./files/simple.ics');
        $calendar = $parser->parse(); // now we have an iterable collection of event, todo, etc objects in $calendar
    
    }
    
    public function NOSHOWtestGenerateCalendar() {
    
    	$calendar = qCal::factory(); // generate a calendar object
    	$calendar->attach(new qCal_Component_Event); // add an event
    	$calendar->attach(new qCal_Component_Journal); // add a journal
    	$todo = new qCal_Component_Todo(); // create a todo
    	// this is a facade
    	// what it does is call:
    	/**
	     * $summary = new qCal_Property_Summary('I eat peas');
	     * $todo->addProperty($summary);
	     * // we may also need a
	     */
    	$todo->summary('I eat peas'); // summarize todo
    	$calendar->attach($todo); // add a todo to calendar
    	$calendar->prodId('//this is cool//'); // give the calendar a product id
    	$calendar->version('2.1'); // tell calendar what version it is
    	$event = new qCal_Component_Event; // create a new event
    	$event->start('3-11-2009 9:00'); // starts on march 3 09
    	$event->end('3-11-2009 12:00'); // ends at 12 same day
    	/**
    	 * qCal_Date_Rule represents a series of dates. It is used to define event recurrence,
    	 * dates to filter by (with qCal_Filter), and probably other things in the future, and may
    	 * be useful elsewhere than this library. 
    	 * @todo compe up with a better name for it
    	 */
    	$rule = new qCal_Date_Rule(); // create a date recurrence rule
    	$rule->until(2010); // will recur until 2010
    	//$rule->count(55); // will occur 55 times
    	$rule->exclude('11-3-2009','2011'); // exclude nov 3 2009 and all of 2011
    	$rule->include('11-11-2009');
    	$rule->frequency('weekly');
    	$rule->interval('2'); // every other week
    	$rule->byDay('TU','TH'); // on tuesdays and thursdays
    	// $rule->by
    	$event->recurs($rule); // apply this rule to the recur
    
    }
    
    public function NOSHOWtestDateRecurrence() {
	
		$pattern = new DatePattern();
		$pattern->until(1995);
		// count() and until() cannot both be used
		// $pattern->count(50); // repeat pattern 50 times
		$pattern->frequency('yearly');
		$pattern->byMonth(4);
		$pattern->byMonthWeek(3);
		$pattern->byDay('tuesday');
		
		// accepts either a date (11/5/2001), a date range (1992-1993) or another DatePattern object
		// may not be possible to allow include() to include a pattern... not sure... find out.
		$pattern->except($except);
		$pattern->include($include);
	
    }
    
    public function NOSHOWtestFilterCalendar() {
    
    	$calendar = qCal::import('calendar.ics'); // imports calendar information from calendar file
    	$filter = new qCal_Filter();
    	$dates = new qCal_Date_Rule();
    	/**
    	 * I'm not sure if this will all work - I need to play around with it and see if this interface
    	 * is possible - I think it will work as long as include/exclude are evaluated separate from the recurrence type stuff below
    	 */
    	$dates->includeRange(2007, 2009); // grab dates from 2007-2009
    	$dates->include('2008', '11-23-2006'); // include all of 08 and nov 11 of 2006
    	$dates->excludeRange('9-2006','10-2006'); // exclude september to october 2006
    	$dates->exclude('4-23-2006', 'april 2007'); // exclude april 23 2006 and all of april 2007
    	/**
    	 * Recurrence rules can be used as well (hopefully)
    	 */
    	$dates->frequency('monthly'); // monthly
    	$dates->interval(2); // every other month
    	$dates->byMonthDay(2,3,5,7); // on the 2nd, 3rd, 4th, and 7th
    	$filter->add($dates);
    	/**
    	 * Can also filter by type (this is a facade that in the background would instantiate qCal_Filter_ByType
    	 * and pass the second arg to the constructor)
    	 */
    	$filter->add('ByType', array('VEVENT','VTODO','VJOURNAL'));
    	$components = $filter->filter($calendar); // returns matches
    
    }

	/**
	 * Property names, parameter names and enumerated parameter values are
	 * case insensitive. For example, the property name "DUE" is the same as
	 * "due" and "Due", DTSTART;TZID=US-Eastern:19980714T120000 is the same
	 * as DtStart;TzID=US-Eastern:19980714T120000.
	 */
	public function testPropertyNamesAndParamValuesAreCaseInsensitive() {
	
		
	
	}
OLDCODE;
	}

}