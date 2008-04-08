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
	title: 'please select an account',
	
	// holds currently selected account
	account: false,
	
    // private
    initComponent: function(){
		Tine.widgets.container.selectionDialog.superclass.initComponent.call(this);
		
		var ok_button = new Ext.Button({
            disabled: true,
            handler: this.handler_okbutton,
            text: 'Ok',
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
		
		this.accountPicker = new Tine.widgets.AccountpickerPanel({
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
 * Account picker pandel widget
 * @class Tine.widgets.AccountpickerPanel
 * @package Tinebase
 * @subpackage Widgets
 * @extends Ext.TabPanel
 * 
 * <p> This widget supplies a account picker panel to be used in related widgets.</p>
 */
Tine.widgets.AccountpickerPanel = Ext.extend(Ext.TabPanel, {
	/**
     * @cfg {Ext.Action}
     * selectAction
     */
    selectAction: false,
    /**
     * @cfg {Bool}
     * multiSelect
     */
	multiSelect: false,
	/**
	 * @cfg {bool}
	 * enable bottom toolbar
	 */
	enableBbar: false,
    /**
     * @cfg {Ext.Toolbar}
     * optional bottom bar, defaults to 'add account' which fires 'accountdblclick' event
     */	
	bbar: null,
	
	activeTab: 0,
    defaults:{autoScroll:true},
    border: false,
    split: true,
    width: 300,
    collapsible: false,
	
	//private
    initComponent: function(){
		this.addEvents(
            /**
             * @event accountdblclick
             * Fires when an account is dbl clicked
             * @param {Ext.Record} dbl clicked account
             */
            'accountdblclick',
			/**
             * @event accountselectionchange
             * Fires when account selection changes
             * @param {Ext.Record} dbl clicked account or undefined if none
             */
			'accountselectionchange'
		);
		
		this.actions = {
			addAccount: new Ext.Action({
                text: 'add account',
                disabled: true,
				scope: this,
                handler: function(){
					var account = this.searchPanel.getSelectionModel().getSelected();
                    this.fireEvent('accountdblclick', account);
				},
                iconCls: 'action_addContact'
            })
        };

        this.ugStore = new Ext.data.SimpleStore({
            fields: Tine.Tinebase.Model.Account
        });
        
        this.ugStore.setDefaultSort('name', 'asc');
        
        this.loadData = function() {
            var accountType  = Ext.ButtonToggleMgr.getSelected('account_picker_panel_ugselect').accountType;
            var searchString = Ext.getCmp('Tinebase_Accounts_SearchField').getRawValue();
            
            if (this.requestParams && this.requestParams.filter == searchString && this.requestParams.accountType == accountType) {
                return;
            }
            this.requestParams = { filter: searchString, accountType: accountType, dir: 'asc', start: 0, limit: 50 };
            
            Ext.getCmp('Tinebase_Accounts_Grid').getStore().removeAll();
            if (this.requestParams.filter.length < 1) {
                return;
            }
            
            switch (accountType){
                case 'account':
                    this.requestParams.method = 'Tinebase.getAccounts';
                    this.requestParams.sort   = 'accountDisplayName';
                    Ext.Ajax.request({
                        params: this.requestParams,
                        success: function(response, options){
                            var data = Ext.util.JSON.decode(response.responseText);
                            var toLoad = [];
                            for (var i=0; i<data.results.length; i++){
                                var item = (data.results[i]);
                                toLoad.push( new Tine.Tinebase.Model.Account({
                                    id: item.accountId,
                                    type: 'user',
                                    name: item.accountDisplayName,
                                    data: item
                                }));
                            }
                            if (toLoad.length > 0) {
                                Ext.getCmp('Tinebase_Accounts_Grid').getStore().add(toLoad);
                            }
                        }
                    });
                    break;
                case 'group':
                    this.requestParams.method = 'Admin.getGroups';
                    this.requestParams.sort   = 'name';
                    Ext.Ajax.request({
                        params: this.requestParams,
                        success: function(response, options){
                            var data = Ext.util.JSON.decode(response.responseText);
                            var toLoad = [];
                            for (var i=0; i<data.results.length; i++){
                                var item = (data.results[i]);
                                toLoad.push( new Tine.Tinebase.Model.Account({
                                    id: item.id,
                                    type: 'group',
                                    name: item.name,
                                    data: item
                                }));
                            }
                            if (toLoad.length > 0) {
                                Ext.getCmp('Tinebase_Accounts_Grid').getStore().add(toLoad);
                            }
                        }
                    });
                    break;
            }
        };
        
        var columnModel = new Ext.grid.ColumnModel([
		    {
                resizable: false,
				sortable: false, 
                id: 'name', 
                header: 'Name', 
                dataIndex: 'name', 
                width: 70
            }
        ]);

        columnModel.defaultSortable = true; // by default columns are sortable
        
        //var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});
        this.quickSearchField = new Ext.app.SearchField({
            id: 'Tinebase_Accounts_SearchField',
            width: 253,
            emptyText: 'enter searchfilter'
        }); 
        this.quickSearchField.on('change', function(){
            this.loadData();
        }, this);
        var ugSelectionChange = function(pressed){
            //console.log(p.iconCls);
        };
        this.Toolbar = new Ext.Toolbar({
            items: [
            {
                pressed: true,
                accountType: 'account',
                iconCls: 'action_selectUser',
                xtype: 'tbbtnlockedtoggle',
                handler: this.loadData,
                enableToggle: true,
                toggleGroup: 'account_picker_panel_ugselect'
            },
            {
                iconCls: 'action_selectGroup',
                accountType: 'group',
                xtype: 'tbbtnlockedtoggle',
                handler: this.loadData,
                enableToggle: true,
                toggleGroup: 'account_picker_panel_ugselect'
            },
                this.quickSearchField
            ]
        });

        if (this.enableBbar && !this.bbar) {
			this.bbar = new Ext.Toolbar({
				items: [this.actions.addAccount]
			});
		}

		this.searchPanel = new Ext.grid.GridPanel({
            title: 'Search',
            id: 'Tinebase_Accounts_Grid',
            store: this.ugStore,
            cm: columnModel,
			enableColumnHide:false,
            enableColumnMove:false,
            autoSizeColumns: false,
            selModel: new Ext.grid.RowSelectionModel({multiSelect:this.multiSelect}),
            enableColLock:false,
            loadMask: true,
            autoExpandColumn: 'name',
            tbar: this.Toolbar,
            bbar: this.Toolbar2,
            border: false
        });
		
		this.searchPanel.on('rowdblclick', function(grid, row, event) {
            var account = this.searchPanel.getSelectionModel().getSelected();
			this.fireEvent('accountdblclick', account);
		}, this);
		
		this.searchPanel.getSelectionModel().on('selectionchange', function(sm){
			var account = sm.getSelected();
			this.actions.addAccount.setDisabled(!account);
			this.fireEvent('accountselectionchange', account);
		}, this);
		
		this.items = [this.searchPanel, {
           title: 'Browse',
           html: 'Browse',
           disabled: true
        }];
		
	    Tine.widgets.AccountpickerPanel.superclass.initComponent.call(this);
	},
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
		
		this.userSelection = new Tine.widgets.AccountpickerPanel({
			enableBbar: true,
			region: 'west',
			split: true,
			bbar: this.userSelectionBottomToolBar,
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
				text: 'Save',
				id: 'AccountsActionSaveButton',
				disabled: true,
				scope: this,
				handler: this.handlers.accountsActionSave
			}, {
				text: 'Apply',
				id: 'AccountsActionApplyButton',
				disabled: true,
				scope: this,
				handler: this.handlers.accountsActionApply
			}, {
				text: 'Close',
				scope: this,
				handler: function(){this.close();}
			}];
		}
		Tine.widgets.AccountpickerActiondialog.superclass.initComponent.call(this);
	},
	/**
	 * Returns user Selection Panel
	 * @return {Tine.widgets.AccountpickerPanel}
	 */
	getUserSelection: function() {
		return this.userSelection;
	}
});
