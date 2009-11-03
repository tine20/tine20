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
 * TODO         use selectAction config?
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
    selectType: 'user',
    
    /**
     * @cfg{String} selectTypeDefault 'user' or 'group' defines which accountType is selected when  {selectType} is true
     */
    selectTypeDefault: 'user',
    
    /**
     * @cfg {Ext.Action}
     * selectAction
     */
    //selectAction: false,
    
    /**
     * @cfg {bool}
     * enable bottom toolbar
     */
    enableBbar: true,

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
     * get only users with defined status (enabled, disabled, expired)
     * get all -> 'enabled expired disabled'
     * 
     * @type String
     * @property userStatus
     */
    userStatus: 'enabled',
    
    /**
     * @type Ext.Menu
     * @property contextMenu
     */
    contextMenu: null,
    
    /**
     * @cfg {bool} have the record account properties an account prefix?
     */
    hasAccountPrefix: false,
    
    /**
     * @cfg {String} recordPrefix
     */
    recordPrefix: '',

    /**
     * grid config
     * @private
     */
    autoExpandColumn: 'name',
    
    /**
     * @cfg {Array} Array of column's config objects where the config options are in
     */
    configColumns: [],
    
    //private
    initComponent: function() {
        this.recordPrefix = (this.hasAccountPrefix) ? 'account_' : '';
        this.recordClass = (this.recordClass !== null) ? this.recordClass : Tine.Tinebase.Model.Account;
        this.configColumns = (this.configColumns !== null) ? this.configColumns : [];
        
        this.initStore();
        this.initActionsAndToolbars();
        this.initGrid();
        
        Tine.widgets.account.PickerGridPanel.superclass.initComponent.call(this);
    },

    /**
     * init store
     * @private
     */
    initStore: function() {
        
        if (this.store === null) {
            this.store = new Ext.data.SimpleStore({
                fields: this.recordClass
            });
        }
        
        // focus+select new record
        this.store.on('add', function(store, records, index) {
            (function() {
                if (this.rendered) {
                    this.getView().focusRow(index);
                    this.getSelectionModel().selectRow(index); 
                }
            }).defer(300, this);
        }, this);
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
        
        this.contextMenu = new Ext.menu.Menu({
            items: [this.actionRemove]
        });
        
        this.accountTypeSelector = this.getAccountTypeSelector();
        this.contactSearchCombo = this.getContactSearchCombo();
        this.groupSearchCombo = this.getGroupSearchCombo();
        
        var items = [];
        switch (this.selectType) {
            case 'both':
                items = items.concat([this.contactSearchCombo, this.groupSearchCombo]);
                if (this.selectTypeDefault == 'user') {
                    this.groupSearchCombo.hide();
                } else {
                    this.contactSearchCombo.hide();
                }
                break;
            case 'user':
                items = items.concat([this.contactSearchCombo]);
                break;
            case 'group':
                items = items.concat([this.groupSearchCombo]);
                break;
        }
        
        // TODO try to make hfit work correctly for search combos
        this.tbar = [this.accountTypeSelector,
            new Ext.Panel({
                layout: 'hfit',
                border: false,
                items: items
        })];
        
        if (this.enableBbar) {
            this.bbar = new Ext.Toolbar({
                items: [
                    this.actionRemove
                ]
            });
        }
    },
    
    /**
     * @return {Ext.Action}
     */
    getAccountTypeSelector: function() {
        return new Ext.Action({
            text: '',
            disabled: false,
            iconCls: (this.selectTypeDefault) ? 'tinebase-accounttype-user' : 'tinebase-accounttype-group',
            menu: new Ext.menu.Menu({
                items: [{
                    text: _('Search User'),
                    scope: this,
                    iconCls: 'tinebase-accounttype-user',
                    handler: function() {
                        this.contactSearchCombo.show();
                        this.groupSearchCombo.hide();
                        this.accountTypeSelector.setIconClass('tinebase-accounttype-user');
                    }
                }, {
                    text: _('Search Group'),
                    scope: this,
                    iconCls: 'tinebase-accounttype-group',
                    handler: function() {
                        this.contactSearchCombo.hide();
                        this.groupSearchCombo.show();
                        this.accountTypeSelector.setIconClass('tinebase-accounttype-group');
                    }
                }, {
                    text: _('Add Anyone'),
                    scope: this,
                    newRecordClass: this.recordClass,
                    iconCls: 'tinebase-accounttype-addanyone',
                    handler: function() {
                        // add anyone
                        var recordData = {};
                        recordData[this.recordPrefix + 'type'] = 'anyone';
                        recordData[this.recordPrefix + 'name'] = _('Anyone');
                        recordData[this.recordPrefix + 'id'] = 0;
                        var record = new this.recordClass(recordData, 0);
                        
                        // check if already in
                        if (! this.store.getById(record.id)) {
                            this.store.add([record]);
                        }
                    }
                }]
            }),
            scope: this
        });
    },

    /**
     * @return {Tine.Addressbook.SearchCombo}
     */
    getContactSearchCombo: function() {
        return new Tine.Addressbook.SearchCombo({
            width: 300,
            accountsStore: this.store,
            emptyText: _('Search for users ...'),
            newRecordClass: this.recordClass,
            recordPrefix: this.recordPrefix,
            internalContactsOnly: true,
            additionalFilters: [{field: 'user_status', operator: 'equals', value: this.userStatus}],
            onSelect: this.onAddRecordFromCombo
        })
    },
    
    /**
     * @return {Tine.Tinebase.widgets.form.RecordPickerComboBox}
     */
    getGroupSearchCombo: function() {
        return new Tine.Tinebase.widgets.form.RecordPickerComboBox({
            width: 300,
            accountsStore: this.store,
            blurOnSelect: true,
            recordClass: Tine.Tinebase.Model.Group,
            newRecordClass: this.recordClass,
            recordPrefix: this.recordPrefix,
            emptyText: _('Search for groups ...'),
            onSelect: this.onAddRecordFromCombo
        });        
    },
    
    /**
     * init grid (column/selection model, ctx menu, ...)
     */
    initGrid: function() {
        this.cm = this.getColumnModel();
        
        this.selModel = new Ext.grid.RowSelectionModel({multiSelect:true});
        
        /*
        // remove non-plugin config columns
        var nonPluginColumns = [];
        for (var i=0; i < this.configColumns.length; i++) {
            if (!this.configColumns[i].init || typeof(this.configColumns[i].init) != 'function') {
                nonPluginColumns.push(this.configColumns[i]);
            }
        }
        for (var i=0; i < nonPluginColumns.length; i++) {
            this.configColumns.remove(nonPluginColumns[i]);
        }
        */
        this.plugins = this.configColumns;
    
        // on selectionchange handler
        this.selModel.on('selectionchange', function(sm) {
            var rowCount = sm.getCount();
            this.actionRemove.setDisabled(rowCount == 0);
        }, this);
        
        // on rowcontextmenu handler
        this.on('rowcontextmenu', function(grid, row, e) {
            e.stopEvent();
            var selModel = grid.getSelectionModel();
            if(!selModel.isSelected(row)) {
                selModel.selectRow(row);
            }
            
            this.contextMenu.showAt(e.getXY());
        }, this);
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
            columns:  [
                //{id: 'type', header: '',        dataIndex: this.recordPrefix + 'type', width: 35, renderer: Tine.Tinebase.common.accountTypeRenderer},
                {id: 'name', header: _('Name'), dataIndex: this.recordPrefix + 'name', renderer: Tine.Tinebase.common.accountRenderer}
            ].concat(this.configColumns)
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
    },
    
    /**
     * key down handler
     * @private
     */
    onKeyDown: function(e){
        if (e.ctrlKey) {
            switch (e.getKey()) {
                case e.A:
                    // select all records
                    this.getSelectionModel().selectAll(true);
                    e.preventDefault();
                    break;
            }
        } else {
            switch (e.getKey()) {
                case e.DELETE:
                    // delete selected record(s)
                    this.onRemove();
                    break;
            }
        }
    },
    
    /**
     * @param {Record} recordToAdd
     */
    onAddRecordFromCombo: function(recordToAdd) {
        var recordData = {};
        
        if (recordToAdd.data.account_id) {
            // user account record
            recordData[this.recordPrefix + 'id'] = recordToAdd.data.account_id;
            recordData[this.recordPrefix + 'type'] = 'user';
            recordData[this.recordPrefix + 'name'] = recordToAdd.data.n_fileas;
            recordData[this.recordPrefix + 'data'] = recordToAdd.data;
            var record = new this.newRecordClass(recordData, recordToAdd.data.account_id);
            
        } else if (recordToAdd.data.name) {
            // group account
            recordData[this.recordPrefix + 'id'] = recordToAdd.id;
            recordData[this.recordPrefix + 'type'] = 'group';
            recordData[this.recordPrefix + 'name'] = recordToAdd.data.name;
            recordData[this.recordPrefix + 'data'] = recordToAdd.data;
            var record = new this.newRecordClass(recordData, recordToAdd.id);
        } 
        
        // check if already in
        if (! this.accountsStore.getById(record.id)) {
            this.accountsStore.add([record]);
        }
        this.collapse();
        this.clearValue();
    }
});

