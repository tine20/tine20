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
            'Felamimail/js/TreeContextMenu.js',
            'Felamimail/js/TreeLoader.js',
            'Felamimail/js/TreePanel.js',
            'Felamimail/js/GridDetailsPanel.js',
            'Felamimail/js/GridPanel.js',
            'Felamimail/js/MessageDisplayDialog.js',
            'Felamimail/js/MessageEditDialog.js',
            'Felamimail/js/VacationEditDialog.js',
            'Felamimail/js/RulesGridPanel.js',
            'Felamimail/js/RulesDialog.js',
            'Felamimail/js/RuleEditDialog.js',
            'Felamimail/js/RuleConditionsPanel.js',
            'Felamimail/js/AccountEditDialog.js',
            'Addressbook/js/SearchCombo.js',
            'Felamimail/js/RecipientGrid.js',
            'Felamimail/js/Felamimail.js',
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
        
        // get message part
        try {
            $part = Felamimail_Controller_Message::getInstance()->getMessagePart($messageId, $partId);
            
            if ($part instanceof Zend_Mime_Part) {
                header("Pragma: public");
                header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
                header("Cache-Control: max-age=0");
                header('Content-Disposition: attachment; filename="' . $part->filename . '"');
                header("Content-Type: " . $part->type);

                fpassthru($part->getDecodedStream());
            }
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' failed to get attachment. ' . $e->getMessage());
        }
        exit;
    }
}
