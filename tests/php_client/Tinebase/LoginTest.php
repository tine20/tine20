<?php
/**
 * Tine 2.0
 * 
 * @package     tests
 * @subpackage  php_client
 * @license     yet unknown
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/*
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_UserTest::main');
}
*/

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
        $this->_connection = Tinebase_Connection::getInstance();
    }
    
    public function testLogin()
    {
        $user = $this->_connection->getUser();
        $this->assertTrue(count(array_keys($user)) > 1);
    }
    
    public function testLogout()
    {
        $this->_connection->logout();
        $user = $this->_connection->getUser();
        $this->assertEquals(0, count(array_keys($user)));
    }
}
