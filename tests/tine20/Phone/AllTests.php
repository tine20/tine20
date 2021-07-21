<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

class Phone_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new \PHPUnit\Framework\TestSuite('Tine 2.0 Phone All Tests');
        $suite->addTestSuite('Phone_Frontend_JsonTest');
        $suite->addTestSuite('Phone_Frontend_SnomTest');
        $suite->addTestSuite('Phone_ControllerTest');
        $suite->addTestSuite('Phone_Call_ControllerTest');
        return $suite;
    }
}
