<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Session
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Zend_RedisProxy as Redis;

/**
 * Class for making use of the redis proxy for sessions too
 *
 * @package     Tinebase
 * @subpackage  Session
 */
class Tinebase_Session_SaveHandler_Redis extends SessionHandler implements Zend_Session_SaveHandler_Interface
{
    protected $_redis;
    protected $_lifeTimeSec;
    protected $_prefix;

    public function __construct(Redis $redis, $lifeTimeSec, $_prefix)
    {
        $this->_redis = $redis;
        if (($this->_lifeTimeSec = (int)$lifeTimeSec) < 1) {
            throw new Tinebase_Exception_Backend('session lifetime needs to be bigger than 1 sec');
        }
        $this->_prefix = $_prefix ?? 'tine20SESSION_';
    }

    public function setRedisLogDelegator(callable $delegator = null)
    {
        $this->_redis->setLogDelegator($delegator);
    }

    /**
     * @inheritDoc
     */
    public function open($save_path, $name)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function close()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function read($id)
    {
        if (false !== ($data = $this->_redis->get($this->_prefix . $id))) {
            $this->_redis->expire($this->_prefix . $id, $this->_lifeTimeSec);
        } else {
            $data = '';
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function write($id, $data)
    {
        return true === $this->_redis->setEx($this->_prefix . $id, $this->_lifeTimeSec, $data);
    }

    /**
     * @inheritDoc
     */
    public function destroy($id)
    {
        return false !== $this->_redis->del($this->_prefix . $id);
    }

    /**
     * @inheritDoc
     */
    public function gc($maxlifetime)
    {
        return true;
    }

    /**
     * Validate session id
     * @param string $session_id The session id
     * @return bool <p>
     * Note this value is returned internally to PHP for processing.
     * </p>
     */
    public function validateId($session_id)
    {
        return 1 === (int)$this->_redis->exists($this->_prefix . $session_id);
    }

    /**
     * Update timestamp of a session
     * @param string $session_id The session id
     * @param string $session_data <p>
     * The encoded session data. This data is the
     * result of the PHP internally encoding
     * the $_SESSION superglobal to a serialized
     * string and passing it as this parameter.
     * Please note sessions use an alternative serialization method.
     * </p>
     * @return bool
     */
    public function updateTimestamp($session_id, $session_data)
    {
        return $this->write($session_id, $session_data);
    }
}
