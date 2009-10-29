/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  widgets
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * TODO         replace this with new account picker / search combo (Tine.widgets.account.PickerGridPanel)
 * @deprecated
 */

Ext.namespace('Tine.widgets');

/**
 * Account picker widget
 * @class Tine.widgets.AccountpickerField
 * @package Tinebase
 * @subpackage Widgets
 * @extends Ext.form.TwinTriggerField
 * 
 * <p> This widget supplies a generic account picker field. When the field is
 triggered a {Tine.widgets.AccountpickerDialog} is showen, to select a account. </p>
 */
Tine.widgets.AccountpickerField = Ext.extend(Ext.form.TwinTriggerField, {
	/**
     * @cfg {bool}
     * selectOnFocus
     */
	selectOnFocus: true,
	
    /**
     * @property {Ext.data.Record} account
     */
    account: null,
    
    
    /**
     * @private
     */
    trigger2Class: 'x-form-account-trigger',
	allowBlank: true,
	editable: false,
    readOnly:true,
	triggerAction: 'all',
	typeAhead: true,
	trigger1Class:'x-form-clear-trigger',
	hideTrigger1:true,
	accountId: null,
	
	//private
    initComponent: function(){
	    Tine.widgets.AccountpickerField.superclass.initComponent.call(this);
		
        this.emptyText = _('nobody');
		if (this.selectOnFocus) {
			this.on('focus', function(){
				return this.onTrigger2Click();
			}, this);
		}
		
		this.onTrigger2Click = function(e) {
            if (! this.disabled) {
                this.dlg = new Tine.widgets.AccountpickerDialog({
                    TriggerField: this,
                    listeners: {
                        scope: this,
                        close: this.onDlgClose
                    }
                });
            }
        };
		
		this.on('select', function(){
			this.triggers[0].show();
		}, this);
	},
	
    // private
    getValue: function() {
        return this.accountId;
    },
    
    // private: only blur if dialog is closed
    onBlur: function() {
        if (!this.dlg) {
            return Tine.widgets.AccountpickerField.superclass.onBlur.apply(this, arguments);
        }
    },
    
    onDlgClose: function() {
        this.dlg = null;
    },
    
    setValue: function (value) {
        if (value) {
            this.triggers[0].show();
            if(value.accountId) {
                // account object
                this.accountId = value.accountId;
                value = value.accountDisplayName;
            } else if (typeof(value.get) == 'function') {
                // account record
                this.accountId = value.get('id');
                value = value.get('name');
            }
        }
        Tine.widgets.AccountpickerField.superclass.setValue.call(this, value);
    },
    
	// private
	onTrigger1Click: function() {
        if (! this.disabled) {
    		this.accountId = null;
    		this.setValue('');
    		this.fireEvent('select', this, null, 0);
    		this.triggers[0].hide();
        }
	}
});

/**
 * Account picker widget
 * @class Tine.widgets.AccountpickerDialog
 * @package Tinebase
 * @subpackage Widgets
 * @extends Ext.Component
 * 
 * <p> This widget supplies a modal account picker dialog.</p>
 */
Tine.widgets.AccountpickerDialog = Ext.extend(Ext.Component, {
	/**
	 * @cfg {Ext.form.field}
	 * TriggerField
	 */
	TriggerField: null,
	/**
     * @cfg {string}
     * title of dialog
     */
	title: null,
	
	// holds currently selected account
	account: false,
	
    // private
    initComponent: function(){
		Tine.widgets.AccountpickerDialog.superclass.initComponent.call(this);
		
        this.title = this.title ? this.title : _('please select an account');
        
        var ok_button = new Ext.Button({
            iconCls: 'action_saveAndClose',
            disabled: true,
            handler: this.handler_okbutton,
            text: _('Ok'),
            scope: this
        });
        
        var cancle_button = new Ext.Button({
            iconCls: 'action_cancel',
            scope: this,
            handler: function() {this.window.close();},
            text: _('Cancel')
        });
			
		this.window = new Ext.Window({
            title: this.title,
            modal: true,
            width: 320,
            height: 400,
            minWidth: 320,
            minHeight: 400,
            layout: 'fit',
            plain: true,
            bodyStyle: 'padding:5px;',
            buttonAlign: 'right',
			buttons: [cancle_button, ok_button]
        });
		
		this.accountPicker = new Tine.widgets.account.PickerPanel({
			'buttons': this.buttons
		});
        
		this.accountPicker.on('accountdblclick', function(account){
			this.account = account;
			this.handler_okbutton();
		}, this);
		
        
		this.accountPicker.on('accountselectionchange', function(account){
			this.account = account;
			ok_button.setDisabled(account ? false : true);
        }, this);
		
		this.window.add(this.accountPicker);
		this.window.show();
	},
	
	// private
	handler_okbutton: function(){
		this.TriggerField.setValue(this.account);
		this.TriggerField.fireEvent('select');
		this.window.close();
	}
});
