/*!
 * Expresso Lite
 * Main script of calendar module.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

require.config({
    baseUrl: '..',
    paths: { jquery: 'common-js/jquery.min' }
});

require(['jquery',
    'common-js/App',
    'common-js/UrlStack',
    'common-js/Layout',
    'calendar/DateCalc',
    'calendar/Events',
    'calendar/WidgetMonth',
    'calendar/WidgetWeek',
    'calendar/WidgetEvents'
],
function($, App, UrlStack, Layout, DateCalc, Events, WidgetMonth, WidgetWeek, WidgetEvents) {
window.Cache = {
    events: null, // Events object
    viewMonth: null, // WidgetMonth object
    viewWeek: null, // WidgetWeek object
    viewEvents: null // WidgetEvents object
};

App.Ready(function() {
    // Initialize page objects.
    Cache.layout = new Layout({
        userMail: App.GetUserInfo('mailAddress'),
        $menu: $('#leftColumn'),
        $middle: $('#middleBody'),
        $right: $('#rightBody')
    });
    Cache.events = new Events();
    Cache.viewMonth = new WidgetMonth({ events:Cache.events, $elem: $('#middleBody') });
    Cache.viewWeek = new WidgetWeek({ events:Cache.events, $elem: $('#middleBody') });
    Cache.viewEvents = new WidgetEvents({ events:Cache.events, $elem: $('#rightBody') });

    // Some initial work.
    UrlStack.keepClean();

    // Load templates of widgets.
    $.when(
        Cache.layout.load(),
        Cache.viewMonth.load(),
        Cache.viewWeek.load(),
        Cache.viewEvents.load()
    ).done(function() {
        Cache.layout.setLeftMenuVisibleOnPhone(true).done(function() {
            $('#renderMonth').trigger('click'); // full month is selected by default
        });

        // Setup events.
        Cache.layout
            .onKeepAlive(function() { })
            .onHideRightPanel(function() { })
            .onSearch(function() { }); // when user performs a search
        Cache.viewMonth
            .onMonthChanged(UpdateCurrentMonthName)
            .onEventClicked(EventClicked);
        Cache.viewWeek
            .onWeekChanged(UpdateCurrentWeekName)
            .onEventClicked(EventClicked);
        $('#btnRefresh').on('click', RefreshEvents);
        $('#renderOptions li').on('click', ChangeRenderOption);
    });
});

function UpdateCurrentMonthName() {
    var curMonth = DateCalc.firstOfMonth(Cache.viewMonth.getCurDate());
    Cache.layout.setTitle(
        DateCalc.monthName(curMonth.getMonth()) + ', ' +
        curMonth.getFullYear()
    );
}

function UpdateCurrentWeekName() {
    var curWeek = DateCalc.sundayOfWeek(Cache.viewWeek.getCurDate());
    var saturday = DateCalc.saturdayOfWeek(curWeek);
    if (curWeek.getMonth() === saturday.getMonth()) {
        Cache.layout.setTitle(
            curWeek.getDate() + ' - ' +
            saturday.getDate() + ' ' +
            DateCalc.monthName(curWeek.getMonth()) + ', ' +
            saturday.getFullYear()
        );
    } else {
        Cache.layout.setTitle(
            curWeek.getDate() + ' ' +
            DateCalc.monthName(curWeek.getMonth()).substr(0, 3) + ' - ' +
            saturday.getDate() + ' ' +
            DateCalc.monthName(saturday.getMonth()).substr(0, 3) + ', ' +
            saturday.getFullYear()
        );
    }
}

function RefreshEvents() {
    var $curLi = $('#renderOptions .renderOptionCurrent');

    Cache.layout.setLeftMenuVisibleOnPhone(false).done(function() {
        $('#btnRefresh').hide();
        $('#txtRefresh').show();

        if ($curLi.is('#renderMonth')) {
            var day1 = DateCalc.firstOfMonth(Cache.viewMonth.getCurDate());
            Cache.events.clearMonthCache(day1);
            Cache.viewMonth.show(day1).done(finishedRefreshing);
        } else if ($curLi.is('#renderWeek')) {
            var sunday = DateCalc.sundayOfWeek(Cache.viewWeek.getCurDate());
            Cache.events.clearWeekCache(sunday);
            Cache.viewWeek.show(sunday).done(finishedRefreshing);
        }
    });

    function finishedRefreshing() {
        $('#btnRefresh').show();
        $('#txtRefresh').hide();
    }
}

function ChangeRenderOption() {
    var $li = $(this);
    $('#renderOptions li').removeClass('renderOptionCurrent'); // remove from all LI
    $li.addClass('renderOptionCurrent');
    Cache.layout.setLeftMenuVisibleOnPhone(false).done(function() {
        if ($li.attr('id') === 'renderMonth') {
            Cache.viewWeek.hide();
            Cache.viewMonth.show(Cache.viewWeek.getCurDate()).done(function() {
                UpdateCurrentMonthName();
            });
        } else if ($li.attr('id') === 'renderWeek') {
            var curMonth = DateCalc.firstOfMonth(Cache.viewMonth.getCurDate());
            if (DateCalc.isSameMonth(curMonth, DateCalc.today())) {
                curMonth = DateCalc.today();
            }
            Cache.viewMonth.hide();
            Cache.viewWeek.show(curMonth).done(function() {
                UpdateCurrentWeekName();
            });
        }
    });
}

function EventClicked(eventsOfDay) {
    Cache.layout.setRightPanelVisible(true);
    Cache.viewEvents.render(eventsOfDay);
}
});
