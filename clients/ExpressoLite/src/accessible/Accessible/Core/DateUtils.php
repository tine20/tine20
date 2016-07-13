<?php
/**
 * Expresso Lite Accessible
 * Date formatting routines.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Diogo Santos <diogo.santos@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace Accessible\Core;
use \DateTime;

class DateUtils
{
    /**
     * @var TODAY.
     */
    const TODAY = 'hoje';

    /**
     * Formats any date needed for accessible email.
     *
     * @param int $dateParam             timestamp int.
     * @param boolean $setCompleteFormat specify a format.
     * @return string                    formatted date.
     */
    public static function getFormattedDate($dateParam, $setCompleteFormat=false)
    {
        if (!isset($dateParam) || !is_int($dateParam )) {
            return '';
        }

        $timeReceived = date('H:i', $dateParam);
        $dayReceived = date('d/m/Y', $dateParam);
        $weekDayReceived = DateUtils::getWeekdayName($dateParam);

        if ($setCompleteFormat) {
            return $weekDayReceived . ', ' . $dayReceived . ', ' . $timeReceived;
        }

        if ($dateParam >= strtotime('today 00:00')) {
            return date('\\h\\o\\j\\e, H:i', $dateParam);
        } else if ($dateParam >= strtotime('yesterday 00:00')) {
            return 'ontem, ' . $timeReceived;
        } else if ($dateParam >= strtotime('-6 day 00:00')) {
            return $weekDayReceived . ', ' . $timeReceived;
        } else {
            return date('d/m/Y', $dateParam);
        }
    }

    /**
     * Gets translation of the day of the week according to the
     * timestamp parameter.
     *
     * @param int $ts Timestamp.
     * @return string Translated day of the week.
     */
    public static function getWeekdayName($ts)
    {
        $dt = new DateTime();
        $dt->setTimestamp($ts);
        $arr = array('domingo', 'segunda', 'terça',
                'quarta', 'quinta', 'sexta', 'sábado'
        );

        // Representation of the day of the week, 0 (Sunday) through 6 (Saturday)
        $offset = $dt->format('w');
        return $arr[$offset];
    }

    /**
     * Return a datetime object representing the current date.
     *
     * @return DateTime Object with date and time information
     */
    private static function getCurrentDate()
    {
        return new DateTime('now');
    }

    /**
     * Return the day of the current date. The numeric representation
     * of a day will be without leading zeros starting from 1 to 31.
     *
     * @return string The day of current date
     */
    public static function getCurrentDay()
    {
        $dt = self::getCurrentDate();
        return $dt->format('j');
    }

    /**
     * Return the translated weekday name of the current date.
     *
     * @return string The translated weekday name
     */
    public static function getCurrentWeekdayName()
    {
        $dt = self::getCurrentDate();
        return self::getWeekdayName($dt->getTimestamp());
    }

    /**
     * Return the translated name of the month of the current date.
     *
     * @return string Translated month name of the current date
     */
    public static function getCurrentMonthName()
    {
        return self::getMonthName(self::getCurrentMonthNumber());
    }

    /**
     * Return the number of the month of the current date. The numeric
     * representation of a month will be without leading zeros starting
     * from 1 (January) through 12 (December).
     *
     * @return string Month number of the current date
     */
    public static function getCurrentMonthNumber()
    {
        $dt = self::getCurrentDate();
        return $dt->format('n');
    }

    /**
     * Return the year of the current day.
     *
     * @return string The year of the current day
     */
    public static function getCurrentYear()
    {
        $dt = self::getCurrentDate();
        return $dt->format('Y');
    }

    /**
     * Return the translated month name according to the month number.
     *
     * @param string $monthNumber The month number without leading zeros
     * @return string The translated month name
     */
    public static function getMonthName($monthNumber)
    {
        $arr = array(
            1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
            'julho', 'agosto','setembro', 'outubro', 'novembro', 'dezembro'
        );
        return $arr[intval($monthNumber)];
    }

    /**
     * Return the first day of the current month in the
     * following format '2016-01-13 00:00'
     *
     * @return string Information about date and time
     */
    public static function getFirstDayOfThisMonth()
    {
        $dt = new DateTime('first day of this month');
        $dt->setTime(0, 0, 0);
        return $dt->format('Y-m-d H:i');
    }

    /**
     * Return the first day of a provided month and year values in the following
     * format '2016-03-01 00:00'
     *
     * @return string Information about date and time
     */
    public static function getFirstDayOfMonth($month, $year)
    {
        $dt = new DateTime();
        $ts = strtotime(date('Y-m-d H:i:s', mktime(0, 0, 0, $month, 1 , $year)));
        $dt->setTimestamp($ts);

        return $dt->format('Y-m-d H:i');
    }

    /**
     * Return the last day of the current month in the following format
     * '2016-12-31 23:59'
     *
     * @return string Information about date and time
     */
    public static function getLastDayOfThisMonth()
    {
        $dt = new DateTime('last day of this month');
        $dt->setTime(23, 59, 59);
        return $dt->format('Y-m-t H:i');
    }

    /**
     * Return the last day of a provided month and year values in the following
     * format '2016-02-29 23:59'
     *
     * @return string Information about date and time
     */
    public static function getLastDayOfMonth($month, $year)
    {
        $dt = new DateTime();
        $ts = strtotime(date('Y-m-d H:i:s', mktime(0, 0, 0, $month, 1 , $year)));
        $dt->setTimestamp($ts);
        $dt->setTime(23, 59, 59);

        return $dt->format('Y-m-t H:i');
    }

    /**
     *  Compare one provided date with the current date. Return true only if the
     *  timestamp from the given date is greater than the current day.
     *
     * @param string $strTime Information about date and time in the following
     *                        format '2016-01-30 01:50'
     * @param string $filterTodayEvents
     * @return boolean True if the timestamp from the given date is greater than
     *                 current date's timestamp, false otherwise
     */
    public static function compareToCurrentDate($strTime)
    {
        $dtCurrent = self::getCurrentDate();
        $dtEvent = new DateTime($strTime);

        return $dtEvent->getTimestamp() > $dtCurrent->getTimestamp();
    }

    /**
     * Check whether the today timestamp is within the current event date range.
     *
     * @param StdClass $currDateRange An object with the current month value (->monthVal)
     *                                and year value (->yearVal)
     * @return boolean If today timestamp is within the event date range returns true,
     *                 false otherwise
     */
    public static function isCurentDayWithinCurrentCalendarDateRange($currDateRange)
    {
        $dtCurrent = self::getCurrentDate(); // Current day

        return intval($dtCurrent->format('n')) === intval($currDateRange->monthVal)
            && intval($dtCurrent->format('Y')) === intval($currDateRange->yearVal);
    }

    /**
     * Return an object with formatted date and time information from the date
     * parameter provided, like '2016-02-29 23:59'
     *
     * @param string $strTime Information about date and time in the following
     *                        format '2015-12-24 23:59'
     * @return StdClass Object with formatted date information, including the
     *                  day (->dayVal), month number (->monthVal),translated
     *                  month name (->monthName), year value (->yearVal),
     *                  weekday name (->weekdayName) and the time (->timeVal)
     */
    public static function getInfomationAboutDate($strTime)
    {
        $dt = new DateTime($strTime);

        return (object) array(
            'dayVal' => $dt->format('j'),
            'monthVal' => $dt->format('n'),
            'monthName' => self::getMonthName($dt->format('n')),
            'yearVal' => $dt->format('Y'),
            'weekdayName' => self::getWeekdayName($dt->getTimestamp()),
            'timeVal' => $dt->format('H:i')
        );
    }
}
