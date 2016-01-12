<?php
/**
 * Expresso Lite Accessible
 * Delete an email message.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Diogo Santos <diogo.santos@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace Accessible\Mail;

use Accessible\Handler;
use ExpressoLite\Backend\LiteRequestProcessor;
use ExpressoLite\Backend\TineSessionRepository;
use Accessible\Dispatcher;
use Accessible\Core\ShowFeedback;

class DeleteMessage extends Handler
{
    /**
     * @see Accessible\Handler::execute
     */

    /**
     * @var TRASH_FOLDER Global name of trash folder.
     */
    const TRASH_FOLDER = 'INBOX/Trash';

    public function execute($params)
    {
        $folders = TineSessionRepository::getTineSession()->getAttribute('folders');
        $isTrashFolder = FALSE;
        foreach ($folders as $fol) {
            if ($fol->id === $params->folderId) {
                $isTrashFolder = $fol->globalName === self::TRASH_FOLDER;
                break;
            }
        }

        $liteRequestProcessor = new LiteRequestProcessor();
        $message = $liteRequestProcessor->executeRequest('DeleteMessages', (object) array(
            'messages' => $params->messageId,
            'forever' => $isTrashFolder ? '1' : '0'
        ));

        $outMsg = ($isTrashFolder) ?
            'Mensagem apagada com sucesso.' :
            'Mensagem movida para lixeira.';

        Dispatcher::processRequest('Core.ShowFeedback', (object) array (
            'typeMsg' => ShowFeedback::MSG_SUCCESS,
            'message' => $outMsg,
            'destinationText' => 'Voltar para ' . $params->folderName,
            'destinationUrl' => (object) array(
                'action' => 'Mail.Main',
                'params' => array (
                   'folderId' => $params->folderId
                )
            )
        ));
    }
}
