<?php
/**
 * Expresso Lite Accessible
 * All message action that needs confirmation, checking whether one
 * or more messages have been previously marked, before completion.
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
use Accessible\Mail\ProcessMessageAction;
use Accessible\Core\MessageIds;

class ConfirmMessageAction extends Handler
{
    /**
     * @var OPTION_OK.
     */
    const OPTION_OK = 'Ok';

    /**
     * @var OPTION_CANCEL.
     */
    const OPTION_CANCEL = 'Cancelar';

    /**
     * @var CHECKED_MESSAGES_EMPTY.
     */
    const CHECKED_MESSAGES_EMPTY = 'Nenhuma mensagem foi selecionada. Por favor, retorne e selecione uma ou mais mensagens.';

    /**
     * @see Accessible\Handler::execute
     */
    public function execute($params)
    {
        if (!$this->hasAtLeastOneMessageChecked($params)) {
            $messageIds = '';
            $typeFeedbackMsg = ShowFeedback::MSG_ALERT;
        } else {
            $typeFeedbackMsg = ShowFeedback::MSG_CONFIRM;
            $messageIds = MessageIds::paramsToString($params);
        }

        $messageFeedback = $this->prepareConfirmMessageAction($params);
        Dispatcher::processRequest('Core.ShowFeedback', (object) array(
            'typeMsg' => $typeFeedbackMsg,
            'message' => $messageFeedback,
            'destinationText' => 'Voltar para ' . $params->folderName,
            'destinationUrl' => (object) array (
                'action' => 'Mail.Main',
                'params' => array (
                    'folderId' => $params->folderId,
                    'folderName' => $params->folderName,
                    'page' => $params->page
                )
            ),
            'buttons' => $this->prepareConfirmationButtons($messageIds, $params)
        ));
    }

   /**
    * Verify if at least one message was marked before an action execution.
    * When one or more messages are previously marked, in a checkbox from messages display,
    * there will be passed one or more attributes fcalled 'check_XX' in wich 'XX' is
    * dynamically assigned and its value contains a message id
    *
    * @param object $params Contains 'check_' attributes each representing a message id
    * @return boolean true if at least one message was checked, false otherwise
    */
    private function hasAtLeastOneMessageChecked($params)
    {
        $objAttributeKeysToArray = array_keys(get_object_vars($params));
        return count(preg_grep('/check_/', $objAttributeKeysToArray)) > 0;
    }

    /**
     * Sets the action process to be executed and creates the confirmation button options
     * for message actions that requires confirmation
     *
     * @param string $messageIds Not empty and has zero or more message ids(comma separated)
     * @param object $params Contains the request confirmation buttons parameters
     * @return null if no message id is passed, object array Containing confirmation buttons otherwise
     */
    private function prepareConfirmationButtons($messageIds, $params)
    {
        if (empty($messageIds) || is_null($params)) {
            return null;
        }

        return (object) array(
            (object) array(
                'action' => 'Mail.ProcessMessageAction',
                'params' => array(
                    'folderId' => $params->folderId,
                    'folderName' => $params->folderName,
                    'page' => $params->page,
                    'messageIds' => $messageIds,
                    'actionProcess' => $params->actionProcess,
                    'confirmOption' => self::OPTION_OK
                )
            ),
            (object) array (
                'action' => 'Mail.ProcessMessageAction',
                'params' => array(
                    'folderId' => $params->folderId,
                    'folderName' => $params->folderName,
                    'page' => $params->page,
                    'messageIds' => $messageIds,
                    'actionProcess' => $params->actionProcess,
                    'confirmOption' => self::OPTION_CANCEL
                )
            )
        );
    }

    /**
     * Prepares the confirmation text message to an action execution that requires it
     *
     *
     * @param string $messageIds Not empty and has one or more message ids(comma separated)
     * @return string Confirmation text message
     */
    private function prepareConfirmMessageAction($params)
    {
        if (!$this->hasAtLeastOneMessageChecked($params)) {
            return self::CHECKED_MESSAGES_EMPTY;
        } else {
            $messageIds = MessageIds::paramsToString($params);
            $countMessagesId = $this->getNumberOfMessageIds($messageIds);

            $messagesSelected = $countMessagesId === 1 ?
                '1 mensagem foi selecionada.' :
                "$countMessagesId mensagens foram selecionadas.";

            return "$messagesSelected<br /> Deseja ". strtolower($params->actionProcess) . '?';
        }
    }

    /**
     * Creates a string comma separated of message ids
     *
     * @param object $params Contains 'check_' attributes each representing a message id
     * @return string Comma separated of message ids
     */
    private function toStringOfMessagesId($params)
    {
        $objVarToArray = get_object_vars($params);
        foreach($objVarToArray as $key => $value) {
            if (!preg_match('/check_/', $key)) {
                unset($objVarToArray[$key]);
            }
        }

        return implode(',', $objVarToArray);
    }

    /**
     * Gets the number of messages id
     *
     * @param string $messageIds Not empty and has one or more message ids(comma separated)
     * @return int A value indicating the number of message ids
     */
    private function getNumberOfMessageIds($messageIds)
    {
        return $messageIds === '' ? 0 : count(explode(',', $messageIds));
    }

    /**
     * Validates the action confirmation option of the request
     *
     * @param object $params Contains the request confirm option
     * @return boolean true If the request confirm option is equal to 'OPTION_OK', false otherwise
     */
    public static function isActionConfirmOk($params)
    {
        return $params->confirmOption === self::OPTION_OK;
    }
}
