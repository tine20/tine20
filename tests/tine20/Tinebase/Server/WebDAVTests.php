<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

// needed for bootstrap / autoloader
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'ServerTestHelper.php';

/**
 * Test class for Tinebase_Server_WebDAV
 * 
 * @package     Tinebase
 */
class Tinebase_Server_WebDAVTests extends ServerTestCase
{
    #protected $_logPriority = Zend_Log::DEBUG;
    
    /**
     * test general functionality of Tinebase_Server_WebDAV
     * @group ServerTests
     */
    public function testServer()
    {
        $request = \Zend\Http\PhpEnvironment\Request::fromString(<<<EOS
PROPFIND /calendars/64d7fdf9202f7b1faf7467f5066d461c2e75cf2b/4/ HTTP/1.1\r
Host: localhost\r
Depth: 0\r
User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7\r
EOS
        );
        
        $_SERVER['REQUEST_METHOD'] = $request->getMethod();
        $_SERVER['REQUEST_URI']    = $request->getUri()->getPath();
        $_SERVER['HTTP_DEPTH']     = '0';
        
        $credentials = $this->getTestCredentials();
        
        $request->getServer()->set('PHP_AUTH_USER', $credentials['username']);
        $request->getServer()->set('PHP_AUTH_PW',   $credentials['password']);
        $request->getServer()->set('REMOTE_ADDR',   'localhost');
        
        $body = fopen('php://temp', 'r+');
        fwrite($body, '<?xml version="1.0" encoding="UTF-8"?><D:propfind xmlns:D="DAV:" xmlns:CS="http://calendarserver.org/ns/"><D:prop><CS:getctag/></D:prop></D:propfind>');
        rewind($body);
        
        ob_start();
        
        $server = new Tinebase_Server_WebDAV();
        
        $server->handle($request, $body);
        
        $result = ob_get_contents();
        
        ob_end_clean();
        
        $this->assertEquals('PD94bWwgdmVyc2lvbj0iMS4wIiBlbm', substr(base64_encode($result),0,30));
    }
    
    /**
     * test general functionality of Tinebase_Server_WebDAV
     * @group ServerTests
     */
    public function testServerWithAuthorizationHeader()
    {
        $credentials = $this->getTestCredentials();
        
        $hash = base64_encode($credentials['username'] . ':' . $credentials['password']);
        
        $request = \Zend\Http\PhpEnvironment\Request::fromString(<<<EOS
PROPFIND /calendars/64d7fdf9202f7b1faf7467f5066d461c2e75cf2b/4/ HTTP/1.1\r
Host: localhost\r
Depth: 0\r
User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7\r
Authorization: Basic $hash\r
EOS
        );
        
        $_SERVER['REQUEST_METHOD'] = $request->getMethod();
        $_SERVER['REQUEST_URI']    = $request->getUri()->getPath();
        $_SERVER['HTTP_DEPTH']     = '0';
        
        $request->getServer()->set('REMOTE_ADDR', 'localhost');
        
        $body = fopen('php://temp', 'r+');
        fwrite($body, '<?xml version="1.0" encoding="UTF-8"?><D:propfind xmlns:D="DAV:" xmlns:CS="http://calendarserver.org/ns/"><D:prop><CS:getctag/></D:prop></D:propfind>');
        rewind($body);
        
        ob_start();
        
        $server = new Tinebase_Server_WebDAV();
        
        $server->handle($request, $body);
        
        $result = ob_get_contents();
        
        ob_end_clean();
        
        $this->assertEquals('PD94bWwgdmVyc2lvbj0iMS4wIiBlbm', substr(base64_encode($result),0,30));
    }
    
