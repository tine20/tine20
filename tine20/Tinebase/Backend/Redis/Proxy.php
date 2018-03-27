<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * A redis proxy that retries redis communication in case of failures
 *
 * @package     Tinebase
 * @subpackage  Backend
 */
class Tinebase_Backend_Redis_Proxy
{
    /**
     * @var Redis
     */
    protected $_redis = null;

    protected $_connectionMethod = null;
    protected $_connectionArguments = null;

    protected $_inMulti = false;
    protected $_multiPipe = [];

    /**
     * Tinebase_Backend_Redis constructor.
     */
    public function __construct()
    {
        $this->_redis = new Redis();
    }

    /**
     * @param $_name
     * @param array $_arguments
     * @return mixed
     * @throws RedisException
     */
    public function __call($_name, array $_arguments)
    {
        $tries = 0;

        switch ($_name) {
            case 'connect':
            case 'open':
            case 'pconnect':
                if (null !== $this->_connectionMethod) {
                    throw new Tinebase_Exception_Backend('already connected');
                }

                $this->_connectionMethod = $_name;
                $this->_connectionArguments = $_arguments;
                $this->_inMulti = false;
                break;

            case 'multi':
                $this->_inMulti = true;
                break;

            case 'discard':
                $this->_inMulti = false;
                break;

            default:
                if (true === $this->_inMulti) {
                    if ('exec' === $_name) {
                        $this->_inMulti = false;
                    }
                    $result = call_user_func_array([$this->_redis, $_name], $_arguments);
                    if ($result === $this->_redis) {
                        return $this;
                    }
                    return $result;
                }
        }

        while (true) {
            try {
                $result = call_user_func_array([$this->_redis, $_name], $_arguments);
                if ($result === $this->_redis) {
                    return $this;
                }
                return $result;
            } catch (RedisException $re) {
                if (++$tries > 5) {
                    throw $re;
                }

                // give Redis 100ms and try again
                usleep(100000);

                switch ($_name) {
                    case 'connect':
                    case 'open':
                    case 'pconnect':
                        continue;
                }

                try {
                    $this->_redis->ping();
                } catch (RedisException $re) {
                    $this->_reconnect();
                }
            }
        }
    }

    protected function _reconnect()
    {
        $cM = $this->_connectionMethod;
        $cA = $this->_connectionArguments;
        $this->close();
        $this->_connectionMethod = $cM;
        $this->_connectionArguments = $cA;
        try {
            call_user_func_array([$this->_redis, $this->_connectionMethod], $this->_connectionArguments);
        } catch (RedisException $re) {}
    }

    public function close()
    {
        $this->_connectionMethod = null;
        $this->_connectionArguments = null;
        try {
            $this->_redis->close();
        } catch (RedisException $re) {}
        $this->_redis = new Redis();
    }
}