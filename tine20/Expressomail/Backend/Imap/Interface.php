<?php
/**
 * Tine 2.0
 *
 * @package     Expressomail
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 * @copyright   Copyright (c) 2016 SERPRO (http://www.serpro.gov.br)
 *
 */

/**
 * Expressomail IMAP backend interface
 * Contains only common methods of Zend_Mail_Storage_Imap
 *
 * @package     Expressomail
 * @subpackage  Backend
 */
interface Expressomail_Backend_Imap_Interface
{
    /**
     * create mailbox and all default system folders if it doesn't exist
     * @return true if a new inbox was created, otherwise false is returned
     * @throws Zend_Mail_Storage_Exception and Expressomail_Exception_IMAP
     */
    public function createDefaultImapSystemFoldersIfNecessary($params);

    /**
     * Check specific permission on folder
     *
     * @param string $_folder
     * @param integer $_acl
     * @return boolean
     */
    public function checkACL($_folder, $_acl);

    /**
     * login to imap server
     *
     * @param object $_params
     * @return void
     * @throws Expressomail_Exception_IMAPInvalidCredentials
     * @throws Expressomail_Exception_IMAPServiceUnavailable
     */
    public function connectAndLogin($_params);

    /**
     * select given folder
     *
     * - overwritten to get results (UIDNEXT, UIDVALIDITY, ...)
     *
     * folder must be selectable!
     *
     * @param  Zend_Mail_Storage_Folder|string $globalName global name of folder or instance for subfolder
     * @return array with folder values
     * @throws Zend_Mail_Storage_Exception
     * @throws Zend_Mail_Protocol_Exception
     */
    public function selectFolder($globalName);

    /**
     * examine given folder
     *
     * - overwritten to get results (UIDNEXT, UIDVALIDITY, ...)
     *
     * folder must be selectable!
     *
     * @param  Zend_Mail_Storage_Folder|string $globalName global name of folder or instance for subfolder
     * @return array with folder values
     * @throws Zend_Mail_Storage_Exception
     * @throws Zend_Mail_Protocol_Exception
     */
    public function examineFolder($globalName);

    /**
     * get folder status
     *
     * @param  Zend_Mail_Storage_Folder|string $globalName global name of folder or instance for subfolder
     * @return array with folder values
     */
    public function getFolderStatus($globalName);

    /**
     * Fetch a message
     *
     * @param int $id number of message
     * @return Zend_Mail_Message
     * @throws Zend_Mail_Protocol_Exception
     */
    public function getMessage($id);

    /**
     * set flags for message
     *
     * NOTE: this method can't set the recent flag.
     *
     * @param  int   $id    number of message
     * @param  array $flags new flags for message
     * @throws Zend_Mail_Storage_Exception
     */
    public function setFlags($id, $flags);

    /**
     * do a search request
     *
     * This method is currently marked as internal as the API might change and is not
     * safe if you don't take precautions.
     *
     * @return array message ids
     */
    public function search(array $params);

    /**
     * add flags
     *
     * @param int $id
     * @param array $flags
     */
    public function addFlags($id, $flags);

    /**
     * clear flags
     *
     * @param int $id
     * @param array $flags
     */
    public function clearFlags($id, $flags);

    /**
     * return uid for given message numbers
     *
     * @param int $from
     * @param int|null $to
     * @return array
     */
    public function getUid($from, $to = null);

    /**
     * get messages summary
     *
     * @param int $from
     * @param int|null $to
     * @return array with $this->_messageClass (Expressomail_Message)
     */
    public function getSummary($from, $to = null, $_useUid = null, $_folderId = NULL);

    /**
     * get messages flags
     *
     * @param int $from
     * @param int|null $to
     * @return array of flags
     */
    public function getFlags($from, $to = null, $_useUid = null);

    /**
     * parse message structure
     *
     * @param array $_structure
     * @param integer $_partId
     * @return array structure
     */
    public function parseStructure($_structure, $_partId = null);

    /**
     * validates that messageUid still exists on imap server
     * @param $from
     * @param $to
     */
    public function messageUidExists($from, $to = null);

