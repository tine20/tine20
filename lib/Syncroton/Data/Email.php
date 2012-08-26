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
    /**
     * used by unit tests only to simulated added folders
     */
    public static $entries = array();
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IDataEmail::forwardEmail()
     */
    public function forwardEmail($source, $inputStream, $saveInSent, $replaceMime)
    {
        // forward email
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_AData::getFileReference()
     */
    public function getFileReference($fileReference)
    {
        list($messageId, $partId) = explode('-', $fileReference, 2);
    
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
    
    protected function _initData()
    {
        /**
        * used by unit tests only to simulated added folders
        */
        Syncroton_Data_AData::$folders[get_class($this)] = array(
            'emailInboxFolderId' => new Syncroton_Model_Folder(array(
                'id'          => sha1(mt_rand(). microtime()),
                'serverId'    => 'emailInboxFolderId',
                'parentId'    => 0,
                'displayName' => 'Inbox',
                'type'        => Syncroton_Command_FolderSync::FOLDERTYPE_INBOX
            )),
            'emailSentFolderId' => new Syncroton_Model_Folder(array(
                'id'          => sha1(mt_rand(). microtime()),
                'serverId'    => 'emailSentFolderId',
                'parentId'    => 0,
                'displayName' => 'Sent',
                'type'        => Syncroton_Command_FolderSync::FOLDERTYPE_SENTMAIL
            ))
        );
        
        /**
         * used by unit tests only to simulated added folders
         */
        
        $entries = $this->getServerEntries('emailInboxFolderId', 1);
        
        if (count($entries) == 0) {
            $testData = array(
                'emailInboxFolderId' => array(
                    'email1' => new Syncroton_Model_Email(array(
                        'accountId'    => 'FooBar',
                        'attachments'  => array(
                            new Syncroton_Model_EmailAttachment(array(
                                'fileReference' => '12345abcd',
                                'umAttOrder'    => 1
                            ))
                        ),
                        'categories'   => array('123', '456'),
                        'cc'           => 'l.kneschke@metaways.de',
                        'dateReceived' => new DateTime('2012-03-21 14:00:00', new DateTimeZone('UTC')), 
                        'from'         => 'k.kneschke@metaways.de',
                        'subject'      => 'Test Subject',
                        'to'           => 'j.kneschke@metaways.de',
                        'read'         => 1,
                        'body'         => new Syncroton_Model_EmailBody(array(
                            'type'              => Syncroton_Model_EmailBody::TYPE_PLAINTEXT, 
                            'data'              => 'Hello!', 
                            'truncated'         => true, 
                            'estimatedDataSize' => 600
                        ))
                    )),
                )
            );
            
            foreach ($testData['emailInboxFolderId'] as $data) {
                $this->createEntry('emailInboxFolderId', $data);
            }
        }
    }
}

