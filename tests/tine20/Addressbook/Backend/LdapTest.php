<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_User
 */
class Addressbook_Backend_LdapTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();

    /**
     * Backend
     *
     * @var Addressbook_Backend_Ldap
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
        $suite  = new \PHPUnit\Framework\TestSuite('Tine 2.0 Addressbook LDAP Backend Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
{
        $config = Tinebase_Core::getConfig()->get('accounts')->get('ldap')->toArray();
        $this->_backend = new Addressbook_Backend_Ldap($config);
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown(): void
{
    
    }
    
    public function testGetAll()
    {
        $this->_backend->getAll();
    }
}
