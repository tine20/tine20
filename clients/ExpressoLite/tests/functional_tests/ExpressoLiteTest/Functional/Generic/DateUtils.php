<?php
/**
 * Expresso Lite
 * This class has the purpose to is to retrieve the day's date and present in the
 * requested format.
 *
 * @package ExpressoLiteTest\Functional\Mail
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Fatima Tonon <fatima.tonon@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLiteTest\Functional\Generic;

class DateUtils
{
    /*
     * retrieve timestamp
     * Use setlocale to define current local time/date for the function strftime
     */
    private static function getTime($format)
    {
        setlocale (LC_TIME, 'pt_BR.UTF-8');
        return strtolower(strftime ("$format", time()));
    }

    /*
     * Retrieve time using date function
     * Use setlocale to define current local time/date for the function date
     */
    private static function getDateTime($format)
    {
        setlocale (LC_TIME, 'pt_BR.UTF-8');
        return date ("$format", time());
    }

    /*
     * Retrieve relative time based in the parameter
     * Use setlocale to define current local time/date for the function strftime
     */
    private static function getRelativeTime($format, $period)
    {
        setlocale (LC_TIME, 'pt_BR.UTF-8');
        return strtolower(strftime ("$format", strtotime("$period", time())));
    }

    /*
     * retrieve month and year of the day
     *
     * @Returns "month, year"
     */
    public static function getMonthYear()
    {
        return (DateUtils::getTime('%B, %Y'));
    }

    /*
     * retrieve previous month and year of the day
     *
     * @Returns previous "month, year"
     */
    public static function getPreviousMonthYear()
    {
        return (DateUtils::getRelativeTime('%B, %Y', '-1 month'));
    }

    /*
     * retrieve next month and current year of the day
     *
     * @Returns next "month, current year"
     */
    public static function getNextMonthYear()
    {
        return (DateUtils::getRelativeTime('%B, %Y', '+1 month'));
    }

    /*
     * retrieve the name of month in short form
     *
     * @Returns "month"
     */
    public static function getShortMonth()
    {
        return (DateUtils::getTime('%b'));
    }

    /*
     * retrieve the full name of month in short form
     *
     * @Returns "month"
     */
    public static function getFullMonth()
    {
        return (DateUtils::getTime('%B'));
    }

    /*
     * retrieve day
     *
     * @Returns "day"
     */
    public static function getday()
    {
        return (DateUtils::getTime('%d'));
    }

    /*
     * retrieve month
     *
     * @Returns "month"
     */
    public static function getMonth()
    {
        return (DateUtils::getTime('%m'));
    }

    /* retrieve Year
     *
     * @Returns "year"
     */
    public static function getYear()
    {
        return (DateUtils::getTime('%Y'));
    }

    /* retrieve day of week
     *
     * @Returns "day of week"
     */
    public static function getDayOfWeek()
    {
        return (DateUtils::getTime('%A'));
    }

    /* retrieve short day of week
     *
     * @Returns "short day of week"
     */
    public static function getShortDayOfWeek()
    {
        return (DateUtils::getTime('%a'));
    }

    /* retrieve day of week short and day of month
     *
     * @Returns "day of week" short and day of month
     */
    public static function getNumericDayMonth()
    {
        return DateUtils::getDateTime("j/n");
    }

    /* retrieve numeric representation of day of week
     * 0 (for Sunday) through 6 (for Saturday)
     *
     * @Returns "year"
     */
    public static function getNumericDayOfWeek()
    {
        return (DateUtils::getTime('%w'));
    }
}