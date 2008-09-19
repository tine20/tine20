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
        $this->_backend = new Phone_Backend_Snom_Callhistory();        
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
     * test save
     * 
     */
    public function testSave()
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
    }
}
