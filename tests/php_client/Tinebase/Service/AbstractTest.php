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
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_Service_AbstractTest::main');
}

class Tinebase_Service_AbstractTest extends PHPUnit_Framework_TestCase
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
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_Service_AbstractTest');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    public function setup()
    {
        $this->_connection = Tinebase_Connection::getDefaultConnection();
    }

    public function testServiceCreationWithConnection()
    {
        $service = new Tinebase_Service_ConcreteService($this->_connection);
        $this->assertTrue($service instanceof Tinebase_Service_Abstract);
    }
    
    public function testServiceCreationWithoutConnection()
    {
        $service = new Tinebase_Service_ConcreteService();
        $this->assertTrue($service instanceof Tinebase_Service_Abstract);
    }
    
    public function testServiceCreationFail()
    {
        $this->setExpectedException('Exception');
        $service = new Tinebase_Service_ConcreteService('No Connection');
    }
}

if (PHPUnit_MAIN_METHOD == 'Tinebase_Service_AbstractTest::main') {
    AllTests::main();
}
