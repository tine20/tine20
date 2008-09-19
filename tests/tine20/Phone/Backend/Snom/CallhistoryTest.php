<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Phone_Backend_Snom_CallhistoryTest::main');
}

/**
 * Test class for Phone_Backend_Snom_CallhistoryTest
 */
class Phone_Backend_Snom_CallhistoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var bool allow the use of GLOBALS to exchange data between tests
     */
    protected $backupGlobals = false;
    
    /**
     * Fixtures
     * 
     * @var array test objects
     */
    protected $_objects = array();
    
    /**
     * Backend
     *
     * @var Phone_Backend_Snom_Callhistory
     */
    protected $_backend;

    /**
     * Runs the test methods of this class.
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Phone Snom Callhistory Backend Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
	}

    /**
     * Sets up the fixture.
     * 
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        // initialise global for this test suite
        $GLOBALS['Phone_Backend_Snom_CallhistoryTest'] = array_key_exists('Phone_Backend_Snom_CallhistoryTest', $GLOBALS) 
            ? $GLOBALS['Phone_Backend_Snom_CallhistoryTest'] 
            : array();
        
        $this->_backend = new Phone_Backend_Snom_Callhistory();     

        $this->_objects['call'] = new Phone_Model_Call(array(
            'line_id'               => 'phpunitlineid',
            'phone_id'              => 'phpunitphoneid',
            'call_id'               => 'phpunitcallid',
            //'start'                 => Zend_Date::now()->getIso(),
            //'connected'             => '2008-09-19 19:00:02',
            //'disconnected'          => '2008-09-19 19:05:02',
            'direction'             => Phone_Model_Call::TYPE_INCOMING,
            'source'                => '26',
            'destination'           => '0406437435',    
        ));
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     */
    protected function tearDown()
    {
    }
    
    /**
     * test start
     * 
     */
    public function testStartCall()
    {
        $call = $this->_backend->startCall($this->_objects['call']);
        $GLOBALS['Phone_Backend_Snom_CallhistoryTest']['callId'] = $call->getId();
        
        $this->assertEquals($this->_objects['call']->destination, $call->destination);
        $this->assertGreaterThan(Zend_Date::now()->getIso(), $call->start);
    }

    /**
     * test start
     * 
     */
    public function testConnected()
    {
    }

    /**
     * test start
     * 
     */
    public function testDisconnected()
    {
    }
    
    /**
     * test get
     * 
     */
    public function testGet()
    {        
    }
    
    /**
     * test delete
     * 
     */
    public function testDelete()
    {
        $callId = $GLOBALS['Phone_Backend_Snom_CallhistoryTest']['callId'];
        $this->_backend->delete($callId);
        
        $this->setExpectedException('Exception');
        $this->_backend->get($callId);
    }
}
