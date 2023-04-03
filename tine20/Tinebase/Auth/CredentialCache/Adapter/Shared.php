<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2019-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * credential cache adapter for shared accounts based on config adapter
 *
 * @package     Tinebase
 * @subpackage  Auth
 */
class Tinebase_Auth_CredentialCache_Adapter_Shared implements Tinebase_Auth_CredentialCache_Adapter_Interface
{
    /**
     * config key const
     */
    const CONFIG_KEY = Tinebase_Config::CREDENTIAL_CACHE_SHARED_KEY;
    protected const MAX_KEY_LENGTH = 24;

    /**
     * setCache() - persists cache
     *
     * @param  Tinebase_Model_CredentialCache $_cache
     */
    public function setCache(Tinebase_Model_CredentialCache $_cache)
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' must never be called');
    }

    /**
     * getCache() - get the credential cache
     *
     * @return NULL|Tinebase_Model_CredentialCache
     */
    public function getCache()
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' must never be called');
    }

    /**
     * resetCache() - resets the cache
     */
    public function resetCache()
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' must never be called');
    }

    /**
     * getDefaultKey() - get default cache key
     * @return string
     * @throws Tinebase_Exception_NotFound
     */
    public function getDefaultKey()
    {
        return self::getKey();
    }

    /**
     * getDefaultId() - get default cache id
     *
     * @return string
     */
    public function getDefaultId()
    {
        return Tinebase_Record_Abstract::generateUID();
    }

    /**
     * @return string
     */
    public static function getKey(): string
    {
        if (empty($key = Tinebase_Config::getInstance()->{self::CONFIG_KEY})) {
            throw new Tinebase_Exception_UnexpectedValue(self::CONFIG_KEY . ' not set in config!');
        }

        if (strlen($key) > self::MAX_KEY_LENGTH) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                __METHOD__ . '::' . __LINE__ . ' ' . self::CONFIG_KEY . ' is longer than '
                . self::MAX_KEY_LENGTH . ' chars');
            $key = substr($key, 0, self::MAX_KEY_LENGTH);
        }

        return $key;
    }

    public static function setRandomKeyInConfig()
    {
        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
            . ' Auto generated CREDENTIAL_CACHE_SHARED_KEY was saved to db. For enhanced security'
            . ' you should move it to a config file to not be part of a db dump.');
        Tinebase_Config::getInstance()->{Tinebase_Config::CREDENTIAL_CACHE_SHARED_KEY} =
            Tinebase_Record_Abstract::generateUID(self::MAX_KEY_LENGTH);
    }
}
