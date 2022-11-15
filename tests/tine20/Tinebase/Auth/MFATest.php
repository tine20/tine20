<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2021-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use OTPHP\TOTP;
use OTPHP\HOTP;
use ParagonIE\ConstantTime\Base32;
use Psr\Http\Message\RequestInterface;

class Tinebase_Auth_MFATest extends TestCase
{
    protected $unsetSharedCredentialKey = false;

    /**
     * @var RequestInterface
     */
    protected $_oldRequest = null;

    protected function setUp(): void
    {
        parent::setUp();

        Tinebase_Auth_MFA::destroyInstances();

        if (empty(Tinebase_Config::getInstance()->{Tinebase_Auth_CredentialCache_Adapter_Shared::CONFIG_KEY})) {
            Tinebase_Config::getInstance()->{Tinebase_Auth_CredentialCache_Adapter_Shared::CONFIG_KEY} = Tinebase_Record_Abstract::generateUID();
            $this->unsetSharedCredentialKey = true;
        }

        $this->_oldRequest = Tinebase_Core::getContainer()->get(RequestInterface::class);
    }

    protected function tearDown(): void
    {
        Tinebase_Core::getContainer()->set(RequestInterface::class, $this->_oldRequest);
        if ($this->unsetSharedCredentialKey) {
            Tinebase_Config::getInstance()->delete(Tinebase_Auth_CredentialCache_Adapter_Shared::CONFIG_KEY);
        }
        parent::tearDown();
        
        Tinebase_Auth_MFA::destroyInstances();
        Tinebase_AreaLock::destroyInstance();
    }

    protected function prepTOTP()
    {
        $secret = Base32::encodeUpperUnpadded(random_bytes(64));

        $this->_originalTestUser->mfa_configs = new Tinebase_Record_RecordSet(
            Tinebase_Model_MFA_UserConfig::class, [[
            Tinebase_Model_MFA_UserConfig::FLD_ID => 'TOTPunittest',
            Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID => 'unittest',
            Tinebase_Model_MFA_UserConfig::FLD_CONFIG_CLASS =>
                Tinebase_Model_MFA_TOTPUserConfig::class,
            Tinebase_Model_MFA_UserConfig::FLD_CONFIG =>
                new Tinebase_Model_MFA_TOTPUserConfig([
                    Tinebase_Model_MFA_TOTPUserConfig::FLD_SECRET => $secret,
                ]),
        ]]);

        $this->_createAreaLockConfig([
            Tinebase_Model_AreaLockConfig::FLD_MFAS => ['unittest'],
        ], [
            Tinebase_Model_MFA_Config::FLD_ID => 'unittest',
            Tinebase_Model_MFA_Config::FLD_USER_CONFIG_CLASS =>
                Tinebase_Model_MFA_TOTPUserConfig::class,
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CONFIG_CLASS =>
                Tinebase_Model_MFA_TOTPConfig::class,
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CLASS =>
                Tinebase_Auth_MFA_HTOTPAdapter::class,
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CONFIG => []
        ]);

        $this->_originalTestUser = Tinebase_User::getInstance()->updateUser($this->_originalTestUser);
        return TOTP::create($secret);
    }

    public function testAuthPAMvalidateTOTP()
    {
        $totp = $this->prepTOTP();
        $pass = $totp->now();
        $credentials = TestServer::getInstance()->getTestCredentials();

        Tinebase_Core::getContainer()->set(RequestInterface::class,
            (new \Laminas\Diactoros\ServerRequest([], [], 'http://unittest/shalala?blub=bla'))
                ->withBody((new \Laminas\Diactoros\StreamFactory())->createStream(json_encode([
                    'user' => $this->_originalTestUser->accountLoginName,
                    'pass' => $credentials['password'] . $pass,
                ]))));

        $response = Tinebase_Controller::getInstance()->publicPostAuthPAMvalidate();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotFalse($body = json_decode($response->getBody()->getContents(), true));
        $this->assertArrayHasKey('login-success', $body);
        $this->assertSame(true, $body['login-success']);
    }

