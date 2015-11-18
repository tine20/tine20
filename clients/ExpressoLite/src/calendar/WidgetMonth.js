/*!
 * Expresso Lite
 * Widget to render a full month.
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
App.LoadCss('calendar/WidgetMonth.css');
return function(options) {
    var userOpts = $.extend({
        events: null, // Events cache object
        $elem: null, // jQuery object for the target DIV
        animationTime: 250
    }, options);

    var THIS             = this;
    var $templateView    = null; // jQuery object with our HTML template
    var curDate          = DateCalc.today(); // month currently displayed
    var onMonthChangedCB = null; // user callbacks
    var onEventClickedCB = null;

    THIS.load = function() {
        return $('#Month_template').length ? // load once
            $.Deferred().resolve().promise() :
            App.LoadTemplate('WidgetMonth.html');
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
            $templateView = $('#Month_template .Month_container').clone();
            $templateView.appendTo(userOpts.$elem).hide();
            _SetWidgetEvents();
        }
        return _LoadEventsOfCurMonth();
    };

    THIS.getCurDate = function() {
        return curDate;
    };

    THIS.onMonthChanged = function(callback) {
        onMonthChangedCB = callback; // onMonthChanged()
        return THIS;
    };

    THIS.onEventClicked = function(callback) {
        onEventClickedCB = callback; // onEventClicked(events)
        return THIS;
    };

    function _SetWidgetEvents() {
        $templateView.find('.Month_prev').on('click', function() {
            $(this).blur();
            curDate = DateCalc.prevMonth(DateCalc.firstOfMonth(curDate));
            _LoadEventsOfCurMonth().done(function() {
                if (onMonthChangedCB !== null) {
                    onMonthChangedCB(); // invoke user callback
                }
            });
        });

        $templateView.find('.Month_next').on('click', function() {
            $(this).blur();
            curDate = DateCalc.nextMonth(DateCalc.firstOfMonth(curDate));
            _LoadEventsOfCurMonth().done(function() {
                if (onMonthChangedCB !== null) {
                    onMonthChangedCB(); // invoke user callback
                }
            });
        });

        $templateView.on('click', '.Month_event', function() {
            if (onEventClickedCB !== null && !App.IsPhone()) { // single event click works only on desktop
                onEventClickedCB($(this).parents('.Month_day').data('events')); // invoke user callback
            }
        });

        $templateView.on('click', '.Month_day', function() {
            if (onEventClickedCB !== null && App.IsPhone()) { // whole day click works only on phones
                onEventClickedCB($(this).data('events')); // invoke user callback
            }
        });
    }

    function _LoadEventsOfCurMonth() {
        var defer = $.Deferred();
        var $loading = $('#Month_template .Month_loading').clone();
        $templateView.hide().after($loading);
        userOpts.events.loadMonth(DateCalc.firstOfMonth(curDate)).done(function() {
            _RenderCells();
            $loading.remove();
            $templateView.fadeIn(userOpts.animationTime, function() {
                defer.resolve();
            });
        }).fail(function() {
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
        var events = userOpts.events.inDay(when);
        for (var i = 0; i < events.length; ++i) {
            var $box = $('#Month_template .Month_event').clone();
            $box.css('color', events[i].color);
            $box.find('.Month_eventHour').text(DateCalc.makeHourMinuteStr(events[i].from));
            $box.find('.Month_eventName').text(events[i].summary);
            if (events[i].confirmation === 'DECLINED') {
                $box.addClass('Month_eventDeclined');
            }
            $box.attr('title', events[i].summary);
            $box.data('event', events[i]);
            $day.find('.Month_dayContent').append($box);
        }
        $day.data('events', events); // store events array for this day
    }
};
});
