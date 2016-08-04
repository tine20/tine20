<?php
/**
 * Expresso Lite Accessible
 * Calendar event confirmation.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Diogo Santos <diogo.santos@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace Accessible\Calendar;

use Accessible\Handler;
use ExpressoLite\Backend\LiteRequestProcessor;
use ExpressoLite\Backend\TineSessionRepository;
use Accessible\Dispatcher;
use Accessible\Core\ShowFeedback;

class EventConfirmation extends Handler
{
    /**
     * @see Accessible\Handler::execute
     */
    public function execute($params)
    {
        $liteRequestProcessor = new LiteRequestProcessor();
        $message = $liteRequestProcessor->executeRequest('setEventConfirmation', (object) array(
            'id' => $params->idEvent,
            'confirmation' => $params->confirmation
        ));

        if ((isset($message->eventId) && isset($message->confirmation))) {
            $outMsg = 'Confirmação de evento realizada com sucesso.';
            $feedbackType = ShowFeedback::MSG_SUCCESS;
        } else {
            $outMsg = 'Não foi possível realizar a confirmação do evento.';
            $feedbackType = ShowFeedback::MSG_SUCCESS;
        }

        Dispatcher::processRequest('Core.ShowFeedback', (object) array (
            'typeMsg' => $feedbackType,
            'message' => $outMsg,
            'destinationText' => 'Voltar para o evento',
            'destinationUrl' => (object) array(
                'action' => 'Calendar.OpenEvent',
                'params' => array (
                    'from' => $params->from,
                    'until' => $params->until,
                    'idEvent' => $params->idEvent,
                    'calendarId' => $params->calendarId,
                    'monthVal' => $params->month,
                    'yearVal' => $params->year
                )
            )
        ));
    }
}
