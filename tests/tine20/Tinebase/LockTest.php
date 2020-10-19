<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2015-2018 Metaways Infosystems GmbH (http://www.metaways.de)
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

    public function testPgsqlLock()
    {
        if (!Tinebase_Lock_UnitTestFix::fixBackend(Tinebase_Lock_Pgsql::class)) {
            static::markTestSkipped('no pgsql available');
        }

        $this->_testLock();
    }

    public function testMySQLLock()
    {
        if (!Tinebase_Lock_UnitTestFix::fixBackend(Tinebase_Lock_Mysql::class)) {
            static::markTestSkipped('no mysql available');
        }

        $this->_testLock();
    }

    public function testRedisLock()
    {
        if (!Tinebase_Lock_UnitTestFix::fixBackend(Tinebase_Lock_Redis::class)) {
            static::markTestSkipped('no redis configured');
        }

        $this->_testLock();
    }

    public function testRedisBackendLock()
    {
        if (!Tinebase_Lock_UnitTestFix::fixBackend(Tinebase_Lock_Redis::class)) {
            static::markTestSkipped('no redis configured');
        }

        $this->_testLockBackend();
    }

    protected function _testLock($recursion = false)
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

        static::assertTrue(Tinebase_Lock::releaseLock('lock1'), 'could not release lock1');
        static::assertTrue(Tinebase_Lock::getLock('lock2')->release(), 'could not release lock2');

        static::assertFalse(Tinebase_Lock::getLock('lock1')->isLocked(), 'lock1 should be unlocked');
        static::assertFalse(Tinebase_Lock::getLock('lock2')->isLocked(), 'lock2 should be unlocked');

        if (false === $recursion) {
            $this->_testLock(true);
        }
    }

    /**
     * Test create a lock
     */
    protected function _testLockBackend()
    {
        $this->_testLockIds['lock1'] = true;
        static::assertTrue(Tinebase_Lock::tryAcquireLock('lock1'), 'could not get acquire lock1');
        $lock = Tinebase_Lock::getLock('lock1');

        if ($lock instanceof Tinebase_Lock_Pgsql) {
            static::markTestSkipped('pgsql can not test this');
            // see http://www.postgresql.org/docs/9.1/static/functions-admin.html
            // "Multiple lock requests stack, so that if the same resource is locked three times it must then be unlocked three times to be released for other sessions' use."
            // we would need to open two connections to be able to test locks for pgsql
        } elseif ($lock instanceof Tinebase_Lock_Redis) {
            $lock2 = new Tinebase_Lock_Redis(Tinebase_Lock::preFixId('lock1'));
            static::assertFalse($lock2->tryAcquire(), 'should not be able to lock the same lock id twice');
        } elseif ($lock instanceof Tinebase_Lock_Mysql) {
            static::markTestSkipped('mysql can not test this');
            //  It is even possible for a given session to acquire multiple locks for the same name.
        } else {
            static::fail('implement a test for this new lock backend');
        }
    }
}
