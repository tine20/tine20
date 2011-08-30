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
    
    definitionFields: ['label'],
    uiconfigFields: ['xtype', 'order', 'group'],
    
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
        
        Ext.each(this.definitionFields, function (name) {
        	this.record.data.definition[name] = this.getForm().findField(name).getValue();
        }, this);
        
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
		switch (combo.getValue()) 
		{
		case 'textfield' :
			this.getForm().findField('length').enable();
			break;
		default :
			this.getForm().findField('length').disable();
			break;
		}
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
               		xtype: 'combo',
                    readOnly: this.record.id != 0,
                    store: [
                    	['textfield', this.app.i18n._('Text')],
						['numberfield', this.app.i18n._('Number')],
						['datefield', this.app.i18n._('Date')],
						['timefield', this.app.i18n._('Time')]
                    ],
                    name: 'xtype',
                    fieldLabel: this.app.i18n._('Type'),
                    mode: 'local',
                    allowBlank: false,
                    editable: false,
                    forceSelection: true,
                    listeners: {
						scope: this,
						'select': this.onTypeComboSelect
					}
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
					disabled: this.record.id != 0 && this.record.get('definition').uiconfig.xtype === 'textfield' ? false : true
				}]
            }, {
            	xtype: 'fieldset',
            	bodyStyle: 'padding: 5px',
            	margins: {top: 5, right: 0, bottom: 0, left: 0},
            	collapsible: true,
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
