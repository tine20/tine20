<?php
/**
 * Expresso Lite Accessible
 * Changes flags of message.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Diogo Santos <diogo.santos@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace Accessible\Mail;

use Accessible\Handler;
use ExpressoLite\Backend\LiteRequestProcessor;
use Accessible\Dispatcher;
use Accessible\Core\ShowFeedback;
use Accessible\Core\MessageIds;

class MarkMessageAsUnread extends Handler
{
    /**
     * @see Accessible\Handler::execute
     */
    public function execute($params)
    {
        if ($params->messageIds === '') { // No message id was specified
            $typeMsg = ShowFeedback::MSG_ERROR;
            $outMsg = 'Não foi possível marcar mensagem como não lida';
        } else {
            $liteRequestProcessor = new LiteRequestProcessor();
            $message = $liteRequestProcessor->executeRequest('MarkAsRead', (object) array(
                'ids' => $params->messageIds,
                'asRead' => '0'
            ));

            $typeMsg = ShowFeedback::MSG_SUCCESS;
            $msgCount = MessageIds::messageCount($params->messageIds);
            $outMsg = $msgCount == 1 ?
                '1 mensagem marcada como não lida com sucesso.' :
                "$msgCount mensagens marcadas como não lida com sucesso.";
        }

        Dispatcher::processRequest('Core.ShowFeedback', (object) array(
            'typeMsg' => $typeMsg,
            'message' => $outMsg,
            'destinationText' => 'Voltar para ' . $params->folderName,
            'destinationUrl' => (object) array(
                'action' => 'Mail.Main',
                'params' => array(
                    'folderId' => $params->folderId
                )
            )
        ));
    }
}
