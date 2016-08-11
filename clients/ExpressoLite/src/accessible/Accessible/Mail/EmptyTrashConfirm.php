<?php
/**
 * Expresso Lite Accessible
 * Empty trash prompt confirmation.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Diogo Santos <diogo.santos@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */

namespace Accessible\Mail;

use Accessible\Handler;
use ExpressoLite\Backend\LiteRequestProcessor;
use Accessible\Dispatcher;
use Accessible\Core\ShowFeedback;
use Accessible\Mail\ProcessMessageAction;
use Accessible\Core\MessageIds;

class EmptyTrashConfirm extends Handler
{
    /**
     * @var EMPTY_TRASH_PROMPT_CONFIRMATION
     */
    const EMPTY_TRASH_PROMPT_CONFIRMATION = 'Esta ação irá apagar todas as mensagens da lixeira de forma permanente. Deseja prosseguir?';

    /**
     * @see Accessible\Handler::execute
     */
    public function execute($params)
    {
        Dispatcher::processRequest('Core.ShowFeedback', (object) array(
            'typeMsg' => ShowFeedback::MSG_CONFIRM,
            'message' => self::EMPTY_TRASH_PROMPT_CONFIRMATION,
            'destinationText' => 'Voltar para ' . $params->folderName,
            'destinationUrl' => (object) array (
                'action' => 'Mail.Main',
                'params' => array (
                    'folderId' => $params->folderId,
                    'folderName' => $params->folderName,
                    'page' => 1
                )
            ),
            'buttons' => $this->prepareConfirmationButtons($params)
        ));
    }

    /**
     * Prepare the confirmation button options for empty trash confirmation.
     *
     * @param object $params Contains the request confirmation buttons parameters
     * @return stdclass An object containing confirmation buttons
     */
    private function prepareConfirmationButtons($params)
    {
        return (object) array(
            (object) array(
                'action' => 'Mail.EmptyTrash',
                'params' => array(
                    'folderId' => $params->folderId,
                    'folderName' => $params->folderName,
                    'page' => 1,
                    'actionProcess' => $params->actionProcess,
                    'confirmOption' => ConfirmMessageAction::OPTION_OK
                )
            ),
            (object) array (
                'action' => 'Mail.ProcessMessageAction',
                'params' => array(
                    'folderId' => $params->folderId,
                    'folderName' => $params->folderName,
                    'page' => 1,
                    'actionProcess' => $params->actionProcess,
                    'confirmOption' => ConfirmMessageAction::OPTION_CANCEL
                )
            )
        );
    }
}
