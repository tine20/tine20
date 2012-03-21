<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Account
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_WebDav_PrincipalBackendTest::main');
}

/**
 * Test class for Tinebase_Group
 * @depricated, some fns might be moved to other testclasses
 */
class Tinebase_WebDav_PrincipalBackendTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    /**
     * @var Tinebase_WebDav_PrincipalBackend
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
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_WebDav_PrincipalBackendTest');
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
        $this->_backend = new Tinebase_WebDav_PrincipalBackend();
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
    
    public function testGetPrincipalByPath()
    {
        $principal = $this->_backend->getPrincipalByPath('principals/users/' . Tinebase_Core::getUser()->contact_id);
        
        //var_dump($principal);
        
        $this->assertEquals('principals/users/' . Tinebase_Core::getUser()->contact_id, $principal['uri']);
        $this->assertEquals(Tinebase_Core::getUser()->accountDisplayName, $principal['{DAV:}displayname']);
        $this->assertTrue(! empty($principal['{urn:ietf:params:xml:ns:caldav}schedule-inbox-URL']));
        $this->assertTrue(! empty($principal['{urn:ietf:params:xml:ns:caldav}schedule-outbox-URL']));
    }
    
    public function testGetGroupMembership()
    {
        $groupMemberships = $this->_backend->getGroupMembership('principals/users/' . Tinebase_Core::getUser()->contact_id);
        
        //var_dump($groupMemberships);
        
        $this->assertGreaterThanOrEqual(1, count($groupMemberships));
    }
}        
    

if (PHPUnit_MAIN_METHOD == 'Tinebase_WebDav_PrincipalBackendTest::main') {
    Tinebase_WebDav_PrincipalBackendTest::main();
}
