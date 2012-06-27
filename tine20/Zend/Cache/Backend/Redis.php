<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Cache
 * @subpackage Zend_Cache_Backend
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */


/**
 * @package    Zend_Cache
 * @subpackage Zend_Cache_Backend
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Cache_Backend_Redis extends Zend_Cache_Backend implements Zend_Cache_Backend_ExtendedInterface
{
    /**
     * Default Values
     */
    const DEFAULT_HOST    = '127.0.0.1';
    const DEFAULT_PORT    =  6379;
    const DEFAULT_PERSISTENT = false;
    #const DEFAULT_WEIGHT  = 1;
    const DEFAULT_TIMEOUT = 1;
    const DEFAULT_PREFIX  = 'ZEND_CACHE_';
    const DEFAULT_STATUS  = true;
    const DEFAULT_FAILURE_CALLBACK = null;
    const TAG_PREFIX      = 'TAGS_';

    /**
     * Log message
     */
    const TAGS_UNSUPPORTED_BY_CLEAN_OF_REDIS_BACKEND = 'Zend_Cache_Backend_Redis::clean() : tags are unsupported by the Redis backend';
    const TAGS_UNSUPPORTED_BY_SAVE_OF_REDIS_BACKEND =  'Zend_Cache_Backend_Redis::save() : tags are unsupported by the Redis backend';

    /**
     * Available options
     *
     * =====> (array) servers :
     * an array of memcached server ; each memcached server is described by an associative array :
     * 'host' => (string) : the name of the memcached server
     * 'port' => (int) : the port of the memcached server
     * 'persistent' => (bool) : use or not persistent connections to this memcached server
     * 'weight' => (int) : number of buckets to create for this server which in turn control its
     *                     probability of it being selected. The probability is relative to the total
     *                     weight of all servers.
     * 'timeout' => (int) : value in seconds which will be used for connecting to the daemon. Think twice
     *                      before changing the default value of 1 second - you can lose all the
     *                      advantages of caching if your connection is too slow.
     * 'retry_interval' => (int) : controls how often a failed server will be retried, the default value
     *                             is 15 seconds. Setting this parameter to -1 disables automatic retry.
     *
     * @var array available options
     */
    protected $_options = array(
        'servers' => array(array(
            'host' => self::DEFAULT_HOST,
            'port' => self::DEFAULT_PORT,
            'persistent' => self::DEFAULT_PERSISTENT,
            #'weight'  => self::DEFAULT_WEIGHT,
            'timeout' => self::DEFAULT_TIMEOUT,
            'prefix' => self::DEFAULT_PREFIX,
        ))
    );

    /**
     * Redis object
     *
     * @var Redis redis object
     */
    protected $_redis = null;

    /**
     * Constructor
     *
     * @param array $options associative array of options
     * @throws Zend_Cache_Exception
     * @return void
     */
    public function __construct(array $options = array())
    {
        if (!extension_loaded('redis')) {
            Zend_Cache::throwException('The redis extension must be loaded for using this backend !');
        }
        
        parent::__construct($options);
        
        if (isset($this->_options['servers'])) {
            $value= $this->_options['servers'];
            if (isset($value['host'])) {
                // in this case, $value seems to be a simple associative array (one server only)
                $value = array(0 => $value); // let's transform it into a classical array of associative arrays
            }
            $this->setOption('servers', $value);
        }
        
        $this->_redis = new Redis;
        
        foreach ($this->_options['servers'] as $server) {
            if (!array_key_exists('port', $server)) {
                $server['port'] = self::DEFAULT_PORT;
            }
            if (!array_key_exists('persistent', $server)) {
                $server['persistent'] = self::DEFAULT_PERSISTENT;
            }
            #if (!array_key_exists('weight', $server)) {
            #    $server['weight'] = self::DEFAULT_WEIGHT;
            #}
            if (!array_key_exists('timeout', $server)) {
                $server['timeout'] = self::DEFAULT_TIMEOUT;
            }
            if (!array_key_exists('prefix', $server)) {
                $server['prefix'] = self::DEFAULT_PREFIX;
            }
            
            $this->_redis->connect($server['host'], $server['port'], $server['timeout']);
        }
        
        $this->_redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        $this->_redis->setOption(Redis::OPT_PREFIX, $server['prefix']);
    }

    /**
     * Test if a cache is available for the given id and (if yes) return it (false else)
     *
     * @param  string  $id                     Cache id
     * @param  boolean $doNotTestCacheValidity If set to true, the cache validity won't be tested
     * @return string|false cached datas
     */
    public function load($id, $doNotTestCacheValidity = false)
    {
        $tmp = $this->_redis->get($id);
        if (is_array($tmp)) {
            return $tmp[0];
        }
        return false;
    }

    /**
     * Test if a cache is available or not (for the given id)
     *
     * @param  string $id Cache id
     * @return mixed|false (a cache is not available) or "last modified" timestamp (int) of the available cache record
     */
    public function test($id)
    {
        try {
            $tmp = $this->_redis->get($id);
        } catch (Exception $e) {
            $this->_log("Zend_Cache_Backend_Redis::test() : Got an exception trying to access redis cache: " . $e);
            return false;
        }
        if (is_array($tmp)) {
            return $tmp[1];
        }
        return false;
    }

    /**
     * Save some string datas into a cache record
     *
     * Note : $data is always "string" (serialization is done by the
     * core not by the backend)
     *
     * @param  string $data             Datas to cache
     * @param  string $id               Cache id
     * @param  array  $tags             Array of strings, the cache record will be tagged by each string entry
     * @param  int    $specificLifetime If != false, set a specific lifetime for this cache record (null => infinite lifetime)
     * @return boolean True if no problem
     */
    public function save($data, $id, $tags = array(), $specificLifetime = false)
    {
        $lifetime = $this->getLifetime($specificLifetime);
        
        $transaction = $this->_redis->multi();
        
        if ($lifetime) {
            $transaction->setex($id, $lifetime, array($data, time(), $lifetime, (array)$tags));
        } else {
            $transaction->set($id, array($data, time(), $lifetime, (array)$tags));
        }

        // add to tag sets
        foreach ((array) $tags as $tag) {
            $transaction->sAdd(self::TAG_PREFIX . $tag, $id);
        }

        $result = $transaction->exec();
        
        return $result;
    }

    /**
     * Remove a cache record
     *
     * @param  string $id Cache id
     * @return boolean True if no problem
     */
    public function remove($id)
    {
        $tmp = $this->_redis->get($id);
        $tags = $tmp[3];
        
        $transaction = $this->_redis->multi();
        
        // remove from tag sets
        foreach ((array) $tags as $tag) {
            $transaction->sRem(self::TAG_PREFIX . $tag, $id);
        }
        $transaction->delete($id);
        
        return $transaction->exec();
    }

    /**
     * Clean some cache records
     *
     * Available modes are :
     * 'all' (default)  => remove all cache entries ($tags is not used)
     * 'old'            => unsupported
     * 'matchingTag'    => remove cache entries matching all given tags
     *                     ($tags can be an array of strings or a single string)
     * 'notMatchingTag' => unsupported
     * 'matchingAnyTag' => remove cache entries matching any given tags
     *                     ($tags can be an array of strings or a single string)
     *
     * @param  string $mode Clean mode
     * @param  array  $tags Array of tags
     * @throws Zend_Cache_Exception
     * @return boolean True if no problem
     */
    public function clean($mode = Zend_Cache::CLEANING_MODE_ALL, $tags = array())
    {
        switch ($mode) {
            case Zend_Cache::CLEANING_MODE_ALL:
                return $this->_redis->flushDB();
                break;
                
            case Zend_Cache::CLEANING_MODE_OLD:
                $this->_log("Zend_Cache_Backend_Redis::clean() : CLEANING_MODE_OLD is not required for Redis backend");
                break;
                
            case Zend_Cache::CLEANING_MODE_MATCHING_TAG:
                $ids = $this->getIdsMatchingTags($tags);
                
                if (!empty($ids)) {
                    $values = $this->_redis->getMultiple($ids);
                    
                    $transaction = $this->_redis->multi();
                    
                    $i = 0;
                    foreach ($ids as $id) {
                        $transaction->delete($id);
                        
                        // if the record also has some other tags attached, remove record from these tag lists too
                        // $values[$i] can be null if id was not found by getMultiple
                        if (is_array($values[$i]) && is_array($values[$i][3])) {
                            $allTags = array_unique(array_merge((array) $tags, $values[$i][3]));
                        // otherwise just remove from the tag lists provided
                        } else {
                            $allTags = (array) $tags;
                        }
                        
                        foreach($allTags as $tag) {
                            $transaction->sRem(self::TAG_PREFIX . $tag, $id);
                        }
                        
                        $i++;
                    }
                    
                    $transaction->exec();
                }
                
                break;
                
            case Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG:
                $ids = $this->getIdsMatchingAnyTags($tags);
                
                if (!empty($ids)) {
                    $values = $this->_redis->getMultiple($ids);
                    
                    $transaction = $this->_redis->multi();
                    
                    $i = 0;
                    foreach ($ids as $id) {
                        $transaction->delete($id);

                        $allTags = array_unique(array_merge($tags, $values[$i][3]));
                        foreach($allTags as $tag) {
                            $transaction->sRem(self::TAG_PREFIX . $tag, $id);
                        }
                        
                        $i++;
                    }
                    
                    $transaction->exec();
                }
                
                break;
            
            case Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG:
                $this->_log(self::TAGS_UNSUPPORTED_BY_CLEAN_OF_REDIS_BACKEND);
                break;
                
            default:
                Zend_Cache::throwException('Invalid mode for clean() method');
                   break;
        }
    }

    /**
     * Return true if the automatic cleaning is available for the backend
     *
     * @return boolean
     */
    public function isAutomaticCleaningAvailable()
    {
        return false;
    }

    /**
     * Set the frontend directives
     *
     * @param  array $directives Assoc of directives
     * @throws Zend_Cache_Exception
     * @return void
     */
    public function setDirectives($directives)
    {
        parent::setDirectives($directives);
        
        $lifetime = $this->getLifetime(false);
        if ($lifetime === null) {
            // #ZF-4614 : we tranform null to zero to get the maximal lifetime
            parent::setDirectives(array('lifetime' => 0));
        }
    }

    /**
     * Return an array of stored cache ids
     *
     * @return array array of stored cache ids (string)
     */
    public function getIds()
    {
        $this->_log("Zend_Cache_Backend_Redis::getIds() : getting the list of cache ids is unsupported by Redis backend");
        return array();
    }

    /**
     * Return an array of stored tags
     *
     * @return array array of stored tags (string)
     */
    public function getTags()
    {
        $keysWithCachePrefix = $this->_redis->keys(self::TAG_PREFIX . '*');
        
        return $this->_removeTagPrefix($keysWithCachePrefix);
    }

    /**
     * Return an array of stored cache ids which match given tags
     *
     * In case of multiple tags, a logical AND is made between tags
     *
     * @param array $tags array of tags
     * @return array array of matching cache ids (string)
     */
    public function getIdsMatchingTags($tags = array())
    {
        $cacheTags = array();
        
        foreach ((array) $tags as $tag) {
            $cacheTags[] = self::TAG_PREFIX . $tag;
        }
        $ids = $this->_redis->sInter($cacheTags);
        
        return $ids;
    }

    /**
     * Return an array of stored cache ids which don't match given tags
     *
     * In case of multiple tags, a logical OR is made between tags
     *
     * @param array $tags array of tags
     * @return array array of not matching cache ids (string)
     */
    public function getIdsNotMatchingTags($tags = array())
    {
        $this->_log(self::TAGS_UNSUPPORTED_BY_SAVE_OF_REDIS_BACKEND);
        return array();
    }

    /**
     * Return an array of stored cache ids which match any given tags
     *
     * In case of multiple tags, a logical AND is made between tags
     *
     * @param array $tags array of tags
     * @return array array of any matching cache ids (string)
     */
    public function getIdsMatchingAnyTags($tags = array())
    {
        $cacheTags = array();
        
        foreach ((array) $tags as $tag) {
            $cacheTags[] = self::TAG_PREFIX . $tag;
        }
        $ids = $this->_redis->sUnion($cacheTags);
        
        return $ids;
    }

    /**
     * Return the filling percentage of the backend storage
     *
     * @throws Zend_Cache_Exception
     * @return int integer between 0 and 100
     */
    public function getFillingPercentage()
    {
        $this->_log("Filling percentage not supported by the Redis backend");
        
        return 0;
    }

    /**
     * Return an array of metadatas for the given cache id
     *
     * The array must include these keys :
     * - expire : the expire timestamp
     * - tags : a string array of tags
     * - mtime : timestamp of last modification time
     *
     * @param string $id cache id
     * @return array array of metadatas (false if the cache id is not found)
     */
    public function getMetadatas($id)
    {
        $tmp = $this->_redis->get($id);
        
        if (is_array($tmp)) {
            $mtime = $tmp[1];
            $lifetime = $tmp[2];
            
            return array(
                'expire' => $mtime + $lifetime,
                'tags'   => $tmp[3],
                'mtime'  => $mtime
            );
        }
        
        return false;
    }

    /**
     * Give (if possible) an extra lifetime to the given cache id
     *
     * @param string $id cache id
     * @param int $extraLifetime
     * @return boolean true if ok
     */
    public function touch($id, $extraLifetime)
    {
        $tmp = $this->_redis->get($id);
        
        if (is_array($tmp)) {
            $data = $tmp[0];
            $mtime = $tmp[1];
            $lifetime = $tmp[2];
            $tags = $tmp[3];
            
            $newLifetime = $lifetime - (time() - $mtime) + $extraLifetime;
            if ($newLifetime <=0) {
                return false;
            }
            
            $result = $this->_redis->setex($id, $newLifetime, array($data, time(), $newLifetime, $tags));
            
            return $result;
        }
        
        return false;
    }

    /**
     * Return an associative array of capabilities (booleans) of the backend
     *
     * The array must include these keys :
     * - automatic_cleaning (is automating cleaning necessary)
     * - tags (are tags supported)
     * - expired_read (is it possible to read expired cache records
     *                 (for doNotTestCacheValidity option for example))
     * - priority does the backend deal with priority when saving
     * - infinite_lifetime (is infinite lifetime can work with this backend)
     * - get_list (is it possible to get the list of cache ids and the complete list of tags)
     *
     * @return array associative of with capabilities
     */
    public function getCapabilities()
    {
        return array(
            'automatic_cleaning' => false,
            'tags'               => true,
            'expired_read'       => false,
            'priority'           => false,
            'infinite_lifetime'  => true,
            'get_list'           => false
        );
    }

    /**
     * strip key prefix and tag prefix from key name
     * 
     * @param array $keys
     * @return array
     */
    protected function _removeTagPrefix(array $keys)
    {
        $keyPrefix = $this->_redis->getOption(Redis::OPT_PREFIX);
        
        $pos = strlen($keyPrefix) + strlen(self::TAG_PREFIX);
        
        $result = array();
        
        foreach ($keys as $key) {
            $result[] = substr($key, $pos);
        }
        
        return $result;
    }    
}
