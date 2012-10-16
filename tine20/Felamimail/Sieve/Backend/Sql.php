<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Sieve
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * email account id
     * 
     * @var string
     */
    protected $_accountId = NULL;
    
    /**
     * constructor
     * 
     * @param   string|Felamimail_Model_Account  $_accountId     the felamimail account id
     * @param   boolean $_readData
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
        
        if (count($this->_rules) === 0 && $this->_vacation === NULL) {
            throw new Tinebase_Exception_NotFound('No sieve data found in database for this account.');
        }
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
            $this->_rules[] = $ruleRecord->getFSR();
        }
    }
    
    /**
     * get vacation
     */
    protected function _getVacation()
    {
        try {
            $vacationRecord = $this->_vacationBackend->getByProperty($this->_accountId, 'account_id');
            $vacationRecord->addresses = Zend_Json::decode($vacationRecord->addresses);
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
            foreach ($this->_rules as $rule) {
                $ruleRecord = new Felamimail_Model_Sieve_Rule();
                $ruleRecord->setFromFSR($rule);
                $ruleRecord->account_id = $this->_accountId;
                $ruleRecord->conditions = Zend_Json::encode($ruleRecord->conditions);
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
        
        try {
            $oldVac = $this->_vacationBackend->get($vacationRecord->getId());
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
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw Exception;
        }         
    }
}
