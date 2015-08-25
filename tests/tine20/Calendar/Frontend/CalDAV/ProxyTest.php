<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2014-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once __DIR__ . '/../../../../../tine20/vendor/sabre/dav/tests/Sabre/HTTP/ResponseMock.php';

/**
 * Test class for Calendar_Frontend_CalDAV_ProxyTest
 */
class Calendar_Frontend_CalDAV_ProxyTest extends TestCase
{
    /**
     * 
     * @var Sabre\DAV\Server
     */
    protected $server;
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();

        // create shared folder and other users folder
        $this->sharedContainer = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'           => __CLASS__ . Tinebase_Record_Abstract::generateUID(),
            'type'           => Tinebase_Model_Container::TYPE_SHARED,
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'backend'        => 'Sql'
        )));

        $sclever = Tinebase_Helper::array_value('sclever', Zend_Registry::get('personas'));
        $this->otherUsersContainer = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'           => __CLASS__ . Tinebase_Record_Abstract::generateUID(),
            'type'           => Tinebase_Model_Container::TYPE_PERSONAL,
            'owner_id'       => $sclever->getId(),
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'backend'        => 'Sql'
        )));
        Tinebase_Container::getInstance()->addGrants($this->otherUsersContainer, Tinebase_Acl_Rights::ACCOUNT_TYPE_USER, Tinebase_Core::getUser(), array(
            Tinebase_Model_Grants::GRANT_READ,
            Tinebase_Model_Grants::GRANT_SYNC,
        ), true);

        // clear container caches (brute force)
        Tinebase_Core::getCache()->clean(Zend_Cache::CLEANING_MODE_ALL);

        $this->server = new Sabre\DAV\Server(new Tinebase_WebDav_Root());
        $this->server->debugExceptions = true;
        $this->server->addPlugin(new \Sabre\CalDAV\Plugin());
        $this->server->addPlugin(new \Sabre\CalDAV\SharingPlugin());
        
        $aclPlugin = new \Sabre\DAVACL\Plugin();
        $aclPlugin->defaultUsernamePath    = Tinebase_WebDav_PrincipalBackend::PREFIX_USERS;
        $aclPlugin->principalCollectionSet = array (Tinebase_WebDav_PrincipalBackend::PREFIX_USERS, Tinebase_WebDav_PrincipalBackend::PREFIX_GROUPS);
        $this->server->addPlugin($aclPlugin);
        
        $this->response = new Sabre\HTTP\ResponseMock();
        $this->server->httpResponse = $this->response;
    }

    /**
     * test testGetProperties method
     */
    public function testGetProperties()
    {
        $body = '<?xml version="1.0" encoding="UTF-8"?>
            <A:expand-property xmlns:A="DAV:">
                <A:property name="calendar-proxy-read-for" namespace="http://calendarserver.org/ns/">
                    <A:property name="email-address-set" namespace="http://calendarserver.org/ns/"/>
                    <A:property name="displayname" namespace="DAV:"/>
                    <A:property name="calendar-user-address-set" namespace="urn:ietf:params:xml:ns:caldav"/>
                </A:property>
                <A:property name="calendar-proxy-write-for" namespace="http://calendarserver.org/ns/">
                    <A:property name="email-address-set" namespace="http://calendarserver.org/ns/"/>
                    <A:property name="displayname" namespace="DAV:"/>
                    <A:property name="calendar-user-address-set" namespace="urn:ietf:params:xml:ns:caldav"/>
                </A:property>
            </A:expand-property>';

        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/' . Tinebase_WebDav_PrincipalBackend::PREFIX_USERS . '/' . Tinebase_Core::getUser()->contact_id
        ));
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();
        //var_dump($this->response->body);

        $this->assertEquals('HTTP/1.1 207 Multi-Status', $this->response->status);
        
        $responseDoc = new DOMDocument();
        $responseDoc->loadXML($this->response->body);
        //$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('cal', 'urn:ietf:params:xml:ns:caldav');
        $xpath->registerNamespace('cs',  'http://calendarserver.org/ns/');
        
        $nodes = $xpath->query('//d:multistatus/d:response/d:propstat/d:prop/cs:calendar-proxy-read-for');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        #$this->assertEmpty($nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//d:multistatus/d:response/d:propstat/d:prop/cs:calendar-proxy-write-for');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        #$this->assertEmpty($nodes->item(0)->nodeValue, $responseDoc->saveXML());

        $nodes = $xpath->query('///d:response/d:href[text()="/principals/users/shared/"]');
        $this->assertEquals(1, $nodes->length, "shared principal is missing in \n" . $responseDoc->saveXML());

        $sclever = Tinebase_Helper::array_value('sclever', Zend_Registry::get('personas'));
        $nodes = $xpath->query('///d:response/d:href[text()="/principals/users/'. $sclever->contact_id .'/"]');
        $this->assertEquals(1, $nodes->length, "sclevers principal is missing in \n" .$responseDoc->saveXML());
    }
    
    /**
     * test testGetProperties method
     */
    public function testGetPropertiesSharedUser()
    {
        $body = '<?xml version="1.0" encoding="UTF-8"?>
            <A:expand-property xmlns:A="DAV:">
                <A:property name="calendar-proxy-read-for" namespace="http://calendarserver.org/ns/">
                    <A:property name="email-address-set" namespace="http://calendarserver.org/ns/"/>
                    <A:property name="displayname" namespace="DAV:"/>
                    <A:property name="calendar-user-address-set" namespace="urn:ietf:params:xml:ns:caldav"/>
                </A:property>
                <A:property name="calendar-proxy-write-for" namespace="http://calendarserver.org/ns/">
                    <A:property name="email-address-set" namespace="http://calendarserver.org/ns/"/>
                    <A:property name="displayname" namespace="DAV:"/>
                    <A:property name="calendar-user-address-set" namespace="urn:ietf:params:xml:ns:caldav"/>
                </A:property>
            </A:expand-property>';

        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'REPORT',
            'REQUEST_URI'    => '/' . Tinebase_WebDav_PrincipalBackend::PREFIX_USERS . '/' . Tinebase_WebDav_PrincipalBackend::SHARED
        ));
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();
        //var_dump($this->response->body);
        
        $this->assertEquals('HTTP/1.1 207 Multi-Status', $this->response->status);
        
        $responseDoc = new DOMDocument();
        $responseDoc->loadXML($this->response->body);
        //$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('cal', 'urn:ietf:params:xml:ns:caldav');
        $xpath->registerNamespace('cs',  'http://calendarserver.org/ns/');
        
        $nodes = $xpath->query('//d:multistatus/d:response/d:propstat/d:prop/cs:calendar-proxy-read-for');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        #$this->assertEmpty($nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//d:multistatus/d:response/d:propstat/d:prop/cs:calendar-proxy-write-for');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        #$this->assertEmpty($nodes->item(0)->nodeValue, $responseDoc->saveXML());
    }
    
    /**
     * test testGetProperties method
     */
    public function testGetPropertiesSharedUserPrincipal()
    {
        $body = '<?xml version="1.0" encoding="UTF-8"?>
            <A:propfind xmlns:A="DAV:">
              <A:prop>
                <B:calendar-home-set xmlns:B="urn:ietf:params:xml:ns:caldav"/>
                <B:calendar-user-address-set xmlns:B="urn:ietf:params:xml:ns:caldav"/>
                <A:current-user-principal/>
                <A:displayname/>
                <C:dropbox-home-URL xmlns:C="http://calendarserver.org/ns/"/>
                <C:email-address-set xmlns:C="http://calendarserver.org/ns/"/>
                <C:notification-URL xmlns:C="http://calendarserver.org/ns/"/>
                <A:principal-collection-set/>
                <A:principal-URL/>
                <A:resource-id/>
                <B:schedule-inbox-URL xmlns:B="urn:ietf:params:xml:ns:caldav"/>
                <B:schedule-outbox-URL xmlns:B="urn:ietf:params:xml:ns:caldav"/>
                <A:supported-report-set/>
              </A:prop>
            </A:propfind>';

        $request = new Sabre\HTTP\Request(array(
            'REQUEST_METHOD' => 'PROPFIND',
            'REQUEST_URI'    => '/' . Tinebase_WebDav_PrincipalBackend::PREFIX_USERS . '/' . Tinebase_WebDav_PrincipalBackend::SHARED,
            'HTTP_BRIEF'     => 't',
            'HTTP_DEPTH'     => '0'
        ));
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();
        //var_dump($this->response->body);
        
        $this->assertEquals('HTTP/1.1 207 Multi-Status', $this->response->status);
        
        $responseDoc = new DOMDocument();
        $responseDoc->loadXML($this->response->body);
        //$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('cal', 'urn:ietf:params:xml:ns:caldav');
        $xpath->registerNamespace('cs',  'http://calendarserver.org/ns/');
        $xpath->registerNamespace('d',  'DAV');
        
        $nodes = $xpath->query('//d:multistatus/d:response/d:propstat/d:prop/cal:calendar-home-set');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        #$this->assertEmpty($nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//d:multistatus/d:response/d:propstat/d:prop/d:principal-URL');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        #$this->assertEmpty($nodes->item(0)->nodeValue, $responseDoc->saveXML());
    }
}
