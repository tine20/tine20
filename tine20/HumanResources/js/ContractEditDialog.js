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
    
    windowHeight: 300,
    
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
    onRecordLoad: function(jsonData) {
        // interrupt process flow until dialog is rendered
        if (! this.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }
        
        Tine.HumanResources.ContractEditDialog.superclass.onRecordLoad.call(this);
        
        var jsonData = jsonData ? Ext.decode(jsonData) : ! Ext.isEmpty(this.record.get('workingtime_json')) ? Ext.decode(this.record.get('workingtime_json')) : null;
        if (jsonData) {
            this.applyJsonData(jsonData);
        }
        
        if (! this.record.id) {
            this.getForm().findField('feast_calendar_id').setValue(Tine.HumanResources.registry.get('defaultFeastCalendar'));
        } else {
            this.window.setTitle(String.format(i18n._('Edit {0}'), this.i18nRecordName));
            this.getForm().findField('workingtime_template').setValue(null);
        }
        
        // disable fields if there are already some vacations booked
        // but allow setting end_date
        
        if (this.record.get('creation_time')) {
            if (! this.record.get('is_editable')) {
                this.getForm().items.each(function(formField) {
                    if (formField.name != 'end_date') {
                        formField.disable();
                    }
                }, this);
            }
        }
    },
    
    /**
     * applies the json data to the form
     * 
     * @param {Object} jsonData
     */
    applyJsonData: function(jsonData) {
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
    getJson: function() {
        var values = this.getForm().getFieldValues(),
            days = [];
        
        for (var index = 0; index < 7; index++) {
            days[index] = parseFloat(values['weekdays_' + index]);
        }
        
        return Ext.encode({days: days});
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
        Tine.HumanResources.ContractEditDialog.superclass.onRecordUpdate.call(this);
        
        this.record.set('workingtime_json', this.getJson());
        this.record.set('feast_calendar_id', this.getForm().findField('feast_calendar_id').selectedContainer);
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
        var weekdayFieldDefaults = {
            
            xtype: 'uxspinner',
            strategy: new Ext.ux.form.Spinner.NumberStrategy({
                incrementValue : .5,
                alternateIncrementValue: 1,
                minValue: 0,
                maxValue: 24,
                allowDecimals : true
            }),
            decimalPrecision: 2,
            decimalSeparator: Tine.Tinebase.registry.get('decimalSeparator'),
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
                region: 'center',
                layout: 'hfit',
                border: false,
                items: [{
                    xtype: 'fieldset',
                    layout: 'hfit',
                    autoHeight: true,
                    title: this.app.i18n._('Contract'),
                    items: [{
                        xtype: 'columnform',
                        labelAlign: 'top',
                        items: [[
                            {xtype: 'datefield', name: 'start_date', fieldLabel: this.app.i18n._('Start Date'), allowBlank: false },
                            {xtype: 'extuxclearabledatefield', name: 'end_date',   fieldLabel: this.app.i18n._('End Date')},
                            {
                                xtype: 'tinewidgetscontainerselectcombo', 
                                name: 'feast_calendar_id',
                                containerName: this.app.i18n._('Calendar'),
                                containersName: this.app.i18n._('Calendars'),
                                appName: 'Calendar',
                                requiredGrant: 'readGrant',
                                hideTrigger2: true,
                                allowBlank: false,
                                blurOnSelect: true,
                                fieldLabel: this.app.i18n._('Feast Calendar')
                            }
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
                                Tine.widgets.form.RecordPickerManager.get('HumanResources', 'WorkingTime', {
                                    value: this.record,
                                    fieldLabel: this.app.i18n._('Choose the template'),
                                    selectedRecord: this.record,
                                    ref: '../../../../../../../templateChooser',
                                    columnWidth: 1/3,
                                    name: 'workingtime_template',
                                    listeners: {
                                        scope:  this,
                                        select: this.updateTemplate.createDelegate(this)
                                    }
                                }), {
                                    fieldLabel: this.app.i18n._('Working Hours per week'),
                                    xtype: 'displayfield',
                                    style: { border: 'silver 1px solid', padding: '3px', height: '11px'},
                                    minValue: 1,
                                    decimalPrecision: 2,
                                    decimalSeparator: Tine.Tinebase.registry.get('decimalSeparator'),
                                    name: 'working_hours',
                                    columnWidth: 1/3
                                },{name: 'vacation_days', fieldLabel: this.app.i18n._('Vacation days of one calendar year'), columnWidth: 1/3, allowBlank: false}
                            ],
                            
                            [Ext.apply({
                                fieldLabel: i18n._('Mon.'),
                                name: 'weekdays_0'
                            }, weekdayFieldDefaults), Ext.apply({
                                fieldLabel: i18n._('Tue.'),
                                name: 'weekdays_1'
                            }, weekdayFieldDefaults), Ext.apply({
                                fieldLabel: i18n._('Wed.'),
                                name: 'weekdays_2'
                            }, weekdayFieldDefaults), Ext.apply({
                                fieldLabel: i18n._('Thu.'),
                                name: 'weekdays_3'
                            }, weekdayFieldDefaults), Ext.apply({
                                fieldLabel: i18n._('Fri.'),
                                name: 'weekdays_4'
                            }, weekdayFieldDefaults), Ext.apply({
                                fieldLabel: i18n._('Sat.'),
                                name: 'weekdays_5'
                            }, weekdayFieldDefaults), Ext.apply({
                                fieldLabel: i18n._('Sun.'),
                                name: 'weekdays_6'
                            }, weekdayFieldDefaults)]
                        ]
                    }]
                }]
            }]
        }]
        };
    },
    
    updateTemplate: function(combo, record, index) {
        this.applyJsonData(Ext.decode(record.get('json')));
    }
});
