<?php
/**
 * backend class for Tinebase_Http_Server
 *
 * This class handles all Http requests for the felamimail application
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
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
     */
    public function getJsFilesToInclude()
    {
        return array(
            'Felamimail/js/Model.js',
            'Felamimail/js/FolderStore.js',
            'Felamimail/js/FolderSelect.js',
            'Felamimail/js/FolderSelectPanel.js',
            'Felamimail/js/sieve/VacationEditDialog.js',
            'Felamimail/js/sieve/RuleEditDialog.js',
            'Felamimail/js/sieve/RulesGridPanel.js',
            'Felamimail/js/sieve/RulesDialog.js',
            'Felamimail/js/sieve/RuleConditionsPanel.js',
            'Felamimail/js/TreeContextMenu.js',
            'Felamimail/js/TreeLoader.js',
            'Felamimail/js/TreePanel.js',
            'Felamimail/js/GridDetailsPanel.js',
            'Felamimail/js/GridPanel.js',
            'Felamimail/js/MessageDisplayDialog.js',
            'Felamimail/js/MessageEditDialog.js',
            'Felamimail/js/AccountEditDialog.js',
            'Addressbook/js/SearchCombo.js',
            'Felamimail/js/ContactSearchCombo.js',
            'Felamimail/js/RecipientGrid.js',
            'Felamimail/js/Felamimail.js',
            'Felamimail/js/ComposeEditor.js',
        );
    }

    /**
     * download email attachment
     *
     * @param  string  $messageId
     * @param  string  $partId
     */
    public function downloadAttachment($messageId, $partId)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Downloading Attachment ' . $partId . ' of message with id ' . $messageId
        );
        
        $this->_downloadMessagePart($messageId, $partId);
    }

    /**
     * download message
     *
     * @param  string  $messageId
     */
    public function downloadMessage($messageId)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Downloading Message ' . $messageId);
        
        $this->_downloadMessagePart($messageId);
    }
    
    /**
     * download message part
     * 
     * @param string $_messageId
     * @param string $_partId
     */
    protected function _downloadMessagePart($_messageId, $_partId = NULL)
    {
        try {
            $part = Felamimail_Controller_Message::getInstance()->getMessagePart($_messageId, $_partId);
            
            if ($part instanceof Zend_Mime_Part) {
                
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                    . ' ' . $part->filename 
                    . ' ' . $part->type 
                    //. ' ' . stream_get_contents($part->getDecodedStream())
                );
        
                $filename = (! empty($part->filename)) ? $part->filename : $_messageId . '.eml';
                $contentType = ($_partId === NULL) ? Felamimail_Model_Message::CONTENT_TYPE_MESSAGE_RFC822 : $part->type;
                
                header("Pragma: public");
                header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
                header("Cache-Control: max-age=0");
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header("Content-Type: " . $contentType);

                $stream = ($_partId === NULL) ? $part->getRawStream() : $part->getDecodedStream();
                fpassthru($stream);
            }
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Failed to get message part: ' . $e->getMessage());
        }
        exit;
    }
}
