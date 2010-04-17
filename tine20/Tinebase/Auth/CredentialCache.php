<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * class for caching credentials
 * 
 * @todo automatic garbage collection via cron
 */
class Tinebase_Auth_CredentialCache extends Tinebase_Backend_Sql_Abstract
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
     * caches given credentials
     *
     * @param  string $_username
     * @param  string $_password
     * @param  string $_key [optional]
     * @return Tinebase_Model_CredentialCache
     */
    public function cacheCredentials($_username, $_password, $_key = NULL)
    {
        $key = ($_key !== NULL) ? $_key : Tinebase_Record_Abstract::generateUID();
        
        $cache = new Tinebase_Model_CredentialCache(array(
            'id'        => Tinebase_Record_Abstract::generateUID(),
            'key'       => substr($key, 0, 24),
            'username'  => $_username,
            'password'  => $_password,
            'creation_time' => Zend_Date::now(),
            'valid_until'   => Zend_Date::now()->addMonth(1)
        ), true, false);
        $cache->convertDates = true;
        
        $this->_encrypt($cache);
        $this->create($cache);
        
        return $cache;
    }
    
    /**
     * returns cached credentials
     *
     * @param Tinebase_Model_CredentialCache
     * @trows Tinebase_Exception_InvalidArgument
     * @trows Tinebase_Exception_NotFound
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
        
        #$td = mcrypt_module_open(MCRYPT_TRIPLEDES, '', 'cbc', '');
        #mcrypt_generic_init($td, $_cache->key, substr($_cache->getId(), 0, 8));
        
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
        
        #$td = mcrypt_module_open(MCRYPT_TRIPLEDES, '', 'cbc', '');
        #mcrypt_generic_init($td, $_cache->key, substr($_cache->getId(), 0, 8));
        
        $cacheData = Zend_Json::decode(trim(mdecrypt_generic($td, $encryptedData)));
        
        $_cache->username = $cacheData['username'];
        $_cache->password = $cacheData['password'];
        
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
    }
}