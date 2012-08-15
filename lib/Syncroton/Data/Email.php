<?php
/**
 * Syncroton
 *
 * @package     Model
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
    public static $entries = array(
    );
    
    public function forwardEmail($collectionId, $itemId, $inputStream, $saveInSent)
    {
        // forward email
    }
    
    public function getFileReference($fileReference)
    {
        list($messageId, $partId) = explode('-', $fileReference, 2);
    
        // example code
        //$file = $this->_imapBackend->getMessagePart($messageId, $partId);
    
        // example code
        return new Syncroton_Model_FileReference(array(
                'ContentType' => 'text/plain',
                'Data'        => 'Lars'
        ));
    }
    
    public function replyEmail($collectionId, $itemId, $inputStream, $saveInSent)
    {
        // forward email
    }
    
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
                        'AccountId'    => 'FooBar',
                        'Attachments'  => array(
                            new Syncroton_Model_EmailAttachment(array(
                                'FileReference' => '12345abcd',
                                'UmAttOrder'    => 1
                            ))
                        ),
                        'Categories'   => array('123', '456'),
                        'Cc'           => 'l.kneschke@metaways.de',
                        'DateReceived' => new DateTime('2012-03-21 14:00:00', new DateTimeZone('UTC')), 
                        'From'         => 'k.kneschke@metaways.de',
                        'Subject'      => 'Test Subject',
                        'To'           => 'j.kneschke@metaways.de',
                        'Read'         => 1,
                        'Body'         => new Syncroton_Model_EmailBody(array(
                            'Type'              => Syncroton_Model_EmailBody::TYPE_PLAINTEXT, 
                            'Data'              => 'Hello!', 
                            'Truncated'         => true, 
                            'EstimatedDataSize' => 600
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

