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
        this.app = Tine.Tinebase.appMgr.get('HumanResources')
        this.dayLengths = [
            [0.25, 0.25],
            [0.5, 0.5],
            [0.75, 0.75],
            [1, 1]
            ];

        this.initDatePicker();
        this.showPrivateInformation = (Tine.Tinebase.common.hasRight('edit_private','HumanResources')) ? true : false;
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
        
        if(this.record.get('employee_id')) {
            this.employeePicker.selectedRecord = new Tine.HumanResources.Model.Employee(this.record.get('employee_id'));
        }
        this.firstDayLengthPicker.setValue(this.datePicker.store.getFirstDay() ? this.datePicker.store.getFirstDay().get('duration') : 1);
        this.lastDayLengthPicker.setValue(this.datePicker.store.getLastDay() ? this.datePicker.store.getLastDay().get('duration') : 1);
        
        Tine.HumanResources.FreeTimeEditDialog.superclass.onRecordLoad.call(this);
        
        this.initStatusBox();
    },

    /**
     * executed when record gets updated from form
     * @private
     */
    onRecordUpdate: function() {
        Tine.HumanResources.FreeTimeEditDialog.superclass.onRecordUpdate.call(this);
        this.record.set('freedays', this.datePicker.getData());
        var fieldName = (this.record.get('type') == 'SICKNESS') ? 'sicknessStatus' : 'vacationStatus';
        this.record.set('status', this.getForm().findField(fieldName).getValue());
    },
    
    /**
     * creates the date picker
     */
    initDatePicker: function() {
        this.datePicker = new Tine.HumanResources.DatePicker({disabled: true, initDate: this.record.get('firstday_date'), app: this.app, record: this.record, recordClass: this.recordClass, editDialog: this, dateProperty: 'date', recordsProperty: 'freedays', foreignIdProperty: 'freeday_id'});
    },
    
    /**
     * validates day length
     * 
     * @param {Float/Integer} value
     * @return {Boolean}
     */
    isDayLengthValid: function(value) {
        return (value <= 1 && value >=0.25);
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
        
        this.employeePicker = Tine.widgets.form.RecordPickerManager.get('HumanResources', 'Employee', {
            fieldLabel: this.app.i18n._('Employee'),
            name: 'employee_id',
            app: this
        });
        
        this.employeePicker.on('select', function(){
            this.contractPicker.enable();
            this.datePicker.loadFeastDays(this.employeePicker.selectedRecord);
        }, this);
        
        var that = this;
        
        this.contractPicker = Tine.widgets.form.RecordPickerManager.get('HumanResources', 'Contract', {
            fieldLabel: this.app.i18n.ngettext('Contract', 'Contracts', 1),
            app: this,
            disabled: true,
            onBeforeQuery: function(qevent) {
                this.store.baseParams.filter = [
                    {field: 'query', operator: 'contains', value: qevent.query }
                ];
                this.store.removeAll();
                this.store.baseParams.filter.push({field: 'employee_id', operator: 'equals', value: that.employeePicker.selectedRecord.get('id')})
            }
        });
        
        this.contractPicker.on('select', function() {
            that.datePicker.loadFeastDays(that.employeePicker.selectedRecord, this.selectedRecord);
            return true;
        });
        
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
                            style: { 'float': 'left', width: '50%', 'min-width': '250px' },
                            labelAlign: 'top',
                            formDefaults: {
                                xtype:'textfield',
                                anchor: '100%',
                                labelSeparator: '',
                                allowBlank: false,
                                columnWidth: 1
                            },
                            items: [
                                [this.employeePicker],[this.contractPicker],[
                                {   xtype: 'widget-keyfieldcombo',
                                    app: 'HumanResources',
                                    keyFieldName: 'freetimeType',
                                    fieldLabel: this.app.i18n._('Type'),
                                    value: 'VACATION',
                                    name: 'type',
                                    columnWidth: .5,
                                    ref: '../../../../../../../typePicker',
                                    listeners: {
                                        scope: this,
                                        select: this.updateStatusBox.createDelegate(this)
                                    }
                                }, {
                                    xtype: 'panel',
                                    layout: 'card',
                                    activeItem: 1,
                                    ref: '../../../../../../../statusBoxWrap',
                                    columnWidth: .5,
                                    fieldLabel: this.app.i18n._('Status'),
                                    items: [{
                                        xtype: 'widget-keyfieldcombo',
                                        app: 'HumanResources',
                                        keyFieldName: 'sicknessStatus',
                                        value: 'EXCUSED',
                                        name: 'sicknessStatus'
                                    }, {
                                        xtype: 'widget-keyfieldcombo',
                                        app: 'HumanResources',
                                        keyFieldName: 'vacationStatus',
                                        value: 'REQUESTED',
                                        name: 'vacationStatus'
                                    }
                                    ]
                                }],
                                [{
                                    fieldLabel: this.app.i18n._('First Day Length'),
                                    xtype: 'combo',
                                    store: this.dayLengths,
                                    value: 1,
                                    columnWidth: .5,
                                    ref: '../../../../../../../firstDayLengthPicker',
                                    validator: this.isDayLengthValid
                                },{
                                    fieldLabel: this.app.i18n._('Last Day Length'),
                                    xtype: 'combo',
                                    store: this.dayLengths,
                                    value: 1,
                                    columnWidth: .5,
                                    ref: '../../../../../../../lastDayLengthPicker',
                                    validator: this.isDayLengthValid
                                }],[{
                                    fieldLabel: this.app.i18n._('Remark'),
                                    name: 'remark'
                                }]
                                ]
                        }, {
                            xtype: 'panel',
                            cls: 'HumanResources x-form-item',
                            width: 220,
                            style: {
                                'float': 'right',
                                margin: '0 5px 10px 0'
                            },
                            items: [{html: '<label style="display:block; margin-bottom: 5px">' + this.app.i18n._('Select Days') + '</label>'}, this.datePicker]
                        }]
                    }]
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
    },
    
    /**
     * updates the statusbox wrap
     * 
     * @param {Tine.Tinebase.widgets.keyfield.ComboBox} the calling combo
     * @param {Tine.Tinebase.data.Record} the selected record
     * @param {Integer} the index of the selected value of the typecombo store
     */
    updateStatusBox: function(typeCombo, keyfieldRecord, index) {
        this.statusBoxWrap.layout.setActiveItem(index);
    },
    
    /**
     * initializes the status box
     */
    initStatusBox: function() {
        var isSickness = this.typePicker.value == 'SICKNESS';
        this.updateStatusBox(null, null, isSickness ? 0 : 1);
        var fieldName = isSickness ? 'sicknessStatus' : 'vacationStatus';
        this.getForm().findField(fieldName).setValue(this.record.get('status'));
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
