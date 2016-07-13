<?php
/**
 * Expresso Lite Accessible
 * Shows information about a calendar event.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Diogo Santos <diogo.santos@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */

namespace Accessible\Calendar;

use Accessible\Handler;
use ExpressoLite\Backend\LiteRequestProcessor;
use ExpressoLite\Backend\TineSessionRepository;
use Accessible\Core\DateUtils;
use Accessible\Dispatcher;
use Accessible\Core\ShowFeedback;
use Accessible\Core\EventUtils;

class OpenEvent extends Handler
{
    /**
     * @see Accessible\Handler::execute
     */
    public function execute($params)
    {
        $isParamsOkToOpenEvent = $this->validateParamsToOpenEvent($params);
        $event = $isParamsOkToOpenEvent ? $this->retrieveCalendarEvent($params) : null;

        if ($isParamsOkToOpenEvent && !is_null($event)) {
            $formtEvInfo = $this->formatEventInformation($event);
            $currentEmailUser = TineSessionRepository::getTineSession()->getAttribute('Expressomail.email');
            $eventHasNotOccurred = EventUtils::checkEventHasNotOccurred($event->from);
            $userAllowedToConfirm = EventUtils::isUserAllowedToConfirmEvent( (object) array(
                    'currentEmailUser' => $currentEmailUser,
                    'attendees'        => (object) $event->attendees,
            ));

            $this->showTemplate('OpenEventTemplate', (object) array(
                'lnkBackToCalendar' => $this->makeUrl('Calendar.Main', array(
                    'month' => $params->monthVal,
                    'year' => $params->yearVal,
                    'calendarId' => $params->calendarId
                )),
                'date'                   => $formtEvInfo->date,
                'summary'                => $formtEvInfo->summary,
                'schedule'               => $formtEvInfo->schedule,
                'location'               => $formtEvInfo->location,
                'description'            => $formtEvInfo->description,
                'organizerName'          => $formtEvInfo->organizerName,
                'organizerOrgUnitRegion' => $formtEvInfo->organizerOrgUnitRegion,
                'countAttendees'         => count($formtEvInfo->attendees),
                'attendeesInformation'   => $this->formatAttendeesInformation(
                    $formtEvInfo->attendees
                ),
                'lnkAccepted' => $this->makeUrl('Calendar.EventConfirmation', array(
                    'idEvent'      => $params->idEvent,
                    'confirmation' => EventUtils::EVENTS_CONFIRM_ACCEPTED,
                    'month'        => $params->monthVal,
                    'year'         => $params->yearVal,
                    'calendarId'   => $params->calendarId,
                    'from'         => $params->from,
                    'until'        => $params->until
                )),
                'lnkDeclined' => $this->makeUrl('Calendar.EventConfirmation', array(
                    'idEvent'      => $params->idEvent,
                    'confirmation' => EventUtils::EVENTS_CONFIRM_DECLINED,
                    'month'        => $params->monthVal,
                    'year'         => $params->yearVal,
                    'calendarId'   => $params->calendarId,
                    'from'         => $params->from,
                    'until'        => $params->until
                )),
                'lnkTentative' => $this->makeUrl('Calendar.EventConfirmation', array(
                    'idEvent'      => $params->idEvent,
                    'confirmation' => EventUtils::EVENTS_CONFIRM_TENTATIVE,
                    'month'        => $params->monthVal,
                    'year'         => $params->yearVal,
                    'calendarId'   => $params->calendarId,
                    'from'         => $params->from,
                    'until'        => $params->until
                )),
                'lnkNeedsAction'   => $this->makeUrl('Calendar.EventConfirmation', array(
                    'idEvent'      => $params->idEvent,
                    'confirmation' => EventUtils::EVENTS_CONFIRM_NEEDS_ACTION,
                    'month'        => $params->monthVal,
                    'year'         => $params->yearVal,
                    'calendarId'   => $params->calendarId,
                    'from'         => $params->from,
                    'until'        => $params->until
                )),
                'isUserAllowedToConfirm' => $eventHasNotOccurred && $userAllowedToConfirm
            ));
        } else { // At this point something was not properly correct to open the event
            Dispatcher::processRequest('Core.ShowFeedback', (object) array (
                'typeMsg' => ShowFeedback::MSG_ERROR,
                'message' => 'Não foi possível acessar as informações desse evento!',
                'destinationText' => 'Voltar para o calendário',
                'destinationUrl' => (object) array(
                    'action' => 'Calendar.Main',
                    'params' => array (
                        'calendarId' => $params->calendarId,
                        'month' => $params->monthVal,
                        'year' => $params->yearVal,
                    )
                )
            ));
        }
    }

