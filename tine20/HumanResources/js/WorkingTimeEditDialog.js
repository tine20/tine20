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
 * @class       Tine.HumanResources.WorkingTimeEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>WorkingTime Compose Dialog</p>
 * <p></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.HumanResources.WorkingTimeEditDialog
 */
Tine.HumanResources.WorkingTimeEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'WorkingTimeEditWindow_',
    appName: 'HumanResources',
    recordClass: Tine.HumanResources.Model.WorkingTime,
    recordProxy: Tine.HumanResources.WorkingTimeBackend,
    tbarItems: [],
    evalGrants: false,
    mode : 'local',
    showContainerSelector: false,
    
    /**
     * The contract
     * 
     * @type {String}
     */
    contract: null,

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
        this.contract = Ext.decode(this.contract);
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
        Tine.HumanResources.WorkingTimeEditDialog.superclass.onRecordLoad.call(this);
        
        this.window.setTitle(String.format(this.app.i18n._('Edit working time for {0} ({1} - {2})'), Ext.util.Format.htmlEncode(this.employeeName), Tine.Tinebase.common.dateRenderer(this.contract.start_date), Tine.Tinebase.common.dateRenderer(this.contract.end_date)));
        
        var jsonData = this.contract.workingtime_json ? Ext.decode(this.contract.workingtime_json) : Ext.decode(this.record.get('json')), 
            days = jsonData.days,
            form = this.getForm(),
            sum = 0.0;
            
        for (var index = 0; index < 7; index++) {
            form.findField('weekdays_' + index).setValue(days[index]);
            sum = sum + parseFloat(days[index]);
        }
        
        this.getForm().findField('working_hours').setValue(sum);
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
                title: this.app.i18n._('Working Time'),
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
                        title: this.app.i18n._('Working Time'),
                        items: [{
                            xtype: 'columnform',
                            labelAlign: 'top',
                            formDefaults: {
                                xtype:'textfield',
                                anchor: '100%',
                                labelSeparator: '',
                                allowBlank: false,
                                columnWidth: .45
                                
                            },
                            items: [[
                                Tine.widgets.form.RecordPickerManager.get('HumanResources', 'WorkingTime', {
                                    value: this.record,
                                    fieldLabel: this.app.i18n._('Choose the template'),
                                    selectedRecord: this.record,
                                    ref: '../../../../../../../templateChooser',
                                    listeners: {
                                        scope:  this,
                                        select: this.updateTemplate.createDelegate(this)
                                    }
                                }), {
                                    fieldLabel: this.app.i18n._('Working Hours per week'),
                                    xtype: 'numberfield',
                                    decimalPrecision: 2,
                                    decimalSeparator: Tine.Tinebase.registry.get('decimalSeparator'),
                                    name: 'working_hours',
                                    readOnly: true
                                }, {
                                   columnWidth: .1,
                                   xtype:'button',
                                   iconCls: 'HumanResourcesWorkingTimeFormButton',
                                   tooltip: Tine.Tinebase.common.doubleEncode(this.app.i18n._('Save working time as template')),
                                   fieldLabel: '&nbsp;',
                                   listeners: {
                                        scope: this,
                                        click: this.onTemplateCreate
                                   }
                                }
                            ]]
                        }]
                    }, {
                        xtype: 'fieldset',
                        autoHeight: true,
                        title: this.app.i18n._('Working Hours per weekday'),
                        items: [{
                            xtype: 'columnform',
                            labelAlign: 'top',
                            formDefaults: {
                                xtype: 'numberfield',
                                decimalPrecision: 2,
                                decimalSeparator: Tine.Tinebase.registry.get('decimalSeparator'),
                                anchor: '100%',
                                labelSeparator: '',
                                allowBlank: false,
                                columnWidth: 1/7,
                                listeners: {
                                    scope:  this,
                                    change: this.updateWorkingHours.createDelegate(this)
                                }
                            },
                            items: [
                                [{
                                    fieldLabel: _('Mon.'),
                                    name: 'weekdays_0'
                                }, {
                                    fieldLabel: _('Tue.'),
                                    name: 'weekdays_1'
                                }, {
                                    fieldLabel: _('Wed.'),
                                    name: 'weekdays_2'
                                }, {
                                    fieldLabel: _('Thu.'),
                                    name: 'weekdays_3'
                                }, {
                                    fieldLabel: _('Fri.'),
                                    name: 'weekdays_4'
                                }, {
                                    fieldLabel: _('Sat.'),
                                    name: 'weekdays_5'
                                }, {
                                    fieldLabel: _('Sun.'),
                                    name: 'weekdays_6'
                                }]
                            ]
                        }]
                    }]
                }]
            }]
        };
    },
    
    updateWorkingHours: function(formField, newValue, oldValue) {
        var sum = this.getForm().findField('working_hours').getValue();
        this.getForm().findField('working_hours').setValue(sum - oldValue + newValue);
    },
    
    updateTemplate: function(combo, record, index) {
        this.record = record;
        this.onRecordLoad();
    },
    
    onSaveAndClose: function() {
        this.loadMask.show();
        this.window.fireEvent('saveAndClose', this.getJson(), Ext.encode(this.record.data));
    },
    
    onTemplateCreate: function() {
        Ext.Msg.prompt(this.app.i18n._('Save working time as template'), this.app.i18n._('Please enter the title of the template'), function(button, title) {
            if (button != 'ok') return;
            
            if(Ext.isEmpty(title)) {
                Ext.Msg.show({
                   title:   this.app.i18n._('Empty Title'),
                   msg:     this.app.i18n._("The title can't be empty!"),
                   icon:    Ext.MessageBox.ERROR,
                   buttons: Ext.Msg.OK
                });
                return;
            }
            
            this.loadMask.show();
            
            var newTemplate = new Tine.HumanResources.Model.WorkingTime({
                title: title,
                type: 'static',
                json: this.getJson(),
                working_hours: this.getForm().findField('working_hours').getValue()
            });
            
            this.recordProxy.saveRecord(newTemplate, {
                scope: this,
                success: this.onAfterTemplateCreate,
                failure: this.handleRequestException
            });
        }, this);
    },
    
    /**
     * is called after a template has been successfully created
     * 
     * @param {Tine.HumanResources.Model.WorkingTime} record
     */
    onAfterTemplateCreate: function(record) {
        this.record = record;
        this.contract.workingtime_json = null;
        this.contract.workingtime_id = record.data;
        this.templateChooser.selectedRecord = record;
        this.templateChooser.setValue(record.get('title'));
        this.onRecordLoad();
        this.loadMask.hide();
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
            days[index] = values['weekdays_' + index];
        }
        
        return Ext.encode({days: days});
    },
    
    /**
     * Exception handler for template saving
     * 
     * @param {} exception
     * @param {} callback
     * @param {} callbackScope
     */
    handleRequestException: function(exception, callback, callbackScope) {
        switch(exception.code) {
            case 629: // duplicate title
                Ext.Msg.show({
                   title:   this.app.i18n._('Duplicate Title'),
                   msg:     this.app.i18n._('The title you have chosen is already in use. Please try another one!'),
                   icon:    Ext.MessageBox.ERROR,
                   buttons: Ext.Msg.OK
                });
                break;
            default:
                Tine.Tinebase.ExceptionHandler.handleRequestException(exception, callback, callbackScope);
                break;
        }
        this.loadMask.hide();
    }
});

/**
 * HumanResources Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.HumanResources.WorkingTimeEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 400,
        height: 320,
        name: Tine.HumanResources.WorkingTimeEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.HumanResources.WorkingTimeEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
