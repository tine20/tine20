<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class for caching credentials
 *  
 * @package     Tinebase
 * @subpackage  Auth
 */
class Tinebase_Auth_CredentialCache extends Tinebase_Backend_Sql_Abstract implements Tinebase_Controller_Interface
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'credential_cache';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = Tinebase_Model_CredentialCache::class;
    
    /**
     * holds credential cache id/key pair for current request
     *
     * @var array
     */
    protected static $_credentialcacheid = NULL;
    
    /**
     * credential cache adapter
     * 
     * @var Tinebase_Auth_CredentialCache_Adapter_Interface
     */
    protected $_cacheAdapter = NULL;
    
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Auth_CredentialCache
     */
    private static $_instance = NULL;
    
    const SESSION_NAMESPACE = 'credentialCache';
    const CIPHER_ALGORITHM = 'aes-256-ctr';
    const HASH_ALGORITHM = 'sha256';
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * the constructor
     *
     * @param Zend_Db_Adapter_Abstract $_dbAdapter (optional)
     * @param array $_options (optional)
     * @throws Tinebase_Exception_SystemGeneric
     */
    public function __construct($_dbAdapter = NULL, $_options = array()) 
    {
        if (! extension_loaded('openssl')) {
            throw new Tinebase_Exception_SystemGeneric('openssl extension required');
        }
        if (!in_array(self::CIPHER_ALGORITHM, openssl_get_cipher_methods(true)))
        {
            throw new Tinebase_Exception_SystemGeneric('cipher algorithm: ' . self::CIPHER_ALGORITHM . ' not supported');
        }
        if (!in_array(self::HASH_ALGORITHM, openssl_get_md_methods(true)))
        {
            throw new Tinebase_Exception_SystemGeneric('hash algorithm: ' . self::HASH_ALGORITHM . ' not supported');
        }

        parent::__construct($_dbAdapter, $_options);
        
        // set default adapter
        $config = Tinebase_Core::getConfig();
        $adapter = ($config->{Tinebase_Auth_CredentialCache_Adapter_Config::CONFIG_KEY}) ? 'Config' : 'Cookie';
        $this->setCacheAdapter($adapter);
    }
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Auth_CredentialCache
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Auth_CredentialCache();
        }
        
        return self::$_instance;
    }
    
    /**
     * set cache adapter
     * 
     * @param string $_adapter
     */
    public function setCacheAdapter($_adapter = 'Cookie')
    {
        $adapterClass = 'Tinebase_Auth_CredentialCache_Adapter_' . $_adapter;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Using credential cache adapter: ' . $adapterClass);
        $this->_cacheAdapter = new $adapterClass();
    }
    
    /**
     * get cache adapter
     * 
     * @return Tinebase_Auth_CredentialCache_Adapter_Interface
     */
    public function getCacheAdapter()
    {
        return $this->_cacheAdapter;
    }
    
    /**
     * caches given credentials
     *
     * @param  string $username
     * @param  string $password
     * @param  string $key [optional]
     * @param  boolean $persist
     * @param  Tinebase_DateTime $validUntil
     * @return Tinebase_Model_CredentialCache
     */
    public function cacheCredentials($username, $password, $key = NULL, $persist = FALSE, $validUntil = null)
    {
        $key = ($key !== NULL) ? $key : $this->_cacheAdapter->getDefaultKey();
        
        $cache = new Tinebase_Model_CredentialCache(array(
            'id'            => $this->_cacheAdapter->getDefaultId(),
            'key'           => substr($key, 0, 24),
            'username'      => $username,
            'password'      => $password,
            'creation_time' => Tinebase_DateTime::now(),
            'valid_until'   => $validUntil ?: Tinebase_DateTime::now()->addMonth(1)
        ), true, false);
        $cache->setConvertDates(true);
        
        $this->_encrypt($cache);
        $this->_saveInSession($cache);
        if ($persist) {
            $this->_persistCache($cache);
        }
        
        return $cache;
    }
    
    /**
     * save cache record in session
     * 
     * @param Tinebase_Model_CredentialCache $cache
     */
    protected function _saveInSession(Tinebase_Model_CredentialCache $cache)
    {
        try {
            $session = Tinebase_Session::getSessionNamespace();
            
            $session->{self::SESSION_NAMESPACE}[$cache->getId()] = $cache->toArray();
        } catch (Zend_Session_Exception $zse) {
            // nothing to do
        }
    }
    
    /**
     * persist cache record (in db)
     * -> needs to check if entry exists (some adapters can have static ids)
     * 
     * @param Tinebase_Model_CredentialCache $cache
     */
    protected function _persistCache(Tinebase_Model_CredentialCache $cache)
    {
        try {
            $this->create($cache);
        } catch (Zend_Db_Statement_Exception $zdse) {
            $this->update($cache);
        }
    }
    
    /**
     * returns cached credentials
     *
     * @param Tinebase_Model_CredentialCache $_cache
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function getCachedCredentials(Tinebase_Model_CredentialCache $_cache)
    {
        if (! $_cache || $_cache === NULL || ! $_cache instanceof Tinebase_Model_CredentialCache) {
            throw new Tinebase_Exception_InvalidArgument('No valid Tinebase_Model_CredentialCache given!');
        }
        
        if (! ($_cache->username && $_cache->password)) {
            $savedCache = $this->_getCache($_cache->getId())->toArray();
            $_cache->setFromArray($savedCache);
            $this->_decrypt($_cache);
        }
    }
    
    /**
     * get cache record (try to find in session first, then DB)
     * 
     * @param string $id
     * @return Tinebase_Model_CredentialCache
     */
    protected function _getCache($id)
    {
        try {
            $session = Tinebase_Session::getSessionNamespace();
            
            $credentialSessionCache = $session->{self::SESSION_NAMESPACE};
            
            if (isset($credentialSessionCache) && isset($credentialSessionCache[$id])) {
                return new Tinebase_Model_CredentialCache($credentialSessionCache[$id]);
            }
            
        } catch (Zend_Session_Exception $zse) {
            // nothing to do
        }

        /** @var Tinebase_Model_CredentialCache $result */
        $result = $this->get($id);
        $this->_saveInSession($result);
        
        return $result;
    }

    /**
     * @param string $_data
     * @param string $_key
     * @return string
     * @throws Exception
     * @throws Tinebase_Exception
     */
    public static function encryptData($_data, $_key)
    {
        // there was a bug with openssl_random_pseudo_bytes but its fixed in all major PHP versions, so we use it. People have to update their PHP versions
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::CIPHER_ALGORITHM), $secure);
        if (!$secure) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                __METHOD__ . '::' . __LINE__ . ' openssl_random_pseudo_bytes returned weak random bytes!');
            if (function_exists('random_bytes')) {
                $iv = random_bytes(openssl_cipher_iv_length(self::CIPHER_ALGORITHM));
            } elseif (function_exists('mcrypt_create_iv')) {
                $iv = mcrypt_create_iv(openssl_cipher_iv_length(self::CIPHER_ALGORITHM), MCRYPT_DEV_URANDOM);
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                    __METHOD__ . '::' . __LINE__ . ' openssl_random_pseudo_bytes returned weak random bytes and we could not find a method better suited!');
            }
        }

        $hash = openssl_digest($_key, self::HASH_ALGORITHM, true);

        if (false === ($encrypted = openssl_encrypt($_data, self::CIPHER_ALGORITHM, $hash, OPENSSL_RAW_DATA, $iv))) {
            throw new Tinebase_Exception('encryption failed: ' . openssl_error_string());
        }

        return base64_encode($iv . $encrypted);
    }

    /**
     * @param $_data
     * @param $_key
     * @return bool|string
     */
    public static function decryptData($_data, $_key)
    {
        if (false === ($encryptedData = base64_decode($_data))) {
            return false;
        }
        $ivLength = openssl_cipher_iv_length(self::CIPHER_ALGORITHM);

        if (strlen($encryptedData) < $ivLength)
        {
            return false;
        }

        $iv = substr($encryptedData, 0, $ivLength);
        $encryptedData = substr($encryptedData, $ivLength);
        $hash = openssl_digest($_key, self::HASH_ALGORITHM, true);

        return openssl_decrypt($encryptedData, self::CIPHER_ALGORITHM, $hash, OPENSSL_RAW_DATA, $iv);
    }

    /**
     * encrypts username and password into cache
     *
     * reference implementation: https://github.com/ioncube/php-openssl-cryptor
     *
     * @param  Tinebase_Model_CredentialCache $_cache
     * @throws Tinebase_Exception
     */
    protected function _encrypt($_cache)
    {
        $data = Zend_Json::encode(array_merge($_cache->toArray(), array(
            'username' => $_cache->username,
            'password' => $_cache->password,
        )));

        $_cache->cache = static::encryptData($data, $_cache->key);
    }

    /**
     * decrypts username and password
     *
     * @param  Tinebase_Model_CredentialCache $_cache
     * @throws Tinebase_Exception_NotFound
     */
    protected function _decrypt($_cache)
    {
        $persistAgain = false;

        if (false === ($jsonEncodedData = static::decryptData($_cache->cache, $_cache->key))
            || ! Tinebase_Helper::is_json(trim($jsonEncodedData)))
        {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                __METHOD__ . '::' . __LINE__ . ' lets try to decode it with the old algorithm, if successful, persist again if this is a persistent cache');

            if (false !== ($jsonEncodedData = openssl_decrypt(
                    base64_decode($_cache->cache), 'AES-128-CBC',
                    $_cache->key,
                    OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING,
                    substr($_cache->getId(), 0, 16)
                ))) {
                $persistAgain = true;
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                    __METHOD__ . '::' . __LINE__ . ' decryption failed');
                throw new Tinebase_Exception_NotFound('decryption failed: ' . openssl_error_string());
            }
        }

        try {
            $cacheData = Tinebase_Helper::jsonDecode(trim($jsonEncodedData));
        } catch(Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                __METHOD__ . '::' . __LINE__ . ' persisted cache data is no valid json');
            throw new Tinebase_Exception_NotFound('persisted cache data is no valid json');
        }

        if (! isset($cacheData['username']) && ! isset($cacheData['password'])) {
            throw new Tinebase_Exception_NotFound('could not find valid credential cache');
        }

        $_cache->username = $cacheData['username'];
        $_cache->password = $cacheData['password'];

        if (true === $persistAgain) {
            try {
                $this->get($_cache->getId());
                $this->_encrypt($_cache);
                $this->_persistCache($_cache);
            } catch(Tinebase_Exception_NotFound $tenf) {
                // shouldn't happen anyway, just to be save.
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                    __METHOD__ . '::' . __LINE__ . ' cache is not in DB, so we don\'t persist it again, just continue gracefully');
            }
        }
    }
    
    /**
     * remove all credential cache records before $_date
     * 
     * @param Tinebase_DateTime|string $_date
     * @return bool
     */
    public function clearCacheTable($_date = NULL)
    {
        $dateString = ($_date instanceof Tinebase_DateTime) ? $_date->format(Tinebase_Record_Abstract::ISO8601LONG) : $_date;
        $dateWhere = ($dateString === NULL) 
            ? $this->_db->quoteInto($this->_db->quoteIdentifier('valid_until') . ' < ?', Tinebase_DateTime::now()->format(Tinebase_Record_Abstract::ISO8601LONG)) 
            : $this->_db->quoteInto($this->_db->quoteIdentifier('creation_time') . ' < ?', $dateString);
        $where = array($dateWhere);

        // TODO should be handled with looong "valid_until" until time
        if (Tinebase_Application::getInstance()->isInstalled('Felamimail')) {
            // delete only records that are not related to email accounts
            $fmailIds = $this->_getFelamimailCredentialIds();
            if (! empty($fmailIds)) {
                $where[] = $this->_db->quoteInto($this->_db->quoteIdentifier('id') .' NOT IN (?)', $fmailIds);
            }
        }
        
        $tableName = $this->getTablePrefix() . $this->getTableName();
        $this->_db->delete($tableName, $where);

        return true;
    }
    
    /**
     * returns all credential ids that are used in felamimail
     * 
     * @return array
     */
    protected function _getFelamimailCredentialIds()
    {
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'felamimail_account', array('credentials_id', 'smtp_credentials_id'));
        $stmt = $this->_db->query($select);
        $result = $stmt->fetchAll(Zend_Db::FETCH_NUM);
        $fmailIds = array();
        foreach ($result as $credentialIds) {
            $fmailIds = array_merge($fmailIds, $credentialIds);
        }
        $fmailIds = array_unique($fmailIds);
        
        return $fmailIds;
    }
}
