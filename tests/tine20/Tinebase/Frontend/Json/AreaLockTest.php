<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  AreaLock
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */
class Tinebase_Frontend_Json_AreaLockTest extends TestCase
{
    /**
     * unit under test (UIT)
     * @var Tinebase_Frontend_Json
     */
    protected $_instance;

    /**
     * set up tests
     *
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->_instance = new Tinebase_Frontend_Json();
    }

    /**
     * checks if confidential provider config isn't sent to clients
     */
    public function testAreaLockProviderConfigRemovedFromRegistryData()
    {
        $this->_createAreaLockConfig([Tinebase_Model_AreaLockConfig::FLD_AREAS => ['foo']]);

        $this->assertSame(['pin'], Tinebase_Config::getInstance()->{Tinebase_Config::AREA_LOCKS}->records
            ->getFirstRecord()->{Tinebase_Model_AreaLockConfig::FLD_MFAS});

        $registryData = $this->_instance->getAllRegistryData();
        $registryConfigValue = $registryData['Tinebase']['config'][Tinebase_Config::AREA_LOCKS]['value'];
        self::assertTrue(isset($registryConfigValue['records'][0]));
        self::assertFalse(isset($registryConfigValue['records'][0]['provider_config']),
            'confidental data should be removed: ' . print_r($registryConfigValue, true));

        $this->assertSame(['pin'], Tinebase_Config::getInstance()->{Tinebase_Config::AREA_LOCKS}->records
            ->getFirstRecord()->{Tinebase_Model_AreaLockConfig::FLD_MFAS});
    }

    public function testAreaLockLoginExceptionInRegistryData()
    {
        $this->_createAreaLockConfig();

        $this->_setPin();

        $registryData = $this->_instance->getAllRegistryData();
        $registryException = $registryData['Tinebase']['areaLockedException'];
        $this->assertSame(630, $registryException['code']);
        $this->assertSame('login', $registryException['area']);
        $this->assertSame([[
            'id' => 'userpin',
            'mfa_config_id' => 'pin',
            'config_class' => Tinebase_Model_MFA_PinUserConfig::class,
            'config' => []
        ]], $registryException['mfaUserConfigs']);
    }

    public function testGetPossibleMFAs()
    {
        $this->_createAreaLockConfig();

        $result = (new Tinebase_Frontend_Json_AreaLock())->getSelfServiceableMFAs();
        $this->assertCount(0, $result);
    }

    public function testGetPossibleMFAs1()
    {
        $this->_createAreaLockConfig([], [Tinebase_Model_MFA_Config::FLD_ALLOW_SELF_SERVICE => true]);

        $result = (new Tinebase_Frontend_Json_AreaLock())->getSelfServiceableMFAs();
        $this->assertCount(1, $result);
        $this->assertSame('pin', $result[0]['mfa_config_id']);
        $this->assertSame(Tinebase_Model_MFA_PinUserConfig::class, $result[0]['config_class']);
    }

    public function testSaveTwoMFAUserConfigTypePin()
    {
        $this->_createAreaLockConfig([], [Tinebase_Model_MFA_Config::FLD_ALLOW_SELF_SERVICE => true]);
        $areaLockFE = new Tinebase_Frontend_Json_AreaLock();

        $userCfg = [
            Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID => 'pin',
            Tinebase_Model_MFA_UserConfig::FLD_CONFIG_CLASS => Tinebase_Model_MFA_PinUserConfig::class,
            Tinebase_Model_MFA_UserConfig::FLD_CONFIG => [
                Tinebase_Model_MFA_PinUserConfig::FLD_PIN => '123456',
            ],
        ];

        $this->assertTrue($areaLockFE->saveMFAUserConfig('pin', $userCfg, '123456'));

        $user = Tinebase_User::getInstance()->getFullUserById(Tinebase_Core::getUser()->getId());
        $pinCfg = $user->mfa_configs->find(Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID, 'pin');
        $this->assertTrue(Tinebase_Auth_MFA::getInstance('pin')->validate('123456', $pinCfg));
        Tinebase_Core::setUser($this->_personas['sclever']);
        Tinebase_Core::setUser($user);

        $userCfg = [
            Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID => 'pin',
            Tinebase_Model_MFA_UserConfig::FLD_CONFIG_CLASS => Tinebase_Model_MFA_PinUserConfig::class,
            Tinebase_Model_MFA_UserConfig::FLD_CONFIG => [
                Tinebase_Model_MFA_PinUserConfig::FLD_PIN => '776655',
            ],
        ];
        $this->assertTrue($areaLockFE->saveMFAUserConfig('pin', $userCfg, '776655'));

        $user = Tinebase_User::getInstance()->getFullUserById(Tinebase_Core::getUser()->getId());
        $this->assertSame(2, $user->mfa_configs->filter(Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID, 'pin')->count());

        // to force cleanup
        Tinebase_Core::setUser($this->_personas['sclever']);
    }

