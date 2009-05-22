/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:MessageEditDialog.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
 * TODO         make it work
 * TODO         init attachments (on forward)
 */
 
Ext.namespace('Tine.Felamimail');

/**
 * attachment grid for compose dialog
 * 
 * @class Tine.Felamimail.AttachmentGrid
 * @extends Ext.grid.GridPanel
 */
Tine.Felamimail.AttachmentGrid = Ext.extend(Ext.grid.GridPanel, {
    
    id: 'felamimail-attachment-grid',
    
    /**
     * actions
     * 
     * @type Object
     */
    actions: {
        add: null,
        remove: null
    },
    
    /**
     * config values
     */
    height: 100,
    header: false,
    frame: true,
    border: false,
    deferredRender: false,
    loadMask: true,
    
    /**
     * init
     */
    initComponent: function() {
        
        this.initToolbar();
        this.initStore();
        this.initColumnModel();
        
        Tine.Felamimail.AttachmentGrid.superclass.initComponent.call(this);
    },
    
    /**
     * init toolbar
     */
    initToolbar: function() {
        this.actions.add = new Ext.Action({
            text: _('Add Attachment'),
            iconCls: 'actionAdd',
            scope: this,
            plugins: [new Ext.ux.file.BrowsePlugin({})],
            handler: this.handlers.add
        });

        this.actions.remove = new Ext.Action({
            text: _('Remove Attachment'),
            iconCls: 'actionRemove',
            scope: this,
            handler: this.handlers.remove
        });
        
        this.tbar = [                
            this.actions.add,
            this.actions.remove
        ]; 
    },
    
    /**
     * init store
     */
    initStore: function() {
        this.store = new Ext.data.SimpleStore({
            fields: Tine.Felamimail.Model.Attachment
        });
        
        // init attachments (on forward)
        /*
        if (this.record.get('to') && this.record.get('to') != '') {
            this.store.add(new Ext.data.Record({type: 'to', 'address': this.record.get('to')}));
            this.record.data.to = [this.record.get('to')];
        } else {
            this.store.add(new Ext.data.Record({type: 'to', 'address': ''}));
        }
        */
        this.store.on('update', this.onUpdateStore, this);
    },
    
    /**
     * init cm
     */
    initColumnModel: function() {
        this.cm = new Ext.grid.ColumnModel([
            {
                resizable: true,
                id: 'filename',
                dataIndex: 'filename',
                width: 300,
                header: 'filename'
            },{
                resizable: true,
                id: 'size',
                dataIndex: 'size',
                width: 100,
                header: 'size',
                renderer: Ext.util.Format.fileSize
            },{
                resizable: true,
                id: 'type',
                dataIndex: 'type',
                width: 100,
                header: 'type'
                // TODO show type icon
                //renderer: Ext.util.Format.fileSize
            }
        ]);
    },

    /**
     * event handlers
     */
    handlers: {   
        
        /**
         * upload new attachment and add to store
         * 
         * @param {} _button
         * @param {} _event
         */
        add: function(_button, _event) {

            var input = _button.detachInputFile();
            var uploader = new Ext.ux.file.Uploader({
                input: input
            });
            uploader.on('uploadcomplete', function(uploader, file){
                //console.log(record);
                this.loadMask.hide();
                
                var file = new Tine.Felamimail.Model.Attachment({
                    filename: file.get('tempFile').name,
                    size: file.get('tempFile').size,
                    type: file.get('tempFile').type
                });
                this.store.add(file);
                
            }, this);
            uploader.on('uploadfailure', this.onUploadFail, this);
            
            this.loadMask.show();
            uploader.upload();
        },

        /**
         * remove attachment from store
         * 
         * @param {} _button
         * @param {} _event
         */
        remove: function(_button, _event) {
            console.log('remove');
        }
    },
    
    /**
     * store has been updated
     * -> update record attachments
     * 
     * @param {} store
     * @param {} record
     * @param {} operation
     * 
     * TODO implement
     */
    onUpdateStore: function(store, record, operation)
    {
        
    },

    /**
     * @private
     */
    onUploadFail: function() {
        Ext.MessageBox.alert(_('Upload Failed'), _('Could not upload attachment. Please notify your Administrator')).setIcon(Ext.MessageBox.ERROR);
    }
});
