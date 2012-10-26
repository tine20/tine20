/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Tine.Timetracker');

/**
 * @namespace   Tine.Timetracker
 * @class       Tine.Timetracker.TimeaccountContractFilterModel
 * @extends     Tine.widgets.grid.ForeignRecordFilter
 * 
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */
Tine.Timetracker.TimeaccountContractFilterModel = Ext.extend(Tine.widgets.grid.ForeignRecordFilter, {
    
    // private
    field: 'contract',
    valueType: 'relation',
    
    /**
     * @private
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Timetracker');
        this.label = this.app.i18n._('Contract');
        this.foreignRecordClass = 'Sales.Contract',
        this.pickerConfig = {emptyText: this.app.i18n._('without contract'), allowBlank: true};

        Tine.Timetracker.TimeaccountContractFilterModel.superclass.initComponent.call(this);
    }
});

Tine.widgets.grid.FilterToolbar.FILTERS['timetracker.timeaccountcontract'] = Tine.Timetracker.TimeaccountContractFilterModel;