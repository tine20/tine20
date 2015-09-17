<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Tinebase_Lock
 */
class Tinebase_LockTest extends TestCase
{
    protected $_testLockId = 'testlockId';
    
    /**
     * tear down tests
     */
    protected function tearDown()
    {
        parent::tearDown();

        Tinebase_Lock::releaseDBSessionLock($this->_testLockId);
    }

        /**
     * Test create a lock
     */
    public function testLock()
    {
        $aquireLock1 = Tinebase_Lock::aquireDBSessionLock($this->_testLockId);

        $this->assertTrue($aquireLock1, 'lock should be available');

        $aquireLock2 = Tinebase_Lock::aquireDBSessionLock($this->_testLockId);

        $this->assertFalse($aquireLock2, 'lock should not be available');
    }

    /**
     * test lock release
     */
    public function testReleaseLock()
    {
        $this->testLock();

        Tinebase_Lock::releaseDBSessionLock($this->_testLockId);

        $aquireLock = Tinebase_Lock::aquireDBSessionLock($this->_testLockId);

        $this->assertTrue($aquireLock, 'lock should be available again');
    }
}
