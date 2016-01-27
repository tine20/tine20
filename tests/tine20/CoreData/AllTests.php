<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     CoreData
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

class CoreData_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 CoreData All Tests');
        
        $suite->addTestSuite('CoreData_JsonTest');
        return $suite;
    }
}
