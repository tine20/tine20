<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Jonas Fischer <j.fischer@metaways.de>
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
        
        return $suite;
    }
}
