<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id: AllTests.php 2695 2008-06-05 13:43:25Z ph_il $
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Voipmanager_AllTests::main');
}

class Voipmanager_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 Voipmanager All Tests');
        $suite->addTest(Voipmanager_Backend_Snom_AllTests::suite());
        $suite->addTestSuite('Voipmanager_ControllerTest');
        //$suite->addTestSuite('Voipmanager_Backend_Snom_PhoneTest');
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Voipmanager_AllTests::main') {
    Voipmanager_AllTests::main();
}
