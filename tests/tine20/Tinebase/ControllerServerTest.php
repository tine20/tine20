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
        $request = $this->_getTestRequest();
        
        $credentials = $this->getTestCredentials();
        
        $result = Tinebase_Controller::getInstance()->login($credentials['username'], $credentials['password'], $request);
        
        $this->assertTrue($result);
    }

    /**
     * @group ServerTests
     */
    public function testValidEmailLogin()
    {
        $oldAuthByEmail = Tinebase_Config::getInstance()->{Tinebase_Config::AUTHENTICATION_BY_EMAIL};
        $oldSplit = Tinebase_Config::getInstance()->{Tinebase_Config::AUTHENTICATIONBACKEND}->tryUsernameSplit;
        try {
            Tinebase_Config::getInstance()->{Tinebase_Config::AUTHENTICATIONBACKEND}->tryUsernameSplit = false;
            Tinebase_Config::getInstance()->{Tinebase_Config::AUTHENTICATION_BY_EMAIL} = true;
            $request = $this->_getTestRequest();
            $credentials = $this->getTestCredentials();
            $account = $this->getAccountByName($credentials['username']);

            $result = Tinebase_Controller::getInstance()->login($account->accountEmailAddress, $credentials['password'],
                $request);

            static::assertTrue($result);
        } finally {
            Tinebase_Config::getInstance()->{Tinebase_Config::AUTHENTICATION_BY_EMAIL} = $oldAuthByEmail;
            Tinebase_Config::getInstance()->{Tinebase_Config::AUTHENTICATIONBACKEND}->tryUsernameSplit = $oldSplit;
        }
    }

    /**
     * @group ServerTests
     */
    public function testInvalidEmailLogin()
    {
        $oldAuthByEmail = Tinebase_Config::getInstance()->{Tinebase_Config::AUTHENTICATION_BY_EMAIL};
        $oldSplit = Tinebase_Config::getInstance()->{Tinebase_Config::AUTHENTICATIONBACKEND}->tryUsernameSplit;
        try {
            Tinebase_Config::getInstance()->{Tinebase_Config::AUTHENTICATIONBACKEND}->tryUsernameSplit = false;
            Tinebase_Config::getInstance()->{Tinebase_Config::AUTHENTICATION_BY_EMAIL} = false;
            $request = $this->_getTestRequest();
            $credentials = $this->getTestCredentials();
            $account = $this->getAccountByName($credentials['username']);

            $result = Tinebase_Controller::getInstance()->login($account->accountEmailAddress, $credentials['password'],
                $request);

            static::assertFalse($result);
        } finally {
            Tinebase_Config::getInstance()->{Tinebase_Config::AUTHENTICATION_BY_EMAIL} = $oldAuthByEmail;
            Tinebase_Config::getInstance()->{Tinebase_Config::AUTHENTICATIONBACKEND}->tryUsernameSplit = $oldSplit;
        }
    }

    /**
     * @return Tinebase_Http_Request
     */
    protected function _getTestRequest()
    {
        return Tinebase_Http_Request::fromString(<<<EOS
POST /index.php HTTP/1.1
Content-Type: application/json
Content-Length: 122
Host: 192.168.122.158
Connection: keep-alive
Origin: http://192.168.1\22.158
X-Tine20-Request-Type: JSON
X-Tine20-Jsonkey: undefined
User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.101 Safari/537.36
X-Tine20-Transactionid: 9c7129898e9f8ab7e4621fddf7077a1eaa425aac
X-Requested-With: XMLHttpRequest
Accept: */*
Referer: http://192.168.122.158/tine20dev/
Accept-Encoding: gzip,deflate
Accept-Language: de-DE,de;q=0.8,en-GB;q=0.6,en;q=0.4
EOS
        );
    }

    /**
     * @group ServerTests
     */
    public function testLoginViaTrustedProxy()
    {
        $proxyIp = '192.168.122.1';
        $realClientIp = '192.168.122.25';
        $_SERVER['REMOTE_ADDR'] = $proxyIp;
        $_SERVER['HTTP_X_FORWARDED_FOR'] = $realClientIp;
        Tinebase_Config::getInstance()->set(Tinebase_Config::TRUSTED_PROXIES, [$proxyIp]);

        $request = $this->_getTestRequest();

        $credentials = $this->getTestCredentials();
        $authResult = Tinebase_Auth::getInstance()->authenticate($credentials['username'], $credentials['password']);
        $accessLog = Tinebase_AccessLog::getInstance()->getAccessLogEntry($credentials['username'], $authResult, $request,
            'unittest');

        self::assertEquals($realClientIp, $accessLog->ip, 'proxy ip in access log: ' . print_r($accessLog->toArray(), true));
    }

    /**
     * @group ServerTests
     */
    public function testInvalidLogin()
    {
        $request = $this->_getTestRequest();


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

        $request = $this->_getTestRequest();

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
