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
 * TODO         make it possible to switch between the comboboxes in 'both' mode and show only one combo at the time 
 * TODO         use selectAction/enableBbar configs?
 * TODO         add 'Anyone' to user selection
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
     * @cfg {String} recordPrefix
     */
    recordPrefix: '',

    /**
     * grid config
     * @private
     */
    autoExpandColumn: 'name',
    
    //private
    initComponent: function() {
        
        this.recordClass = (this.recordClass !== null) ? this.recordClass : Tine.Tinebase.Model.Account;
        this.configColumns = (this.configColumns !== null) ? this.configColumns : [];
        
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
        
        this.accountsSearchCombo = new Tine.Addressbook.SearchCombo({
            accountsStore: this.store,
            emptyText: _('Search for users ...'),
            newRecordClass: this.recordClass,
            recordPrefix: this.recordPrefix,
            internalContactsOnly: true,
            additionalFilters: [{field: 'user_status', operator: 'equals', value: this.userStatus}],
            onSelect: function(contactRecord){
                // user account record
                var recordData = {};
                recordData[this.recordPrefix + 'id'] = contactRecord.data.account_id;
                recordData[this.recordPrefix + 'type'] = 'user';
                recordData[this.recordPrefix + 'name'] = contactRecord.data.n_fileas;
                recordData[this.recordPrefix + 'data'] = contactRecord.data;
                var record = new this.newRecordClass(recordData, contactRecord.data.account_id);
                
                // check if already in
                if (! this.accountsStore.getById(record.id)) {
                    this.accountsStore.add([record]);
                }
                this.collapse();
                this.clearValue();
            }    
        })

        this.groupSearchCombo = new Tine.Tinebase.widgets.form.RecordPickerComboBox({
            accountsStore: this.store,
            blurOnSelect: true,
            recordClass: Tine.Tinebase.Model.Group,
            newRecordClass: this.recordClass,
            recordPrefix: this.recordPrefix,
            emptyText: _('Search for groups ...'),
            onSelect: function(groupRecord){
                // group account record
                var recordData = {};
                recordData[this.recordPrefix + 'id'] = groupRecord.id;
                recordData[this.recordPrefix + 'type'] = 'group';
                recordData[this.recordPrefix + 'name'] = groupRecord.data.name;
                recordData[this.recordPrefix + 'data'] = groupRecord.data;
                var record = new this.newRecordClass(recordData, groupRecord.id);
                
                // check if already in
                if (! this.accountsStore.getById(record.id)) {
                    this.accountsStore.add([record]);
                }
                this.collapse();
                this.clearValue();
            }    
        });
        
        switch (this.selectType) {
            case 'both':
                var combos = [this.accountsSearchCombo, this.groupSearchCombo];
                break;
            case 'user':
                var combos = [this.accountsSearchCombo];
                break;
            case 'group':
                var combos = [this.groupSearchCombo];
                break;
        }
        
        this.tbar = new Ext.Panel({
            layout: 'hfit',
            border: false,
            width: '100%',
            items: combos
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
            columns: [
                {id: 'type', header: '', dataIndex: 'type', width: 35, renderer: Tine.Tinebase.common.accountTypeRenderer},
                {id: 'name', header: _('Name'), dataIndex: 'name'}
            ]//.concat(this.configColumns)
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
    }
});
