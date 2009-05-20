/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:MessageEditDialog.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
 * TODO         add additional row on TAB in last row
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
    
    /**
     * the message record
     * @type 
     */
    record: null,
    
    /**
     * config values
     */
    autoExpandColumn: 'address',
    clicksToEdit:1,
    //margins : '2 5 2 5',
    height: 88,
    //header: false,
    frame: true,
    border: false,
    //region: 'center',
    //layout: 'border',
    
    /**
     * init
     */
    initComponent: function() {
        
        //this.view = new Ext.grid.GridView({});
        this.initStore();
        this.initColumnModel();
        
        //console.log(this.record);
        
        Tine.Felamimail.RecipientGrid.superclass.initComponent.call(this);
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
        
        // TODO init recipients (on reply/reply to all)
        for (var i=0; i < 3; i++) {
            this.store.add(new Ext.data.Record({type: 'to', 'address': ''}));
        }
        
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
                id: 'address',
                dataIndex: 'address',
                width: 40,
                // TODO use searchable combo here
                editor: new Ext.form.TextField({})  
            }
        ]);
    },
    
    /**
     * store has been updated
     * -> update record to/cc/bcc
     * 
     * @param {} store
     * @param {} record
     * @param {} operation
     */
    onUpdateStore : function(store, record, operation)
    {
        this.record.data.to = [];
        this.record.data.cc = [];
        this.record.data.bcc = [];
        
        store.each(function(recipient){
            if (recipient.data.address != '') {
                this.record.data[recipient.data.type].push(recipient.data.address);
            }
        }, this);
        //store.commitChanges();
        //console.log(this.record);
    }
});
