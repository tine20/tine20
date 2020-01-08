/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

/**
 * @namespace   Tine.HumanResources
 * @class       Tine.HumanResources.WorkingTimeSchemeEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 */
Tine.HumanResources.WorkingTimeSchemeEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'WorkingTimeEditWindow_',
    appName: 'HumanResources',
    modelName: 'WorkingTimeScheme',
    
    
    windowHeight: 500,
    evalGrants: false,
    

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
        this.recordClass = Tine.HumanResources.Model.WorkingTimeScheme;
        Tine.HumanResources.WorkingTimeSchemeEditDialog.superclass.initComponent.call(this);
    },
    
    /**
     * executed after record got updated from proxy, if json Data is given, it's used
     * 
     * @private
     */
    onRecordLoad: function(jsonData) {
        Tine.HumanResources.WorkingTimeSchemeEditDialog.superclass.onRecordLoad.call(this);
        
        var jsonData = jsonData || this.record.get('json') || null;
        if (Ext.isString(jsonData)) {
            jsonData = Ext.decode(jsonData);
        }

        if (jsonData) {
            this.applyJsonData(jsonData);
        }

        var type = this.record.get('type');
        if (type == 'individual') {
            this.setReadOnly(true);
        }

        if (type == 'shared') {
            this.typeCombo.readOnly = true;
        }
    },

    setReadOnly: function(readOnly) {
        this.typeCombo.readOnly = true;
        this.blConfigPanel.setReadOnly(readOnly);
        this.getForm().items.each(function(formField) {
            if (formField.name.match(/^weekdays/)) {
                formField.setDisabled(readOnly);
            }
        }, this);
        this.btnSaveAndClose.setDisabled(readOnly);

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
            sum += this.getForm().findField(('weekdays_' + index)).getValue();
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
        Tine.HumanResources.ContractEditDialog.superclass.onRecordUpdate.call(this);
        
        this.record.set('json', this.getJson());

    },

    onAfterRecordLoad: function () {
        Tine.HumanResources.WorkingTimeSchemeEditDialog.superclass.onAfterRecordLoad.call(this);
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
            title: this.app.i18n._('Working Time Rules')
        });

        var weekdayFieldDefaults = {
            
            xtype: 'durationspinner',
            baseUnit: 'seconds',
            anchor: '100%',
            labelSeparator: '',
            allowBlank: false,
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
            title: this.app.i18n._('Working Time Schema'),
            autoScroll: true,
            border: false,
            frame: true,
            layout: 'border',
            items: [{
                region: 'north',
                height: 130,
                layout: 'hfit',
                border: false,
                items: [this.typeCombo = new Ext.form.ComboBox ({
                    name: 'type',
                    fieldLabel: this.app.i18n._('Type'),
                    store: [
                        ['template', this.app.i18n._('Template (individual working time schemas are created for each contract)')],
                        ['shared', this.app.i18n._('Shared (this working time schemas can be shared among contracts)')]
                    ]
                }),{
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
                                    columnWidth: 2/3
                                }
                            ), {
                                fieldLabel: this.app.i18n._('Working Hours per week'),
                                xtype: 'durationspinner',
                                baseUnit: 'seconds',
                                name: 'working_hours',
                                disabled: true,
                                columnWidth: 1/3
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
                region: 'center',
                layout: 'fit',
                flex: 1,
                border: false,
                items: [
                    this.blConfigPanel
                    // this.breaksPanel
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
Tine.HumanResources.WorkingTimeSchemeEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 600,
        height: 520,
        name: Tine.HumanResources.WorkingTimeSchemeEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.HumanResources.WorkingTimeSchemeEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};