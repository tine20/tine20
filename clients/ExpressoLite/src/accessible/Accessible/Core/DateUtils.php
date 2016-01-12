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

class DateUtils
{
    /**
     * Formats any date needed for accessible.
     *
     * @param int $dateParam             timestamp int.
     * @param boolean $setCompleteFormat specify a format.
     * @return string                    formatted date.
     */
    public static function getFormattedDate($dateParam, $setCompleteFormat=false)
    {
        if(!isset($dateParam) || !is_int($dateParam )){
            return '';
        }

        $timeReceived = date('H:i', $dateParam);
        $dayReceived = date('d/m/Y', $dateParam);
        $weekDayReceived = DateUtils::getWeekDay($dateParam);

        if ($setCompleteFormat) {
            return $weekDayReceived . ', ' . $dayReceived . ', ' . $timeReceived;
        }

        if ($dateParam >= strtotime('today 00:00')) {
            return date('\\h\\o\\j\\e, H:i', $dateParam);
        } elseif ($dateParam >= strtotime('yesterday 00:00')) {
            return 'ontem, ' . $timeReceived;
        } elseif ($dateParam >= strtotime('-6 day 00:00')) {
            return $weekDayReceived . ', ' . $timeReceived;
        } else {
            return date('d/m/Y', $dateParam);
        }
    }

    /**
     * Gets translation of the day of the week.
     *
     * @param int $dateParam Date parameter.
     * @return string        Translated day of the week.
     */
    private static function getWeekDay($dateParam)
    {
        $weekDay = date('w', $dateParam);
        switch($weekDay) {
            case'0': $weekDay = 'domingo'; break;
            case'1': $weekDay = 'segunda'; break;
            case'2': $weekDay = 'terça';   break;
            case'3': $weekDay = 'quarta';  break;
            case'4': $weekDay = 'quinta';  break;
            case'5': $weekDay = 'sexta';   break;
            case'6': $weekDay = 'sábado';  break;
        }
        return $weekDay;
    }
}
