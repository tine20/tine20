/*!
 * Expresso Lite
 * Widget to render a day/month/year picker.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */

define(['jquery',
    'common-js/App',
    'common-js/UrlStack',
    'calendar/DateCalc'
],
function($, App, UrlStack, DateCalc) {
App.loadCss('calendar/WidgetChooseDate.css');
var WidgetChooseDate = function(options) {
    var userOpts = $.extend({
        $elem: null, // jQuery object for the target DIV
        dtInit: DateCalc.today() // initial rendered date
    }, options);

    var THIS        = this;
    var $txtBox     = null; // renders the date YMD numbers
    var $panel      = null; // renders the month grid
    var isMouseDown = false;
    var curDate     = userOpts.dtInit; // currently chosen date
    var renderDate  = userOpts.dtInit; // currently rendered month
    var onSelectCB  = $.noop; // user callback

    (function _Constructor() {
        $txtBox = $('#ChooseDate_template .ChooseDate_txtBox').clone();
        userOpts.$elem.empty().append($txtBox);
        $txtBox.val(DateCalc.makeDayMonthYearStr(userOpts.dtInit));

        $panel = $('#ChooseDate_template .ChooseDate_panel').clone();

        _SetEvents();
    })();

    function _SetEvents() {
        $txtBox.on('focus', function() {
            _Show();
        });

        $txtBox.on('blur', function() {
            if (!isMouseDown) {
                _Hide();
            }
        });

        $txtBox.add($panel).on('keydown', function(ev) {
            if (ev.which === 27) { // esc
                ev.stopImmediatePropagation();
                _Hide();
                return false;
            }
        });

        $txtBox.add($panel).on('mousedown', function() {
            isMouseDown = true;
        });

        $txtBox.add($panel).on('mouseup', function() {
            isMouseDown = false;
        });

        $panel.on('click', '.ChooseDate_prevNav', function() {
            renderDate = DateCalc.prevMonth(renderDate);
            _RenderCells();
        });

        $panel.on('click', '.ChooseDate_nextNav', function() {
            renderDate = DateCalc.nextMonth(renderDate);
            _RenderCells();
        });

        $panel.on('blur', '.ChooseDate_prevNav,.ChooseDate_nextNav', function() {
            if (!isMouseDown) {
                _Hide();
            }
        });

        $panel.on('click', '.ChooseDate_day', function() {
            var dt = $(this).data('date');
            _Hide().done(function() {
                curDate = dt;
                $txtBox.val(DateCalc.makeDayMonthYearStr(curDate));
                onSelectCB(curDate);
            });
        });
    }

    function _Hide() {
        var defer = $.Deferred();
        if ($panel.is(':visible')) {
            UrlStack.pop('#monthPopup');
            $panel.detach();
            if ($('.ChooseDate_darkCover').length) { // shown on phone only
                $('.ChooseDate_darkCover').velocity('fadeOut', {
                    duration: 200,
                    complete: function() {
                        $('.ChooseDate_darkCover').remove();
                        defer.resolve();
                    }
                });
            } else {
                defer.resolve();
            }
        } else {
            defer.resolve();
        }
        return defer.promise();
    }

    function _Show() {
        _Hide().done(function() {
            renderDate = curDate;
            _RenderCells();
            var css = { };
            if (App.isPhone()) { // as a modal popup
                $(document.createElement('div'))
                    .addClass('ChooseDate_darkCover') // dark background cover on screen
                    .appendTo(document.body)
                    .one('click.ChooseDate', _Hide);

                var xpos = $(document).width() / 2 - 280 / 2; // fixed number because buggy mobile
                css = {
                    left: xpos+'px',
                    top: '25%' // empirically found
                };
                UrlStack.push('#monthPopup', _Hide);
            } else { // as a small modeless popup just below the textbox
                var txtPos = $txtBox.offset();
                css = {
                    left: txtPos.left+'px',
                    top: (txtPos.top + $txtBox.outerHeight() + 1)+'px'
                };
            }
            $panel.appendTo(document.body).css(css);
        });
    }

    function _RenderCells() {
        var $canvas = $panel.find('.ChooseDate_canvas');
        var numWeeks = DateCalc.weeksInMonth(DateCalc.firstOfMonth(renderDate));
        var runDate = DateCalc.sundayOfWeek(DateCalc.firstOfMonth(renderDate));
        var dateToday = DateCalc.today();

        $canvas.empty();
        $panel.find('.ChooseDate_curMonthName').text(
            DateCalc.monthName(renderDate.getMonth())+' '+renderDate.getFullYear() );

        for (var w = 0; w < numWeeks; ++w) {
            var $week = $('#ChooseDate_template .ChooseDate_week').clone();
            for (var d = 0; d < 7; ++d) {
                var $day = $('#ChooseDate_template .ChooseDate_day').clone();
                if (runDate.getMonth() !== renderDate.getMonth()) {
                    $day.addClass('ChooseDate_dayOutside');
                }
                if (DateCalc.isSameDay(runDate, dateToday)) {
                    $day.addClass('ChooseDate_dayToday');
                }
                if (DateCalc.isSameDay(runDate, curDate)) {
                    $day.addClass('ChooseDate_daySelected');
                }
                $day.text(runDate.getDate());
                $day.data('date', DateCalc.clone(runDate)); // store a date object for this day
                runDate.setDate(runDate.getDate() + 1); // advance 1 day
                $day.appendTo($week);
            }
            $canvas.append($week);
        }
    }

    THIS.getCurDate = function() {
        return curDate;
    };

    THIS.setCurDate = function(dt) {
        curDate = dt;
        $txtBox.val(DateCalc.makeDayMonthYearStr(curDate));
        onSelectCB(curDate);
        return THIS;
    };

    THIS.onSelect = function(callback) {
        onSelectCB = callback; // onSelect(date)
        return THIS;
    };
};

WidgetChooseDate.Load = function() {
    // Static method, since this class can be instantiated ad-hoc.
    return $('#ChooseDate_template').length ?
        $.Deferred().resolve().promise() :
        App.loadTemplate('WidgetChooseDate.html');
};

return WidgetChooseDate;
});
