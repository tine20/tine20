<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2014-2015 Metaways Infosystems GmbH (http://www.metaways.de)
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
     *
     * @see 0011440: rework login failure handling
     */
    public function testAccountBlocking()
    {
        // NOTE: end transaction here as NOW() returns the start of the current transaction in pgsql
        //  and is used in user status statement (think about using statement_timestamp() instead of NOW() with pgsql)
        Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
        $this->_transactionId = null;

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
        
        for ($i=0; $i <= 3; $i++) {
            $result = Tinebase_Controller::getInstance()->login($credentials['username'], 'foobar', $request);
            $this->assertFalse($result);
        }
        
        $result = Tinebase_Controller::getInstance()->login($credentials['username'], $credentials['password'], $request);
        $this->assertFalse($result, 'account must be blocked now');

        // wait for some time (2^4 = 16 +1 seconds)
        $timeToWait = 17;

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Waiting for ' . $timeToWait . ' seconds...');

        sleep($timeToWait);

        $result = Tinebase_Controller::getInstance()->login($credentials['username'], $credentials['password'], $request);
        $this->assertTrue($result, 'account should be unblocked now');
    }
}
