<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

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
    public function testServer($noAssert = false)
    {
        $request = Tinebase_Http_Request::fromString(<<<EOS
PROPFIND /calendars/64d7fdf9202f7b1faf7467f5066d461c2e75cf2b/4/ HTTP/1.1
Host: localhost
Depth: 0
User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7
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

        if (true === $noAssert) {
            return $result;
        }

        $this->assertEquals('PD94bWwgdmVyc2lvbj0iMS4wIiBlbm', substr(base64_encode($result),0,30));
    }

    /**
     * @group nogitlabci
     * gitlabci: PHPUnit_Framework_Exception: Tine 2.0 can't setup the configured logger! The Server responded: Zend_Log_Exception: "php://stdout" cannot be opened with mode "a" in
     */
    public function testDenyingWebDavClient()
    {
        Tinebase_Config::getInstance()->set(Tinebase_Config::DENY_WEBDAV_CLIENT_LIST, array('/deniedClient/'));

        $_SERVER['HTTP_USER_AGENT'] = 'deniedClient';
        static::assertTrue(empty($this->testServer(true)));
    }

    public function testServerWithNtlmV2Client()
    {
        Tinebase_Config::getInstance()->set(Tinebase_Config::PASSWORD_SUPPORT_NTLMV2, true);
        Tinebase_Config::getInstance()->set(Tinebase_Config::PASSWORD_NTLMV2_ENCRYPTION_KEY, 'abcdefgh');
        $credentials = $this->getTestCredentials();
        $user = Tinebase_User::getInstance()->getUserByLoginName($credentials['username']);
        try {
            Tinebase_Core::set(Tinebase_Core::USER, $user);
            Tinebase_User::getInstance()->setPassword($user->getId(), $credentials['password']);
        } finally {
            Tinebase_Core::unsetUser();
        }

        $request = Tinebase_Http_Request::fromString(
            "PROPFIND /calendars/64d7fdf9202f7b1faf7467f5066d461c2e75cf2b/4/ HTTP/1.1\r\n"
            . "Host: localhost\r\n"
            . "Depth: 0\r\n"
            . "User-Agent: Microsoft-WebDAV-MiniRedir\r\n"
            //. "Authorization: Basic $hash\r\n"
        );

        $_SERVER['HTTP_USER_AGENT'] = 'Microsoft-WebDAV-MiniRedir';
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

        static::assertTrue(empty($result), 'empty response body expected: ' . print_r($result, true));
        static::assertEquals(Tinebase_Auth_NtlmV2::AUTH_PHASE_NOT_STARTED, $server->getNtlmV2()->getLastAuthStatus());

        $domain = iconv('UTF-8', 'UTF-16LE', 'shoo');
        $msg = "NTLMSSP\x00\x01" .
            "\x00\x00\x00\x00\x00\x00\x00" . // 16
            pack('vvv', strlen($domain), strlen($domain), 22) . $domain;
        $request->getHeaders()->addHeaderLine('Authorization', 'NTLM ' . base64_encode($msg));

        ob_start();
        $server = new Tinebase_Server_WebDAV();
        $server->handle($request, $body);
        $result = ob_get_contents();
        ob_end_clean();

        static::assertTrue(empty($result), 'empty response body expected: ' . print_r($result, true));
        static::assertEquals(Tinebase_Auth_NtlmV2::AUTH_PHASE_ONE, $server->getNtlmV2()->getLastAuthStatus());
        static::assertNotEmpty($server->getNtlmV2()->getLastResponse());


        $clientblob = "\x01\x01\x00\x00\x00\x00\x00\x00" . Tinebase_Auth_NtlmV2::ntlm_get_random_bytes(8);
        $md4hash = Tinebase_Auth_NtlmV2::getPwdHash($credentials['password']);
        $ntlmv2hash = Tinebase_Auth_NtlmV2::ntlm_hmac_md5($md4hash, iconv('UTF-8', 'UTF-16LE', strtoupper($credentials['username']) . 'shoo'));
        $blobhash = Tinebase_Auth_NtlmV2::ntlm_hmac_md5($ntlmv2hash, $server->getNtlmV2()->getServerNounce() . $clientblob);
        $ntlmresponse = $blobhash . $clientblob;
        $username = iconv('UTF-8', 'UTF-16LE', $credentials['username']);
        $msg = "NTLMSSP\x00\x03" .
            "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00" . // 20
            pack('vvv', strlen($ntlmresponse), strlen($ntlmresponse), 42 + strlen($username) + strlen($domain)) . // 26
            "\x00\x00" . // 28
            pack('vvv', strlen($domain), strlen($domain), 42 + strlen($username)) . // 34
            "\x00\x00"  . // 36
            pack('vvv', strlen($username), strlen($username), 42) /* 42 */. $username . $domain . $ntlmresponse;


        $request->getHeaders()->removeHeader($request->getHeaders('Authorization'));
        $request->getHeaders()->addHeaderLine('Authorization', 'NTLM ' . base64_encode($msg));

        ob_start();
        $server = new Tinebase_Server_WebDAV();
        $server->handle($request, $body);
        $result = ob_get_contents();
        ob_end_clean();

        static::assertEquals(Tinebase_Auth_NtlmV2::AUTH_SUCCESS, $server->getNtlmV2()->getLastAuthStatus());
        $this->assertEquals('PD94bWwgdmVyc2lvbj0iMS4wIiBlbm', substr(base64_encode($result),0,30), $result);
    }

    /**
     * test general functionality of Tinebase_Server_WebDAV
     * @group ServerTests
     */
    public function testServerWithAuthorizationHeader()
    {
        $credentials = $this->getTestCredentials();
        
        $hash = base64_encode($credentials['username'] . ':' . $credentials['password']);
        
        $request = Tinebase_Http_Request::fromString(
"PROPFIND /calendars/64d7fdf9202f7b1faf7467f5066d461c2e75cf2b/4/ HTTP/1.1\r\n"
. "Host: localhost\r\n"
. "Depth: 0\r\n"
. "User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7\r\n"
. "Authorization: Basic $hash\r\n"
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

        $this->assertEquals('PD94bWwgdmVyc2lvbj0iMS4wIiBlbm', substr(base64_encode($result),0,30), $result);
    }
    
    /**
     * test general functionality of Tinebase_Server_WebDAV
     * @group ServerTests
     */
    public function testServerWithAuthorizationEnv()
    {
        $credentials = $this->getTestCredentials();
        
        $hash = base64_encode($credentials['username'] . ':' . $credentials['password']);
        
        $request = Tinebase_Http_Request::fromString(<<<EOS
PROPFIND /calendars/64d7fdf9202f7b1faf7467f5066d461c2e75cf2b/4/ HTTP/1.1
Host: localhost
Depth: 0
User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7
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
        
        $request = Tinebase_Http_Request::fromString(<<<EOS
PROPFIND /calendars/64d7fdf9202f7b1faf7467f5066d461c2e75cf2b/4/ HTTP/1.1
Host: localhost
Depth: 0
User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7
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
        $request = Tinebase_Http_Request::fromString(<<<EOS
PROPFIND /principals/users/ HTTP/1.1
Host: localhost
Depth: 0
User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7
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
        
        $request = Tinebase_Http_Request::fromString(
"PROPFIND /principals/users/{$account->contact_id}/ HTTP/1.1\r\n"
. "Host: localhost\r\n"
. "Depth: 0\r\n"
. "User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7\r\n"
. "\r\n"
. "<?xml version=\"1.0\" encoding=\"UTF-8\"?><D:propfind xmlns:D=\"DAV:\" xmlns:C=\"urn:ietf:params:xml:ns:caldav\"><D:prop><C:calendar-home-set/><C:calendar-user-address-set/><C:schedule-inbox-URL/><C:schedule-outbox-URL/></D:prop></D:propfind>\r\n"
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
        if (Tinebase_Core::getUser() === null) {
            Tinebase_Core::set(Tinebase_Core::USER, $account);
        }
        
        $containerId = $this->getPersonalContainer($account, 'Calendar_Model_Event')
            ->getFirstRecord()
            ->getId();
        
        $request = Tinebase_Http_Request::fromString(<<<EOS
REPORT /calendars/{$account->contact_id}/{$containerId}/ HTTP/1.1
Host: localhost
Depth: 1
Content-Type: application/xml; charset="utf-8"
User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7

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

        if (Tinebase_Core::getUser() === null) {
            Tinebase_Core::set(Tinebase_Core::USER, $account);
        }
        
        $containerId = $this->getPersonalContainer($account, 'Calendar_Model_Event')
            ->getFirstRecord()
            ->getId();
        
        $request = Tinebase_Http_Request::fromString(
"PROPFIND /calendars/{$account->contact_id}/{$containerId}/ HTTP/1.1" . "\r\n"
. "Host: localhost" . "\r\n"
. "Depth: 1" . "\r\n"
. 'Content-Type: application/xml; charset="utf-8"' . "\r\n"
. "User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7" . "\r\n"
. "\r\n"
. '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n"
. '<D:propfind xmlns:D="DAV:" xmlns:CS="http://calendarserver.org/ns/" xmlns:C="urn:ietf:params:xml:ns:caldav"><D:prop><D:resourcetype/><D:owner/><D:current-user-principal/><D:supported-report-set/><C:supported-calendar-component-set/><CS:getctag/></D:prop></D:propfind>'
. "\r\n"
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
