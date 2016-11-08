/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Tine.Events');

/**
 * @class     Tine.Events.EventDetailsPanel
 * @namespace Events
 * @extends   Tine.widgets.grid.DetailsPanel
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Tine.Events.EventDetailsPanel = Ext.extend(Tine.widgets.grid.DetailsPanel, {
    
    app: null,
    
    /**
     * @cfg {Number} defaultHeight
     * default Heights
     */
    defaultHeight: 150,
    
    initComponent: function() {
        this.summaryRecord = new Ext.data.Record({
            count: 0
        }, 0);
        
        Tine.Events.EventDetailsPanel.superclass.initComponent.call(this);
    },
        /**
     * renders datetime
     * 
     * @param {Date} dt
     * @return {String}
     */
    datetimeRenderer: function(dt) {
        if (! dt) {
            return this.app.i18n._('Unknown date');
        }
        
        return String.format(this.app.i18n._("{0} {1} o'clock"), dt.format('l') + ', ' + Tine.Tinebase.common.dateRenderer(dt), dt.format('H:i'));
    },
        /**
     * renders department
     * 
     * @param {List} d
     * @return {String}
     */
    departmentRenderer: function(d) {
        if (! d) {
            return '';
        }
        
        return d.name;
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
                    fieldLabel: this.app.i18n._('Total Events')
                }], 160)
            );
        }
        return this.multiRecordsPanel;
    },
    
    /**
     * main Event record details panel
     * 
     * @return {Ext.ux.display.DisplayPanel}
     */
    getSingleRecordPanel: function() {
        if (! this.singleRecordPanel) {
            this.singleRecordPanel = new Ext.ux.display.DisplayPanel(
                this.wrapPanel([{
                    xtype: 'ux.displayfield',
                    name: 'title',
                    fieldLabel: this.app.i18n._('Title')
                }, {
                    xtype: 'ux.displayfield',
                    name: 'action',
                    fieldLabel: this.app.i18n._('Action'),
                    renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('Events', 'actionType', 'text')
                }, {
                    xtype: 'ux.displayfield',
                    name: 'project_type',
                    fieldLabel: this.app.i18n._('Project type'),
                    renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('Events', 'projectType', 'text')
                }, {
                    xtype: 'ux.displayfield',
                    name: 'department',
                    fieldLabel: this.app.i18n._('Department'),
                    renderer: this.departmentRenderer.createDelegate(this)
                }, {
                    xtype: 'ux.displayfield',
                    name: 'event_dtstart',
                    fieldLabel: this.app.i18n._('Start Time'),
                    renderer: this.datetimeRenderer.createDelegate(this)
                }, {
                    xtype: 'ux.displayfield',
                    name: 'event_dtend',
                    fieldLabel: this.app.i18n._('End Time'),
                    renderer: this.datetimeRenderer.createDelegate(this)
                }, {
                    xtype: 'ux.displayfield',
                    name: 'location',
                    fieldLabel: this.app.i18n._('Location')
                }
                ], 80)
            );
        }
        return this.singleRecordPanel;
    },
    
    /**
     * update Event record details panel
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
