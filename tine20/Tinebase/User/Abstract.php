<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * abstract class for all account backends
 *
 * @package     Tinebase
 * @subpackage  User
 */
 
abstract class Tinebase_User_Abstract
{
    /**
     * get list of accounts with NO internal informations
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_Record_RecordSet with record class Tinebase_User_Model_User
     */
    abstract public function getUsers($_filter = NULL, $_sort = NULL, $_dir = 'ASC', $_start = NULL, $_limit = NULL);
    
    /**
     * get list of accounts
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_Record_RecordSet with record class Tinebase_User_Model_FullUser
     */
    public function getFullUsers($_filter = NULL, $_sort = NULL, $_dir = 'ASC', $_start = NULL, $_limit = NULL)
    {
        return $this->getUsers($_filter, $_sort, $_dir, $_start, $_limit, 'Tinebase_User_Model_FullUser');
    }
    
    /**
     * get account by login name
     *
     * @param   string      $_loginName
     * @return  Tinebase_User_Model_User full account
     */
    abstract public function getUserByLoginName($_loginName);

    /**
     * get full account by login name
     *
     * @param   string      $_loginName
     * @return  Tinebase_User_Model_FullUser full account
     */
    public function getFullUserByLoginName($_loginName)
    {
        return $this->getUserByLoginName($_loginName, 'Tinebase_User_Model_FullUser');
    }
    
    /**
     * get account by id
     *
     * @param   int         $_accountId
     * @return  Tinebase_User_Model_User account
     */
    abstract public function getUserById($_accountId);

    /**
     * get full account by id
     *
     * @param   int         $_accountId
     * @return  Tinebase_User_Model_FullUser full account
     */
    public function getFullUserById($_accountId)
    {
        return $this->getUserById($_accountId, 'Tinebase_User_Model_FullUser');
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
     * @param Tinebase_User_Model_FullUser $_account
     * @return Tinebase_User_Model_FullUser
     */
    abstract public function updateUser(Tinebase_User_Model_FullUser $_account);

    /**
     * adds a new account
     *
     * @param Tinebase_User_Model_FullUser $_account
     * @return Tinebase_User_Model_FullUser
     */
    abstract public function addUser(Tinebase_User_Model_FullUser $_account);
    
    /**
     * delete an account
     *
     * @param int $_accountId
     */
    abstract public function deleteUser($_accountId);

    /**
     * delete multiple accounts
     *
     * @param array $_accountIds
     */
    abstract public function deleteUsers(array $_accountIds);
}