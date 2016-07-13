<?php
/**
 * Expresso Lite Accessible
 * Empty trash email folder.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Diogo Santos <diogo.santos@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */

namespace Accessible\Mail;

use Accessible\Handler;
use ExpressoLite\Backend\LiteRequestProcessor;
use ExpressoLite\Backend\TineSessionRepository;
use Accessible\Dispatcher;
use Accessible\Core\ShowFeedback;

class EmptyTrash extends Handler
{
    /**
     * @var EMPTY_TRASH_SUCCESS.
     */
    const EMPTY_TRASH_SUCCESS = 'Emails da lixeira foram apagados com sucesso.';

    /**
     * @see Accessible\Handler::execute
     */
    public function execute($params)
    {
        $liteRequestProcessor = new LiteRequestProcessor();
        $response = $liteRequestProcessor->executeRequest('EmptyTrash', (object) array(
            'trashId' => $params->folderId
        ));

        if ($response->status) {
            $typeMsg = ShowFeedback::MSG_SUCCESS;
            $feedbackMsg = self::EMPTY_TRASH_SUCCESS;
        } else {
            $typeMsg = ShowFeedback::MSG_ERROR;
            $feedbackMsg = $response->message;
        }

        Dispatcher::processRequest('Core.ShowFeedback', (object) array (
            'typeMsg' => $typeMsg,
            'message' => $feedbackMsg,
            'destinationText' => 'Voltar para ' . $params->folderName,
            'destinationUrl' => (object) array(
                'action' => 'Mail.Main',
                'params' => array (
                   'folderId' => $params->folderId,
                   'page' => 1
                )
            )
        ));
    }
}
