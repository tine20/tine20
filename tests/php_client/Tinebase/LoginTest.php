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
    
    public function testLogin()
    {
        $client = Tinebase_Connection::getInstance();
        $user = $client->getUser();
        $this->assertTrue(count(array_keys($user)) > 1);
    }
}
