/**
 * egroupware 2.0
 * 
 * @package     Egwbase
 * @subpackage  widgets
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
Ext.namespace('Egw.widgets');

Egw.widgets.AccountpickerField = Ext.extend(Ext.form.TwinTriggerField, {
	allowBlank: true,
	editable: false,
    readOnly:true,
	triggerAction: 'all',
	selectOnFocus: true,
	typeAhead: true,
	trigger1Class:'x-form-clear-trigger',
	hideTrigger1:true,
	accountId: null,
	
	//private
    initComponent: function(){
	    Egw.widgets.AccountpickerField.superclass.initComponent.call(this);
		
		if (this.selectOnFocus) {
			this.on('focus', function(){
				return this.onTrigger2Click();
			});
		}
		
		this.onTrigger2Click = function(e) {
		    var ok_button = new Ext.Button({
	            disabled: true,
	            handler: this.handler_okbutton,
	            text: 'Ok',
	            scope: this
	        });
			
            this.dlg = new Egw.widgets.AccountpickerDialog({
                TriggerField: this,
				buttons: [ok_button]
            });
			
			this.dlg.accountPicker.searchPanel.getSelectionModel().on('selectionchange', function(sm){
	            ok_button.setDisabled(sm.getCount() < 1);
	        },this);
        };
	},
	
    // private
    getValue: function(){
        return this.accountId;
    },
	//private
	handler_okbutton: function(){
        account = this.dlg.accountPicker.searchPanel.getSelectionModel().getSelected();
        this.accountId = account.data.accountId;
		this.setValue(account.data.accountDisplayName);
		this.fireEvent('select', this, account, 0);
        this.dlg.w.hide();
		this.triggers[0].show();
    },
	onTrigger1Click: function(){
		this.accountId = null;
		this.setValue('');
		this.fireEvent('select', this, null, 0);
		this.triggers[0].hide();
	}
});
	
Egw.widgets.AccountpickerDialog = Ext.extend(Ext.Component, {
	/**
	 * @cfg {Ext.form.field}
	 * TriggerField
	 */
	TriggerField: null,
	/**
     * @cfg {Array}
     * buttons
     */
    buttons: [],
	
	title: 'please select an account',
	
    // private
    initComponent: function(){
		Egw.widgets.container.selectionDialog.superclass.initComponent.call(this);
		this.w = new Ext.Window({
            title: this.title,
            modal: true,
            width: 320,
            height: 400,
            minWidth: 320,
            minHeight: 400,
            layout: 'fit',
            plain: true,
            bodyStyle: 'padding:5px;',
            buttonAlign: 'center'
        });
		
		this.accountPicker = new Egw.widgets.AccountpickerPanel({
			'buttons': this.buttons
		});
		
		this.w.add(this.accountPicker);
		this.w.show();
	}
});

Egw.widgets.AccountpickerPanel = Ext.extend(Ext.TabPanel, {
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
	
	activeTab: 0,
    defaults:{autoScroll:true},
    border: false,
    split: true,
    width: 300,
    collapsible: false,
	
	//private
    initComponent: function(){
		
		this.dataStore = new Ext.data.JsonStore({
            baseParams: {
                method: 'Egwbase.getAccounts'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'accountId',
            fields: [
                {name: 'accountId'},
                {name: 'accountDisplayName'}
            ],
            remoteSort: true
        });
        
        this.dataStore.setDefaultSort('accountDisplayName', 'asc');

        this.dataStore.on('beforeload', function(_dataStore) {
            _dataStore.baseParams.filter = Ext.getCmp('Egwbase_Accounts_SearchField').getRawValue();
        });        

        var columnModel = new Ext.grid.ColumnModel([
            {
                resizable: true, 
                id: 'accountDisplayName', 
                header: 'Name', 
                dataIndex: 'accountDisplayName', 
                width: 70
            }
        ]);

        columnModel.defaultSortable = true; // by default columns are sortable
        
        //var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});

        this.quickSearchField = new Ext.app.SearchField({
            id: 'Egwbase_Accounts_SearchField',
            width: 290,
            emptyText: 'enter searchfilter'
        }); 
        this.quickSearchField.on('change', function(){
			var store = Ext.getCmp('Egwbase_Accounts_Grid').getStore();
			var lastValue = store.lastOptions ? store.lastOptions.params.filter : false;
			if (lastValue != this.getRawValue()) {

				if (Ext.getCmp('Egwbase_Accounts_SearchField').getRawValue() == '') {
					Ext.getCmp('Egwbase_Accounts_Grid').getStore().removeAll();
				}
				else {
					Ext.getCmp('Egwbase_Accounts_Grid').getStore().load({
						params: {
							start: 0,
							limit: 50
						}
					});
				}
			}
        });

        this.Toolbar = new Ext.Toolbar({
            items: [
                this.quickSearchField
            ]
        });


        this.Toolbar2 = new Ext.Toolbar({
            /*id: 'Addressbook_Contacts_Toolbar', */
            items: [
                //action_addAccount
            ]
        });

		this.searchPanel = new Ext.grid.GridPanel({
            title: 'Search',
            id: 'Egwbase_Accounts_Grid',
            store: this.dataStore,
            cm: columnModel,
            autoSizeColumns: false,
            selModel: new Ext.grid.RowSelectionModel({multiSelect:this.multiSelect}),
            enableColLock:false,
            loadMask: true,
            autoExpandColumn: 'accountDisplayName',
            tbar: this.Toolbar,
            bbar: this.Toolbar2,
            border: false
        });
		
		this.items = [this.searchPanel, {
           title: 'Browse',
           html: 'Browse',
           disabled: true
        }];
		
	    Egw.widgets.AccountpickerPanel.superclass.initComponent.call(this);
		
	}
});

