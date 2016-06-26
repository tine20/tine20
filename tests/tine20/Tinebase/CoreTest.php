<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2014-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test class for Tinebase_Core
 * 
 * @package     Tinebase
 */
class Tinebase_CoreTest extends TestCase
{
    protected function tearDown()
    {
        parent::tearDown();
        
        Tinebase_Core::set(Tinebase_Core::REQUEST, null);
    }
    
    public function testGetDispatchServerJSON()
    {
        $request = \Zend\Http\PhpEnvironment\Request::fromString(
            "OPTIONS /index.php HTTP/1.1\r\n".
            "Host: localhost\r\n".
            "User-Agent: Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.5; en-US; rv:1.9.1b3pre) Gecko/20081130 Minefield/3.1b3pre\r\n".
            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n".
            "Accept-Language: en-us,en;q=0.5\r\n".
            "Accept-Encoding: gzip,deflate\r\n".
            "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n".
            "Connection: keep-alive\r\n".
            "Origin: http://foo.example\r\n".
            "Access-Control-Request-Method: POST\r\n".
            "Access-Control-Request-Headers: X-PINGOTHER"
        );
        Tinebase_Core::set(Tinebase_Core::REQUEST, $request);
        
        $server = Tinebase_Core::getDispatchServer($request);
        
        $this->assertInstanceOf('Tinebase_Server_Json', $server);
        
        $request = \Zend\Http\PhpEnvironment\Request::fromString(
            "POST /index.php HTTP/1.1\r\n".
            "X-Tine20-Request-Type: JSON\r\n".
            "\r\n".
            '{"jsonrpc":"2.0","method":"Admin.searchUsers","params":{"filter":[{"field":"query","operator":"contains","value":"","id":"ext-record-2"}],"paging":{"sort":"accountLoginName","dir":"ASC","start":0,"limit":50}},"id":37}'
        );
        Tinebase_Core::set(Tinebase_Core::REQUEST, $request);
        
        $server = Tinebase_Core::getDispatchServer($request);
        
        $this->assertInstanceOf('Tinebase_Server_Json', $server);
        
        
        $request = \Zend\Http\PhpEnvironment\Request::fromString(
            "POST /index.php HTTP/1.1\r\n".
            "Content-Type: application/json\r\n".
            "\r\n".
            '{"jsonrpc":"2.0","method":"Admin.searchUsers","params":{"filter":[{"field":"query","operator":"contains","value":"","id":"ext-record-2"}],"paging":{"sort":"accountLoginName","dir":"ASC","start":0,"limit":50}},"id":37}'
        );
        Tinebase_Core::set(Tinebase_Core::REQUEST, $request);
        
        $server = Tinebase_Core::getDispatchServer($request);
        
        $this->assertInstanceOf('Tinebase_Server_Json', $server);
    }
    
    public function testGetDispatchServerSnom()
    {
        $request = \Zend\Http\PhpEnvironment\Request::fromString(
            "POST /index.php HTTP/1.1\r\n".
            "User-Agent: Mozilla/4.0 (compatible; snom300-SIP 8.4.35 1.1.3-u)"
        );
        
        $server = Tinebase_Core::getDispatchServer($request);
        
        $this->assertInstanceOf('Voipmanager_Server_Snom', $server);
    }
    
    public function testGetDispatchServerAsterisk()
    {
        $request = \Zend\Http\PhpEnvironment\Request::fromString(
            "POST /index.php HTTP/1.1\r\n".
            "User-Agent: asterisk-libcurl-agent/1.0"
        );
        
        $server = Tinebase_Core::getDispatchServer($request);
        
        $this->assertInstanceOf('Voipmanager_Server_Asterisk', $server);
    }
    
    public function testGetDispatchServerActiveSync()
    {
        $request = \Zend\Http\PhpEnvironment\Request::fromString(
            "GET /index.php?frontend=activesync HTTP/1.1\r\n".
            "User-Agent: SAMSUNG-GT-I9300/101.403"
        );
        $request->setQuery(new \Zend\Stdlib\Parameters(array('frontend' => 'activesync')));
        
        $server = Tinebase_Core::getDispatchServer($request);
        
        $this->assertInstanceOf('ActiveSync_Server_Http', $server);
    }
    
    public function testGetDispatchServerWebDAV()
    {
        $request = \Zend\Http\PhpEnvironment\Request::fromString(
            "GET /index.php?frontend=webdav HTTP/1.1\r\n".
            "User-Agent: SAMSUNG-GT-I9300/101.403"
        );
        $request->setQuery(new \Zend\Stdlib\Parameters(array('frontend' => 'webdav')));
        
        $server = Tinebase_Core::getDispatchServer($request);
        
        $this->assertInstanceOf('Tinebase_Server_WebDAV', $server);
    }
    
}
