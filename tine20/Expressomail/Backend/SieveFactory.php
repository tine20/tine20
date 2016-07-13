<?php
/**
 * factory class for sieve backends
 * 
 * @package     Expressomail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * An instance of the sieve backend class should be created using this class
 * 
 * @package     Expressomail
 */
class Expressomail_Backend_SieveFactory
{
    /**
     * Default sieve backend name
     */
    const SIEVE = 'Sieve';

    /**
     * Default sieve backend class
     */
    const EXPRESSOMAIL_BACKEND_SIEVE = 'Expressomail_Backend_Sieve';

    /**
     * backend object instances
     */
    private static $_backends = array();

    /**
     * Available backends to sieve
     * @var array
     */
    protected static $_availableBackends = array(
        self::SIEVE => self::EXPRESSOMAIL_BACKEND_SIEVE
    );
    
    /**
     * factory function to return a selected account/imap backend class
     *
     * @param   string|Expressomail_Model_Account $_accountId
     * @return  Expressomail_Backend_Sieve
     */
    static public function factory($_accountId)
    {
        $accountId = ($_accountId instanceof Expressomail_Model_Account) ? $_accountId->getId() : $_accountId;
        
        if (! isset(self::$_backends[$accountId])) {
            $account = ($_accountId instanceof Expressomail_Model_Account) ? $_accountId : Expressomail_Controller_Account::getInstance()->get($accountId);
                    
            // get imap config from account to connect with sieve server
            $sieveConfig = $account->getSieveConfig();
            
            // we need to instantiate a new sieve backend
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Connecting to server ' . $sieveConfig['host'] . ':' . $sieveConfig['port'] 
                . ' (secure: ' . ((array_key_exists('ssl', $sieveConfig) && $sieveConfig['ssl'] !== FALSE) ? $sieveConfig['ssl'] : 'none') 
                . ') with user ' . $sieveConfig['username']);

            $expressomailConfig = Expressomail_Config::getInstance();
            $sieveBackendDefinition = $expressomailConfig->getDefinition(Expressomail_Config::SIEVEBACKEND);

            $backendClassName = self::$_availableBackends[$sieveBackendDefinition['default']];
            $expressomailSettings = $expressomailConfig->get(Expressomail_Config::EXPRESSOMAIL_SETTINGS);
            $backendName = isset($expressomailSettings[Expressomail_Config::SIEVEBACKEND]) ?
                    $expressomailSettings[Expressomail_Config::SIEVEBACKEND] :
                    $sieveBackendDefinition['default'];
            if($sieveBackendName != $sieveBackendDefinition['default']) {
                if(Tinebase_Helper::checkClassExistence(self::$_availableBackends[$backendName], true)) {
                    $backendClassName = self::$_availableBackends[$backendName];
                }
            }

            self::$_backends[$accountId] = new $backendClassName($sieveConfig);
        }
        
        return self::$_backends[$accountId];
    }

    /**
     * Adds a custom backend to sieve
     * @param string $backendName
     * @param string $backendClassName
     * @throws Tinebase_Exception_InvalidArgument
     */
    public static function addCustomBackend($backendName, $backendClassName)
    {
        if(array_key_exists($backendName, self::$_availableBackends)) {
            Tinebase_Core::getLogger()->err(__METHOD__ . "::" . __LINE__ . ":: Backend \"$backendName\" already added");
            throw new Tinebase_Exception_InvalidArgument("Backend \"$backendName\" already added");
        }

        if(in_array($backendClassName, self::$_availableBackends)) {
            Tinebase_Core::getLogger()->err(__METHOD__ . "::" . __LINE__ . ":: Backend class \"$backendClassName\" already added");
            throw new Tinebase_Exception_InvalidArgument("Backend class \"$backendClassName\" already added");
        }

        self::$_availableBackends[$backendName] = $backendClassName;
    }

    /**
     * Get all available backends
     * @return array
     */
    public static function getAvailableBackends()
    {
        return self::$_availableBackends;
    }
}
