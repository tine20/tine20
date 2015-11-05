/*!
 * Expresso Lite
 * Date formatting routines.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2014 Serpro (http://www.serpro.gov.br)
 */

define([], function() {
    function _Pad2(num) {
        return (num < 10) ? '0'+num : num;
    }

    function _IsToday(yourDate, howManyDaysAgo) {
        var now = new Date();
        now.setDate(now.getDate() + howManyDaysAgo);
        return now.getFullYear() === yourDate.getFullYear() &&
            now.getMonth() === yourDate.getMonth() &&
            now.getDate() === yourDate.getDate();
    }

    function _GetWeekDay(dateObj) {
        var date2 = new Date(dateObj.getTime()); // clone date object
        date2.setMinutes(date2.getMinutes() - date2.getTimezoneOffset()); // apply timezone offset
        var week = [ 'domingo', 'segunda', 'terça', 'quarta', 'quinta', 'sexta', 'sábado' ];
        return week[date2.getUTCDay()];
    }

return {
    Humanize: function(dateObj) {
        if (_IsToday(dateObj, 0)) {
            return 'hoje, ' +
                _Pad2(dateObj.getHours()) + ':' +
                _Pad2(dateObj.getMinutes());
        } else if (_IsToday(dateObj, -1)) {
            return 'ontem, ' +
                _Pad2(dateObj.getHours()) + ':' +
                _Pad2(dateObj.getMinutes());
        } else if (_IsToday(dateObj, -2) || _IsToday(dateObj, -3) ||
            _IsToday(dateObj, -4) || _IsToday(dateObj, -5) ||
            _IsToday(dateObj, -6) )
        {
            return _GetWeekDay(dateObj) + ', ' +
                _Pad2(dateObj.getHours()) + ':' +
                _Pad2(dateObj.getMinutes());
        } else {
            return _Pad2(dateObj.getDate()) + '/' +
                _Pad2(dateObj.getMonth() + 1) + '/' +
                _Pad2(dateObj.getFullYear());
        }
    },

    Long: function(dateObj) {
        return _GetWeekDay(dateObj) + ', ' +
            _Pad2(dateObj.getDate()) + '/' +
            _Pad2(dateObj.getMonth() + 1) + '/' +
            dateObj.getFullYear() + ', ' +
            _Pad2(dateObj.getHours()) + ':' +
            _Pad2(dateObj.getMinutes());
    },

    Medium: function(dateObj) {
        return _Pad2(dateObj.getDate()) + '/' +
            _Pad2(dateObj.getMonth() + 1) + '/' +
            dateObj.getFullYear() + ' ' +
            _Pad2(dateObj.getHours()) + ':' +
            _Pad2(dateObj.getMinutes());
    }
};
});
