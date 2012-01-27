<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Jonas Fischer <j.fischer@metaways.de>
 */

class Syncope_Backend_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Syncope All Backend Tests');
        
        $suite->addTestSuite('Syncope_Backend_ContentTests');
        $suite->addTestSuite('Syncope_Backend_DeviceTests');
        $suite->addTestSuite('Syncope_Backend_FolderTests');
        $suite->addTestSuite('Syncope_Backend_SyncStateTests');
        
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'ActiveSync_AllTests::main') {
    ActiveSync_AllTests::main();
}
