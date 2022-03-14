Tine.Tinebase.ApplicationStarter.isInitialised().then(() => {
    const app = Tine.Tinebase.appMgr.get('HumanResources')

    Tine.widgets.MainScreen.registerContentType('Timetracker', {appName: 'HumanResources', modelName: 'DailyWTReport', group: app.i18n._('Workingtime Tracking'), groupIconCls: 'HumanResourcesTimetrackerHooks'});
    Tine.widgets.MainScreen.registerContentType('Timetracker', {appName: 'HumanResources', modelName: 'MonthlyWTReport', group: app.i18n._('Workingtime Tracking'), groupIconCls: 'HumanResourcesTimetrackerHooks'});
    Tine.widgets.MainScreen.registerContentType('Timetracker', {appName: 'HumanResources', modelName: 'FreeTime', group: app.i18n._('Workingtime Tracking'), groupIconCls: 'HumanResourcesTimetrackerHooks'});

    Tine.widgets.MainScreen.registerContentType('Timetracker', {
        appName: 'HumanResources',
        contentType: 'FreeTimePlanning',
        text: app.i18n._('Free Time Planning'),
        xtype: 'humanresources.freetimeplanning',
        group: app.i18n._('Workingtime Tracking'),
        groupIconCls: 'HumanResourcesTimetrackerHooks'
    });

});
