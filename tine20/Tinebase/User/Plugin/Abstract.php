<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * abstract class for user plugins
 * 
 * @package Tinebase
 * @subpackage User
 * @method inspectAddUser(Tinebase_Model_FullUser $_addedUser, Tinebase_Model_FullUser $_newUserProperties)
 * @method inspectUpdateUser(Tinebase_Model_FullUser $_updatedUser, Tinebase_Model_FullUser $_newUserProperties)
 * @method inspectGetUserByProperty(Tinebase_Model_User $_user)
 */
abstract class Tinebase_User_Plugin_Abstract
{
    /**
     * supportAliasesDispatchFlag
     *
     * @var boolean
     */
    protected $_supportAliasesDispatchFlag = false;

    /**
     * Check if we should append domain name or not
     *
     * @param  string $_userName
     * @return string
     */
    protected function _appendDomain($_userName)
    {
        $domainConfigKey = ($this instanceof Tinebase_EmailUser_Imap_Interface) ? 'domain' : 'primarydomain';
        
        if (!empty($this->_config[$domainConfigKey])) {
            $domain = '@' . $this->_config[$domainConfigKey];
            if (strpos($_userName, $domain) === FALSE) {
                $_userName .= $domain;
            }
        }
        
        return $_userName;
    }

    /**
     * @param string $accountId
     * @param string $accountLoginName
     * @param string $accountEmailAddress
     * @param string|null $alternativeLoginName
     * @return string|null
     */
    public function getLoginName($accountId, $accountLoginName, $accountEmailAddress, $alternativeLoginName = null)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(
            __METHOD__ . '::' . __LINE__ . " $accountId, $accountLoginName, $accountEmailAddress, $alternativeLoginName");

        $domainConfigKey = ($this instanceof Tinebase_EmailUser_Imap_Interface) ? 'domain' : 'primarydomain';
        if (isset($this->_config['useEmailAsUsername']) && $this->_config['useEmailAsUsername']) {
            $emailUsername = $accountEmailAddress;
        } else if (isset($this->_config['instanceName']) && ! empty($this->_config['instanceName'])) {
            $emailUsername = $accountId . '@' . $this->_config['instanceName'];
        } else if (isset($this->_config[$domainConfigKey]) && $this->_config[$domainConfigKey] !== null) {
            $emailAddressDomain = substr($accountEmailAddress, strpos($accountEmailAddress, '@') + 1);
            if ($emailAddressDomain !== $this->_config[$domainConfigKey]) {
                // secondary domains still need the primary domain in username!
                $emailUsername = preg_replace('/[@\.]+/', '-', $accountEmailAddress)
                    . '@' . $this->_config[$domainConfigKey];
            } else if (strpos($accountLoginName, '@') === false) {
                $emailUsername = $this->_appendDomain($accountLoginName);
            } else {
                $emailUsername = $accountLoginName;
            }
        } else if ($alternativeLoginName !== null) {
            $emailUsername = $alternativeLoginName;
        } else {
            $emailUsername = $accountLoginName;
        }

        return $emailUsername;
    }

    /**
     * @return bool
     *
     * @todo make this a generic "capabilities" feature
     */
    public function supportsAliasesDispatchFlag()
    {
        return $this->_supportAliasesDispatchFlag;
    }

    /**
     * update/set email user password
     *
     * @param string $_userId
     * @param string $_password
     * @param bool $_encrypt
     * @param bool $_mustChange
     * @param array $_additionalData
     * @return void
     */
    public function inspectSetPassword($_userId, string $_password, bool $_encrypt = true, bool $_mustChange = false, array &$_additionalData = [])
    {
        // do nothing here - implement in plugin if needed
    }

    /**
     * delete user by id
     *
     * @param   Tinebase_Model_FullUser $_user
     */
    public function inspectDeleteUser(Tinebase_Model_FullUser $_user)
    {
        // do nothing here - implement in plugin if needed
    }

    /**
     * check if user exists already in plugin user table
     *
     * @param Tinebase_Model_FullUser $_user
     * @return boolean
     */
    public function userExists(Tinebase_Model_FullUser $_user)
    {
        return false;
    }

    /**
     * @param Tinebase_Model_FullUser $_user
     * @param string $newId
     * @return mixed
     * @throws Tinebase_Exception_NotImplemented
     */
    public function copyUser(Tinebase_Model_FullUser $_user, $newId)
    {
        throw new Tinebase_Exception_NotImplemented('do not call this method on ' . self::class);
    }

    /**
     * get email username depending on config
     * NOTE: translates xprops to userid if necessary
     *
     * @param Tinebase_Model_FullUser $user
     * @param $alternativeLoginName
     * @return null|string
     */
    public function getEmailUserName(Tinebase_Model_FullUser $user, $alternativeLoginName = null): ?string
    {
        $userId = Tinebase_EmailUser_XpropsFacade::getEmailUserId($user);
        return $this->getLoginName($userId, $user->accountLoginName, $user->accountEmailAddress,
            $alternativeLoginName);
    }

    /**
     * backup user to a dump file
     *
     * @throws Tinebase_Exception_NotImplemented
     */
    public function backup($option)
    {
        throw new Tinebase_Exception_NotImplemented('do not call this method on ' . self::class);
    }

    /**
     * delete all email users
     *
     * @throws Tinebase_Exception_NotImplemented
     */
    public function deleteAllEmailUsers()
    {
        throw new Tinebase_Exception_NotImplemented('do not call this method on ' . self::class);
    }
}
