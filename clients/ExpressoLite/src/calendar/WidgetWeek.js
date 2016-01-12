/*!
 * Expresso Lite
 * Widget to render a full week.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

define(['jquery',
    'common-js/App',
    'calendar/DateCalc'
],
function($, App, DateCalc) {
App.LoadCss('calendar/WidgetWeek.css');
return function(options) {
    var userOpts = $.extend({
        events: null, // Events cache object
        $elem: null, // jQuery object for the target DIV
        animationTime: 250
    }, options);

    var THIS             = this;
    var $templateView    = null; // jQuery object with our HTML template
    var curDate          = DateCalc.today();
    var onWeekChangeCB   = null; // user callbacks
    var onEventClickedCB = null;

    THIS.load = function() {
        return $('#Week_template').length ? // load once
            $.Deferred().resolve().promise() :
            App.LoadTemplate('WidgetWeek.html');
    };

    THIS.hide = function() {
        if ($templateView !== null) {
            $templateView.hide();
        }
        return THIS;
    };

    THIS.show = function(when) {
        curDate = when;
        if ($templateView === null) {
            $templateView = $('#Week_template .Week_container').clone();
            $templateView.appendTo(userOpts.$elem).hide();
            _RenderHourLabels();
            _SetWidgetEvents();
        }
        return _LoadEventsOfCurWeek();
    };

    THIS.getCurDate = function() {
        return curDate;
    };

    THIS.onWeekChanged = function(callback) {
        onWeekChangedCB = callback; // onWeekChanged(curWeek)
        return THIS;
    };

    THIS.onEventClicked = function(callback) {
        onEventClickedCB = callback; // onEventClicked(events)
        return THIS;
    };

    function _SetWidgetEvents() {
        $templateView.find('.Week_prev').on('click', function() {
            $(this).blur();
            curDate = DateCalc.prevWeek(DateCalc.sundayOfWeek(curDate));
            _LoadEventsOfCurWeek().done(function() {
                if (onWeekChangedCB !== null) {
                    onWeekChangedCB(); // invoke user callback
                }
            });
        });

        $templateView.find('.Week_next').on('click', function() {
            $(this).blur();
            curDate = DateCalc.nextWeek(DateCalc.sundayOfWeek(curDate));
            _LoadEventsOfCurWeek().done(function() {
                if (onWeekChangedCB !== null) {
                    onWeekChangedCB(); // invoke user callback
                }
            });
        });

        $templateView.on('click', '.Week_event', function() {
            if (onEventClickedCB !== null) {
                onEventClickedCB([ $(this).data('event') ]); // invoke user callback
            }
        });
    }

    function _LoadEventsOfCurWeek() {
        var defer = $.Deferred();
        var $loading = $('#Week_template .Week_loading').clone(); // put throbber
        $templateView.hide().after($loading);
        userOpts.events.loadWeek(DateCalc.sundayOfWeek(curDate)).done(function() {
            $loading.remove();
            _RenderCells();
            $templateView.show();
            $templateView.find('.Week_grid').scrollTop( // scroll so that 8:00 is first row shown
                $templateView.find('.Week_eachHour:first').outerHeight() * 8);
            $templateView.hide().fadeIn(userOpts.animationTime, function() {
                defer.resolve();
            });
        }).fail(function() {
            defer.reject();
        });
        return defer.promise();
    }

    function _RenderHourLabels() {
        var $hoursPhone = $templateView.find('.Week_hourLabelsPhone');
        var $hoursDesktop = $templateView.find('.Week_hourLabelsDesktop');
        $hoursPhone.empty();
        $hoursDesktop.empty();

        for (var h = 0; h < 24; ++h) {
            var $hourP = $('#Week_template .Week_eachHour').clone();
            $hourP.text(h);
            $hourP.appendTo($hoursPhone);

            var $hourD = $('#Week_template .Week_eachHour').clone();
            $hourD.text(h + ':00');
            $hourD.appendTo($hoursDesktop);
        }
    }

    function _RenderCells() {
        var $divgridHours = $templateView.find('.Week_gridHours');
        var runDate = DateCalc.sundayOfWeek(curDate);
        $divgridHours.empty();
        $templateView.find('.Week_labelWeek').removeClass('Week_labelWeekToday');

        for (var d = 0; d < 7; ++d) {
            // Labels for each week day.
            $templateView.find('.Week_labelWeekDay'+d).text(runDate.getDate() + '/' +
                (runDate.getMonth() + 1));

            // Column of a single day.
            var $day = $('#Week_template .Week_colDay').clone();
            if (DateCalc.isSameDay(runDate, DateCalc.today())) {
                $templateView.find('.Week_labelWeekDay'+d).parent().addClass('Week_labelWeekToday');
                $day.addClass('Week_colDayToday');
            }

            // Cells of each hour.
            var isWeekend = (d === 0) || (d === 6);
            for (var h = 0; h < 24; ++h) {
                var $hour = $('#Week_template .Week_cellHour').clone();
                var isNonWorkingHour = (h < 7) || (h >= 19);
                if (isWeekend || isNonWorkingHour) {
                    $hour.addClass('Week_cellHourNonWorking');
                }
                $hour.appendTo($day);
            }

            // Events of the week day.
            var events = userOpts.events.inDay(runDate);
            var cyHour = $templateView.find('.Week_eachHour:first').outerHeight();
            for (var e = 0; e < events.length; ++e) {
                var $ev = $('#Week_template .Week_event').clone();
                $ev.text(events[e].summary);
                $ev.attr('title', events[e].summary);
                var y = events[e].from.getHours() + events[e].from.getMinutes() / 60;
                var cy = DateCalc.hourDiff(events[e].until, events[e].from);
                $ev.css({
                    top: (y * cyHour)+'px',
                    height: (cy * cyHour)+'px',
                    'background-color': events[e].color
                });
                $ev.data('event', events[e]);
                $day.append($ev);
            }

            /// Advance to tomorrow.
            runDate.setDate(runDate.getDate() + 1);
            $divgridHours.append($day);
        }
    }
};
});
