/*
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.namespace('Tine.Sipgate');

/**
 * admin settings panel
 * 
 * @namespace   Tine.Sipgate
 * @class       Tine.Sipgate.AssignLinesGrid
 * @extends     Ext.grid.EditorGridPanel
 * 
 * <p>Sipgate Assign Accounts Panel</p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * Create a new Tine.Sipgate.AssignLinesGrid
 */
 
Tine.Sipgate.AssignLinesGrid = Ext.extend(Ext.grid.EditorGridPanel, {

    frame: true,
    border: true,
    autoScroll: true,
    layout: 'fit',
    clicksToEdit: 1,
    mode: 'local',
    
    defaultSortInfo: {field: 'sip_uri', direction: 'ASC'},
    autoExpandColumn: 'e164_in',
    
    recordClass: Tine.Sipgate.Model.Line,
    recordProxy: Tine.Sipgate.lineBackend,
    /*
     * config
     */
    app: null,
    editDialog: null,
    store: null,
    
    initComponent: function() {
        if (!this.app) {
            this.app = Tine.Tinebase.appMgr.get(this.appName);
        }
        
        this.on('afteredit', this.onAfterRowEdit, this);

        this.title = this.app.i18n._('Assign Lines');
        this.cm = this.getColumnModel();
        Tine.Sipgate.AssignLinesGrid.superclass.initComponent.call(this);
    },
    
    onClose: function() {
        this.fireEvent('cancel');
        this.purgeListeners();
        this.window.close();
    },
    
    onRecordLoad: function() {
        if(! this.store) {
            this.store = new Tine.Tinebase.data.RecordStore({
                recordProxy: this.recordProxy,
                recordClass: this.recordClass,
                autoSave: false
            }, this);
        } else {
            this.store.removeAll(true);
        }

        Ext.each(this.editDialog.record.get('lines'), function(ar) {
            this.store.add(new Tine.Sipgate.Model.Line(ar));
        }, this);
    },

    
    
    /**
     * returns column model
     * 
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumnModel: function() {
        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: false,
                width: 160
            }, 
            columns: [
                { id: 'id', header: this.app.i18n._('Id'), dataIndex: 'id', hidden: true },
                { id: 'tos', header: this.app.i18n._('Type'), dataIndex: 'tos', width: 100, renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('Sipgate', 'connectionTos') },
                { id: 'uri_alias', header: this.app.i18n._('Uri Alias'), dataIndex: 'uri_alias' },
                { id: 'sip_uri', header: this.app.i18n._('Sip Uri'), dataIndex: 'sip_uri' },
                { id: 'e164_out', header: this.app.i18n._('Outgoing'), dataIndex: 'e164_out', width: 100, disabled: true},
                { id: 'e164_in', header: this.app.i18n._('Incoming'), dataIndex: 'e164_in', width: 100, disabled: true, 
                  renderer: Tine.Sipgate.common.renderE164In
                  },
                { id: 'user_id', dataIndex: 'user_id', header: this.app.i18n._('Assigned User'), renderer: Tine.Tinebase.common.accountRenderer,
                  scope: this, editor: Tine.widgets.form.RecordPickerManager.get('Addressbook', 'Contact', {
                    useAccountRecord: true,
                    userOnly: true,
                    blurOnSelect: true,
                    listeners: {
                        scope: this,
                        select: this.onChange
                    }
                    })
                }
           ]
       });
    },

    onChange: function(combo) {
        this.lastSelectedRecord = combo.selectedRecord;
    },
    
    onAfterRowEdit: function(o) {
        if(!this.lastSelectedRecord) {
            this.onAfterRowEdit.defer(100, this, [o]);
            return;
        }

        var accountStruct =  {
            accountId: this.lastSelectedRecord.get('account_id'),
            accountDisplayName: this.lastSelectedRecord.get('n_fileas'),
            accountFullName: this.lastSelectedRecord.get('n_fn'),
            accountLastName: this.lastSelectedRecord.get('n_family'),
            accountFirstName: this.lastSelectedRecord.get('n_given'),
            contactId: this.lastSelectedRecord.get('id')
        }
        
        o.record.set('user_id', accountStruct);
        o.grid.store.removeAt(o.row);
        o.grid.store.insert(o.row, o.record);
        this.lastSelectedRecord = null;
        this.view.refresh();
    }
});
