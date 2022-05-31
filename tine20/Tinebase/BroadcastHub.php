<?php declare(strict_types=1);

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

use Zend_RedisProxy as Redis;

/**
 * class to broadcast CRUD actions on records
 *
 * @package     Tinebase
 */
class Tinebase_BroadcastHub
{
    use Tinebase_Controller_SingletonTrait;

    public function isActive(): bool
    {
        return $this->_isActive;
    }

    public function push(string $verb, string $model, string $recordId, ?string $containerId): int
    {
        return $this->_getRedis()->publish($this->_pubSubName, json_encode([
            'verb'          => $verb,
            'model'         => $model,
            'recordId'      => $recordId,
            'containerId'   => $containerId,
        ]));
    }

    public function pushAfterCommit(string $verb, string $model, string $recordId, ?string $containerId): void
    {
        Tinebase_TransactionManager::getInstance()->registerAfterCommitCallback(
            function() use($verb, $model, $recordId, $containerId) {
                Tinebase_BroadcastHub::getInstance()->push($verb, $model, $recordId, $containerId);
        });
    }

    protected $_config;
    protected $_isActive;
    protected $_redis;
    protected $_pubSubName;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_config = Tinebase_Config::getInstance()->{Tinebase_Config::BROADCASTHUB};
        $this->_isActive = $this->_config->{Tinebase_Config::BROADCASTHUB_ACTIVE};
        $this->_pubSubName = $this->_config->{Tinebase_Config::BROADCASTHUB_PUBSUBNAME};
    }

    protected function _getRedis(): Redis
    {
        if (null === $this->_redis) {
            if (!$this->_isActive) {
                throw new Tinebase_Exception_Backend(__CLASS__ . ' is not activated');
            }
            $this->_redis = new Redis();
            $this->_redis->connect($this->_config->{Tinebase_Config::BROADCASTHUB_REDIS}
                    ->{Tinebase_Config::BROADCASTHUB_REDIS_HOST},
                $this->_config->{Tinebase_Config::BROADCASTHUB_REDIS}->{Tinebase_Config::BROADCASTHUB_REDIS_PORT});
        }

        return $this->_redis;
    }
}
