<?php
/**
 * Expresso Lite Accessible
 * Process any message action, checking the action to be performed
 * and dispatching the request to the correct responsible handler.
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
use Accessible\Mail\ConfirmMessageAction;

class ProcessMessageAction extends Handler
{
    /**
     * @var ACTION_MARK_UNREAD.
     */
    const ACTION_MARK_UNREAD = 'Marcar como não lido';

    /**
     * @see Accessible\Handler::execute
     */
    public function execute($params)
    {
        //check if the action can be executed
        if (!ConfirmMessageAction::isActionConfirmOk($params)) {
            // Message action will not be complete
            Dispatcher::processRequest('Mail.Main', $params);
            return;
        }

        // check wich action to be executed
        if($params->actionProcess === self::ACTION_MARK_UNREAD) {
            Dispatcher::processRequest('Mail.MarkMessageAsUnread', $params);
        } else {
            // action does not exist
            Dispatcher::processRequest('Core.ShowFeedback', (object) array (
                'typeMsg' => ShowFeedback::MSG_ERROR,
                'message' => 'Ação requisitada não existe',
                'destinationText' => 'Voltar para ' . $params->folderName,
                'destinationUrl' => (object) array(
                    'action' => 'Mail.Main',
                    'params' => array (
                        'folderId' => $params->folderId,
                        'page' => $params->page
                    )
                )
            ));
        }
    }
}
