<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class for caching credentials
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
    protected $_modelName = 'Tinebase_Model_CredentialCache';
    
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
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}
    
    /**
     * the constructor
     * 
     * @param Zend_Db_Adapter_Abstract $_db (optional)
     * @param array $_options (optional)
     */
    public function __construct($_dbAdapter = NULL, $_options = array()) 
    {
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
     * @param  string $_username
     * @param  string $_password
     * @param  string $_key [optional]
     * @param  boolean $_saveInSession
     * @return Tinebase_Model_CredentialCache
     */
    public function cacheCredentials($username, $password, $key = NULL, $persist = FALSE)
    {
        $key = ($key !== NULL) ? $key : $this->_cacheAdapter->getDefaultKey();
        
        $cache = new Tinebase_Model_CredentialCache(array(
            'id'            => $this->_cacheAdapter->getDefaultId(),
            'key'           => substr($key, 0, 24),
            'username'      => $username,
            'password'      => $password,
            'creation_time' => Tinebase_DateTime::now(),
            'valid_until'   => Tinebase_DateTime::now()->addMonth(1)
        ), true, false);
        $cache->convertDates = true;
        
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
        $session = Tinebase_Core::getSession();
        if ($session && is_object($session)) {
            $session->{self::SESSION_NAMESPACE}[$cache->getId()] = $cache;
        }
    }
    
    /**
     * persist cache record (in db)
     * -> needs to check if entry exists (some adapters can have static ids)
     * 
     * @param Tinebase_Model_CredentialCache $cache
     * @param boolean $_saveInSession
     */
    protected function _persistCache(Tinebase_Model_CredentialCache $cache, $_saveInSession = TRUE)
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
     * @param Tinebase_Model_CredentialCache
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function getCachedCredentials($_cache)
    {
        if (! $_cache || $_cache === NULL || ! $_cache instanceof Tinebase_Model_CredentialCache) {
            throw new Tinebase_Exception_InvalidArgument('No valid Tinebase_Model_CredentialCache given!');
        }
        
        if (! ($_cache->username && $_cache->password)) {
            $savedCache = $this->_getCache($_cache->getId());
            $_cache->setFromArray($savedCache->toArray());
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
        $session = Tinebase_Core::getSession();
        $credentialSessionCache = ($session && is_object($session)) ? $session->{self::SESSION_NAMESPACE} : NULL;
        if (isset($credentialSessionCache) && isset($credentialSessionCache[$id])) {
            $result = $credentialSessionCache[$id];
        } else {
            $result = $this->get($id);
            $this->_saveInSession($result);
        }
        
        return $result;
    }
    
    /**
     * encryptes username and password into cache
     *
     * @param  Tinebase_Model_CredentialCache $_cache
     * @return void
     * 
     * @todo check which cipher to use for encryption
     */
    protected function _encrypt($_cache)
    {
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', 'cbc', '');
        mcrypt_generic_init($td, $_cache->key, substr($_cache->getId(), 0, 16));
        
        $data = array_merge($_cache->toArray(), array(
            'username' => $_cache->username,
            'password' => $_cache->password,
        ));
        $_cache->cache = base64_encode(mcrypt_generic($td, Zend_Json::encode($data)));
        
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
    }
    
    /**
     * decryptes username and password
     *
     * @param  Tinebase_Model_CredentialCache $_cache
     * @return void
     */
    protected function _decrypt($_cache)
    {
        $encryptedData = base64_decode($_cache->cache);
        
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', 'cbc', '');
        mcrypt_generic_init($td, $_cache->key, substr($_cache->getId(), 0, 16));
        
        $cacheData = Zend_Json::decode(trim(mdecrypt_generic($td, $encryptedData)));
        
        $_cache->username = $cacheData['username'];
        $_cache->password = $cacheData['password'];
        
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
    }
    
    /**
     * remove all credential cache records before $_date
     * 
     * @param Tinebase_DateTime|string $_date
     */
    public function clearCacheTable($_date = NULL)
    {
        $dateString = ($_date instanceof Tinebase_DateTime) ? $_date->format(Tinebase_Record_Abstract::ISO8601LONG) : $_date;
        $dateWhere = ($dateString === NULL) 
            ? $this->_db->quoteInto($this->_db->quoteIdentifier('valid_until') . ' < ?', Tinebase_DateTime::now()->format(Tinebase_Record_Abstract::ISO8601LONG)) 
            : $this->_db->quoteInto($this->_db->quoteIdentifier('creation_time') . ' < ?', $dateString);
        $where = array($dateWhere);
             
        if (Setup_Controller::getInstance()->isInstalled('Felamimail')) {
            // delete only records that are not related to email accounts
            $fmailIds = $this->_getFelamimailCredentialIds();
            if (! empty($fmailIds)) {
                $where[] = $this->_db->quoteInto($this->_db->quoteIdentifier('id') .' NOT IN (?)', $fmailIds);
            }
        }
        
        $tableName = $this->getTablePrefix() . $this->getTableName();
        $this->_db->delete($tableName, $where);
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
