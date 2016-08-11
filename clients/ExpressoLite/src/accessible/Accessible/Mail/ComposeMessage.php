<?php
/**
 * Expresso Lite Accessible
 * Email composition.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace Accessible\Mail;

use Accessible\Handler;
use ExpressoLite\Backend\LiteRequestProcessor;
use ExpressoLite\Backend\TineSessionRepository;
use Accessible\Core\MessageUtils;

class ComposeMessage extends Handler
{
    /**
     * @see Accessible\Handler::execute
     */
    public function execute($params)
    {
        // Retrieve original message, if replying or forwarding.
        if (isset($params->reply) || isset($params->replyAll) || isset($params->forward)) {
            $lrp = new LiteRequestProcessor();
            $msg = $lrp->executeRequest('GetMessage', (object) array(
                'id' => $params->messageId
            ));
        } else {
            $msg = null; // compose new mail, not reply/forward
        }

        $this->showTemplate('ComposeMessageTemplate', (object) array(
            'folderId' => $params->folderId,
            'folderName' => $params->folderName,
            'page' => isset($params->page) ? $params->page : '1',
            'actionText' => $this->actionText($params),
            'subject' => $this->formatSubject($params, $msg),
            'to' => (isset($params->reply) || isset($params->replyAll)) ? $msg->from_email : '',
            'cc' => isset($params->replyAll) ? $this->formatCcAddresses($params, $msg) : '',
            'replyToId' => (isset($params->reply) || isset($params->replyAll)) ? $params->messageId : '',
            'forwardFromId' => isset($params->forward) ? $params->messageId : '',
            'signature' => TineSessionRepository::getTineSession()->getAttribute('Expressomail.signature'),
            'quotedBody' => $this->prepareQuotedMessage($params, $msg),
            'lnkBackText' => (isset($params->reply) || isset($params->replyAll) || isset($params->forward)) ?
                'mensagem de origem' : $params->folderName,
            'lnkBackUrl' => $this->returnLink($params),
            'lnkSendMessageAction' => $this->makeUrl('Mail.SendMessage'),
            'existingAttachments' => isset($params->attachments) ? json_decode($params->attachments) : array()
        ));
    }

    /**
     * Returns text for the action being performed on this message composition.
     *
     * @param  stdClass $params Request parameters.
     * @return string           Text of action.
     */
    private function actionText($params)
    {
        if (isset($params->reply)) {
            return 'Responder';
        } else if (isset($params->replyAll)) {
            return 'Responder a todos';
        } else if (isset($params->forward)) {
            return 'Encaminhar';
        }
        return 'Compor';
    }

    /**
     * Formats subject address field, if needed.
     *
     * @param  stdClass $params Request parameters.
     * @param  stdClass $msg    Message object, if replied or forwarded.
     * @return string           Subject of message.
     */
    private function formatSubject($params, $msg = null)
    {
        if ((isset($params->reply) || isset($params->replyAll)) && $msg !== null) {
            return 'Re: ' . $msg->subject;
        } else if (isset($params->forward) && $msg !== null) {
            return 'Fwd: ' . $msg->subject;
        }
        return ''; // composing new message, no subject is given
    }

    /**
     * Returns the URL to go back to previous page.
     *
     * @param  stdClass $params Request parameters.
     * @return string           The return URL.
     */
    private function returnLink($params)
    {
        if (isset($params->reply) || isset($params->replyAll) || isset($params->forward)) {
            return $this->makeUrl('Mail.OpenMessage', array(
                'messageId' => $params->messageId,
                'folderId' => $params->folderId,
                'folderName' => $params->folderName,
                'isTrashFolder' => ($params->folderName === 'Inbox / Lixeira') ? 1 : 0,
                'page' => $params->page
            ));
        }

        return $this->makeUrl('Mail.Main', array(
            'folderId' => $params->folderId,
            'page' => $params->page
        ));
    }

    /**
     * Formats "Cc" address field, if needed.
     *
     * @param  stdClass $params Request parameters.
     * @param  stdClass $msg    Message object, if replied or forwarded.
     * @return string           The return Cc addresses formatted.
     */
    private function formatCcAddresses($params, $msg = null)
    {
        $userEmail = TineSessionRepository::getTineSession()->getAttribute('Expressomail.email');

        if (($key = array_search($userEmail, $msg->to)) !== false) {
            unset($msg->to[$key]);
        }

        if (isset($params->replyAll) && $msg !== null) {
            return implode(', ', array_merge($msg->to, $msg->cc));
        }
        return '';
    }

    /**
     * Formats replied/forwarded message body texts.
     *
     * @param  stdClass $params Request parameters.
     * @param  stdClass $msg    Message object, if replied or forwarded.
     * @return string           Formatted body text.
     */
    private function prepareQuotedMessage($params, $msg = null)
    {
        if (is_null($msg)) {
            return '';
        }

        $formatedDate = date('d/m/Y H:i', strtotime($msg->received));
        $bodyMessage = MessageUtils::getSanitizedBodyContent($msg->body->message);
        $quotedMessage = MessageUtils::getSanitizedBodyContent($msg->body->quoted);

        if ((isset($params->reply) || isset($params->replyAll)) && $msg !== null) {
            return '<br />Em ' . $formatedDate . ', ' .
                $msg->from_name . ' escreveu:' .
                '<blockquote>' . $bodyMessage . '<br />' . $quotedMessage . '</blockquote>';
        } else if (isset($params->forward) && $msg !== null) {
            return '<br />-----Mensagem original-----<br />' .
                '<b>Assunto:</b> ' . $msg->subject . '<br />' .
                '<b>Remetente:</b> "' . $msg->from_name . '" &lt;' . $msg->from_email . '&gt;<br />' .
                '<b>Para:</b> ' . implode(', ', $msg->to) . '<br />' .
                (!empty($msg->cc) ? '<b>Cc:</b> ' . implode(', ', $msg->cc) . '<br />' : '') .
                '<b>Data:</b> ' . $formatedDate . '<br /><br />' .
                $bodyMessage . '<br />' . $quotedMessage;
        }
        return '';
    }
}
