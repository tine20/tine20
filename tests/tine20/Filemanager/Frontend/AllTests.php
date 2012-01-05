<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Filemanager
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Filemanager_Frontend_AllTests::main');
}

class Filemanager_Frontend_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 Filemanager Frontend Tests');
        $suite->addTestSuite('Filemanager_Frontend_JsonTests');
        $suite->addTestSuite('Filemanager_Frontend_WebDAVTest');
        
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Filemanager_Frontend_AllTests::main') {
    Filemanager_Frontend_AllTests::main();
}
