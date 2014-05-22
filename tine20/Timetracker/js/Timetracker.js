/*
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Tine.Timetracker');

/**
 * @namespace   Tine.Timetracker
 * @class       Tine.Timetracker.Application
 * @extends     Tine.Tinebase.Application
 * Timetracker Application Object <br>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Timetracker.Application = Ext.extend(Tine.Tinebase.Application, {
    init: function() {
        Tine.Timetracker.Application.superclass.init.apply(this, arguments);
        
        Ext.ux.ItemRegistry.registerItem('Tine.widgets.grid.GridPanel.addButton', {
            text: this.i18n._('New Timesheet'), 
            iconCls: 'TimetrackerTimesheet',
            scope: this,
            handler: function() {
                var ms = this.getMainScreen(),
                    cp = ms.getCenterPanel('Timesheet');
                    
                cp.onEditInNewWindow.call(cp, {});
            }
        });
    }
});

/**
 * @namespace   Tine.Timetracker
 * @class       Tine.Timetracker.MainScreen
 * @extends     Tine.widgets.MainScreen
 * MainScreen of the Timetracker Application <br>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * 
 * @constructor
 */
Tine.Timetracker.MainScreen = Ext.extend(Tine.widgets.MainScreen, {
    activeContentType: 'Timesheet',
    contentTypes: [
        {modelName: 'Timesheet',   requiredRight: null,     singularContainerMode: true},
        {modelName: 'Timeaccount', requiredRight: 'manage', singularContainerMode: true}]
});

/**
 * default filter panels
 */
Tine.Timetracker.TimesheetFilterPanel = function(config) {
    Ext.apply(this, config);
    Tine.Timetracker.TimesheetFilterPanel.superclass.constructor.call(this);
};

Ext.extend(Tine.Timetracker.TimesheetFilterPanel, Tine.widgets.persistentfilter.PickerPanel, {
    filter: [{field: 'model', operator: 'equals', value: 'Timetracker_Model_TimesheetFilter'}]
});

Tine.Timetracker.TimeaccountFilterPanel = function(config) {
    Ext.apply(this, config);
    Tine.Timetracker.TimeaccountFilterPanel.superclass.constructor.call(this);
};

Ext.extend(Tine.Timetracker.TimeaccountFilterPanel, Tine.widgets.persistentfilter.PickerPanel, {
    filter: [{field: 'model', operator: 'equals', value: 'Timetracker_Model_TimeaccountFilter'}]
});



/**
 * default timesheets backend
 */
Tine.Timetracker.timesheetBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Timetracker',
    modelName: 'Timesheet',
    recordClass: Tine.Timetracker.Model.Timesheet
});

/**
 * default timeaccounts backend
 */
Tine.Timetracker.timeaccountBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Timetracker',
    modelName: 'Timeaccount',
    recordClass: Tine.Timetracker.Model.Timeaccount
});



// add renderer for invoice position gridpanel
Tine.Timetracker.HumanHourRenderer = function(value) {
    return Ext.util.Format.round(value, 2);
}

Tine.Timetracker.registerRenderers = function() {
    
    if (! Tine.hasOwnProperty('Sales') || ! Tine.Sales.hasOwnProperty('InvoicePositionQuantityRendererRegistry')) {
        Tine.Timetracker.registerRenderers.defer(500);
        return false;
    }
    
    Tine.Sales.InvoicePositionQuantityRendererRegistry.register('Timetracker_Model_Timeaccount', Tine.Timetracker.HumanHourRenderer);
};

Tine.Timetracker.registerRenderers();
