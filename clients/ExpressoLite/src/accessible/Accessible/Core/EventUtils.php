<?php
/**
 * Expresso Lite Accessible
 * Event calendar routines.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Diogo Santos <diogo.santos@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */

namespace Accessible\Core;

use ExpressoLite\Backend\LiteRequestProcessor;
use Accessible\Core\DateUtils;
use \DateTime;

class EventUtils
{
    /**
     * @var EVENTS_CALENDAR_NAME.
     */
    const EVENTS_CALENDAR_NAME = 'Calendário de eventos';

    /**
     * @var EVENTS_NO_SCHEDULED.
     */
    const EVENTS_NO_SCHEDULED = 'Não existem eventos agendados';

    /**
     * @var EVENTS_ONE_SCHEDULED.
     */
    const EVENTS_ONE_SCHEDULED = 'Existe 1 evento agendado';

    /**
     * @var EVENTS_MANY_SCHEDULED.
     */
    const EVENTS_MANY_SCHEDULED = 'eventos agendados';

    /**
     * @var EVENTS_ONE_NOT_OCCURRED.
     */
    const EVENTS_ONE_NOT_OCCURRED = '1 não ocorreu';

    /**
     * @var EVENTS_MANY_NOT_OCCURRED.
     */
    const EVENTS_MANY_NOT_OCCURRED = 'não ocorreram';

    /**
     * @var EVENTS_ONE_NOT_STARTED.
     */
    const EVENTS_ONE_NOT_STARTED = '1 não começou';

    /**
     * @var EVENTS_MANY_NOT_STARTED.
     */
    const EVENTS_MANY_NOT_STARTED = 'não começaram';

    /**
     * @var EVENTS_WITHOUT_SUMMARY.
     */
    const EVENTS_WITHOUT_SUMMARY = 'Não foi definido um assunto deste evento';

    /**
     * @var EVENTS_WITHOUT_SUMMARY.
     */
    const EVENTS_WITHOUT_DESCRIPTION = 'Não foi definida uma descrição deste evento';

    /**
     * @var EVENTS_WITHOUT_SUMMARY.
     */
    const EVENTS_WITHOUT_LOCATION = 'Não foi definido um local para este evento';

    /**
     * @var EVENTS_PREVIOUS_MONTH.
     */
    const EVENTS_PREVIOUS_MONTH = 'mês anterior';

    /**
     * @var EVENTS_CURRENT_MONTH.
     */
    const EVENTS_CURRENT_MONTH = 'mês exibido';

    /**
     * @var EVENTS_NEXT_MONTH.
     */
    const EVENTS_NEXT_MONTH = 'mês seguinte';

    /**
     * @var EVENTS_CONFIRM_ACCEPTED
     */
    const EVENTS_CONFIRM_ACCEPTED = 'ACCEPTED';

    /**
     * @var EVENTS_CONFIRM_ACCEPTED_STATUS.
     */
    const EVENTS_CONFIRM_ACCEPTED_STATUS = 'participação aceita';

    /**
     * @var EVENTS_CONFIRM_TENTATIVE
     */
    const EVENTS_CONFIRM_TENTATIVE = 'TENTATIVE';

    /**
     * @var EVENTS_CONFIRM_TENTATIVE_STATUS.
     */
    const EVENTS_CONFIRM_TENTATIVE_STATUS = 'tentará participar';

    /**
     * @var EVENTS_CONFIRM_NEEDS_ACTION
     */
    const EVENTS_CONFIRM_NEEDS_ACTION = 'NEEDS-ACTION';

    /**
     * @var EVENTS_CONFIRM_NEEDS_ACTION_STATUS.
     */
    const EVENTS_CONFIRM_NEEDS_ACTION_STATUS = 'aguardando resposta';

    /**
     * @var EVENTS_CONFIRM_DECLINED
     */
    const EVENTS_CONFIRM_DECLINED = 'DECLINED';

    /**
     * @var EVENTS_CONFIRM_DECLINED_STATUS.
     */
    const EVENTS_CONFIRM_DECLINED_STATUS = 'participação recusada';

    /**
     * Mapping of event confirmation types and description about calendar events.
     *
     * @var $confirmationTypesAndDescription
     */
    private static $confirmationTypesAndDescription = array(
        self::EVENTS_CONFIRM_ACCEPTED     => self::EVENTS_CONFIRM_ACCEPTED_STATUS,
        self::EVENTS_CONFIRM_TENTATIVE    => self::EVENTS_CONFIRM_TENTATIVE_STATUS,
        self::EVENTS_CONFIRM_NEEDS_ACTION => self::EVENTS_CONFIRM_NEEDS_ACTION_STATUS,
        self::EVENTS_CONFIRM_DECLINED     => self::EVENTS_CONFIRM_DECLINED_STATUS,
    );

