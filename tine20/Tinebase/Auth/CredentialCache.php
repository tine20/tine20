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
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Using credential cache apapter: ' . $adapterClass);
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
     * @return Tinebase_Model_CredentialCache
     */
    public function cacheCredentials($_username, $_password, $_key = NULL)
    {
        $key = ($_key !== NULL) ? $_key : $this->_cacheAdapter->getDefaultKey();
        
        $cache = new Tinebase_Model_CredentialCache(array(
            'id'            => $this->_cacheAdapter->getDefaultId(),
            'key'           => substr($key, 0, 24),
            'username'      => $_username,
            'password'      => $_password,
            'creation_time' => Tinebase_DateTime::now(),
            'valid_until'   => Tinebase_DateTime::now()->addMonth(1)
        ), true, false);
        $cache->convertDates = true;
        
        $this->_encrypt($cache);
        
        // need to check if entry exists (some adapters can have static ids)
        try {
            $this->create($cache);
        } catch (Zend_Db_Statement_Exception $zdse) {
            $this->update($cache);
        }
        
        return $cache;
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
            $_cache->setFromArray($this->get($_cache->getId())->toArray());
            $this->_decrypt($_cache);
        }
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
            ? $this->_db->quoteInto('valid_until < ?', Tinebase_DateTime::now()->format(Tinebase_Record_Abstract::ISO8601LONG)) 
            : $this->_db->quoteInto('creation_time < ?', $dateString);
            
        $tableName = $this->getTablePrefix() . $this->getTableName();
        if (Setup_Controller::getInstance()->isInstalled('Felamimail')) {
            // delete only records that are not related to email accounts
            $where = SQL_TABLE_PREFIX . 'felamimail_account.credentials_id IS NULL';
            if ($dateWhere) {
                $where .= ' AND ' . $dateWhere;
            }
            $this->_db->query('DELETE ' . $tableName . ' FROM ' . $tableName .
                ' LEFT JOIN ' . $this->getTablePrefix() . 'felamimail_account ON ' . $tableName . '.id = ' . 
                    $this->getTablePrefix() . 'felamimail_account.credentials_id' .
                ' WHERE ' . $where);
        } else {
            $this->_db->delete($tableName, $dateWhere);            
        }
    }
}
