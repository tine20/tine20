<?php declare(strict_types=1);

/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use \OTPHP\HOTP;
use \ParagonIE\ConstantTime\Base32;
use \Psr\Http\Message\RequestInterface;

class Admin_Frontend_Json_OVpnApiTest extends TestCase
{
    /**
     * @var RequestInterface
     */
    protected $_oldRequest = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->_oldRequest = Tinebase_Core::getContainer()->get(RequestInterface::class);
    }

    public function tearDown(): void
    {
        Tinebase_Core::getContainer()->set(RequestInterface::class, $this->_oldRequest);
        Tinebase_Auth_MFA::destroyInstances();

        parent::tearDown();
    }

    protected function prepareAreaLockAndRealm(): array
    {
        $this->_createAreaLockConfig([], [
            Tinebase_Model_MFA_Config::FLD_ID => 'unittest',
            Tinebase_Model_MFA_Config::FLD_USER_CONFIG_CLASS =>
                Tinebase_Model_MFA_HOTPUserConfig::class,
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CONFIG_CLASS =>
                Tinebase_Model_MFA_HOTPConfig::class,
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CLASS =>
                Tinebase_Auth_MFA_HTOTPAdapter::class,
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CONFIG => []
        ]);

        return (new Admin_Frontend_Json())->saveOVpnApiRealm([
            Admin_Model_OVpnApiRealm::FLD_ISSUER => 'tine20 unittest',
            Admin_Model_OVpnApiRealm::FLD_NAME   => 'unittest realm',
            Admin_Model_OVpnApiRealm::FLD_KEY    => 'unittest',
        ]);
    }

    public function testHotpWithoutPin()
    {
        $jsonFe = new Admin_Frontend_Json();
        $realm = $this->prepareAreaLockAndRealm();

        $secret = Base32::encodeUpperUnpadded(random_bytes(64));
        $hotp = HOTP::create($secret);

        $account = $jsonFe->saveOVpnApiAccount([
            Admin_Model_OVpnApiAccount::FLD_NAME            => 'unittest account',
            Admin_Model_OVpnApiAccount::FLD_REALM           => $realm['id'],
            Admin_Model_OVpnApiAccount::FLD_AUTH_CONFIGS    => [
                [
                    'id' => Tinebase_Record_Abstract::generateUID(),
                    Admin_Model_OVpnApi_AuthConfig::FLD_MFA_CONFIG_ID   => 'unittest',
                    Admin_Model_OVpnApi_AuthConfig::FLD_CONFIG_CLASS    => Tinebase_Model_MFA_HOTPUserConfig::class,
                    Admin_Model_OVpnApi_AuthConfig::FLD_CONFIG          => [
                        Tinebase_Model_MFA_HOTPUserConfig::FLD_SECRET       => $secret,
                    ],
                ],
            ],
        ]);

        Admin_Config::getInstance()->{Admin_Config::OVPN_API}->{Admin_Config::OVPN_API_KEY} = 'unittest';
        $pass = $hotp->at(0);

        Tinebase_Core::getContainer()->set(RequestInterface::class,
            (new \Laminas\Diactoros\ServerRequest([], [], 'http://unittest/shalala?blub=bla'))
                ->withBody((new \Laminas\Diactoros\StreamFactory())->createStream(json_encode([
                    'user' => $account[Admin_Model_OVpnApiAccount::FLD_NAME],
                    'realm' => $realm[Admin_Model_OVpnApiRealm::FLD_KEY],
                    'pass' => $pass,
                ]))));

        $response = Admin_Controller::getInstance()->publicPostOVpnApi('unittest');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotFalse($body = json_decode($response->getBody()->getContents(), true));
        $this->assertArrayHasKey('result', $body);
        $this->assertArrayHasKey('status', $body['result']);
        $this->assertArrayHasKey('value', $body['result']['status']);
        $this->assertSame(1, $body['result']['status']['value']);
    }

    public function testHotpWithoutPinWrong()
    {
        $jsonFe = new Admin_Frontend_Json();
        $realm = $this->prepareAreaLockAndRealm();

        $secret = Base32::encodeUpperUnpadded(random_bytes(64));
        $hotp = HOTP::create($secret);

        $account = $jsonFe->saveOVpnApiAccount([
            Admin_Model_OVpnApiAccount::FLD_NAME            => 'unittest account',
            Admin_Model_OVpnApiAccount::FLD_REALM           => $realm['id'],
            Admin_Model_OVpnApiAccount::FLD_AUTH_CONFIGS    => [
                [
                    'id' => Tinebase_Record_Abstract::generateUID(),
                    Admin_Model_OVpnApi_AuthConfig::FLD_MFA_CONFIG_ID   => 'unittest',
                    Admin_Model_OVpnApi_AuthConfig::FLD_CONFIG_CLASS    => Tinebase_Model_MFA_HOTPUserConfig::class,
                    Admin_Model_OVpnApi_AuthConfig::FLD_CONFIG          => [
                        Tinebase_Model_MFA_HOTPUserConfig::FLD_SECRET       => $secret,
                    ],
                ],
            ],
        ]);

        Admin_Config::getInstance()->{Admin_Config::OVPN_API}->{Admin_Config::OVPN_API_KEY} = 'unittest';
        $pass = 'wrong';

        Tinebase_Core::getContainer()->set(RequestInterface::class,
            (new \Laminas\Diactoros\ServerRequest([], [], 'http://unittest/shalala?blub=bla'))
                ->withBody((new \Laminas\Diactoros\StreamFactory())->createStream(json_encode([
                    'user' => $account[Admin_Model_OVpnApiAccount::FLD_NAME],
                    'realm' => $realm[Admin_Model_OVpnApiRealm::FLD_KEY],
                    'pass' => $pass,
                ]))));

        $response = Admin_Controller::getInstance()->publicPostOVpnApi('unittest');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotFalse($body = json_decode($response->getBody()->getContents(), true));
        $this->assertArrayHasKey('result', $body);
        $this->assertArrayHasKey('status', $body['result']);
        $this->assertArrayHasKey('value', $body['result']['status']);
        $this->assertSame(0, $body['result']['status']['value']);
    }

    public function testHotpWithPin()
    {
        $jsonFe = new Admin_Frontend_Json();
        $realm = $this->prepareAreaLockAndRealm();

        $secret = Base32::encodeUpperUnpadded(random_bytes(64));
        $hotp = HOTP::create($secret);

        $account = $jsonFe->saveOVpnApiAccount([
            Admin_Model_OVpnApiAccount::FLD_NAME            => 'unittest account',
            Admin_Model_OVpnApiAccount::FLD_REALM           => $realm['id'],
            Admin_Model_OVpnApiAccount::FLD_PIN             => '1a',
            Admin_Model_OVpnApiAccount::FLD_AUTH_CONFIGS    => [
                [
                    'id' => Tinebase_Record_Abstract::generateUID(),
                    Admin_Model_OVpnApi_AuthConfig::FLD_MFA_CONFIG_ID   => 'unittest',
                    Admin_Model_OVpnApi_AuthConfig::FLD_CONFIG_CLASS    => Tinebase_Model_MFA_HOTPUserConfig::class,
                    Admin_Model_OVpnApi_AuthConfig::FLD_CONFIG          => [
                        Tinebase_Model_MFA_HOTPUserConfig::FLD_SECRET       => $secret,
                    ],
                ],
            ],
        ]);

        Admin_Config::getInstance()->{Admin_Config::OVPN_API}->{Admin_Config::OVPN_API_KEY} = 'unittest';
        $pass = $hotp->at(0);

        Tinebase_Core::getContainer()->set(RequestInterface::class,
            (new \Laminas\Diactoros\ServerRequest([], [], 'http://unittest/shalala?blub=bla'))
                ->withBody((new \Laminas\Diactoros\StreamFactory())->createStream(json_encode([
                    'user' => $account[Admin_Model_OVpnApiAccount::FLD_NAME],
                    'realm' => $realm[Admin_Model_OVpnApiRealm::FLD_KEY],
                    'pass' => '1a' . $pass,
                ]))));

        $response = Admin_Controller::getInstance()->publicPostOVpnApi('unittest');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotFalse($body = json_decode($response->getBody()->getContents(), true));
        $this->assertArrayHasKey('result', $body);
        $this->assertArrayHasKey('status', $body['result']);
        $this->assertArrayHasKey('value', $body['result']['status']);
        $this->assertSame(1, $body['result']['status']['value']);
    }

    public function testHotpWithPinWrong()
    {
        $jsonFe = new Admin_Frontend_Json();
        $realm = $this->prepareAreaLockAndRealm();

        $secret = Base32::encodeUpperUnpadded(random_bytes(64));
        $hotp = HOTP::create($secret);

        $account = $jsonFe->saveOVpnApiAccount([
            Admin_Model_OVpnApiAccount::FLD_NAME            => 'unittest account',
            Admin_Model_OVpnApiAccount::FLD_REALM           => $realm['id'],
            Admin_Model_OVpnApiAccount::FLD_PIN             => '1a',
            Admin_Model_OVpnApiAccount::FLD_AUTH_CONFIGS    => [
                [
                    'id' => Tinebase_Record_Abstract::generateUID(),
                    Admin_Model_OVpnApi_AuthConfig::FLD_MFA_CONFIG_ID   => 'unittest',
                    Admin_Model_OVpnApi_AuthConfig::FLD_CONFIG_CLASS    => Tinebase_Model_MFA_HOTPUserConfig::class,
                    Admin_Model_OVpnApi_AuthConfig::FLD_CONFIG          => [
                        Tinebase_Model_MFA_HOTPUserConfig::FLD_SECRET       => $secret,
                    ],
                ],
            ],
        ]);

        Admin_Config::getInstance()->{Admin_Config::OVPN_API}->{Admin_Config::OVPN_API_KEY} = 'unittest';
        $pass = $hotp->at(0);

        Tinebase_Core::getContainer()->set(RequestInterface::class,
            (new \Laminas\Diactoros\ServerRequest([], [], 'http://unittest/shalala?blub=bla'))
                ->withBody((new \Laminas\Diactoros\StreamFactory())->createStream(json_encode([
                    'user' => $account[Admin_Model_OVpnApiAccount::FLD_NAME],
                    'realm' => $realm[Admin_Model_OVpnApiRealm::FLD_KEY],
                    'pass' => 'wrong' . $pass,
                ]))));

        $response = Admin_Controller::getInstance()->publicPostOVpnApi('unittest');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotFalse($body = json_decode($response->getBody()->getContents(), true));
        $this->assertArrayHasKey('result', $body);
        $this->assertArrayHasKey('status', $body['result']);
        $this->assertArrayHasKey('value', $body['result']['status']);
        $this->assertSame(0, $body['result']['status']['value']);
    }
}