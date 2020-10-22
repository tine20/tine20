<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @subpackage  WebDav
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Test helper
 */
require_once 'vendor/sabre/dav/tests/Sabre/DAV/Auth/Backend/Mock.php';

/**
 *
 */
class Tinebase_WebDav_Plugin_PropfindTest extends Tinebase_WebDav_Plugin_AbstractBaseTest
{
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
{
        if (Tinebase_User::getConfiguredBackend() === Tinebase_User::ACTIVEDIRECTORY) {
            $this->markTestSkipped('only working in non-AD setups');
        }

        parent::setUp();

        $mockBackend = new Sabre\DAV\Auth\Backend\Mock();
        $mockBackend->defaultUser = Tinebase_Core::getUser()->contact_id;

        $plugin = new Sabre\DAV\Auth\Plugin($mockBackend,'realm');
        $this->server->addPlugin($plugin);

        $aclPlugin = new Tinebase_WebDav_Plugin_ACL();
        $aclPlugin->defaultUsernamePath = Tinebase_WebDav_PrincipalBackend::PREFIX_USERS;
        $aclPlugin->principalCollectionSet = array(Tinebase_WebDav_PrincipalBackend::PREFIX_USERS, Tinebase_WebDav_PrincipalBackend::PREFIX_GROUPS, Tinebase_WebDav_PrincipalBackend::PREFIX_INTELLIGROUPS
        );
        $aclPlugin->principalSearchPropertySet = array(
            '{DAV:}displayname' => 'Display name',
            '{' . \Sabre\DAV\Server::NS_SABREDAV . '}email-address' => 'Email address',
            '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}email-address-set' => 'Email addresses',
            '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}first-name' => 'First name',
            '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}last-name' => 'Last name',
            '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}calendar-user-address-set' => 'Calendar user address set',
            '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}calendar-user-type' => 'Calendar user type'
        );

        $this->server->addPlugin($aclPlugin);

        $this->server->addPlugin(new \Sabre\CalDAV\Plugin());
        $this->server->addPlugin(new \Sabre\CalDAV\SharingPlugin());

        $this->plugin = new Tinebase_WebDav_Plugin_PrincipalSearch();
        $this->server->addPlugin($this->plugin);
    }

    /**
     * test testGetProperties method
     */
    public function testPropfindOnUserWithHiddenGroup()
    {
        $group = Admin_Controller_Group::getInstance()->create(new Tinebase_Model_Group([
            'name'          => 'webdavunittest',
            'visibility'    => Tinebase_Model_Group::VISIBILITY_HIDDEN,
            'members'       => [Tinebase_Core::getUser()->getId()]
        ]));
        Tinebase_Group::getInstance()->addGroupMember($group, Tinebase_Core::getUser());
        Tinebase_Group::getInstance()->resetClassCache();

        $body = '<?xml version=\'1.0\' encoding=\'UTF-8\' ?' . '>
            <propfind xmlns="DAV:" xmlns:CAL="urn:ietf:params:xml:ns:caldav">
                <prop>
                    <CAL:calendar-home-set />
                    <n0:calendar-proxy-read-for xmlns:n0="http://calendarserver.org/ns/" />
                    <n1:calendar-proxy-write-for xmlns:n1="http://calendarserver.org/ns/" />
                    <group-membership />
                </prop>
            </propfind>';

        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'PROPFIND',
            'REQUEST_URI'    => '/principals/users/' . Tinebase_Core::getUser()->contact_id . '/'
        ));
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();
        $this->assertEquals('HTTP/1.1 207 Multi-Status', $this->response->status, $this->response->body);

        $responseDoc = new DOMDocument();
        $responseDoc->loadXML($this->response->body);
        $responseDoc->formatOutput = true;
        $responseXml = $responseDoc->saveXML();
        $xpath = new DomXPath($responseDoc);

        $listId = Tinebase_Group::getInstance()->getGroupById(Tinebase_Core::getUser()->accountPrimaryGroup)->list_id;
        $nodes = $xpath->query('//d:multistatus/d:response/d:propstat/d:prop/d:group-membership/d:href'
            . '[text() = "/principals/groups/' . $listId . '"]');
        $this->assertEquals(1, $nodes->length, $responseXml . PHP_EOL . ' can not find list id ' . $listId);
        $this->assertNotEmpty($nodes->item(0)->nodeValue, $responseXml);

        $nodes = $xpath->query('//d:multistatus/d:response/d:propstat/d:prop/d:group-membership/d:href'
            . '[text() = "/principals/groups/' . $group->list_id . '"]');
        $this->assertEquals(0, $nodes->length, $responseXml . PHP_EOL . ' should not find list id ' . $group->list_id);
    }
}
