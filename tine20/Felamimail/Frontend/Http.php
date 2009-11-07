<?php
/**
 * backend class for Tinebase_Http_Server
 *
 * This class handles all Http requests for the felamimail application
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
class Felamimail_Frontend_Http extends Tinebase_Frontend_Http_Abstract
{
    /**
     * app name
     *
     * @var string
     */
    protected $_applicationName = 'Felamimail';
    
    /**
     * Returns all JS files which must be included for this app
     *
     * @return array Array of filenames
     * 
     * @todo add filename/content disposition
     * @todo use stream?
     */
    public function getJsFilesToInclude()
    {
        return array(
            'Felamimail/js/Models.js',
            'Felamimail/js/Felamimail.js',
            'Felamimail/js/FelamimailTreeLoader.js',
            'Felamimail/js/FelamimailTreePanel.js',
            'Felamimail/js/FelamimailGridDetailsPanel.js',
            'Felamimail/js/FelamimailGridPanel.js',
            'Felamimail/js/FelamimailMessageEditDialog.js',
            'Felamimail/js/FelamimailAccountEditDialog.js',
            'Addressbook/js/SearchCombo.js',
            'Felamimail/js/FelamimailRecipientGrid.js',
            'Felamimail/js/FelamimailAttachmentGrid.js',
        );
    }

    /**
     * download email attachment
     *
     * @param string $messageId
     * @param integer $partId
     */
    public function downloadAttachment($messageId, $partId)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Downloading Attachment ' . $partId . ' of message with id ' . $messageId
        );
        
        // get message part
        try {
            $part = Felamimail_Controller_Message::getInstance()->getMessagePart($messageId, $partId);
            
            if ($part !== NULL) {
                $headers = $part->getHeaders();
                
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                    . ' Attachment headers:' . print_r($headers, true)
                );
                
                preg_match(Felamimail_Model_Message::ATTACHMENT_FILENAME_REGEXP, $headers['content-disposition'], $matches);
                $filename = (isset($matches[0])) ? $matches[0] : 'filename'; 
                
                header("Pragma: public");
                header("Cache-Control: max-age=0");
                header('Content-Disposition: attachment; ' . $filename);
                header("Content-Description: email attachment");
                header("Content-type: " . $headers['content-type']);
                 
                $content = $part->getContent();
                switch (strtolower($headers['content-transfer-encoding'])) {
                    case 'base64':
                        $content = base64_decode($content);
                        break;
                    case 'quoted-printable':
                        $content = quoted_printable_decode($content);
                        break;
                }
                echo $content;
            }
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . $e->getMessage());
        }
        exit;
    }
}
