<?php
/**
 * Expresso Lite Accessible
 * Manipulates data for the calendar application main screen,
 * loading information about calendar events.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Diogo Santos <diogo.santos@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */

namespace Accessible\Calendar;

use Accessible\Handler;
use ExpressoLite\Backend\LiteRequestProcessor;
use Accessible\Core\DateUtils;
use Accessible\Core\EventUtils;
use ExpressoLite\Backend\TineSessionRepository;

class Main extends Handler
{
    /**
     * @see Accessible\Handler::execute
     */
    public function execute($params)
    {
        $currCalendar  = $this->getCurrentCalendar($params);
        $currDateRange = $this->formatCurrentCalendarDateRange($params);
        $currNavigationDateRange = EventUtils::getPreparedDateRangeForCalendarNavigation( (object) array(
            'month' => $currDateRange->monthVal,
            'year'  => $currDateRange->yearVal
        ));

        // The complete list of events according to the current calendar date range
        $allEvents = $this->getEventListing($currCalendar->id, $currDateRange);

        // Entire event listing filtered by current day
        $todayEvents = $this->formatTodaysEventListing($allEvents->listing);
        $showTodayEvents = DateUtils::isCurentDayWithinCurrentCalendarDateRange($currDateRange);

        $this->showTemplate('MainTemplate', (object) array(
            'calendarMainTitle' => $this->prepareCalendarMainTitle($currDateRange, $currCalendar),
            'isTodayExhibition' => $showTodayEvents,
            'hasTodayEvents' => $todayEvents->hasEvents,
            'todayEventListing' => $this->formatEventsForVisualization(
                $todayEvents->listing,
                $currCalendar->id,
                $currDateRange
            ),
            'dateRangeTodayEventsSummary' => EventUtils::setTodayEventsDateRangeSummary($todayEvents->listing),
            'hasEvents' => $allEvents->hasEvents,
            'eventListing' => $this->formatEventsForVisualization(
                $allEvents->listing,
                $currCalendar->id,
                $currDateRange
             ),
            'dateRangeEventsSummary' => EventUtils::setEventsDateRangeSummary($allEvents->listing, $currDateRange),
            'lnkEmail' => $this->makeUrl('Mail.Main'),
            'lnkLogoff' => $this->makeUrl('Login.Logoff'),
            'lnkChangeCalendar' => $this->makeUrl('Calendar.OpenCalendar'),
            'lnkBack' => $this->makeUrl('Calendar.Main', array(
                'calendarId' => $currCalendar->id,
            )),
            'calendarNavigation' => $this->formatCalendarNavigationLinks(
                $currCalendar->id,
                $currNavigationDateRange
            ),
            'lnkChangeCalendar' => $this->makeUrl('Calendar.OpenCalendar', array(
                'calendarId' => $currCalendar->id,
                'month' => $currDateRange->monthVal,
                'year' => $currDateRange->yearVal
            ))
        ));
    }

    /**
     * Return the current calendar as is passed or not a calendar id, if any id is
     * provided or is invalid, so the personal calendar of the user will be used as
     * default.
     *
     * @param StdClass $params Contains the initial request to calendar module
     * @return StdClass Calendar object
     */
    private function getCurrentCalendar($params)
    {
        $lrp = new LiteRequestProcessor();
        $calendars = $lrp->executeRequest('GetCalendars', (object) array());

        if (!isset($params->calendarId)) {
            // If no calendar id is set, use the personal calendar as the default
            return $calendars[0];
        } else {
            foreach ($calendars as $cal) {
                if (intval($cal->id) === intval($params->calendarId)) {
                    return $cal;
                }
            }
            return null; // return null if no calendar with the specified id is found
        }
    }

    /**
     * Checks if any time parameter was supplied , if not then the year and month
     * of the current day will be used as date range reference.
     *
     * @param StdClass $params Initial request to calendar module
     * @return StdClass An object with a month value (->monthVal) and
     *                  year value (->yearVal)
     */
    private function formatCurrentCalendarDateRange($params)
    {
        // Validating provided parameters and formats event date range
        return (object) array(
            'monthVal' => isset($params->month) ?
                $params->month :
                DateUtils::getCurrentMonthNumber(),
            'yearVal' => isset($params->year) ?
                $params->year :
                DateUtils::getCurrentYear()
        );
    }

    /**
     * Given an event listing in an arbitrary date range, this method returns
     * a new listing containing only the events of the current day
     *
     * @param StdClass $eventListing Listing of events in a given date range
     * @return StdClass Today event listing content (->listing) and a
     *                  boolean (->hasEvents) indicating whether or not
     *                  there are events in current day
     */
    private function formatTodaysEventListing($eventListing)
    {
        $todayEventList = array();
        if (isset($eventListing) && count((array) $eventListing) > 0) {
            foreach ($eventListing as $event) {
                if (EventUtils::isEventScheduledForToday($event->from)) {
                    $todayEventList[] = $event;
                }
            }
        }

        return (object) array(
            'hasEvents' => count($todayEventList) > 0,
            'listing' => (object) $todayEventList
        );
    }

