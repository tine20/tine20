<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Addressbook_CliTest::main');
}

/**
 * Test class for Addressbook_Frontend_Cli
 */
class Addressbook_CliTest extends PHPUnit_Framework_TestCase
{
    /**
     * Backend
     *
     * @var Addressbook_Frontend_Cli
     */
    protected $_cli;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Addressbook Cli Tests');
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
        $this->_cli = new Addressbook_Frontend_Cli();
        $this->_container = Addressbook_Controller_Contact::getInstance()->getDefaultAddressbook();
        $this->_originalGrants = Tinebase_Container::getInstance()->getGrantsOfContainer($this->_container);
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        Tinebase_Container::getInstance()->setGrants($this->_container, $this->_originalGrants, TRUE);
    }
    
    /**
     * test to set container grants
     */
    public function testSetContainerGrants()
    {
        $opts = new Zend_Console_Getopt('abp:');
        $params = array('containerId=' . $this->_container->getId(), 'accountId=' . Tinebase_Core::getUser()->getId(), 'grants=privateGrant');
        $opts->setArguments($params);
        
        ob_start();
        $this->_cli->setContainerGrants($opts);
        $out = ob_get_clean();
        
        $this->assertContains("Added grants to container.", $out);        
        
        $grants = Tinebase_Container::getInstance()->getGrantsOfContainer($this->_container);
        $this->assertTrue(($grants->getFirstRecord()->privateGrant == 1));
    }
}       
    
if (PHPUnit_MAIN_METHOD == 'Addressbook_CliTest::main') {
    Addressbook_CliTest::main();
}
