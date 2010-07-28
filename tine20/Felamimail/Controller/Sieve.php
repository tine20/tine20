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
        
        $script = $this->_getSieveScript($_accountId);
        
        $result = new Felamimail_Model_Sieve_Vacation(array(
            'id'    => $_accountId
        ));
            
        if ($script !== NULL) {
            $result->setFromFSV($script->getVacation());
        }
        
        return $result;
    }
    
    /**
     * get sieve script for account
     * 
     * @param string $_accountId
     * @return NULL|Felamimail_Sieve_Script
     */
    protected function _getSieveScript($_accountId)
    {
        $scripts = $this->_backend->listScripts();

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Getting list of SIEVE scripts.');
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($scripts, TRUE));
   
        if (count($scripts) > 0 && array_key_exists($this->_scriptName, $scripts)) {
        
            $scriptName = $scripts[$this->_scriptName]['name'];
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Get Tine 2.0 script: ' . $scriptName);
            
            $script = $this->_backend->getScript($scriptName);
            //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Got sieve script: ' . $script);
            $result = new Felamimail_Sieve_Script($script);
            
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' No Tine 2.0 SIEVE scripts found.');
            
            $result = NULL;
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
        $this->_setSieveBackendAndAuthenticate($_vacation->getId());
        
        $fsv = $_vacation->getFSV();
        
        $script = $this->_getSieveScript($_vacation->getId());
        $fss = new Felamimail_Sieve_Script($script);
        $fss->setVacation($fsv);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Put updated vacation SIEVE script ' . $this->_scriptName);
        
        $scriptToPut = $fss->getSieve();
        //echo $scriptToPut;
        $this->_backend->putScript($this->_scriptName, $scriptToPut);
        
        return $this->getVacation($_vacation->getId());
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
     * @param string $_accountId
     */
    public function deleteScript($_accountId)
    {
        $this->_setSieveBackendAndAuthenticate($_accountId);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Delete SIEVE script ' . $this->_scriptName);
        
        $this->_backend->deleteScript($this->_scriptName);
    }
    
    /**
     * get rules for account
     * 
     * @param string $_accountId
     * @return Tinebase_Record_RecordSet of Felamimail_Model_Sieve_Rule
     * 
     * @todo implement
     */
    public function getRules($_accountId)
    {
        throw new Felamimail_Exception('not implemented yet.');
    }
    
    /**
     * set rules for account
     * 
     * @param string $_accountId
     * @param Tinebase_Record_RecordSet $_rules (Felamimail_Model_Sieve_Rule)
     * @return Tinebase_Record_RecordSet
     * 
     * @todo implement
     */
    public function setRules($_accountId, Tinebase_Record_RecordSet $_rules)
    {
        throw new Felamimail_Exception('not implemented yet.');
    }
}
