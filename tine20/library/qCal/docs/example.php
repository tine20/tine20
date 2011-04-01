<?php
set_include_path(realpath("../lib") . PATH_SEPARATOR . get_include_path());
require_once "../tests/convenience.php";
/**
 * Parse an iCalendar file
 */
$filepath = realpath('../tests/files');
$parser = new qCal_Parser(array(
	'searchpath' => $filepath,
));
// parse a file
$ical = $parser->parseFile('simple.ics');
// parse raw data
// $rawdata = file_get_contents($filepath . '/simple.ics');
// $ical->parse($rawdata);

/**
 * Render an iCal object as an icalendar file
 */
$iCalData = $ical->render();

// eventually we can use other renderers as well...
// $xCal = $ical->render(new qCal_Renderer_xCal()); // xCal is an implementation of icalendar in xml
// $hCal = $ical->render(new qCal_Renderer_hCal()); // hCal is a microformat (html version of icalendar format)

/**
 * Build an iCal object from scratch
 */
$calendar = new qCal(array(
	'prodid' => '-//Some Calendar Company//Calendar Program v0.1//EN'
));
$todo = new qCal_Component_Vtodo(array(
	'class' => 'private',
	'dtstart' => '20090909',
	'description' => 'Eat some bacon!!',
	'summary' => 'Eat bacon',
	'priority' => 1,
));
$todo->attach(new qCal_Component_Valarm(array(
	'action' => 'audio',
	'trigger' => '20090423',
	'attach' => 'http://www.example.com/foo.wav',
)));
$calendar->attach($todo);
// now you can render the calendar if you want, or just echo it out