    public function testAuthPAMvalidateTOTPFail()
    {
        $this->prepTOTP();
        $credentials = TestServer::getInstance()->getTestCredentials();

        Tinebase_Core::getContainer()->set(RequestInterface::class,
            (new \Laminas\Diactoros\ServerRequest([], [], 'http://unittest/shalala?blub=bla'))
                ->withBody((new \Laminas\Diactoros\StreamFactory())->createStream(json_encode([
                    'user' => $this->_originalTestUser->accountLoginName,
                    'pass' => $credentials['password'] . '123123',
                ]))));

        $response = Tinebase_Controller::getInstance()->publicPostAuthPAMvalidate();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotFalse($body = json_decode($response->getBody()->getContents(), true));
        $this->assertArrayHasKey('login-success', $body);
        $this->assertSame(false, $body['login-success']);
    }

    public function testTOTP()
    {
        $secret = Base32::encodeUpperUnpadded(random_bytes(64));

        $this->_originalTestUser->mfa_configs = new Tinebase_Record_RecordSet(
            Tinebase_Model_MFA_UserConfig::class, [[
            Tinebase_Model_MFA_UserConfig::FLD_ID => 'TOTPunittest',
            Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID => 'unittest',
            Tinebase_Model_MFA_UserConfig::FLD_CONFIG_CLASS =>
                Tinebase_Model_MFA_TOTPUserConfig::class,
            Tinebase_Model_MFA_UserConfig::FLD_CONFIG =>
                new Tinebase_Model_MFA_TOTPUserConfig([
                    Tinebase_Model_MFA_TOTPUserConfig::FLD_SECRET => $secret,
                ]),
        ]]);

        $this->_createAreaLockConfig([], [
            Tinebase_Model_MFA_Config::FLD_ID => 'unittest',
            Tinebase_Model_MFA_Config::FLD_USER_CONFIG_CLASS =>
                Tinebase_Model_MFA_TOTPUserConfig::class,
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CONFIG_CLASS =>
                Tinebase_Model_MFA_TOTPConfig::class,
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CLASS =>
                Tinebase_Auth_MFA_HTOTPAdapter::class,
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CONFIG => []
        ]);

        $this->_originalTestUser = Tinebase_User::getInstance()->updateUser($this->_originalTestUser);
        $mfa = Tinebase_Auth_MFA::getInstance('unittest');
        $totp = TOTP::create($secret);

        $this->assertFalse($mfa->validate('shaaaaaaaaaalala', $this->_originalTestUser->mfa_configs->getFirstRecord()),
            'validate didn\'t fail as expected');


        $this->assertTrue($mfa->validate(($secret = $totp->at(time())), $this->_originalTestUser->mfa_configs->getFirstRecord()),
                'validate didn\'t succeed');

        $this->_originalTestUser = Tinebase_User::getInstance()->getFullUserById($this->_originalTestUser->getId());
        $this->assertFalse($mfa->validate($secret, $this->_originalTestUser->mfa_configs->getFirstRecord()),
            'replay didn\'t fail');

        $this->assertFalse($mfa->validate($totp->at(time()-240), $this->_originalTestUser->mfa_configs->getFirstRecord()),
            'validate didn\'t fail as expected on out of time range');

        $this->assertFalse($mfa->validate($totp->at(time()+240), $this->_originalTestUser->mfa_configs->getFirstRecord()),
            'validate didn\'t fail as expected on out of time range');
    }

