<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Tests
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to test <...>
 *
 * @package     Syncroton
 * @subpackage  Tests
 */
class Syncroton_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Syncroton All Tests');
        
        $suite->addTestSuite('Syncroton_Backend_AllTests');
        $suite->addTestSuite('Syncroton_Command_AllTests');
        $suite->addTestSuite('Syncroton_Data_AllTests');
        $suite->addTestSuite('Syncroton_Model_AllTests');
        $suite->addTestSuite('Syncroton_Wbxml_AllTests');
        #$suite->addTestSuite('Syncroton_ServerTests'); #Disabled because it breaks CodeCoverage on build server
        
        return $suite;
    }
}
