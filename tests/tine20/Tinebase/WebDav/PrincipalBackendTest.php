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

/**
 * Test class for Tinebase_Group
 * @depricated, some fns might be moved to other testclasses
 */
class Tinebase_WebDav_PrincipalBackendTest extends TestCase
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
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();
        
        $this->_backend = new Tinebase_WebDav_PrincipalBackend();
    }
    
    /**
     * test getPrincipalsByPrefix with groups prefix
     */
    public function testGetPrincipalsByGroupPrefix()
    {
        $principals = $this->_backend->getPrincipalsByPrefix(Tinebase_WebDav_PrincipalBackend::PREFIX_GROUPS);

        $this->assertGreaterThanOrEqual(1, count($principals));
    }
    
    /**
     * test getPrincipalsByPrefix with users prefix
     */
    public function testGetPrincipalsByUserPrefix()
    {
        $principals = $this->_backend->getPrincipalsByPrefix(Tinebase_WebDav_PrincipalBackend::PREFIX_USERS);

        $this->assertGreaterThanOrEqual(1, count($principals));
    }
    
    /**
     * @todo it's just a fake test // the test has to use a list_id when backend logic is implemented finaly
     */
    public function testGetPrincipalByGroupPath()
    {
        $list = Tinebase_Group::getInstance()->getGroupById(Tinebase_Core::getUser()->accountPrimaryGroup);
        
        $principal = $this->_backend->getPrincipalByPath(Tinebase_WebDav_PrincipalBackend::PREFIX_GROUPS . '/' . $list->list_id);
        
        //var_dump($principal);
        
        $this->assertEquals(Tinebase_WebDav_PrincipalBackend::PREFIX_GROUPS . '/' . $list->list_id, $principal['uri']);
        $this->assertEquals($list->name . ' (Group)', $principal['{DAV:}displayname']);
    }
    
    public function testGetPrincipalByUserPath()
    {
        $principal = $this->_backend->getPrincipalByPath(Tinebase_WebDav_PrincipalBackend::PREFIX_USERS . '/' . Tinebase_Core::getUser()->contact_id);
        
        //var_dump($principal);
        
        $this->assertEquals(Tinebase_WebDav_PrincipalBackend::PREFIX_USERS . '/' . Tinebase_Core::getUser()->contact_id, $principal['uri']);
        $this->assertEquals(Tinebase_Core::getUser()->accountDisplayName, $principal['{DAV:}displayname']);
    }
    
    public function testGetGroupMembership()
    {
        $groupMemberships = $this->_backend->getGroupMembership(Tinebase_WebDav_PrincipalBackend::PREFIX_USERS . '/' . Tinebase_Core::getUser()->contact_id);
        
        //var_dump($groupMemberships);
        
        $this->assertGreaterThanOrEqual(1, count($groupMemberships));
    }
    
    public function testSearchPrincipalsByEMail()
    {
        if (Tinebase_User::getConfiguredBackend() === Tinebase_User::ACTIVEDIRECTORY) {
            // account email addresses are empty with AD backend
            $this->markTestSkipped('skipped for ad backend');
        }

        $uris = $this->_backend->searchPrincipals(Tinebase_WebDav_PrincipalBackend::PREFIX_USERS, array(
            '{http://sabredav.org/ns}email-address' => Tinebase_Core::getUser()->accountEmailAddress)
        );
        
        $this->assertEquals(1, count($uris), 'could not find user by email address ' . Tinebase_Core::getUser()->accountEmailAddress);
        $this->assertContains('principals/users/' . Tinebase_Core::getUser()->contact_id, $uris);
    }
    
    public function testSearchPrincipalsByFirstName()
    {
        if (Tinebase_User::getConfiguredBackend() === Tinebase_User::ACTIVEDIRECTORY) {
            // account email addresses are empty with AD backend
            $this->markTestSkipped('skipped for ad backend');
        }

        $uris = $this->_backend->searchPrincipals(Tinebase_WebDav_PrincipalBackend::PREFIX_USERS, array(
            '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}first-name' => Tinebase_Core::getUser()->accountFirstName)
        );
        
        $this->assertEquals(1, count($uris), 'could not find user by first name ' . Tinebase_Core::getUser()->accountFirstName);
        $this->assertContains('principals/users/' . Tinebase_Core::getUser()->contact_id, $uris);
    }
    
    public function testSearchPrincipalsByLastName()
    {
        $uris = $this->_backend->searchPrincipals(Tinebase_WebDav_PrincipalBackend::PREFIX_USERS, array(
            '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}last-name' => Tinebase_Core::getUser()->accountLastName)
        );
        
        $this->assertEquals(1, count($uris), 'could not find user by email address ' . Tinebase_Core::getUser()->accountEmailAddress);
        $this->assertContains('principals/users/' . Tinebase_Core::getUser()->contact_id, $uris);
    }
    
    public function testGetPrincipalByProxyWritePath()
    {
        $principal = $this->_backend->getPrincipalByPath(Tinebase_WebDav_PrincipalBackend::PREFIX_USERS . '/' . Tinebase_Core::getUser()->contact_id . '/calendar-proxy-write');
        
        //var_dump($principal);
        
        $this->assertEquals(Tinebase_WebDav_PrincipalBackend::PREFIX_USERS . '/' . Tinebase_Core::getUser()->contact_id . '/calendar-proxy-write', $principal['uri']);
    }
    
    public function testGetMemberSetUser()
    {
        $groupMemberships = $this->_backend->getGroupMemberSet(Tinebase_WebDav_PrincipalBackend::PREFIX_USERS . '/' . Tinebase_Core::getUser()->contact_id);
        
        //var_dump($groupMemberships);
        
        $this->assertEquals(0, count($groupMemberships));
    }
    
    public function testGetMemberSetGroup()
    {
        $list = Tinebase_Group::getInstance()->getGroupById(Tinebase_Core::getUser()->accountPrimaryGroup);
        
        $groupMemberships = $this->_backend->getGroupMemberSet(Tinebase_WebDav_PrincipalBackend::PREFIX_GROUPS . '/' . $list->list_id);
        
        //var_dump($groupMemberships);
        
        $this->assertGreaterThanOrEqual(1, count($groupMemberships));
    }

    public function testGetMemberSetProxyRead()
    {
        $this->markTestIncomplete('needs access to other accounts calendar');
        
        $groupMemberships = $this->_backend->getGroupMemberSet(Tinebase_WebDav_PrincipalBackend::PREFIX_USERS . '/' . Tinebase_Core::getUser()->contact_id . '/calendar-proxy-read');
        
        //var_dump($groupMemberships);
        
        $this->assertGreaterThanOrEqual(1, count($groupMemberships));
    }
}
