<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Data
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync Sync command
 *
 * @package     Model
 */
class Syncroton_Data_Email extends Syncroton_Data_AData implements Syncroton_Data_IDataEmail
{
    protected $_supportedFolderTypes = array(
        Syncroton_Command_FolderSync::FOLDERTYPE_DELETEDITEMS,
        Syncroton_Command_FolderSync::FOLDERTYPE_DRAFTS,
        Syncroton_Command_FolderSync::FOLDERTYPE_INBOX,
        Syncroton_Command_FolderSync::FOLDERTYPE_MAIL_USER_CREATED,
        Syncroton_Command_FolderSync::FOLDERTYPE_OUTBOX,
        Syncroton_Command_FolderSync::FOLDERTYPE_SENTMAIL
    );
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IDataEmail::forwardEmail()
     */
    public function forwardEmail($source, $inputStream, $saveInSent, $replaceMime)
    {
        if ($inputStream == 'triggerException') {
            throw new Syncroton_Exception_Status(Syncroton_Exception_Status::MAILBOX_SERVER_OFFLINE);
        }
        
        // forward email
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_AData::getFileReference()
     */
    public function getFileReference($fileReference)
    {
        list($messageId, $partId) = explode(Syncroton_Data_AData::LONGID_DELIMITER, $fileReference, 2);
    
        // example code
        return new Syncroton_Model_FileReference(array(
            'contentType' => 'text/plain',
            'data'        => 'Lars'
        ));
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IDataEmail::replyEmail()
     */
    public function replyEmail($source, $inputStream, $saveInSent, $replaceMime)
    {
        // forward email
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_AData::updateEntry()
     */
    public function updateEntry($_folderId, $_serverId, Syncroton_Model_IEntry $_entry)
    {
        // not used by email
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IDataEmail::sendEmail()
     */
    public function sendEmail($inputStream, $saveInSent)
    {
        if ($inputStream == 'triggerException') {
            throw new Syncroton_Exception_Status(Syncroton_Exception_Status::MAILBOX_SERVER_OFFLINE);
        }
        // send email
    }
}

