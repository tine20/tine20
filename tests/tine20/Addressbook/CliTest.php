<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

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
        $out = $this->_cliHelper(array(
            'containerId=' . $this->_container->getId(), 
            'accountId=' . Tinebase_Core::getUser()->getId(), 
            'grants=privateGrant'
        ));
        
        $grants = Tinebase_Container::getInstance()->getGrantsOfContainer($this->_container);
        $this->assertTrue(($grants->getFirstRecord()->privateGrant == 1));
    }

    /**
     * test to set container grants with filter and overwrite old grants
     */
    public function testSetContainerGrantsWithFilterAndOverwrite()
    {
        $nameFilter = 'Tine 2.0 Admin Account';
        $filter = new Tinebase_Model_ContainerFilter(array(
            array('field' => 'application_id', 'operator' => 'equals', 
                'value' => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId()),
            array('field' => 'name', 'operator' => 'contains', 'value' => $nameFilter),
        ));
        $count = Tinebase_Container::getInstance()->searchCount($filter);
        
        $out = $this->_cliHelper(array(
            'namefilter="' . $nameFilter . '"', 
            'accountId=' . Tinebase_Core::getUser()->getId(), 
            'grants=privateGrant,adminGrant',
            'overwrite=1'
        ), $count);
        
        $grants = Tinebase_Container::getInstance()->getGrantsOfContainer($this->_container);
        $this->assertTrue(($grants->getFirstRecord()->privateGrant == 1));
        $this->assertTrue(($grants->getFirstRecord()->adminGrant == 1));
    }
    
    /**
     * call setContainerGrants cli function with params
     * 
     * @param array $_params
     * @return string
     */
    protected function _cliHelper($_params, $_numberExpected = 1)
    {
        $opts = new Zend_Console_Getopt('abp:');
        $opts->setArguments($_params);
        
        ob_start();
        $this->_cli->setContainerGrants($opts);
        $out = ob_get_clean();
        
        $this->assertContains("Updated $_numberExpected container(s)", $out, 'Text not found in: ' . $out);
        
        return $out;
    }
}
