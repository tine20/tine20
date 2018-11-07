<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Lock
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Pgsql lock implementation
 * the lock only persists during a connection session
 * no need to use __destruct to release the lock, it goes away automatically
 *
 * @package     Tinebase
 * @subpackage  Lock
 */
class Tinebase_Lock_Pgsql extends Tinebase_Lock_Abstract
{
    public function keepAlive()
    {
        $db = Tinebase_Core::getDb();
        $db->query('SELECT NOW()')->fetchAll();
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
        if (($stmt = $db->query('SELECT pg_try_advisory_lock(' . $this->_lockId . ')')) &&
                $stmt->setFetchMode(Zend_Db::FETCH_NUM) &&
                ($row = $stmt->fetch()) &&
                $row[0] == 1) {
            $this->_isLocked = true;
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function release()
    {
        if (!$this->_isLocked) {
            throw new Tinebase_Exception_Backend('trying to release an unlocked lock');
        }
        $db = Tinebase_Core::getDb();
        if (($stmt = $db->query('SELECT pg_advisory_unlock(' . $this->_lockId . ')')) &&
                $stmt->setFetchMode(Zend_Db::FETCH_NUM) &&
                ($row = $stmt->fetch()) &&
                $row[0] == 1) {
            $this->_isLocked = false;
            return true;
        }
        return false;
    }

    protected function processLockId()
    {
        $this->_lockId = current(unpack('N', sha1($this->_lockId, true)));
    }
}