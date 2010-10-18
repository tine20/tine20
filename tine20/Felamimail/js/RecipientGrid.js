/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Felamimail');

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.RecipientGrid
 * @extends     Ext.grid.EditorGridPanel
 * 
 * <p>Recipient Grid Panel</p>
 * <p>grid panel for to/cc/bcc recipients</p>
 * <pre>
 * TODO         add name to email address for display
 * TODO         disable horizontal scrollbar
 * TODO         use 'standard' template for adb search combo with image and both email addresses
 * </pre>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:MessageEditDialog.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Felamimail.RecipientGrid
 */
Tine.Felamimail.RecipientGrid = Ext.extend(Ext.grid.EditorGridPanel, {
    
    /**
     * @private
     */
    id: 'felamimail-recipient-grid',
    
    /**
     * the message record
     * @type Tine.Felamimail.Model.Message
     * @property record
     */
    record: null,
    
    /**
     * @type Ext.Menu
     * @property contextMenu
     */
    contextMenu: null,
    
    /**
     * @type Ext.data.SimpleStore
     * @property store
     */
    store: null,
    
    /**
     * @cfg {String} autoExpandColumn
     * auto expand column of grid
     */
    autoExpandColumn: 'address',
    
    /**
     * @cfg {Number} clicksToEdit
     * clicks to edit for editor grid panel
     */
    clicksToEdit:1,
    
    /**
     * @cfg {Boolean} header
     * show header
     */
    header: false,
    
    /**
     * @cfg {Boolean} border
     * show border
     */
    border: false,
    
    /**
     * @cfg {Boolean} deferredRender
     * deferred rendering
     */
    deferredRender: false,
    
    /**
     * @private
     */
    initComponent: function() {
        
        this.initStore();
        this.initColumnModel();
        this.initActions();
        this.sm = new Ext.grid.RowSelectionModel();
        
        Tine.Felamimail.RecipientGrid.superclass.initComponent.call(this);

        this.on('rowcontextmenu', function(grid, row, e) {
            e.stopEvent();
            var selModel = grid.getSelectionModel();
            if(!selModel.isSelected(row)) {
                selModel.selectRow(row);
            }
            this.contextMenu.showAt(e.getXY());
        }, this);

        this.on('afteredit', this.onAfterEdit, this);
    },
    
    /**
     * init store
     * @private
     */
    initStore: function() {
        this.store = new Ext.data.SimpleStore({
            fields   : ['type', 'address']
        });
        
        // init recipients (on reply/reply to all)
        this._addRecipients(this.record.get('to'), 'to');
        this._addRecipients(this.record.get('cc'), 'cc');
        
        this.store.add(new Ext.data.Record({type: 'to', 'address': ''}));
        
        this.store.on('update', this.onUpdateStore, this);
    },
    
    /**
     * init cm
     * @private
     */
    initColumnModel: function() {
        this.cm = new Ext.grid.ColumnModel([
            {
                resizable: true,
                id: 'type',
                dataIndex: 'type',
                width: 103,
                menuDisabled: true,
                header: 'type',
                renderer: function(value) {
                    switch(value) {
                        case 'to':
                            return _('To:');
                            break;
                        case 'cc':
                            return _('Cc:');
                            break;
                        case 'bcc':
                            return _('Bcc:');
                            break;
                        default:
                            return '';
                    }
                },
                editor: new Ext.form.ComboBox({
                    typeAhead     : false,
                    triggerAction : 'all',
                    lazyRender    : true,
                    editable      : false,
                    mode          : 'local',
                    value         : null,
                    forceSelection: true,
                    store         : [
                        ['to',  _('To:')],
                        ['cc',  _('Cc:')],
                        ['bcc', _('Bcc:')]
                    ]
                })
            },{
                resizable: true,
                menuDisabled: true,
                id: 'address',
                dataIndex: 'address',
                header: 'address',
                editor: new Tine.Felamimail.ContactSearchCombo({})
            }
        ]);
    },
    
    /**
     * init actions / ctx menu
     * @private
     */
    initActions: function() {
        this.action_remove = new Ext.Action({
            text: _('Remove'),
            handler: this.onDelete,
            iconCls: 'action_delete',
            scope: this
        });
        
        this.contextMenu = new Ext.menu.Menu({
            items:  this.action_remove
        });        
    },
    
    /**
     * start editing after render
     * @private
     */
    afterRender: function() {
        Tine.Felamimail.RecipientGrid.superclass.afterRender.call(this);
        
        if (this.store.getCount() == 1) {
            this.startEditing.defer(200, this, [0, 1]);
        }
    },
    
    /**
     * store has been updated
     * -> update record to/cc/bcc (if edit)
     * -> add additional row (if new address has been added)
     * 
     * @param {} store
     * @param {} record
     * @param {} operation
     * @private
     */
    onUpdateStore: function(store, record, operation) {
        // update record recipient fields
        this.record.data.to = [];
        this.record.data.cc = [];
        this.record.data.bcc = [];
        store.each(function(recipient){
            if (recipient.data.address != '') {
                this.record.data[recipient.data.type].push(recipient.data.address);
            }
        }, this);
    },
    
    /**
     * after edit
     * 
     * @param {} o
     */
    onAfterEdit: function(o) {
        if (o.field == 'address') {
            if (o.originalValue == '') {
                // use selected type to create new row with empty address and start editing
                this.store.add(new Ext.data.Record({type: o.record.data.type, 'address': ''}));
                this.store.commitChanges();
                this.startEditing(o.row +1, o.column);
            } else if (o.value == '') {
                this.store.remove(o.record);
            }
            if (this.store.getCount() > 6) {
                // TODO get height dynamically
                this.setHeight(155);
            }
            this.ownerCt.doLayout();
        }
    },    
    
    /**
     * delete handler
     */
    onDelete: function(btn, e) {
        var sm = this.getSelectionModel();
        var records = sm.getSelections();
        Ext.each(records, function(record) {
            if (record.get('address') != '') {
                this.store.remove(record);
                this.store.fireEvent('update', this.store);
            }
        }, this);
        this.ownerCt.doLayout();
    },
    
    /**
     * add recipients to grid store
     * 
     * @param {Array} recipients
     * @param {String} type
     * @private
     * 
     * TODO get own email address and don't add it to store
     */
    _addRecipients: function(recipients, type) {
        if (recipients) {
            for (var i=0; i<recipients.length; i++) {
                this.store.add(new Ext.data.Record({type: type, 'address': recipients[i]}));
            }
        }
    }
});
