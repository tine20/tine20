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
    define('PHPUnit_MAIN_METHOD', 'Tinebase_LoginTest::main');
}

class Tinebase_LoginTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Tinebase_Connection
     */
    protected $_connection = NULL;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_LoginTest');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    public function setup()
    {
        $this->_connection = Tinebase_Connection::getDefaultConnection();
    }
    
    public function testLogin()
    {
        $this->_connection->login();
        $user = $this->_connection->getUser();
        $this->assertTrue(count(array_keys($user)) > 1);
    }
}

if (PHPUnit_MAIN_METHOD == 'Tinebase_LoginTest::main') {
    AllTests::main();
}