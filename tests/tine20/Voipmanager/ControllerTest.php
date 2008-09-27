<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Voipmanager_ControllerTest::main');
}

/**
 * Test class for Tinebase_Group
 */
class Voipmanager_ControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * Fixtures
     * 
     * @var array test objects
     */
    protected $_objects = array();

    /**
     * Backend
     *
     * @var Voipmanager_Controller
     */
    protected $_backend;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Voipmanager Controller Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
	}

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->_backend = Voipmanager_Controller::getInstance();    

        #$this->_objects['call'] = new Phone_Model_Call(array(
        #    'id'                    => 'phpunitcallid',
        #    'line_id'               => 'phpunitlineid',
        #    'phone_id'              => 'phpunitphoneid',
        #    'direction'             => Phone_Model_Call::TYPE_INCOMING,
        #    'source'                => '26',
        #    'destination'           => '0406437435',    
        #));
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {	
    }

    /**
     * test getDBInstance
     * Zend_Db_Adapter_Abstract
     */
    public function testGetDBInstance()
    {
        $db = $this->_backend->getDBInstance();
        
        $this->assertType('Zend_Db_Adapter_Abstract', $db);
    }
    
    public function testCreateAsteriskContext()
    {
        $testContext = $this->_getAsteriskContext();
        $returnedContext = $this->_backend->createAsteriskContext($testContext);
        $this->_backend->deleteAsteriskContexts($returnedContext); 
    }
    
    protected function _getAsteriskContext()
    {
        return new Voipmanager_Model_AsteriskContext(array(
            'name'  => 'Unit Test Context',
            'description' => 'Buh!'
        ));
    }    
}		