    /**
     * test general functionality of Tinebase_Server_WebDAV
     * @group ServerTests
     */
    public function testServerWithAuthorizationEnv()
    {
        $credentials = $this->getTestCredentials();
        
        $hash = base64_encode($credentials['username'] . ':' . $credentials['password']);
        
        $request = \Zend\Http\PhpEnvironment\Request::fromString(<<<EOS
PROPFIND /calendars/64d7fdf9202f7b1faf7467f5066d461c2e75cf2b/4/ HTTP/1.1\r
Host: localhost\r
Depth: 0\r
User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7\r
EOS
        );
        
        $_SERVER['REQUEST_METHOD'] = $request->getMethod();
        $_SERVER['REQUEST_URI']    = $request->getUri()->getPath();
        $_SERVER['HTTP_DEPTH']     = '0';
        
        $request->getServer()->set('HTTP_AUTHORIZATION', 'Basic ' . $hash);
        $request->getServer()->set('REMOTE_ADDR',          'localhost');
        
        $body = fopen('php://temp', 'r+');
        fwrite($body, '<?xml version="1.0" encoding="UTF-8"?><D:propfind xmlns:D="DAV:" xmlns:CS="http://calendarserver.org/ns/"><D:prop><CS:getctag/></D:prop></D:propfind>');
        rewind($body);
        
        ob_start();
        
        $server = new Tinebase_Server_WebDAV();
        
        $server->handle($request, $body);
        
        $result = ob_get_contents();
        
        ob_end_clean();
        
        $this->assertEquals('PD94bWwgdmVyc2lvbj0iMS4wIiBlbm', substr(base64_encode($result),0,30));
    }
    
    /**
     * test general functionality of Tinebase_Server_WebDAV
     * @group ServerTests
     */
    public function testServerWithAuthorizationRemoteUser()
    {
        $credentials = $this->getTestCredentials();
        
        $hash = base64_encode($credentials['username'] . ':' . $credentials['password']);
        
        $request = \Zend\Http\PhpEnvironment\Request::fromString(<<<EOS
PROPFIND /calendars/64d7fdf9202f7b1faf7467f5066d461c2e75cf2b/4/ HTTP/1.1\r
Host: localhost\r
Depth: 0\r
User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7\r
EOS
        );
        
        $_SERVER['REQUEST_METHOD'] = $request->getMethod();
        $_SERVER['REQUEST_URI']    = $request->getUri()->getPath();
        $_SERVER['HTTP_DEPTH']     = '0';
        
        $request->getServer()->set('REDIRECT_REMOTE_USER', 'Basic ' . $hash);
        $request->getServer()->set('REMOTE_ADDR',          'localhost');
        
        $body = fopen('php://temp', 'r+');
        fwrite($body, '<?xml version="1.0" encoding="UTF-8"?><D:propfind xmlns:D="DAV:" xmlns:CS="http://calendarserver.org/ns/"><D:prop><CS:getctag/></D:prop></D:propfind>');
        rewind($body);
        
        ob_start();
        
        $server = new Tinebase_Server_WebDAV();
        
        $server->handle($request, $body);
        
        $result = ob_get_contents();
        
        ob_end_clean();
        
        $this->assertEquals('PD94bWwgdmVyc2lvbj0iMS4wIiBlbm', substr(base64_encode($result),0,30));
    }
    
    /**
     * test propfind for current-user-principal
     * 
     * you have to provide a valid contactid
     * @group ServerTests
     */
    public function testPropfindCurrentUserPrincipal()
    {
        $request = \Zend\Http\PhpEnvironment\Request::fromString(<<<EOS
PROPFIND /principals/users/ HTTP/1.1\r
Host: localhost\r
Depth: 0\r
User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7\r
EOS
        );
        
        $_SERVER['REQUEST_METHOD'] = $request->getMethod();
        $_SERVER['REQUEST_URI']    = $request->getUri()->getPath();
        $_SERVER['HTTP_DEPTH']     = '0';
        
        $credentials = $this->getTestCredentials();
        
        $request->getServer()->set('PHP_AUTH_USER', $credentials['username']);
        $request->getServer()->set('PHP_AUTH_PW',   $credentials['password']);
        $request->getServer()->set('REMOTE_ADDR',   'localhost');
        
        $body = fopen('php://temp', 'r+');
        fwrite($body, '<?xml version="1.0" encoding="UTF-8"?><D:propfind xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav"><D:prop><D:current-user-principal/><D:principal-URL/><D:resourcetype/></D:prop></D:propfind>');
        rewind($body);

        $bbody = fopen('php://temp', 'r+');
        fwrite($bbody, '<?xml version="1.0" encoding="UTF-8"?><D:propfind xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav"><D:prop><C:calendar-home-set/><C:calendar-user-address-set/><C:schedule-inbox-URL/><C:schedule-outbox-URL/></D:prop></D:propfind>');
        rewind($bbody);
        
        ob_start();
        
        $server = new Tinebase_Server_WebDAV();
        
        $server->handle($request, $body);
        
        $result = ob_get_contents();
        
        ob_end_clean();
        
        #error_log($result);
        
        $responseDoc = new DOMDocument();
        $responseDoc->loadXML($result);
        #$responseDoc->formatOutput = true; error_log($responseDoc->saveXML());
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('d', 'DAV:');
        
        $nodes = $xpath->query('//d:multistatus/d:response/d:propstat/d:prop/d:current-user-principal/d:href');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertNotEmpty($nodes->item(0)->nodeValue, $responseDoc->saveXML());
    }
    
