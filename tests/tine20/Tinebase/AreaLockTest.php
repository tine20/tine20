<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Tinebase_AreaLock
 */
class Tinebase_AreaLockTest extends TestCase
{
    /**
     * unit in test
     *
     * @var Tinebase_AreaLock
     */
    protected $_uit = null;

    /**
     * @var string
     */
    protected $_pin = '1234';

    /**
     * set up tests
     */
    protected function setUp()
    {
        parent::setUp();
        $this->_uit = Tinebase_AreaLock::getInstance();
    }

    /**
     * @param array $config
     */
    protected function _createLockConfig($config = [])
    {
        // @todo add more providers (in separate test classes?)

        $config = array_merge([
            'area' => Tinebase_Model_AreaLockConfig::AREA_LOGIN,
            'provider' => Tinebase_Auth::PIN,
            'validity' => Tinebase_Model_AreaLockConfig::VALIDITY_SESSION,
        ], $config);
        $locks = new Tinebase_Config_KeyField([
            'records' => new Tinebase_Record_RecordSet('Tinebase_Model_AreaLockConfig', [$config])
        ]);
        Tinebase_Config::getInstance()->set(Tinebase_Config::AREA_LOCKS, $locks);
    }

    public function testGetState()
    {
        $this->_createLockConfig();
        $state = $this->_uit->getState(Tinebase_Model_AreaLockConfig::AREA_LOGIN);
        self::assertEquals(new Tinebase_DateTime('1970-01-01'), $state->expires);
        self::assertEquals(Tinebase_Model_AreaLockConfig::AREA_LOGIN, $state->area);

        $this->_setPin();
        $this->_uit->unlock(Tinebase_Model_AreaLockConfig::AREA_LOGIN, $this->_pin);
        $state = $this->_uit->getState(Tinebase_Model_AreaLockConfig::AREA_LOGIN);
        self::assertEquals(new Tinebase_DateTime('2150-01-01'), $state->expires);
    }

    public function testUnlock()
    {
        $this->_createLockConfig();

        $incorrectPin = '5678';
        $state = $this->_uit->unlock(Tinebase_Model_AreaLockConfig::AREA_LOGIN, $incorrectPin);
        self::assertEquals(new Tinebase_DateTime('1970-01-01'), $state->expires);

        $this->_setPin();
        $state = $this->_uit->unlock(Tinebase_Model_AreaLockConfig::AREA_LOGIN, $this->_pin);
        // @ŧodo expect session lifetime as expiry date?
        self::assertEquals(new Tinebase_DateTime('2150-01-01'), $state->expires, 'area should be unlocked');
    }
    
    protected function _setPin()
    {
        $user = Tinebase_Core::getUser();
        Tinebase_User::getInstance()->setPin($user, $this->_pin);
    }

    public function testLock()
    {
        $this->_createLockConfig();
        $this->_setPin();
        $state = $this->_uit->unlock(Tinebase_Model_AreaLockConfig::AREA_LOGIN, $this->_pin);
        self::assertEquals(new Tinebase_DateTime('2150-01-01'), $state->expires);

        $state = $this->_uit->lock(Tinebase_Model_AreaLockConfig::AREA_LOGIN, $this->_pin);
        self::assertEquals(new Tinebase_DateTime('1970-01-01'), $state->expires);
    }

    public function isLocked()
    {
        $this->_createLockConfig();
        $isLocked = $this->_uit->unlock(Tinebase_Model_AreaLockConfig::AREA_LOGIN, $this->_pin);
        self::assertTrue($isLocked);

        $this->testUnlock();
        $isLocked = $this->_uit->unlock(Tinebase_Model_AreaLockConfig::AREA_LOGIN, $this->_pin);
        self::assertFalse($isLocked);
    }

    /**
     * @param string $validity
     * @see 0013328: protect applications with second factor
     */
    public function testAppProtection($validity = Tinebase_Model_AreaLockConfig::VALIDITY_SESSION)
    {
        Tasks_Controller_Task::getInstance()->resetValidatedAreaLock();
        $this->_createLockConfig([
            'area' => 'Tasks',
            'validity' => $validity,
        ]);

        // try to access app
        try {
            Tasks_Controller_Task::getInstance()->getAll();
            self::fail('it should not be possible to access app without PIN');
        } catch (Tinebase_Exception $te) {
            // check exception
            self::assertTrue($te instanceof Tinebase_Exception_AreaLocked);
        }

        $this->_setPin();
        $this->_uit->unlock('Tasks', $this->_pin);

        // try to access app again
        $result = Tasks_Controller_Task::getInstance()->getAll();
        self::assertGreaterThanOrEqual(0, count($result));
    }

    /**
     * testAppProtectionWithPresence
     */
    public function testAppProtectionWithPresence()
    {
        $this->testAppProtection(Tinebase_Model_AreaLockConfig::VALIDITY_PRESENCE);
    }

    /**
     * create VALIDITY_PRESENCE config and test report presence
     */
    public function testLockWithPresence()
    {
        $this->_createLockConfig([
            'lifetime' => 5,
            'validity' => Tinebase_Model_AreaLockConfig::VALIDITY_PRESENCE,
        ]);
        $this->_setPin();
        $this->_uit->unlock(Tinebase_Model_AreaLockConfig::AREA_LOGIN, $this->_pin);
        sleep(3);
        self::assertFalse($this->_uit->isLocked(Tinebase_Model_AreaLockConfig::AREA_LOGIN, 'should be unlocked for at least 5 secs'));
        Tinebase_Presence::getInstance()->reportPresence();
        sleep(3);
        self::assertFalse($this->_uit->isLocked(Tinebase_Model_AreaLockConfig::AREA_LOGIN), 'should still be unlocked - presence was reported');
        sleep(3);
        self::assertTrue($this->_uit->isLocked(Tinebase_Model_AreaLockConfig::AREA_LOGIN), 'should now be locked - no presence was reported for 6 secs');
    }

    /**
     * create VALIDITY_SESSION config with lifetime of 5 secs
     */
    public function testLockWithLifetime()
    {
        $this->_createLockConfig([
            'lifetime' => 5,
            'validity' => Tinebase_Model_AreaLockConfig::VALIDITY_LIFETIME,
        ]);
        $this->_setPin();
        $this->_uit->unlock(Tinebase_Model_AreaLockConfig::AREA_LOGIN, $this->_pin);
        self::assertFalse($this->_uit->isLocked(Tinebase_Model_AreaLockConfig::AREA_LOGIN));
        sleep(6);

        self::assertTrue($this->_uit->isLocked(Tinebase_Model_AreaLockConfig::AREA_LOGIN),
            'should be locked again after 6 seconds');
    }
}
