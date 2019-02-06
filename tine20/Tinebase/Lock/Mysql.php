<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Lock
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Mysql lock implementation
 * the lock only persists during a connection session
 * no need to use __destruct to release the lock, it goes away automatically
 *
 * @package     Tinebase
 * @subpackage  Lock
 */
class Tinebase_Lock_Mysql extends Tinebase_Lock_Abstract
{
    protected static $mysqlLockId = null;
    protected static $supportsMultipleLocks = false;

    public function keepAlive()
    {
        if ($this->_isLocked) {
            $db = Tinebase_Core::getDb();
            if (!($stmt = $db->query('SELECT IS_USED_LOCK("' . $this->_lockId . '") = CONNECTION_ID()')) ||
                    !$stmt->setFetchMode(Zend_Db::FETCH_NUM) ||
                    !($row = $stmt->fetch()) ||
                    $row[0] != 1) {
                throw new Tinebase_Exception_Backend('lock is not held by us anymore');
            }
        }
    }

    /**
     * @return bool
     */
    public function tryAcquire()
    {
        if ($this->_isLocked) {
            throw new Tinebase_Exception_Backend('trying to acquire a lock on a locked lock');
        }
        $db = Tinebase_Core::getDb();
        // first check if the lock is free, that means, not yet aquired possible by our own session
        // belows get_lock would allow to aquire the lock inside our own mysql session multiple times
        if (($stmt = $db->query('SELECT IS_FREE_LOCK("' . $this->_lockId . '")')) &&
                $stmt->setFetchMode(Zend_Db::FETCH_NUM) &&
                ($row = $stmt->fetch()) &&
                $row[0] != 1) {
            return false;
        }
        $stmt->closeCursor();
        if (($stmt = $db->query('SELECT GET_LOCK("' . $this->_lockId . '", 0)')) &&
                $stmt->setFetchMode(Zend_Db::FETCH_NUM) &&
                ($row = $stmt->fetch()) &&
                $row[0] == 1) {
            $this->_isLocked = true;
            return true;
        }
        return false;
    }

    /**
     * @param string $lockId
     * @return bool
     */
    public function release()
    {
        if (!$this->_isLocked) {
            throw new Tinebase_Exception_Backend('trying to release an unlocked lock');
        }

        $db = Tinebase_Core::getDb();
        if (($stmt = $db->query('SELECT RELEASE_LOCK("' . $this->_lockId . '")')) &&
                $stmt->setFetchMode(Zend_Db::FETCH_NUM) &&
                ($row = $stmt->fetch()) &&
                $row[0] == 1) {
            $this->_isLocked = false;
            return true;
        }
        return false;
    }
}