    public function testHOTP()
    {
        $secret = Base32::encodeUpperUnpadded(random_bytes(64));

        $this->_originalTestUser->mfa_configs = new Tinebase_Record_RecordSet(
            Tinebase_Model_MFA_UserConfig::class, [[
            Tinebase_Model_MFA_UserConfig::FLD_ID => 'HOTPunittest',
            Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID => 'unittest',
            Tinebase_Model_MFA_UserConfig::FLD_CONFIG_CLASS =>
                Tinebase_Model_MFA_HOTPUserConfig::class,
            Tinebase_Model_MFA_UserConfig::FLD_CONFIG =>
                new Tinebase_Model_MFA_HOTPUserConfig([
                    Tinebase_Model_MFA_HOTPUserConfig::FLD_COUNTER => 0,
                    Tinebase_Model_MFA_HOTPUserConfig::FLD_SECRET => $secret,
                ]),
        ]]);

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

        $this->_originalTestUser = Tinebase_User::getInstance()->updateUser($this->_originalTestUser);
        $mfa = Tinebase_Auth_MFA::getInstance('unittest');
        $hotp = HOTP::create($secret);

        $this->assertFalse($mfa->validate('shaaaaaaaaaalala', $this->_originalTestUser->mfa_configs->getFirstRecord()),
            'validate didn\'t fail as expected');
        $this->_originalTestUser = Tinebase_User::getInstance()->getUserById($this->_originalTestUser->getId(),
            Tinebase_Model_FullUser::class);
        $this->assertTrue($mfa->validate($hotp->at(0), $this->_originalTestUser->mfa_configs->getFirstRecord()),
            'validate didn\'t succeed');
        $this->_originalTestUser = Tinebase_User::getInstance()->getUserById($this->_originalTestUser->getId(),
            Tinebase_Model_FullUser::class);
        $this->assertFalse($mfa->validate($hotp->at(0), $this->_originalTestUser->mfa_configs->getFirstRecord()),
            'validate didn\'t fail as expected on second call');

        $this->_originalTestUser = Tinebase_User::getInstance()->getUserById($this->_originalTestUser->getId(),
            Tinebase_Model_FullUser::class);
        $this->assertTrue($mfa->validate($hotp->at(1), $this->_originalTestUser->mfa_configs->getFirstRecord()),
            'validate didn\'t succeed');
        $this->_originalTestUser = Tinebase_User::getInstance()->getUserById($this->_originalTestUser->getId(),
            Tinebase_Model_FullUser::class);
        $this->assertFalse($mfa->validate($hotp->at(1), $this->_originalTestUser->mfa_configs->getFirstRecord()),
            'validate didn\'t fail as expected on second call');

        $this->_originalTestUser = Tinebase_User::getInstance()->getUserById($this->_originalTestUser->getId(),
            Tinebase_Model_FullUser::class);
        $this->assertTrue($mfa->validate($hotp->at(5), $this->_originalTestUser->mfa_configs->getFirstRecord()),
            'validate didn\'t succeed');

        $this->_originalTestUser = Tinebase_User::getInstance()->getUserById($this->_originalTestUser->getId(),
            Tinebase_Model_FullUser::class);
        $this->assertFalse($mfa->validate($hotp->at(15), $this->_originalTestUser->mfa_configs->getFirstRecord()),
            'validate didn\'t fail as expected on out of bound call');
        $this->_originalTestUser = Tinebase_User::getInstance()->getUserById($this->_originalTestUser->getId(),
            Tinebase_Model_FullUser::class);
        $this->assertFalse($mfa->validate($hotp->at(5), $this->_originalTestUser->mfa_configs->getFirstRecord()),
            'validate didn\'t fail as expected on out of bound call');
        $this->_originalTestUser = Tinebase_User::getInstance()->getUserById($this->_originalTestUser->getId(),
            Tinebase_Model_FullUser::class);
        $this->assertFalse($mfa->validate($hotp->at(4), $this->_originalTestUser->mfa_configs->getFirstRecord()),
            'validate didn\'t fail as expected on out of bound call');

        $this->_originalTestUser = Tinebase_User::getInstance()->getUserById($this->_originalTestUser->getId(),
            Tinebase_Model_FullUser::class);
        $this->assertTrue($mfa->validate($hotp->at(6), $this->_originalTestUser->mfa_configs->getFirstRecord()),
            'validate didn\'t succeed');
    }

