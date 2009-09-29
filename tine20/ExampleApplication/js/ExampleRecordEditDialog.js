/*
 * Tine 2.0
 * 
 * @package     ExampleApplication
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:ExampleRecordEditDialog.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
 */
 
Ext.namespace('Tine.ExampleApplication');

/**
 * @namespace   Tine.ExampleApplication
 * @class       Tine.ExampleApplication.ExampleRecordEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>ExampleRecord Compose Dialog</p>
 * <p></p>
 * 
 *  @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:ExampleRecordEditDialog.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.ExampleApplication.ExampleRecordEditDialog
 */
Tine.ExampleApplication.ExampleRecordEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'ExampleRecordEditWindow_',
    appName: 'ExampleApplication',
    recordClass: Tine.ExampleApplication.Model.ExampleRecord,
    recordProxy: Tine.ExampleApplication.recordBackend,
    loadRecord: false,
    tbarItems: [{xtype: 'widget-activitiesaddbutton'}],
    evalGrants: false,
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     * @private
     */
    updateToolbars: function() {

    },
    
    /**
     * executed after record got updated from proxy
     * 
     * @private
     */
    onRecordLoad: function() {
    	// you can do something here

    	Tine.ExampleApplication.ExampleRecordEditDialog.superclass.onRecordLoad.call(this);        
    },
    
    /**
     * executed when record gets updated from form
     * - add attachments to record here
     * 
     * @private
     */
    onRecordUpdate: function() {
        Tine.ExampleApplication.ExampleRecordEditDialog.superclass.onRecordUpdate.call(this);
        
        // you can do something here    
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     * 
     * @return {Object}
     * @private
     */
    getFormItems: function() {
        return {
            xtype: 'tabpanel',
            border: false,
            plain:true,
            activeTab: 0,
            border: false,
            items:[{               
                title: this.app.i18n._('ExampleRecord'),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'border',
                items: [{
                    region: 'center',
                    xtype: 'columnform',
                    labelAlign: 'top',
                    formDefaults: {
                        xtype:'textfield',
                        anchor: '100%',
                        labelSeparator: '',
                        columnWidth: .333
                    },
                    items: [/*[{
                        fieldLabel: this.app.i18n._('Number'),
                        name: 'number',
                        allowBlank: false
                        }, {
                        columnWidth: .666,
                        fieldLabel: this.app.i18n._('Title'),
                        name: 'title',
                        allowBlank: false
                        }], [{
                        columnWidth: 1,
                        xtype: 'textarea',
                        name: 'description',
                        height: 150
                        }], [{
                            fieldLabel: this.app.i18n._('Unit'),
                            name: 'price_unit'
                        }, {
                        	xtype: 'numberfield',
                            fieldLabel: this.app.i18n._('Unit Price'),
                            name: 'price',
                            allowNegative: false
                            //decimalSeparator: ','
                        }, {
                            fieldLabel: this.app.i18n._('Budget'),
                            name: 'budget'
                        }, {
                            hideLabel: true,
                            boxLabel: this.app.i18n._('Timesheets are billable'),
                            name: 'is_billable',
                            xtype: 'checkbox'
                        }, {
                            fieldLabel: this.app.i18n._('Status'),
                            name: 'is_open',
                            xtype: 'combo',
                            mode: 'local',
                            forceSelection: true,
                            triggerAction: 'all',
                            store: [[0, this.app.i18n._('closed')], [1, this.app.i18n._('open')]]
                        }, {
                            fieldLabel: this.app.i18n._('Billed'),
                            name: 'status',
                            xtype: 'combo',
                            mode: 'local',
                            forceSelection: true,
                            triggerAction: 'all',
                            value: 'not yet billed',
                            store: [
                                ['not yet billed', this.app.i18n._('not yet billed')], 
                                ['to bill', this.app.i18n._('to bill')],
                                ['billed', this.app.i18n._('billed')]
                            ]
                        }]*/] 
                }, {
                    // activities and tags
                    layout: 'accordion',
                    animate: true,
                    region: 'east',
                    width: 210,
                    split: true,
                    collapsible: true,
                    collapseMode: 'mini',
                    margins: '0 5 0 5',
                    border: true,
                    items: [
                    new Tine.widgets.activities.ActivitiesPanel({
                        app: 'ExampleApplication',
                        showAddNoteForm: false,
                        border: false,
                        bodyStyle: 'border:1px solid #B5B8C8;'
                    }),
                    new Tine.widgets.tags.TagPanel({
                        app: 'ExampleApplication',
                        border: false,
                        bodyStyle: 'border:1px solid #B5B8C8;'
                    })]
                }]
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            })]
        };
    }
});

/**
 * ExampleApplication Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.ExampleApplication.ExampleRecordEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 470,
        name: Tine.ExampleApplication.ExampleRecordEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.ExampleApplication.ExampleRecordEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
