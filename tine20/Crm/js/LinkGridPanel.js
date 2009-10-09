/*
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * TODO         make row dblclick work
 * TODO         add to extdoc
 */
 
Ext.ns('Tine.Crm.LinkGridPanel');

/**
 * @namespace   Tine.Crm.LinkGridPanel
 * 
 * TODO         move change contact type functions
 */
Tine.Crm.LinkGridPanel.initActions = function() {

    this.actionAdd = new Ext.Action({
        requiredGrant: 'editGrant',
        text: String.format(this.app.i18n._('Add new {0}'), this.recordClass.getMeta('recordName')),
        tooltip: String.format(this.app.i18n._('Add new {0}'), this.recordClass.getMeta('recordName')),
        iconCls: 'actionAdd',
        disabled: true,
        scope: this,
        handler: function(_button, _event) {
            var editWindow = this.recordEditDialogOpener({
                listeners: {
                    scope: this,
                    'update': this.onUpdate
                }
            });
        }
    });
    
    this.actionUnlink = new Ext.Action({
        requiredGrant: 'editGrant',
        text: String.format(this.app.i18n._('Unlink {0}'), this.recordClass.getMeta('recordName')),
        tooltip: String.format(this.app.i18n._('Unlink selected {0}'), this.recordClass.getMeta('recordName')),
        disabled: true,
        iconCls: 'actionRemove',
        onlySingle: true,
        scope: this,
        handler: function(_button, _event) {                       
            var selectedRows = this.getSelectionModel().getSelections();
            for (var i = 0; i < selectedRows.length; ++i) {
                this.store.remove(selectedRows[i]);
            }           
        }
    });
    
    this.actionEdit = new Ext.Action({
        requiredGrant: 'editGrant',
        text: String.format(this.app.i18n._('Edit {0}'), this.recordClass.getMeta('recordName')),
        tooltip: String.format(this.app.i18n._('Edit selected {0}'), this.recordClass.getMeta('recordName')),
        disabled: true,
        iconCls: 'actionEdit',
        onlySingle: true,
        scope: this,
        handler: function(_button, _event) {
            var selectedRows = this.getSelectionModel().getSelections();
            
            var editWindow = this.recordEditDialogOpener({
                record: selectedRows[0],
                listeners: {
                    scope: this,
                    'update': this.onUpdate
                }
            });
        }
    });

    // init toolbars and ctx menut / add actions
    this.bbar = [                
        this.actionAdd,
        this.actionUnlink
    ];
    
    this.actions = [
        this.actionEdit,
        this.actionUnlink
    ];
    
    if (this.otherActions) {
        this.actions = this.actions.concat(this.otherActions);
    }

    this.contextMenu = new Ext.menu.Menu({
        items: this.actions.concat(['-', this.actionAdd])
    });
    
    this.actions.push(this.actionAdd);
};

/**
 * init store
 * 
 */ 
Tine.Crm.LinkGridPanel.initStore = function() {
    
    this.store = new Ext.data.JsonStore({
        fields: (this.storeFields) ? this.storeFields : this.recordClass
    });

    // focus+select new record
    this.store.on('add', function(store, records, index) {
        (function() {
            this.getView().focusRow(index);
            this.getSelectionModel().selectRow(index); 
        }).defer(300, this);
    }, this);
};

/**
 * init ext grid panel
 * 
 * TODO         add grants for linked entries to disable EDIT?
 * TODO         use action updater?
 */
Tine.Crm.LinkGridPanel.initGrid = function() {
    this.cm = this.getColumnModel();
    
    this.selModel = new Ext.grid.RowSelectionModel({multiSelect:true});
    this.selModel.on('selectionchange', function(sm) {
        //Tine.widgets.actionUpdater(sm, this.actions, 'container_id', !this.evalGrants);
        
        var rowCount = sm.getCount();
        if (this.record && (this.record.get('container_id') && this.record.get('container_id').account_grants)) {
            for (var i=0; i < this.actions.length; i++) {
                this.actions[i].setDisabled(
                    ! this.record.get('container_id').account_grants.editGrant 
                    || (this.actions[i].initialConfig.onlySingle && rowCount != 1)
                );
            }
        }
        
    }, this);
    
    this.on('rowcontextmenu', function(grid, row, e) {
        e.stopEvent();
        var selModel = grid.getSelectionModel();
        if(!selModel.isSelected(row)) {
            selModel.selectRow(row);
        }
        
        this.contextMenu.showAt(e.getXY());
    }, this);
    
    //this.on('rowdblclick', this.actionEdit, this);
};

/**
 * update event handler for related records
 * 
 */
Tine.Crm.LinkGridPanel.onUpdate = function(record) {
    var response = {
        responseText: record
    };
    record = this.recordProxy.recordReader(response);
    
    var myRecord = this.store.getById(record.id);
    if (myRecord) {
        myRecord.beginEdit();
        for (var p in record.data) { 
            myRecord.set(p, record.get(p));
        }
        myRecord.endEdit();
    } else {
        this.store.add(record);
    }
};
