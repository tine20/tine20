<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Felamimail_Controller_AccountTest::main');
}

/**
 * Test class for Felamimail_Controller_Account
 */
class Felamimail_Controller_AccountTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Felamimail_Controller_Account
     */
    protected $_controller = array();
    
    /**
     * @var Felamimail_Model_Account
     */
    protected $_account = NULL;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Felamimail Account Controller Tests');
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
        $this->_controller = Felamimail_Controller_Account::getInstance();
        $this->_account = $this->_controller->search()->getFirstRecord();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        // reset old account settings
        $this->_controller->update($this->_account);
    }

    /**
     * test account capabilities
     */
    public function testGetAccountCapabilities()
    {
        $account = $this->_controller->updateCapabilities($this->_account);
        
        $this->assertEquals('', $account->ns_personal);
        $this->assertEquals('/', $account->delimiter);
        $this->assertEquals('#Users', $account->ns_other);
        $this->assertEquals('#Public', $account->ns_shared);
    }
}
