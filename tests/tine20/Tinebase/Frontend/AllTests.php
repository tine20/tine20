<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_Frontend_AllTests::main');
}

class Tinebase_Frontend_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 Tinebase All Frontend Tests');
        $suite->addTestSuite('Tinebase_Frontend_Json_ContainerTest');
        $suite->addTestSuite('Tinebase_Frontend_Json_PersistentFilterTest');
        $suite->addTestSuite('Tinebase_Frontend_JsonTest');
        $suite->addTestSuite('Tinebase_Frontend_CliTest');
        $suite->addTestSuite('Tinebase_Frontend_HttpTest');
        
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Tinebase_Frontend_AllTests::main') {
    Tinebase_Frontend_AllTests::main();
}
