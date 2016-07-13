/*!
 * Expresso Lite
 * Main script of calendar module.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2015-2016 Serpro (http://www.serpro.gov.br)
 */

require.config({ baseUrl:'..', });

require([
    'common-js/jQuery',
    'common-js/App',
    'common-js/UrlStack',
    'common-js/Layout',
    'common-js/SimpleMenu',
    'common-js/Contacts',
    'common-js/SplashScreen',
    'calendar/DateCalc',
    'calendar/Events',
    'calendar/WidgetMonth',
    'calendar/WidgetWeek',
    'calendar/WidgetEvents',
    'calendar/WidgetEditEvent'
],
function($, App, UrlStack, Layout, SimpleMenu, Contacts, SplashScreen, DateCalc, Events,
    WidgetMonth, WidgetWeek, WidgetEvents, WidgetEditEvent) {
window.Cache = {
    events: null, // Events object
    chooseViewMenu: null,
    chooseCalendarMenu: null,
    viewMonth: null, // WidgetMonth object
    viewWeek: null, // WidgetWeek object
    viewEvents: null, // WidgetEvents object
    wndEditEvent: null, // WidgetEditEvent object, modeless popup
    curView: '', // 'month'|'week'
    curCalendar: null, // calendar object
    curDate: DateCalc.today(), // current date being displayed
    splashScreen: null // splash screen for displaying offline message
};

App.ready(function() {
    // Initialize page objects.
    Cache.layout = new Layout({
        userMail: App.getUserInfo('mailAddress'),
        $menu: $('#leftColumn'),
        $middle: $('#middleBody'),
        $right: $('#rightBody')
    });
    Cache.events = new Events();
    Cache.chooseViewMenu = new SimpleMenu({ $parentContainer: $('#chooseViewMenu') });
    Cache.chooseCalendarMenu = new SimpleMenu({ $parentContainer: $('#chooseCalendarMenu') });
    Cache.viewMonth = new WidgetMonth({ events:Cache.events, $elem: $('#middleBody') });
    Cache.viewWeek = new WidgetWeek({ events:Cache.events, $elem: $('#middleBody') });
    Cache.viewEvents = new WidgetEvents({ events:Cache.events, $elem: $('#rightBody') });
    Cache.wndEditEvent = new WidgetEditEvent({ });
    Cache.splashScreen = new SplashScreen({ });

    // Some initial work.
    UrlStack.keepClean();
    Contacts.loadPersonal();

    // Load templates of widgets.
    $.when(
        Cache.layout.load(),
        Cache.viewMonth.load(),
        Cache.viewWeek.load(),
        Cache.viewEvents.load(),
        Cache.wndEditEvent.load(),
        Cache.splashScreen.load()
    ).done(function() {
        $('#btnRefresh,#btnCreateEvent').css('display', 'none');
        Cache.layout.setLeftMenuVisibleOnPhone(true).done(function() {
            LoadUserCalendars().done(function() {
                $('#btnRefresh,#btnCreateEvent').css('display', '');
            }).fail(function() {
                Cache.layout.setLeftMenuVisibleOnPhone(false).done(function() {
                    Cache.layout.hideTop();
                    Cache.splashScreen.showNoInternetMessage();
                });
            });
        });

        // Setup events.
        Cache.layout
            .onKeepAlive(function() { })
            .onHideRightPanel(function() { Cache.viewMonth.clearDaySelected(); })
            .onSearch(function() { alert('Busca no calendário não implementada nesta versão.'); }); // when user performs a search
        Cache.chooseViewMenu
            .addOption('Ver mês', 'month', function() { SetCalendarView('month'); })
            .addOption('Ver semana', 'week', function() { SetCalendarView('week'); });
        Cache.viewMonth
            .onMonthChanged(UpdateCurrentMonthName)
            .onEventClicked(EventClicked);
        Cache.viewWeek
            .onWeekChanged(UpdateCurrentWeekName)
            .onEventClicked(EventClicked);
        Cache.viewEvents
            .onRemoved(EventRemoved);
        Cache.wndEditEvent
            .onEventSaved(UpdateAfterSaving);
        $('#btnRefresh').on('click', RefreshEvents);
        $('#btnCreateEvent').on('click', CreateEvent);
    });
});

function LoadUserCalendars() {
    var defer = $.Deferred();
    $('#chooseViewMenu,#chooseCalendarMenu').hide();
    App.post('getCalendars')
        .fail(function(resp) {
            App.errorMessage('Erro ao carregar calendários pessoais.', resp);
            defer.reject();
        }).done(function(cals) {
            $('#loadCalendars').hide();
            $('#chooseViewMenu,#chooseCalendarMenu').show();
            $.each(cals, function(idx, cal) {
                Cache.chooseCalendarMenu.addOption(cal.name, cal.id, function() {
                    Cache.curCalendar = cal;
                    (Cache.curView === 'month') ? RenderMonth() : RenderWeek();
                });
            });
            Cache.chooseViewMenu.selectOption('month');
            defer.resolve();
        });
    return defer.promise();
}

function SetCalendarView(view) {
    Cache.layout.setLeftMenuVisibleOnPhone(false).done(function() {
        var isFirst = (Cache.curView === '');
        Cache.curView = view;
        if (isFirst) {
            Cache.chooseCalendarMenu.selectFirstOption();
        } else {
            (view === 'month') ? RenderMonth() : RenderWeek();
        }
    });
}

function RenderMonth() {
    Cache.layout.setLeftMenuVisibleOnPhone(false).done(function() {
        Cache.viewWeek.hide();
        Cache.viewMonth.show(Cache.curCalendar.id, Cache.curDate).done(function() {
            UpdateCurrentMonthName();
        });
    });
}

function RenderWeek() {
    Cache.layout.setLeftMenuVisibleOnPhone(false).done(function() {
        var curMonth = DateCalc.firstOfMonth(Cache.curDate);
        if (DateCalc.isSameMonth(curMonth, DateCalc.today())) {
            curMonth = DateCalc.today();
        }
        Cache.viewMonth.hide();
        Cache.viewWeek.show(Cache.curCalendar.id, curMonth).done(function() {
            UpdateCurrentWeekName();
        });
    });
}

function UpdateCurrentMonthName() {
    Cache.curDate = Cache.viewMonth.getCurDate();
    var curMonth = DateCalc.firstOfMonth(Cache.curDate);
    Cache.layout.setTitle(
        DateCalc.monthName(curMonth.getMonth()) + ', ' +
        curMonth.getFullYear()
    );
}

function UpdateCurrentWeekName() {
    Cache.curDate = Cache.viewWeek.getCurDate();
    var curWeek = DateCalc.sundayOfWeek(Cache.curDate);
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
    Cache.layout.setLeftMenuVisibleOnPhone(false).done(function() {
        $('#btnRefresh,#btnCreateEvent').hide();
        $('#txtRefresh').show();
        var curSel = Cache.chooseViewMenu.getSelectedIdentifier();
        if (curSel === 'month') {
            var day1 = DateCalc.firstOfMonth(Cache.curDate);
            Cache.events.clearMonthCache(Cache.curCalendar.id, day1);
            Cache.viewMonth.show(Cache.curCalendar.id, day1).done(finishedRefreshing);
        } else if (curSel === 'week') {
            var sunday = DateCalc.sundayOfWeek(Cache.curDate);
            Cache.events.clearWeekCache(Cache.curCalendar.id, sunday);
            Cache.viewWeek.show(Cache.curCalendar.id, sunday).done(finishedRefreshing);
        }
    });

    function finishedRefreshing() {
        $('#btnRefresh,#btnCreateEvent').show();
        $('#txtRefresh').hide();
    }
}

function CreateEvent() {
    $('#btnCreateEvent').blur();
    Cache.layout.setLeftMenuVisibleOnPhone(false).done(function() {
        Cache.wndEditEvent.show(Cache.curCalendar);
    });
}

function EventClicked(eventsOfDay, eventClicked) {
    Cache.layout.setRightPanelVisible(true);
    Cache.viewEvents.render(eventsOfDay, eventClicked);
}

function EventRemoved(event) {
    Cache.layout.setRightPanelVisible(false);
    $('#btnRefresh').trigger('click');
}

function UpdateAfterSaving() {
    $('#btnRefresh').trigger('click');
}
});
