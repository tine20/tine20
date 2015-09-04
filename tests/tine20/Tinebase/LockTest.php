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
    /**
     * Test create a scheduled import
     */
    public function testLock()
    {
        $aquireLock1 = Tinebase_Lock::aquireDBSessionLock('testlock');

        $this->assertTrue($aquireLock1, 'lock should be available');

        $aquireLock2 = Tinebase_Lock::aquireDBSessionLock('testlock');

        $this->assertFalse($aquireLock2, 'lock should not be available');
    }
}
