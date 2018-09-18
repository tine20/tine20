/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

/**
 * @namespace   Tine.HumanResources
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */

Tine.HumanResources.BreakGridPanel = Ext.extend(Tine.widgets.grid.QuickaddGridPanel, {
    /*
     * config
     */
    frame: true,
    border: true,
    autoScroll: true,
    layout: 'fit',
    defaultSortInfo: {field: 'hours', direction: 'DESC'},
    autoExpandColumn: 'hours',
    quickaddMandatory: 'hours',
    clicksToEdit: 1,
    enableColumnHide:false,
    enableColumnMove:false,
    enableHdMenu: false,
    validate: true,
    /*
     * public
     */
    app: null,
    
    /**
     * the calling editDialog
     */
    editDialog: null,

    stateful: true,
    stateId: 'hr-breakgridpanel',
    
    /**
     * initializes the component
     */
    initComponent: function() {
        this.title = this.app.i18n._('Breaks');

        Tine.HumanResources.BreakGridPanel.superclass.initComponent.call(this);
        
        this.recordClass = Tine.HumanResources.Model.Break;
        this.store.sortInfo = this.defaultSortInfo;
    },
    
    /**
     * loads the existing Breaks into the store
     */
    onRecordLoad: function(record) {
        
        if (this.record) {
            return;
        }

        this.record = record;

        if (record.get('breaks') && record.get('breaks').length > 0) {
            var breakRecords = [];
            Ext.each(record.get('breaks'), function(breakregualation) {
                // If we copy the meeting we have to reset the dependet Top IDs by hand :(
                if (this.editDialog.copyRecord) {
                    breakregualation.workingtime_id = null;
                    breakregualation.id = Tine.Tinebase.data.Record.generateUID();
                }
                
                breakRecords.push(new this.recordClass(breakregualation));
            }, this);
            this.store.add(breakRecords);
        }
    },
    
    /**
     * new entry event -> add new record to store
     * @see Tine.widgets.grid.QuickaddGridPanel
     * @param {Object} recordData
     * @return {Boolean}
     */
    onNewentry: function(recordData) {
        recordData.workingtime_id = this.record.id;
        Tine.HumanResources.BreakGridPanel.superclass.onNewentry.call(this, recordData);
    },
    
    /**
     * returns column model
     * 
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumnModel: function() {
        
        var columns = [
            {id: 'hours', dataIndex: 'hours', header: this.app.i18n._('Hours'), scope: this,
                quickaddField: new Tine.Timetracker.DurationSpinner(),
                editor: new Tine.Timetracker.DurationSpinner(),
                width: 200,
                renderer: Tine.Tinebase.common.minutesRenderer
            }, {id: 'break_duration', dataIndex: 'break_duration', header: this.app.i18n._('Break duration'), scope: this,
                quickaddField: new Tine.Timetracker.DurationSpinner(),
                editor: new Tine.Timetracker.DurationSpinner(),
                width: 200,
                renderer: Tine.Tinebase.common.minutesRenderer
            }
        ];
        
        
        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: false,
                width: 350,
                editable: true
            }, 
            columns: columns
       });
    },

    /**
     * get relations data as array
     *
     * @return {Array}
     */
    getData: function() {
        var breaks = [];
        
        this.store.each(function(record) {
            if (record.data.name != '') {
                breaks.push(record.data);
            }
        }, this);
        
        return breaks;
    }
});

