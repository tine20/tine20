<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

/**
 * class Tinebase_EmailUser_Imap_Cyrus
 * 
 * Email User Settings Managing for cyrus attributes
 * 
 * @package Tinebase
 * @subpackage User
 */
class Tinebase_EmailUser_Imap_Cyrus extends Tinebase_EmailUser_Abstract
{
    /**
     * 
     * @var Zend_Mail_Protocol_Imap
     */
    protected $_imap;
    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_config = Tinebase_Config::getInstance()->getConfigAsArray(Tinebase_Model_Config::IMAP);
        
        #$this->_config = array_merge($imapConfig['cyrus'], $this->_config);
        #$this->_config['domain'] = (isset($imapConfig['domain'])) ? $imapConfig['domain'] : '';
        
        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . '  imap config: ' . print_r($this->_config, true));
    }
    
    /**
     * get email user by id
     *
     * @todo    retrieve quota from imap server
     * 
     * @param   string  $_userId
     * @return  Tinebase_Model_EmailUser user
     */
    public function getUserById($_userId)
    {
        $user = ($_userId instanceof Tinebase_Model_FullUser) ? $_userId : Tinebase_User::getInstance()->getFullUserById($_userId);
        
        #$imap = $this->_getImapConnection();
        #$imap->getQuota();
        
        $data = array(
            'emailUserId'   => $user->accountLoginName,
            'emailMailQuota' => 0,
            'emailMailSize'  => 0
        );
        
        return new Tinebase_Model_EmailUser($data, true);
    }

    /**
     * adds email properties for a new user
     * 
     * @param  Tinebase_Model_FullUser $_user
     * @param  Tinebase_Model_EmailUser $_emailUser
     * @return Tinebase_Model_EmailUser
     * 
     */
    public function addUser($_user, Tinebase_Model_EmailUser $_emailUser)
    {
        return $this->updateUser($_user, $_emailUser);
    }
    
    /**
     * updates email properties for an existing user
     * 
     * @param  Tinebase_Model_FullUser $_user
     * @param  Tinebase_Model_EmailUser  $_emailUser
     * @return Tinebase_Model_EmailUser
     */
    public function updateUser($_user, Tinebase_Model_EmailUser $_emailUser)
    {
        // do nothing when no email address is set
        if (!empty($_user->accountEmailAddress)) {
            return $_emailUser;
        }
        
        $imap = $this->_getImapConnection();
        
        $mailboxString = $this->_getUserMailbox($_user->accountLoginName);
        
        $mailbox = $imap->listMailbox('', $mailboxString);
        
        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . '  imap config: ' . print_r($mailbox, true));
        
        if (!array_key_exists($mailboxString, $mailbox)) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' must create mailbox ');
            if ($imap->create($mailboxString) == true) {
                if ($imap->setACL($mailboxString, $_user->accountLoginName, 'lrswipcda') !== true) {
                    // failed to set acl
                }
            }
        }
        
        return $this->getUserById($_user);
    }

    /**
     * delete user by id
     *
     * @param   string         $_userId
     */
    public function deleteUser($_userId)
    {
        $user = ($_userId instanceof Tinebase_Model_FullUser) ? $_userId : Tinebase_User::getInstance()->getFullUserById($_userId);
        
        $imap = $this->_getImapConnection();
        
        $mailboxString = $this->_getUserMailbox($user->accountLoginName);
        
        $mailbox = $imap->listMailbox('', $mailboxString);
        
        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . '  imap config: ' . print_r($mailbox, true));
        
        // does mailbox exist at all?
        if (array_key_exists($mailboxString, $mailbox)) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' must delete mailbox ');
            if ($imap->setACL($mailboxString, $this->_config['cyrus']['admin'], 'lrswipcda') === true) {
                $imap->delete($mailboxString);
            }
        }
    }
    
    /**
     * update/set email user password
     * 
     * @param string $_userId
     * @param string $_password
     * @return void
     */
    public function setPassword($_userId, $_password)
    {
        // nothing to be done for cyrus imap server
    }
    
    /**
     * 
     * @return Zend_Mail_Protocol_Imap
     */
    protected function _getImapConnection()
    {
        if (! $this->_imap instanceof Zend_Mail_Protocol_Imap) {
            $this->_imap = new Zend_Mail_Protocol_Imap($this->_config['host'], $this->_config['port']);
            $this->_imap->login($this->_config['cyrus']['admin'], $this->_config['cyrus']['password']);
        }

        return $this->_imap;
    }

    /**
     * get mailbox string for users aka user.loginname
     * 
     * @param  string  $_username  the imap account name
     * @throws Tinebase_Exception_InvalidArgument
     * @return string
     */
    protected function _getUserMailbox($_username)
    {
        $imap = $this->_getImapConnection();
        
        $namespaces = $imap->getNamespace();
        
        if (!isset($namespaces['other'])) {
            throw new Tinebase_Exception_InvalidArgument('other namespace not found');
        }
        
        $mailboxString = $namespaces['other']['name'] . $_username;
        
        return $mailboxString;
    }
}
