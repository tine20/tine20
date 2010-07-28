/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
 
Ext.ns('Tine.Felamimail')


Tine.Felamimail.MessageDisplayDialog = Ext.extend(Tine.Felamimail.GridDetailsPanel ,{
    /**
     * @cfg {Tine.Felamimail.Model.Message}
     */
    record: null,
    
    autoScroll: false,
    
    initComponent: function() {
        this.addEvents('remove');
        
        this.app = Tine.Tinebase.appMgr.get('Felamimail');
        this.i18n = this.app.i18n;
        
        // trick onPrint/onPrintPreview
        this.detailsPanel = this;
        
        this.initActions();
        this.initToolbar();
        
        this.supr().initComponent.apply(this, arguments);
        
    },
    
    initActions: function() {
        this.action_deleteRecord = new Ext.Action({
            text: this.app.i18n._('Delete'),
            handler: this.onMessageDelete.createDelegate(this, [false]),
            iconCls: 'action_delete',
            disabled: this.record.id.match(/_/)
        });
        
        this.action_reply = new Ext.Action({
            text: this.app.i18n._('Reply'),
            handler: this.onMessageReplyTo.createDelegate(this, [false]),
            iconCls: 'action_email_reply',
            disabled: this.record.id.match(/_/)
        });

        this.action_replyAll = new Ext.Action({
            text: this.app.i18n._('Reply To All'),
            handler: this.onMessageReplyTo.createDelegate(this, [true]),
            iconCls: 'action_email_replyAll',
            disabled: this.record.id.match(/_/)
        });

        this.action_forward = new Ext.Action({
            text: this.app.i18n._('Forward'),
            handler: this.onMessageForward.createDelegate(this),
            iconCls: 'action_email_forward',
            disabled: this.record.id.match(/_/)
        });
        
        this.action_print = new Ext.Action({
            text: this.app.i18n._('Print Message'),
            handler: this.onMessagePrint.createDelegate(this),
            iconCls:'action_print',
            menu:{
                items:[
                    new Ext.Action({
                        text: this.app.i18n._('Print Preview'),
                        handler: this.onMessagePrintPreview.createDelegate(this),
                        iconCls:'action_printPreview'
                    })
                ]
            }
        });
        
    },
    
    initToolbar: function() {
        // use toolbar from gridPanel
        this.tbar = new Ext.Toolbar({
            defaults: {height: 55},
            items: [{
                xtype: 'buttongroup',
                columns: 5,
                items: [
                    Ext.apply(new Ext.Button(this.action_deleteRecord), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top'
                    }),
                    Ext.apply(new Ext.Button(this.action_reply), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top'
                    }),
                    Ext.apply(new Ext.Button(this.action_replyAll), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top'
                    }),
                    Ext.apply(new Ext.Button(this.action_forward), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top'
                    }),
                    Ext.apply(new Ext.SplitButton(this.action_print), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign:'top',
                        arrowAlign:'right'
                    })
                ]
            }]
        });
        
    },
    
    afterRender: function() {
        this.supr().afterRender.apply(this, arguments);
        this.showMessage();
    },
    
    showMessage: function() {
        this.layout.setActiveItem(this.getSingleRecordPanel());
        this.updateDetails(this.record, this.getSingleRecordPanel().body);
    },
    
    /**
     * executed after a msg compose
     * 
     * @param {String} composedMsg
     * @param {String} action
     * @param {Array}  [affectedMsgs]  messages affected 
     * 
     */
    onAfterCompose: function(composedMsg, action, affectedMsgs) {
        this.fireEvent('update', composedMsg, action, affectedMsgs);
    },
    
    /**
     * executed after deletion of this message
     */
    onAfterDelete: function() {
        this.fireEvent('remove', Ext.encode(this.record.data));
        this.window.close();
    },
    
    /**
     * delete message handler
     */
    onMessageDelete: function(force) {
        var mainApp = Ext.ux.PopupWindowMgr.getMainWindow().Tine.Tinebase.appMgr.get('Felamimail'),
            folderId = this.record.get('folder_id'),
            folder = mainApp.getFolderStore().getById(folderId),
            accountId = folder ? folder.get('account_id') : null,
            account = Tine.Felamimail.loadAccountStore().getById(accountId),
            trashId = account ? account.getTrashFolderId() : null;
            
        this.loadMask.show();
        if (trashId) {
            var filter = [{field: 'id', operator: 'equals', value: this.record.id}];
            
            Tine.Felamimail.messageBackend.moveMessages(filter, trashId, { 
                callback: this.onAfterDelete.createDelegate(this, ['move'])
            });
        } else {
            Tine.Felamimail.messageBackend.addFlags(this.record.id, '\\Deleted', { 
                callback: this.onAfterDelete.createDelegate(this, ['flag'])
            });
        }
    },
    
    /**
     * reply message handler
     */
    onMessageReplyTo: function(toAll) {
        Tine.Felamimail.MessageEditDialog.openWindow({
            replyTo : Ext.encode(this.record.data),
            replyToAll: toAll,
            listeners: {
                'update': this.onAfterCompose.createDelegate(this, ['reply', Ext.encode([this.record.data])], 1)
            }
        });
    },
    
    /**
     * forward message handler
     */
    onMessageForward: function() {
        Tine.Felamimail.MessageEditDialog.openWindow({
            forwardMsgs : Ext.encode([this.record.data]),
            listeners: {
                'update': this.onAfterCompose.createDelegate(this, ['forward', Ext.encode([this.record.data])], 1)
            }
        });
    },
    
    onMessagePrint: Tine.Felamimail.GridPanel.prototype.onPrint,
    onMessagePrintPreview: Tine.Felamimail.GridPanel.prototype.onPrintPreview
});

Tine.Felamimail.MessageDisplayDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 700,
        name: 'TineFelamimailMessageDisplayDialog_' + id,
        contentPanelConstructor: 'Tine.Felamimail.MessageDisplayDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};