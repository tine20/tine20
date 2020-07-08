/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

/**
 * @namespace   Tine.HumanResources
 * @class       Tine.HumanResources.ContractEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Contract Compose Dialog</p>
 * <p></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.HumanResources.ContractEditDialog
 */
Tine.HumanResources.ContractEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    appName: 'HumanResources',
    evalGrants: false,
    
    windowHeight: 550,
    
    /**
     * just update the contract grid panel, no persisten
     * 
     * @type String
     */
    mode: 'local',
    
    /**
     * The record is editable if the valid interval is in the future or not older than 2 hours
     * This property is set accordingly
     * 
     * @type 
     */
    allowEdit: null,

    displayNotes: true,

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
        Tine.HumanResources.ContractEditDialog.superclass.initComponent.call(this);
    },
    
    /**
     * executed after record got updated from proxy, if json Data is given, it's used
     * 
     * @private
     */
    onRecordLoad: function() {
        // interrupt process flow until dialog is rendered
        if (! this.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }
        
        Tine.HumanResources.ContractEditDialog.superclass.onRecordLoad.call(this);

        if (! this.record.id) {
            this.getForm().findField('feast_calendar_id').setValue(Tine.HumanResources.registry.get('defaultFeastCalendar'));
        } else {
            this.window.setTitle(String.format(i18n._('Edit {0}'), this.i18nRecordName));
        }

        this.applyWorkingTimeSchema(this.record.get('working_time_scheme'));

        // disable fields if there are already some vacations booked
        // but allow setting end_date
        if (this.record.get('creation_time')) {
            if (! this.record.get('is_editable')) {
                this.getForm().items.each(function(formField) {
                    if (formField.name != 'end_date') {
                        formField.setDisabled(true);
                    }
                }, this);

                this.blConfigPanel.setReadOnly(true);
            }
        }
    },

    applyWorkingTimeSchema: function(workingtimeSchema) {
        if (! workingtimeSchema) {
            return;
        }

        var _ = window.lodash,
            type = _.get(workingtimeSchema, 'data.type', _.get(workingtimeSchema, 'type'));

        this.applyJsonData(_.get(workingtimeSchema, 'data.json', _.get(workingtimeSchema, 'json')));
        this.blConfigPanel.onRecordLoad(this, this.record);

        var readOnly = type == 'shared';

        this.blConfigPanel.setReadOnly(readOnly);
        this.getForm().items.each(function(formField) {
            if (formField.name.match(/^weekdays/)) {
                formField.setDisabled(readOnly);
            }
        }, this);
    },

    /**
     * applies the json data to the form
     * 
     * @param {Object} jsonData
     */
    applyJsonData: function(jsonData) {
        jsonData = Ext.isString(jsonData) ? Ext.decode(jsonData) : jsonData;

        var days = jsonData.days,
        form = this.getForm(),
        sum = 0.0;

        for (var index = 0; index < 7; index++) {
            form.findField('weekdays_' + index).setValue(days[index]);
            sum = sum + parseFloat(days[index]);
        }
        
        form.findField('working_hours').setValue(sum);
    },
    
    updateWorkingHours: function(formField, newValue, oldValue) {
        var sum = 0;
        for (var index = 0; index < 7; index++) {
            sum += parseFloat(this.getForm().findField(('weekdays_' + index)).getValue());
        }
        
        this.getForm().findField('working_hours').setValue(sum);
    },

    /**
     * returns appropriate json for the template or the contract
     * 
     * @return {String}
     */
    getJsonData: function() {
        var values = this.getForm().getFieldValues(),
            days = [];
        
        for (var index = 0; index < 7; index++) {
            days[index] = parseFloat(values['weekdays_' + index]);
        }
        
        return {days: days};
    },

    /**
     * closes open subpanels on cancel
     */
    onCancel: function() {
        Tine.HumanResources.ContractEditDialog.superclass.onCancel.call(this);
    },
    
    /**
     * executed when record gets updated from form
     * @private
     */
    onRecordUpdate: function() {
        var _ = window.lodash,
            working_time_scheme = this.record.get('working_time_scheme');

        // NOTE: this reduces working_time_scheme to id...
        Tine.HumanResources.ContractEditDialog.superclass.onRecordUpdate.call(this);
        
        this.record.set('feast_calendar_id', this.getForm().findField('feast_calendar_id').selectedContainer);
        this.record.set('working_time_scheme', working_time_scheme);
        this.blConfigPanel.onRecordUpdate(this, this.record);
        _.set(this.record, 'data.working_time_scheme.json', this.getJsonData());
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
        // blpipes is of type records with subrecord hr.blconfig
        this.blConfigPanel = new Tine.Tinebase.BL.BLConfigPanel({
            app: this.app,
            editDialog: this,
            owningRecordClass: Tine.HumanResources.Model.WorkingTimeScheme,
            dataPath: 'data.working_time_scheme.blpipe',
            title: this.app.i18n._('Working Time Rules')
        });

        var weekdayFieldDefaults = {

            xtype: 'durationspinner',
            baseUnit: 'seconds',
            anchor: '100%',
            labelSeparator: '',
            allowBlank: true,
            columnWidth: 1/7,
            listeners: {
                scope:  this,
                blur: this.updateWorkingHours.createDelegate(this),
                spin: this.updateWorkingHours.createDelegate(this)
            }
        };
        
        return {
            xtype: 'tabpanel',
            border: false,
            plain:true,
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }],
            defaults: {
                hideMode: 'offsets'
            },
            activeTab: 0,
            border: false,
            items: [{
            title: this.app.i18n._('Contract'),
            autoScroll: true,
            border: false,
            frame: true,
            layout: 'border',
            items: [{
                region: 'north',
                layout: 'hfit',
                height: 220,
                border: false,
                items: [{
                    xtype: 'fieldset',
                    layout: 'hfit',
                    autoHeight: true,
                    title: this.app.i18n._('Contract'),
                    items: [{
                        xtype: 'columnform',
                        labelAlign: 'top',
                        defaults: {columnWidth: 1/2},
                        items: [[
                                {xtype: 'datefield', name: 'start_date', fieldLabel: this.app.i18n._('Start Date'), allowBlank: false, columnWidth: 1/2 },
                                {xtype: 'extuxclearabledatefield', name: 'end_date',   fieldLabel: this.app.i18n._('End Date'), columnWidth: 1/2}
                            ], [
                                {
                                    xtype: 'tinewidgetscontainerselectcombo',
                                    name: 'feast_calendar_id',
                                    containerName: this.app.i18n._('Calendar'),
                                    containersName: this.app.i18n._('Calendars'),
                                    recordClass: Tine.Calendar.Model.Event,
                                    requiredGrant: 'readGrant',
                                    hideTrigger2: true,
                                    allowBlank: false,
                                    blurOnSelect: true,
                                    columnWidth: 1/2,
                                    fieldLabel: this.app.i18n._('Public Holiday Calendar')
                                },
                                {name: 'vacation_days', fieldLabel: this.app.i18n._('Vacation days of one calendar year'), allowBlank: false, columnWidth: 1/2}
                        ]]
                    }]
                }, {
                    xtype: 'fieldset',
                    layout: 'hfit',
                    autoHeight: true,
                    title: this.app.i18n._('Working Time'),
                    items: [{
                        xtype: 'columnform',
                        labelAlign: 'top',
                        items: [
                        [
                                Tine.widgets.form.RecordPickerManager.get('HumanResources', 'WorkingTimeScheme', {
                                    value: this.record,
                                    fieldLabel: this.app.i18n._('Working Time Schema'),
                                    selectedRecord: this.record,
                                    ref: '../../../../../../../templateChooser',
                                    columnWidth: 2/3,
                                    name: 'working_time_scheme',
                                    listeners: {
                                        scope:  this,
                                        select: this.onWorkingtimeSchemaSelect.createDelegate(this)
                                    }
                                }), {
                                    fieldLabel: this.app.i18n._('Working Hours per week'),
                                    xtype: 'durationspinner',
                                    baseUnit: 'seconds',
                                    disabled: true,
                                    name: 'working_hours',
                                    columnWidth: 1/3
                                }
                            ],
                            
                            [Ext.apply({
                                fieldLabel: this.app.i18n._('Mon.'),
                                name: 'weekdays_0'
                            }, weekdayFieldDefaults), Ext.apply({
                                fieldLabel: this.app.i18n._('Tue.'),
                                name: 'weekdays_1'
                            }, weekdayFieldDefaults), Ext.apply({
                                fieldLabel: this.app.i18n._('Wed.'),
                                name: 'weekdays_2'
                            }, weekdayFieldDefaults), Ext.apply({
                                fieldLabel: this.app.i18n._('Thu.'),
                                name: 'weekdays_3'
                            }, weekdayFieldDefaults), Ext.apply({
                                fieldLabel: this.app.i18n._('Fri.'),
                                name: 'weekdays_4'
                            }, weekdayFieldDefaults), Ext.apply({
                                fieldLabel: this.app.i18n._('Sat.'),
                                name: 'weekdays_5'
                            }, weekdayFieldDefaults), Ext.apply({
                                fieldLabel: this.app.i18n._('Sun.'),
                                name: 'weekdays_6'
                            }, weekdayFieldDefaults)]
                        ]
                    }]
                }]
            }, {
                region: 'center',
                layout: 'fit',
                flex: 1,
                border: false,
                items: [
                    this.blConfigPanel
                ]
            }]
        }]
        };
    },
    
    onWorkingtimeSchemaSelect: function(combo, record, index) {
        this.record.set('working_time_scheme', record.data);
        this.applyWorkingTimeSchema(record);
    }
});
