/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
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
    
    definitionFields: ['label', 'type', 'value_search', 'length'],
    uiconfigFields: ['order', 'group'],
    
    keyFieldConfig: null,
    
    /**
     * executed after record got updated from proxy
     */
    onRecordLoad: function () {
        Tine.Admin.CustomfieldEditDialog.superclass.onRecordLoad.apply(this, arguments);
        
        // get definition
        if (this.rendered && this.record.data.definition) {
	        Ext.each(this.definitionFields, function (name) {
	        	this.getForm().findField(name).setValue(this.record.data.definition[name]);
	        }, this);
	        
	        Ext.each(this.uiconfigFields, function (name) {
	        	this.getForm().findField(name).setValue(this.record.data.definition.uiconfig[name]);
	        }, this);
        }
    },    
    
    /**
     * executed when record gets updated from form
     */
    onRecordUpdate: function () {
        Tine.Admin.CustomfieldEditDialog.superclass.onRecordUpdate.apply(this, arguments);
        
        // set definition
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
        
        if (this.keyFieldConfig) {
        	this.record.data.definition.keyFieldConfig = this.keyFieldConfig;
        }
        
        // save ui config
        Ext.each(this.uiconfigFields, function (name) {
        	this.record.data.definition.uiconfig[name] = this.getForm().findField(name).getValue();;
        }, this);
    },
    
    /**
	 * Called on type combo select
	 * 
	 * @param {Ext.form.Combobox}  	combo
	 * @param {Ext.data.Recod}  	record
	 * @param {Int}  				index
	 */
	onTypeComboSelect: function (combo, record, index) {
		var type = Ext.util.Format.lowercase(combo.getValue());
		
		this.getForm().findField('length').setDisabled(type !== 'string');
		this.getForm().findField('value_search').setValue(type === 'searchcombo' ? 1 : 0);
		this.configureStoreBtn.setDisabled(type !== 'keyfield');
	},
    
	/**
	 * Set value
	 * 
	 *  @param {String} type
	 */	
	onStoreWindowOK: function () {
		this.keyFieldConfig = {
			value: {
				records: this.storeWindowGrid.getValue()		
			}
		};
		this.onStoreWindowClose();
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
     *  @param {String} application
     */
    getApplicationModels: function (application) {
    	// remove all current data
    	this.modelStore.removeAll();
    	
    	this.recordProxy.doXHTTPRequest({
            scope: this,
            params: { 
                method: 'Admin.getApplicationModels',
                application: application
            },
            beforeSuccess: function (response) {
                return [Ext.util.JSON.decode(response.responseText)];
            },
            success: function (result) {
                this.modelStore.loadData(result);
            },
            failure: this.onRequestFailed,
            timeout: 3600000 // 60 minutes
        });
    },
    
    /**
	 * Create store + grid
	 * 
	 * @returns {Tine.widgets.grid.QuickaddGridPanel}
	 */
	initStoreWindowGrid: function () {        
		var self = this,
			storeEntry = Ext.data.Record.create([
			    {name: 'id'},
			    {name: 'value'}
			]);
		
    	this.storeWindowGrid = new Tine.widgets.grid.QuickaddGridPanel({
    		autoExpandColumn: 'value',
			quickaddMandatory: 'id',
			resetAllOnNew: true,
			useBBar: true,
			recordClass: storeEntry,
    		getColumnModel: function () {
		        return new Ext.grid.ColumnModel([{ 
		            id: 'id', 
		            header: self.app.i18n._('ID'), 
		            dataIndex: 'id', 
		            width: 170, 
		            hideable: false, 
		            sortable: false, 
		            quickaddField: new Ext.form.TextField({
		                emptyText: self.app.i18n._('Add a New ID...')
		            })
		        }, { 
		            id: 'value', 
		            header: self.app.i18n._('Value'), 
		            dataIndex: 'value', 
		            width: 170, 
		            hideable: false, 
		            sortable: false, 
		            quickaddField: new Ext.form.TextField({
		                emptyText: self.app.i18n._('Add a New Value...')
		            })
		        }]);
		    },
		    onNewentry: function (recordData) {
		    	// check if id exists in grid
				if (this.store.findExact('id', recordData.id) !== -1) {
					Ext.Msg.alert(self.app.i18n._('Error'), self.app.i18n._('ID allready exists'));
					return false;
				}
					
				// check if value exists in grid
				if (this.store.findExact('value', recordData.value) !== -1) {
					Ext.Msg.alert(self.app.i18n._('Error'), self.app.i18n._('Value allready exists'));
					return false;
				}
				
				// if value is empty, set it to ID
				if (Ext.isEmpty(recordData.value)) {
					recordData.value = recordData.id;
				}
		    	
		    	Tine.widgets.grid.QuickaddGridPanel.prototype.onNewentry.apply(this, arguments);
		    },
		    getValue: function () {
		    	var data = [];
		    	this.store.each(function (rec) {
		    		data.push(rec.data);
		    	});
		    	
		    	return data;
		    }
    	});
    	
    	if (this.record.id != 0 && this.record.get('definition').keyFieldConfig.value) {
    		this.storeWindowGrid.setStoreFromArray(this.record.get('definition').keyFieldConfig.value.records);
    	}
		
		return this.storeWindowGrid;
	},
    
    /**
	 * Show window with configuring combobox store
	 * 
	 * @param {String} type
	 */
	showStoreWindow: function (type) {
		this.storeWindow = Tine.WindowFactory.getWindow({
			width: 500,
			height: 320,
			items: this.initStoreWindowGrid(),
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
        
        this.modelStore = new Ext.data.JsonStore({
        	root: 'results',
        	totalProperty: 'totalcount',
            fields: [{name: 'value'}, {name: 'name'}]
        });
        
        this.configureStoreBtn = new Ext.Button({
        	columnWidth: 0.333,
        	fieldLabel: '&#160;',
			xtype: 'button',
			icon: 'images/oxygen/16x16/apps/kexi.png',
			text: this.app.i18n._('Configure store'),
			disabled: this.record.id != 0 && Ext.util.Format.lowercase(this.record.get('definition').type) === 'keyfield' ? false : true,
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
                    listeners: {
                    	scope: this,
                    	'select': function (combo) {
                    		this.getApplicationModels(combo.getValue());
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
							['int', this.app.i18n._('Number')],
							['date', this.app.i18n._('Date')],
							['datetime', this.app.i18n._('DateTime')],
							['time', this.app.i18n._('Time')],
							['boolean', this.app.i18n._('Boolean')],
							['searchcombo', this.app.i18n._('Search Combo')],
							['keyfield', this.app.i18n._('Key Field')]
							
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
					disabled: this.record.id != 0 && Ext.util.Format.lowercase(this.record.get('definition').type) === 'string' ? false : true
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
        width: 450,
        height: 450,
        name: Tine.Admin.CustomfieldEditDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Admin.CustomfieldEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
