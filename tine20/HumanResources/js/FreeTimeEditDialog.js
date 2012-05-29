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
 * @class       Tine.HumanResources.FreeTimeEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>FreeTime Compose Dialog</p>
 * <p></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * Create a new Tine.HumanResources.FreeTimeEditDialog
 */
Tine.HumanResources.FreeTimeEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'FreeTimeEditWindow_',
    appName: 'HumanResources',
    recordClass: Tine.HumanResources.Model.FreeTime,
    recordProxy: Tine.HumanResources.freetimeBackend,
    tbarItems: [{xtype: 'widget-activitiesaddbutton'}],
    evalGrants: false,
    showContainerSelector: false,
    
    dayLengths: null,
    /**
     * show private Information (autoset due to rights)
     * @type 
     */
    showPrivateInformation: null,
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     * @private
     */
    updateToolbars: function() {

    },
    /**
     * inits the component
     */
    initComponent: function() {
        this.dayLengths = [
            [0.25, 0.25],
            [0.5, 0.5],
            [0.75, 0.75],
            [1, 1]
            ];
        this.showPrivateInformation = (Tine.Tinebase.common.hasRight('edit_private','HumanResources')) ? true : false;
        this.initDatePicker();
        
        Tine.HumanResources.FreeTimeEditDialog.superclass.initComponent.call(this);
    },
    
    /**
     * executed after record got updated from proxy
     * 
     * @private
     */
    onRecordLoad: function() {
        // interrupt process flow until dialog is rendered
        if (! this.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }

        this.datePicker.onRecordLoad(this.record);
        this.firstDayLengthPicker.setValue(this.datePicker.store.getFirstDay().get('duration'));
        this.lastDayLengthPicker.setValue(this.datePicker.store.getLastDay().get('duration'));
        Tine.HumanResources.FreeTimeEditDialog.superclass.onRecordLoad.call(this);
    },
//    
    /**
     * executed when record gets updated from form
     * @private
     */
    onRecordUpdate: function() {
        Tine.HumanResources.FreeTimeEditDialog.superclass.onRecordUpdate.call(this);
        this.record.set('freedays', this.datePicker.getData());
    },
    
    initDatePicker: function() {
        this.datePicker = new Tine.HumanResources.DatePicker({initDate: this.record.get('firstday_date'), app: this.app, record: this.record, recordClass: this.recordClass, editDialog: this, dateProperty: 'date', recordsProperty: 'freedays', foreignIdProperty: 'freeday_id'});
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
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }],
            activeTab: 0,
            border: false,
            items:[{
                title: this.app.i18n._('FreeTime'),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'border',
                items: [{
                    region: 'center',
                    layout: 'hfit',
                    border: false,
                    items: [{
                        xtype: 'fieldset',
                        autoHeight: true,
                        title: this.app.i18n._('FreeTime'),
                        items: [{
                            xtype: 'columnform',
                            style: { float: 'left', width: '50%', 'min-width': '250px' },
                            labelAlign: 'top',
                            formDefaults: {
                                xtype:'textfield',
                                anchor: '100%',
                                labelSeparator: '',
                                allowBlank: false,
                                columnWidth: 1
                            },
                            items: [[
                                new Tine.widgets.form.RecordPickerManager.get('HumanResources', 'Employee', {
                                    fieldLabel: this.app.i18n._('Employee'),
                                    name: 'employee_id'
                                })],[
                                new Tine.Tinebase.widgets.keyfield.ComboBox({
                                    app: 'HumanResources',
                                    keyFieldName: 'freetimeType',
                                    fieldLabel: this.app.i18n._('Type'),
                                    value: 'VACATION',
                                    name: 'type'
                                })],[
                                {
                                    fieldLabel: this.app.i18n._('First Day Length'),
                                    xtype: 'combo',
                                    store: this.dayLengths,
                                    value: 1,
                                    columnWidth: .5,
                                    ref: '../../../../../../../firstDayLengthPicker'
                                },{
                                    fieldLabel: this.app.i18n._('Last Day Length'),
                                    xtype: 'combo',
                                    store: this.dayLengths,
                                    value: 1,
                                    columnWidth: .5,
                                    ref: '../../../../../../../lastDayLengthPicker'
                                }]
                                ]
                        }, {
                            xtype: 'panel',
                            cls: 'HumanResources x-form-item',
                            width: 220,
                            style: {
//                                padding: '18px',
                                float: 'right',
                                margin: '0 5px 10px 0'
                            },
                            items: [{html: '<label style="display:block; margin-bottom: 5px">' + this.app.i18n._('Select Days') + '</label>'}, this.datePicker]
                        }]
                    }/*, {
                        xtype: 'fieldset',
                        padding: '10px',
                        autoHeight: true,
                        title: this.app.i18n._('Dates'),
                        items: [ this.datePicker ]
                       }*/
                    ]
                }, {
                    // activities and tags
                    layout: 'accordion',
                    animate: true,
                    region: 'east',
                    width: 210,
                    split: true,
                    collapsible: true,
                    collapseMode: 'mini',
                    header: false,
                    margins: '0 5 0 5',
                    border: true,
                    items: [
                        new Ext.Panel({
                            title: this.app.i18n._('Description'),
                            iconCls: 'descriptionIcon',
                            layout: 'form',
                            labelAlign: 'top',
                            border: false,
                            items: [{
                                style: 'margin-top: -4px; border 0px;',
                                labelSeparator: '',
                                xtype: 'textarea',
                                name: 'description',
                                hideLabel: true,
                                grow: false,
                                preventScrollbars: false,
                                anchor: '100% 100%',
                                emptyText: this.app.i18n._('Enter description'),
                                requiredGrant: 'editGrant'
                            }]
                        }),
                        new Tine.widgets.activities.ActivitiesPanel({
                            app: 'HumanResources',
                            showAddNoteForm: false,
                            border: false,
                            bodyStyle: 'border:1px solid #B5B8C8;'
                        }),
                        new Tine.widgets.tags.TagPanel({
                            app: 'HumanResources',
                            border: false,
                            bodyStyle: 'border:1px solid #B5B8C8;'
                        })
                    ]
                }]
            }, 
            new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
                }) 
            ]
        };
    }
});

/**
 * HumanResources Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.HumanResources.FreeTimeEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 570,
        name: Tine.HumanResources.FreeTimeEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.HumanResources.FreeTimeEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
