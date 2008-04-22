<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Account
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * abstract class for all account backends
 *
 * @package     Tinebase
 * @subpackage  Account
 */
 
abstract class Tinebase_Account_Abstract
{
    /**
     * get list of accounts with NO internal informations
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_Record_RecordSet with record class Tinebase_Account_Model_Account
     */
    abstract public function getAccounts($_filter = NULL, $_sort = NULL, $_dir = 'ASC', $_start = NULL, $_limit = NULL);
    
    /**
     * get list of accounts
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_Record_RecordSet with record class Tinebase_Account_Model_FullAccount
     */
    public function getFullAccounts($_filter = NULL, $_sort = NULL, $_dir = 'ASC', $_start = NULL, $_limit = NULL)
    {
        return $this->getAccounts($_filter, $_sort, $_dir, $_start, $_limit, 'Tinebase_Account_Model_FullAccount');
    }
    
    /**
     * get account by login name
     *
     * @param   string      $_loginName
     * @return  Tinebase_Account_Model_Account full account
     */
    abstract public function getAccountByLoginName($_loginName);

    /**
     * get full account by login name
     *
     * @param   string      $_loginName
     * @return  Tinebase_Account_Model_FullAccount full account
     */
    public function getFullAccountByLoginName($_loginName)
    {
        return $this->getAccountByLoginName($_loginName, 'Tinebase_Account_Model_FullAccount');
    }
    
    /**
     * get account by id
     *
     * @param   int         $_accountId
     * @return  Tinebase_Account_Model_Account account
     */
    abstract public function getAccountById($_accountId);

    /**
     * get full account by id
     *
     * @param   int         $_accountId
     * @return  Tinebase_Account_Model_FullAccount full account
     */
    public function getFullAccountById($_accountId)
    {
        return $this->getAccountById($_accountId, 'Tinebase_Account_Model_FullAccount');
    }
    
    /**
     * update account status
     *
     * @param   int         $_accountId
     * @param   string      $_status
    */
    abstract public function setStatus($_accountId, $_status);

    /**
     * sets/unsets expiry date (calls backend class with the same name)
     *
     * @param   int         $_accountId
     * @param   Zend_Date   $_expiryDate
    */
    abstract public function setExpiryDate($_accountId, $_expiryDate);

    /**
     * blocks/unblocks the account (calls backend class with the same name)
     *
     * @param   int $_accountId
     * @param   Zend_Date   $_blockedUntilDate
    */
    abstract public function setBlockedDate($_accountId, $_blockedUntilDate);
    
    /**
     * set login time for account (with ip address)
     *
     * @param int $_accountId
     * @param string $_ipAddress
     */
    abstract public function setLoginTime($_accountId, $_ipAddress);
    
    /**
     * updates an existing account
     *
     * @param Tinebase_Account_Model_FullAccount $_account
     * @return Tinebase_Account_Model_FullAccount
     */
    abstract public function updateAccount(Tinebase_Account_Model_FullAccount $_account);

    /**
     * adds a new account
     *
     * @param Tinebase_Account_Model_FullAccount $_account
     * @return Tinebase_Account_Model_FullAccount
     */
    abstract public function addAccount(Tinebase_Account_Model_FullAccount $_account);
    
    /**
     * delete an account
     *
     * @param int $_accountId
     */
    abstract public function deleteAccount($_accountId);

    /**
     * delete multiple accounts
     *
     * @param array $_accountIds
     */
    abstract public function deleteAccounts(array $_accountIds);
}