    /**
     * get uids by uid
     *
     * @param int $from
     * @param int|null $to
     * @return array with uids
     */
    public function getUidbyUid($from, $to = null);

    /**
     * @param string $from
     * @param string $to
     */
    public function resolveMessageSequence($from, $to = null);

    /**
     * @param string $from
     * @param string $to
     * @throws Zend_Mail_Protocol_Exception
     */
    public function resolveMessageUid($from, $to = null);

    /**
     * Remove a message from server. If you're doing that from a web enviroment
     * you should be careful and use a uniqueid as parameter if possible to
     * identify the message.
     *
     * @param   int $id number of message
     * @return  void
     * @throws  Expressomail_Exception_IMAP
     */
    public function removeMessage($id);

    /**
     * copy an existing message
     *
     * @param  int|array                       $id     number of message(s)
     * @param  string|Zend_Mail_Storage_Folder $folder name or instance of targer folder
     * @return void
     * @throws Expressomail_Exception_IMAP
     */
    public function copyMessage($id, $folder);

    /**
     * get server capabilities and namespace
     *
     * @return array
     */
    public function getCapabilityAndNamespace();

    /**
     * empty complete folder by setting \Deleted flag and expunge afterwards
     *
     * @param string $globalName
     * @return void
     * @throws Zend_Mail_Storage_Exception
     */
    public function emptyFolder($globalName);

    /**
     * remove all messages marked as deleted
     *
     * @param string $globalName
     * @return void
     * @throws Zend_Mail_Storage_Exception
     */
    public function expunge($globalName);

    /**
     * get quota for mailbox
     *
     * @param string $_mailbox
     * @return array quota info
     */
    public function getQuota($_mailbox);

    /**
     * set quota for specified mailbox
     *
     * @see http://tools.ietf.org/html/rfc2087
     * @param  string  $mailbox   the mailbox (user/example)
     * @param  string  $resource  the resource (STORAGE or MESSAGE)
     * @param  int     $limit     the limit (set to null to remove limit)
     */
    public function setQuota($mailbox = '*', $resource = 'STORAGE', $limit=null);

        /**
     * Get sorted messages through the Impa sort command
     *
     * @param array $params
     * @param boolean $uid
     * @param boolean $descending
     * @param array $search
     * @param string $charset
     * @return array
     *
     * @todo verify capabilities and throw an exception if server don't implement sort extension
     */
    public function sort(array $params, array $search = NULL, $charset = 'UTF-8');

    /**
     * get folder Acls
     *
     * @param  Zend_Mail_Storage_Folder|string $globalName global name of folder or instance for subfolder
     * @param  bool $returnOwnerACL true if it will return owner's ACL
     * @return array with folder values
     */
    public function getFolderAcls($_globalName, $returnOwnerACL = FALSE);

     /**
     * get folder Acls
     *
     * @param  Zend_Mail_Storage_Folder|string $globalName global name of folder or instance for subfolder
     * @return array with folder values
     */
    public function getUsersWithSendAsAcl($_folders);

     /**
     * set folder Acls
     *
     * @param  Zend_Mail_Storage_Folder|string $globalName global name of folder or instance for subfolder
     * @param  string $acls acls
     * @return array with folder values
     */
    public function setFolderAcls($globalName,$acls);

     /**
     * Fetch Messages Ids changed since $modseq
     *
     * @param  string      $box   -  Folder to select
     * @param  integer     $modseq  -  $modSeq to search messages since
     * @return array       list of messages ids, flags changed since last modseq
     * @throws Zend_Mail_Protocol_Exception
     */
    public function fetchIdsChangedSinceModSeq($box, $modseq);

    /**
     * Returns the current usernamespace
     *
     * @return type string
     */
    public function getUserNameSpace();

    /**
     * Set shared seen value to imap
     *
     * @param string $value
     * @return boolean return operation's success status
     */
    public function setSharedSeen($value);

    /**
     * Get Shared seen value
     *
     * @return boolean shared seen value
     */
    public function getSharedSeen();

    /**
     * Getting cyrus  murder backend hostname
     *
     * @return mixed
     */
    public function getCyrusMurderBackend();
}