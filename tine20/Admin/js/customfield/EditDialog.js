/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
/*global Ext, Tine*/

Ext.ns('Tine.Admin.customfield');

/**
 * @namespace   Tine.Admin.customfield
 * @class       Tine.Admin.CustomfieldEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Customfield Edit Dialog</p>
 * <p>
 * </p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Admin.CustomfieldEditDialog
 * 
 */
Tine.Admin.CustomfieldEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'customfieldEditWindow_',
    appName: 'Admin',
    recordClass: Tine.Admin.Model.Customfield,
    recordProxy: Tine.Admin.customfieldBackend,
    evalGrants: false,
    
    /**
     * definition properties for cusomfield
     * @type {Array}
     */
    definitionFields: ['label', 'type', 'value_search', 'length', 'required'],
    
    /**
     * ui properties for customfield
     * @type {Array}
     */
    uiconfigFields: ['order', 'group'],
    
    /**
     * type of field with stores
     * @type {Array}
     */
    fieldsWithStore: ['record', 'keyField'],
    
    /**
     * currently selected field type
     * @type 
     */
    fieldType: null,
        
    /**
     * executed after record got updated from proxy
     *  - load values for customfield definition and ui
     */
    onRecordLoad: function () {
        Tine.Admin.CustomfieldEditDialog.superclass.onRecordLoad.apply(this, arguments);
        
        // get definition
        if (this.rendered && this.record.get('definition')) {
            this.fieldType = this.record.get('definition').type;
            
            // load definition values
            Ext.each(this.definitionFields, function (name) {
                this.getForm().findField(name).setValue(this.record.get('definition')[name]);
            }, this);
            
            // load ui config values
            Ext.each(this.uiconfigFields, function (name) {
                this.getForm().findField(name).setValue(this.record.get('definition').uiconfig[name]);
            }, this);
            
            // load specific values for fields with store
            if (this.record.get('definition')[this.fieldType + 'Config']) {
                this[this.fieldType + 'Config'] = this.record.get('definition')[this.fieldType + 'Config'];
            }
        }
    },    
    
    /**
     * executed when record gets updated from form
     *  - get values for customfield definition and ui
     */
    onRecordUpdate: function () {
        Tine.Admin.CustomfieldEditDialog.superclass.onRecordUpdate.apply(this, arguments);
        
        // field definition
        this.record.data.definition = {
            uiconfig: {}
        };
        
        // save definition
        Ext.each(this.definitionFields, function (name) {
            var field = this.getForm().findField(name);
            
            if (! field.disabled && (name !== 'value_search' || (name === 'value_search' && field.getValue() == 1))) {
                this.record.data.definition[name] = field.getValue();
            }
        }, this);
        
        // save ui config
        Ext.each(this.uiconfigFields, function (name) {
            this.record.data.definition.uiconfig[name] = this.getForm().findField(name).getValue();;
        }, this);
        
        // set specific values for fields with stores
        Ext.each(this.fieldsWithStore, function (field) {
            if (this.fieldsWithStore.indexOf(this.fieldType) !== -1 && this[field + 'Config']) {
                this.record.data.definition[field + 'Config'] = this[field + 'Config'];
            }
        }, this);
    },
    
    /**
     * Apply changes handler
     *  - validate some additional data before saving
     * 
     * @param {Ext.Button} button
     * @param {Ext.EventObject} event
     * @param {Boolean} closeWindow
     */
    onApplyChanges: function () {
        if (this.fieldsWithStore.indexOf(this.fieldType) !== -1 && ! this[this.fieldType + 'Config']) {
            Ext.Msg.alert(_('Errors'), this.app.i18n._('Please configure store for this field type'));
            return;
        }
        
        Tine.Admin.CustomfieldEditDialog.superclass.onApplyChanges.apply(this, arguments);
    },
    
    /**
     * Called on type combo select
     * 
     * @param {Ext.form.Combobox}   combo
     * @param {Ext.data.Recod}      record
     * @param {Int}                 index
     */
    onTypeComboSelect: function (combo, record, index) {
        this.fieldType = combo.getValue();
        
        this.getForm().findField('length').setDisabled(this.fieldType !== 'string');
        this.getForm().findField('value_search').setValue(this.fieldType === 'searchcombo' ? 1 : 0);
        this.configureStoreBtn.setDisabled(this.fieldsWithStore.indexOf(this.fieldType) === -1);
    },
    
    /**
     * Set field with store config
     */ 
    onStoreWindowOK: function () {
        if (this[this.fieldType + 'Store'].isValid()) {
            this[this.fieldType + 'Config'] = {
                value: {
                    records: this[this.fieldType + 'Store'].getValue()      
                }
            };
            
            // set default if defined for keyField
            if (this.fieldType === 'keyField') {
                var defaultRecIdx = this[this.fieldType + 'Store'].store.findExact('default', true);
                if (defaultRecIdx !== -1) {
                    this[this.fieldType + 'Config'].value['default'] = this[this.fieldType + 'Store'].store.getAt(defaultRecIdx).get('id');
                }
            }
             
            this.onStoreWindowClose();
        }
    },
    
    /**
     * Close store Window
     */
    onStoreWindowClose: function () {
        this.storeWindow.purgeListeners();
        this.storeWindow.close();
    },
    
    /**
     * Get available model for given application
     * 
     *  @param {Mixed} application
     *  @param {Boolean} customFieldModel
     */
    getApplicationModels: function (application, customFieldModel) {
        var models      = [],
            useModel,
            appName     = Ext.isString(application) ? application : application.get('name'),
            app         = Tine.Tinebase.appMgr.get(appName),
            trans       = app && app.i18n ? app.i18n : Tine.Tinebase.translation,
            appModels   = Tine[appName].Model;
        
        if (appModels) {
            for (var model in appModels) {
                if (appModels.hasOwnProperty(model) && typeof appModels[model].getMeta === 'function') {
                    if (customFieldModel && appModels[model].getField('customfields')) {
                        useModel = appModels[model].getMeta('appName') + '_Model_' + appModels[model].getMeta('modelName');
                        
                        Tine.log.info('Found model with customfields property: ' + useModel);
                        models.push([useModel, trans.n_(appModels[model].getMeta('recordName'), appModels[model].getMeta('recordsName'), 1)]);
                    }
                    else if (! customFieldModel) {
                        useModel = 'Tine.' + appModels[model].getMeta('appName') + '.Model.' + appModels[model].getMeta('modelName');
                        
                        Tine.log.info('Found model: ' + useModel);
                        models.push([useModel, trans.n_(appModels[model].getMeta('recordName'), appModels[model].getMeta('recordsName'), 1)]);
                    }
                }
            }
        }
        
        return models;
    },
    
    /**
     * Create store for keyField
     * 
     * @returns {Tine.widgets.grid.QuickaddGridPanel}
     */
    initKeyFieldStore: function () {
        Tine.log.info('Initialize keyField store config');
        
        var self = this,
            
            storeEntry = Ext.data.Record.create([
                {name: 'default'},
                {name: 'id'},
                {name: 'value'}
            ]),
        
            defaultCheck = new Ext.ux.grid.CheckColumn({
                id: 'default', 
                header: self.app.i18n._('Default'),
                dataIndex: 'default',
                sortable: false,
                align: 'center',
                width: 55
            });
            
        this[this.fieldType + 'Store'] = new Tine.widgets.grid.QuickaddGridPanel({
            autoExpandColumn: 'value',
            quickaddMandatory: 'id',
            resetAllOnNew: true,
            useBBar: true,
            border: false,
            recordClass: storeEntry,
            store: new Ext.data.Store({
                reader: new Ext.data.ArrayReader({idIndex: 0}, storeEntry),
                listeners: {
                    scope: this,
                    'update': function (store, rec, operation) {
                        rec.commit(true);
                        
                        // be sure that only one default is checked
                        if (rec.get('default')) {
                            store.each(function (r) {
                                if (r.id !== rec.id) {
                                    r.set('default', false);
                                    r.commit(true);
                                }
                            }, this);
                        }                       
                    }
                }
            }),
            plugins: [defaultCheck],
            getColumnModel: function () {
                return new Ext.grid.ColumnModel([
                defaultCheck,
                {
                    id: 'id', 
                    header: self.app.i18n._('ID'), 
                    dataIndex: 'id', 
                    hideable: false, 
                    sortable: false, 
                    quickaddField: new Ext.form.TextField({
                        emptyText: self.app.i18n._('Add a New ID...')
                    })
                }, {
                    id: 'value', 
                    header: self.app.i18n._('Value'), 
                    dataIndex: 'value', 
                    hideable: false, 
                    sortable: false, 
                    quickaddField: new Ext.form.TextField({
                        emptyText: self.app.i18n._('Add a New Value...')
                    })
                }]);
            },
            /**
             * Do some checking on new entry add
             */
            onNewentry: function (recordData) {
                // check if id exists in grid
                if (this.store.findExact('id', recordData.id) !== -1) {
                    Ext.Msg.alert(self.app.i18n._('Error'), self.app.i18n._('ID already exists'));
                    return false;
                }
                    
                // check if value exists in grid
                if (this.store.findExact('value', recordData.value) !== -1) {
                    Ext.Msg.alert(self.app.i18n._('Error'), self.app.i18n._('Value already exists'));
                    return false;
                }
                
                // if value is empty, set it to ID
                if (Ext.isEmpty(recordData.value)) {
                    recordData.value = recordData.id;
                }
                
                Tine.widgets.grid.QuickaddGridPanel.prototype.onNewentry.apply(this, arguments);
            },
            setValue: function (data) {
                this.setStoreFromArray(data);
            },
            getValue: function () {
                var data = [];
                this.store.each(function (rec) {
                    data.push({
                        id: rec.get('id'),
                        value: rec.get('value')
                    });
                });
                
                return data;
            },
            isValid: function () {
                return true;
            }
        });
        
        /**
         * Load values if exists in field definition
         */
        var configValue = ! Ext.isEmpty(this.record.get('definition')) && this.record.get('definition')[this.fieldType + 'Config'] 
            ? this.record.get('definition')[this.fieldType + 'Config'].value 
            : null;
            
        if (this.record.id != 0 && configValue) {
            this[this.fieldType + 'Store'].setValue(configValue.records);
            
            // if there is default value check it
            if (configValue['default']) {
                var defaultRecIdx = this[this.fieldType + 'Store'].store.findExact('id', configValue['default']);
                if (defaultRecIdx !== -1) {
                    this[this.fieldType + 'Store'].store.getAt(defaultRecIdx).set('default', true);
                }
            }
        }
        
        return this[this.fieldType + 'Store'];
    },
    
    /**
     * Create store for record field
     * 
     * @returns {Ext.Panel}
     */
    initRecordStore: function () {
        Tine.log.info('Initialize record store config');
        
        var self = this;
               
        this[this.fieldType + 'Store'] = new Ext.FormPanel({
            labelAlign: 'top',
            frame: true,
            border: false,
            defaults: {anchor: '100%'},
            bodyStyle: 'padding: 15px',
            items:[{
                xtype: 'combo',
                store: this.appStore,
                name: 'application_id',
                displayField: 'name',
                valueField: 'id',
                fieldLabel: this.app.i18n._('Application'),
                mode: 'local',
                forceSelection: true,
                editable: false,
                listeners: {
                    scope: this,
                    'select': function (combo, rec) {
                        // load combo with found models for selected application
                        this[this.fieldType + 'Store'].items.get(1).store.loadData(this.getApplicationModels(rec, false));
                    }
                }
            }, {
                xtype: 'combo',
                store: new Ext.data.ArrayStore({
                    idIndex: 0,  
                    fields: ['recordClass', 'recordName']
                }),
                name: 'recordClass',
                displayField: 'recordName',
                valueField: 'recordClass',
                fieldLabel: this.app.i18n._('Record Class'),
                mode: 'local',
                forceSelection: false,
                editable: true,
                allowBlank: false
            }],
            setValue: function (data) {
                var parts = data.split('.'), // e.g Tine.Admin.Model.Group
                    app   = Tine.Tinebase.appMgr.get(parts[1]);
                
                // set value for application combo
                this.items.get(0).setValue(app.id);
                
                // load records based on application combo and set value
                this.items.get(1).store.loadData(self.getApplicationModels(app.appName, false));
                this.items.get(1).setValue(data);
                
            },
            getValue: function () {
                return this.items.get(1).getValue();
            },
            isValid: function () {
                try {
                    var model = eval(this.items.get(1).getValue());
                } catch (e) {
                    Ext.Msg.alert(_('Errors'), self.app.i18n._('Given record class not found'));
                    return false;
                }
                
                return true;
            }
        });
        
        /**
         * Load values if exists in field definition
         */
        var configValue = ! Ext.isEmpty(this.record.get('definition')) && this.record.get('definition')[this.fieldType + 'Config'] 
            ? this.record.get('definition')[this.fieldType + 'Config'].value 
            : null;
            
        if (this.record.id != 0 && configValue) {
            this[this.fieldType + 'Store'].setValue(configValue.records);
        }
        
        return this[this.fieldType + 'Store'];
    },
    
    /**
     * Show window for configuring field store
     */
    showStoreWindow: function () {
        this.storeWindow = Tine.WindowFactory.getWindow({
            modal: true, // this needs to be modal atm, popup does not work due to issues with this in tineInit.js
            width: 500,
            height: 320,
            border: false,
            items: this['init' + (this.fieldType.charAt(0).toUpperCase() + this.fieldType.substr(1)) + 'Store'](),
            fbar: ['->', {
                text: _('OK'),
                minWidth: 70,
                scope: this,
                handler: this.onStoreWindowOK,
                iconCls: 'action_applyChanges'
            }, {
                text: _('Cancel'),
                minWidth: 70,
                scope: this,
                handler: this.onStoreWindowClose,
                iconCls: 'action_cancel'
            }]
        });
    },
    
    /**
     * returns dialog
     */
    getFormItems: function () {
        this.appStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            fields: Tine.Admin.Model.Application
        });
        this.appStore.loadData({
            results:    Tine.Tinebase.registry.get('userApplications'),
            totalcount: Tine.Tinebase.registry.get('userApplications').length
        });
        
        this.modelStore = new Ext.data.ArrayStore({
            idIndex: 0,
            fields: [{name: 'value'}, {name: 'name'}]
        });
        
        this.configureStoreBtn = new Ext.Button({
            columnWidth: 0.333,
            fieldLabel: '&#160;',
            xtype: 'button',
            iconCls: 'admin-node-customfields-store',
            text: this.app.i18n._('Configure store'),
            disabled: this.record.id == 0 || this.fieldsWithStore.indexOf(this.record.get('definition').type) === -1,
            scope: this,
            handler: this.showStoreWindow
        });
        
        return {
            layout: 'vbox',
            layoutConfig: {
                align: 'stretch',
                pack: 'start'
            },
            border: false,
            items: [{
                xtype: 'columnform',
                border: false,
                autoHeight: true,
                items: [[{
                    xtype: 'combo',
                    readOnly: this.record.id != 0,
                    store: this.appStore,
                    columnWidth: 0.5,
                    name: 'application_id',
                    displayField: 'name',
                    valueField: 'id',
                    fieldLabel: this.app.i18n._('Application'),
                    mode: 'local',
                    anchor: '100%',
                    allowBlank: false,
                    forceSelection: true,
                    editable: false,
                    listeners: {
                        scope: this,
                        'select': function (combo, rec) {
                            // add models for select application
                            this.modelStore.loadData(this.getApplicationModels(rec, true));
                        }
                    }
                }, {
                    xtype: 'combo',
                    readOnly: this.record.id != 0,
                    store: this.modelStore,
                    columnWidth: 0.5,
                    name: 'model',
                    displayField: 'name',
                    valueField: 'value',
                    fieldLabel: this.app.i18n._('Model'),
                    mode: 'local',
                    anchor: '100%',
                    allowBlank: false,
                    forceSelection: true,
                    editable: false
                }]]
            }, {
                xtype: 'fieldset',
                bodyStyle: 'padding: 5px',
                margins: {top: 5, right: 0, bottom: 0, left: 0},
                title: this.app.i18n._('Custom field definition'),
                labelAlign: 'top',
                defaults: {anchor: '100%'},
                items: [{
                    xtype: 'columnform',
                    border: false,
                    items: [[{
                        columnWidth: 0.666,
                        xtype: 'combo',
                        readOnly: this.record.id != 0,
                        store: [
                            ['string', this.app.i18n._('Text')],
                            ['integer', this.app.i18n._('Number')],
                            ['date', this.app.i18n._('Date')],
                            ['datetime', this.app.i18n._('DateTime')],
                            ['time', this.app.i18n._('Time')],
                            ['boolean', this.app.i18n._('Boolean')],
                            ['searchcombo', this.app.i18n._('Search Combo')],
                            ['keyField', this.app.i18n._('Key Field')],
                            ['record', this.app.i18n._('Record')]
                            
                        ],
                        name: 'type',
                        fieldLabel: this.app.i18n._('Type'),
                        mode: 'local',
                        allowBlank: false,
                        editable: false,
                        forceSelection: true,
                        listeners: {
                            scope: this,
                            'select': this.onTypeComboSelect
                        }
                    }, this.configureStoreBtn]]
                }, {
                    xtype: 'textfield',
                    fieldLabel: this.app.i18n._('Name'), 
                    name: 'name',
                    allowBlank: false,
                    maxLength: 50
                }, {
                    xtype: 'textfield',
                    fieldLabel: this.app.i18n._('Label'), 
                    name: 'label',
                    allowBlank: false,
                    maxLength: 50
                }, {
                    xtype: 'numberfield',
                    fieldLabel: this.app.i18n._('Length'),
                    name: 'length',
                    disabled: this.record.id == 0 || this.record.get('definition').type !== 'string'
                }, {
                    xtype: 'checkbox',
                    fieldLabel: this.app.i18n._('Required'),
                    name: 'required'
                }]
            }, {
                xtype: 'fieldset',
                bodyStyle: 'padding: 5px',
                margins: {top: 5, right: 0, bottom: 0, left: 0},
                title: this.app.i18n._('Custom field additional properties'),
                labelAlign: 'top',
                defaults: {anchor: '100%'},
                items: [{
                    xtype: 'textfield',
                    fieldLabel: this.app.i18n._('Group'), 
                    name: 'group',
                    maxLength: 50
                }, {
                    xtype: 'numberfield',
                    fieldLabel: this.app.i18n._('Order'),
                    name: 'order'
                }]
            }, {
                xtype: 'hidden',
                name: 'value_search',
                value: 0
            }]
        };
    },
    
    /**
     * is form valid?
     * 
     * @return {Boolean}
     */
    isValid: function() {
        var result = Tine.Admin.UserEditDialog.superclass.isValid.call(this);

        if (! this.record.id && this.customFieldExists()) {
            result = false;
            this.getForm().markInvalid([{
                id: 'name',
                msg: this.app.i18n._("Customfield already exists. Please choose another name.")
            }]);
        }
        
        return result;
    },
    
    /**
     * check if customfield name already exists for this app
     * 
     * @return {Boolean}
     */
    customFieldExists: function() {
        var applicationField = this.getForm().findField('application_id'),
            store = applicationField.getStore(),
            app = store.getById(applicationField.getValue()),
            cfName = this.getForm().findField('name').getValue(),
            cfExists = false;
        
        if (app && cfName) {
            var customfieldsOfApp = Tine[app.get('name')].registry.get('customfields');
            Ext.each(customfieldsOfApp, function(cfConfig) {
                if (cfName === cfConfig.name) {
                    cfExists = true;
                }
            }, this);
        }
        
        return cfExists;
    }
});

/**
 * Customfield Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Admin.CustomfieldEditDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 500,
        height: 500,
        name: Tine.Admin.CustomfieldEditDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Admin.CustomfieldEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