    /**
     * This method standardize events date range commonly used in all events
     * calendar routines that needs date range information. Date representation
     * is like in the following format '2016-03-01 00:00'.
     *
     * @param StdClass $dateRange Formatted date range with the month number
     *                            (->monthVal) and year value (->yearVal)
     * @return StdClass An event date range object with 'from' date value (->from)
     *                  and 'until' date value (->until)
     */
    public static function prepareEventsDateRange($dateRange = null)
    {
        if (is_null($dateRange) || (!isset($dateRange->monthVal) && !isset($dateRange->yearVal))){
            // No parameters provided, current year and month as the date range
            $fromVal =  DateUtils::getFirstDayOfThisMonth();
            $untilVal = DateUtils::getLastDayOfThisMonth();
        } else {
            $fromVal =  DateUtils::getFirstDayOfMonth($dateRange->monthVal, $dateRange->yearVal);
            $untilVal = DateUtils::getLastDayOfMonth($dateRange->monthVal, $dateRange->yearVal);
        }
        return (object) array(
            'from' => $fromVal,
            'until' => $untilVal
        );
    }

    /**
     * Given an event listing, formats a string message according to the total
     * count of scheduled events, like 'there are 2 scheduled events' or
     * 'no scheduled events'.
     *
     * @param StdClass $eventListing Event listing
     * @return string Formatted message with the total count of scheduled events
     */
    private static function formatTotalEventScheduled($eventListing)
    {
        $countScheduledEvents = abs(count((array) $eventListing));
        if ($countScheduledEvents === 0) {
            return self::EVENTS_NO_SCHEDULED;
        } else {
           return $countScheduledEvents === 1 ?
               self::EVENTS_ONE_SCHEDULED :
               'Existem ' . $countScheduledEvents .' ' .  self::EVENTS_MANY_SCHEDULED;
        }
    }

    /**
     * Given an event listing, formats a message according the total count of
     * scheduled events that have not occurred yet.
     *
     * @param StdClass $eventListing Event listing
     * @return string Formatted Message with the total count of scheduled events
     *                that have not occurred yet
     */
    private static function formatEventScheduledNotOccurred($eventListing)
    {
        $countEventsNotYetOccurred = 0;
        if (!is_null($eventListing) && count($eventListing) > 0) {
            foreach ($eventListing as $event) {
                if (DateUtils::compareToCurrentDate($event->from)) {
                    $countEventsNotYetOccurred++;
                }
            }
        }

        if ($countEventsNotYetOccurred != 0) {
            return $countEventsNotYetOccurred === 1 ?
                ', sendo que ' . self::EVENTS_ONE_NOT_OCCURRED :
                ', sendo que ' . $countEventsNotYetOccurred . ' ' . self::EVENTS_MANY_NOT_OCCURRED;
        } else {
            return ''; // Any event to occur
        }
    }

    /**
     * Creates a summary of events date range. The summary of entire event listing
     * is like: 'There are seven events scheduled for January 2016 and that 1 did
     * not happen'.
     *
     * @param StdClass $eventListing Event listing
     * @param StdClass $dateRange Event listing
     * @return string The summary of a event date range
     */
    public static function setEventsDateRangeSummary($eventListing, $dateRange)
    {
        return
            self::formatTotalEventScheduled($eventListing) . ' para '
            . DateUtils::getMonthName($dateRange->monthVal) . ' de ' . $dateRange->yearVal
            . self::formatEventScheduledNotOccurred($eventListing) . '.';
    }

    /**
     * Creates a summary of events date range. The summary of today listing is like:
     * 'There are no scheduled events for today, Wednesday, January 20, 2016'.
     *
     * @param StdClass $todayEventListing Event listing
     * @return string The summary of today events date range
     */
    public static function setTodayEventsDateRangeSummary($todayEventListing)
    {
        return
            self::formatTotalEventScheduled($todayEventListing) . ' para ' . DateUtils::TODAY . ', '
            . DateUtils::getCurrentWeekdayName() . ', '
            . DateUtils::getCurrentDay() . ' de '
            . DateUtils::getCurrentMonthName() . ' de '
            . DateUtils::getCurrentYear()
            . self::formatEventScheduledNotOccurred($todayEventListing) . '.';
    }

    /**
     * Check if the event is scheduled to current date.
     *
     * @param string $strTime Information about date and time in the following
     *                        format '2015-12-24 23:59'
     * @return boolean True if event's day, month and year are equals to
     *                 current's day, month and year; false otherwise
     */
    public static function isEventScheduledForToday($strTime)
    {
        $dtEvent = DateUtils::getInfomationAboutDate($strTime);
        return
            $dtEvent->dayVal ===   DateUtils::getCurrentDay() &&
            $dtEvent->monthVal === DateUtils::getCurrentMonthNumber() &&
            $dtEvent->yearVal ===  DateUtils::getCurrentYear();
    }

