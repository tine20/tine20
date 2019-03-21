/*
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Tine.Timetracker');

Tine.widgets.grid.RendererManager.register('Timetracker', 'Timesheet', 'timeaccount_closed', function(row, index, record) {
    var isopen = (record.data.timeaccount_id.is_open == '1');
    return Tine.Tinebase.common.booleanRenderer(!isopen);
});

Tine.widgets.grid.RendererManager.register('Timetracker', 'Timesheet', 'timeaccount_id', function(row, index, record) {
    var record = new Tine.Timetracker.Model.Timeaccount(record.get('timeaccount_id'));
    var closedText = record.get('is_open') ? '' : (' (' + Tine.Tinebase.appMgr.get('Timetracker').i18n._('closed') + ')');
    return record.get('number') ? (record.get('number') + ' - ' + record.get('title') + closedText) : '';
});

// Tine.Tinebase.data.TitleRendererManager.register('Timetracker', 'Timeaccount', function(record) {
//     var closedText = record.get('is_open') ? '' : (' (' + Tine.Tinebase.appMgr.get('Timetracker').i18n._('closed') + ')');
//     return record.get('number') ? (record.get('number') + ' - ' + record.get('title') + closedText) : '';
// });

Tine.Tinebase.data.TitleRendererManager.register('Timetracker', 'Timesheet', function(record) {
    var timeaccount = record.get('timeaccount_id'),
        description = Ext.util.Format.ellipsis(record.get('description'), 30, true),
        timeaccountTitle = '';

    if (timeaccount) {
        if (typeof(timeaccount.get) !== 'function') {
            timeaccount = new Tine.Timetracker.Model.Timeaccount(timeaccount);
        }
        timeaccountTitle = timeaccount.getTitle();
    }

    timeaccountTitle = timeaccountTitle ? '[' + timeaccountTitle + '] ' : '';
    return timeaccountTitle + description;
});

Tine.widgets.grid.RendererManager.register('Timetracker', 'Timeaccount', 'status', function(row, index, record) {
    return Tine.Tinebase.appMgr.get('Timetracker').i18n._hidden(record.get('status'));
});

Tine.widgets.grid.RendererManager.register('Timetracker', 'Timeaccount', 'is_open', function(row, index, record) {
    var i18n = Tine.Tinebase.appMgr.get('Timetracker').i18n;
    return record.get('is_open') ? i18n._('open') : i18n._('closed');
});

// add renderer for invoice position gridpanel
Tine.Timetracker.HumanHourRenderer = function(value) {
    return Ext.util.Format.round(value, 2);
};

Tine.Timetracker.registerRenderers = function() {
    
    if (! Tine.hasOwnProperty('Sales') || ! Tine.Sales.hasOwnProperty('InvoicePositionQuantityRendererRegistry')) {
        Tine.Timetracker.registerRenderers.defer(10);
        return false;
    }
    
    Tine.Sales.InvoicePositionQuantityRendererRegistry.register('Timetracker_Model_Timeaccount', 'hour', Tine.Timetracker.HumanHourRenderer);
};

Tine.Timetracker.registerRenderers();

Tine.Timetracker.registerAccountables = function() {
    if (! Tine.hasOwnProperty('Sales') || ! Tine.Sales.hasOwnProperty('AccountableRegistry')) {
        Tine.Timetracker.registerAccountables.defer(10);
        return false;
    }
    
    Tine.Sales.AccountableRegistry.register('Timetracker', 'Timeaccount');
};

Tine.Timetracker.registerAccountables();