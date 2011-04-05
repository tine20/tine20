<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * message flags controller for Felamimail
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_Message_Get extends Felamimail_Controller_Message
{
    /**
     * holds the instance of the singleton
     *
     * @var Felamimail_Controller_Message_Get
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() 
    {
        $this->_backend = new Felamimail_Backend_Cache_Sql_Message();
        $this->_currentAccount = Tinebase_Core::getUser();
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {        
    }
    
    /**
     * the singleton pattern
     *
     * @return Felamimail_Controller_Message_Get
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {            
            self::$_instance = new Felamimail_Controller_Message_Get();
        }
        
        return self::$_instance;
    }
    
    /**
     * get complete message by id
     *
     * @param string|Felamimail_Model_Message  $_id
     * @param boolean                          $_setSeen
     * @return Felamimail_Model_Message
     */
    public function getCompleteMessage($_id, $_partId = null, $_setSeen = FALSE)
    {
        if ($_id instanceof Felamimail_Model_Message) {
            $message = $_id;
        } else {
            $message = $this->get($_id);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . 
            ' Getting message ' . $message->messageuid 
        );
        
        // get account
        $folder = Felamimail_Controller_Folder::getInstance()->get($message->folder_id);
        $account = Felamimail_Controller_Account::getInstance()->get($folder->account_id);
        $mimeType = ($account->display_format == Felamimail_Model_Account::DISPLAY_HTML || $account->display_format == Felamimail_Model_Account::DISPLAY_CONTENT_TYPE) 
            ? Zend_Mime::TYPE_HTML 
            : Zend_Mime::TYPE_TEXT;
        
        $headers     = $this->getMessageHeaders($message, $_partId, true);
        $body        = $this->getMessageBody($message, $_partId, $mimeType, $account, true);
        $attachments = $this->getAttachments($message, $_partId);
        
        // set \Seen flag
        if ($_setSeen && !in_array(Zend_Mail_Storage::FLAG_SEEN, $message->flags)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
                ' Add \Seen flag to msg uid ' . $message->messageuid
            );
            Felamimail_Controller_Message_Flags::getInstance()->addFlags($message, Zend_Mail_Storage::FLAG_SEEN);
            $message->flags[] = Zend_Mail_Storage::FLAG_SEEN;
        }
        
        if ($_partId === null) {
            $message->body        = $body;
            $message->headers     = $headers;
            $message->attachments = $attachments;
        } else {
            // create new object for rfc822 message
            $structure = $message->getPartStructure($_partId, FALSE);
            
            $message = new Felamimail_Model_Message(array(
                'messageuid'  => $message->messageuid,
                'folder_id'   => $message->folder_id,
                'received'    => $message->received,
                'size'        => (array_key_exists('size', $structure)) ? $structure['size'] : 0,
                'partid'      => $_partId,
                'body'        => $body,
                'headers'     => $headers,
                'attachments' => $attachments
            ));

            $message->parseHeaders($headers);
            
            $structure = array_key_exists('messageStructure', $structure) ? $structure['messageStructure'] : $structure;
            $message->parseStructure($structure);
        }
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($message->toArray(), true));
        
        return $message;
    }
}
