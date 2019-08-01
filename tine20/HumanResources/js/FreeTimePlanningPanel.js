/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

Tine.HumanResources.FreeTimePlanningPanel = Ext.extend(Tine.widgets.grid.GridPanel, {

    // private
    recordClass: 'Tine.HumanResources.Model.Employee',
    // border: false,
    // layout: 'border',
    // usePagingToolbar: false,
    // autoRefreshInterval: null,
    // listenMessageBus: false,
});

Ext.reg('humanresources.freetimeplanning', Tine.HumanResources.FreeTimePlanningPanel);

Tine.HumanResources.FreeTimePlanningWestPanel = Ext.extend(Tine.widgets.mainscreen.WestPanel, {
    recordClass: 'Tine.HumanResources.Model.Employee',
    hasContainerTreePanel: false,
    hasFavoritesPanel: true
});

Tine.onAppLoaded('HumanResources').then(() => {
    console.warn('HR Loaded ;-)');
})