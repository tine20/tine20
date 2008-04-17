<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Account
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Account ldap backend
 * 
 * @package     Tinebase
 * @subpackage  Account
 */
class Tinebase_Account_Ldap implements Tinebase_Account_Interface
{
    /**
     * the constructor
     *
     * @param  array $options Options used in connecting, binding, etc.
     * don't use the constructor. use the singleton 
     */
    private function __construct(array $_options) {
        $this->_backend = new Tinebase_Ldap($_options);
        $this->_backend->bind();
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * holdes the instance of the singleton
     *
     * @var Tinebase_Account_Ldap
     */
    private static $instance = NULL;
    
    /**
     * Enter description here...
     *
     * @var Tinebase_Ldap
     */
    protected $_backend = NULL;
    
    protected $rowNameMapping = array(
        'accountId'             => 'uidnumber',
        //'accountDisplayName'    => 'n_fileas',
        'accountFullName'       => 'cn',
        'accountFirstName'      => 'givenname',
        'accountLastName'       => 'sn',
        'accountLoginName'      => 'uid',
        //'accountLastLogin'      => 'last_login',
        //'accountLastLoginfrom'  => 'last_login_from',
        //'accountLastPasswordChange' => 'last_password_change',
        'accountStatus'         => 'status',
        'accountExpires'        => 'expires_at',
        'accountPrimaryGroup'   => 'gidnumber',
        'accountEmailAddress'   => 'mail'
    );
    
    
    /**
     * the singleton pattern
     *
     * @param  array $options Options used in connecting, binding, etc.
     * @return Tinebase_Account_Ldap
     */
    public static function getInstance(array $_options = array()) 
    {
        if (self::$instance === NULL) {
            self::$instance = new Tinebase_Account_Ldap($_options);
        }
        
        return self::$instance;
    }
    
    /**
     * get list of accounts
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @param string $_accountClass the type of subclass for the Tinebase_Record_RecordSet to return
     * @return Tinebase_Record_RecordSet with record class Tinebase_Account_Model_Account
     */
    public function getAccounts($_filter = NULL, $_sort = NULL, $_dir = 'ASC', $_start = NULL, $_limit = NULL, $_accountClass = 'Tinebase_Account_Model_Account')
    {        
        $accounts = $this->_backend->fetchAll(Zend_Registry::get('configFile')->accounts->get('ldap')->baseDn, 'objectclass=posixaccount');
        
        $result = new Tinebase_Record_RecordSet($_accountClass);
        
        foreach($accounts as $account) {
            $accountArray = array(
                'accountStatus' => 'enabled'
            );
            foreach($account as $key => $value) {
                if(is_int($key)) {
                    continue;
                }
                $keyMapping = array_search($key, $this->rowNameMapping);
                if($keyMapping !== FALSE) {
                    $accountArray[$keyMapping] = $value[0];
                }
            }
            $accountObject = new $_accountClass($accountArray);
            $result->addRecord($accountObject);
        }
        
        return $result;
    }
    
    /**
     * get account by login name
     *
     * @param string $_loginName the loginname of the account
     * @return Tinebase_Account_Model_Account the account object
     *
     * @throws Tinebase_Record_Exception_NotDefined when row is empty
     */
    public function getAccountByLoginName($_loginName, $_accountClass = 'Tinebase_Account_Model_Account')
    {
        $select = $this->_getAccountSelectObject()
            ->where(SQL_TABLE_PREFIX . 'accounts.login_name = ?', $_loginName);

        $stmt = $select->query();

        $row = $stmt->fetch(Zend_Db::FETCH_ASSOC);
        
        // throw exception if data is empty (if the row is no array, the setFromArray function throws a fatal error 
        // because of the wrong type that is not catched by the block below)
        if ( $row === false ) {
             throw new Exception('account not found');
        }        

        try {
            $account = new $_accountClass();
            $account->setFromArray($row);
        } catch (Exception $e) {
            $validation_errors = $account->getValidationErrors();
            Zend_Registry::get('logger')->debug( 'Tinebase_Account_Sql::getAccountByLoginName: ' . $e->getMessage() . "\n" .
                "Tinebase_Account_Model_Account::validation_errors: \n" .
                print_r($validation_errors,true));
            throw ($e);
        }
        
        return $account;
    }
    
    /**
     * get account by accountId
     *
     * @param int $_accountId the account id
     * @return Tinebase_Account_Model_Account the account object
     */
    public function getAccountById($_accountId, $_accountClass = 'Tinebase_Account_Model_Account')
    {
        $accountId = Tinebase_Account_Model_Account::convertAccountIdToInt($_accountId);
        
        $select = $this->_getAccountSelectObject()
            ->where(SQL_TABLE_PREFIX . 'accounts.id = ?', $accountId);

        $stmt = $select->query();

        $row = $stmt->fetch(Zend_Db::FETCH_ASSOC);
        if($row === false) {
            throw new Exception('account not found');
        }

        try {
            $account = new $_accountClass();
            $account->setFromArray($row);
        } catch (Exception $e) {
            $validation_errors = $account->getValidationErrors();
            Zend_Registry::get('logger')->debug( 'Tinebase_Account_Sql::_getAccountFromSQL: ' . $e->getMessage() . "\n" .
                "Tinebase_Account_Model_Account::validation_errors: \n" .
                print_r($validation_errors,true));
            throw ($e);
        }
        
        return $account;
    }
    
    /**
     * update the lastlogin time of account
     *
     * @param int $_accountId
     * @param string $_ipAddress
     * @return void
     */
    public function setLoginTime($_accountId, $_ipAddress) 
    {
        $accountId = Tinebase_Account_Model_Account::convertAccountIdToInt($_accountId);
        
        $accountsTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'accounts'));
        
        $accountData['last_login_from'] = $_ipAddress;
        $accountData['last_login']      = Zend_Date::now()->getIso();
        
        $where = array(
            $accountsTable->getAdapter()->quoteInto('id = ?', $accountId)
        );
        
        $result = $accountsTable->update($accountData, $where);
        
        return $result;
    }
    
    protected function _getAccountSelectObject()
    {
        $db = Zend_Registry::get('dbAdapter');
        
        $select = $db->select()
            ->from(SQL_TABLE_PREFIX . 'accounts', 
                array(
                    'accountId'             => $this->rowNameMapping['accountId'],
                    'accountLoginName'      => $this->rowNameMapping['accountLoginName'],
                    'accountLastLogin'      => $this->rowNameMapping['accountLastLogin'],
                    'accountLastLoginfrom'  => $this->rowNameMapping['accountLastLoginfrom'],
                    'accountLastPasswordChange' => $this->rowNameMapping['accountLastPasswordChange'],
                    'accountStatus'         => $this->rowNameMapping['accountStatus'],
                    'accountExpires'        => $this->rowNameMapping['accountExpires'],
                    'accountPrimaryGroup'   => $this->rowNameMapping['accountPrimaryGroup']
                )
            )
            ->join(
                SQL_TABLE_PREFIX . 'addressbook',
                SQL_TABLE_PREFIX . 'accounts.id = ' . SQL_TABLE_PREFIX . 'addressbook.account_id',
                array(
                    'accountDisplayName'    => $this->rowNameMapping['accountDisplayName'],
                    'accountFullName'       => $this->rowNameMapping['accountFullName'],
                    'accountFirstName'      => $this->rowNameMapping['accountFirstName'],
                    'accountLastName'       => $this->rowNameMapping['accountLastName'],
                    'accountEmailAddress'   => $this->rowNameMapping['accountEmailAddress']
                )
            );
                
        return $select;
    }
    
}