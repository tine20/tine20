<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2015 Metaways Infosystems GmbH (http://www.metaways.de)
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
        
        $suite->addTestSuite('ActiveSync_TimezoneConverterTest');
        $suite->addTestSuite('ActiveSync_Frontend_JsonTests');
        $suite->addTestSuite('ActiveSync_Server_PluginTests');
        
        return $suite;
    }
}
