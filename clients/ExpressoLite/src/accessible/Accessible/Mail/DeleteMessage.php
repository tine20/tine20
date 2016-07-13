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

    /**
     * @var SINGLE_MESSAGE_FROM_TRASH.
     */
    const SINGLE_MESSAGE_FROM_TRASH = 'Mensagem apagada com sucesso';

    /**
     * @var MULTIPLE_MESSAGE_FROM_TRASH.
     */
    const MULTIPLE_MESSAGE_FROM_TRASH = 'Mensagens apagadas com sucesso';

    /**
     * @var SINGLE_MESSAGE_TO_TRASH.
     */
    const SINGLE_MESSAGE_TO_TRASH = 'Mensagem movida para a lixeira com sucesso';

    /**
     * @var MULTIPLE_MESSAGE_TO_TRASH.
     */
    const MULTIPLE_MESSAGE_TO_TRASH = 'Mensagens movidas para a lixeira com sucesso';

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
            'messages' => $params->messageIds,
            'forever' => $isTrashFolder ? '1' : '0'
        ));

        $outMsg = $this->getFormattedFeedbackMsg($params->messageIds, $isTrashFolder);

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

    /**
     * Format feedback message for deleting message action.
     *
     * @param string $messageIds Message ids concatenated by comma
     * @param boolean $isTrashFolder Indicate whether the current folder
     *                               is the trash or not
     *
     * @return string Formatted feedback message according to the number
     *                of messages and the current folder
     */
    private function getFormattedFeedbackMsg($messageIds, $isTrashFolder)
    {
        if ($isTrashFolder) {
            $formattedMsg = count(explode(',', $messageIds)) > 1 ?
                self::MULTIPLE_MESSAGE_FROM_TRASH :
                self::SINGLE_MESSAGE_FROM_TRASH;
        } else {
            $formattedMsg = count(explode(',', $messageIds)) > 1 ?
                self::MULTIPLE_MESSAGE_TO_TRASH :
                self::SINGLE_MESSAGE_TO_TRASH;
        }
        return $formattedMsg;
    }
}
