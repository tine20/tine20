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
 * Test class for Tinebase_Controller
 * 
 * @package     Tinebase
 */
class Tinebase_ControllerServerTest extends ServerTestCase
{
    /**
     * @group ServerTests
     */
    public function testValidLogin()
    {
        Zend_Session::$_unitTestEnabled = true;
        
        $request = \Zend\Http\PhpEnvironment\Request::fromString(<<<EOS
POST /index.php HTTP/1.1\r
Content-Type: application/json\r
Content-Length: 122\r
Host: 192.168.122.158\r
Connection: keep-alive\r
Origin: http://192.168.1\22.158\r
X-Tine20-Request-Type: JSON\r
X-Tine20-Jsonkey: undefined\r
User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.101 Safari/537.36\r
X-Tine20-Transactionid: 9c7129898e9f8ab7e4621fddf7077a1eaa425aac\r
X-Requested-With: XMLHttpRequest\r
Accept: */*\r
Referer: http://192.168.122.158/tine20dev/\r
Accept-Encoding: gzip,deflate\r
Accept-Language: de-DE,de;q=0.8,en-GB;q=0.6,en;q=0.4\r
EOS
        );
        
        $credentials = $this->getTestCredentials();
        
        $result = Tinebase_Controller::getInstance()->login($credentials['username'], $credentials['password'], $request);
        
        $this->assertTrue($result);
    }
    
    /**
     * @group ServerTests
     */
    public function testInvalidLogin()
    {
        Zend_Session::$_unitTestEnabled = true;
        
        $request = \Zend\Http\PhpEnvironment\Request::fromString(<<<EOS
POST /index.php HTTP/1.1\r
Content-Type: application/json\r
Content-Length: 122\r
Host: 192.168.122.158\r
Connection: keep-alive\r
Origin: http://192.168.1\22.158\r
X-Tine20-Request-Type: JSON\r
X-Tine20-Jsonkey: undefined\r
User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.101 Safari/537.36\r
X-Tine20-Transactionid: 9c7129898e9f8ab7e4621fddf7077a1eaa425aac\r
X-Requested-With: XMLHttpRequest\r
Accept: */*\r
Referer: http://192.168.122.158/tine20dev/\r
Accept-Encoding: gzip,deflate\r
Accept-Language: de-DE,de;q=0.8,en-GB;q=0.6,en;q=0.4\r
EOS
        );
        
        $credentials = $this->getTestCredentials();
        
        $result = Tinebase_Controller::getInstance()->login($credentials['username'], 'foobar', $request);
        
        $this->assertFalse($result);
    }
    
    /**
     * @group ServerTests
     */
    public function testAccountBlocking()
    {
        Zend_Session::$_unitTestEnabled = true;
        
        $request = \Zend\Http\PhpEnvironment\Request::fromString(<<<EOS
POST /index.php HTTP/1.1\r
Content-Type: application/json\r
Content-Length: 122\r
Host: 192.168.122.158\r
Connection: keep-alive\r
Origin: http://192.168.1\22.158\r
X-Tine20-Request-Type: JSON\r
X-Tine20-Jsonkey: undefined\r
User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.101 Safari/537.36\r
X-Tine20-Transactionid: 9c7129898e9f8ab7e4621fddf7077a1eaa425aac\r
X-Requested-With: XMLHttpRequest\r
Accept: */*\r
Referer: http://192.168.122.158/tine20dev/\r
Accept-Encoding: gzip,deflate\r
Accept-Language: de-DE,de;q=0.8,en-GB;q=0.6,en;q=0.4\r
EOS
        );
        
        $credentials = $this->getTestCredentials();
        
        $maxLoginFailures = Tinebase_Config::getInstance()->get(Tinebase_Config::MAX_LOGIN_FAILURES, 5);
        
        for ($i=0; $i<=$maxLoginFailures; $i++) {
            $result = Tinebase_Controller::getInstance()->login($credentials['username'], 'foobar', $request);
            
            $this->assertFalse($result);
        }
        
        // account must be blocked now
        $result = Tinebase_Controller::getInstance()->login($credentials['username'], $credentials['password'], $request);
        
        $this->assertFalse($result);
    }
}
