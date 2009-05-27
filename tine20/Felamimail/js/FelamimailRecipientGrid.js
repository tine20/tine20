/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:MessageEditDialog.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
 * TODO         add name to email address for display
 */
 
Ext.namespace('Tine.Felamimail');

/**
 * grid panel for to/cc/bcc recipients
 * 
 * @class Tine.Felamimail.RecipientGrid
 * @extends Ext.grid.EditorGridPanel
 */
Tine.Felamimail.RecipientGrid = Ext.extend(Ext.grid.EditorGridPanel, {
    
    id: 'felamimail-recipient-grid',
    
    /**
     * the message record
     * @type Tine.Felamimail.Model.Message
     */
    record: null,
    
    /**
     * config values
     */
    autoExpandColumn: 'address',
    clicksToEdit:1,
    height: 80,
    header: false,
    frame: true,
    border: false,
    deferredRender: false,
    
    /********************** init **************************/
    
    /**
     * init
     */
    initComponent: function() {
        
        //this.view = new Ext.grid.GridView({});
        this.initStore();
        this.initColumnModel();
        
        //console.log(this.record);
        
        Tine.Felamimail.RecipientGrid.superclass.initComponent.call(this);
        
        //this.on('afterlayout', this.onAfterlayout, this);
    },
    
    /**
     * init store
     */
    initStore: function() {
        //this.store = new Ext.data.JsonStore({
        this.store = new Ext.data.SimpleStore({
            //id       : 'id',
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
     */
    initColumnModel: function() {
        this.cm = new Ext.grid.ColumnModel([
            {
                resizable: true,
                id: 'type',
                dataIndex: 'type',
                width: 80,
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
                width: 40,
                header: 'address',
                editor: new Tine.Felamimail.ContactSearchCombo({})
            }
        ]);
    },
    
    /********************** events **************************/
    
    /**
     * on render event
     * 
     * @param {} ct
     * @param {} position
     * 
     * TODO focus first 'To' address when composing new mail (it isn't working yet :( )
     * TODO don't focus search combo if replying
     * TODO try afterRender!
     */
    onAfterlayout: function(ct, position){
        //Tine.Felamimail.RecipientGrid.superclass.onRender.call(this, ct, position);
        
        // TODO focus first 'To' row / second column (address)
        //this.startEditing.defer(1500, this, 0, 1);
        console.log('after');
        
        if (this.store.count() == 1) {
            console.log('start');
            this.startEditing(0, 1);
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
     */
    onUpdateStore: function(store, record, operation)
    {
        if (operation == 'edit') {
            this.record.data.to = [];
            this.record.data.cc = [];
            this.record.data.bcc = [];
            
            store.each(function(recipient){
                if (recipient.data.address != '') {
                    this.record.data[recipient.data.type].push(recipient.data.address);
                }
            }, this);

            // add additional row if new address has been added
            if (record.modified.address == '') {
                store.add(new Ext.data.Record({type: 'to', 'address': ''}));
            }
            
            store.commitChanges();
        }
    },
    
    /********************** helper funcs **************************/
 
    /**
     * add recipients to grid store
     * 
     * @param {Array} recipients
     * @param {String} type
     * 
     * TODO get own email address and don't add it to store
     */
    _addRecipients: function(recipients, type) {
        if (recipients) {
            for (var i=0; i<recipients.length; i++) {
                this.store.add(new Ext.data.Record({type: type, 'address': recipients[i]}));
                //this.record.data[type].push(recipients[i]);
            }
        }
    }
});

/**
 * contact email search combo
 * 
 * @class Tine.Felamimail.ContactSearchCombo
 * @extends Tine.Addressbook.SearchCombo
 * 
 * TODO what about email_home?
 */
Tine.Felamimail.ContactSearchCombo = Ext.extend(Tine.Addressbook.SearchCombo, {

    forceSelection: false,
    
    //private
    initComponent: function() {
        this.tpl = new Ext.XTemplate(
            '<tpl for="."><div class="search-item">',
                '{[this.encode(values.n_fileas)]}',
                ' (<b>{[this.encode(values.email)]}</b>)',
                /*
                '<table cellspacing="0" cellpadding="2" border="0" style="font-size: 11px;" width="100%">',
                    '<tr>',
                        //'<td width="50%"><b>{[this.encode(values.n_fileas)]}</b></td>',
                        //'<td width="50%"><b>{[this.encode(values.email)]}</b></td>',
                        '<td width="40%"><b>{[this.encode(values.n_fileas)]}</b><br/>{[this.encode(values.org_name)]}</td>',
                        '<td width="40%">{[this.encode(values.email)]}<br/>',
                            '{[this.encode(values.email_home)]}</td>',
                        '<td width="20%">',
                            '<img width="45px" height="39px" src="{jpegphoto}" />',
                        '</td>',
                    '</tr>',
                '</table>',
                */
            '</div></tpl>',
            {
                encode: function(value) {
                     if (value) {
                        return Ext.util.Format.htmlEncode(value);
                    } else {
                        return '';
                    }
                }
            }
        );
        
        Tine.Felamimail.ContactSearchCombo.superclass.initComponent.call(this);
    },
    
    /**
     * override default onSelect
     * - set email/name as value
     * 
     * @param {} record
     * 
     * TODO add name
     * TODO make it possible to choose between office/home email addresses
     */
    onSelect: function(record) {
        if (record.get('email') != '') {
            this.setValue(record.get('email'));
        } /*else {
            this.setValue(record.get('email_home'));
        } */
        this.collapse();
        this.fireEvent('blur', this);
    }    
});

