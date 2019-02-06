<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2015-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Locking utility class
 *
 * @package     Tinebase
 */
class Tinebase_Lock
{
    protected static $locks = [];
    protected static $lastKeepAlive = null;

    /**
     * @var string
     */
    protected static $backend = null;

    /**
     * tries to release all locked locks (catches and logs exceptions silently)
     * removes all lock objects
     */
    public static function clearLocks()
    {
        /** @var Tinebase_Lock_Abstract $lock */
        foreach (static::$locks as $lock) {
            try {
                if ($lock->isLocked()) {
                    $lock->release();
                }
            } catch (Exception $e) {
                Tinebase_Exception::log($e);
            }
        }

        static::$locks = [];
    }

    public static function keepLocksAlive()
    {
        // only do this once a minute
        if (null !== static::$lastKeepAlive && time() - static::$lastKeepAlive < 60) {
            return;
        }
        static::$lastKeepAlive = time();

        /** @var Tinebase_Lock_Abstract $lock */
        foreach (static::$locks as $lock) {
            // each lock will check that it is still owns the lock
            $lock->keepAlive();
        }
    }

    /**
     * @param $id
     * @return Tinebase_Lock_Interface
     */
    public static function getLock($id)
    {
        $id = static::preFixId($id);
        if (!isset(static::$locks[$id])) {
            static::$locks[$id] = static::getBackend($id);
        }
        return static::$locks[$id];
    }
    /**
     * @param string $id
     * @return bool|null bool on success / failure, null if not supported
     */
    public static function tryAcquireLock($id)
    {
        $id = static::preFixId($id);
        if (isset(static::$locks[$id])) {
            return static::$locks[$id]->tryAcquire();
        }
        if (($lock = static::getBackend($id)) === null) {
            return null;
        }
        static::$locks[$id] = $lock;
        return $lock->tryAcquire();
    }

    /**
     * @param string $id
     * @return bool
     */
    public static function releaseLock($id)
    {
        $id = static::preFixId($id);
        if (isset(static::$locks[$id])) {
            return static::$locks[$id]->release();
        }
        return false;
    }

    public static function preFixId($id)
    {
        return 'tine20_' . $id;
    }
    /**
     * @param string $id
     * @return Tinebase_Lock_Interface
     */
    protected static function getBackend($id)
    {
        return new Tinebase_Lock_Mysql($id);
    }

    public static function resetKeepAliveTime()
    {
        static::$lastKeepAlive = null;
    }
}