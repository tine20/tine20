/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  widgets
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
 */

Ext.namespace('Tine.widgets.account');

/**
 * Account Picker GridPanel
 * 
 * @namespace   Tine.widgets.account
 * @class       Tine.widgets.account.PickerGridPanel
 * @extends     Ext.grid.GridPanel
 * 
 * <p>Account Picker GridPanel</p>
 * <p><pre>
 * TODO         finish implementation
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.widgets.account.PickerGridPanel
 */
Tine.widgets.account.PickerGridPanel = Ext.extend(Ext.grid.GridPanel, {
    /**
     * @cfg {String} one of 'user', 'group', 'both'
     * selectType
     */
    //selectType: 'user',
    /**
     * @cfg{String} selectTypeDefault 'user' or 'group' defines which accountType is selected when  {selectType} is true
     */
    //selectTypeDefault: 'user',
    /**
     * @cfg {Ext.Action}
     * selectAction
     */
    //selectAction: false,
    /**
     * @cfg {bool}
     * enable bottom toolbar
     */
    //enableBbar: false,

    /**
     * store to hold all accounts
     * 
     * @type Ext.data.Store
     * @property store
     */
    store: null,
    
    /**
     * record class
     * @cfg {} recordClass
     */
    recordClass: null,
    
    /**
     * @type Ext.Menu
     * @property contextMenu
     */
    //contextMenu: null,

    /**
     * grid config
     * @private
     */
    autoExpandColumn: 'name',
    
    /*
    defaults:{autoScroll:true},
    border: false,
    split: true,
    width: 300,
    collapsible: false,
    */
    
    //private
    initComponent: function() {
        
        this.initStore();
        this.initActionsAndToolbars();
        this.initGrid();
        
        Tine.widgets.account.PickerGridPanel.superclass.initComponent.call(this);
    },

    /**
     * TODO do we need this?
     */
    initStore: function() {
        
        if (this.store === null) {
            this.store = new Ext.data.SimpleStore({
                fields: Tine.Tinebase.Model.Account
            });
        }
        
        /*
        // focus+select new record
        this.store.on('add', function(store, records, index) {
            (function() {
                if (this.rendered) {
                    this.getView().focusRow(index);
                    this.getSelectionModel().selectRow(index); 
                }
            }).defer(300, this);
        }, this);
        */
    },

    /**
     * init actions and toolbars
     */
    initActionsAndToolbars: function() {
        
        this.actionRemove = new Ext.Action({
            text: _('Remove account'),
            disabled: true,
            scope: this,
            handler: this.onRemove,
            iconCls: 'action_deleteContact'
        })

        this.tbar = new Ext.Panel({
            layout: 'fit',
            border: false,
            width: '100%',
            items: [
                new Tine.Addressbook.SearchCombo({
                    accountsStore: this.store,
                    emptyText: _('Search for user accounts to add ...'),
                    internalContactsOnly: true,
                    onSelect: function(contactRecord){
                        var record = new Tine.Tinebase.Model.Account({
                            id: contactRecord.data.account_id,
                            type: 'user',
                            name: contactRecord.data.n_fileas,
                            data: contactRecord.data
                        }, contactRecord.data.account_id);
                        
                        // check if already in
                        if (! this.accountsStore.getById(record.id)) {
                            console.log(record);
                            this.accountsStore.add([record]);
                        }
                        this.collapse();
                        this.clearValue();
                    }    
                })
            ]
        });
        
        this.bbar = new Ext.Toolbar({
            items: [
                this.actionRemove
            ]
        });
    },

    /**
     * init grid (column/selection model, ctx menu, ...)
     */
    initGrid: function() {
        this.cm = this.getColumnModel();
        
        this.selModel = new Ext.grid.RowSelectionModel({multiSelect:true});
    
        // on selectionchange handler
        this.selModel.on('selectionchange', function(sm) {
            var rowCount = sm.getCount();
            this.actionRemove.setDisabled(rowCount == 0);
            
            /*
            var selectedRows = this.getSelectionModel().getSelections();
            if (selectedRows.length > 0) {
                var selectedRecord = selectedRows[0];
            }
            if (this.record && (this.record.get('container_id') && this.record.get('container_id').account_grants)) {
                for (var i=0; i < this.actions.length; i++) {
                    this.actions[i].setDisabled(
                        ! this.record.get('container_id').account_grants.editGrant 
                        || (this.actions[i].initialConfig.onlySingle && rowCount != 1)
                        || (this.actions[i] == this.actionEdit && selectedRecord && selectedRecord.phantom == true)
                    );
                }
            }
            */
            
        }, this);
        
        // on rowcontextmenu handler
        /*
        this.on('rowcontextmenu', function(grid, row, e) {
            e.stopEvent();
            var selModel = grid.getSelectionModel();
            if(!selModel.isSelected(row)) {
                selModel.selectRow(row);
            }
            
            this.contextMenu.showAt(e.getXY());
        }, this);
        */
    },
    
    /**
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumnModel: function() {
        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: true
            },
            columns: [
                {id: 'name', header: _('Name'), dataIndex: 'name', width: 200}
            ]
        });
    },
    
    /**
     * remove handler
     * 
     * @param {} button
     * @param {} event
     */
    onRemove: function(button, event) {                       
        var selectedRows = this.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; ++i) {
            this.store.remove(selectedRows[i]);
        }           
    }
});
