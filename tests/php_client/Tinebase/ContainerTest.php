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
    define('PHPUnit_MAIN_METHOD', 'Tinebase_ContainerTest::main');
}

class Tinebase_ContainerTest extends PHPUnit_Framework_TestCase
{
    protected $backupGlobals = false;
    
    /**
     * @var Tinebase_Connection
     */
    protected $_connection = NULL;
    
    /**
     * @var Tinebase_Service
     */
    protected $_service = NULL;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_Tinebase_ContainerTest');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    public function setup()
    {
        $this->_connection = Tinebase_Connection::getDefaultConnection();
        $this->_service = new Tinebase_Service($this->_connection);
    }

    /**
     * tests retrivial of containers for current user
     *
     */
    public function testGetPersonalContainers()
    {
        $user = $this->_connection->getUser();
        $containers = $this->_service->getContainer('Crm', 'personal', $user->getId());
        
        $this->assertTrue($containers instanceof Tinebase_Record_RecordSet, 'containers is not a recordSet');
        $this->assertGreaterThan(0, count($containers), 'no containers returned, this should not happen!');
    }
}

if (PHPUnit_MAIN_METHOD == 'Tinebase_ContainerTest::main') {
    AllTests::main();
}