    /**
     * Returns an event date range for month navigation (indicating previous, current
     * and next both month and year information for each).
     *
     * @param $params Contains the initial request to calendar module
     * @return array An array of prepared date range objects wich containing the
     *               navigation order direction (->order), the number of the
     *               month (->month) and the year (->year)
     */
    public static function getPreparedDateRangeForCalendarNavigation($params)
    {
        $dt = strtotime(date('Y-m-d', mktime(0,0,0, $params->month, 01 , $params->year)));

        return array(
            'previousMonth' => (object) array(
                'order' => self::EVENTS_PREVIOUS_MONTH,
                'month' => intval(date('m', strtotime('previous month', $dt))),
                'year' => intval(date('Y', strtotime('previous month', $dt))),
            ),
            'currentMonth' => (object) array(
                'order' => self::EVENTS_CURRENT_MONTH,
                'month' => intval(date('m', strtotime('this month', $dt))),
                'year' =>  intval(date('Y', strtotime('this month', $dt))),
            ),
            'nextMonth' => (object) array(
                'order' => self::EVENTS_NEXT_MONTH,
                'month' => intval(date('m', strtotime('next month', $dt))),
                'year' =>  intval(date('Y', strtotime('next month', $dt))),
            )
        );
    }

    /**
     * Format the current status of attendee confirmation in a particular event.
     *
     * @param string $confirmationType The type of the current attendee status confirmation
     *                                 at a particular event
     * @return array An array of formatted information about the attendee status confirmation,
     *               wich contains the description of the status (->confirmDescription) and the
     *               corresponding css icon class (->confirmIconCssClass)
     */
    public static function getConfirmationDescription($confirmationType)
    {
        return self::$confirmationTypesAndDescription[$confirmationType];
    }

    /**
     * Prepare a list in which each index is a type of event attendee confirmation and it's
     * element is an empty array to be further filled with attendees that has the same
     * confirmation type.
     *
     * @return array An associative array which the indexes are the existing types of event
     *               attendee confirmation (->ACCEPTED), (->TENTATIVE), (->NEEDS-ACTION),
     *               (->DECLINED) and their elements are empty arrays.
     */
    public static function prepareListOfConfirmationTypesToGroupAttendees()
    {
        $confirmationTypes = array_keys(self::$confirmationTypesAndDescription);
        $arr = array();
        foreach ($confirmationTypes as $confirmType){
            $arr[$confirmType] = array(); // empty array to be filled, to group by confirmation type
        }

        return $arr;
    }

    /**
     * Sort by name a prepared list of event attendees, also grouping the current attendee by the
     * confirmation type.
     *
     * @param array $attendeesList An array list indexed by type of confirmation (['ACCEPTED']),
     *                             (['TENTATIVE']), (['NEEDS-ACTION']), (['DECLINED']) which the
     *                             element is an UNORDERED attendee list
     * @return array The indexed by type of confirmation array which the respective element is an
     *               SORTED BY NAME attendee list
     */
    public static function sortAttendeesByName(&$attendeesList)
    {
        usort($attendeesList[self::EVENTS_CONFIRM_ACCEPTED], function($e1, $e2) {
            return strcmp($e1->name, $e2->name);
        });
        usort($attendeesList[self::EVENTS_CONFIRM_TENTATIVE], function($e1, $e2) {
            return strcmp($e1->name, $e2->name);
        });
        usort($attendeesList[self::EVENTS_CONFIRM_NEEDS_ACTION], function($e1, $e2) {
            return strcmp($e1->name, $e2->name);
        });
        usort($attendeesList[self::EVENTS_CONFIRM_DECLINED], function($e1, $e2) {
            return strcmp($e1->name, $e2->name);
        });

        return $attendeesList;
    }

    /**
     * Check whether an event has not occurred.
     *
     * @param string $strTime Information about date and time in the following
     *                        format '2016-01-30 01:50'
     * @return boolean True if the event has not occurred, false otherwise
     */
    public static function checkEventHasNotOccurred($strTime)
    {
        return DateUtils::compareToCurrentDate($strTime);
    }

    /**
     * Check whether the current user belongs to an event attendees list.
     *
     * @param stdclass $attendeesInformation An object that contains formatted information about
     *                                calendar event, like the email of the current user
     *                                (->currentEmailUser) and the event's attendees list
     *                                (->attendees)
     * @return boolean True if the object corresponding to the current user is found,
     *                 false otherwise
     */
    public static function isUserAllowedToConfirmEvent($attendeesInformation)
    {
        $item = null;
        foreach($attendeesInformation->attendees as $attende) {
            if ($attendeesInformation->currentEmailUser === $attende->email) {
                $item = $attende;
                break;
            }
        }

        return isset($item);
    }
}
