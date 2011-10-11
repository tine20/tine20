<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Sieve
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * @param   string  $_accountId     the felamimail account id
     */
    public function __construct($_accountId)
    {
        $this->_rulesBackend = new Tinebase_Backend_Sql(array(
            'modelName' => 'Felamimail_Model_Sieve_Rule', 
            'tableName' => 'felamimail_sieve_rules',
        ));

        $this->_rulesBackend = new Tinebase_Backend_Sql(array(
            'modelName' => 'Felamimail_Model_Sieve_Vacation', 
            'tableName' => 'felamimail_sieve_rules',
        ));
        
        $this->readScriptData();
    }
    
    /**
     * get sieve data from db
     * 
     * @throws Tinebase_Exception_NotFound
     */
    public function readScriptData()
    {
        if ($this->_accountId === NULL) {
            throw new Felamimail_Exception('No accountId has been set.');
        }
        
        $this->_rules = $this->_rulesBackend->getMultipleByProperty($this->_accountId, 'accountId');
        
        try {
            $this->_vacation = $this->_vacationBackend->getByProperty($this->_accountId, 'accountId');
        } catch (Tinebase_Exception_NotFound $tenf) {
            // do nothing
        }
        
        if (count($this->_rules) === 0 && $this->_vacation === NULL) {
            throw new Tinebase_Exception_NotFound('No sieve data found in database for this account.');
        }
    }
    
    /**
     * get sieve script as string
     * 
     * @return string
     */
    public function getSieve()
    {
        if ($this->_accountId === NULL) {
            throw new Felamimail_Exception('No accountId has been set.');
        }
        
        $sieve = parent::getSieve();
        
        $this->_saveRules();
        $this->_saveVacation();
        
        return $sieve;
    }
    
    /**
     * persist rules data in db
     */
    protected function _saveRules()
    {
        // @todo use transaction
        $this->_rulesBackend->deleteByProperty($this->_accountId, 'accountId');
        foreach ($this->_rules as $rule) {
            if (empty($rule->account_id)) {
                $rule->account_id = $this->_accountId;
            }
            $this->_rulesBackend->create($_rule);
        }
    }

    /**
     * persist vacation data in db
     */
    protected function _saveVacation()
    {
        if (empty($this->_vacation->account_id)) {
            $this->_vacation->account_id = $this->_accountId;
        }
                
        if (isset($this->_vacation->id)) {
            $this->_vacationBackend->create($this->_vacation);
        } else {
            $this->_vacationBackend->update($this->_vacation);
        }
    }
}
