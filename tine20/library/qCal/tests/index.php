<?php
/**
 * The config file defines the location of Simpletest, qCal's library files,
 * the test classes, and the sample ics/ical files.
 */
require_once 'config.php';

define('TESTCASE_PATH', dirname(__FILE__));
define('TESTFILE_PATH', TESTCASE_PATH . '/files');
define('TESTCLASS_PATH', TESTCASE_PATH . '/testclasses');
define('DS', DIRECTORY_SEPARATOR);
define('PS', PATH_SEPARATOR);

// establish include path
set_include_path(
    SIMPLETEST_PATH . PS .
    QCAL_PATH . PS .
    TESTCASE_PATH . PS .
	TESTCLASS_PATH . PS . 
    get_include_path()
);

// require autoloader
require_once QCAL_PATH . '/autoload.php';
// require convenience functions
require_once TESTCASE_PATH . '/convenience.php';
// require necessary simpletest files
require_once SIMPLETEST_PATH . '/unit_tester.php';
require_once SIMPLETEST_PATH . '/reporter.php';
require_once SIMPLETEST_PATH . '/mock_objects.php';

// add tests cases to group and run the tests
$test = new GroupTest('Core qCal Tests');
$test->addTestCase(new UnitTestCase_Parser);
$test->addTestCase(new UnitTestCase_Component);
$test->addTestCase(new UnitTestCase_Component_Alarm);
$test->addTestCase(new UnitTestCase_Component_Calendar);
$test->addTestCase(new UnitTestCase_Component_Timezone);
$test->addTestCase(new UnitTestCase_Component_Event);
$test->addTestCase(new UnitTestCase_Property);
$test->addTestCase(new UnitTestCase_Value);
$test->addTestCase(new UnitTestCase_Value_Date);
$test->addTestCase(new UnitTestCase_Value_Recur);
$test->addTestCase(new UnitTestCase_Value_Multi);
$test->addTestCase(new UnitTestCase_Renderer);
$test->addTestCase(new UnitTestCase_DateTime);
$test->addTestCase(new UnitTestCase_Date);
$test->addTestCase(new UnitTestCase_Duration);
$test->addTestCase(new UnitTestCase_Period);
$test->addTestCase(new UnitTestCase_Timezone);
$test->addTestCase(new UnitTestCase_Time);
$test->addTestCase(new UnitTestCase_Recur);
// $test->addTestCase(new UnitTestCase_Database);

/**
 * Sprint One: 12/15/2009 - 12/29/2009
 */
$test->addTestCase(new UnitTestCase_SprintOne);
/**
 * Sprint Two: 12/30/2009 - 1/14/2009
 */
$test->addTestCase(new UnitTestCase_SprintTwo);

$test->run(new HtmlReporter());