    public function testUpdateMFAUserconfigMetaData()
    {
        $this->_createAreaLockConfig([], [Tinebase_Model_MFA_Config::FLD_ALLOW_SELF_SERVICE => true]);
        $areaLockFE = new Tinebase_Frontend_Json_AreaLock();

        $userCfg = [
            Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID => 'pin',
            Tinebase_Model_MFA_UserConfig::FLD_CONFIG_CLASS => Tinebase_Model_MFA_PinUserConfig::class,
            Tinebase_Model_MFA_UserConfig::FLD_CONFIG => [
                Tinebase_Model_MFA_PinUserConfig::FLD_PIN => '123456',
            ],
        ];

        $this->assertTrue($areaLockFE->saveMFAUserConfig('pin', $userCfg, '123456'));

        $user = Tinebase_User::getInstance()->getFullUserById(Tinebase_Core::getUser()->getId());
        $pinCfg = $user->mfa_configs->find(Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID, 'pin');
        $this->assertTrue(Tinebase_Auth_MFA::getInstance('pin')->validate('123456', $pinCfg));
        $this->assertEmpty($pinCfg->{Tinebase_Model_MFA_UserConfig::FLD_NOTE});
        Tinebase_Core::setUser($this->_personas['sclever']);
        Tinebase_Core::setUser($user);

        $this->assertTrue($areaLockFE->updateMFAUserConfigMetaData([
            'id' => $pinCfg->getId(),
            Tinebase_Model_MFA_UserConfig::FLD_NOTE => 'test',
        ]));

        $user = Tinebase_User::getInstance()->getFullUserById(Tinebase_Core::getUser()->getId());
        $pinCfg = $user->mfa_configs->find(Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID, 'pin');
        $this->assertTrue(Tinebase_Auth_MFA::getInstance('pin')->validate('123456', $pinCfg));
        $this->assertSame('test', $pinCfg->{Tinebase_Model_MFA_UserConfig::FLD_NOTE});

        // force cleanup
        Tinebase_Core::setUser($this->_personas['sclever']);
    }

    public function testSaveMFAUserConfig()
    {
        $this->_createAreaLockConfig([], [Tinebase_Model_MFA_Config::FLD_ALLOW_SELF_SERVICE => true]);
        $areaLockFE = new Tinebase_Frontend_Json_AreaLock();

        $userCfg = [
            Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID => 'pin',
            Tinebase_Model_MFA_UserConfig::FLD_CONFIG_CLASS => Tinebase_Model_MFA_PinUserConfig::class,
            Tinebase_Model_MFA_UserConfig::FLD_CONFIG => [
                Tinebase_Model_MFA_PinUserConfig::FLD_PIN => '123456',
            ],
        ];

        try {
            $areaLockFE->saveMFAUserConfig('pin', $userCfg);
            $this->fail(Tinebase_Exception_AreaLocked::class . ' expected');
        } catch (Tinebase_Exception_AreaLocked $e) {}

        try {
            $areaLockFE->saveMFAUserConfig('pin', $userCfg, 'asdfas');
            $this->fail(Tinebase_Exception_AreaUnlockFailed::class . ' expected');
        } catch (Tinebase_Exception_AreaUnlockFailed $e) {}

        $this->assertTrue($areaLockFE->saveMFAUserConfig('pin', $userCfg, '123456'));

        $user = Tinebase_User::getInstance()->getFullUserById(Tinebase_Core::getUser()->getId());
        $pinCfg = clone ($user->mfa_configs->find(Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID, 'pin'));

        $pinCfg->{Tinebase_Model_MFA_UserConfig::FLD_CONFIG}->{Tinebase_Model_MFA_PinUserConfig::FLD_PIN} = '7890';
        $pinCfg = $pinCfg->toArray();
        try {
            $areaLockFE->saveMFAUserConfig('pin', $pinCfg);
            $this->fail(Tinebase_Exception_AreaLocked::class . ' expected');
        } catch (Tinebase_Exception_AreaLocked $e) {}

        try {
            $areaLockFE->saveMFAUserConfig('pin', $pinCfg, 'asdfas');
            $this->fail(Tinebase_Exception_AreaUnlockFailed::class . ' expected');
        } catch (Tinebase_Exception_AreaUnlockFailed $e) {}

        $this->assertTrue($areaLockFE->saveMFAUserConfig('pin', $pinCfg, '7890'));
        $user = Tinebase_User::getInstance()->getFullUserById(Tinebase_Core::getUser()->getId());
        $pinCfg = $user->mfa_configs->find(Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID, 'pin');
        $this->assertTrue(Tinebase_Auth_MFA::getInstance('pin')->validate('7890', $pinCfg));

        Tinebase_Core::setUser($this->_personas['sclever']);
        Tinebase_Core::setUser($user);

        $this->assertSame([], $areaLockFE->deleteMFAUserConfigs(['foo']));
        $user = Tinebase_User::getInstance()->getFullUserById(Tinebase_Core::getUser()->getId());
        $pinCfg = $user->mfa_configs->find(Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID, 'pin');
        $this->assertNotNull($pinCfg);

        $this->assertSame([$pinCfg->getId()], $areaLockFE->deleteMFAUserConfigs([$pinCfg->getId()]));
        $user = Tinebase_User::getInstance()->getFullUserById(Tinebase_Core::getUser()->getId());
        $this->assertNull($user->mfa_configs->find(Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID, 'pin'));

        Tinebase_Core::setUser($this->_personas['sclever']);
        Tinebase_Core::setUser($user);
    }
}
