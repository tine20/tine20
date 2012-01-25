<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'ActiveSync_Backend_AllTests::main');
}

class ActiveSync_Backend_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 ActiveSync Controller All Tests');
        
        $suite->addTestSuite('ActiveSync_Backend_DeviceTests');
        #$suite->addTestSuite('ActiveSync_Backend_FolderTests');
        #$suite->addTestSuite('ActiveSync_Backend_PolicyTests');
        #$suite->addTestSuite('ActiveSync_Backend_SyncStateTests');
        
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'ActiveSync_Backend_AllTests::main') {
    ActiveSync_Backend_AllTests::main();
}
