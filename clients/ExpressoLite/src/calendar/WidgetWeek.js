/*!
 * Expresso Lite
 * Widget to render a full week.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

define([
    'common-js/jQuery',
    'common-js/App',
    'calendar/DateCalc'
],
function($, App, DateCalc) {
App.loadCss('calendar/WidgetWeek.css');
return function(options) {
    var userOpts = $.extend({
        events: null, // Events cache object
        $elem: null, // jQuery object for the target DIV
        animationTime: 250
    }, options);

    var THIS             = this;
    var $templateView    = null; // jQuery object with our HTML template
    var curDate          = DateCalc.today(); // week currently displayed
    var curCalendarId    = '';   // ID of calendar currently displayed
    var onWeekChangedCB  = $.noop; // user callbacks
    var onEventClickedCB = $.noop;

    THIS.load = function() {
        return $('#Week_template').length ? // load once
            $.Deferred().resolve().promise() :
            App.loadTemplate('WidgetWeek.html');
    };

    THIS.hide = function() {
        if ($templateView !== null) {
            $templateView.hide();
        }
        return THIS;
    };

    THIS.show = function(calendarId, when) {
        curCalendarId = calendarId;
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
        onEventClickedCB = callback; // onEventClicked(eventsOfDay, clickedEvent)
        return THIS;
    };

    function _SetWidgetEvents() {
        $templateView.find('.Week_prev').on('click', function() {
            $(this).blur();
            curDate = DateCalc.prevWeek(DateCalc.sundayOfWeek(curDate));
            _LoadEventsOfCurWeek().done(function() {
                onWeekChangedCB(); // invoke user callback
            });
        });

        $templateView.find('.Week_next').on('click', function() {
            $(this).blur();
            curDate = DateCalc.nextWeek(DateCalc.sundayOfWeek(curDate));
            _LoadEventsOfCurWeek().done(function() {
                onWeekChangedCB(); // invoke user callback
            });
        });

        $templateView.on('click', '.Week_event', function() {
            var objEv = $(this).data('event');
            onEventClickedCB([ objEv ], objEv); // invoke user callback
        });
    }

    function _LoadEventsOfCurWeek() {
        var defer = $.Deferred();
        var $loading = $('#Week_template .Week_loading').clone(); // put throbber
        $templateView.hide().after($loading);
        userOpts.events.loadWeek(curCalendarId, DateCalc.sundayOfWeek(curDate)).done(function() {
            $loading.remove();
            _RenderCells();
            $templateView.show();
            $templateView.find('.Week_grid').scrollTop( // scroll so that 8:00 is first row shown
                $templateView.find('.Week_eachHour:first').outerHeight() * 8);
            $templateView.velocity('fadeIn', {
                duration: userOpts.animationTime,
                complete: function() {
                    defer.resolve();
                }
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
            var events = userOpts.events.inDay(curCalendarId, runDate);
            _CalcOverlay(events);
            var cyHour = $templateView.find('.Week_eachHour:first').outerHeight();

            for (var e = 0; e < events.length; ++e) {
                var $ev = $('#Week_template .Week_event').clone();
                $ev.text(events[e].summary);
                $ev.attr('title', events[e].summary);
                var cx = (100 / 7) / (events[e].overlay.shares + 1); // percent
                var y = events[e].from.getHours() + events[e].from.getMinutes() / 60;
                var cy = DateCalc.hourDiff(events[e].until, events[e].from);
                $ev.css({
                    top: (y * cyHour)+'px',
                    height: (cy * cyHour)+'px',
                    'background-color': events[e].color,
                    'margin-left': events[e].overlay.ident ? (cx * events[e].overlay.ident)+'%' : 0,
                    width: cx+'%'
                });
                delete events[e].overlay; // remove overlay info from event
                $ev.data('event', events[e]);
                $day.append($ev);
            }

            // Advance to tomorrow.
            runDate.setDate(runDate.getDate() + 1);
            $divgridHours.append($day);
        }
    }

    function _CalcOverlay(events) {
        for (var i = 0; i < events.length; ++i) { // add overlay info to each weekday event
            events[i].overlay = {
                shares: 0, // how many events sharing the same vertical pos
                ident: 0 // left-identation of cell
            };
        }

        for (var i = 0; i < events.length - 1; ++i) {
            var ev1 = events[i];
            for (var j = i + 1; j < events.length; ++j) {
                var ev2 = events[j];
                var overlayed = (ev2.from >= ev1.from && ev2.from <= ev1.until) ||
                    (ev2.until >= ev1.from && ev2.until <= ev1.until);
                if (overlayed) {
                    ++ev1.overlay.shares;
                    ++ev2.overlay.shares;
                    ++ev2.overlay.ident;
                }
            }
        }
    }
};
});