    public function testYubicoAtCreateUser()
    {
        $userTest = new Admin_Frontend_Json_UserTest();
        $ref = new ReflectionProperty(Admin_Frontend_Json_UserTest::class, '_json');
        $ref->setAccessible(true);
        $ref->setValue($userTest, new Admin_Frontend_Json());
        $userData = $userTest->_getUserArrayWithPw();
        $userData['mfa_configs'] = [[
            Tinebase_Model_MFA_UserConfig::FLD_ID => 'yubicoOTPunittest',
            Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID => 'unittest',
            Tinebase_Model_MFA_UserConfig::FLD_CONFIG_CLASS =>
                Tinebase_Model_MFA_YubicoOTPUserConfig::class,
            Tinebase_Model_MFA_UserConfig::FLD_CONFIG =>
                [
                    Tinebase_Model_MFA_YubicoOTPUserConfig::FLD_PUBLIC_ID => 'vvccccdhdtnh',
                    Tinebase_Model_MFA_YubicoOTPUserConfig::FLD_PRIVAT_ID => '1449e1c9cd4c',
                    Tinebase_Model_MFA_YubicoOTPUserConfig::FLD_AES_KEY => '9a9798f480da0193ab7be4e8abc952c2',
                ],
        ]];

        $userData = (new Admin_Frontend_Json())->saveUser($userData);
        $this->assertNotEmpty($userData['mfa_configs'][0][Tinebase_Model_MFA_UserConfig::FLD_CONFIG]
            [Tinebase_Model_MFA_YubicoOTPUserConfig::FLD_CC_ID]);
        $this->assertSame('vvccccdhdtnh', $userData['mfa_configs'][0][Tinebase_Model_MFA_UserConfig::FLD_CONFIG]
            [Tinebase_Model_MFA_YubicoOTPUserConfig::FLD_PUBLIC_ID]);
        $this->assertSame($userData['accountId'], $userData['mfa_configs'][0][Tinebase_Model_MFA_UserConfig::FLD_CONFIG]
            [Tinebase_Model_MFA_YubicoOTPUserConfig::FLD_ACCOUNT_ID]);
        $this->assertCount(3, $userData['mfa_configs'][0][Tinebase_Model_MFA_UserConfig::FLD_CONFIG]);
    }

