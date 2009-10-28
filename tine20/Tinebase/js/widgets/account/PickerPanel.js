/**
 * Account picker panel
 * 
 * @class Tine.widgets.account.PickerPanel
 * @package Tinebase
 * @subpackage Widgets
 * @extends Ext.TabPanel
 * 
 * <p> This widget supplies a account picker panel to be used in related widgets.</p>
 */
 
Ext.namespace('Tine.widgets', 'Tine.widgets.account');
Tine.widgets.account.PickerPanel = Ext.extend(Ext.TabPanel, {
    /**
     * @cfg {String} one of 'user', 'group', 'both'
     * selectType
     */
    selectType: 'user',
    /**
     * @cfg{String} selectTypeDefault 'user' or 'group' defines which accountType is selected when  {selectType} is true
     */
    selectTypeDefault: 'user',
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
                text: _('add account'),
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
            this.requestParams = { 
        		paging: {
            		dir: 'asc', 
            		start: 0, 
            		limit: 50 
        		}
            };
            
            Ext.getCmp('Tinebase_Accounts_Grid').getStore().removeAll();
            
            switch (accountType){
                case 'user':
                    this.requestParams.method = 'Addressbook.searchContacts';
                    this.requestParams.paging.sort   = 'n_fileas';
                    this.requestParams.filter = 
                    	[{
        		        	 field: 'query',
        		        	 operator: 'contains',
        		        	 value: searchString
    		        	 }, {
        		        	 field: 'type',
        		        	 operator: 'equals',
        		        	 value: 'user'
    		        	 }, {
        		        	 field: 'user_status',
        		        	 operator: 'equals',
        		        	 value: 'enabled'
    		        	 }];
                    
                    
                    Ext.Ajax.request({
                        params: this.requestParams,
                        success: function(response, options){
                            var data = Ext.util.JSON.decode(response.responseText);
                            
                            var toLoad = [];
                            for (var i=0; i<data.results.length; i++){
                                var item = (data.results[i]);
                                toLoad.push( new Tine.Tinebase.Model.Account({
                                    id: item.account_id,
                                    type: 'user',
                                    name: item.n_fileas,
                                    data: item
                                }));
                            }
                            if (toLoad.length > 0) {
                                var grid = Ext.getCmp('Tinebase_Accounts_Grid');
                                grid.getStore().add(toLoad);
                                
                                // select first result and focus row
                                grid.getSelectionModel().selectFirstRow();                                
                                grid.getView().focusRow(0);
                            }
                        }
                    });
                    break;
                case 'group':
                    this.requestParams.method = 'Tinebase.getGroups';
                    this.requestParams.sort   = 'name';
                    this.requestParams.filter = searchString;
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
                                var grid = Ext.getCmp('Tinebase_Accounts_Grid');
                                grid.getStore().add(toLoad);
                                
                                // select first result
                                grid.getSelectionModel().selectFirstRow();
                                grid.getView().focusRow(0);
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
                header: _('Name'), 
                dataIndex: 'name', 
                width: 70
            }
        ]);

        columnModel.defaultSortable = true; // by default columns are sortable
        
        //var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});
        this.quickSearchField = new Ext.ux.SearchField({
            id: 'Tinebase_Accounts_SearchField',
            emptyText: _('enter searchfilter')
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
                scope: this,
                hidden: this.selectType != 'both',
                pressed: this.selectTypeDefault != 'group',
                accountType: 'user',
                iconCls: 'action_selectUser',
                xtype: 'tbbtnlockedtoggle',
                handler: this.loadData,
                enableToggle: true,
                toggleGroup: 'account_picker_panel_ugselect'
            },
            {
                scope: this,
                hidden: this.selectType != 'both',
                pressed: this.selectTypeDefault == 'group',
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
            title: _('Search'),
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
            border: false
        });
        
        this.searchPanel.on('rowdblclick', function(grid, row, event) {
            var account = this.searchPanel.getSelectionModel().getSelected();
            this.fireEvent('accountdblclick', account);
        }, this);
        
        // on keypressed("enter") event to add account
        this.searchPanel.on('keydown', function(event){
             //if(event.getKey() == event.ENTER && !this.searchPanel.editing){
             if(event.getKey() == event.ENTER){
                var account = this.searchPanel.getSelectionModel().getSelected();
                this.fireEvent('accountdblclick', account);
             }
        }, this);
        
        this.searchPanel.getSelectionModel().on('selectionchange', function(sm){
            var account = sm.getSelected();
            this.actions.addAccount.setDisabled(!account);
            this.fireEvent('accountselectionchange', account);
        }, this);
        
        this.items = [this.searchPanel, {
           title: _('Browse'),
           html: _('Browse'),
           disabled: true
        }];
        
        Tine.widgets.account.PickerPanel.superclass.initComponent.call(this);
        
        this.on('resize', function(){
            this.quickSearchField.setWidth(this.getSize().width - 3 - (this.selectType == 'both' ? 44 : 0));
        }, this);

        this.quickSearchField.on('render', function(field) {
            this.quickSearchField.focus(false, 350);
        }, this);
    }
});