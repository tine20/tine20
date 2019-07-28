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
Tine.HumanResources.WorkingTimeEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'WorkingTimeEditWindow_',
    appName: 'HumanResources',
    modelName: 'WorkingTime',
    recordClass: Tine.HumanResources.Model.WorkingTime,
    
    
    
    windowHeight: 500,
    

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
        Tine.HumanResources.WorkingTimeEditDialog.superclass.initComponent.call(this);
    },
    
    /**
     * executed after record got updated from proxy, if json Data is given, it's used
     * 
     * @private
     */
    onRecordLoad: function(jsonData) {
        Tine.HumanResources.WorkingTimeEditDialog.superclass.onRecordLoad.call(this);
        
        var jsonData = jsonData ? Ext.decode(jsonData) : ! Ext.isEmpty(this.record.get('json')) ? Ext.decode(this.record.get('json')) : null;
        if (jsonData) {
            this.applyJsonData(jsonData);
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
        
        this.record.set('json', this.getJson());
        this.record.set('breaks', this.breaksPanel.getData());

    },

    onAfterRecordLoad: function () {
        this.breaksPanel.onRecordLoad(this.record);
        Tine.HumanResources.WorkingTimeEditDialog.superclass.onAfterRecordLoad.call(this);
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
        this.breaksPanel = new Tine.HumanResources.BreakGridPanel({
            app: this.app,
            editDialog: this
        });
        
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
            plain:true,
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }],
            defaults: {
                hideMode: 'offsets'
            },
            activeTab: 0,
            items: [{
            title: this.app.i18n._('Working time Model'),
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
                    title: this.app.i18n._('Working Time'),
                    items: [{
                        xtype: 'columnform',
                        items: [
                            [Tine.widgets.form.FieldManager.get(
                                this.appName,
                                this.modelName,
                                'title',
                                Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG,
                                {
                                    columnWidth: 1
                                }
                            )], [
                            {
                                fieldLabel: this.app.i18n._('Work start'),
                                columnWidth: 1/4,
                                emptyText: this.app.i18n._('not set'),
                                name: 'evaluation_period_start',
                                xtype: 'timefield'
                            }, {
                                fieldLabel: this.app.i18n._('Work end'),
                                columnWidth: 1/4,
                                emptyText: this.app.i18n._('not set'),
                                name: 'evaluation_period_end',
                                xtype: 'timefield'
                            }, {
                                fieldLabel: this.app.i18n._('Working Hours per week'),
                                xtype: 'displayfield',
                                style: { border: 'silver 1px solid', padding: '3px', height: '11px'},
                                minValue: 1,
                                decimalPrecision: 2,
                                decimalSeparator: Tine.Tinebase.registry.get('decimalSeparator'),
                                name: 'working_hours',
                                columnWidth: 2/4
                            }],
                            
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
            },{
                region: 'south',
                layout: 'fit',
                flex: 1,
                height: 300,
                border: false,
                items: [
                    this.breaksPanel
                ]
            }]
        }]
        };
    },
    
    updateTemplate: function(combo, record, index) {
        this.applyJsonData(Ext.decode(record.get('json')));
    }
});

/**
 * Opens a new contact edit dialog window
 *
 * @return {Ext.ux.Window}
 */
Tine.HumanResources.WorkingTimeEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 400,
        height: 500,
        name: Tine.HumanResources.WorkingTimeEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.HumanResources.WorkingTimeEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};