    /**
     * Validate the parameters required for viewing a particular event.
     *
     * @param stdClass $params Initial request to calendar module. It is expected the information
     *                         about date and time in from (->from) and until values (->until),
     *                         the id of the current calendar (->calendarId) and the id of the
     *                         event (->idEvent) to be viewed.
     * @return boolean True if all params (->calendarId), (->from), (->until) and (->eventId)
     *                 were set, false otherwise
     */
    private function validateParamsToOpenEvent($params)
    {
        return isset($params->from) && isset($params->until)
            && isset($params->calendarId) && isset($params->idEvent);
    }

    /**
     * Retrieve the calendar event object.
     *
     * @param stdClass $params Contains the request's parameters to calendar module. The information
     *                         about date and time in from value (->from) and until value (->until);
     *                         also, it must be informed the id of the current calendar (->calendarId)
     * @return stdClass The Event object if it possible to retrieve the event data, null otherwise
     */
    private function retrieveCalendarEvent($params)
    {
        $foundEvent = null;
        $liteRequestProcessor = new LiteRequestProcessor();

        $message = $liteRequestProcessor->executeRequest('SearchEvents', (object) array(
            'from' => $params->from,
            'until' => $params->until,
            'calendarId' => $params->calendarId,
            'timeZone' => TineSessionRepository::getTineSession()->getAttribute('Tinebase.timeZone')
        ));

        foreach ($message->events AS $event) {
            if ($params->idEvent === $event->id) {
                $foundEvent = $event;
                break;
            }
        }

        return $foundEvent;
    }

    /**
     * Format information about a particular calendar event to be viewed.
     *
     * @param stdClass $event The Event object
     * @return stdClass An object containning formatted event information like date (->date),
     *                  time (->schedule), the organizer's name (->organizerName), organization
     *                  unit and region (->orgUnitRegion), the total count of attendees
     *                  (->countAttendees), the list of attendees (->attendees), the event's
     *                  summary (->summary) and the description about the event (->description)
     */
    private function formatEventInformation($event)
    {
        $fromInfo  = DateUtils::getInfomationAboutDate($event->from);
        $untilInfo = DateUtils::getInfomationAboutDate($event->until);

        return (object) array(
            'schedule' => $fromInfo->timeVal . ' às ' . $untilInfo->timeVal,
            'date' => $fromInfo->weekdayName . ', ' . $fromInfo->dayVal . ' de '
                    . $fromInfo->monthName . ' de ' . $fromInfo->yearVal . '.',
            'attendees'     => $event->attendees,
            'organizerName' => $event->organizer->name,
            'organizerOrgUnitRegion' => $event->organizer->orgUnit . ', ' . $event->organizer->region,
            'summary'     => empty($event->summary) ?
                EventUtils::EVENTS_WITHOUT_SUMMARY : $event->summary,
            'location'    => empty($event->location) ?
                EventUtils::EVENTS_WITHOUT_LOCATION : $event->location,
            'description' => empty($event->description) ?
                EventUtils::EVENTS_WITHOUT_DESCRIPTION : nl2br($event->description),
        );
    }

    /**
     * Format information, about the attendees of a calendar event, to be viewed. The current user
     * logged in, if he is one of the attendees, so he must be the first exhibited attendee. The
     * remaining attendees will be group by, in the following order, that have the confirmation
     * type: ACCEPTED, TENTATIVE, NEEDS-ACTION and DECLINED.
     *
     * @param array $attendees An array of attendees objects
     * @return array An array of formatted information about attendees in wich element contains
     *               the name (->name) of the attendee, it's current confirmation (->userConfirm),
     *               the icon css class of the current confirmation type (->userConfirmIcon) and
     *               the organization and region about attendee's role
     */
    private function formatAttendeesInformation($attendees)
    {
        // Email of current logged in user, because we'll search for it in attendees list
        $currUserEmail = TineSessionRepository::getTineSession()->getAttribute('Expressomail.email');

        // Array which indexes are confirmation types, each one containing an empty list of attendees
        $result = EventUtils::prepareListOfConfirmationTypesToGroupAttendees();

        $currUserAttendee = null;
        $userHasNotFounded = true;
        foreach($attendees as $attendee) {
            // Formatting the description for the current attendee event confirmation type
            $attendee->confirmStatus = EventUtils::getConfirmationDescription($attendee->confirmation);

            // Verifying if the logged in user is also an attendee of the event.
            if ($userHasNotFounded && $attendee->email === $currUserEmail) {
                $currUserAttendee = $attendee;
                $userHasNotFounded = false;
            } else {
                $result[$attendee->confirmation][] = $attendee;
            }
        }

        // Checking whether the logged in user is a attendee of the current event
        if (!$userHasNotFounded && !is_null($currUserAttendee)) {
            array_unshift($result, array($currUserAttendee)); // First one to be displayed
        }

        return EventUtils::sortAttendeesByName($result);
    }
}