    /**
     * Prepares the calendar main title which is composed by the current
     * calendar name concatenated with the month and year of the current
     * used calendar.
     *
     * @param StdClass $currDateRange Formatted date range with month and
     *                                year values
     * @param StdClass $currentCalendar The current calendar in use
     * @return string Calendar main title
     */
    private function prepareCalendarMainTitle($currDateRange, $currentCalendar)
    {
        return
            $currentCalendar->name . ' em ' .
            DateUtils::getMonthName($currDateRange->monthVal) . ' de ' .
            $currDateRange->yearVal . ' ';
    }

    /**
     * Returns an ordered listing of events according to the provided events
     * date range and to the currently selected user calendar.
     *
     * @param string $calendarId Current calendar to be used
     * @param StdClass $currDateRange Formatted date range with month and year values
     *                                to use as the event date range
     * @return StdClass Event listing content (->listing) and a boolean (->hasEvents)
     *                  indicating whether or not there are calendar events
     */
    private function getEventListing($calendarId, $currDateRange)
    {
        // Setting properly the correct timezone based on login user timezone
        $timeZone = TineSessionRepository::getTineSession()->getAttribute('Tinebase.timeZone');

        $lrp = new LiteRequestProcessor();
        $preparedEventDateRange = EventUtils::prepareEventsDateRange($currDateRange);
        $message = $lrp->executeRequest('SearchEvents', (object) array(
                'from'  => $preparedEventDateRange->from,
                'until' => $preparedEventDateRange->until,
                'timeZone' => $timeZone,
                'calendarId' => $calendarId
        ));

        // Sorts the event list comparing each event start time (->from)
        usort($message->events, function($e1, $e2) {
            return strcmp($e1->from, $e2->from);
        });

        return (object) array(
            'hasEvents' => count($message->events) > 0,
            'listing' => (object) $message->events
        );
    }

    /**
     * Formats information about events to be displayed on calendar events
     * main screen.
     *
     * @param array $eventListing Event listing in a given date range to be
     *                            formatted for visualization
     * @param int $currCalendarId The id of the current calendar in use
     * @param stdClass $currDateRange Formatted date range with month and
     *                                year values
     * @return array Formatted list of events
     */
    private function formatEventsForVisualization($eventListing, $currCalendarId, $currDateRange)
    {
        if (isset($eventListing) && count($eventListing) > 0) {
            foreach ($eventListing as &$event) {
                $fromData  = DateUtils::getInfomationAboutDate($event->from);
                $untilData = DateUtils::getInfomationAboutDate($event->until);
                $event->formattedDay =     $fromData->dayVal;
                $event->formattedFrom =    $fromData->timeVal;
                $event->formattedUntil =   $untilData->timeVal;
                $event->formattedMonth =   $fromData->monthName;
                $event->formattedWeekDay = $fromData->weekdayName;
                $event->notYetOccurred =   DateUtils::compareToCurrentDate($event->from);
                $event->lnkOpenEvent = $this->makeUrl('Calendar.OpenEvent', array(
                    'from' => $event->from,
                    'until' => $event->until,
                    'idEvent' => $event->id,
                    'calendarId' => $currCalendarId,
                    'monthVal' => $currDateRange->monthVal,
                    'yearVal' => $currDateRange->yearVal
                ));
            }
            return $eventListing;
        } else {
            return array();
        }
    }

    /**
     * Format links for calendar navigation.
     *
     * @param int $calendarId The id of the current calendar in use
     * @param array $eventDateRange An array of prepared event date range objects
     * @return array Formatted link objects to calendar navigation, each element
     *               contains a link to navigate through calendar (->lnk), the text
     *               of the link (->lnkText) and a title for the link (->lnkTitle)
     */
    private function formatCalendarNavigationLinks($calendarId, $eventDateRange)
    {
        $calendarNavigationLinks = array();

        // Formatting links
        foreach ($eventDateRange as $edr) {
            $monthName = DateUtils::getMonthName($edr->month);

            $calendarNavigationLinks[] = (object) array(
                'lnk' => $this->makeUrl('Calendar.Main', array(
                    'month' => $edr->month,
                    'year' => $edr->year,
                    'calendarId' => $calendarId
                )),
                'lnkText' => $edr->order . ' - ' . $monthName . ' de ' . $edr->year,
                'lnkTitle' => EventUtils::EVENTS_CALENDAR_NAME . ' de ' . $monthName . ' de ' . $edr->year
            );
        }
        return $calendarNavigationLinks;
    }
}