    /**
     * test propfind for current-user-principal
     * 
     * you have to provide a valid contactid
     * @group ServerTests
     */
    public function testPropfindPrincipal()
    {
        $credentials = $this->getTestCredentials();
        
        $account = $this->getAccountByName($credentials['username']);
        
        $this->assertInstanceOf('Tinebase_Model_FullUser', $account);
        
        $request = \Zend\Http\PhpEnvironment\Request::fromString(<<<EOS
PROPFIND /principals/users/{$account->contact_id}/ HTTP/1.1\r
Host: localhost\r
Depth: 0\r
User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7\r
\r
<?xml version="1.0" encoding="UTF-8"?><D:propfind xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav"><D:prop><C:calendar-home-set/><C:calendar-user-address-set/><C:schedule-inbox-URL/><C:schedule-outbox-URL/></D:prop></D:propfind>
EOS
        );
        
        $_SERVER['REQUEST_METHOD'] = $request->getMethod();
        $_SERVER['REQUEST_URI']    = $request->getUri()->getPath();
        $_SERVER['HTTP_DEPTH']     = '0';
        
        $request->getServer()->set('PHP_AUTH_USER', $credentials['username']);
        $request->getServer()->set('PHP_AUTH_PW',   $credentials['password']);
        $request->getServer()->set('REMOTE_ADDR',   'localhost');
        
        ob_start();
        
        $server = new Tinebase_Server_WebDAV();
        
        $server->handle($request);
        
        $result = ob_get_contents();
        
        ob_end_clean();
        
        #error_log($result);
        
        $responseDoc = new DOMDocument();
        $responseDoc->loadXML($result);
        #$responseDoc->formatOutput = true; error_log($responseDoc->saveXML());
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('d', 'DAV:');
        $xpath->registerNamespace('cal', 'urn:ietf:params:xml:ns:caldav');
        
        $nodes = $xpath->query('//d:multistatus/d:response/d:propstat/d:prop/cal:calendar-home-set');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertNotEmpty($nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//d:multistatus/d:response/d:propstat/d:prop/cal:calendar-user-address-set');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertNotEmpty($nodes->item(0)->nodeValue, $responseDoc->saveXML());
        
        $nodes = $xpath->query('//d:multistatus/d:response/d:propstat/d:prop/cal:schedule-inbox-URL');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertNotEmpty($nodes->item(0)->nodeValue, $responseDoc->saveXML());
    }
    
    /**
     * test general functionality of Tinebase_Server_WebDAV
     * @group ServerTests
     */
    public function testReportQuery()
    {
        $credentials = $this->getTestCredentials();
        
        $account = $this->getAccountByName($credentials['username']);
        
        $this->assertInstanceOf('Tinebase_Model_FullUser', $account);
        
        $containerId = $this->getPersonalContainer($account, 'Calendar_Model_Event')
            ->getFirstRecord()
            ->getId();
        
        $request = \Zend\Http\PhpEnvironment\Request::fromString(<<<EOS
REPORT /calendars/{$account->contact_id}/{$containerId}/ HTTP/1.1\r
Host: localhost\r
Depth: 1\r
Content-Type: application/xml; charset="utf-8"\r
User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7\r
\r
<?xml version="1.0" encoding="utf-8" ?><C:calendar-query xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav"><D:prop><D:getetag/><C:calendar-data/></D:prop><C:filter><C:comp-filter name="VCALENDAR"><C:comp-filter name="VEVENT"><C:time-range start="20060104T000000Z" end="20160105T000000Z"/></C:comp-filter></C:comp-filter></C:filter></C:calendar-query>
EOS
        );
        
        $_SERVER['REQUEST_METHOD'] = $request->getMethod();
        $_SERVER['REQUEST_URI']    = $request->getUri()->getPath();
        $_SERVER['HTTP_DEPTH']     = '0';
        
        $request->getServer()->set('PHP_AUTH_USER', $credentials['username']);
        $request->getServer()->set('PHP_AUTH_PW',   $credentials['password']);
        $request->getServer()->set('REMOTE_ADDR',   'localhost');
        
        ob_start();
        
        $server = new Tinebase_Server_WebDAV();
        
        $server->handle($request);
        
        $result = ob_get_contents();
        
        ob_end_clean();
        
        $responseDoc = new DOMDocument();
        $responseDoc->loadXML($result);
        #$responseDoc->formatOutput = true; error_log($responseDoc->saveXML());
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('cal', 'urn:ietf:params:xml:ns:caldav');
        $xpath->registerNamespace('cs',  'http://calendarserver.org/ns/');
        
        #$nodes = $xpath->query('//d:multistatus/d:response/d:propstat/d:prop/d:current-user-principal');
        #$this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        
        // emoty response
        $this->assertEquals('PD94bWwgdmVyc2lvbj0iMS4wIiBlbm', substr(base64_encode($result),0,30));
    }
    
    /**
     * test PROPFIND on calendar
     * 
     * @group ServerTests
     */
    public function testPropfindThundebird()
    {
        $credentials = $this->getTestCredentials();
        
        $account = $this->getAccountByName($credentials['username']);
        
        $this->assertInstanceOf('Tinebase_Model_FullUser', $account);
        
        $containerId = $this->getPersonalContainer($account, 'Calendar_Model_Event')
            ->getFirstRecord()
            ->getId();
        
        $request = \Zend\Http\PhpEnvironment\Request::fromString(<<<EOS
PROPFIND /calendars/{$account->contact_id}/{$containerId}/ HTTP/1.1\r
Host: localhost\r
Depth: 1\r
Content-Type: application/xml; charset="utf-8"\r
User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7\r
\r
<?xml version="1.0" encoding="UTF-8"?>
<D:propfind xmlns:D="DAV:" xmlns:CS="http://calendarserver.org/ns/" xmlns:C="urn:ietf:params:xml:ns:caldav"><D:prop><D:resourcetype/><D:owner/><D:current-user-principal/><D:supported-report-set/><C:supported-calendar-component-set/><CS:getctag/></D:prop></D:propfind>
EOS
        );
        
        $_SERVER['REQUEST_METHOD'] = $request->getMethod();
        $_SERVER['REQUEST_URI']    = $request->getUri()->getPath();
        $_SERVER['HTTP_DEPTH']     = '0';
        
        $request->getServer()->set('PHP_AUTH_USER', $credentials['username']);
        $request->getServer()->set('PHP_AUTH_PW',   $credentials['password']);
        $request->getServer()->set('REMOTE_ADDR',   'localhost');
        
        ob_start();
        
        $server = new Tinebase_Server_WebDAV();
        
        $server->handle($request);
        
        $result = ob_get_contents();
        
        ob_end_clean();
        
        $responseDoc = new DOMDocument();
        $responseDoc->loadXML($result);
        #$responseDoc->formatOutput = true; error_log($responseDoc->saveXML());
        
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('cal', 'urn:ietf:params:xml:ns:caldav');
        $xpath->registerNamespace('cs',  'http://calendarserver.org/ns/');
        
        $nodes = $xpath->query('//d:multistatus/d:response/d:propstat/d:prop/d:current-user-principal');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
    }
}
