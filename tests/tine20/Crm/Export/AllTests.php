<?php
/**
 * Crm_Export_AllTests
 * 
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

class Crm_Export_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 Crm All Export Tests');
        $suite->addTestSuite('Crm_Export_PdfTest');
        $suite->addTestSuite('Crm_Export_CsvTest');
        $suite->addTestSuite('Crm_Export_OdsTest');
        $suite->addTestSuite('Crm_Export_XlsTest');
        return $suite;
    }
}
