<?php
/**
 * Expresso Lite
 * Handler for searchEvent calls.
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLite\Backend\Request;

class SetEventConfirmation extends LiteRequest
{
    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        $eventId = $this->param('id');
        $confirmation = $this->param('confirmation');

        $rawEvent = $this->getRawEventById($eventId);
        $this->setRawEventConfirmation($rawEvent, $confirmation);
        $this->jsonRpc('Calendar.saveEvent', (object) array(
            'recordData' => $rawEvent,
            'checkBusyConflicts' => 1 // faster; can't have a conflict when confirming an event
        ));

        return (object) array(
            'eventId' => $eventId, // no need to return the whole event object again, just confirm
            'confirmation' => $confirmation
        );
    }

    /**
     * Retrieves a raw Tine event object by its ID.
     *
     * @param string $eventId Unique ID of event.
     *
     * @return stdClass Raw Tine event object.
     */
    private function getRawEventById($eventId)
    {
        $response = $this->jsonRpc('Calendar.searchEvents', (object) array(
            'filter' => array(
                (object) array(
                    'field' => 'id',
                    'operator' => 'equals',
                    'value' => $eventId
                )
            ),
            'paging' => (object) array(
                'dir' => 'ASC',
                'limit' => 1, // IDs are unique
                'start' => 0
            )
        ));
        return $response->result->results[0];
    }

    /**
     * Sets the confirmation status for current user on a raw Tine event.
     *
     * @param stdClass $rawEvent Raw Tine event object.
     * @param string   $confirmation User confirmation status: ACCEPTED, DECLINED, NEEDS-ACTION.
     */
    private function setRawEventConfirmation(&$rawEvent, $confirmation)
    {
        $userMail = $this->tineSession->getAttribute('Expressomail.email');

        $rawEvent->attachments = array();
        $rawEvent->send = '';

        foreach ($rawEvent->attendee as &$atd) {
            if (is_object($atd->user_id)) {
                if ($userMail === $atd->user_id->email) {
                    $atd->status = $confirmation;
                    return;
                }
            }
        }
    }
}
