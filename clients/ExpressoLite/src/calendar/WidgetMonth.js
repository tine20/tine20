/*!
 * Expresso Lite
 * Widget to render a full month.
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
App.loadCss('calendar/WidgetMonth.css');
return function(options) {
    var userOpts = $.extend({
        events: null, // Events cache object
        $elem: null, // jQuery object for the target DIV
        animationTime: 250
    }, options);

    var THIS             = this;
    var $templateView    = null; // jQuery object with our HTML template
    var curDate          = DateCalc.today(); // month currently displayed
    var curCalendarId    = '';   // ID of calendar currently displayed
    var onMonthChangedCB = $.noop; // user callbacks
    var onEventClickedCB = $.noop;

    THIS.load = function() {
        return $('#Month_template').length ? // load once
            $.Deferred().resolve().promise() :
            App.loadTemplate('WidgetMonth.html');
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
            $templateView = $('#Month_template .Month_container').clone();
            $templateView.appendTo(userOpts.$elem).hide();
            _SetWidgetEvents();
        }
        return _LoadEventsOfCurMonth();
    };

    THIS.getCurDate = function() {
        return curDate;
    };

    THIS.clearDaySelected = function() {
        $templateView.find('.Month_daySelected').removeClass('Month_daySelected');
        return THIS;
    };

    THIS.onMonthChanged = function(callback) {
        onMonthChangedCB = callback; // onMonthChanged()
        return THIS;
    };

    THIS.onEventClicked = function(callback) {
        onEventClickedCB = callback; // onEventClicked(eventsOfDay, clickedEvent)
        return THIS;
    };

    function _SetWidgetEvents() {
        $templateView.find('.Month_prev').on('click', function() {
            $(this).blur();
            var oldDate = curDate;
            curDate = DateCalc.prevMonth(DateCalc.firstOfMonth(curDate));
            _LoadEventsOfCurMonth().done(function() {
                onMonthChangedCB(); // invoke user callback
            }).fail(function() {
                curDate = oldDate;
            });
        });

        $templateView.find('.Month_next').on('click', function() {
            $(this).blur();
            var oldDate = curDate;
            curDate = DateCalc.nextMonth(DateCalc.firstOfMonth(curDate));
            _LoadEventsOfCurMonth().done(function() {
                onMonthChangedCB(); // invoke user callback
            }).fail(function() {
                curDate = oldDate;
            });
        });

        $templateView.on('click', '.Month_event', function() {
            if (!App.isPhone()) { // single event click works only on desktop
                THIS.clearDaySelected();
                var $ev = $(this);
                var $day = $ev.parents('.Month_day');
                $day.addClass('Month_daySelected');
                onEventClickedCB($day.data('events'), $ev.data('event')); // invoke user callback
            }
        });

        $templateView.on('click', '.Month_day', function() {
            if (App.isPhone()) { // whole day click works only on phones
                THIS.clearDaySelected();
                var $day = $(this);
                $day.addClass('Month_daySelected');
                onEventClickedCB($day.data('events')); // invoke user callback
            }
        });

        $templateView.on('mouseover', '.Month_event', function() {
            var objEv = $(this).data('event');
            var id = objEv.isEcho ? objEv.origEvent.id : objEv.id;
            $templateView.find('.Month_event_'+id).addClass('Month_eventHover');
        });

        $templateView.on('mouseout', '.Month_event', function() {
            var objEv = $(this).data('event');
            var id = objEv.isEcho ? objEv.origEvent.id : objEv.id;
            $templateView.find('.Month_event_'+id).removeClass('Month_eventHover');
        });
    }

    function _LoadEventsOfCurMonth() {
        var defer = $.Deferred();
        var $loading = $('#Month_template .Month_loading').clone();
        $templateView.hide().after($loading);
        userOpts.events.loadMonth(curCalendarId, DateCalc.firstOfMonth(curDate)).always(function() {
            $loading.remove();
        }).done(function() {
            _RenderCells();
            $templateView.velocity('fadeIn', {
                duration: userOpts.animationTime,
                complete: function() {
                    defer.resolve();
                }
            });
        }).fail(function() {
            $templateView.show();
            //restore previous view
            defer.reject();
        });
        return defer.promise();
    }

    function _RenderCells() {
        var $divMonthCanvas = $templateView.find('.Month_canvas');
        var numWeeks = DateCalc.weeksInMonth(DateCalc.firstOfMonth(curDate));
        var runDate = DateCalc.sundayOfWeek(DateCalc.firstOfMonth(curDate));
        var dateToday = DateCalc.today();
        $divMonthCanvas.empty();

        for (var w = 0; w < numWeeks; ++w) {
            var $week = $('#Month_template .Month_week').clone();
            for (var d = 0; d < 7; ++d) {
                var $day = $('#Month_template .Month_day').clone();
                if (runDate.getMonth() !== curDate.getMonth()) {
                    $day.addClass('Month_dayOutside');
                }
                if (DateCalc.isSameDay(runDate, dateToday)) {
                    $day.addClass('Month_dayToday');
                }
                $day.find('.Month_dayDisplay').text(runDate.getDate());
                $day.data('date', DateCalc.clone(runDate)); // store a date object for this day
                _FillEvents(runDate, $day);
                runDate.setDate(runDate.getDate() + 1); // advance 1 day
                $day.appendTo($week);
            }
            $week.addClass('Month_'+numWeeks+'weeks'); // specific CSS style to define row height
            $divMonthCanvas.append($week);
        }
    }

    function _FillEvents(when, $day) {
        var events = userOpts.events.inDay(curCalendarId, when);
        for (var i = 0; i < events.length; ++i) {
            var $box = $('#Month_template .Month_event').clone();
            if (events[i].isEcho) {
                $box.addClass('Month_eventEcho');
                if (DateCalc.isSameDay(events[i].origEvent.until, when)) {
                    $box.addClass('Month_eventEchoLastDay');
                }
            }
            var objEv = events[i].isEcho ? events[i].origEvent : events[i];
            $box.addClass('Month_event_'+objEv.id); // class name with ID to speed up mouseover
            $box.css('color', objEv.color);
            if (!objEv.wholeDay) {
                $box.find('.Month_eventHour').text(DateCalc.makeHourMinuteStr(objEv.from));
            }
            $box.find('.Month_eventName').text(objEv.summary);
            if (objEv.confirmation === 'DECLINED') {
                $box.addClass('Month_eventDeclined');
            }
            $box.data('event', objEv);
            $day.find('.Month_dayContent').append($box);
        }
        $day.data('events', events); // store events array for this day
    }
};
});
