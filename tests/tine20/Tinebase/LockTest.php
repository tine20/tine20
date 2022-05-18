<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2015-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Test class for Tinebase_Lock
 */
class Tinebase_LockTest extends TestCase
{
    protected $_testLockIds = [];

    /**
     * set up tests
     */
    protected function setUp(): void
{
        parent::setUp();

        $this->_testLockIds = [];
    }

    /**
     * tear down tests
     */
    protected function tearDown(): void
{
        foreach ($this->_testLockIds as $id => $foo) {
            $lock = Tinebase_Lock::getLock($id);
            if ($lock->isLocked()) {
                $lock->release();
            }
        }

        parent::tearDown();
    }

    public function testLock($recursion = false)
    {
        $this->_testLockIds['lock1'] = true;
        static::assertTrue(Tinebase_Lock::tryAcquireLock('lock1'), 'could not get acquire lock1');

        $this->_testLockIds['lock2'] = true;
        static::assertTrue(Tinebase_Lock::tryAcquireLock('lock2'), 'could not get acquire lock2');

        Tinebase_Lock::keepLocksAlive();

        try {
            Tinebase_Lock::tryAcquireLock('lock1');
            static::fail('must not be possible to lock same lock twice');
        } catch (Tinebase_Exception_Backend $teb) {
            static::assertEquals('trying to acquire a lock on a locked lock', $teb->getMessage());
        }

        static::assertTrue(Tinebase_Lock::getLock('lock1')->isLocked(), 'lock1 should be locked');
        static::assertTrue(Tinebase_Lock::getLock('lock2')->isLocked(), 'lock2 should be locked');

        // would throw on failure
        Tinebase_Lock::keepLocksAlive();

        static::assertTrue(Tinebase_Lock::releaseLock('lock1'), 'could not release lock1');
        static::assertTrue(Tinebase_Lock::getLock('lock2')->release(), 'could not release lock2');

        static::assertFalse(Tinebase_Lock::getLock('lock1')->isLocked(), 'lock1 should be unlocked');
        static::assertFalse(Tinebase_Lock::getLock('lock2')->isLocked(), 'lock2 should be unlocked');

        if (false === $recursion) {
            $this->testLock(true);
        }
    }

    public function testKeepAliveFailure()
    {
        static::assertTrue(Tinebase_Lock::tryAcquireLock('lock1'), 'could not get acquire lock1');

        static::assertTrue(($stmt = Tinebase_Core::getDb()->query('SELECT RELEASE_LOCK("' . sha1('tine20_lock1') . '")')) &&
                $stmt->setFetchMode(Zend_Db::FETCH_NUM) &&
                ($row = $stmt->fetch()) &&
                $row[0] == 1);
        $stmt->closeCursor();
        Tinebase_Lock::resetKeepAliveTime();
        try {
            Tinebase_Lock::keepLocksAlive();
        } catch (Tinebase_Exception_Backend $teb) {
            self::assertEquals('lock is not held by us anymore', $teb->getMessage());
        }
    }

    /**
     * Test create a lock
     */
    public function testLockBackend()
    {
        $this->_testLockIds['lock1'] = true;
        static::assertTrue(Tinebase_Lock::tryAcquireLock('lock1'), 'could not get acquire lock1');
        Tinebase_Lock::getLock('lock1');

        $lock2 = new Tinebase_Lock_Mysql(Tinebase_Lock::preFixId('lock1'));
        static::assertFalse($lock2->tryAcquire(), 'should not be able to lock the same lock id twice');
    }
}
