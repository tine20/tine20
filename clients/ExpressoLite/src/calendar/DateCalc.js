/*!
 * Expresso Lite
 * Specific date-related operations.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

define([
],
function() {
var DateCalc = {
    monthName: function(number) {
        var months = [ 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
            'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro' ];
        return months[number];
    },

    weekDayName: function(number) {
        var weekDays = [ 'domingo', 'segunda', 'terça', 'quarta',
            'quinta', 'sexta', 'sábado' ];
        return weekDays[number];
    },

    create: function(year, month, day) {
        // All dates use 12:00:00 to avoid Daylight Saving Time tweaks.
        // Month is zero-based.
        return new Date(year, month, day, 12);
    },

    clone: function(when) {
        return DateCalc.create(when.getFullYear(), when.getMonth(), when.getDate());
    },

    today: function() {
        var ty = new Date();
        return DateCalc.create(ty.getFullYear(), ty.getMonth(), ty.getDate());
    },

    isSameDay: function(when1, when2) {
        return when1.getFullYear() === when2.getFullYear() &&
            when1.getMonth() === when2.getMonth() &&
            when1.getDate() === when2.getDate(); // don't compare hours/minutes
    },

    isSameMonth: function(when1, when2) {
        return when1.getFullYear() === when2.getFullYear() &&
            when1.getMonth() === when2.getMonth();
    },

    isBeforeCurrentTime: function(when) {
        return when.getTime() < Date.now();
    },

    hourDiff: function(when1, when2) {
        // http://stackoverflow.com/questions/7709803/javascript-get-minutes-between-two-dates
        var diffMs = Math.abs(when1 - when2);
        return Math.round((diffMs % 86400000) / 3600000);
    },

    firstOfMonth: function(when) {
        return DateCalc.create(when.getFullYear(), when.getMonth(), 1);
    },

    lastOfMonth: function(when) {
        return DateCalc.create(when.getFullYear(), when.getMonth() + 1, 0);
    },

    sundayOfWeek: function(when) {
        return DateCalc.create(when.getFullYear(),
            when.getMonth(),
            when.getDate() - when.getDay()); // 1st day of current week
    },

    saturdayOfWeek: function(when) {
        return DateCalc.create(when.getFullYear(),
            when.getMonth(),
            when.getDate() - when.getDay() + 6); // last day of current week
    },

    daysInMonth: function(when) {
        return DateCalc.create(when.getFullYear(), when.getMonth() + 1, 0).getDate();
    },

    weeksInMonth: function(when) {
        // http://stackoverflow.com/questions/2483719/get-weeks-in-month-through-javascript
        var used = DateCalc.firstOfMonth(when).getDay() +
            DateCalc.lastOfMonth(when).getDate();
        return Math.ceil(used / 7);
    },

    prevMonth: function(when) {
        return DateCalc.create(when.getFullYear(), when.getMonth() - 1, 1); // 1st day of previous month
    },

    nextMonth: function(when) {
        return DateCalc.create(when.getFullYear(), when.getMonth() + 1, 1); // 1st day of next month
    },

    prevWeek: function(when) {
        var d = DateCalc.sundayOfWeek(when);
        return DateCalc.create(d.getFullYear(), d.getMonth(), d.getDate() - 7); // sunday of previous week
    },

    nextWeek: function(when) {
        var d = DateCalc.sundayOfWeek(when);
        return DateCalc.create(d.getFullYear(), d.getMonth(), d.getDate() + 7); // sunday of next week
    },

    monthEndsInSaturday: function(when) {
        // If a month ends in Saturday, it means there are no days of
        // next month being displayed on current month rendering.
        return DateCalc.lastOfMonth(when).getDay() === 6;
    },

    makeQueryStr: function(when) {
        return when.getFullYear() + '-' +
            DateCalc.pad2(when.getMonth() + 1) + '-' +
            DateCalc.pad2(when.getDate()) + ' 00:00:00'; // format to be used in requests
    },

    makeHourMinuteStr: function(when) {
        return DateCalc.pad2(when.getHours()) + ':' +
            DateCalc.pad2(when.getMinutes());
    },

    makeWeekDayMonthYearStr: function(when) {
        return DateCalc.weekDayName(when.getDay())+', ' +
            when.getDate()+' de ' +
            DateCalc.monthName(when.getMonth())+' de '+
            when.getFullYear();
    },

    strToDate: function(str) {
        // Expected format: '2015-08-04 14:30:00', in UTC±0.
        return new Date(str.replace(' ', 'T')+'+0000'); // this 'T' is a JavaScript requirement
    },

    pad2: function(n) {
        return (n >= 10) ? n : ('0'+n);
    }
};

return DateCalc;
});
