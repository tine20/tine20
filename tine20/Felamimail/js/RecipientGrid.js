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
    cls: 'felamimail-recipient-grid',
    
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
     * @cfg {Number} numberOfRecordsForFixedHeight
     */
    numberOfRecordsForFixedHeight: 6,

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
    
    forceValidation: true,
    
    enableDrop: true,
    ddGroup: 'recipientDDGroup',

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
            if (!selModel.isSelected(row)) {
                selModel.selectRow(row);
            }
            
            var record = this.store.getAt(row);
            this.action_remove.setDisabled(record.get('address') == '');
            
            this.contextMenu.showAt(e.getXY());
        }, this);
            
        this.on('beforeedit', this.onBeforeEdit, this);
        this.on('afteredit', this.onAfterEdit, this);
        this.on('validateedit', this.onValidateEdit, this);
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
        this.syncRecipientsToStore(['to', 'cc']);
        
        this.store.add(new Ext.data.Record({type: 'to', 'address': ''}));
        
        this.store.on('update', this.onUpdateStore, this);
        this.store.on('add', this.onAddStore, this);
    },
    
    /**
     * init cm
     * @private
     */
    initColumnModel: function() {
        
        var app = Tine.Tinebase.appMgr.get('Felamimail');
        
        this.searchCombo = new Tine.Felamimail.ContactSearchCombo({
            listeners: {
                scope: this,
                specialkey: function(combo, e) {
                    // jump to subject if we are in the last row and it is empty
                    var sm = this.getSelectionModel(),
                        record = sm.getSelected();
                    if ((! record || record.get('address') == '') && ! sm.hasNext()) {
                        this.fireEvent('specialkey', combo, e);
                    }
                },
                blur: function(combo) {
                    // need to update record because we relay blur event and it might not be updated otherwise
                    var value = combo.getValue();
                    if (this.activeEditor && this.activeEditor.record.get('address') != value) {
                        this.activeEditor.record.set('address', value);
                    }
                }
            }
        });
        
        this.cm = new Ext.grid.ColumnModel([
            {
                resizable: true,
                id: 'type',
                dataIndex: 'type',
                width: 104,
                menuDisabled: true,
                header: 'type',
                renderer: function(value) {
                    var result = '',
                        qtip = app.i18n._('Click here to set To/CC/BCC.');

                    switch(value) {
                        case 'to':
                            result = app.i18n._('To:');
                            break;
                        case 'cc':
                            result = app.i18n._('Cc:');
                            break;
                        case 'bcc':
                            result = app.i18n._('Bcc:');
                            break;
                    }
                    
                    return '<div qtip="' + qtip +'">' + result + '</div>';
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
                        ['to',  app.i18n._('To:')],
                        ['cc',  app.i18n._('Cc:')],
                        ['bcc', app.i18n._('Bcc:')]
                    ]
                })
            },{
                resizable: true,
                menuDisabled: true,
                id: 'address',
                dataIndex: 'address',
                header: 'address',
                editor: this.searchCombo
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
        
        this.setFixedHeight(true);
        
        this.relayEvents(this.searchCombo, ['blur' ]);
        
        this.initDropTarget();
    },
    
    /**
     * init drop target with notifyDrop fn 
     * - adds new records from drag data to the recipient store
     */
    initDropTarget: function() {
        var dropTargetEl = this.getView().scroller.dom;
        var dropTarget = new Ext.dd.DropTarget(dropTargetEl, {
            ddGroup    : 'recipientDDGroup',
            notifyDrop : function(ddSource, e, data) {
                this.grid.addRecordsToStore(ddSource.dragData.selections);
                return true;
            },
            grid: this
        });        
    },
    
    /**
     * add records to recipient store
     * 
     * @param {Array} records
     * @param {String} type
     */
    addRecordsToStore: function(records, type) {
        if (! type) {
            var emptyRecord = this.store.getAt(this.store.findExact('address', '')),
                type = (emptyRecord) ? emptyRecord.get('type') : 'to';
        }
                        
        var hasEmail = false,
            added = false;

        Ext.each(records, function(record) {
            if (record.hasEmail()) {
                this.store.add(new Ext.data.Record({type: type, 'address': Tine.Felamimail.getEmailStringFromContact(record)}));
                added = true;
            }
        }, this);        
    },
    
    /**
     * set grid to fixed height if it has more than X records
     *  
     * @param {} doLayout
     */
    setFixedHeight: function (doLayout) {
        if (this.store.getCount() > this.numberOfRecordsForFixedHeight) {
            this.setHeight(155);
        }

        if (doLayout && doLayout === true) {
            this.ownerCt.doLayout();
        }
    },
    
    /**
     * store has been updated
     * 
     * @param {} store
     * @param {} record
     * @param {} operation
     * @private
     */
    onUpdateStore: function(store, record, operation) {
        this.syncRecipientsToRecord();
    },
    
    /**
     * on add event of store
     * 
     * @param {} store
     * @param {} records
     * @param {} index
     */
    onAddStore: function(store, records, index) {
        this.syncRecipientsToRecord();
    },
    
    /**
     * sync grid with record
     * -> update record to/cc/bcc
     */
    syncRecipientsToRecord: function() {
        // update record recipient fields
        this.record.data.to = [];
        this.record.data.cc = [];
        this.record.data.bcc = [];
        this.store.each(function(recipient){
            if (recipient.data.address != '') {
                this.record.data[recipient.data.type].push(recipient.data.address);
            }
        }, this);
    },

    /**
     * sync grid with record
     * -> update store
     */
    syncRecipientsToStore: function(fields, record, setHeight) {
        record = record || this.record;
        
        Ext.each(fields, function(field) {
            this._addRecipients(record.get(field), field);
        }, this);
        this.store.sort('address');
        
        if (setHeight && setHeight === true) {
            this.setFixedHeight(true);
        }
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
                this.startEditing.defer(50, this, [o.row +1, o.column]);
            } else if (o.value == '') {
                this.store.remove(o.record);
            }
            this.setFixedHeight(false);
            this.ownerCt.doLayout();
            this.searchCombo.focus.defer(80, this.searchCombo);
        }
    },    
    
    onBeforeEdit: function(o) {
        Ext.fly(this.getView().getCell(o.row, o.column)).addClass('x-grid3-td-address-editing');
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
    
     onValidateEdit: function(o) {
        Ext.fly(this.getView().getCell(o.row, o.column)).removeClass('x-grid3-td-address-editing');
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
            recipients = Ext.unique(recipients);
            for (var i=0; i < recipients.length; i++) {
                this.store.add(new Ext.data.Record({type: type, 'address': recipients[i]}));
            }
        }
    }
});

Ext.reg('felamimailrecipientgrid', Tine.Felamimail.RecipientGrid);
