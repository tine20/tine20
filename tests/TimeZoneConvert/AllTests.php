<?php
/**
 * TimeZoneConvert
 *
 * @package     TimeZoneConvert
 * @subpackage  Tests
 * @license     MIT, BSD, and GPL
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 */

class TimeZoneConvert_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('TimeZoneConvert All Tests');
        $suite->addTestSuite('TimeZoneConvert_TransitionRuleTests');
        $suite->addTestSuite('TimeZoneConvert_TransitionTests');
        $suite->addTestSuite('TimeZoneConvert_VTimeZoneTests');
        $suite->addTestSuite('TimeZoneConvert_TimeZoneConvertTests');
        
        return $suite;
    }
}