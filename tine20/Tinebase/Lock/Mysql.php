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
        $db = Tinebase_Core::getDb();
        $db->query('SELECT now()')->fetchAll();
    }

    /**
     * @param int $timeout
     * @return bool
     */
    public function tryAcquire(int $timeout = 0)
    {
        if ($this->_isLocked) {
            throw new Tinebase_Exception_Backend('trying to acquire a lock on a locked lock');
        }
        if (!static::$supportsMultipleLocks && static::$mysqlLockId !== null) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                .' your mysql version does not support multiple locks per session, configure a better lock backend');
            $this->_isLocked = true;
            return null;
        }
        $db = Tinebase_Core::getDb();
        if (($stmt = $db->query('SELECT GET_LOCK("' . $this->_lockId . '", ' . $timeout . ')')) &&
                $stmt->setFetchMode(Zend_Db::FETCH_NUM) &&
                ($row = $stmt->fetch()) &&
                $row[0] == 1) {
            static::$mysqlLockId = $this->_lockId;
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
        if (!static::$supportsMultipleLocks && static::$mysqlLockId !== $this->_lockId) {
            $this->_isLocked = false;
            return null;
        }

        $db = Tinebase_Core::getDb();
        if (($stmt = $db->query('SELECT RELEASE_LOCK("' . $this->_lockId . '")')) &&
                $stmt->setFetchMode(Zend_Db::FETCH_NUM) &&
                ($row = $stmt->fetch()) &&
                $row[0] == 1) {
            static::$mysqlLockId = null;
            $this->_isLocked = false;
            return true;
        }
        return false;
    }

    /**
     * @throws Setup_Exception
     * @throws Tinebase_Exception_InvalidArgument
     */
    public static function checkCapabilities()
    {
        if (Setup_Backend_Factory::factory()->supports('mysql >= 5.7.5 | mariadb >= 10.0.2')) {
            static::$supportsMultipleLocks = true;
        }
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            .' mysql support for multiple locks: ' . var_export(static::$supportsMultipleLocks, true));
    }

    /**
     * @return bool
     */
    public static function supportsMultipleLocks()
    {
        return static::$supportsMultipleLocks;
    }
}