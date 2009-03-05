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
 * 
 * @deprecated  user backends should be refactored
 */

/**
 * abstract class for all user backends
 *
 * @package     Tinebase
 * @subpackage  User
 */
 
abstract class Tinebase_User_Abstract
{
    /**
     * get list of users
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_Record_RecordSet with record class Tinebase_Model_FullUser
     */
    public function getFullUsers($_filter = NULL, $_sort = NULL, $_dir = 'ASC', $_start = NULL, $_limit = NULL)
    {
        return $this->getUsers($_filter, $_sort, $_dir, $_start, $_limit, 'Tinebase_Model_FullUser');
    }
    
    /**
     * get full user by login name
     *
     * @param   string      $_loginName
     * @return  Tinebase_Model_FullUser full user
     */
    public function getFullUserByLoginName($_loginName)
    {
        return $this->getUserByLoginName($_loginName, 'Tinebase_Model_FullUser');
    }
    
    /**
     * get full user by id
     *
     * @param   int         $_accountId
     * @return  Tinebase_Model_FullUser full user
     */
    public function getFullUserById($_accountId)
    {
        return $this->getUserById($_accountId, 'Tinebase_Model_FullUser');
    }
    
    /**
     * get dummy user record
     *
     * @param string $_accountClass Tinebase_Model_User|Tinebase_Model_FullUser
     * @param integer $_id [optional]
     * @return Tinebase_Model_User|Tinebase_Model_FullUser
     * 
     * @todo add primary group for full users?
     */
    public function getNonExistentUser($_accountClass = 'Tinebase_Model_User', $_id = 0) 
    {
        $translate = Tinebase_Translation::getTranslation('Tinebase');
        
        $data = array(
            'accountId'             => ($_id !== NULL) ? $_id : 0,
            'accountLoginName'      => $translate->_('unknown'),
            'accountDisplayName'    => $translate->_('unknown'),
            'accountLastName'       => $translate->_('unknown'),
            'accountFirstName'      => $translate->_('unknown'),
            'accountFullName'       => $translate->_('unknown'),
            //'accountPrimaryGroup' => 0,         
        );
        $result = new $_accountClass($data);
        
        return $result;
    }
    
    /******************* abstract functions *********************/
    
    /**
     * get list of users with NO internal informations
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_Record_RecordSet with record class Tinebase_Model_User
     */
    abstract public function getUsers($_filter = NULL, $_sort = NULL, $_dir = 'ASC', $_start = NULL, $_limit = NULL);
    
    /**
     * get user by login name
     *
     * @param   string      $_loginName
     * @return  Tinebase_Model_User full user
     */
    abstract public function getUserByLoginName($_loginName);

    /**
     * get user by id
     *
     * @param   int         $_accountId
     * @return  Tinebase_Model_User user
     */
    abstract public function getUserById($_accountId);
    
    /**
     * setPassword() - sets / updates the password in the account backend
     *
     * @param string $_loginName
     * @param string $_password
     * @param bool   $_encrypt encrypt password
     */
    abstract public function setPassword($_loginName, $_password, $_encrypt = TRUE);
    
    /**
     * update user status
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
     * blocks/unblocks the user (calls backend class with the same name)
     *
     * @param   int $_accountId
     * @param   Zend_Date   $_blockedUntilDate
    */
    abstract public function setBlockedDate($_accountId, $_blockedUntilDate);
    
    /**
     * set login time for user (with ip address)
     *
     * @param int $_accountId
     * @param string $_ipAddress
     */
    abstract public function setLoginTime($_accountId, $_ipAddress);
    
    /**
     * updates an existing user
     *
     * @param Tinebase_Model_FullUser $_account
     * @return Tinebase_Model_FullUser
     */
    abstract public function updateUser(Tinebase_Model_FullUser $_account);

    /**
     * adds a new user
     *
     * @param Tinebase_Model_FullUser $_account
     * @return Tinebase_Model_FullUser
     */
    abstract public function addUser(Tinebase_Model_FullUser $_account);
    
    /**
     * delete an user
     *
     * @param int $_accountId
     */
    abstract public function deleteUser($_accountId);

    /**
     * delete multiple users
     *
     * @param array $_accountIds
     */
    abstract public function deleteUsers(array $_accountIds);
    
    /**
     * Get multiple users
     *
     * @param string|array $_id Ids
     * @return Tinebase_Record_RecordSet
     */
    abstract public function getMultiple($_id);
}
