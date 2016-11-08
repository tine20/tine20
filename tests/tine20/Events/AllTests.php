<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Events
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2007-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

class Events_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 Events All Tests');
        
        $suite->addTestSuite('Events_JsonTest');
        $suite->addTestSuite('Events_ControllerTest');
        return $suite;
    }
}
