<?php
/**
 * Expresso Lite
 * Handler for saveMessageDraft calls.
 * Originally avaible in Tine.class (prior to the backend refactoring).
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014-2015 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend\Request;

use ExpressoLite\Backend\Request\Utils\MessageUtils;

class SaveMessageDraft extends LiteRequest
{

    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        $draftFolderId = $this->param('draftFolderId');
        $subject = $this->param('subject');
        $body = $this->param('body');
        $to = $this->toArrayOfEmails($this->param('to'));
        $cc = $this->toArrayOfEmails($this->param('cc'));
        $bcc = $this->toArrayOfEmails($this->param('bcc'));
        $isImportant = $this->param('isImportant') == '1';
        $replyToId = $this->emptyAsNull($this->param('replyToId'));
        $forwardFromId = $this->emptyAsNull($this->param('forwardFromId'));
        $origDraftId = $this->emptyAsNull($this->param('origDraftId'));
        $attachs = $this->param('attachs') != '' ? json_decode($this->param('attachs')) : array(); // array of tempFile objects

        $recordData = MessageUtils::buildMessageForSaving(
            $this->tineSession, $subject, $body, $to, $cc, $bcc,
            $isImportant, $replyToId, $forwardFromId, $origDraftId, $attachs
        );

        $this->jsonRpc('Expressomail.saveMessageInFolder', (object) array(
            'folderName' => 'INBOX/Drafts',
            'recordData' => $recordData
        ));

        $draftMsg = $this->processor->executeRequest('searchHeadlines', array(
            'folderIds' => $draftFolderId,
            'start' => 0,
            'limit' => 1
        )); // newest draft


        $result = MessageUtils::addOrClearFlag($this->tineSession, array(
            $draftMsg[0]->id
        ), "\\Seen", true);
        // because Tine saves new draft as unread


        return $result;
    }

    /**
     * Converts a comma separated list of emails to an array
     *
     * @param string $emails Comma separated list of emails
     *
     * @return array Array of email strings
     */
    private function toArrayOfEmails($emails)
    {
        if ($emails != '') {
            return explode(',', $emails);
        } else {
            return array();
        }
    }

    /**
     * Utility method to convert empty strings '' to null, if necessary
     *
     * @param string $string
     *
     * @return null if the string was '', the original $string otherwise
     */
    private function emptyAsNull($string)
    {
        return $string == '' ? null : $string;
    }
}
