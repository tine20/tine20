<?php
/**
 * Expresso Lite Accessible
 * Move one or more messages to the specified folder.
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

class MoveMessage extends Handler
{
    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute($params)
    {
        $liteRequestProcessor = new LiteRequestProcessor();
        $message = $liteRequestProcessor->executeRequest('MoveMessages', (object) array(
            'folder' => $params->folderId,
            'messages' => $params->messageIds
        ));

        $outMsg = count(explode(',',$params->messageIds)) > 1 ?
            count(explode(',',$params->messageIds)) . ' mensagens movidas para a pasta ' . $params->folderName . ' com sucesso.' :
            count(explode(',',$params->messageIds)) . ' mensagem movida para a pasta ' . $params->folderName . ' com sucesso.';

        Dispatcher::processRequest('Core.ShowFeedback', (object) array (
            'typeMsg' => ShowFeedback::MSG_SUCCESS,
            'message' => $outMsg,
            'destinationText' => 'Voltar para ' . $params->currentFolderName,
            'destinationUrl' => (object) array(
                'action' => 'Mail.Main',
                'params' => array (
                   'folderId' => $params->currentFolderId,
                )
            )
        ));
    }
}
