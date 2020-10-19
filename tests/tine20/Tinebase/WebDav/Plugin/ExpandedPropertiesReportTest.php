<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 */


/**
 * Test class for Tinebase_WebDav_Plugin_OwnCloud
 */
class Tinebase_WebDav_Plugin_ExpandedPropertiesReportTest extends Tinebase_WebDav_Plugin_PrincipalSearchTest
{
    protected function setUp(): void
{
        parent::setUp();
        $this->server->addPlugin(new Tinebase_WebDav_Plugin_ExpandedPropertiesReport());
    }

    public function testExpandProperty()
    {
        $list = Tinebase_Group::getInstance()->getGroupById(Tinebase_Core::getUser()->accountPrimaryGroup);

        $body = '<?xml version="1.0" encoding="UTF-8"?>
                <A:expand-property xmlns:A="DAV:">
                  <A:property name="expanded-group-member-set" namespace="http://calendarserver.org/ns/">
                    <A:property name="last-name" namespace="http://calendarserver.org/ns/"/>
                    <A:property name="principal-URL" namespace="DAV:"/>
                    <A:property name="calendar-user-type" namespace="urn:ietf:params:xml:ns:caldav"/>
                    <A:property name="calendar-user-address-set" namespace="urn:ietf:params:xml:ns:caldav"/>
                    <A:property name="first-name" namespace="http://calendarserver.org/ns/"/>
                    <A:property name="record-type" namespace="http://calendarserver.org/ns/"/>
                    <A:property name="displayname" namespace="DAV:"/>
                  </A:property>
                </A:expand-property>';

        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/principals/groups/' . $list->list_id . '/'
        ));
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();

        $responseDoc = new DOMDocument();
        $responseDoc->loadXML($this->response->body);
        #$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('cal', 'urn:ietf:params:xml:ns:caldav');
        $xpath->registerNamespace('cs', 'http://calendarserver.org/ns/');

        $nodes = $xpath->query('///cs:expanded-group-member-set/d:response/d:href[text()="/principals/groups/' . $list->list_id . '/"]');
        $this->assertEquals(1, $nodes->length, 'group itself (not shown by client) is missing');

        $nodes = $xpath->query('///cs:expanded-group-member-set/d:response/d:href[text()="/principals/intelligroups/' . $list->list_id . '/"]');
        $this->assertEquals(1, $nodes->length, 'intelligroup (to keep group itself) is missing');

        $nodes = $xpath->query('///cs:expanded-group-member-set/d:response/d:href[text()="/principals/users/' . Tinebase_Core::getUser()->contact_id . '/"]');
        $this->assertEquals(1, $nodes->length, 'user is missing');
    }

    public function testConvert()
    {
        $emailArrayFromClient = array(array(
            'userType'     => 'user',
            'firstName'    => 'Users',
            'lastName'     => '(Group)',
            'partStat'     => 'NEEDS-ACTION',
            'role'         => 'REQ',
            'email'        => 'urn:uuid:principals/intelligroups/cc74c2880f8c5c0eaacc57ea95f4d2571fb8a4b1',
        ));

        $event = new Calendar_Model_Event();
        Calendar_Model_Attender::emailsToAttendee($event, $emailArrayFromClient);

        $this->assertEquals('cc74c2880f8c5c0eaacc57ea95f4d2571fb8a4b1', $event->attendee->getFirstRecord()->user_id);
        $this->assertEquals('group', $event->attendee->getFirstRecord()->user_type);
    }
}
