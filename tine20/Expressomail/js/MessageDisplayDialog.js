/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Expressomail')


Tine.Expressomail.MessageDisplayDialog = Ext.extend(Tine.Expressomail.GridDetailsPanel ,{
    /**
     * @cfg {Tine.Expressomail.Model.Message}
     */
    record: null,
    
    mailStoreData: null,

    autoScroll: false,
    
    encrypted: false,

    initComponent: function() {
        if (Ext.isString(this.record)) {
            this.record = Tine.Expressomail.messageBackend.recordReader({responseText: this.record});
        }

        this.encrypted = (this.record.get('smimeEml') != '') && (typeof(this.record.get('smimeEml')) != "undefined");
        
        this.addEvents('remove',
            /**
            * @event addcontact
            * @desc  Fired when contact is added
            * @param {Json String} data data of the contact
            */
            'addcontact'
        );

        this.app = Tine.Tinebase.appMgr.get('Expressomail');
        this.i18n = this.app.i18n;

        // trick onPrint/onPrintPreview
        this.detailsPanel = this;

        this.initActions();
        this.initToolbar();

        Tine.log.debug('Tine.Expressomail.MessageDisplayDialog::initComponent() -> message record:');
        Tine.log.debug(this.record);

        this.supr().initComponent.apply(this, arguments);
    },

    /**
     * init actions
     */
    initActions: function() {
        this.action_deleteRecord = new Ext.Action({
            text: this.app.i18n._('Delete'),
            handler: this.onMessageDelete.createDelegate(this, [false]),
            iconCls: 'action_delete',
            disabled: this.record.id.match(/_/)
        });

        if (Tine.Expressomail.registry.get('preferences').get('enableEncryptedMessage') == '1'
            && Tine.Tinebase.registry.get('preferences').get('windowtype')== 'Ext') {
            
            var encrypted_option_text = ' ('+this.app.i18n._('encrypted') + ')';
            var encrypted_text = (this.encrypted ? encrypted_option_text : '');
            var encrypted_text_alternate = (this.encrypted ? '' : encrypted_option_text);
            
            this.action_reply = new Ext.Action({
                text: this.app.i18n._('Reply') + encrypted_text,
                handler: this.onMessageReplyTo.createDelegate(this, [false,this.encrypted]),
                iconCls: 'action_email_reply',
                menu: {
                    items: [new Ext.Action({
                            text: this.app.i18n._('Reply') + encrypted_text_alternate,
                            handler: this.onMessageReplyTo.createDelegate(this, [false,!this.encrypted]),
                            iconCls:'action_email_reply'
                        })
                    ]
                }
            });

            this.action_replyAll = new Ext.Action({
                text: this.app.i18n._('Reply To All') + encrypted_text,
                handler: this.onMessageReplyTo.createDelegate(this, [true,this.encrypted]),
                iconCls: 'action_email_replyAll',
                menu: {
                    items: [
                        new Ext.Action({
                            text: this.app.i18n._('Reply To All') + encrypted_text_alternate,
                            handler: this.onMessageReplyTo.createDelegate(this, [true,!this.encrypted]),
                            iconCls:'action_email_replyAll'
                        })
                    ]
                }
            });

            this.action_forward = new Ext.Action({
                text: this.app.i18n._('Forward') + encrypted_text,
                handler: this.onMessageForward.createDelegate(this,[this.encrypted]),
                iconCls: 'action_email_forward',
                menu: {
                    items: [
                        new Ext.Action({
                            text: this.app.i18n._('Forward') + encrypted_text_alternate,
                            handler: this.onMessageForward.createDelegate(this,[!this.encrypted]),
                            iconCls:'action_email_forward'
                        })
                    ]
                }
            });
        }
        else {
            this.action_reply = new Ext.Action({
                text: this.app.i18n._('Reply'),
                handler: this.onMessageReplyTo.createDelegate(this, [false,this.encrypted]),
                iconCls: 'action_email_reply'
            });

            this.action_replyAll = new Ext.Action({
                text: this.app.i18n._('Reply To All'),
                handler: this.onMessageReplyTo.createDelegate(this, [true,this.encrypted]),
                iconCls: 'action_email_replyAll'
            });

            this.action_forward = new Ext.Action({
                text: this.app.i18n._('Forward'),
                handler: this.onMessageForward.createDelegate(this,[this.encrypted]),
                iconCls: 'action_email_forward'
            });
        }
        
        this.action_download = new Ext.Action({
            text: this.app.i18n._('Save'),
            handler: this.onMessageDownload.createDelegate(this),
            iconCls: 'action_email_download',
            disabled: this.record.id.match(/_/)
        });

        this.action_print = new Ext.Action({
            text: this.app.i18n._('Print Message'),
            handler: this.onMessagePrint.createDelegate(this.app.getMainScreen().getCenterPanel(), [this]),
            iconCls:'action_print',
            menu:{
                items:[
                    new Ext.Action({
                        text: this.app.i18n._('Print Preview'),
                        handler: this.onMessagePrintPreview.createDelegate(this.app.getMainScreen().getCenterPanel(), [this]),
                        iconCls:'action_printPreview'
                    })
                ]
            }
        });

    },

    /**
     * init toolbar
     */
    initToolbar: function() {
        if (Tine.Expressomail.registry.get('preferences').get('enableEncryptedMessage') == '1'
            && Tine.Tinebase.registry.get('preferences').get('windowtype')== 'Ext') {
            var button_type = Ext.SplitButton;
        }
        else {
            var button_type = Ext.Button;
        }
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
                    Ext.apply(new button_type(this.action_reply), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top'
                    }),
                    Ext.apply(new button_type(this.action_replyAll), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top'
                    }),
                    Ext.apply(new button_type(this.action_forward), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top'
                    }),
                    Ext.apply(new Ext.SplitButton(this.action_print), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign:'top',
                        arrowAlign:'right'
                    }),
                    Ext.apply(new Ext.Button(this.action_download), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign:'top',
                        arrowAlign:'right'
                    })
                ]
            }]
        });

    },

    /**
     * after render
     */
    afterRender: function() {
        this.supr().afterRender.apply(this, arguments);
        this.showMessage();

        var title = this.record.get('subject');
        if (title !== undefined) {
            // TODO make this work for attachment mails
            this.window.setTitle(title);
        }
    },

    /**
     * show message
     */
    showMessage: function() {
        this.layout.setActiveItem(this.getSingleRecordPanel());
        this.record.set('decrypted',true);
        this.updateDetails(this.record, this.getSingleRecordPanel().body);
    },
    
    onEditClose: function(contact) {
        // Relaying the contact to mainWindow
        Tine.log.debug('Tine.Expressomail.GridPanel::onEditClose / arguments:' + contact);
        this.fireEvent('addcontact', contact);
        
    },

    onDestroy: function() {
        this.supr().onDestroy.apply(this, arguments);
        this.app.mainScreen.GridPanel.focusSelectedMessage();
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
     * download message
     */
    onMessageDownload: function() {
        var downloader = new Ext.ux.file.Download({
            params: {
                method: 'Expressomail.downloadMessage',
                requestType: 'HTTP',
                messageId: this.record.id,
                filter : ''
            }
        }).start();
    },

    /**
     * delete message handler
     */
      onMessageDelete: function(force) {
        var mainApp = Ext.ux.PopupWindowMgr.getMainWindow().Tine.Tinebase.appMgr.get('Expressomail'),
            folderId = this.record.get('folder_id'),
            folder = mainApp.getFolderStore().getById(folderId),
            accountId = folder ? folder.get('account_id') : null,
            account = mainApp.getAccountStore().getById(accountId),
            trashId = account ? account.getTrashFolderId() : null;
        
        this.loadMask.show();
        if(Tine.Expressomail.registry.get('preferences').get('confirmDelete') == '1')
        {
            Ext.MessageBox.confirm('', this.app.i18n._('Confirm Delete') + ' ?', function(btn) {
                if(btn == 'yes') { 
                    this.moveOrDeleteMessage(trashId);
                }
                else {
                    this.loadMask.hide();
                }
            }, this);
            
        }
        else
        {
             this.moveOrDeleteMessage(trashId);
        }
    },
            
         
    /**
     * Do the actual delete or move
     */
    
    moveOrDeleteMessage: function(trashId){
        if (trashId) {
            var filter = [{field: 'id', operator: 'equals', value: this.record.id}];

            Tine.Expressomail.messageBackend.moveMessages(filter, trashId, { 
                callback: this.onAfterDelete.createDelegate(this, ['move'])
            });
        } else {
            Tine.Expressomail.messageBackend.addFlags(this.record.id, '\\Deleted', { 
                callback: this.onAfterDelete.createDelegate(this, ['flag'])
            });
        }
    },       

    /**
     * reply message handler
     */
    onMessageReplyTo: function(toAll,encrypted) {
        Tine.Expressomail.MessageEditDialog.openWindow({
            encrypted: encrypted || false,
            mailStoreData: this.mailStoreData,
            replyTo : Ext.encode(this.record.data),
            replyToAll: toAll,
            listeners: {
                'update': this.onAfterCompose.createDelegate(this, ['reply', Ext.encode([this.record.data])], 1),
                'addcontact': this.onEditClose.createDelegate(this)
                }
        });
    },

    /**
     * forward message handler
     */
    onMessageForward: function(encrypted) {
        Tine.Expressomail.MessageEditDialog.openWindow({
            encrypted: encrypted || false,
            mailStoreData: this.mailStoreData,
            forwardMsgs : Ext.encode([this.record.data]),
            listeners: {
                'update': this.onAfterCompose.createDelegate(this, ['forward', Ext.encode([this.record.data])], 1),
                'addcontact': this.onEditClose.createDelegate(this)
                }
        });
    },

    /**
     * (on) update details
     *
     * @param {Tine.Expressomail.Model.Message} record
     * @param {String} body
     * @private
     */
    updateDetails: function(record, body) {
        this.supr().updateDetails.apply(this, arguments);
        var title = this.record.get('subject');
        if (title !== undefined) {
            this.window.setTitle(title);
        }
    },

    onMessagePrint: Tine.Expressomail.GridPanel.prototype.onPrint,
    onMessagePrintPreview: Tine.Expressomail.GridPanel.prototype.onPrintPreview
});

Tine.Expressomail.MessageDisplayDialog.openWindow = function (config) {
    var record = (Ext.isString(config.record)) ? Ext.util.JSON.decode(config.record) : config.record,
        id = (record && record.id) ? record.id : 0,
        window = Tine.WindowFactory.getWindow({
            width: 800,
            height: 700,
            name: 'TineExpressomailMessageDisplayDialog_' + id,
            contentPanelConstructor: 'Tine.Expressomail.MessageDisplayDialog',
            contentPanelConstructorConfig: config
        });
    return window;
};
