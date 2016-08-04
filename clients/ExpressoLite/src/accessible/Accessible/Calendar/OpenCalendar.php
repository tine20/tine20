<?php
/**
 * Expresso Lite Accessible
 * Show all calendars (personal or shared) for the user to choose one
 * and view it's events.
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

class OpenCalendar extends Handler
{
    /**
     * @see Accessible\Handler::execute
     */
    public function execute($params)
    {
        $lrp = new LiteRequestProcessor();

        // Gets personals and shared calendars
        $calendars = $lrp->executeRequest('GetCalendars', (object) array());

        $this->showTemplate('OpenCalendarTemplate', (object) array(
            'calendars' => self::formatCalendarTree($calendars, $params),
            'lnkBack' => $this->makeUrl('Calendar.Main', array(
                'calendarId' => $params->calendarId,
                'month' => $params->month,
                'years' => $params->year
             ))
        ));
    }

    /**
     * Format information about all calendars (personal and shared) that the
     * user has some kind of access.
     *
     * @param array $arrCalendars An array of calendar objects
     * @return array An array of objects in which each contains formatted
     *               information about the calendars, like the id of the
     *               calendar (->id), the calendar name (->name) and a
     *               link to load other calendar (->lnkOpenCalendar)
     */
    private function formatCalendarTree($arrCalendars)
    {
        $retCalendars = array();
        foreach ($arrCalendars as $cal) {
            $retCalendars[] = (object) array(
                'id' => $cal->id,
                'name' => $cal->name,
                'lnkOpenCalendar' => $this->makeUrl('Calendar.Main', array(
                    'calendarId' => $cal->id
                ))
            );
        }

        return $retCalendars;
    }
}
