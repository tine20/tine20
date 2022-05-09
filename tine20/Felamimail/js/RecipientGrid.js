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
    autoStartEditing: false,
    
    /**
     * @cfg {String} autoExpandColumn
     * auto expand column of grid
     */
    autoExpandColumn: 'address',
    autoExpandMax : 2000,
    
    /**
     * @cfg {Number} clicksToEdit
     * clicks to edit for editor grid panel
     */
    clicksToEdit: 2,
    
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

    allowTypeSelect: true,
    
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
        this.on('cellclick', this.onCellClick, this);
        this.on('beforeedit', this.onBeforeEdit, this);
        this.on('afteredit', this.onAfterEdit, this);
        
        this.postalSubscriptions = [];
        this.postalSubscriptions.push(postal.subscribe({
            channel: "recordchange",
            topic: 'Addressbook.*.*',
            callback: _.bind(this.onContactUpdate, this)
        }));
    },
    
    onContactUpdate: async function (record) {
        if (! record.email) { 
            return;
        }
        
        const updatedContact = {
            'type': record.type,
            'email': record.email,
            'n_fileas': record.n_fileas ?? record.name,
            'email_type': '',
            'name': record.n_fileas ?? record.name,
            'record_id': record.id
        };
        
        const latestEditContact = this.lastEditedRecord.get('address') ?? null;
        
        // we dont know the type by default
        const {results: contacts} = await Tine.Addressbook.searchContactsByRecipientsToken([updatedContact]);
        
        this.store.each(function (item) {
            const addressData = item.get('address');
            _.each(contacts, async (contact, idx) => {
                if (latestEditContact.record_id) {
                    if (addressData.record_id !== contact.record_id
                        || addressData.email_type !== contact.email_type) {
                        return;
                    }

                    const isEmailChanged = addressData.email !== contact.email;
                    const isNameChanged = addressData.name !== contact.name;

                    if (isEmailChanged || isNameChanged) {
                        await this.updateRecipientsToken(item, [contact], item.get('type'));
                    }
                } else {
                    if (idx > 0) {
                        return;
                    }
                    
                    if (addressData.email === latestEditContact.email) {
                        await this.updateRecipientsToken(item, [contact], item.get('type'));
                    }
                }
            })
        }, this);
    },
    
    /**
     * show context menu
     * 
     * @param {Tine.Felamimail.RecipientGrid} grid
     * @param {Number} row
     * @param {Event} e
     */
    onCtxMenu: async function (grid, row, e) {
        const targetInput = e.getTarget('input[type=text]', 1, true);
        if (targetInput) {
            return;
        }
    
        const activeRow = (row === null) ? ((this.activeEditor) ? this.activeEditor.row : 0) : row;
        e.stopEvent();

        const sm = grid.getSelectionModel();
        if (! sm.isSelected(activeRow)) {
            sm.selectRow(activeRow);
        }
    },
    
    /**
     * init store
     * @private
     */
    initStore: async function () {
        if (!this.record) {
            this.initStore.defer(200, this);
            return false;
        }
    
        this.store = new Ext.data.SimpleStore({
            fields: ['type', 'address']
        });
        
        await this.initRecord().then(() => {
            this.syncRecipientsToStore(['to', 'cc', 'bcc'], this.record);
        })
    
        this.store.on('update', this.onUpdateStore, this);
        this.store.on('add', this.onAddStore, this);
    },
    
    /**
     * init cm
     * @private
     */
    initColumnModel: function() {
        const app = Tine.Tinebase.appMgr.get('Felamimail');
        
        this.searchCombo = new Tine.Felamimail.ContactSearchCombo({
            lazyInit: false,
            listeners: {
                scope: this,
                specialkey: this.onSearchComboSpecialkey,
                select: this.onSearchComboSelect,
                render: (combo) => {
                    combo.getEl().on('paste', async (e, dom) => {
                        e = e.browserEvent;
                        const clipboardData = e.clipboardData || window.clipboardData;
                        const pastedData = clipboardData.getData('Text');
                        const value = pastedData.replaceAll("\r\n", ',');
                        
                        if (!this.loadMask) {
                            this.loadMask = new Ext.LoadMask(Ext.getBody(), {msg: app.i18n._('Loading Mail Addresses')});
                        }
                        this.loadMask.show();
           
                        const contacts = await Tine.Tinebase.common.findContactsByEmailString(value);
                        await this.updateRecipientsToken(null, contacts);
                        this.loadMask.hide();
                    })
                },
                blur: async (combo) => {
                    const value = combo.getRawValue();
                    Tine.log.debug('Tine.Felamimail.MessageEditDialog::onSearchComboBlur() -> current value: ' + value);
                    if (value !== '') {
                        const latestAddressData = this.lastEditedRecord ? this.lastEditedRecord.get('address') : '';
                        if (latestAddressData === '') {
                            const recipients = await Tine.Tinebase.common.findContactsByEmailString(value);
                            await this.updateRecipientsToken(null, recipients, null, true);
                        } else {
                            this.addEmptyRowAndDoLayout();
                        }
                    }
                }
            }
        });
        
        this.RecipientTypeCombo = new Ext.form.ComboBox({
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
                    let result = '';
                    const qtip = Ext.util.Format.htmlEncode(app.i18n._('Click here to set To/CC/BCC.'));
                    const type = Ext.util.Format.capitalize(value);
                    
                    result = Ext.util.Format.htmlEncode(app.i18n._(`${type}:`));
                    result = Tine.Tinebase.common.cellEditorHintRenderer(result);
                    
                    return '<div qtip="' + qtip +'">' + result + '</div>';
                },
                editor: this.RecipientTypeCombo
            }, {
                resizable: true,
                menuDisabled: true,
                id: 'address',
                dataIndex: 'address',
                header: 'address',
                editor: this.searchCombo,
                columnWidth: 1,
                renderer: (values) => {
                    if (! values?.email) { 
                        return ''; 
                    }
                    
                    const renderEmail = values.email !== '' && values.name !== '' ? ` < ${values.email} >` : values.email;
                    const iconCls = this.searchCombo.resolveAddressIconCls(values);
                    const note = values?.note && values.note !== '' ? `( ${values.note} )` : '';
                    
                    return iconCls + 
                        '<span class="tinebase-contact-link">' 
                            + '<b>' + Ext.util.Format.htmlEncode(`${values.name}${renderEmail}`) + '</b>'
                            + ' ' + Ext.util.Format.htmlEncode(note)
                        + '</span>'
                        + '<div class="tinebase-contact-link-wait"></div>';
                },
            }
        ]);
    },
    
    /**
     * specialkey is pressed in search combo
     * 
     * @param {Combo} combo
     * @param {Event} e
     */
    onSearchComboSpecialkey: async function (combo, e) {
        const value = combo.getRawValue();
        Tine.log.debug('Tine.Felamimail.MessageEditDialog::onSearchComboSpecialkey() -> current value: ' + value);
    
        if (e.getKey() === e.ENTER) {
            Tine.log.debug('Tine.Felamimail.MessageEditDialog::onSearchComboSpecialkey() -> ENTER');
            
            if (combo?.selectedRecord) { 
                return false; 
            }
            
            if (value !== '') {
                const sm = this.getSelectionModel();
                const records = sm.getSelections();
                const oldRecord = records[0] ?? null;
                const recipients = await Tine.Tinebase.common.findContactsByEmailString(value);
                await this.updateRecipientsToken(oldRecord, recipients, null, true);
            }
        } else if (this.activeEditor && e.getKey() === e.BACKSPACE) {
            Tine.log.debug('Tine.Felamimail.MessageEditDialog::onSearchComboSpecialkey() -> BACKSPACE');
            // remove row on backspace if we have more than 1 rows in grid
            if (value === '' && this.store.getCount() > 1 && this.activeEditor.row > 0) {
                this.store.remove(this.activeEditor.record);
                this.activeEditor.row -= 1;
                const record = this.store.getAt(this.activeEditor.row);
                this.activeEditor.record.set('address', record.data.address);
                const selModel = this.getSelectionModel();
                if (! selModel.isSelected(this.activeEditor.row)) {
                    selModel.selectRow(this.activeEditor.row);
                }
                this.setFixedHeight(false);
                this.ownerCt.doLayout();
                this.startEditing.defer(100, this, [this.activeEditor.row, this.activeEditor.col]);
            }
        } else if (this.activeEditor && e.getKey() === e.ESC) {
            // TODO should ESC close the compose window if search combo is already empty?
//            if (value == '') {
//                this.fireEvent('specialkey', this, e);
//            }
            if (this.activeEditor.startValue === '') {
                this.startEditing.defer(100, this, [this.activeEditor.row, this.activeEditor.col]);
            }
            return true;
        }
    
        // jump to subject if we are in the last row, and it is empty OR TAB was pressed
        if (this.activeEditor && e.getKey() === e.TAB || (e.getKey() === e.ENTER && this.store.getCount() === this.activeEditor?.row + 1)) {
            Tine.log.debug(`Tine.Felamimail.MessageEditDialog::onSearchComboSpecialkey() -> last row`);
            
            if (value === '') {
                this.fireEvent('specialkey', combo, e);
                return false;
            }
        }
    },
    
    onSearchComboSelect: async function (combo, value , startValue) {
        Tine.log.debug('Tine.Felamimail.MessageEditDialog::onSearchComboSelect()');
    
        if (value === '') {
            this.onDelete();
            return;
        }
        
        const contact = value.data ?? '';
        let recipients = [contact];
        
        if (contact?.type === 'group') {
            recipients = await this.resolveGroupContact(contact);
        }
        
        await this.updateRecipientsToken(null, recipients, null, true);
        combo.selectedRecord = null;
    },
    
    /**
     * start editing (check if message compose dlg is saving/sending first)
     * 
     * @param {} row
     * @param {} col
     */
    startEditing: function(row, col) {
        if (!this.allowTypeSelect && col === 0) { 
            return;
        }
        
        if (! this.composeDlg || ! this.composeDlg.saving) {
            Tine.Felamimail.RecipientGrid.superclass.startEditing.apply(this, arguments);
        }
    },
    
    preEditValue : function(r, field){
        const value = r.data[field];
        if (value?.email) {
            return this.autoEncode && Ext.isString(value.email) ? Ext.util.Format.htmlDecode(value.email) : value.email;
        } else {
            Tine.Felamimail.RecipientGrid.superclass.preEditValue.apply(this, arguments);
        }
    },
    
    /**
     * on contact click
     *
     * @param e
     */
    onContactClick : async function (e) {
        const app = Tine.Tinebase.appMgr.get('Felamimail');
        const target = e.target;
        
        //skip non tinebase-contact-link target
        if (! target.className.includes('tinebase-contact-link') && !target?.parentNode?.className.includes('tinebase-contact-link')) {
            return;
        }
        
        const row = this.getView().findRowIndex(target);
        const col = this.getView().findCellIndex(target);
        const record = this.store.getAt(row);
        const position = Ext.fly(target).getXY();
        position[1] = position[1] + Ext.fly(target).getHeight();
        const targetInput = e.getTarget('input[type=text]', 1, true);
        const contact = record.get('address');
        
        if (targetInput || ! record || record.get('address') === '' || col !== 1 || row === false || ! contact?.email) {
            return;
        }
        
        const contactCtxMenu = await Tine.Tinebase.tineInit.getEmailContextMenu(targetInput, contact.email, contact.name, contact.type);
        let index = 0;
        
        switch (contact.type) {
            case 'mailingList':
                const item = new Ext.Action({
                    text: app.i18n._('Resolve to single contact'),
                    iconCls: '',
                    handler: async (item) => {
                        const contacts = await this.resolveGroupContact(contact);
                        await this.updateRecipientsToken(record, contacts, record.get('type'), true);
                    },
                });
    
                contactCtxMenu.insert(index, item);
                index ++;
                break;
            default :
                const {results : contacts} = await Tine.Addressbook.searchContactsByRecipientsToken([contact]);
                
                _.each(contacts, (emailData) => {
                    const selected = emailData.email === contact.email;
                    const emailItem = new Ext.Action({
                        text: `[${emailData.email_type}] -- ${emailData.email}`,
                        iconCls: selected ? 'action_enable' : '',
                        handler: async (item) => {
                            if (! selected) {
                                const newSelectedContact = _.find(contacts, (contact) => {return item.text.includes(contact.email)});
                                await this.updateRecipientsToken(record, [newSelectedContact], record.get('type'));
                            }
                        },
                    });
    
                    contactCtxMenu.insert(index, emailItem);
                    index ++;
                });
                break;
        }
    
        if (index > 0) {
            contactCtxMenu.insert(index, '-');
        }
        
        contactCtxMenu.addMenuItem(this.action_remove);
        contactCtxMenu.showAt(position);
    },
    
    /**
     * resolve group contact
     * 
     * return group members token data
     * @private
     */
    resolveGroupContact: async function (contact) {
        let recipients = [];
        
        if (contact === '') {
            return recipients;
        }
        
        if(contact.type === 'group' || contact.type === 'mailingList') {
            if (contact.name !== '' || contact.email !== '') {
                const {results: contacts} = await Tine.Addressbook.searchContactsByRecipientsToken([contact]);
                const group = contact;
                const contactMembers = contacts[0].emails ?? [];

                _.each(contactMembers, (contact) => {
                    contact.type = group.type + 'Member';
                    contact.note = i18n._('from') + ' ' + group.name;
                })

                recipients = contactMembers;
            }
        }
        
        return recipients;
    },
    
    // private
    onCellDblClick: function onCellClick(g, row, col, e) {
        this.startEditing(row, col);
    },
    
    // private
    onCellClick: async function onCellClick(g, row, col, e) {
        await this.onContactClick(e);
    },
    
    // private
    onMouseDown: async function (e, target) {
        const row = this.getView().findRowIndex(target);
        if (row === false) {
            return;
        }
        
        const col = this.getView().findCellIndex(target);
        this.lastEditedRecord = this.store.getAt(row);
    
        const activeRow = (row === null) ? ((this.activeEditor) ? this.activeEditor.row : 0) : row;
        const selModel = this.getSelectionModel();
    
        if (!selModel.isSelected(activeRow)) {
            selModel.selectRow(activeRow);
        }
    
        if (col === 1 && this.lastEditedRecord.get('address') === '') {
            this.startEditing.defer(50, this, [row, col]);
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
            scope: this,
            disable: false
        });
        
        this.contextMenu = new Ext.menu.Menu({
            items:  this.action_remove
        });
    },

    /**
     * start editing after render
     * @private
     */
    afterRender: async function () {
        Tine.Felamimail.RecipientGrid.superclass.afterRender.call(this);
        // kill x-scrollers
        this.el.child('div[class=x-grid3-scroller]').setStyle('overflow-x', 'hidden');
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
    
        // update record recipient fields
        this.store.each(function(record, index){
            const addressData = record.get('address') ?? '';
            const type = record.get('type') ?? '';
            
            if (type !== '' && addressData !== '') {
                this.record.data[type].push(addressData);
            }
        }, this);
    },
    
    /**
     * resolve recipients to token
     * 
     */
    initRecord: async function() {
        await _.reduce(['to', 'cc', 'bcc'], (pre, type) => {
            return pre.then(async () => {
                let contacts = this.record.data[type];
                let emails = _.filter(contacts, (addressData) => {
                    return _.isString(addressData)
                });
            
                if (emails.length > 0) {
                    emails = _.join(emails, '>, ');
                    const resolvedContacts = await Tine.Tinebase.common.findContactsByEmailString(emails);
                    _.each(contacts, (addressData, idx) => {
                        if (_.isString(addressData)) {
                            contacts[idx] = _.find(resolvedContacts, (contact) => {
                                return addressData.includes(contact.email);
                            });
                        }
                    })
                }
    
                this.record.data[type] = contacts;
            })
        }, Promise.resolve());
    },

    /**
     * sync grid with record
     * -> update store
     * 
     * @param {Array} types
     * @param {Tine.Felamimail.Model.Message} record
     * @param {Boolean} setHeight
     * @param {Boolean} clearStore
     */
    syncRecipientsToStore: function (types, record, setHeight, clearStore) {
        if (clearStore) {
            this.store.removeAll(true);
        }
    
        record = record || this.record;
    
        _.each(types, (type) => {
            const contacts = record.data[type];
            _.each(contacts, (addressData) => {
                this.store.add(new Ext.data.Record({type: type, 'address': addressData}));
            })
        });
    
        this.store.sort('address');
    
        if (setHeight && setHeight === true) {
            this.setFixedHeight(true);
        }
    
        this.addEmptyRowAndDoLayout(true);
    },

    // save selected record for usage in onAfterEdit
    onEditComplete: async function (ed, value, startValue) {
        const row = ed.row;
        const col = ed.col;
        const rowRecord = this.store.getAt(row);
        let contact = rowRecord?.data?.address ?? '';
        rowRecord.selectedRecord = contact ?? null;
    
        if (col === 1) {
            if (contact !== '' && startValue === contact.email) {
                return;
            }
            value = contact;
        }
        
        Tine.Felamimail.RecipientGrid.superclass.onEditComplete.call(this, ed, value, startValue);
    },
    
  

    /**
     * after edit
     * 
     * @param {} o
     */
    onAfterEdit: async function (o) {
        Tine.log.debug('Tine.Felamimail.MessageEditDialog::onAfterEdit()');
        Tine.log.debug(o);
        
        if (o.field === 'address') {
            Ext.fly(this.getView().getCell(o.row, o.column)).removeClass('x-grid3-td-address-editing');
            if (o.originalValue !== '' && o.value === '') {
                this.store.remove(o.record);
                this.setFixedHeight(true);
            }
        
            this.lastEditedRecord = o.record;
        
            if (o.originalValue !== '') {
                this.addEmptyRowAndDoLayout(true);
            }
        }
    
        if (o.field === 'type') {
            if (o.value !== '' && o.record.get('address') !== '') {
                const contact = o.record.get('address');
                await this.updateRecipientsToken(o.record, [contact], o.value);
            }
        }
    },
    
    /**
     * delete handler
     */
    onDelete: function(btn, e) {
        const sm = this.getSelectionModel();
        const records = sm.getSelections();
        Ext.each(records, function(record) {
            if (record.get('address') !== '' && this.store.getCount() > 0) {
                this.store.remove(record);
                this.store.fireEvent('update', this.store);
            }
        }, this);
        
        this.setFixedHeight(true);
        this.addEmptyRowAndDoLayout(true);
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
     */
    updateRecipientsToken: async function (oldContact = null, recipients, type, autoStartEdit = false) {
        if (recipients.length === 0) {
            return;
        }
        
        const sm = this.getSelectionModel();
        const records = sm.getSelections();
        const oldRecord = this.store.indexOf(oldContact) > -1 ? oldContact : records[0];
        const index = oldRecord ? this.store.indexOf(oldRecord) : -1;
        
        if (!type) {
            type = this.activeEditor ? this.activeEditor.record.data.type 
                : this.lastActiveEditor ? this.lastActiveEditor.record.data.type 
                : this.lastEditedRecord ? this.lastEditedRecord.data.type
                : 'to';
        }

        const updatedRecipients = [];
        const duplicatedRecipients = [];
    
        if (index > -1 && oldRecord.get('address') !== '') {
            this.store.remove(oldRecord);
        }

        _.each(recipients, async (recipient) => {
            if (!recipient.email) {
                return;
            }
            
            const existingRecipient = this.store.findBy(function (record) {
                const addressData = record.get('address') ?? '';
                    if (addressData !== ''
                        && addressData?.email === recipient.email
                        && addressData?.name === recipient.name
                        && record.get('type') === type) {
                        duplicatedRecipients.push(record);
                        return true;
                    }
            }, this);
    
            if (existingRecipient === -1) {
                const record = new Ext.data.Record({
                    type: type,
                    'address': recipient
                });
                updatedRecipients.push(record);
                this.lastEditedRecord = record;
            }
        })
        
        if (updatedRecipients.length > 0) {
            if (index > -1) {
                this.store.insert(index, updatedRecipients);
                
                _.each(updatedRecipients, (recipient, rowCount) => {
                    const row = this.getView().getRow(index + rowCount);
                    if (row) {
                        Ext.fly(row).highlight();
                    }
                })
            } else {
                this.store.add(updatedRecipients);
            }
        }

        _.each(duplicatedRecipients, (record) => {
            const row = this.getView().getRow(this.store.indexOf(record));
            if (row) {
                Ext.fly(row).highlight('',{duration: 2});
                this.getView().focusRow(row);
            }
        })
        
        if (duplicatedRecipients.length) {
            await new Promise((resolve) => {
                setTimeout(resolve, 1000)
            });
        }
        
        this.addEmptyRowAndDoLayout(autoStartEdit);
    },
    
    /**
     * adds row and adjusts layout
     *
     * @param autoStartEdit
     */
    addEmptyRowAndDoLayout: function(autoStartEdit = false) {
        const record = this.activeEditor ? this.activeEditor.record : this.lastEditedRecord;
        const existingEmptyRecords = this.store.queryBy(function (record) {
            if (! record?.data?.address || record.data.address === '') {
                return true;
            }
        }, this);
        
        const emptyRecord = new Ext.data.Record({
            type: record?.data?.type ?? 'to',
            'address': ''
        });
        
        _.each(existingEmptyRecords.items, (record) => {
            this.store.remove(record);
        })
        
        this.store.add(emptyRecord);
        this.store.commitChanges();
        this.setFixedHeight(false);
        
        if (this.ownerCt) {
            this.ownerCt.doLayout();
        }
    
        const selModel = this.getSelectionModel();
        const emptyRecordIdx = this.store.indexOf(emptyRecord);
    
        if (!selModel.isSelected(emptyRecordIdx)) {
            selModel.selectRow(emptyRecordIdx);
        }

        if (autoStartEdit) {
            this.startEditing.defer(100, this, [emptyRecordIdx, 1]);
        }
    },
    
    disableRecipientsCombo(disable) {
        if (this.store && disable) {
            _.map(this.store.data.items, (record, index) => {
                record.data.type = 'to';
                this.store.removeAt(index);
                this.store.insert(index,record);
            })
        }
        
        this.allowTypeSelect = !disable;
    }
});

Ext.reg('felamimailrecipientgrid', Tine.Felamimail.RecipientGrid);
