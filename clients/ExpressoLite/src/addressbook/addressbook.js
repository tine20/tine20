/*!
 * Expresso Lite
 * Main script of addressbook module.
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
    'addressbook/WidgetCatalogMenu',
    'addressbook/WidgetContactList',
    'addressbook/WidgetContactDetails'
],
function($, App, Layout, WidgetCatalogMenu, WidgetContactList, WidgetContactDetails) {
window.Cache = {
    layout: null,
    widgetCatalogMenu: null,
    widgetContactList: null,
    widgetContactDetails: null
};

App.Ready(function() {
    function showDetailView() {
        if (!Cache.layout.isRightPanelVisible()) {
            Cache.layout.setRightPanelVisible(true);
            Cache.widgetContactList.scrollToCurrentItem();
        }
    }

    function showListView() {
        Cache.widgetContactList.unselectCurrentItem();
        if (Cache.layout.isRightPanelVisible()) {
            Cache.layout.setRightPanelVisible(false);
        }
    }

    (function constructor() {
        Cache.layout = new Layout({
            userMail: App.GetUserInfo('mailAddress'),
            $menu: $('#leftMenu'),
            $middle: $('#contactListSection'),
            $right: $('#contactDetailsSection')
        });

        Cache.layout
        .onSearch(function (text){
            showListView();
            Cache.widgetContactList.changeQuery(text);
        })
        .onHideRightPanel(function () {
            showListView();
        })
        .onKeepAlive(function () {
            App.Post('checkSessionStatus');
            // we just want to keep the session alive,
            // so no need for onDone
        });


        Cache.widgetCatalogMenu = new WidgetCatalogMenu({
            $parentContainer: $('#tipoContatoDiv')
        });

        Cache.widgetCatalogMenu
        .addOption('Catálogo Corporativo', function () {
            Cache.layout.setLeftMenuVisibleOnPhone(false)
            .done(function() {
                Cache.widgetContactList.changeToCorporateCatalog();
            });
        }).addOption('Catálogo Pessoal', function () {
            Cache.layout.setLeftMenuVisibleOnPhone(false)
            .done(function() {
                Cache.widgetContactList.changeToPersonalCatalog();
            });
        });

        Cache.widgetContactList = new WidgetContactList({
            $parentContainer: $('#contactListSection')
        });

        Cache.widgetContactList.onItemClick(function(contact) {
            showDetailView();
            Cache.widgetContactDetails.showDetails(contact);
        });


        Cache.widgetContactDetails = new WidgetContactDetails({
            $parentContainer: $('#contactDetailsSection')
        });

        Cache.widgetContactDetails.load();
        //user shouldn't be kept waiting for this just right now

        $.when(
            Cache.widgetContactList.load(),
            Cache.layout.load()
        ).done(function() {
            Cache.widgetContactList.changeToCorporateCatalog();
        });
    })();
}); // App.Ready

}); // require
