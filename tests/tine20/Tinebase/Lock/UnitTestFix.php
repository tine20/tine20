<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Locking utility class, unit test fixes
 *
 * @package     Tinebase
 */
class Tinebase_Lock_UnitTestFix extends Tinebase_Lock
{
    /**
     * for unit tests, don't use it unless you know what you do
     */
    public static function clearLocks()
    {
        static::$locks = [];
    }

    /**
     * for unit tests, don't use it unless you know what you do
     */
    public static function fixBackend($backend)
    {
        switch ($backend) {
            case Tinebase_Lock_Redis::class:
                $cachingBackend = null;
                $config = Tinebase_Config::getInstance();
                if ($config->caching && $config->caching->backend) {
                    $cachingBackend = ucfirst($config->caching->backend);
                }
                if ($cachingBackend === 'Redis' && extension_loaded('redis')) {
                    $host = $config->caching->host ? $config->caching->host :
                        ($config->caching->redis && $config->caching->redis->host ?
                            $config->caching->redis->host : 'localhost');
                    $port = $config->caching->port ? $config->caching->port :
                        ($config->caching->redis && $config->caching->redis->port ? $config->caching->redis->port : 6379);
                    static::$backend = Tinebase_Lock_Redis::class;
                    Tinebase_Lock_Redis::connect($host, $port);
                    return true;
                }
                return false;
            case Tinebase_Lock_Mysql::class:
                $db = Tinebase_Core::getDb();
                if ($db instanceof Zend_Db_Adapter_Pdo_Mysql) {
                    static::$backend = Tinebase_Lock_Mysql::class;
                    Tinebase_Lock_Mysql::checkCapabilities();
                    return true;
                }
                return false;
            case Tinebase_Lock_Pgsql::class:
                $db = Tinebase_Core::getDb();
                if ($db instanceof Zend_Db_Adapter_Pdo_Pgsql) {
                    static::$backend = Tinebase_Lock_Pgsql::class;
                    return true;
                }
                return false;
            default:
                throw new Tinebase_Exception_NotImplemented('lock backend ' . $backend . ' is not implemented');
        }
    }
}