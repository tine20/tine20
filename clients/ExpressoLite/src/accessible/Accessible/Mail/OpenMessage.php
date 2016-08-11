<?php
/**
 * Expresso Lite Accessible
 * Opens an email message.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Diogo Santos <diogo.santos@serpro.gov.br>
 * @author    Edgar Lucca <edgar.lucca@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace Accessible\Mail;

use Accessible\Handler;
use ExpressoLite\Backend\LiteRequestProcessor;
use ExpressoLite\Backend\TineSessionRepository;
use Accessible\Core\DateUtils;
use Accessible\Core\MessageUtils;

class OpenMessage extends Handler
{
    /**
     * @see Accessible\Handler::execute
     */
    public function execute($params)
    {
        $liteRequestProcessor = new LiteRequestProcessor();
        $message = $liteRequestProcessor->executeRequest('GetMessage', (object) array(
            'id' => $params->messageId
        ));

        $markAsRead = $liteRequestProcessor->executeRequest('MarkAsRead', (object) array(
            'ids' => $params->messageId,
            'asRead' => '1'
        ));

        if ($message->subject == '') {
            $message->subject = '(sem assunto)';
        }

        $attachments = $this->formatAttachments($message->attachments, $params->messageId);

        $this->showTemplate('OpenMessageTemplate', (object) array(
            'folderName' => $params->folderName,
            'message' => $message,
            'bodyMessage' => MessageUtils::getSanitizedBodyContent($message->body->message),
            'quotedMessage' => MessageUtils::getSanitizedBodyContent($message->body->quoted),
            'formattedDate' => DateUtils::getFormattedDate(strtotime($message->received), true),
            'page' => $params->page,
            'lnkBack' => $this->makeUrl('Mail.Main', array(
                'folderId' => $params->folderId,
                'page' => $params->page
            )),
            'lnkDelete' => $this->makeUrl('Mail.DeleteMessage', array(
                'folderName' => $params->folderName,
                'messageIds' => $params->messageId,
                'folderId' => $params->folderId
            )),
            'lnkMark' => $this->makeUrl('Mail.MarkMessageAsUnread', array(
                'folderName' => $params->folderName,
                'messageIds' => $params->messageId,
                'folderId' => $params->folderId
            )),
            'lnkReply' => $this->makeUrl('Mail.ComposeMessage', array(
                'folderId' => $params->folderId,
                'folderName' => $params->folderName,
                'page' => $params->page,
                'messageId' => $params->messageId,
                'reply' => 'yes',
                'attachments' => urlencode(json_encode($attachments))
            )),
            'lnkReplyAll' => $this->makeUrl('Mail.ComposeMessage', array(
                'folderId' => $params->folderId,
                'folderName' => $params->folderName,
                'page' => $params->page,
                'messageId' => $params->messageId,
                'replyAll' => 'yes',
                'attachments' => urlencode(json_encode($attachments))
            )),
            'lnkForward' => $this->makeUrl('Mail.ComposeMessage', array(
                'folderId' => $params->folderId,
                'folderName' => $params->folderName,
                'page' => $params->page,
                'messageId' => $params->messageId,
                'forward' => 'yes',
                'attachments' => urlencode(json_encode($attachments))
            )),
            'attachmentsForExhibition' => $attachments,
            'lnkMoveMsgToFolder' => $this->makeUrl('Mail.OpenFolder', array(
                'folderId' => $params->folderId,
                'folderName' => $params->folderName,
                'page' => $params->page,
                'messageIds' => $params->messageId,
                'isMsgBeingMoved' => true
            ))
        ));
    }

    /**
     * Creates attachments information such as extension, filename, size and
     * download link for each attachment.
     *
     * @param array  $attachments      Attachments array from message object.
     * @param string $msgId            ID of current message.
     * @return array $formattedAttachments
     */
    private function formatAttachments(array $attachments, $msgId)
    {
        if (is_null($attachments) || empty($msgId)) {
            return array();
        }

        $formattedAttachments = array();
        foreach ($attachments as $attach) {
            $attach->accessibleExtension = strrchr($attach->filename, '.');
            $attach->accessibleFileName = basename ($attach->filename, $attach->accessibleExtension);
            $attach->accessibleFileSize = $this->sizeFilter($attach->size);

            $attach->lnkDownload = '../api/ajax.php?' .
                'r=downloadAttachment&' .
                'forceDownload&' .
                'fileName=' . urlencode($attach->filename) . '&' .
                'messageId=' . $msgId . '&' .
                'partId=' . $attach->partId;
            array_push($formattedAttachments, $attach);
        }
        return $formattedAttachments;
    }

    /**
     * Filters file size in Bytes, KBytes, MBytes, GBytes, TBytes.
     *
     * @param string $fileSizeBytes       represents size file in bytes.
     */
    public function sizeFilter($fileSizeBytes)
    {
        $base = log($fileSizeBytes, 1024);
        $suffixes = array('bytes', 'KB', 'MB', 'GB', 'TB');

        return round(pow(1024, $base - floor($base)), 0) . ' ' . $suffixes[floor($base)];
    }
}