    public function testYubicoOTP()
    {
        $this->_originalTestUser->mfa_configs = new Tinebase_Record_RecordSet(
            Tinebase_Model_MFA_UserConfig::class, [[
            Tinebase_Model_MFA_UserConfig::FLD_ID => 'yubicoOTPunittest',
            Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID => 'unittest',
            Tinebase_Model_MFA_UserConfig::FLD_CONFIG_CLASS =>
                Tinebase_Model_MFA_YubicoOTPUserConfig::class,
            Tinebase_Model_MFA_UserConfig::FLD_CONFIG =>
                new Tinebase_Model_MFA_YubicoOTPUserConfig([
                    Tinebase_Model_MFA_YubicoOTPUserConfig::FLD_PUBLIC_ID => 'vvccccdhdtnh',
                    Tinebase_Model_MFA_YubicoOTPUserConfig::FLD_PRIVAT_ID => '1449e1c9cd4c',
                    Tinebase_Model_MFA_YubicoOTPUserConfig::FLD_AES_KEY => '9a9798f480da0193ab7be4e8abc952c2',
                ]),
        ]]);

        $this->_createAreaLockConfig([], [
            Tinebase_Model_MFA_Config::FLD_ID => 'unittest',
            Tinebase_Model_MFA_Config::FLD_USER_CONFIG_CLASS =>
                Tinebase_Model_MFA_YubicoOTPUserConfig::class,
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CONFIG_CLASS =>
                Tinebase_Model_MFA_YubicoOTPConfig::class,
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CLASS =>
                Tinebase_Auth_MFA_YubicoOTPAdapter::class,
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CONFIG => []
        ]);

        $this->_originalTestUser = Tinebase_User::getInstance()->updateUser($this->_originalTestUser);
        $mfa = Tinebase_Auth_MFA::getInstance('unittest');

        $this->assertFalse($mfa->validate('shaaaaaaaaaalala', $this->_originalTestUser->mfa_configs->getFirstRecord()),
            'validate didn\'t fail as expected');
        $this->assertTrue($mfa->validate('vvccccdhdtnhleteeguflgbchbgfcbvbclnkknethrfv', $this->_originalTestUser
            ->mfa_configs->getFirstRecord()), 'validate didn\'t succeed');
        $this->_originalTestUser = Tinebase_User::getInstance()->getUserById($this->_originalTestUser->getId(),
            Tinebase_Model_FullUser::class);
        $this->assertFalse($mfa->validate('vvccccdhdtnhleteeguflgbchbgfcbvbclnkknethrfv', $this->_originalTestUser
            ->mfa_configs->getFirstRecord()), 'validate didn\'t fail as expected on second call');

        $this->assertTrue($mfa->validate('vvccccdhdtnhtrbtrhtbvfldecgjevlutenjkgugglfh', $this->_originalTestUser
            ->mfa_configs->getFirstRecord()), 'validate didn\'t succeed');
        $this->_originalTestUser = Tinebase_User::getInstance()->getUserById($this->_originalTestUser->getId(),
            Tinebase_Model_FullUser::class);
        $this->assertFalse($mfa->validate('vvccccdhdtnhleteeguflgbchbgfcbvbclnkknethrfv', $this->_originalTestUser
            ->mfa_configs->getFirstRecord()), 'validate didn\'t fail as expected on second call');
        $this->assertFalse($mfa->validate('vvccccdhdtnhtrbtrhtbvfldecgjevlutenjkgugglfh', $this->_originalTestUser
            ->mfa_configs->getFirstRecord()), 'validate didn\'t succeed');
    }

    public function testSmsAtCreateUser()
    {
        $this->_skipIfLDAPBackend('Zend_Ldap_Exception: 0x44 (Already exists): adding: cn=tine20phpunitgroup,ou=groups,dc=tine,dc=test');

        $userTest = new Admin_Frontend_Json_UserTest();
        $ref = new ReflectionProperty(Admin_Frontend_Json_UserTest::class, '_json');
        $ref->setAccessible(true);
        $ref->setValue($userTest, new Admin_Frontend_Json());
        $userData = $userTest->_getUserArrayWithPw();
        $userData['mfa_configs'] = [[
            Tinebase_Model_MFA_UserConfig::FLD_ID => 'userunittest',
            Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID => 'unittest',
            Tinebase_Model_MFA_UserConfig::FLD_CONFIG_CLASS =>
                Tinebase_Model_MFA_SmsUserConfig::class,
            Tinebase_Model_MFA_UserConfig::FLD_CONFIG => [
                    Tinebase_Model_MFA_SmsUserConfig::FLD_CELLPHONENUMBER => ' 0176 / 1234567890',
                ],
        ]];

        $userData = (new Admin_Frontend_Json())->saveUser($userData);
        $this->assertNotEmpty($userData['mfa_configs'][0][Tinebase_Model_MFA_UserConfig::FLD_CONFIG]
            [Tinebase_Model_MFA_SmsUserConfig::FLD_CELLPHONENUMBER]);
        $this->assertSame('+491761234567890', $userData['mfa_configs'][0][Tinebase_Model_MFA_UserConfig::FLD_CONFIG]
            [Tinebase_Model_MFA_SmsUserConfig::FLD_CELLPHONENUMBER]);
    }

