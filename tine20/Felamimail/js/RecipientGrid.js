/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2013 Metaways Infosystems GmbH (http://www.metaways.de)
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
 * @author      Philipp Schüle <p.schuele@metaways.de>
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
     * message compose dlg
     * @type Tine.Felamimail.MessageEditDialog
     */
    composeDlg: null,
    
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
     * @cfg {Boolean} autoStartEditing
     */
    autoStartEditing: true,
    
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

    skipAutoCheckboxSelection: true,
    enableDrop: true,
    ddGroup: 'recipientDDGroup',
    
    /**
     * options are saved in onAfterEdit
     * 
     * @type Ext.data.Record
     */
    lastEditedRecord: null,
    
    /**
     * grid view config
     * - do not mark records as dirty
     * 
     * @type Object
     */
    viewConfig: {
        markDirty: false
    },
    
    /**
     * @private
     */
    initComponent: function() {
        
        this.initStore();
        this.initColumnModel();
        this.initActions();
        this.sm = new Ext.grid.RowSelectionModel();
        
        Tine.Felamimail.RecipientGrid.superclass.initComponent.call(this);
        
        this.on('rowcontextmenu', this.onCtxMenu, this);
        // this is relayed by the contact search combo
        this.on('contextmenu', this.onCtxMenu.createDelegate(this, [this, null], 0), this);
        
        this.on('beforeedit', this.onBeforeEdit, this);
        this.on('afteredit', this.onAfterEdit, this);
    },
    
    /**
     * show context menu
     * 
     * @param {Tine.Felamimail.RecipientGrid} grid
     * @param {Number} row
     * @param {Event} e
     */
    onCtxMenu: function(grid, row, e) {
        var activeRow = (row === null) ? ((this.activeEditor) ? this.activeEditor.row : 0) : row;
        
        e.stopEvent();
        var selModel = grid.getSelectionModel();
        if (! selModel.isSelected(activeRow)) {
            selModel.selectRow(activeRow);
        }
        
        var record = this.store.getAt(activeRow);
        if (record) {
            this.action_remove.setDisabled(record.get('address') == '');
            this.contextMenu.showAt(e.getXY());
        }
    },
    
    /**
     * init store
     * @private
     */
    initStore: function() {
        
        if(!this.record) {
            this.initStore.defer(200, this);
            return false;
        }
        
        this.store = new Ext.data.SimpleStore({
            fields   : ['type', 'address']
        });
        
        // init recipients (on reply/reply to all)
        this.syncRecipientsToStore(['to', 'cc', 'bcc']);
        
        this.store.add(new Ext.data.Record({type: 'to', 'address': '', 'contact_type': 'contact'}));
        
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
            lazyInit: false,
            listeners: {
                scope: this,
                specialkey: this.onSearchComboSpecialkey,
                select: this.onSearchComboSelect,
                blur: function(combo) {
                    Tine.log.debug('Tine.Felamimail.MessageEditDialog::onSearchComboBlur()');
                    this.getView().el.select('.x-grid3-td-address-editing').removeClass('x-grid3-td-address-editing');
                    // need to update record because it might not be updated otherwise (for example: delete value, click into next row or subject)
                    if (this.activeEditor) {
                        var value = combo.getRawValue();
                        Tine.log.debug('Tine.Felamimail.MessageEditDialog::onSearchComboBlur() -> current value: ' + value);
                        if (value !== null && this.activeEditor.record.get('address') != value) {
                            this.activeEditor.record.set('address', value);
                        }
                        if (!this.searchCombo.getValueIsList()) {
                            this.onSearchComboSelect(combo);
                        }
                    }
                    this.stopEditing();
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
                        qtip = Ext.util.Format.htmlEncode(app.i18n._('Click here to set To/CC/BCC.'));

                    switch(value) {
                        case 'to':
                            result = Ext.util.Format.htmlEncode(app.i18n._('To:'));
                            break;
                        case 'cc':
                            result = Ext.util.Format.htmlEncode(app.i18n._('Cc:'));
                            break;
                        case 'bcc':
                            result = Ext.util.Format.htmlEncode(app.i18n._('Bcc:'));
                            break;
                    }
                    
                    result = Tine.Tinebase.common.cellEditorHintRenderer(result);
                    
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
                    lazyInit      : false,
                    store         : [
                        ['to',  app.i18n._('To:')],
                        ['cc',  app.i18n._('Cc:')],
                        ['bcc', app.i18n._('Bcc:')]
                    ],
                    listeners: {
                        focus: function(combo) {
                            combo.onTriggerClick();
                        }
                    }
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
     * specialkey is pressed in search combo
     * 
     * @param {Combo} combo
     * @param {Event} e
     */
    onSearchComboSpecialkey: function(combo, e) {
        var value = combo.getValue();
        Tine.log.debug('Tine.Felamimail.MessageEditDialog::onSearchComboSpecialkey() -> current value: ' + value);
        
        if (e.getKey() == e.ENTER) {
            Tine.log.debug('Tine.Felamimail.MessageEditDialog::onSearchComboSpecialkey() -> ENTER');
            if (this.activeEditor && value !== null && this.activeEditor.record.get('address') != value) {
                this.activeEditor.record.set('address', value);
            }
            if (!combo.getValueIsList()) {
                this.onSearchComboSelect(combo);
            }
        } else if (this.activeEditor && e.getKey() == e.BACKSPACE) {
            Tine.log.debug('Tine.Felamimail.MessageEditDialog::onSearchComboSpecialkey() -> BACKSPACE');
            // remove row on backspace if we have more than 1 rows in grid
            if (value == '' && this.store.getCount() > 1 && this.activeEditor.row > 0) {
                this.store.remove(this.activeEditor.record);
                this.activeEditor.row -= 1;
                this.setFixedHeight(false);
                this.ownerCt.doLayout();
                this.startEditing.defer(50, this, [this.activeEditor.row, this.activeEditor.col]);
                return true;
            }
        } else if (this.activeEditor && e.getKey() == e.ESC) {
            // TODO should ESC close the compose window if search combo is already empty?
//            if (value == '') {
//                this.fireEvent('specialkey', this, e);
//            }
            this.startEditing.defer(50, this, [this.activeEditor.row, this.activeEditor.col]);
            return true;
        }

        // jump to subject if we are in the last row and it is empty OR TAB was pressed
        if (this.activeEditor && e.getKey() == e.TAB || (e.getKey() == e.ENTER && value == '' && this.store.getCount() == this.activeEditor.row + 1)) {
            Tine.log.debug('Tine.Felamimail.MessageEditDialog::onSearchComboSpecialkey() -> TAB');
            if (value !== null && this.activeEditor.record.get('address') != value) {
                this.activeEditor.record.set('address', value);
            }
            
            this.fireEvent('specialkey', combo, e);
            this.getView().el.select('.x-grid3-td-address-editing').removeClass('x-grid3-td-address-editing');
            this.stopEditing();
            return false;
        }
    },
    
    onSearchComboSelect: function(combo) {
        Tine.log.debug('Tine.Felamimail.MessageEditDialog::onSearchComboSelect()');

        var selectedRecord = combo.selectedRecord;
        var value = combo.getValue();
        
        if (combo.getValueIsList()) {
            var emails = value.split(",");
            emails.sort();
            this._addRecipients(emails, this.activeEditor ? this.activeEditor.record.data.type : this.lastActiveEditor.record.data.type);
            this.setFixedHeight(false);
            this.ownerCt.doLayout();
            this.store.remove(this.activeEditor ? this.activeEditor.record : this.lastEditedRecord);
            this.addRowAndDoLayout(this.activeEditor ? this.activeEditor.record : this.lastEditedRecord);
        } else {
            if (value !== '') {
                this.addRowAndDoLayout(this.activeEditor ? this.activeEditor.record : this.lastEditedRecord);
            }
        }
    },
    
    /**
     * adds row and adjusts layout
     * 
     * @param {} oldRecord
     */
    addRowAndDoLayout: function(oldRecord) {
        var emptyRecord = this.store.getAt(this.store.findExact('address', ''));
        if (! emptyRecord) {
            emptyRecord = new Ext.data.Record({type: oldRecord.data.type, 'address': ''});
            this.store.add(emptyRecord);
            this.store.commitChanges();
            this.setFixedHeight(false);
            this.ownerCt.doLayout();
        }
        
        this.startEditing.defer(50, this, [this.store.indexOf(emptyRecord), 1]);
    },
    
    /**
     * start editing (check if message compose dlg is saving/sending first)
     * 
     * @param {} row
     * @param {} col
     */
    startEditing: function(row, col) {
        if (! this.composeDlg || ! this.composeDlg.saving) {
            Tine.Felamimail.RecipientGrid.superclass.startEditing.apply(this, arguments);
        }
    },
    
    /**
     * init actions / ctx menu
     * @private
     */
    initActions: function() {
        this.action_remove = new Ext.Action({
            text: i18n._('Remove'),
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
        
        // kill x-scrollers
        this.el.child('div[class=x-grid3-scroller]').setStyle('overflow-x', 'hidden');
        
        if (this.autoStartEditing && this.store.getCount() == 1) {
            this.startEditing.defer(200, this, [0, 1]);
        }
        
        this.setFixedHeight(true);
    },

    /**
     * set grid to fixed height if it has more than X records
     *  
     * @param {} doLayout
     */
    setFixedHeight: function (doLayout) {
        if (this.store.getCount() > this.numberOfRecordsForFixedHeight) {
            this.setHeight(155);
        } else {
            this.setHeight(this.store.getCount()*24 + 1);
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
        Tine.Tinebase.common.assertComparable(this.record.data.to);
        Tine.Tinebase.common.assertComparable(this.record.data.cc);
        Tine.Tinebase.common.assertComparable(this.record.data.bcc);

        // update record recipient fields
        this.record.set('to', Tine.Tinebase.common.assertComparable([]));
        this.record.set('cc', Tine.Tinebase.common.assertComparable([]));
        this.record.set('bcc', Tine.Tinebase.common.assertComparable([]));
        this.store.each(function(recipient){
            if (recipient.data.address != '') {
                this.record.data[recipient.data.type].push(recipient.data.address);
            }
        }, this);
    },

    /**
     * sync grid with record
     * -> update store
     * 
     * @param {Array} fields
     * @param {Tine.Felamimail.Model.Message} record
     * @param {Boolean} setHeight
     * @param {Boolean} clearStore
     */
    syncRecipientsToStore: function(fields, record, setHeight, clearStore) {
        if (clearStore) {
            this.store.removeAll(true);
        }
        
        record = record || this.record;
        
        Ext.each(fields, function(field) {
            this._addRecipients(record.get(field), field);
        }, this);
        this.store.sort('address');
        
        if (clearStore) {
            this.store.add(new Ext.data.Record({type: 'to', 'address': ''}));
        }
        
        if (setHeight && setHeight === true) {
            this.setFixedHeight(true);
        }
    },

    // save selected record for usage in onAfterEdit
    onEditComplete: function(ed, value, startValue) {
        var _ = window.lodash,
            selectedRecord = _.get(ed, 'field.selectedRecord'),
            row = ed.row,
            rowRecord = this.store.getAt(row);

        rowRecord.selectedRecord = selectedRecord;

        Tine.Felamimail.RecipientGrid.superclass.onEditComplete.call(this, ed, value, startValue);
    },

    /**
     * after edit
     * 
     * @param {} o
     */
    onAfterEdit: function(o) {
        if (o.field == 'address') {
            Tine.log.debug('Tine.Felamimail.MessageEditDialog::onAfterEdit()');
            Tine.log.debug(o);
            
            Ext.fly(this.getView().getCell(o.row, o.column)).removeClass('x-grid3-td-address-editing');
            
            this.lastEditedRecord = o.record;
        }
    },
    
    /**
     * delete handler
     */
    onDelete: function(btn, e) {
        var sm = this.getSelectionModel();
        var records = sm.getSelections();
        Ext.each(records, function(record) {
            if (record.get('address') != '' && this.store.getCount() > 1) {
                this.store.remove(record);
                this.store.fireEvent('update', this.store);
            }
        }, this);
        
        this.setFixedHeight(true);
    },
    
    /**
     * on before edit
     * 
     * @param {} o
     */
    onBeforeEdit: function(o) {
        this.getView().el.select('.x-grid3-td-address-editing').removeClass('x-grid3-td-address-editing');
        Ext.fly(this.getView().getCell(o.row, o.column)).addClass('x-grid3-td-address-editing');
    },
    
    
    /**
     * add recipients to grid store
     * 
     * @param {Array} recipients
     * @param {String} type
     * @private
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
