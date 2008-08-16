<?php
/**
 * Tine 2.0 PHP HTTP Client
 * 
 * @package     tests
 * @subpackage  php_client
 * @license     New BSD License
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_ConnectionTest::main');
}

class Tinebase_ConnectionTest extends PHPUnit_Framework_TestCase
{
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_ConnectionTest');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    public function setup()
    {

    }
    
    public function testCreateConnection()
    {
        $connection = new Tinebase_Connection(
            $GLOBALS['TestHelper']['url'],
            $GLOBALS['TestHelper']['username'],
            $GLOBALS['TestHelper']['password']
        );
    }
    
    public function testDefaultConnection()
    {
        $connection = new Tinebase_Connection(
            $GLOBALS['TestHelper']['url'],
            $GLOBALS['TestHelper']['username'],
            $GLOBALS['TestHelper']['password']
        );
        Tinebase_Connection::setDefaultConnection($connection);
        $this->assertEquals($connection, Tinebase_Connection::getDefaultConnection());
    }
}

if (PHPUnit_MAIN_METHOD == 'Tinebase_ConnectionTest::main') {
    AllTests::main();
}
//$connection = Tinebase_Connection::getInstance($url, $username, $password);
//Tinebase_Service_Abstract::setDefaultConnection($connection);
