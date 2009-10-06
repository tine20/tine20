/*
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * TODO         generalize this
 * TODO         make grants work
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
        tooltip: String.format(his.app.i18n._('Unlink selected {0}'), this.recordClass.getMeta('recordName')),
        disabled: true,
        iconCls: 'actionRemove',
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
        //disabled: true,
        iconCls: 'actionEdit',
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
    
    var actionItems = [
        this.actionEdit,
        this.actionUnlink
    ];
    
    if (this.otherActions) {
        actionItems = actionItems.concat(this.otherActions);
    }

    this.contextMenu = new Ext.menu.Menu({
        items: actionItems.concat(['-', this.actionAdd])
    });
};

/**
 * init store
 * 
 */ 
Tine.Crm.LinkGridPanel.initStore = function() {
    
    this.store = new Ext.data.JsonStore({
        id: 'id',
        fields: this.storeFields
    });

    // focus+select new record
    this.store.on('add', function(store, records, index) {
        (function() {
            this.getView().focusRow(index);
            this.getSelectionModel().selectRow(index); 
        }).defer(100, this);
    }, this);
};

/**
 * init ext grid panel
 * 
 * TODO         generalize
 * TODO         add grants again for all actions with required grants
 */
Tine.Crm.LinkGridPanel.initGrid = function() {
    this.cm = this.getColumnModel();
    
    this.selModel = new Ext.grid.RowSelectionModel({multiSelect:true});
    this.selModel.on('selectionchange', function(_selectionModel) {
        var rowCount = _selectionModel.getCount();
        /*
        if (this.record && (this.record.get('container_id') && this.record.get('container_id').account_grants)) {
            this.actionUnlink.setDisabled(!this.record.get('container_id').account_grants.editGrant || rowCount != 1);
        }
        this.actionEdit.setDisabled(rowCount != 1);
        */
        this.actionUnlink.setDisabled(rowCount != 1);
    }, this);
    
    this.on('rowcontextmenu', function(grid, row, e) {
        e.stopEvent();
        var selModel = grid.getSelectionModel();
        if(!selModel.isSelected(row)) {
            selModel.selectRow(row);
        }
        
        this.contextMenu.showAt(e.getXY());
    }, this);
};

/**
 * update event handler for related contacts
 * 
 * TODO         generalize
 */
Tine.Crm.LinkGridPanel.onUpdate = function(contact) {
    var response = {
        responseText: contact
    };
    contact = Tine.Addressbook.contactBackend.recordReader(response);
    
    var myContact = this.store.getById(contact.id);
    if (myContact) {
        myContact.beginEdit();
        for (var p in contact.data) { 
            myContact.set(p, contact.get(p));
        }
        myContact.endEdit();
    } else {
        contact.data.relation_type = 'customer';
        this.store.add(contact);
    }        
};