    public function testGenericSmsAdapter()
    {
        $this->_originalTestUser->mfa_configs = new Tinebase_Record_RecordSet(
            Tinebase_Model_MFA_UserConfig::class, [[
            Tinebase_Model_MFA_UserConfig::FLD_ID => 'userunittest',
                Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID => 'unittest',
                Tinebase_Model_MFA_UserConfig::FLD_CONFIG_CLASS =>
                    Tinebase_Model_MFA_SmsUserConfig::class,
                Tinebase_Model_MFA_UserConfig::FLD_CONFIG =>
                    new Tinebase_Model_MFA_SmsUserConfig([
                        Tinebase_Model_MFA_SmsUserConfig::FLD_CELLPHONENUMBER => '1234567890',
                    ]),
            ]]);

        $this->_createAreaLockConfig([], [
            Tinebase_Model_MFA_Config::FLD_ID => 'unittest',
            Tinebase_Model_MFA_Config::FLD_USER_CONFIG_CLASS =>
                Tinebase_Model_MFA_SmsUserConfig::class,
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CONFIG_CLASS =>
                Tinebase_Model_MFA_GenericSmsConfig::class,
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CLASS =>
                Tinebase_Auth_MFA_GenericSmsAdapter::class,
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CONFIG => [
                Tinebase_Model_MFA_GenericSmsConfig::FLD_URL => 'https://shoo.tld/restapi/message',
                Tinebase_Model_MFA_GenericSmsConfig::FLD_BODY => '{"encoding":"auto","body":"{{ message }}","originator":"{{ app.branding.title }}","recipients":["{{ cellphonenumber }}"],"route":"2345"}',
                Tinebase_Model_MFA_GenericSmsConfig::FLD_METHOD => 'POST',
                Tinebase_Model_MFA_GenericSmsConfig::FLD_HEADERS => [
                    'Auth-Bearer' => 'unittesttokenshaaaaalalala'
                ],
                Tinebase_Model_MFA_GenericSmsConfig::FLD_PIN_TTL => 600,
                Tinebase_Model_MFA_GenericSmsConfig::FLD_PIN_LENGTH => 6,
            ]
        ]);

        $mfa = Tinebase_Auth_MFA::getInstance('unittest');
        $mfa->getAdapter()->setHttpClientConfig([
            'adapter' => ($httpClientTestAdapter = new Tinebase_ZendHttpClientAdapter())
        ]);
        $httpClientTestAdapter->setResponse(new Zend_Http_Response(200, []));

        $this->assertTrue($mfa->sendOut($this->_originalTestUser->mfa_configs->getFirstRecord()),
            'sendOut didn\'t succeed');
        $sessionData = Tinebase_Session::getSessionNamespace()->{Tinebase_Auth_MFA_GenericSmsAdapter::class};
        $this->assertIsArray($sessionData, 'session data not set properly');
        $this->assertArrayHasKey('ttl', $sessionData, 'session data not set properly');
        $this->assertArrayHasKey('pin', $sessionData, 'session data not set properly');
        $this->assertStringContainsString('"body":"' . $sessionData['pin'] . ' is your ',
            $httpClientTestAdapter->lastRequestBody);
        $this->assertStringContainsString('"recipients":["+491234567890"],"route":"2345"',
            $httpClientTestAdapter->lastRequestBody);

        $this->assertFalse($mfa->validate('shaaaaaaaaaalala', $this->_originalTestUser->mfa_configs->getFirstRecord()),
            'validate didn\'t fail as expected');
        $this->assertTrue($mfa->validate($sessionData['pin'], $this->_originalTestUser->mfa_configs->getFirstRecord()),
            'validate didn\'t succeed');
        $this->assertFalse($mfa->validate($sessionData['pin'], $this->_originalTestUser->mfa_configs->getFirstRecord()),
            'validate didn\'t fail as expected on second call');
    }
}
