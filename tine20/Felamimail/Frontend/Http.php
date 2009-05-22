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
            'Felamimail/js/FelamimailTreePanel.js',
            'Felamimail/js/FelamimailGridDetailsPanel.js',
            'Felamimail/js/FelamimailGridPanel.js',
            'Felamimail/js/FelamimailEditDialog.js',
            'Addressbook/js/SearchCombo.js',
            'Felamimail/js/FelamimailRecipientGrid.js',
            'Felamimail/js/FelamimailAttachmentGrid.js',
        );
    }

    /**
     * download email attachment
     *
     * @param string $_messageId
     * @param integer $_partId
     */
    public function downloadAttachment($_messageId, $_partId)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Downloading Attachment ' . $_partId . ' of message with id ' . $_messageId
        );
        
        // get message part
        $part = Felamimail_Controller_Message::getInstance()->getMessagePart($_messageId, $_partId);
        
        if ($part !== NULL) {
            $headers = $part->getHeaders();
            
            header("Pragma: public");
            header("Cache-Control: max-age=0");
            header("Content-Disposition: " . $headers['content-disposition']);
            header("Content-Description: email attachment");
            header("Content-type: " . $headers['content-type']); 
            echo $part->getContent();
        }
        exit;
    }
}
