<?php
/**
 * Expresso Lite Accessible
 * Sends a message.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace Accessible\Mail;

use Accessible\Dispatcher;
use Accessible\Handler;
use ExpressoLite\Backend\LiteRequestProcessor;
use ExpressoLite\Backend\TineSessionRepository;
use ExpressoLite\Backend\Request\Utils\MessageUtils;
use Accessible\Core\ShowFeedback;

class SendMessage extends Handler
{
    /**
     * @see Accessible\Handler::execute
     */
    public function execute($params)
    {
        // Treat uploaded files, if any.
        $upFiles = array();
        foreach ($_FILES as $upf) {
            $processed = $this->uploadFile($upf);
            if ($processed !== null) {
                $upFiles[] = $processed;
            }
        }

        // Retrieve original message, if replying or forwarding.
        $lrp = new LiteRequestProcessor();

        if (!empty($params->replyToId) || !empty($params->forwardFromId)) {
            $msg = $lrp->executeRequest('GetMessage', (object) array(
                'id' => !empty($params->replyToId) ?
                    $params->replyToId : $params->forwardFromId
            ));
        } else {
            $msg = null; // compose new mail, not reply/forward
        }

        $lrp->executeRequest('SaveMessage', (object) array(
            'subject' => $params->subject,
            'body' => $this->prepareMessageBody($params, $msg),
            'to' => $params->addrTo,
            'cc' => $params->addrCc,
            'bcc' => $params->addrBcc,
            'isImportant' => isset($params->important) ? '1' : '0',
            'attachs' => empty($upFiles) && (!is_null($msg) && !$msg->has_attachment) ? '' : $this->formatAllAttachs($params, $msg, $upFiles),
            'replyToId' => $params->replyToId,
            'forwardFromId' => $params->forwardFromId,
            'origDraftId' => ''
        ));

        Dispatcher::processRequest('Core.ShowFeedback', (object) array(
            'typeMsg' => ShowFeedback::MSG_SUCCESS,
            'message' => 'Mensagem enviada com sucesso.',
            'destinationText' => 'Voltar para ' . $params->folderName,
            'destinationUrl' => (object) array(
                'action' => 'Mail.Main',
                'params' => array(
                   'folderId' => $params->folderId,
                   'page' => $params->page
                )
            )
        ));
    }

    /**
     * Formats replied/forwarded message body texts.
     *
     * @param  stdClass $params Request parameters.
     * @param  stdClass $msg    Message object, if replied or forwarded.
     * @return string           Formatted body text.
     */
    private function prepareMessageBody($params, $msg = null)
    {
        $out = '';

        if ($msg !== null) {
            $formattedDate = date('d/m/Y H:i', strtotime($msg->received));
            if (!empty($params->replyToId) && $msg !== null) {
                $out = '<br />Em ' . $formattedDate . ', ' .
                    $msg->from_name . ' escreveu:' .
                    '<blockquote>' . $msg->body->message . '<br />' .
                    ($msg->body->quoted !== null ? $msg->body->quoted : '') .
                    '</blockquote>';
            } else if (!empty($params->forwardFromId) && $msg !== null) {
                $out = '<br />-----Mensagem original-----<br />' .
                    '<b>Assunto:</b> ' . $msg->subject . '<br />' .
                    '<b>Remetente:</b> "' . $msg->from_name . '" &lt;' . $msg->from_email . '&gt;<br />' .
                    '<b>Para:</b> ' . implode(', ', $msg->to) . '<br />' .
                    (!empty($msg->cc) ? '<b>Cc:</b> ' . implode(', ', $msg->cc) . '<br />' : '') .
                    '<b>Data:</b> ' . $formattedDate . '<br /><br />' .
                    $msg->body->message . '<br />' .
                    ($msg->body->quoted !== null ? $msg->body->quoted : '');
            }
        }

        return nl2br($params->messageBody) . '<br /><br />' .
            TineSessionRepository::getTineSession()->getAttribute('Expressomail.signature') .
            '<br />' . $out;
    }

    /**
     * Pushes uploaded file into Tine shadows.
     *
     * @param  array $upFileObj Associative array of uploaded $_FILE entry.
     * @return stdClass         Ordinary Tine upload status.
     */
    private function uploadFile(array $upFileObj)
    {
        if (empty($upFileObj['tmp_name']) || $upFileObj['error'] !== 0) {
            return null; // this file upload slot was not used
        }

        return json_decode(MessageUtils::uploadTempFile(
            TineSessionRepository::getTineSession(),
            file_get_contents($upFileObj['tmp_name']),
            $upFileObj['name'],
            $upFileObj['type']
        ));
    }

    /**
     * Formats attachments (existing and new attachments) data to be sent.
     *
     * @param  stdClass $params Request parameters.
     * @param  stdClass $msg Message object, if replied or forwarded.
     * @param  array $newUploadedFiles
     *
     * @return attachments data that ExpressoLite needs to save a message
     */
    private function formatAllAttachs($params, $msg = null, $newUploadedFiles)
    {
        $formattedAttchments = array();

        // When msg is not null is certainly a reply/forwarding email message
        // there may be attachments
        if (!is_null($msg) && !empty($msg->attachments)) {

            // Perhaps there may be existing attachments
            // that should not be sent on reply/forwarding email message
            $attachNamesToBeRemoved = $this->getAttachNamesToBeRemoved($params);

            foreach ($msg->attachments as $attach) {
                if (!in_array($attach->filename, $attachNamesToBeRemoved)) {
                    array_push($formattedAttchments, (object) array(
                        'name' => $attach->filename,
                        'size' => $attach->size,
                        'type' => $attach->{'content-type'},
                        'partId' => $attach->partId
                    ));
                }
            }
        }

        // New attchment files uploaded
        if (!empty($newUploadedFiles)) {
            foreach ($newUploadedFiles as $newUpFile) {
                array_push($formattedAttchments, $newUpFile);
            }
        }

        return json_encode($formattedAttchments);
    }

    /**
     * Gets attachment names that should be removed.
     * All attachments that should be removed are previously marked by a checkbox
     * when the user is reply/forwarding a message with attachments that should not be sent.
     *
     * @param  stdClass $params Request parameters
     * @return array Attachment names
     */
    private function getAttachNamesToBeRemoved($params)
    {
        if (is_null($params)) {
            return array();
        }

        $objVarsToArray = get_object_vars($params);

        $patternCheckboxAttach = '/checkAttach_/';
        return array_intersect_key(
            $objVarsToArray, array_flip(
                preg_grep($patternCheckboxAttach, array_keys($objVarsToArray), 0)
            )
        );
    }
}
