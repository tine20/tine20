<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Jonas Fischer <j.fischer@metaways.de>
 */

/**
 * All ActiveSync tests
 * 
 * @package     ActiveSync
 */
class ActiveSync_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('All ActiveSync tests');
        
        $suite->addTest(ActiveSync_Command_AllTests::suite());
        $suite->addTest(ActiveSync_Controller_AllTests::suite());
        $suite->addTest(ActiveSync_Backend_AllTests::suite());
        
        $suite->addTestSuite(ActiveSync_TimezoneConverterTest::class);
        $suite->addTestSuite(ActiveSync_Frontend_JsonTests::class);
        $suite->addTestSuite(ActiveSync_Server_PluginTests::class);
        
        return $suite;
    }
}
