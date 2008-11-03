<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Erp
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id: AllTests.php 4597 2008-09-25 15:02:00Z p.schuele@metaways.de $
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (! defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Erp_AllTests::main');
}

class Erp_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 Erp All Tests');
        $suite->addTestSuite('Erp_Backend_ProjectTest');
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Erp_AllTests::main') {
    Erp_AllTests::main();
}
