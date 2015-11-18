<?php
/**
 * Expresso Lite
 * Handler for saveMessage calls (self explanatory).
 * Originally avaible in Tine.class (prior to the backend refactoring).
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend\Request;

use ExpressoLite\Backend\Request\Utils\MessageUtils;

class SaveMessage extends LiteRequest
{

    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        $subject = $this->param('subject');
        $body = $this->param('body');
        $to = $this->toArrayOfEmails($this->param('to'));
        $cc = $this->toArrayOfEmails($this->param('cc'));
        $bcc = $this->toArrayOfEmails($this->param('bcc'));
        $isImportant = $this->param('isImportant') == '1';
        $replyToId = $this->emptyAsNull($this->param('replyToId'));
        $forwardFromId = $this->emptyAsNull($this->param('forwardFromId'));
        $origDraftId = $this->emptyAsNull($this->param('origDraftId'));
        $wantConfirm = $this->isParamSet('wantConfirm') ? $this->param('wantConfirm') == '1' : false;

        $attachs = $this->param('attachs');
        if ($attachs != '') {
            $attachs = json_decode($attachs);
        } else {
            $attachs = array();
        }

        $response = $this->jsonRpc('Expressomail.saveMessage', (object) array(
            'recordData' => MessageUtils::buildMessageForSaving(
                $this->tineSession, $subject, $body, $to, $cc, $bcc,
                $isImportant, $replyToId, $forwardFromId, $origDraftId, $attachs, $wantConfirm)
        ));

        return $response->result;
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
