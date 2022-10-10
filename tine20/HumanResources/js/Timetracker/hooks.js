/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Tine.Tinebase.ApplicationStarter.isInitialised().then(() => {
    const app = Tine.Tinebase.appMgr.get('HumanResources');

    const defaults = {
        appName: 'HumanResources',
        hideColumnsMode: 'hide',
        group: app.i18n._('Workingtime Tracking'),
        groupIconCls: 'HumanResourcesTimetrackerHooks',
        stateIdSuffix: '-timetrackerhook'
    };

    Tine.widgets.MainScreen.registerContentType('Timetracker', Object.assign({
        modelName: 'DailyWTReport',
        showColumns: ['tags', 'employee_id', 'date', 'working_time_target', 'working_time_actual', 'working_time_correction',
            'working_time_total', 'working_time_balance']
    }, defaults));

    Tine.widgets.MainScreen.registerContentType('Timetracker', Object.assign({
        modelName: 'MonthlyWTReport',
        showColumns: ['tags', 'employee_id', 'month', 'working_time_balance_previous', 'working_time_target', 'working_time_correction',
            'working_time_actual', 'working_time_balance']
    }, defaults));

    Tine.widgets.MainScreen.registerContentType('Timetracker', Object.assign({
        modelName: 'FreeTime',
    }, defaults));

    Tine.widgets.MainScreen.registerContentType('Timetracker', Object.assign({
        contentType: 'FreeTimePlanning',
        text: app.i18n._('Free Time Planning'),
        xtype: 'humanresources.freetimeplanning',
    }, defaults));

});
