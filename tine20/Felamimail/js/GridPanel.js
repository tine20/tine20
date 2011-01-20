/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
 
Ext.namespace('Tine.Felamimail');

/**
 * Message grid panel
 * 
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.GridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>Message Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Felamimail.GridPanel
 */
Tine.Felamimail.GridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
	/**
	 * record class
	 * @cfg {Tine.Felamimail.Model.Message} recordClass
	 */
    recordClass: Tine.Felamimail.Model.Message,
    
	/**
	 * message detail panel
	 * 
	 * @type Tine.Felamimail.GridDetailsPanel
	 * @property detailsPanel
	 */
    detailsPanel: null,
    
    /**
     * transaction id of current delete message request
     * @type Number
     */
    deleteTransactionId: null,
    
    /**
     * @private model cfg
     */
    evalGrants: false,
    filterSelectionDelete: true,
    
    /**
     * @private grid cfg
     */
    defaultSortInfo: {field: 'received', direction: 'DESC'},
    gridConfig: {
        //loadMask: true,
        autoExpandColumn: 'subject',
        // drag n dropfrom
        enableDragDrop: true,
        ddGroup: 'mailToTreeDDGroup'
    },
    // we don't want to update the preview panel on context menu
    updateDetailsPanelOnCtxMenu: false,
    
    /**
     * Return CSS class to apply to rows depending upon flags
     * - checks Flagged, Deleted and Seen
     * 
     * @param {Tine.Felamimail.Model.Message} record
     * @param {Integer} index
     * @return {String}
     */
    getViewRowClass: function(record, index) {
        var className = '';
        
        if (record.hasFlag('\\Flagged')) {
            className += ' flag_flagged';
        }
        if (record.hasFlag('\\Deleted')) {
            className += ' flag_deleted';
        }
        if (! record.hasFlag('\\Seen')) {
            className += ' flag_unread';
        }
        
        return className;
    },
    
    /**
     * init message grid
     * @private
     */
    initComponent: function() {
        
        this.app = Tine.Tinebase.appMgr.get('Felamimail');
        this.i18nEmptyText = this.app.i18n._('No Messages found or the cache is empty.');
        
        this.recordProxy = Tine.Felamimail.messageBackend;
        
        this.gridConfig.columns = this.getColumns();
        this.initFilterToolbar();
        this.initDetailsPanel();
        
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
        
        this.pagingConfig = {
            doRefresh: this.doRefresh.createDelegate(this)
        };
        
        Tine.Felamimail.GridPanel.superclass.initComponent.call(this);
        this.grid.getSelectionModel().on('rowselect', this.onRowSelection, this);
        this.app.getFolderStore().on('update', this.onUpdateFolderStore, this);
    },
    
    /**
     * cleanup on destruction
     */
    onDestroy: function() {
        this.app.getFolderStore().un('update', this.onUpdateFolderStore, this);
    },
    
    /**
     * folder store gets updated -> refresh grid if new messages arrived or messages have been removed
     * 
     * @param {Tine.Felamimail.FolderStore} store
     * @param {Tine.Felamimail.Model.Folder} record
     * @param {String} operation
     */
    onUpdateFolderStore: function(store, record, operation) {
        if (operation === Ext.data.Record.EDIT && record.isModified('cache_totalcount') && record.isModified('cache_job_actions_done')) {
            var tree = this.app.getMainScreen().getTreePanel(),
                selectedNodes = (tree) ? tree.getSelectionModel().getSelectedNodes() : [];
            
            // only refresh if 1 or no messages are selected
            if (this.getGrid().getSelectionModel().getCount() <= 1) {
                var refresh = false;
                for (var i = 0; i < selectedNodes.length; i++) {
                    if (selectedNodes[i].id == record.id) {
                        refresh = true;
                        break;
                    }
                }
                
                // check if folder is in filter or allinboxes are selected and updated folder is an inbox
                if (! refresh) {
                    var filters = this.filterToolbar.getValue();
                    for (var i = 0; i < filters.length; i++) {
                        if (filters[i].field == 'path' && filters[i].operator == 'in') {
                            if (filters[i].value.indexOf(record.get('path')) !== -1 || (filters[i].value.indexOf('/allinboxes') !== -1 && record.isInbox())) {
                                refresh = true;
                                break;
                            }
                        }
                    }
                }
                
                if (refresh) {
                    this.loadGridData({
                        removeStrategy: 'keepBuffered'
                    });
                }
            }
        }
    },
    
    /**
     * skip initial till we know the INBOX id
     */
    initialLoad: function() {
        var account = this.app.getActiveAccount(),
            accountId = account ? account.id : null,
            inbox = accountId ? this.app.getFolderStore().queryBy(function(record) {
                return record.get('account_id') === accountId && record.get('localname') === 'INBOX';
            }, this).first() : null;
            
        if (! inbox) {
            this.initialLoad.defer(100, this, arguments);
            return;
        }
        
        return Tine.Felamimail.GridPanel.superclass.initialLoad.apply(this, arguments);
    },
    
    /**
     * init actions with actionToolbar, contextMenu and actionUpdater
     * 
     * @private
     */
    initActions: function() {

        this.action_write = new Ext.Action({
            requiredGrant: 'addGrant',
            actionType: 'add',
            text: this.app.i18n._('Compose'),
            handler: this.onMessageCompose.createDelegate(this),
            iconCls: this.app.appName + 'IconCls'
        });

        this.action_reply = new Ext.Action({
            requiredGrant: 'readGrant',
            actionType: 'reply',
            text: this.app.i18n._('Reply'),
            handler: this.onMessageReplyTo.createDelegate(this, [false]),
            iconCls: 'action_email_reply',
            disabled: true
        });

        this.action_replyAll = new Ext.Action({
            requiredGrant: 'readGrant',
            actionType: 'replyAll',
            text: this.app.i18n._('Reply To All'),
            handler: this.onMessageReplyTo.createDelegate(this, [true]),
            iconCls: 'action_email_replyAll',
            disabled: true
        });

        this.action_forward = new Ext.Action({
            requiredGrant: 'readGrant',
            actionType: 'forward',
            text: this.app.i18n._('Forward'),
            handler: this.onMessageForward.createDelegate(this),
            iconCls: 'action_email_forward',
            disabled: true
        });

        this.action_flag = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Toggle highlighting'),
            handler: this.onToggleFlag.createDelegate(this, ['\\Flagged'], true),
            iconCls: 'action_email_flag',
            allowMultiple: true,
            disabled: true
        });
        
        this.action_markUnread = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Mark read/unread'),
            handler: this.onToggleFlag.createDelegate(this, ['\\Seen'], true),
            iconCls: 'action_mark_read',
            allowMultiple: true,
            disabled: true
        });
        
        this.action_deleteRecord = new Ext.Action({
            requiredGrant: 'deleteGrant',
            allowMultiple: true,
            singularText: this.app.i18n._('Delete'),
            pluralText: this.app.i18n._('Delete'),
            translationObject: this.i18nDeleteActionText ? this.app.i18n : Tine.Tinebase.translation,
            text: this.app.i18n._('Delete'),
            handler: this.onDeleteRecords,
            disabled: true,
            iconCls: 'action_delete',
            scope: this
        });
        
        this.action_addAccount = new Ext.Action({
            text: this.app.i18n._('Add Account'),
            handler: this.onAddAccount,
            iconCls: 'action_add',
            scope: this,
            disabled: ! Tine.Tinebase.common.hasRight('add_accounts', 'Felamimail')
        });
        this.action_printPreview = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Print Preview'),
            handler: this.onPrintPreview.createDelegate(this, []),
            disabled:true,
            iconCls:'action_printPreview',
            scope:this
        });
        this.action_print = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Print Message'),
            handler: this.onPrint.createDelegate(this, []),
            disabled:true,
            iconCls:'action_print',
            scope:this,
            menu:{
                items:[
                    this.action_printPreview
                ]
            }
        });
        this.actionUpdater.addActions([
            this.action_write,
            this.action_deleteRecord,
            this.action_reply,
            this.action_replyAll,
            this.action_forward,
            this.action_flag,
            this.action_markUnread,
            this.action_addAccount,
            this.action_print,
            this.action_printPreview
        ]);
        
        this.contextMenu = new Ext.menu.Menu({
            items: [
                this.action_reply,
                this.action_replyAll,
                this.action_forward,
                this.action_flag,
                this.action_markUnread,
                this.action_deleteRecord
            ]
        });
    },
    
    /**
     * initialises filter toolbar
     * 
     * @private
     */
    initFilterToolbar: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: Tine.Felamimail.Model.Message.getFilterModel(),
             defaultFilter: 'query',
             filters: [],
             plugins: [
                new Tine.widgets.grid.FilterToolbarQuickFilterPlugin({
                    criteriaIgnores: [
                        {field: 'query',     operator: 'contains',     value: ''},
                        {field: 'id' }
                    ]
                })
             ]
        });
    },    
    
    /**
     * the details panel (shows message content)
     * 
     * @private
     */
    initDetailsPanel: function() {
        this.detailsPanel = new Tine.Felamimail.GridDetailsPanel({
            gridpanel: this,
            grid: this,
            app: this.app,
            i18n: this.app.i18n
        });
    },
    
    getActionToolbar: function() {
        if (! this.actionToolbar) {
            this.actionToolbar = new Ext.Toolbar({
                defaults: {height: 55},
                items: [{
                    xtype: 'buttongroup',
                    columns: 8,
                    items: [
                        Ext.apply(new Ext.Button(this.action_write), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        }),
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
                        }),
                        this.action_flag,
                        this.action_addAccount,
                        this.action_markUnread
                    ]
                }, this.getActionToolbarItems()]
            });
            
            if (this.filterToolbar && typeof this.filterToolbar.getQuickFilterField == 'function') {
                this.actionToolbar.add('->', this.filterToolbar.getQuickFilterField());
            }
        }
        
        return this.actionToolbar;
    },
    
    /**
     * returns cm
     * 
     * @private
     */
    getColumns: function(){
        return [{
            id: 'id',
            header: this.app.i18n._("Id"),
            width: 100,
            sortable: true,
            dataIndex: 'id',
            hidden: true
        }, {
            id: 'content_type',
            header: this.app.i18n._("Attachments"),
            width: 12,
            sortable: true,
            dataIndex: 'content_type',
            renderer: this.attachmentRenderer
        }, {
            id: 'flags',
            header: this.app.i18n._("Flags"),
            width: 24,
            sortable: true,
            dataIndex: 'flags',
            renderer: this.flagRenderer
        },{
            id: 'subject',
            header: this.app.i18n._("Subject"),
            width: 300,
            sortable: true,
            dataIndex: 'subject'
        },{
            id: 'from_email',
            header: this.app.i18n._("From (Email)"),
            width: 100,
            sortable: true,
            dataIndex: 'from_email'
        },{
            id: 'from_name',
            header: this.app.i18n._("From (Name)"),
            width: 100,
            sortable: true,
            dataIndex: 'from_name'
        },{
            id: 'sender',
            header: this.app.i18n._("Sender"),
            width: 100,
            sortable: true,
            dataIndex: 'sender',
            hidden: true
        },{
            id: 'to',
            header: this.app.i18n._("To"),
            width: 150,
            sortable: true,
            dataIndex: 'to',
            hidden: true
        },{
            id: 'sent',
            header: this.app.i18n._("Sent"),
            width: 100,
            sortable: true,
            dataIndex: 'sent',
            hidden: true,
            renderer: Tine.Tinebase.common.dateTimeRenderer
        },{
            id: 'received',
            header: this.app.i18n._("Received"),
            width: 100,
            sortable: true,
            dataIndex: 'received',
            renderer: Tine.Tinebase.common.dateTimeRenderer
        },{
            id: 'folder_id',
            header: this.app.i18n._("Folder"),
            width: 100,
            sortable: true,
            dataIndex: 'folder_id',
            hidden: true,
            renderer: this.accountAndFolderRenderer.createDelegate(this)
        },{
            id: 'size',
            header: this.app.i18n._("Size"),
            width: 80,
            sortable: true,
            dataIndex: 'size',
            hidden: true,
            renderer: Ext.util.Format.fileSize
        }];
    },
    
    /**
     * attachment column renderer
     * 
     * @param {String} value
     * @return {String}
     * @private
     */
    attachmentRenderer: function(value) {
        var result = '';
        
        if (value && value.match(/multipart\/mixed/)) {
            result = '<img class="FelamimailFlagIcon" src="images/oxygen/16x16/actions/attach.png">';
        }
        
        return result;
    },
    
    /**
     * get flag icon
     * 
     * @param {String} flags
     * @return {String}
     * @private
     * 
     * TODO  use spacer if first flag(s) is/are not set?
     */
    flagRenderer: function(value, metadata, record) {
        var icons = [],
            result = '';
            
        if (record.hasFlag('\\Answered')) {
            icons.push({src: 'images/oxygen/16x16/actions/mail-reply-sender.png', qtip: _('Answered')});
        }   
        if (record.hasFlag('Passed')) {
            icons.push({src: 'images/oxygen/16x16/actions/mail-forward.png', qtip: _('Forwarded')});
        }   
//        if (record.hasFlag('\\Recent')) {
//            icons.push({src: 'images/oxygen/16x16/actions/knewstuff.png', qtip: _('Recent')});
//        }   
        
        Ext.each(icons, function(icon) {
            result += '<img class="FelamimailFlagIcon" src="' + icon.src + '" ext:qtip="' + icon.qtip + '">';
        }, this);
        
        return result;
    },
    
    /**
     * returns account and folder globalname
     * 
     * @param {String} folderId
     * @param {Object} metadata
     * @param {Folder|Account} record
     * @return {String}
     */
    accountAndFolderRenderer: function(folderId, metadata, record) {
        var folderStore = this.app.getFolderStore(),
            account = Tine.Felamimail.loadAccountStore().getById(record.get('account_id')),
            result = (account) ? account.get('name') : record.get('account_id'),
            folder = folderStore.getById(folderId);
        
        if (! folder) {
            folder = folderStore.getById(record.id);
            if (! folder) {
                // only account
                return (result) ? result : record.get('name');
            }
        }
            
        result += '/';
        if (folder) {
            result += folder.get('globalname');
        } else {
            result += folderId;
        }
            
        return result;
    },
    
    /**
     * executed when user clicks refresh btn
     */
    doRefresh: function() {
        var tree = this.app.getMainScreen().getTreePanel(),
            node = tree ? tree.getSelectionModel().getSelectedNode() : null,
            folder = node ? this.app.getFolderStore().getById(node.id) : null,
            refresh = this.pagingToolbar.refresh;
            
        // refresh is explicit
        this.editBuffer = [];
            
        if (folder) {
            refresh.disable();
            Tine.log.info('user forced mail check for folder "' + folder.get('localname') + '"');
            this.app.checkMails(folder, function() {
                refresh.enable();
            });
        } else {
            this.filterToolbar.onFilterChange();
        }
    },
    
    /**
     * permanently delete selected messages
     */
    deleteSelectedMessages: function() {
        this.moveOrDeleteMessages(null);
    },
    
    /**
     * move selected messages to given folder
     * 
     * @param {Tine.Felamimail.Model.Folder} folder
     * @param {Boolean} toTrash
     */
    moveSelectedMessages: function(folder, toTrash) {
        if (folder.isCurrentSelection()) {
            // nothing to do ;-)
            return;
        }
        
        this.moveOrDeleteMessages(folder, toTrash);
    },
    
    /**
     * move (folder !== null) or delete selected messages 
     * 
     * @param {Tine.Felamimail.Model.Folder} folder
     * @param {Boolean} toTrash
     */
    moveOrDeleteMessages: function(folder, toTrash) {
        var sm = this.getGrid().getSelectionModel(),
            filter = sm.getSelectionFilter(),
            msgsIds = [];

        if (sm.isFilterSelect) {
            var msgs = this.getStore(),
                nextRecord = null;
        } else {
            var msgs = sm.getSelectionsCollection();
            
            if (sm.getCount() == 1 && this.getStore().getCount() > 1) {
                // select next message (or previous if it was the last)
                lastIdx = this.getStore().indexOf(msgs.last());
                nextRecord = this.getStore().getAt(lastIdx + 1);
                if (! nextRecord) {
                    nextRecord = this.getStore().getAt(lastIdx - 1);
                }
            }
        }
        
        var increaseUnreadCountInTargetFolder = 0;
        msgs.each(function(msg) {
            var isSeen = msg.hasFlag('\\Seen'),
                currFolder = this.app.getFolderStore().getById(msg.get('folder_id')),
                diff = isSeen ? 0 : 1;
                
            if (currFolder) {
                currFolder.set('cache_unreadcount', currFolder.get('cache_unreadcount') - diff);
            }
            if (folder) {
                increaseUnreadCountInTargetFolder += diff;
            }
           
            msgsIds.push(msg.id);
            this.getStore().remove(msg);    
        },  this);
        
        if (folder && increaseUnreadCountInTargetFolder > 0) {
            // update unread count of target folder (only when moving)
            folder.set('cache_unreadcount', folder.get('cache_unreadcount') + increaseUnreadCountInTargetFolder);
        }            
        
        this.deleteQueue = this.deleteQueue.concat(msgsIds);
        this.pagingToolbar.refresh.disable();
        if (nextRecord !== null) {
            sm.selectRecords([nextRecord]);
        }
        
        if (folder !== null) {
            // move
            var targetFolderId = (toTrash) ? '_trash_' : folder.id;
            this.deleteTransactionId = Tine.Felamimail.messageBackend.moveMessages(filter, targetFolderId, { 
                callback: this.onAfterDelete.createDelegate(this, [msgsIds, folder])
            }); 
        } else {
            // delete
            this.deleteTransactionId = Tine.Felamimail.messageBackend.addFlags(filter, '\\Deleted', { 
                callback: this.onAfterDelete.createDelegate(this, [msgsIds])
            });
        }
    },
    
    /**
     * executed after a msg compose
     * 
     * @param {String} composedMsg
     * @param {String} action
     * @param {Array}  [affectedMsgs]  messages affected 
     */
    onAfterCompose: function(composedMsg, action, affectedMsgs) {
        // mark send folders cache status incomplete
        composedMsg = Ext.isString(composedMsg) ? new this.recordClass(Ext.decode(composedMsg)) : composedMsg;
        
        // NOTE: if affected messages is decoded, we need to fetch the originals out of our store
        if (Ext.isString(affectedMsgs)) {
            var msgs = [],
                store = this.getStore();
            Ext.each(Ext.decode(affectedMsgs), function(msgData) {
                var msg = store.getById(msgData.id);
                if (msg) {
                    msgs.push(msg);
                }
            }, this);
            affectedMsgs = msgs;
        }
        
        var composerAccount = Tine.Felamimail.loadAccountStore().getById(composedMsg.get('account_id')),
            sendFolderId = composerAccount ? composerAccount.getSendFolderId() : null,
            sendFolder = sendFolderId ? this.app.getFolderStore().getById(sendFolderId) : null;
            
        if (sendFolder) {
            sendFolder.set('cache_status', 'incomplete');
        }
        
        if (Ext.isArray(affectedMsgs)) {
            Ext.each(affectedMsgs, function(msg) {
                if (['reply', 'forward'].indexOf(action) !== -1) {
                    msg.addFlag(action === 'reply' ? '\\Answered' : 'Passed');
                } else if (action == 'senddraft') {
                    this.deleteTransactionId = Tine.Felamimail.messageBackend.addFlags(msg.id, '\\Deleted', { 
                        callback: this.onAfterDelete.createDelegate(this, [[msg.id]])
                    });
                }
            }, this);
		}
    },
    
    /**
     * executed after msg delete
     * 
     * @param {Array} [ids]
     * @param {Tine.Felamimail.Model.Folder} [folder]
     */
    onAfterDelete: function(ids, folder) {
        this.editBuffer = this.editBuffer.diff(ids);

        if (! this.deleteTransactionId || ! Tine.Felamimail.messageBackend.isLoading(this.deleteTransactionId)) {
            this.loadGridData({
                removeStrategy: 'keepBuffered'
            });
        }
    },
    
    /**
     * compose new message handler
     */
    onMessageCompose: function() {
        Tine.Felamimail.MessageEditDialog.openWindow({
            listeners: {
                'update': this.onAfterCompose.createDelegate(this, ['compose'], 1)
            }
        });
    },
    
    /**
     * forward message(s) handler
     */
    onMessageForward: function() {
        var sm = this.getGrid().getSelectionModel(),
            msgs = sm.getSelections(),
            msgsData = [];
            
        Ext.each(msgs, function(msg) {msgsData.push(msg.data)}, this);
        
        if (sm.getCount() > 0) {
            Tine.Felamimail.MessageEditDialog.openWindow({
                forwardMsgs : Ext.encode(msgsData),
                listeners: {
                    'update': this.onAfterCompose.createDelegate(this, ['forward', msgs], 1)
                }
            });
        }
    },
    
    /**
     * reply message handler
     * 
     * @param {bool} toAll
     */
    onMessageReplyTo: function(toAll) {
        var sm = this.getGrid().getSelectionModel(),
            msg = sm.getSelected();
            
        Tine.Felamimail.MessageEditDialog.openWindow({
            replyTo : Ext.encode(msg.data),
            replyToAll: toAll,
            listeners: {
                'update': this.onAfterCompose.createDelegate(this, ['reply', [msg]], 1)
            }
        });
    },
    
    /**
     * delete messages handler
     * 
     * @return {void}
     */
    onDeleteRecords: function() {
        var account = this.app.getActiveAccount(),
            trashId = account.getTrashFolderId(),
            trash = trashId ? this.app.getFolderStore().getById(trashId) : null;
            
        return trash && !trash.isCurrentSelection() ? this.moveSelectedMessages(trash, true) : this.deleteSelectedMessages();
    },

    /**
     * called when a row gets selected
     * 
     * @param {SelectionModel} sm
     * @param {Number} rowIndex
     * @param {Tine.Felamimail.Model.Message} record
     * @param {Boolean} now
     */
    onRowSelection: function(sm, rowIndex, record, now) {
        if (! now) {
            return this.onRowSelection.defer(250, this, [sm, rowIndex, record, true]);
        }
        
        if (sm.getCount() == 1 && sm.isIdSelected(record.id) && !record.hasFlag('\\Seen')) {
            record.addFlag('\\Seen');
            Tine.Felamimail.messageBackend.addFlags(record.id, '\\Seen');
            this.app.getMainScreen().getTreePanel().decrementCurrentUnreadCount();
        }
    },
    
    /**
     * row doubleclick handler
     * 
     * - opens message edit dialog (if draft/template)
     * - opens message display dialog (everything else)
     * 
     * @param {Tine.Felamimail.GridPanel} grid
     * @param {Row} row
     * @param {Event} e
     */
    onRowDblClick: function(grid, row, e) {
        
        var record = this.grid.getSelectionModel().getSelected(),
            folder = this.app.getFolderStore().getById(record.get('folder_id')),
            account = Tine.Felamimail.loadAccountStore().getById(folder.get('account_id'));
            action = (folder.get('globalname') == account.get('templates_folder')) ? 'senddraft' :
                     folder.get('globalname') == account.get('drafts_folder') ? 'sendtemplate' : null;
        
        // check folder to determine if mail should be opened in compose dlg
        if (action !== null) {
            Tine.Felamimail.MessageEditDialog.openWindow({
                draftOrTemplate: Ext.encode(record.data),
                listeners: {
                    scope: this,
                    'update': this.onAfterCompose.createDelegate(this, [action, [record]], 1)
                }
            });
        } else {
            Tine.Felamimail.MessageDisplayDialog.openWindow({
                record: record,
                listeners: {
                    scope: this,
                    'update': this.onAfterCompose,
                    'remove': this.onRemoveInDisplayDialog
                }
            });
        }
    },
    
    /**
     * message got removed in display dialog
     * 
     * @param {} msgData
     */
    onRemoveInDisplayDialog: function (msgData) {
        var msg = this.getStore().getById(Ext.decode(msgData).id);
            folderId = msg ? msg.get('folder_id') : null,
            folder = folderId ? this.app.getFolderStore().getById(folderId) : null,
            accountId = folder ? folder.get('account_id') : null,
            account = accountId ? Tine.Felamimail.loadAccountStore().getById(accountId) : null,
            trashId = account ? account.getTrashFolderId() : null,
            trash = trashId ? this.app.getFolderStore().getById(trashId) : null;
            
        this.getStore().remove(msg);
        this.onAfterDelete(null, trash);
    },    
    
    /**
     * called when the store gets updated
     * 
     * NOTE: we only allow updateing flags BUT the actual updating is done 
     *       directly from the UI fn's to support IMAP optimised bulk actions
     */
    onStoreUpdate: function(store, record, operation) {
        if (operation === Ext.data.Record.EDIT && record.isModified('flags')) {
            record.commit()
        }
    },
    
    /**
     * key down handler
     * 
     * @param {Event} e
     */
    onKeyDown: function(e) {
        if (e.ctrlKey) {
            switch (e.getKey()) {
                case e.N:
                case e.M:
                    this.onMessageCompose();
                    e.preventDefault();
                    break;
                case e.R:
                    this.onMessageReplyTo();
                    e.preventDefault();
                    break;
                case e.L:
                    this.onMessageForward();
                    e.preventDefault();
                    break;
            }
        }
        
        Tine.Felamimail.GridPanel.superclass.onKeyDown.call(this, e);
    },
    
    /**
     * toggle flagged status of mail(s)
     * - Flagged/Seen
     * 
     * @param {Button} button
     * @param {Event} event
     * @param {String} flag
     */
    onToggleFlag: function(btn, e, flag) {
        var sm = this.getGrid().getSelectionModel(),
            filter = sm.getSelectionFilter(),
            msgs = sm.isFilterSelect ? this.getStore() : sm.getSelectionsCollection(),
            flagCount = 0;
            
        // switch all msgs to one state -> toogle most of them
        msgs.each(function(msg) {
            flagCount += msg.hasFlag(flag) ? 1 : 0;
        });
        var action = flagCount >= Math.round(msgs.getCount()/2) ? 'clear' : 'add';
        
        // mark messages in UI and add to edit buffer
        msgs.each(function(msg) {
            // update unreadcount
            if (flag === '\\Seen') {
                var isSeen = msg.hasFlag('\\Seen'),
                    folder = this.app.getFolderStore().getById(msg.get('folder_id')),
                    diff = (action === 'clear' && isSeen) ? 1 :
                           (action === 'add' && ! isSeen) ? -1 : 0;
                           
                if (folder) {
                    folder.set('cache_unreadcount', folder.get('cache_unreadcount') + diff);
                    folder.commit();
                }
            }
            
            msg[action + 'Flag'](flag);
            
            this.addToEditBuffer(msg);
        }, this);
        
        // do request
        Tine.Felamimail.messageBackend[action+ 'Flags'](filter, flag);
    },
    
    /**
     * called before store queries for data
     */
    onStoreBeforeload: function(store, options) {
        this.supr().onStoreBeforeload.apply(this, arguments);
        
        if (! Ext.isEmpty(this.deleteQueue)) {
            options.params.filter.push({field: 'id', operator: 'notin', value: this.deleteQueue});
        }
    },
    
    /**
     * add new account button
     * 
     * @param {Button} button
     * @param {Event} event
     */
    onAddAccount: function(button, event) {
        var popupWindow = Tine.Felamimail.AccountEditDialog.openWindow({
            record: null,
            listeners: {
                scope: this,
                'update': function(record) {
                    var account = new Tine.Felamimail.Model.Account(Ext.util.JSON.decode(record));
                    
                    // add to registry
                    Tine.Felamimail.registry.get('preferences').replace('defaultEmailAccount', account.id);
                    // need to do this because store could be unitialized yet
                    var registryAccounts = Tine.Felamimail.registry.get('accounts');
                    registryAccounts.results.push(account.data);
                    registryAccounts.totalcount++;
                    Tine.Felamimail.registry.replace('accounts', registryAccounts);
                    
                    // add to tree / store
                    var treePanel = this.app.getMainScreen().getTreePanel();
                    treePanel.addAccount(account);
                    treePanel.accountStore.add([account]);
                }
            }
        });        
    },
    
    /**
     * print handler
     * 
     * @param {Tine.Felamimail.GridDetailsPanel} details panel [optional]
     */
    onPrint: function(detailsPanel) {
        if (!Ext.get('felamimailPrintHelperIframe')) {
            Ext.getBody().createChild({
                id: 'felamimailPrintHelper',
                tag:'div',
                //style:'position:absolute;top:0px;width:100%;height:100%;',
                children:[{
                    tag:'iframe',
                    id: 'felamimailPrintHelperIframe'
                }]
            });
        }
        var content = this.getDetailsPanelContentForPrinting(detailsPanel || this.detailsPanel);
        Ext.get('felamimailPrintHelperIframe').dom.contentWindow.document.documentElement.innerHTML = content;
        Ext.get('felamimailPrintHelperIframe').dom.contentWindow.print();
    },
    
    /**
     * get detail panel content
     * 
     * @param {Tine.Felamimail.GridDetailsPanel} details panel
     * @return {String}
     */
    getDetailsPanelContentForPrinting: function(detailsPanel) {
        // TODO somehow we have two <div class="preview-panel-felamimail"> -> we need to fix that and get the first element found
        var detailsPanels = detailsPanel.getEl().query('.preview-panel-felamimail');
        var detailsPanelContent = (detailsPanels.length > 1) ? detailsPanels[1].innerHTML : detailsPanels[0].innerHTML;
        
        var buffer = '<html><head>';
        buffer += '<title>' + this.app.i18n._('Print Preview') + '</title>';
        buffer += '</head><body>';
        buffer += detailsPanelContent;
        buffer += '</body></html>';
        
        return buffer;
    },
    
    /**
     * print preview handler
     * 
     * @param {Tine.Felamimail.GridDetailsPanel} details panel [optional]
     */
    onPrintPreview: function(detailsPanel) {
        var content = this.getDetailsPanelContentForPrinting(detailsPanel || this.detailsPanel);
        
        var win = window.open('about:blank',this.app.i18n._('Print Preview'),'width=500,height=500,scrollbars=yes,toolbar=yes,status=yes,menubar=yes');
        win.document.open()
        win.document.write(content);
        win.document.close();
        win.focus();
    },
    
    /**
     * format headers
     * 
     * @param {Object} headers
     * @param {Bool} ellipsis
     * @param {Bool} onlyImportant
     * @return {String}
     */
    formatHeaders: function(headers, ellipsis, onlyImportant) {
        var result = '';
        for (header in headers) {
            if (headers.hasOwnProperty(header) && 
                    (! onlyImportant || header == 'from' || header == 'to' || header == 'subject' || header == 'date')) 
            {
                result += '<b>' + header + ':</b> ' 
                    + Ext.util.Format.htmlEncode(
                        (ellipsis) 
                            ? Ext.util.Format.ellipsis(headers[header], 40)
                            : headers[header]
                    ) + '<br/>';
            }
        }
        return result;
    }    
});
