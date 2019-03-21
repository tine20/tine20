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
     * @return bool|null bool on success / failure, null if not supported
     */
    public static function releaseLock($id)
    {
        $id = static::preFixId($id);
        if (isset(static::$locks[$id])) {
            return static::$locks[$id]->release();
        }
        if (static::getBackend($id) === null) {
            return null;
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
        if (null === static::$backend) {
            $db = Tinebase_Core::getDb();
            if ($db instanceof Zend_Db_Adapter_Pdo_Mysql) {
                Tinebase_Lock_Mysql::checkCapabilities();
            }

            $config = Tinebase_Config::getInstance();
            $cachingBackend = null;
            if ($config->caching && $config->caching->backend) {
                $cachingBackend = ucfirst($config->caching->backend);
            }

            if (Tinebase_Lock_Mysql::supportsMultipleLocks()) {
                static::$backend = Tinebase_Lock_Mysql::class;
            } elseif ($cachingBackend === 'Redis' && extension_loaded('redis')) {
                $host = $config->caching->host ? $config->caching->host :
                    ($config->caching->redis && $config->caching->redis->host ?
                        $config->caching->redis->host : 'localhost');
                $port = $config->caching->port ? $config->caching->port :
                    ($config->caching->redis && $config->caching->redis->port ? $config->caching->redis->port : 6379);
                static::$backend = Tinebase_Lock_Redis::class;
                Tinebase_Lock_Redis::connect($host, $port);
            } elseif ($db instanceof Zend_Db_Adapter_Pdo_Mysql) {
                static::$backend = Tinebase_Lock_Mysql::class;
            } elseif($db instanceof Zend_Db_Adapter_Pdo_Pgsql) {
                static::$backend = Tinebase_Lock_Pgsql::class;
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::'
                    . __LINE__ .' no lock backend found');
                return null;
            }
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                .' lock backend is: ' . static::$backend);
        }
        return new static::$backend($id);
    }
}