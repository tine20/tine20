<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     ExampleApplication
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

class ExampleApplication_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite()
    {
        $suite = new \PHPUnit\Framework\TestSuite('Tine 2.0 ExampleApplication All Tests');
        
        $suite->addTestSuite('ExampleApplication_JsonTest');
        $suite->addTestSuite('ExampleApplication_ImportTest');
        $suite->addTestSuite('ExampleApplication_ControllerTest');
        return $suite;
    }
}
