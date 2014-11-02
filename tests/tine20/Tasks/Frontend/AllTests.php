<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2014-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * All tasks frontend tests
 * 
 * @package     Tasks
 */
class Tasks_Frontend_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('All tasks frontend tests');
        
        $suite->addTest(Tasks_Frontend_WebDAV_AllTests::suite());
        
        $suite->addTestSuite('Tasks_Frontend_ActiveSyncTest');
        
        return $suite;
    }
}
