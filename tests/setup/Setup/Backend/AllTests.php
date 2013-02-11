<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

class Setup_Backend_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tine 2.0 Setup All Backend Tests');
        
        $backendAdapter = Tinebase_Core::getConfig()->database->adapter;
        if ($backendAdapter === 'pdo_mysql') {
            // MySQL only tests
            $suite->addTestSuite('Setup_Backend_MysqlTest');
            $suite->addTestSuite('Setup_Backend_Schema_AllTests');
        } else if ($backendAdapter === 'pdo_pgsql') {
            // Postgresql only tests
            $suite->addTestSuite('Setup_Backend_PgsqlTest');
        }
        
        return $suite;
    }
}
