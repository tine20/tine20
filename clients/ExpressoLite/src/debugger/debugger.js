/*!
 * Expresso Lite
 * Main script of debugger module.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

require.config({
    baseUrl: '..',
    paths: { jquery: 'common-js/jquery.min' }
});

require(['jquery',
    'common-js/App',
    'common-js/Layout',
    'common-js/SimpleMenu',
    'debugger/WidgetTineCookies',
    'debugger/WidgetComponentTest'
],
function($, App, Layout, SimpleMenu, WidgetTineCookies, WidgetComponentTest) {
window.Cache = {
    layout: null,
    simpleMenu: null
};

window.DebugWidgets = {
    widgetTineCookies: null,
    widgetComponentTest: null
}

$(document).ready(function() {
    (function constructor() {
        initLayout();
        initSimpleMenu();
        initWidgetTineCookies();
        initWidgetComponentTest();

        $.when(
            Cache.layout.load(),
            DebugWidgets.widgetTineCookies.Load(),
            DebugWidgets.widgetComponentTest.Load()
       ).done(function() {
           Cache.simpleMenu.selectFirstOption();
        });
    })();

    function initLayout() {
        Cache.layout = new Layout({
            userMail: App.GetUserInfo('mailAddress'),
            $menu: $('#leftMenu'),
            $middle: $('#mainContent')
        });

        Cache.layout
        .onSearch(function(text){
            window.alert('Busca não disponível no módulo Debugger');
        })
        .onKeepAlive(function() {
            App.Post('checkSessionStatus');
            // we just want to keep the session alive,
            // so no need for onDone
        });

        return Cache.layout;
    }

    function initSimpleMenu() {
        Cache.simpleMenu = new SimpleMenu({
            $parentContainer: $('#menuDiv')
        });

        Cache.simpleMenu
        .addOption('Tine Cookies', 'tine_cookies', function() {
            selectDebugWidget(DebugWidgets.widgetTineCookies);
        }).addOption('Widget Test', 'widget_test', function() {
            selectDebugWidget(DebugWidgets.widgetComponentTest);
        });

        return Cache.simpleMenu;
    }

    function initWidgetTineCookies() {
        DebugWidgets.widgetTineCookies = new WidgetTineCookies({
            $parentContainer: $('#tineCookiesSection')
        });

        return DebugWidgets.widgetTineCookies;
    }

    function initWidgetComponentTest() {
        DebugWidgets.widgetComponentTest = new WidgetComponentTest({
            $parentContainer: $('#componentTestSection')
        });

        return DebugWidgets.widgetComponentTest;
    }

    function selectDebugWidget(widget) {
        for (var i in DebugWidgets) {
            DebugWidgets[i].Hide();
        }

        widget.Refresh();
        widget.Show();
        Cache.layout.setTitle(widget.GetTitle());
    }
}); // $(document).ready

}); // require
