<?php
/**
 * Expresso Lite
 * Handler for searchEvent calls.
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2015-2016 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLite\Backend\Request;
use \DateTime;
use \DateTimeZone;

class SearchEvents extends LiteRequest
{
    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        $start = $this->isParamSet('start') ? $this->param('start') : 0; // pagination
        $limit = $this->isParamSet('limit') ? $this->param('limit') : 50;

        $from = $this->param('from');
        $until = $this->param('until');
        $calendarId = $this->param('calendarId');

        $response = $this->jsonRpc('Calendar.searchEvents', (object) array(
            'filter' => array( (object) array(
                'condition' => 'OR',
                'filters' => array( (object) array(
                    'condition' => 'AND',
                    'filters' => $this->buildSearchFilters($calendarId, 'LIST_DECLINED')
                ))
            ), (object) array(
                'field' => 'period',
                'operator' => 'within',
                'value' => (object) array(
                    'from' => $from,
                    'until' => $until
                )
            )),
            'paging' => (object) array()
        ));

        return (object) array(
            'totalCount' => $response->result->totalcount,
            'events' => $this->formatEvents($response->result->results)
        );
    }

    /**
     * Builds the filter to be used in searchEvents Tine request.
     *
     * @param string  $calendarId        ID of calendar to be searched.
     * @param string $listDeclinedEvents 'LIST_DECLINED' or 'DONT_LIST_DECLINED' flag.
     *
     * @return array[stdClass] Formatted filter to be used in searchEvents Tine request.
     */
    private function buildSearchFilters($calendarId, $listDeclinedEvents)
    {
        $filters = array();

        if ($listDeclinedEvents === 'DONT_LIST_DECLINED') {
            $filters[] = (object) array(
                'field' => 'attender_status',
                'operator' => 'notin',
                'value' => array('DECLINED')
            );
        }


        $filters[] = (object) array(
            'field' => 'container_id',
            'operator' => 'in',
            'value' => array( (object) array(
                'id' => $calendarId
            ))
        );

        return $filters;
    }

    /**
     * Formats an array of Tine raw events stripping away the garbage.
     *
     * @param array $events Raw Tine events.
     *
     * @return array Array of formatted events.
     */
    private function formatEvents(array $events)
    {
        $ret = array();
        foreach ($events as $e) {
            $ret[] = (object) array(
                'id' => $e->id,
                'from' => $this->parseTimeZone($e->dtstart, $e->originator_tz),
                'until' => $this->parseTimeZone($e->dtend, $e->originator_tz),
                'wholeDay' => $e->is_all_day_event === '1',
                'summary' => $e->summary,
                'description' => $e->description,
                'location' => $e->location,
                'color' => $this->getEventColor($e),
                'confirmation' => $this->getUserConfirmation($e),
                'organizer' => $this->getUserInformation($e->organizer),
                'attendees' => $this->getAttendees($e)
            );
        }
        return $ret;
    }

    /**
     * Given a datetime string, formats it according to the given timezone.
     *
     * @param string $strTime String like '2015-08-05 13:50:00'.
     * @param string $strZone PHP DateTimeZone string, like 'America_SaoPaulo'.
     *
     * @return string String with datetime, like '2015-08-05 13:50:00', UTC±0 timezone.
     */
    private function parseTimeZone($strTime, $strZone)
    {
        $timeZone = $this->isParamSet('timeZone') ?
            $this->param('timeZone') :
            'UTC';
        $d = new DateTime($strTime, new DateTimeZone($strZone));
        $d->setTimezone(new DateTimeZone($timeZone));
        return $d->format('Y-m-d H:i:s');
    }

    /**
     * Get the hexadecimal color code for the given event.
     *
     * @param stdClass $event The event object to retrieve the color.
     *
     * @return string Hexadecimal color code.
     */
    private function getEventColor($event)
    {
        // For some odd reason, "container_id" may be a full object with all
        // information, or just a number without anything useful.
        return is_object($event->container_id) ?
            $event->container_id->color :
            $this->tineSession->getAttribute('Calendar.defaultEventColor');
    }

    /**
     * Tells confirmation status of current user upon the event.
     *
     * @param stdClass $event The event object to retrieve the confirmation status.
     *
     * @return string Current user confirmation status upon the event.
     */
    private function getUserConfirmation($event)
    {
        foreach ($event->attendee as $atd) {
            if (is_object($atd->user_id)) {
                if ($this->tineSession->getAttribute('Expressomail.email') === $atd->user_id->email) {
                    return $atd->status;
                }
            }
        }
        return ''; // if user removed himself from the event, should never happen
    }

    /**
     * Returns all event attendees.
     *
     * @param stdClass $event The event object to retrieve the attendees.
     *
     * @return array[stdClass] All event attendees.
     */
    private function getAttendees($event)
    {
        $ret = array();
        foreach ($event->attendee as $atd) {
            if (is_object($atd->user_id)) {
                $objAtd = $this->getUserInformation($atd->user_id);
                $objAtd->confirmation = $atd->status;
                $ret[] = $objAtd;
            }
        }
        return $ret;
    }

    /**
     * Filters information about an user.
     *
     * @param stdClass $user Raw Tine user object from event.
     *
     * @return stdClass Filtered event user information.
     */
    private function getUserInformation($user)
    {
        return (object) array(
            'id' => $user->id,
            'name' => $user->n_fn,
            'email' => $user->email,
            'region' => $user->adr_one_region,
            'orgUnit' => $user->org_unit,
            'phone' => $user->tel_work
        );
    }
}
