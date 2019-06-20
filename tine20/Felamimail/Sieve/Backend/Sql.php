<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Sieve
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        allow multiple scripts with different names for one account?
 */

/**
 * class read and write sieve data from / to a sql database table
 * 
 * @package     Felamimail
 * @subpackage  Sieve
 */
class Felamimail_Sieve_Backend_Sql extends Felamimail_Sieve_Backend_Abstract
{
    /**
     * rules backend
     * 
     * @var Tinebase_Backend_Sql
     */
    protected $_rulesBackend = NULL;

    /**
     * vacation backend
     * 
     * @var Tinebase_Backend_Sql
     */
    protected $_vacationBackend = NULL;

    /**
     * script part backend
     *
     * @var Tinebase_Backend_Sql
     */
    protected $_scriptPartBackend = NULL;
    
    /**
     * email account id
     * 
     * @var string
     */
    protected $_accountId = NULL;

    /**
     * constructor
     *
     * @param   string|Felamimail_Model_Account $_accountId the felamimail account id
     * @param   boolean $_readData
     * @throws Felamimail_Exception
     */
    public function __construct($_accountId, $_readData = TRUE)
    {
        $this->_rulesBackend = new Tinebase_Backend_Sql(array(
            'modelName' => 'Felamimail_Model_Sieve_Rule', 
            'tableName' => 'felamimail_sieve_rule',
        ));

        $this->_vacationBackend = new Tinebase_Backend_Sql(array(
            'modelName' => 'Felamimail_Model_Sieve_Vacation', 
            'tableName' => 'felamimail_sieve_vacation',
        ));

        $this->_scriptPartBackend = new Tinebase_Backend_Sql(array(
            'modelName' => 'Felamimail_Model_Sieve_ScriptPart',
            'tableName' => 'felamimail_sieve_scriptpart',
        ));
        
        $this->_accountId = ($_accountId instanceof Felamimail_Model_Account) ? $_accountId->getId() : $_accountId;
        
        if (empty($this->_accountId)) {
            throw new Felamimail_Exception('No accountId has been set.');
        }
        
        if ($_readData) {
            $this->readScriptData();
        }
    }
    
    /**
     * get sieve data from db
     * 
     * @throws Tinebase_Exception_NotFound
     */
    public function readScriptData()
    {
        $this->_getRules();
        $this->_getVacation();
        $this->_getScriptParts();
        
        if (count($this->_rules) === 0 && $this->_vacation === NULL && $this->_scriptParts->count() === 0) {
            throw new Tinebase_Exception_NotFound('No sieve data found in database for this account.');
        }
    }

    protected function _getScriptParts()
    {
        $this->_scriptParts = $this->_scriptPartBackend->getMultipleByProperty($this->_accountId, 'account_id', FALSE, 'id');
    }

    /**
     * get rules
     */
    protected function _getRules()
    {
        $ruleRecords = $this->_rulesBackend->getMultipleByProperty($this->_accountId, 'account_id', FALSE, 'id');
        
        $this->_rules = array();
        foreach ($ruleRecords as $ruleRecord) {
            $ruleRecord->conditions = Zend_Json::decode($ruleRecord->conditions);
            if (Tinebase_Helper::is_json($ruleRecord->action_argument)) {
                $ruleRecord->action_argument = Zend_Json::decode($ruleRecord->action_argument);
            }

            $this->_rules[] = $ruleRecord->getFSR();
        }
    }
    
    /**
     * get vacation
     */
    protected function _getVacation()
    {
        try {
            /** @var Felamimail_Model_Sieve_Vacation $vacationRecord */
            $vacationRecord = $this->_vacationBackend->getByProperty($this->_accountId, 'account_id');
            $vacationRecord->addresses = Zend_Json::decode($vacationRecord->addresses);
            
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' Got vacation from DB: ' . print_r($vacationRecord->toArray(), TRUE));
            
            $this->_vacation = $vacationRecord->getFSV();
            
        } catch (Tinebase_Exception_NotFound $tenf) {
            // do nothing
        }
    }
    
    /**
     * get sieve script as string
     * 
     * @return string
     */
    public function getSieve()
    {
        $sieve = parent::getSieve();
        
        $this->save();
        return $sieve;
    }
    
    /**
     * save sieve data
     */
    public function save()
    {
        $this->_saveRules();
        $this->_saveVacation();
        $this->_saveScriptParts();
    }

    /**
     * persist script parts data in db
     *
     * @throws Exception
     */
    protected function _saveScriptParts()
    {
        if (empty($this->_scriptParts)) {
            $this->_scriptPartBackend->deleteByProperty($this->_accountId, 'account_id');
            return;
        }

        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

            $this->_scriptPartBackend->deleteByProperty($this->_accountId, 'account_id');
            /** @var Felamimail_Model_Sieve_ScriptPart $scriptPart */
            foreach ($this->_scriptParts as $scriptPart) {
                $this->_scriptPartBackend->create($scriptPart);
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }
    }
    
    /**
     * persist rules data in db
     * 
     * @throws Exception
     */
    protected function _saveRules()
    {
        if (empty($this->_rules)) {
            $this->_rulesBackend->deleteByProperty($this->_accountId, 'account_id');
            return;
        }
        
        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
            $this->_rulesBackend->deleteByProperty($this->_accountId, 'account_id');
            $id = 1;
            foreach ($this->_rules as $rule) {
                $ruleRecord = new Felamimail_Model_Sieve_Rule();
                $ruleRecord->setFromFSR($rule);
                $ruleRecord->account_id = $this->_accountId;
                $ruleRecord->conditions = Zend_Json::encode($ruleRecord->conditions);
                if (is_array($ruleRecord->action_argument)) {
                    $ruleRecord->action_argument = Zend_Json::encode($ruleRecord->action_argument);
                }
                if ($ruleRecord->getId()) {
                    // prevent duplicate ids
                    $id = max($id, (int) $ruleRecord->getId());
                }
                $ruleRecord->setId($id++);
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                    __METHOD__ . '::' . __LINE__ . ' Creating rule record: ' . print_r($ruleRecord->toArray(), true));

                $this->_rulesBackend->create($ruleRecord);
            }
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }
    }

    /**
     * persist vacation data in db
     */
    protected function _saveVacation()
    {
        if (empty($this->_vacation)) {
            return;
        }
        
        $vacationRecord = new Felamimail_Model_Sieve_Vacation();
        $vacationRecord->setFromFSV($this->_vacation);
        $vacationRecord->account_id = $this->_accountId;
        $vacationRecord->setId($this->_accountId);
        $vacationRecord->addresses = Zend_Json::encode($vacationRecord->addresses);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Saving vacation in DB: ' . print_r($vacationRecord->toArray(), TRUE));
        
        try {
            $this->_vacationBackend->get($vacationRecord->getId());
            $this->_vacationBackend->update($vacationRecord);
        } catch (Tinebase_Exception_NotFound $tenf) {
            $this->_vacationBackend->create($vacationRecord);
        }
    }
    
    /**
     * delete all sieve data associated with account
     */
    public function delete()
    {
        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
            $this->_rulesBackend->deleteByProperty($this->_accountId, 'account_id');
            $this->_vacationBackend->deleteByProperty($this->_accountId, 'account_id');
            $this->_scriptPartBackend->deleteByProperty($this->_accountId, 'account_id');
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }
    }
}
