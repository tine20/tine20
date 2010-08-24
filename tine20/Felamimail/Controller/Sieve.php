<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        add __destruct with $_backend->logout()?
 */

/**
 * Sieve controller for Felamimail
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_Sieve extends Tinebase_Controller_Abstract
{
    /**
     * script name
     *
     */
    protected $_scriptName = 'felamimail2.0';
    
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Felamimail';
    
    /**
     * Sieve Script backend
     *
     * @var Felamimail_Sieve_Script
     */
    protected $_scriptBackend = NULL;
    
    /**
     * Sieve backend
     *
     * @var Felamimail_Backend_Sieve
     */
    protected $_backend = NULL;
    
    /**
     * holds the instance of the singleton
     *
     * @var Felamimail_Controller_Sieve
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() {
        $this->_currentAccount = Tinebase_Core::getUser();
        $this->_scriptBackend = new Felamimail_Sieve_Script();
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
     * @return Felamimail_Controller_Sieve
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Felamimail_Controller_Sieve();
        }
        
        return self::$_instance;
    }
    
    /**
     * get vacation for account
     * 
     * @param string|Felamimail_Model_Account $_accountId
     * @return Felamimail_Model_Sieve_Vacation
     */
    public function getVacation($_accountId)
    {
        $this->_setSieveBackendAndAuthenticate($_accountId);
        
        $script = $this->_getSieveScript();
        $vacation = ($script !== NULL) ? $script->getVacation() : NULL;
        
        $result = new Felamimail_Model_Sieve_Vacation(array(
            'id'    => ($_accountId instanceOf Felamimail_Model_Account) ? $_accountId->getId() : $_accountId
        ));
            
        if ($vacation !== NULL) {
            $result->setFromFSV($vacation);
        }
        
        return $result;
    }
    
    /**
     * get sieve script for account
     * 
     * @return NULL|Felamimail_Sieve_Script
     */
    protected function _getSieveScript()
    {
        $result = NULL;
        $scripts = $this->_backend->listScripts();

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Getting list of SIEVE scripts: ' . print_r($scripts, TRUE));
   
        if (count($scripts) > 0 && array_key_exists($this->_scriptName, $scripts)) {
        
            $scriptName = $scripts[$this->_scriptName]['name'];
            
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Get SIEVE script: ' . $scriptName);
            
            $script = $this->_backend->getScript($scriptName);
            if ($script) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Got SIEVE script: ' . $script);
                $result = new Felamimail_Sieve_Script($script);
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Could not get SIEVE script: ' . $scriptName);
            }
            
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' No relevant SIEVE scripts found.');
        }
        
        return $result;
    }
    
    /**
     * init and connect to sieve backend + authenticate with imap user of account
     * 
     * @param string|Felamimail_Model_Account $_accountId
     * @throws Felamimail_Exception
     */
    protected function _setSieveBackendAndAuthenticate($_accountId)
    {
        if (empty($_accountId)) {
            throw new Felamimail_Exception('No account id given.');
        }
        
        $this->_backend = Felamimail_Backend_SieveFactory::factory($_accountId);
    }
    
    /**
     * set vacation for account
     * 
     * @param Felamimail_Model_Sieve_Vacation $_vacation
     * @return Felamimail_Model_Sieve_Vacation
     */
    public function setVacation(Felamimail_Model_Sieve_Vacation $_vacation)
    {
        $accountId = $_vacation->getId();
        $this->_setSieveBackendAndAuthenticate($accountId);
        
        $fsv = $_vacation->getFSV();
        
        $script = $this->_getSieveScript();
        if ($script === NULL) {
            $script = new Felamimail_Sieve_Script();
        }
        $script->setVacation($fsv);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Put updated vacation SIEVE script ' . $this->_scriptName);
        
        $this->_putScript($accountId, $script);
        
        return $this->getVacation($accountId);
    }
    
    /**
     * put updated sieve script
     * 
     * @param string|Felamimail_Model_Account $_accountId
     * @param Felamimail_Sieve_Script $_script
     */
    protected function _putScript($_accountId, $_script)
    {
        $scriptToPut = $_script->getSieve();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $scriptToPut);
        
        $this->_backend->putScript($this->_scriptName, $scriptToPut);
        $this->activateScript($_accountId);
    }

    /**
     * set sieve script name
     * 
     * @param string $_name
     */
    public function setScriptName($_name)
    {
        $this->_scriptName = $_name;
    }
    
    /**
     * delete sieve script
     * 
     * @param string|Felamimail_Model_Account $_accountId
     */
    public function deleteScript($_accountId)
    {
        $this->_setSieveBackendAndAuthenticate($_accountId);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Delete SIEVE script ' . $this->_scriptName);
        
        $this->_backend->deleteScript($this->_scriptName);
    }

    /**
     * activate sieve script
     * 
     * @param string|Felamimail_Model_Account $_accountId
     */
    public function activateScript($_accountId)
    {
        $this->_setSieveBackendAndAuthenticate($_accountId);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Activate vacation SIEVE script ' . $this->_scriptName);
        $this->_backend->setActive($this->_scriptName);
    }
    
    /**
     * get name of active script for account
     * 
     * @param string|Felamimail_Model_Account $_accountId
     * @return string|NULL
     */
    public function getActiveScriptName($_accountId)
    {
        $this->_setSieveBackendAndAuthenticate($_accountId);
        
        $scripts = $this->_backend->listScripts();
        
        $result = NULL;
        foreach ($scripts as $scriptname => $values) {
            if ($values['active'] == 1) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Found active script: ' . $scriptname);
                $result = $scriptname;
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Inactive script: ' . $scriptname);
            }
        }
        
        return $result;
    }
    
    /**
     * get rules for account
     * 
     * @param string $_accountId
     * @return Tinebase_Record_RecordSet of Felamimail_Model_Sieve_Rule
     */
    public function getRules($_accountId)
    {
        $result = new Tinebase_Record_RecordSet('Felamimail_Model_Sieve_Rule');
        
        $this->_setSieveBackendAndAuthenticate($_accountId);
        
        $script = $this->_getSieveScript();
        if ($script !== NULL) {
            foreach ($script->getRules() as $fsr) {
                $rule = new Felamimail_Model_Sieve_Rule();
                $rule->setFromFSR($fsr);
                $result->addRecord($rule);
            }
        }
        
        return $result;
    }
    
    /**
     * set rules for account
     * 
     * @param string $_accountId
     * @param Tinebase_Record_RecordSet $_rules (Felamimail_Model_Sieve_Rule)
     * @return Tinebase_Record_RecordSet
     */
    public function setRules($_accountId, Tinebase_Record_RecordSet $_rules)
    {
        $this->_setSieveBackendAndAuthenticate($_accountId);
        
        $script = $this->_getSieveScript();
        if ($script === NULL) {
            $script = new Felamimail_Sieve_Script();
        } else {
            $script->clearRules();
        }
        
        foreach ($_rules as $rule) {
            $fsr = $rule->getFSR();
            $script->addRule($fsr);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Put updated rules SIEVE script ' . $this->_scriptName);
        
        $this->_putScript($_accountId, $script);
        
        return $this->getRules($_accountId);
    }
}
