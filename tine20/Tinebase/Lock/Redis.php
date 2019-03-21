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

use Zend_RedisProxy as Redis;

/**
 * Redis lock implementation
 *
 * @package     Tinebase
 * @subpackage  Lock
 */
class Tinebase_Lock_Redis extends Tinebase_Lock_Abstract
{
    /**
     * @var Redis
     */
    protected static $_redis = null;

    protected $_lockUUID = null;

    public function keepAlive()
    {
        // use the method! not the property! the method does the keep a live for us.
        if (!$this->isLocked()) {
            throw new Tinebase_Exception_Backend('trying to keep an unlocked lock alive');
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
        $this->_lockUUID = Tinebase_Record_Abstract::generateUID();

        // set a TTL of 10 minutes
        if (true === static::$_redis->rawCommand('SET',  $this->_lockId, $this->_lockUUID, 'NX', 'PX', '600000')) {
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

        // this Redis "Lock" is a lease! not a lock. So maybe we lost our lease to a time out here
        // first clear the error, execute eval, if that returns 0, but no errors occurred, it's a time out
        static::$_redis->clearLastError();
        if (static::$_redis->eval('if redis.call("get",KEYS[1]) == KEYS[2]
                then
                    return redis.call("del",KEYS[1])
                else
                    return 0
                end', [$this->_lockId, $this->_lockUUID], 2)) {
            $this->_isLocked = false;
            return true;
        }
        if (null === static::$_redis->getLastError()) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::'
                . __LINE__ .' releasing an expired lock');
            $this->_isLocked = false;
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::'
                . __LINE__ .' lock release failed: ' . static::$_redis->getLastError());
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isLocked()
    {
        if ($this->_isLocked) {
            $getResult = null;
            if (!($expireResult = static::$_redis->expire($this->_lockId, 600)) ||
                    ($getResult = static::$_redis->get($this->_lockId)) !== $this->_lockUUID) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::'
                    . __LINE__ .' lock was locked, expireResult: ' . var_export($expireResult, true)
                    . ' getResult: ' . var_export($getResult, true));
                $this->_isLocked = false;
            }
        }
        return $this->_isLocked;
    }

    public function __destruct()
    {
        if ($this->_isLocked) {
            $this->release();
        }
    }

    public static function connect($host, $port)
    {
        static::$_redis = new Redis();
        if (!static::$_redis->connect($host, $port, 3)) {
            throw new Tinebase_Exception_Backend('could not connect to redis');
        }
    }
}