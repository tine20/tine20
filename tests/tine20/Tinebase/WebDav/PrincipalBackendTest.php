<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Account
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2018 Metaways Infosystems GmbH (http://www.metaways.de)
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
    protected function setUp(): void
{
        parent::setUp();
        
        $this->_backend = new Tinebase_WebDav_PrincipalBackend();
    }

    public function testPrincipalsWithBrokenGroupList()
    {
        $group = Admin_Controller_Group::getInstance()->create(new Tinebase_Model_Group([
            'name' => 'unittest'
        ]));

        Tinebase_Group::getInstance()->addGroupMember($group->getId(), Tinebase_Core::getUser()->getId());
        Tinebase_Core::getDb()->delete(SQL_TABLE_PREFIX . 'addressbook_lists', 'id = "' . $group->list_id . '"');

        $server = new Sabre\DAV\Server(new Tinebase_WebDav_Root());
        $server->debugExceptions = true;

        require_once 'vendor/sabre/dav/tests/Sabre/HTTP/ResponseMock.php';

        $response = new Sabre\HTTP\ResponseMock();
        $server->httpResponse = $response;

        $server->addPlugin(
            new \Sabre\DAV\Auth\Plugin(new Tinebase_WebDav_Auth(), null)
        );

        $aclPlugin = new Tinebase_WebDav_Plugin_ACL();
        $aclPlugin->defaultUsernamePath    = Tinebase_WebDav_PrincipalBackend::PREFIX_USERS;
        $aclPlugin->principalCollectionSet = array (Tinebase_WebDav_PrincipalBackend::PREFIX_USERS, Tinebase_WebDav_PrincipalBackend::PREFIX_GROUPS, Tinebase_WebDav_PrincipalBackend::PREFIX_INTELLIGROUPS
        );
        $aclPlugin->principalSearchPropertySet = array(
            '{DAV:}displayname'                                                   => 'Display name',
            '{' . \Sabre\DAV\Server::NS_SABREDAV . '}email-address'               => 'Email address',
            '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}email-address-set'  => 'Email addresses',
            '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}first-name'         => 'First name',
            '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}last-name'          => 'Last name',
            '{' . \Sabre\CalDAV\Plugin::NS_CALDAV         . '}calendar-user-address-set' => 'Calendar user address set',
            '{' . \Sabre\CalDAV\Plugin::NS_CALDAV         . '}calendar-user-type' => 'Calendar user type'
        );

        $server->addPlugin($aclPlugin);
        $server->addPlugin(new Tinebase_WebDav_Plugin_ExpandedPropertiesReport());
        $server->addPlugin(new Calendar_Frontend_CalDAV_SpeedUpPlugin); // this plugin must be loaded before CalDAV plugin
        $server->addPlugin(new Calendar_Frontend_CalDAV_FixMultiGet404Plugin()); // replacement for new \Sabre\CalDAV\Plugin());

        $body = '<?xml version="1.0" encoding="UTF-8"?>
<A:expand-property xmlns:A="DAV:">
  <A:property name="calendar-proxy-write-for" namespace="http://calendarserver.org/ns/">
    <A:property name="email-address-set" namespace="http://calendarserver.org/ns/"/>
    <A:property name="displayname" namespace="DAV:"/>
    <A:property name="calendar-user-address-set" namespace="urn:ietf:params:xml:ns:caldav"/>
  </A:property>
  <A:property name="calendar-proxy-read-for" namespace="http://calendarserver.org/ns/">
    <A:property name="email-address-set" namespace="http://calendarserver.org/ns/"/>
    <A:property name="displayname" namespace="DAV:"/>
    <A:property name="calendar-user-address-set" namespace="urn:ietf:params:xml:ns:caldav"/>
  </A:property>
</A:expand-property>
';

        $request = new Sabre\HTTP\Request([
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/principals/users/' . Tinebase_Core::getUser()->contact_id . '/',
        ]);
        $request->setBody($body);
        $server->httpRequest = $request;
        $server->exec();

        $this->assertStringContainsString('<d:status>HTTP/1.1 200 OK</d:status>', $response->body);
    }

    /**
     * test getPrincipalsByPrefix with groups prefix
     */
    public function testGetPrincipalsByGroupPrefix()
    {
        $principals = $this->_backend->getPrincipalsByPrefix(Tinebase_WebDav_PrincipalBackend::PREFIX_GROUPS);

        static::assertGreaterThanOrEqual(1, count($principals));
        $roleFound = false;
        foreach($principals as $principal) {
            if (strpos($principal['uri'], '/role-') !== false) {
                $roleFound = true;
                break;
            }
        }
        static::assertTrue($roleFound, 'no role principal found');
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
        
        $this->assertEquals(Tinebase_WebDav_PrincipalBackend::PREFIX_GROUPS . '/' . $list->list_id, $principal['uri']);
        $this->assertEquals($list->name . ' ('. Tinebase_Translation::getTranslation('Calendar')->_('Group') . ')', $principal['{DAV:}displayname']);

        $role = Tinebase_Acl_Roles::getInstance()->getRoleByName('user role');
        $principal = $this->_backend->getPrincipalByPath(Tinebase_WebDav_PrincipalBackend::PREFIX_GROUPS . '/role-' . $role->getId());
        $this->assertEquals(Tinebase_WebDav_PrincipalBackend::PREFIX_GROUPS . '/role-' . $role->getId(), $principal['uri']);
    }
    
    public function testGetPrincipalByUserPath()
    {
        $principal = $this->_backend->getPrincipalByPath(Tinebase_WebDav_PrincipalBackend::PREFIX_USERS . '/' . Tinebase_Core::getUser()->contact_id);
        
        $this->assertEquals(Tinebase_WebDav_PrincipalBackend::PREFIX_USERS . '/' . Tinebase_Core::getUser()->contact_id, $principal['uri']);
        $this->assertEquals(Tinebase_Core::getUser()->accountDisplayName, $principal['{DAV:}displayname']);
    }
    
    public function testGetGroupMembership()
    {
        $groupMemberships = $this->_backend->getGroupMembership(Tinebase_WebDav_PrincipalBackend::PREFIX_USERS . '/' . Tinebase_Core::getUser()->contact_id);
        
        $this->assertGreaterThanOrEqual(1, count($groupMemberships));
        $roleFound = false;
        foreach($groupMemberships as $membership) {
            if (strpos($membership, '/role-') !== false) {
                $roleFound = true;
                break;
            }
        }
        static::assertTrue($roleFound, 'no role membership found');
    }

    public function testSearchPrincipalsQueryForGroups()
    {
        if (Tinebase_User::getConfiguredBackend() === Tinebase_User::ACTIVEDIRECTORY) {
            // account email addresses are empty with AD backend
            $this->markTestSkipped('skipped for ad backend');
        }

        $uris = $this->_backend->searchPrincipals(Tinebase_WebDav_PrincipalBackend::PREFIX_GROUPS, array(
                '{http://calendarserver.org/ns/}search-token' => 'user')
        );

        $role = Tinebase_Acl_Roles::getInstance()->getRoleByName('user role');
        $this->assertContains(Tinebase_WebDav_PrincipalBackend::PREFIX_GROUPS . '/role-' . $role->getId(), $uris);
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
        /*Tinebase_Core::getCache()->clean();
        Tinebase_Group::getInstance()->resetClassCache();*/

        $list = Tinebase_Group::getInstance()->getGroupById(Tinebase_Core::getUser()->accountPrimaryGroup);
        
        $groupMemberships = $this->_backend->getGroupMemberSet(Tinebase_WebDav_PrincipalBackend::PREFIX_GROUPS . '/' . $list->list_id);
        
        //var_dump($groupMemberships);
        
        static::assertGreaterThanOrEqual(1, count($groupMemberships));

        $role = Tinebase_Acl_Roles::getInstance()->getRoleByName('user role');
        $groupMemberships = $this->_backend->getGroupMemberSet(Tinebase_WebDav_PrincipalBackend::PREFIX_GROUPS . '/role-' . $role->getId());
        static::assertGreaterThanOrEqual(1, count($groupMemberships));
    }

    public function testGetMemberSetProxyRead()
    {
        $this->markTestIncomplete('needs access to other accounts calendar');
        
        $groupMemberships = $this->_backend->getGroupMemberSet(Tinebase_WebDav_PrincipalBackend::PREFIX_USERS . '/' . Tinebase_Core::getUser()->contact_id . '/calendar-proxy-read');
        
        //var_dump($groupMemberships);
        
        $this->assertGreaterThanOrEqual(1, count($groupMemberships));
    }
}
