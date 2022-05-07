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
        $this->assertCount(1, $result);
        $this->assertSame('pin', $result[0]['mfa_config_id']);
        $this->assertSame(Tinebase_Model_MFA_PinUserConfig::class, $result[0]['config_class']);
    }

    public function testSaveMFAUserConfig()
    {
        $this->_createAreaLockConfig();
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
    }
}
