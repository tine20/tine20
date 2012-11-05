/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Tine.ExampleApplication');

/**
 * @class     Tine.ExampleApplication.ExampleRecordDetailsPanel
 * @namespace ExampleApplication
 * @extends   Tine.widgets.grid.DetailsPanel
 * @author    Alexander Stintzing <a.stintzing@metaways.de>
 */

Tine.ExampleApplication.ExampleRecordDetailsPanel = Ext.extend(Tine.widgets.grid.DetailsPanel, {
    
    app: null,
    
    /**
     * @cfg {Number} defaultHeight
     * default Heights
     */
    defaultHeight: 100,
    
    initComponent: function() {
        this.summaryRecord = new Ext.data.Record({
            count: 0
        }, 0);
        
        Tine.ExampleApplication.ExampleRecordDetailsPanel.superclass.initComponent.call(this);
    },
    
    /**
     * default panel w.o. data
     * 
     * @return {Ext.ux.display.DisplayPanel}
     */
    getDefaultInfosPanel: function() {
        return this.getMultiRecordsPanel();
    },
    
    /**
     * get panel for multi selection aggregates/information
     * 
     * @return {Ext.Panel}
     */
    getMultiRecordsPanel: function() {
        if (! this.multiRecordsPanel) {
            this.multiRecordsPanel = new Ext.ux.display.DisplayPanel(
                this.wrapPanel([{
                    xtype: 'ux.displayfield',
                    name: 'count',
                    fieldLabel: this.app.i18n._('Total Example Records')
                }], 160)
            );
        }
        return this.multiRecordsPanel;
    },
    
    /**
     * main example record details panel
     * 
     * @return {Ext.ux.display.DisplayPanel}
     */
    getSingleRecordPanel: function() {
        if (! this.singleRecordPanel) {
            this.singleRecordPanel = new Ext.ux.display.DisplayPanel(
                this.wrapPanel([{
                    xtype: 'ux.displayfield',
                    name: 'name',
                    fieldLabel: this.app.i18n._('Name')
                }, {
                    xtype: 'ux.displayfield',
                    name: 'status',
                    fieldLabel: this.app.i18n._('Status'),
                    renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('ExampleApplication', 'exampleStatus', 'text')
                }], 80)
            );
        }
        return this.singleRecordPanel;
    },
    
    /**
     * update example record details panel
     * 
     * @param {Tine.Tinebase.data.Record} record
     * @param {Mixed} body
     */
    updateDetails: function(record, body) {
        this.getSingleRecordPanel().loadRecord.defer(100, this.getSingleRecordPanel(), [record]);
    },
    
    /**
     * show default template
     * 
     * @param {Mixed} body
     */
    showDefault: function(body) {
        this.showMulti(this.grid.getSelectionModel());
    },
    
    /**
     * show template for multiple rows
     * 
     * @param {Ext.grid.RowSelectionModel} sm
     * @param {Mixed} body
     */
    showMulti: function(sm, body) {
        if (sm.getCount() === 0) {
            var count = this.grid.store.proxy.jsonReader.jsonData.totalcount;
        } else {
            var count = sm.getCount();
        }
        
        this.summaryRecord.set('count', count);
        
        this.getMultiRecordsPanel().loadRecord.defer(100, this.getMultiRecordsPanel(), [this.summaryRecord]);
    }
});
