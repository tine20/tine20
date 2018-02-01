<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Cache
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2015-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 *
 * TODO refactor persistent cache visiblity handling as this is not quite easy to read/understand
 */

/**
 * per request in memory cache class
 * 
 * a central place to manage in class caches
 * 
 * @package     Tinebase
 * @subpackage  Cache
 */
class Tinebase_Cache_PerRequest 
{
    /**
     * persistent cache entry only visible for one user
     */
    const VISIBILITY_PRIVATE = 'private';

    /**
     * persistent cache entry visible for all users
     */
    const VISIBILITY_SHARED  = 'shared';
    
    /**
     * the cache
     *
     * @var array
     */
    protected $_inMemoryCache = array();
    
    /**
     * use Zend_Cache as fallback
     *
     * @var bool
     */
    protected $_usePersistentCache = false;

    /**
     * methods which should use persistent caching (by class)
     * 
     * @var array
     */
    protected $_persistentCacheMethods = array();
    
    /**
     * verbose log
     *
     * @var bool
     *
     * TODO allow to configure this
     */
    protected $_verboseLog = false;
    
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Cache_PerRequest
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct()
    {
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
    }
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Cache_PerRequest
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Cache_PerRequest();
        }
        
        return self::$_instance;
    }
    
    /**
     * set/get persistent cache usage
     *
     * @param  boolean $setTo
     * @return boolean
     */
    public function usePersistentCache($setTo = NULL)
    {
        $currValue = $this->_usePersistentCache;
        if ($setTo !== NULL) {
            $this->_usePersistentCache = (bool)$setTo;
        }
        
        return $currValue;
    }

    /**
     * configure which methods should use persistent caching
     * 
     * @param string $class
     * @param array $methods
     */
    public function setPersistentCacheMethods($class, $methods)
    {
        $this->_persistentCacheMethods[$class] = $methods;
    }
    
    /**
     * save entry in cache
     * 
     * @param  string   $class
     * @param  string   $method
     * @param  string   $cacheId
     * @param  string   $value
     * @param  string|boolean   $usePersistentCache
     * @param  integer|boolean  $persistentCacheTTL
     * @return Tinebase_Cache_PerRequest
     */
    public function save($class, $method, $cacheId, $value, $usePersistentCache = false, $persistentCacheTTL = false)
    {
        $cacheId = sha1($cacheId);

        if (! $usePersistentCache) {
            $usePersistentCache = $this->_usePersistentCaching($class, $method);
        }

        // store in in-memory cache
        $this->_inMemoryCache[$class][$method][$cacheId] = array('value' => $value, 'usePersistentCache' => $usePersistentCache);

        if ($usePersistentCache !== false) {
            $persistentCache = Tinebase_Core::getCache();
            
            if ($persistentCache instanceof Zend_Cache_Core) {
                $persistentCacheId = $this->getPersistentCacheId($class, $method, $cacheId, /* visibility = */ $usePersistentCache);
                $persistentCache->save($value, $persistentCacheId, array(), $persistentCacheTTL);
            }
        }
        
        return $this;
    }
    
    /**
     * load entry from cache
     * 
     * @param string $class
     * @param string $method
     * @param string $cacheId
     * @param boolean|string $usePersistentCache
     * @throws Tinebase_Exception_NotFound
     * @return mixed
     */
    public function load($class, $method, $cacheId, $usePersistentCache = false)
    {
        if ($this->_verboseLog) {
            $traceException = new Exception();
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . " class/method/cacheid: $class $method $cacheId stack: " . $traceException->getTraceAsString());
        }
        
        $cacheId = sha1($cacheId);
        
        // lookup in in-memory cache
        if (isset($this->_inMemoryCache[$class]) &&
            isset($this->_inMemoryCache[$class][$method]) &&
            isset($this->_inMemoryCache[$class][$method][$cacheId])
        ) {
            return $this->_inMemoryCache[$class][$method][$cacheId]['value'];
        };

        if ($this->_verboseLog) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ... not found in class cache');
        }

        if (! $usePersistentCache) {
            $usePersistentCache = $this->_usePersistentCaching($class, $method);
        }

        if ($usePersistentCache !== false) {
            $persistentCache = Tinebase_Core::getCache();
            
            if ($persistentCache instanceof Zend_Cache_Core) {
                $persistentCacheId = $this->getPersistentCacheId($class, $method, $cacheId, /* visibility = */ $usePersistentCache);
                $value = $persistentCache->load($persistentCacheId);
                
                // false means no value found or the value of the cache entry is false
                // lets make an additional round trip to the cache to test for existence of cacheId
                if ($value === false) {
                    if (!$persistentCache->test($persistentCacheId)) {
                        if ($this->_verboseLog) {
                            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ... not found in zend cache');
                        }
                        
                        throw new Tinebase_Exception_NotFound('cacheId not found');
                    }
                    // race condition... is the value really false or did just somebody add the value to the cache?
                    $value = $persistentCache->load($persistentCacheId);
                    // it is still a race condition, load [T1], add [T2], test [T1], remove [T2], load [T1] ...
                    // but that seems rather extremely unlikely
                }
                
                $this->_inMemoryCache[$class][$method][$cacheId] = array('value' => $value, 'usePersistentCache' => $usePersistentCache);
                
                return $value;
            }
        }
        
        throw new Tinebase_Exception_NotFound('cacheId not found');
    }
    
    /**
     * reset cache entries
     * 
     * @param string $class
     * @param string $method
     * @param string $cacheId
     * @return Tinebase_Cache_PerRequest
     */
    public function reset($class = null, $method = null, $cacheId = null)
    {
        $persistentCache = Tinebase_Core::getCache();
        $cacheId = $cacheId ? sha1($cacheId) : $cacheId;
        
        // reset all cache entries
        if (empty($class)) {
            if ($persistentCache instanceof Zend_Cache_Core) {
                foreach ($this->_inMemoryCache as $class => $methods) {
                    foreach ($methods as $method => $cacheEntries) {
                        foreach ($cacheEntries as $cacheId => $cacheEntry) {
                            $this->_purgePersistentCacheEntry($persistentCache, $class, $method, $cacheId);
                        }
                    }
                }
            }
            $this->_inMemoryCache = array();
            return $this;
        }
        
        // reset all cache entries of the given class
        if (empty($method)) {
            if ($persistentCache instanceof Zend_Cache_Core && isset($this->_inMemoryCache[$class])) {
                foreach ($this->_inMemoryCache[$class] as $method => $cacheEntries) {
                    foreach ($cacheEntries as $cacheId => $cacheEntry) {
                        $this->_purgePersistentCacheEntry($persistentCache, $class, $method, $cacheId);
                    }
                }
            }
            
            $this->_inMemoryCache[$class] = array();
            return $this;
        }
        
        // reset all cache entries of the method of the given class
        if (empty($cacheId)) {
            if ($persistentCache instanceof Zend_Cache_Core && isset($this->_inMemoryCache[$class]) && isset($this->_inMemoryCache[$class][$method])) {
                foreach ($this->_inMemoryCache[$class][$method] as $cacheId => $cacheEntry) {
                    $this->_purgePersistentCacheEntry($persistentCache, $class, $method, $cacheId);
                }
            }
            
            $this->_inMemoryCache[$class][$method] = array();
            return $this;
        }

        // reset single cache entry
        if (isset($this->_inMemoryCache[$class]) &&
            isset($this->_inMemoryCache[$class][$method]) &&
            isset($this->_inMemoryCache[$class][$method][$cacheId])
        ) {
            if ($persistentCache instanceof Zend_Cache_Core) {
                $this->_purgePersistentCacheEntry($persistentCache, $class, $method, $cacheId);
            }
            
            unset($this->_inMemoryCache[$class][$method][$cacheId]);
        };

        return $this;
    }
    
    /**
     * 
     * @param Zend_Cache_Core $persistentCache
     * @param string $class
     * @param string $method
     * @param string $cacheId
     */
    protected function _purgePersistentCacheEntry(Zend_Cache_Core $persistentCache, $class, $method, $cacheId)
    {
        $cacheEntry = $this->_inMemoryCache[$class][$method][$cacheId];
        
        if ($cacheEntry['usePersistentCache'] !== false) {
            $persistentCache->remove($this->getPersistentCacheId($class, $method, $cacheId, $cacheEntry['usePersistentCache']));
        }
    }
    
    /**
     * check if persistent caching should be used for class/method
     *
     * -> returns false or visibility if persistent caching should be used
     * 
     * @param $class
     * @param $method
     * @return bool|string
     *
     * TODO allow to configure visibility in _persistentCacheMethods
     */
    protected function _usePersistentCaching($class, $method)
    {
        if ($this->_usePersistentCache) {
            return self::VISIBILITY_PRIVATE;
        }
        
        if (isset($this->_persistentCacheMethods[$class]) && in_array($method, $this->_persistentCacheMethods[$class])) {
            return self::VISIBILITY_PRIVATE;
        }
        
        return false;
    }

    /**
     * get cache id for persistent cache
     *
     * @param string $class
     * @param string $method
     * @param string $cacheId
     * @param string $visibilty
     * @return string
     */
    public function getPersistentCacheId($class, $method, $cacheId, $visibilty = self::VISIBILITY_PRIVATE)
    {
        if (!in_array($visibilty, array(self::VISIBILITY_PRIVATE, self::VISIBILITY_SHARED))) {
            $visibilty = self::VISIBILITY_PRIVATE;
        }
        
        $userId = null;
        
        if ($visibilty !== self::VISIBILITY_SHARED) {
            $userId = (is_object(Tinebase_Core::getUser())) ? Tinebase_Core::getUser()->getId() : 'NOUSER';
        }
        
        return sha1($class . $method . $cacheId . $userId);
    }
}
