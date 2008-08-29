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
		
		if (this.selectOnFocus) {
			this.on('focus', function(){
				return this.onTrigger2Click();
			});
		}
		
		this.onTrigger2Click = function(e) {
            this.dlg = new Tine.widgets.AccountpickerDialog({
                TriggerField: this
            });
        };
		
		this.on('select', function(){
			this.triggers[0].show();
		});
	},
	
    // private
    getValue: function(){
        return this.accountId;
    },
	// private
	onTrigger1Click: function(){
		this.accountId = null;
		this.setValue('');
		this.fireEvent('select', this, null, 0);
		this.triggers[0].hide();
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
            disabled: true,
            handler: this.handler_okbutton,
            text: _('Ok'),
            scope: this
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
			buttons: [ok_button],
            buttonAlign: 'center'
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
		this.TriggerField.accountId = this.account.data.accountId;
		this.TriggerField.setValue(this.account.data.accountDisplayName);
		this.TriggerField.fireEvent('select');
		this.window.hide();
	}
});



/**
 * @class Tine.widgets.AccountpickerActiondialog
 * <p>A baseclass for assembling dialogs with actions related to accounts</p>
 * <p>This class should be extended by its users and normaly not be instanciated
 * using the new keyword.</p>
 * @extends Ext.Component
 * @constructor
 * @param {Object} config The configuration options
 */
Tine.widgets.AccountpickerActiondialog = Ext.extend(Ext.Window, {
	/**
	 * @cfg
	 * {Ext.Toolbar} Toolbar to display in the bottom area of the user selection
	 */
	userSelectionBottomToolBar: null,
	
	modal: true,
    layout:'border',
    width:700,
    height:450,
    closeAction:'hide',
    plain: true,

    //private
    initComponent: function(){
		//this.addEvents()
		
		this.userSelection = new Tine.widgets.account.PickerPanel({
			enableBbar: true,
			region: 'west',
			split: true,
			bbar: this.userSelectionBottomToolBar,
            selectType: this.selectType,
			selectAction: function() {
				
			}
		});
		
		if (!this.items) {
			this.items = [];
			this.userSelection.region = 'center';
		}
		this.items.push(this.userSelection);
		
		// set standart buttons if no buttons are given
		if (!this.buttons) {
			this.buttons = [{
				text: _('Apply'),
				id: 'AccountsActionApplyButton',
				disabled: true,
				scope: this,
				handler: this.handlers.accountsActionApply
			}, {
				text: _('Close'),
				scope: this,
				handler: function(){this.close();}
			}, {
                text: _('Save'),
                id: 'AccountsActionSaveButton',
                disabled: true,
                scope: this,
                handler: this.handlers.accountsActionSave
            }];
		}
		Tine.widgets.AccountpickerActiondialog.superclass.initComponent.call(this);
	},
	/**
	 * Returns user Selection Panel
	 * @return {Tine.widgets.account.PickerPanel}
	 */
	getUserSelection: function() {
		return this.userSelection;
	}
});
