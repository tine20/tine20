<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * class Tinebase_EmailUser_Imap_Cyrus
 * 
 * Email User Settings Managing for cyrus attributes
 * 
 * @package Tinebase
 * @subpackage User
 * @todo add quota support
 */
class Tinebase_EmailUser_Imap_Cyrus extends Tinebase_User_Plugin_Abstract
{
    /**
     * 
     * @var Zend_Mail_Protocol_Imap
     */
    protected $_imap;
    
    /**
     * email user config
     * 
     * @var array 
     */
    protected $_config = array(
        'domain'   => null,
        'host'     => null,
        'port'     => 143,
        'ssl'      => FALSE,
        'admin'    => null,
        'password' => null
    );
    
    /**
     * the constructor
     *
     */
    public function __construct(array $_options = array())
    {
        // get cyrus imap config options (host, username, password, port)
        $imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP, new Tinebase_Config_Struct())->toArray();
        
        // merge _config and dovecot imap
        $this->_config = array_merge($this->_config, $imapConfig['cyrus']);
        
        // set domain from imap config
        $this->_config['domain'] = !empty($imapConfig['domain']) ? $imapConfig['domain'] : null;
        $this->_config['host']   = $imapConfig['host'];
        $this->_config['port']   = !empty($imapConfig['port']) ? $imapConfig['port'] : 143;
        $this->_config['ssl']    = $imapConfig['ssl'] != 'none' ? $imapConfig['ssl'] : false;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' cyrus imap config: ' . print_r($this->_config, true));
    }
    
    /**
     * get new email user
     * 
     * @param  Tinebase_Model_FullUser   $_user
     * @return Tinebase_Model_EmailUser
     */
    public function getNewUser(Tinebase_Model_FullUser $_user)
    {
        $result = new Tinebase_Model_EmailUser(array(
            'emailUserId'     => $this->_appendDomain($_user->accountLoginName),
            'emailUsername' => $this->_appendDomain($_user->accountLoginName)
        ));
        
        return $result;
    }
    
    /**
     * delete user by id
     *
     * @param  Tinebase_Model_FullUser  $_user
     */
    public function inspectDeleteUser(Tinebase_Model_FullUser $_user)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Delete Cyrus imap account of user ' . $_user->accountLoginName);

        $imap = $this->_getImapConnection();
        
        $mailboxString = $this->_getUserMailbox($_user->accountLoginName);
        
        $mailboxes = $imap->listMailbox('', $mailboxString);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " search for {$mailboxString} in " . print_r($mailboxes, true));
        
        // does mailbox exist at all?
        if (array_key_exists($mailboxString, $mailboxes)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' must delete mailbox ');
            if ($imap->setACL($mailboxString, $this->_config['admin'], 'lrswipcda') === true) {
                $imap->delete($mailboxString);
            }
        }
    }
    
    /**
     * inspect get user by property
     * 
     * @param Tinebase_Model_User  $_user  the user object
     */
    public function inspectGetUserByProperty(Tinebase_Model_User $_user)
    {
        if (! $_user instanceof Tinebase_Model_FullUser) {
            return;
        }
        
        $imap = $this->_getImapConnection();
        
        $mailboxString = $this->_getUserMailbox($_user->accountLoginName);

        $quota = $imap->getQuotaRoot($mailboxString);
        
        $emailUser = new Tinebase_Model_EmailUser(array(
            'emailUsername'  => $this->_appendDomain($_user->accountLoginName),
            'emailUserId'    => $this->_appendDomain($_user->accountLoginName),
            'emailMailQuota' => isset($quota['STORAGE']) ? round($quota['STORAGE']['limit'] / 1024) : null,
            'emailMailSize'  => isset($quota['STORAGE']) ? round($quota['STORAGE']['usage'] / 1024) : null
        ));
                
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($emailUser->toArray(), TRUE));
        
        $_user->imapUser  = $emailUser;
        $_user->emailUser = Tinebase_EmailUser::merge(clone $_user->imapUser, isset($_user->emailUser) ? $_user->emailUser : null);
    }
        
    /**
     * update/set email user password
     * 
     * @param  string  $_userId
     * @param  string  $_password
     * @param  bool    $_encrypt encrypt password
     */
    public function inspectSetPassword($_userId, $_password, $_encrypt = TRUE)
    {
        // nothing to be done for cyrus imap server
    }
        
    /**
     * adds email properties for a new user
     * 
     * @param  Tinebase_Model_FullUser  $_addedUser
     * @param  Tinebase_Model_FullUser  $_newUserProperties
     */
    protected function _addUser(Tinebase_Model_FullUser $_addedUser, Tinebase_Model_FullUser $_newUserProperties)
    {
        // do nothing when no email address is set
        if (empty($_addedUser->accountEmailAddress)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . 
                " user {$_addedUser->accountLoginName} has no email address. Don't create cyrus imap mailbox."
            );
            
            return;
        }
        
        $imap = $this->_getImapConnection();
        
        $mailboxString = $this->_getUserMailbox($_addedUser->accountLoginName);
        
        $mailboxes = $imap->listMailbox('', $mailboxString);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " search for {$mailboxString} in " . print_r($mailboxes, true));
        
        if (!array_key_exists($mailboxString, $mailboxes)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' must create mailbox: '. $mailboxString);
            if ($imap->create($mailboxString) == true) {
                if ($imap->setACL($mailboxString, $this->_appendDomain($_addedUser->accountLoginName), 'lrswipcda') !== true) {
                    // failed to set acl
                }
            }
        }
        
        $this->_setImapQuota($_newUserProperties, $imap, $mailboxString);
        
        $this->inspectGetUserByProperty($_addedUser);
    }
    
    /**
     * set quota directly on IMAP server
     * 
     * @param Tinebase_Model_FullUser $_user
     * @param Zend_Mail_Protocol_Imap $_imap
     * @param string $_mailboxString
     */
    protected function _setImapQuota(Tinebase_Model_FullUser $_user, Zend_Mail_Protocol_Imap $_imap = NULL, $_mailboxString = NULL)
    {
        $imap = ($_imap !== NULL) ? $_imap : $this->_getImapConnection();
        $mailboxString = ($_mailboxString !== NULL) ? $_mailboxString : $this->_getUserMailbox($_user->accountLoginName);
        
        $limit = (isset($_user->imapUser) && $_user->imapUser->emailMailQuota) > 0 ? convertToBytes($_user->imapUser->emailMailQuota . 'M') / 1024 : null;
        $imap->setQuota($mailboxString, 'STORAGE', $limit);
    }
    
    /**
     * get imap connection
     * 
     * @return Zend_Mail_Protocol_Imap
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _getImapConnection()
    {
        if (! $this->_imap instanceof Zend_Mail_Protocol_Imap) {
            $this->_imap = new Zend_Mail_Protocol_Imap($this->_config['host'], $this->_config['port'], $this->_config['ssl']);
            $loginResult = $this->_imap->login($this->_config['admin'], $this->_config['password']);
            if (! $loginResult) {
                throw new Tinebase_Exception_AccessDenied('Could not login to cyrus server ' . $this->_config['host'] . ' with user ' . $this->_config['admin']);
            }
        }

        return $this->_imap;
    }

    /**
     * get mailbox string for users aka user.loginname
     * 
     * @param  string  $_username  the imap account name
     * @throws Tinebase_Exception_NotFound
     * @return string
     */
    protected function _getUserMailbox($_username)
    {
        $imap = $this->_getImapConnection();
        
        $namespaces = $imap->getNamespace();
        
        if (!isset($namespaces['other'])) {
            throw new Tinebase_Exception_NotFound('other namespace not found');
        }
        
        $mailboxString = $namespaces['other']['name'] . $this->_appendDomain($_username);
        
        return $mailboxString;
    }
    
    /**
     * updates email properties for an existing user
     * 
     * @param  Tinebase_Model_FullUser  $_updatedUser
     * @param  Tinebase_Model_FullUser  $_newUserProperties
     */
    protected function _updateUser(Tinebase_Model_FullUser $_updatedUser, Tinebase_Model_FullUser $_newUserProperties)
    {
        $this->_setImapQuota($_newUserProperties);
        $this->inspectGetUserByProperty($_updatedUser);
    }
    
    /**
     * check if user exists already in dovecot user table
     * 
     * @param  Tinebase_Model_FullUser  $_user
     * @return boolean
     */
    protected function _userExists(Tinebase_Model_FullUser $_user)
    {
        try {
            $mailboxString = $this->_getUserMailbox($_user->accountLoginName);
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                . " Mailbox of user " . $_user->accountLoginName . " not found: " . $tenf->getMessage());
            return false;
        }
        
        $imap = $this->_getImapConnection();
        $mailboxes = $imap->listMailbox('', $mailboxString);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " search for {$mailboxString} in " . print_r($mailboxes, true));
        
        // does mailbox exist at all?
        if (!array_key_exists($mailboxString, $mailboxes)) {
            return false;
        }
        
        return true;
    }
}
