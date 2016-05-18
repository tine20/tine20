/*
 * Tine 2.0
 *
 * @package     Expressomail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.namespace('Tine.Expressomail');

/**
 * Message grid panel
 *
 * @namespace   Tine.Expressomail
 * @class       Tine.Expressomail.GridPanel
 * @extends     Tine.widgets.grid.GridPanel
 *
 * <p>Message Grid Panel</p>
 * <p><pre>
 * </pre></p>
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Expressomail.GridPanel
 */
Tine.Expressomail.GridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    /**
     * record class
     * @cfg {Tine.Expressomail.Model.Message} recordClass
     */
    recordClass: Tine.Expressomail.Model.Message,

    /**
     * message detail panel
     *
     * @type Tine.Expressomail.GridDetailsPanel
     * @property detailsPanel
     */
    detailsPanel: null,

    /**
     * transaction id of current delete message request
     * @type Number
     */
    deleteTransactionId: null,

    /**
     * this is true if messages are moved/deleted
     *
     * @type Boolean
     */
    movingOrDeleting: false,

    manualRefresh: false,

    /**
     * @private model cfg
     */
    evalGrants: false,
    filterSelectionDelete: true,
    // autoRefresh is done via onUpdateFolderStore
    autoRefreshInterval: false,
    
    fetchtry: 0,

    saveOnDestroy: {},
    updateDetailsDelay: 500,

    /**
     * @private grid cfg
     */
    defaultSortInfo: {field: 'received', direction: 'DESC'},
    gridConfig: {
        autoExpandColumn: 'subject',
        // drag n dropfrom
        enableDragDrop: true,
        ddGroup: 'mailToTreeDDGroup'
    },
    // we don't want to update the preview panel on context menu
    updateDetailsPanelOnCtxMenu: true,

    /**
     * Return CSS class to apply to rows depending upon flags
     * - checks Flagged, Deleted and Seen
     *
     * @param {Tine.Expressomail.Model.Message} record
     * @param {Integer} index
     * @return {String}
     */
    getViewRowClass: function(record, index) {
        var className = '';
        if (record.isImportant()) {
            className += ' importance_high';
        }
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

        this.app = Tine.Tinebase.appMgr.get('Expressomail');
        this.i18nEmptyText = this.app.i18n._('No Messages found or the cache is empty.');

        this.recordProxy = Tine.Expressomail.messageBackend;

        this.gridConfig.columns = this.getColumns();
        this.initFilterToolbar();
        this.initDetailsPanel();

        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);

        this.pagingConfig = {
            doRefresh: this.doRefresh.createDelegate(this)
        };

        this.defaultPaging.limit = parseInt(Tine.Expressomail.registry.get('preferences').get('emailsPerPage'));

        Tine.Expressomail.GridPanel.superclass.initComponent.call(this)

        this.grid.getSelectionModel().on('rowselect', this.onRowSelectionDelay, this);
        this.app.getFolderStore().on('update', this.onUpdateFolderStore, this);

        this.initPagingToolbar();

        this.saveOnDestroy = {};
    },

    /**
     * init grid
     * @private
     */
    initGrid: function() {
        Tine.Expressomail.GridPanel.superclass.initGrid.call(this);
        this.selectionModel.events.selectionchange.clearListeners();
        this.selectionModel.on('selectionchange', this.onSelectionChangeDelay, this);
    },

    /**
     * called when a selection gets changed
     *
     * @param {SelectionModel} sm
     */
    onSelectionChangeDelay: function(sm) {
        this.actionUpdater.updateActions(sm);
        this.ctxNode = this.selectionModel.getSelections();
        if (this.updateOnSelectionChange && this.detailsPanel) {
            if (this.selectionChangeDelayedTask) {
                this.selectionChangeDelayedTask.cancel();
            }
            this.selectionChangeDelayedTask = new Ext.util.DelayedTask(this.detailsPanel.onDetailsUpdate, this.detailsPanel, [sm]);
            this.selectionChangeDelayedTask.delay(this.updateDetailsDelay);
        }
    },

    /**
     * called when a row gets selected
     *
     * @param {SelectionModel} sm
     * @param {Number} rowIndex
     * @param {Tine.Expressomail.Model.Message} record
     * @param {Number} retryCount
     */
    onRowSelectionDelay: function(sm, rowIndex, record, retryCount) {
        if (this.rowSelectionDelayedTask) {
            this.rowSelectionDelayedTask.cancel();
        }
        this.rowSelectionDelayedTask = new Ext.util.DelayedTask(this.onRowSelection, this, [sm, rowIndex, record, retryCount]);
        this.rowSelectionDelayedTask.delay(this.updateDetailsDelay);
    },

    /**
     * get load mask
     * 
     * @return {Ext.LoadMask}
     */
    getLoadMask: function() {
        if (! this.loadMask) {
            this.loadMask = new Ext.LoadMask(this.el);
        }
        
        return this.loadMask;
    },

    /**
     * add quota bar to paging toolbar
     */
    initPagingToolbar: function() {
        Ext.QuickTips.init();

        this.quotaBar = new Ext.ProgressBar({
            width: 120,
            height: 16,
            style: {
                marginTop: '1px'
            },
            text: this.app.i18n._('Quota usage')
        });
        this.pagingToolbar.insert(12, new Ext.Toolbar.Separator());
        this.pagingToolbar.insert(12, this.quotaBar);

        // NOTE: the Ext.progessbar has an ugly bug: it does not layout correctly when hidden
        //       so we need to listen when we get activated to relayout the progessbar
        Tine.Tinebase.appMgr.on('activate', function(app) {
            if (app.appName === 'Expressomail') {
                this.quotaBar.syncProgressBar();
                this.quotaBar.setWidth(this.quotaBar.getWidth());
            }
        }, this);

        this.pagingToolbar.pageSize = parseInt(Tine.Expressomail.registry.get('preferences').get('emailsPerPage'));
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
     * @param {Tine.Expressomail.FolderStore} store
     * @param {Tine.Expressomail.Model.Folder} record
     * @param {String} operation
     */
    onUpdateFolderStore: function(store, record, operation) {
        if (operation === Ext.data.Record.EDIT && record.isModified('cache_totalcount') && (this.saveOnDestroy ? !this.saveOnDestroy.confirm : true)) {
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
                    filters = filters.filters ? filter.filters : filters;

                    for (var i = 0; i < filters.length; i++) {
                        if (filters[i].field == 'path' && filters[i].operator == 'in') {
                            if (filters[i].value.indexOf(record.get('path')) !== -1 || (filters[i].value.indexOf('/allinboxes') !== -1 && record.isInbox())) {
                                refresh = true;
                                break;
                            }
                        }
                    }
                }

                if (refresh && this.noDeleteRequestInProgress()) {
                    Tine.log.debug('Refresh grid because of folder update.');
                    this.loadGridData({
                        removeStrategy: 'keepBuffered',
                        autoRefresh: true
                    });
                }
            }
        }
    },

    /**
     * skip initial till we know the INBOX id
     */
    initialLoad: function() {
    },

    /**
     * init actions with actionToolbar, contextMenu and actionUpdater
     *
     * @private
     */
    initActions: function() {
        var encrypted_text = ' ('+this.app.i18n._('encrypted') + ')';

        this.action_write = new Ext.Action({
            requiredGrant: 'addGrant',
            actionType: 'add',
            text: this.app.i18n._('Compose'),
            handler: this.onMessageCompose.createDelegate(this),
            disabled: ! this.app.getActiveAccount(),
            iconCls: this.app.appName + 'IconCls'
        });

        // all actions reply
        this.action_reply = new Ext.Action({
            requiredGrant: 'readGrant',
            actionType: 'reply',
            text: this.app.i18n._('Reply'),
            handler: this.onMessageReplyTo.createDelegate(this, [false]),
            iconCls: 'action_email_reply',
            disabled: true
        });

        this.action_reply_encrypted = new Ext.Action({
            requiredGrant: 'readGrant',
            actionType: 'reply',
            text: this.app.i18n._('Reply') + encrypted_text,
            handler: this.onMessageReplyTo.createDelegate(this, [false,false,true]),
            iconCls: 'action_email_reply',
            disabled: true
        });

        this.action_split_reply = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Reply'),
            handler: this.onMessageReplyTo.createDelegate(this, [false,false,false]),
            disabled: true,
            iconCls: 'action_email_reply',
            scope: this,
            menu:{
                items:[
                    this.action_reply_encrypted
                ]
            }
        });
        
        this.action_split_reply_encrypted = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Reply') + encrypted_text,
            handler: this.onMessageReplyTo.createDelegate(this, [false,false,true]),
            disabled: true,
            iconCls: 'action_email_reply',
            scope: this,
            menu:{
                items:[
                    this.action_reply
                ]
            }
        });
        
        // all actions replyAll
        this.action_replyAll = new Ext.Action({
            requiredGrant: 'readGrant',
            actionType: 'replyAll',
            text: this.app.i18n._('Reply To All'),
            handler: this.onMessageReplyTo.createDelegate(this, [true]),
            iconCls: 'action_email_replyAll',
            disabled: true
        });

        this.action_replyAll_encrypted = new Ext.Action({
            requiredGrant: 'readGrant',
            actionType: 'replyAll',
            text: this.app.i18n._('Reply To All') + encrypted_text,
            handler: this.onMessageReplyTo.createDelegate(this, [true,false,true]),
            iconCls: 'action_email_replyAll',
            disabled: true
        });
        
        this.action_split_replyAll = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Reply To All'),
            handler: this.onMessageReplyTo.createDelegate(this, [true,false,false]),
            disabled: true,
            iconCls: 'action_email_replyAll',
            scope: this,
            menu:{
                items:[
                    this.action_replyAll_encrypted
                ]
            }
        });
        
        this.action_split_replyAll_encrypted = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Reply To All') + encrypted_text,
            handler: this.onMessageReplyTo.createDelegate(this, [true,false,true]),
            disabled: true,
            iconCls: 'action_email_replyAll',
            scope: this,
            menu:{
                items:[
                    this.action_replyAll
                ]
            }
        });
        
        // all actions forward
        this.action_forward = new Ext.Action({
            requiredGrant: 'readGrant',
            actionType: 'forward',
            text: this.app.i18n._('Forward'),
            handler: this.onMessageForward.createDelegate(this, []),
            iconCls: 'action_email_forward',
            disabled: true
        });

        this.action_forward_encrypted = new Ext.Action({
            requiredGrant: 'readGrant',
            actionType: 'forward',
            text: this.app.i18n._('Forward') + encrypted_text,
            handler: this.onMessageForward.createDelegate(this, [false,true]),
            iconCls: 'action_email_forward',
            disabled: true
        });
        
        this.action_split_forward = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Forward'),
            handler: this.onMessageForward.createDelegate(this, [false,false]),
            disabled: true,
            iconCls: 'action_email_forward',
            scope: this,
            menu:{
                items:[
                    this.action_forward_encrypted
                ]
            }
        });
        
        this.action_split_forward_encrypted = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Forward') + encrypted_text,
            handler: this.onMessageForward.createDelegate(this, [false,true]),
            disabled: true,
            iconCls: 'action_email_forward',
            scope: this,
            menu:{
                items:[
                    this.action_forward
                ]
            }
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
            translationObject: this.i18nDeleteActionText ? this.app.i18n : i18n,
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
            disabled: ! Tine.Tinebase.common.hasRight('add_accounts', 'Expressomail')
        });
        this.action_exportMsg = new Ext.Action({
            requiredGrant: 'readGrant',
            allowMultiple: true,
            text: this.app.i18n._('Export'),
            handler: this.onExportMsgs,
            iconCls: 'action_exportMsg',
            scope: this
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
        this.action_reportPhishing = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Report phishing'),
            handler: this.onReportPhishing,
            iconCls: 'action_reportPhishing',
            allowMultiple: true,
            disabled: true,
            scope: this
        });
        this.actionUpdater.addActions([
//            this.action_write,
            this.action_deleteRecord,
            this.action_reply,
            this.action_replyAll,
            this.action_forward,
            this.action_flag,
            this.action_markUnread,
            this.action_addAccount,
            this.action_print,
            this.action_printPreview,
            this.action_reportPhishing
        ]);

        if (Tine.Expressomail.registry.get('preferences').get('enableEncryptedMessage') == '1'
            && Tine.Tinebase.registry.get('preferences').get('windowtype')== 'Ext') {
            this.actionUpdater.addActions([
                this.action_reply_encrypted,
                this.action_replyAll_encrypted,
                this.action_forward_encrypted,
                this.action_split_reply,
                this.action_split_replyAll,
                this.action_split_forward,
                this.action_split_reply_encrypted,
                this.action_split_replyAll_encrypted,
                this.action_split_forward_encrypted
            ]);
            var menu_items = [
                    this.action_reply,
                    this.action_reply_encrypted,
                    '-',
                    this.action_replyAll,
                    this.action_replyAll_encrypted,
                    '-',
                    this.action_forward,
                    this.action_forward_encrypted,
                    '-',
                    this.action_flag,
                    this.action_markUnread,
                    this.action_exportMsg,
                    this.action_deleteRecord
            ];
        }
        else {
            var menu_items = [
                    this.action_reply,
                    this.action_replyAll,
                    this.action_forward,
                    this.action_flag,
                    this.action_markUnread,
                    this.action_exportMsg,
                    this.action_deleteRecord
            ];
        }

        if (Tine.Expressomail.registry.get("reportPhishingEmail") && Tine.Expressomail.registry.get("reportPhishingEmail") != '') {
            // just show this menu item if report phishing email is configured
            menu_items.push(this.action_reportPhishing);
        }

        this.contextMenu = new Ext.menu.Menu({
            items: menu_items
        });

    },

    /**
     * delete messages handler
     *
     * @return {void}
     */
    onReportPhishing: function() {
        var email = Tine.Expressomail.registry.get("reportPhishingEmail");
        var subjects = [];
        var msgs = this.selectionModel.getSelections();
        var msgsIds = [];
        Ext.each(msgs, function(item) {
            subjects.push(item.get('subject'));
            msgsIds.push(item.id);
        });
        var title = this.app.i18n._('Report phishing');
        var text_init = this.app.i18n._("Phishing are messages with the intention of getting personal data like:<br/>passwords, finantial data like credit card numbers and so on.");
        var text_end = this.app.i18n._("Report listed messages as phishing?");
        var text = text_init
                 + "<ul class='mail-phishing-subjects-list'>"
                 + "<li class='mail-phishing-subject-item'>"
                 + subjects.join("</li><li class='mail-phishing-subject-item'>")
                 + "</li>"
                 + "</ul>"
                 + text_end;

        Ext.MessageBox.confirm(title, text, function(btn) {
            Ext.MessageBox.updateText(""); // this is a workaround to the MessageBox width issue in Chrome
            if(btn === 'yes') {
                this.reportPhishingMask = new Ext.LoadMask(Ext.getBody(), {msg: this.app.i18n._('Sending phishing report...')});
                this.reportPhishingMask.show();
                var account = this.app.getActiveAccount(),
                    accountId = account ? account.id : null;
                var message = new Tine.Expressomail.Model.Message(Tine.Expressomail.Model.Message.getDefaultData());
                message.set('account_id', accountId);
                message.set('to', [email]);
                message.set('cc', []);
                message.set('bcc', []);
                var subject_string = 'Phishing - {0} message',
                    subject_string_plural = 'Phishing - {0} messages',
                    subject = String.format(this.app.i18n.ngettext(subject_string, subject_string_plural, subjects.length), subjects.length);
                message.set('subject', subject);
                var body_string = "At {0}, user {1} reported attached message as phishing:",
                    body_string_plural = "At {0}, user {1} reported attached messages as phishing:";
                // the first param of the translated string is not replaced in js, the php will replace it with server datetime
                var body = String.format(this.app.i18n.ngettext(body_string, body_string_plural, subjects.length), "{0}", account.get('from'));
                message.set("body",
                            "<p>" + body + "</p>"
                          + "<ul class='mail-phishing-subjects-list'>"
                          + "<li class='mail-phishing-subject-item'>"
                          + subjects.join("</li><li class='mail-phishing-subject-item'>")
                          + "</li>"
                          + "</ul>");
                Tine.Expressomail.messageBackend.reportPhishing(message, msgsIds, {
                    scope: this,
                    success: function(record){
                        var account = this.app.getActiveAccount(),
                            trashId = (account) ? account.getTrashFolderId() : null,
                            trash = trashId ? this.app.getFolderStore().getById(trashId) : null,
                            trashConfigured = (account.get('trash_folder'));

                        if ( (Tine.Expressomail.registry.get('preferences').get('confirmUseTrash') == '1' && trash && ! trash.isCurrentSelection())
                           || (! trash && trashConfigured) ) {
                            this.moveSelectedMessages(trash, true);
                        } else {
                            this.deleteSelectedMessages();
                        }
                        this.reportPhishingMask.hide();
                    },
                    failure: function(record){
                        Ext.MessageBox.show({
                            title: this.app.i18n._('Failed to send phishing report!'),
                            msg: this.app.i18n._('Your phishing report could not be send.'),
                            buttons: Ext.MessageBox.OK,
                            icon: Ext.MessageBox.WARNING
                        });
                        this.reportPhishingMask.hide();
                    },
                    timeout: 300000 // 5 minutes
                });
            }
        }, this);

    },

    /**
     * initialises filter toolbar
     *
     * @private
     */
    initFilterToolbar: function() {
        this.filterToolbar = this.getFilterToolbar({
            dontRefreshOnDeleteFilter: true
        });
        this.filterToolbar.criteriaIgnores = [
            {field: 'query',     operator: 'contains',     value: ''},
            {field: 'id'},
            {field: 'path'}
        ];
    },

    /**
     * the details panel (shows message content)
     *
     * @private
     */
    initDetailsPanel: function() {
        this.detailsPanel = new Tine.Expressomail.GridDetailsPanel({
            gridpanel: this,
            grid: this,
            app: this.app,
            i18n: this.app.i18n
        });
    },

    /**
     * reload action toolbar
     *
     * @param encrypted reload toolbar for an encrypted message
     * @return {void}
     */
    reloadActionToolbar: function(encrypted) {
        this.actionToolbar = null;
        this.encrypted_message = encrypted;
        this.app.mainScreen['ActionToolbar'] = null;
        this.app.mainScreen.showNorthPanel();
    },

    /**
     * get action toolbar
     *
     * @return {Ext.Toolbar}
     */
    getActionToolbar: function() {
        if (! this.actionToolbar) {
            var button_reply, button_replyAll, button_forward;
            if (Tine.Expressomail.registry.get('preferences').get('enableEncryptedMessage') == '1'
                && Tine.Tinebase.registry.get('preferences').get('windowtype')== 'Ext') {
                if (this.encrypted_message) {
                    button_reply = new Ext.SplitButton(this.action_split_reply_encrypted);
                    button_replyAll = new Ext.SplitButton(this.action_split_replyAll_encrypted);
                    button_forward = new Ext.SplitButton(this.action_split_forward_encrypted);
                }
                else {
                    button_reply = new Ext.SplitButton(this.action_split_reply);
                    button_replyAll = new Ext.SplitButton(this.action_split_replyAll);
                    button_forward = new Ext.SplitButton(this.action_split_forward);
                }
            }
            else {
                button_reply = new Ext.Button(this.action_reply);
                button_replyAll = new Ext.Button(this.action_replyAll);
                button_forward = new Ext.Button(this.action_forward);
            }
            this.actionToolbar = new Ext.Toolbar({
                defaults: {height: 55},
                items: [{
                    xtype: 'buttongroup',
                    columns: 8,
                    items: [
                        Ext.apply(new Ext.SplitButton(this.action_write), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top',
                            arrowAlign:'right',
                            menu: new Ext.menu.Menu({
                                items: [],
                                plugins: [{
                                    ptype: 'ux.itemregistry',
                                    key:   'Tine.widgets.grid.GridPanel.addButton'
                                }]
                            })
                        }),
                        Ext.apply(new Ext.Button(this.action_deleteRecord), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        }),
                        Ext.apply(button_reply, {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign:'top',
                            arrowAlign:'right'
                        }),
                        Ext.apply(button_replyAll, {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        }),
                        Ext.apply(button_forward, {
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
            id: 'smime',
            header: this.app.i18n._("Security"),
            width: 12,
            sortable: true,
            dataIndex: 'smime',
            renderer: this.smimeRenderer
        }, {
            id: 'content_type',
            header: this.app.i18n._("Attachments"),
            width: 12,
            sortable: true,
            dataIndex: 'has_attachment',
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
     * Smime renderer
     *
     * @param {String} value
     * @return {String}
     * @private
     */
    smimeRenderer: function(value) {
        var icons = [], result = '';
        value = new String(value);

        if (value && value.match(/1/))  
        {
            icons.push({src: 'images/oxygen/16x16/mimetypes/application-pkcs7-signature.png', qtip: i18n._('Assinado')});  // i18n._('Signed')
        }
        if (value && value.match(/2/))
        { 
            icons.push({src: 'images/oxygen/16x16/actions/encrypted.png', qtip: i18n._('Criptografado')});   //_('Encrypted')
        }
        if (value && value.match(/3/))
        {
            icons.push({src: 'images/oxygen/16x16/mimetypes/application-zip.png', qtip: i18n._('Compactado')});  //_('Compressed')
        }
        if (value && value.match(/4/))
        {
            icons.push({src: 'images/oxygen/16x16/mimetypes/application-pkcs7-mime.png', qtip: i18n._('Apenas Certificados')});   //_('Certs Only')
        }
        if (value && value.match(/5/))
        {
            icons.push({src: 'images/oxygen/16x16/mimetypes/application-pkcs7-mime.png', qtip: i18n._('pkcs7-mime')});
        }

        Ext.each(icons, function(icon) {
            result += '<img class="FelamimailFlagIcon" src="' + icon.src + '" ext:qtip="' + icon.qtip + '">';
        }, this);

        return result;
    },

    /**
     * attachment column renderer
     *
     * @param {String} value
     * @return {String}
     * @private
     */
    attachmentRenderer: function(value, metadata, record) {
        var result = '';

        if (value == 1) {
            result = '<img class="ExpressomailFlagIcon" src="images/oxygen/16x16/actions/attach.png">';
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
       
        if (record.get('reading_conf')) {
            icons.push({src: 'images/oxygen/16x16/actions/mail-mark-task.png', qtip: Ext.util.Format.htmlEncode(i18n._('Confirmação de Leitura'))});  //_('Reading Confirmation')
        }

        if (record.hasFlag('\\Answered')) {
            icons.push({src: 'images/oxygen/16x16/actions/mail-reply-sender.png', qtip: Ext.util.Format.htmlEncode(i18n._('Respondida'))}); //_('Answered')
        }
        if (record.hasFlag('Passed')) {
            icons.push({src: 'images/oxygen/16x16/actions/mail-forward.png', qtip: Ext.util.Format.htmlEncode(i18n._('Encaminhada'))}); //_('Forwarded')
        }
//        if (record.hasFlag('\\Recent')) {
//            icons.push({src: 'images/oxygen/16x16/actions/knewstuff.png', qtip: i18n._('Recent')});
//        }

        Ext.each(icons, function(icon) {
            result += '<img class="ExpressomailFlagIcon" src="' + icon.src + '" ext:qtip="' + Tine.Tinebase.common.doubleEncode(icon.qtip) + '">';
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
            account = this.app.getAccountStore().getById(record.get('account_id')),
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
        var folder = this.getCurrentFolderFromTree(),
            refresh = this.pagingToolbar.refresh;

        // refresh is explicit
        this.editBuffer = [];
        this.manualRefresh = true;

        if (folder) {
            refresh.disable();
            Tine.log.info('User forced mail check for folder "' + folder.get('localname') + '"');
            this.app.checkMails(folder, function() {
                refresh.enable();
                this.manualRefresh = false;
            });
        } else {
            this.filterToolbar.onFilterChange();
        }
    },

        /**
     * Export messages handler
     *
     * @return {void}
     */
    onExportMsgs: function() {
        var sm = this.getGrid().getSelectionModel(),
            filter = sm.getSelectionFilter(),
            msgsIds = '';
        if (sm.isFilterSelect) {
            var msgs = this.getStore(),
                nextRecord = null;
        } else {
            var msgs = sm.getSelectionsCollection(),
                nextRecord = this.getNextMessage(msgs);
        }
        var increaseUnreadCountInTargetFolder = 0;
        msgs.each(function(msg) {
            if(msgsIds.length==0) {
                msgsIds = msgsIds + msg.id;
            } else {
                msgsIds = msgsIds + ',' + msg.id;
            }
        },  this);
        var downloader = new Ext.ux.file.Download({
            params: {
                method: 'Expressomail.downloadMessage',
                requestType: 'HTTP',
                messageId: msgsIds
            }
        }).start();

    },

    /**
     * get currently selected folder from tree
     * @return {Tine.Expressomail.Model.Folder}
     */
    getCurrentFolderFromTree: function() {
        var tree = this.app.getMainScreen().getTreePanel(),
            node = tree ? tree.getSelectionModel().getSelectedNode() : null,
            folder = node ? this.app.getFolderStore().getById(node.id) : null;

        return folder;
    },

    /**
     * delete messages handler
     *
     * @return {void}
     */
    onDeleteRecords: function() {
        var account = this.app.getActiveAccount(),
            trashId = (account) ? account.getTrashFolderId() : null,
            trash = trashId ? this.app.getFolderStore().getById(trashId) : null,
            trashConfigured = (account.get('trash_folder'));

        if(Tine.Expressomail.registry.get('preferences').get('confirmDelete') == '1')
        {
            Ext.MessageBox.confirm('', this.app.i18n._('Confirm Delete') + ' ?', function(btn) {
                if(btn == 'yes') {
                    return (Tine.Expressomail.registry.get('preferences').get('confirmUseTrash') == '1' && (trash && ! trash.isCurrentSelection()) || (! trash && trashConfigured)) ? this.moveSelectedMessages(trash, true) : this.deleteSelectedMessages();
                }
                this.focusSelectedMessage();
            }, this);
        }
        else
            {
                return (Tine.Expressomail.registry.get('preferences').get('confirmUseTrash') == '1' && (trash && ! trash.isCurrentSelection()) || (! trash && trashConfigured)) ? this.moveSelectedMessages(trash, true) : this.deleteSelectedMessages();
            }
    },

    focusSelectedMessage: function(){

        // Return focus to grid
        var record = this.getGrid().getSelectionModel().getSelected();
        if (record) {
            this.getGrid().getView().focusRow(this.getGrid().store.indexOf(record));
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
     * @param {Tine.Expressomail.Model.Folder} folder
     * @param {Boolean} toTrash
     */
    moveSelectedMessages: function(folder, toTrash) {
        if (folder && folder.isCurrentSelection()) {
            // nothing to do ;-)
            return;
        }

        this.moveOrDeleteMessages(folder, toTrash);
    },

    /**
     * move (folder !== null) or delete selected messages
     *
     * @param {Tine.Expressomail.Model.Folder} folder
     * @param {Boolean} toTrash
     */
    moveOrDeleteMessages: function(folder, toTrash) {

        // this is needed to prevent grid reloads while messages are moved or deleted
        this.movingOrDeleting = true;

        var sm = this.getGrid().getSelectionModel(),
            filter = sm.getSelectionFilter(),
            msgsIds = [],
            foldersNeedUpdate = false;

        if (sm.isFilterSelect) {
            var msgs = this.getStore(),
                nextRecord = null;
        } else {
            var msgs = sm.getSelectionsCollection(),
                nextRecord = this.getNextMessage(msgs);
        }

        var folderStore = this.app.getFolderStore(),
            account = this.app.getActiveAccount(),
            dRegexp = new RegExp('\\'+account.get('delimiter')+'$'),
            inboxName = account.get('ns_personal').replace(dRegexp, '').toUpperCase(),
            inboxRecord = folderStore.getAt(folderStore.findExact('globalname', inboxName)),
            totalSize = 0,
            quotaLimit = parseInt(inboxRecord.get('quota_limit'), 10)*1024,
            quotaUsage = parseInt(inboxRecord.get('quota_usage'), 10)*1024,
            trashId = (account) ? account.getTrashFolderId() : null,
            trashConfigured = (account.get('trash_folder')),
            trashRecord = trashId ?
                folderStore.getById(trashId) :
                folderStore.find('globalname', trashConfigured);

        msgs.each(function (msg){
            totalSize += parseInt(msg.get('size'));
        }, this);


        if (isNaN(totalSize) || isNaN(quotaLimit) || isNaN(quotaUsage)){
            Tine.log.debug('Tine.Expressomail.GridPanel::moveOrDeleteMessages - isNaN value ');
            return;
        }

        if (toTrash && quotaUsage + totalSize > quotaLimit){
            var config = {
                title: this.app.i18n._('Not Enough Space to Move Message to Trash Folder.',
                    msgs.length),
                msg: this.app.i18n._('YOU CAN DELETE MESSAGE PERMANENTLY.', msgs.length)
                    + '<br />' + this.app.i18n._("This operation can't be undone!"),
                buttons: {
                    // Changed order of buttons
                    cancel: this.app.i18n._('Cancel'),
                    yes:    this.app.i18n._('Delete Permanently')
                },
                fn: function(button) {
                    if (button === 'yes') {
                        this.moveOrDeleteMessages(null);
                    }
                },
                icon: Ext.MessageBox.WARNING,
                scope: this
            };
            // Don't have enough space to move, see if trash folder is not empty
            if (trashRecord.get('cache_totalcount') <= 0) {
                // Ask to delete permanently.
                Ext.MessageBox.show(config);
            } else {
                config.msg = this.app.i18n._('Your trash folder is not empty!') + '<br />'
                    + this.app.i18n._('You can CANCEL this operation and clean trash before try again.') + '<br />'
                    + this.app.i18n._('or,') + '<br />' + config.msg;
                Ext.MessageBox.show(config);
//                Ext.MessageBox.show({
//                    title: this.app.i18n._('Not Enough Space to Move Message to Trash Folder.',
//                        msgs.length),
//                    msg: this.app.i18n._('Do you want to clean trash folder before move message?',
//                        msgs.length),
//                    buttons: {
//                        cancel: this.app.i18n._('Cancel'),
//                        yes:    this.app.i18n._('Clean Trash Folder')
//                    },
//                    fn: function(button) {
//                        if (button === 'yes') {
//                            // Clean Trash Folder
//                            var treePanel = this.app.getMainScreen().getTreePanel(),
//                                trashNode = treePanel.getNodeById(trashId);
//                            if (trashNode){
//                                trashNode.getUI().addClass("x-tree-node-loading");
//                            }
//                            Ext.Ajax.request({
//                                params: {
//                                    method: 'Expressomail.emptyFolder',
//                                    folderId: trashId
//                                },
//                                scope: this,
//                                //success: function(result, request, folder, toTrash, treePanel, trashId, trashNode){
//                                success: function(result, request){
//                                    var selectedNode = treePanel.getSelectionModel().getSelectedNode(),
//                                        isTrashSelected = (trashNode && selectedNode.id === trashNode.id),
//                                        responseJson = Ext.util.JSON.decode(result.responseText);
//
//                                    if (isTrashSelected) {
//                                        var folder = Tine.Expressomail.folderBackend.recordReader(result);
//                                        folder.set('cache_unreadcount', 0);
//                                        folder.set('cache_totalcount', 0);
//                                        folder.set('quota_usage', responseJson.quota_usage);
//                                        inboxRecord.set('quota_usage', responseJson.quota_usage);
//                                        this.app.getFolderStore().updateFolder(folder);
//                                    } else {
//                                        var folder = this.app.getFolderStore().getById(trashId);
//                                        folder.set('cache_unreadcount', 0);
//                                        folder.set('cache_totalcount', 0);
//                                        folder.set('quota_usage', responseJson.quota_usage);
//                                        inboxRecord.set('quota_usage', responseJson.quota_usage);
//                                    }
//                                    if (trashNode){
//                                        trashNode.getUI().removeClass("x-tree-node-loading");
//                                        trashNode.removeAll();
//                                    }
//                                    // Trash folder cleaned successfully try to move messages again.
//                                    this.moveOrDeleteMessages(folder, toTrash);
//                                }.createDelegate(this),
//                                failure: function() {
//                                    if (trashNode){
//                                        trashNode.getUI().removeClass("x-tree-node-loading");
//                                    }
//                                }.createDelegate(this),
//                                timeout: 120000 // 2 minutes
//                            });
//                        }
//                        // canceled
//                    },
//                    icon: Ext.MessageBox.QUESTION,
//                    scope: this
//                 });
            }
            return;
        }

        var increaseUnreadCountInTargetFolder = 0;
        msgs.each(function(msg) {
            var isSeen = msg.hasFlag('\\Seen'),
                currFolder = this.app.getFolderStore().getById(msg.get('folder_id')),
                diff = isSeen ? 0 : 1;

            if (currFolder) {
                currFolder.set('cache_unreadcount', currFolder.get('cache_unreadcount') - diff);
                currFolder.set('cache_totalcount', currFolder.get('cache_totalcount') - 1);
                if (sm.isFilterSelect && sm.getCount() > 50 && currFolder.get('cache_status') !== 'pending') {
                    Tine.log.debug('Tine.Expressomail.GridPanel::moveOrDeleteMessages - Set cache status to pending for folder ' + currFolder.get('globalname'));
                    currFolder.set('cache_status', 'pending');
                    foldersNeedUpdate = true;
                }
                currFolder.commit();
            }
            if (folder) {
                increaseUnreadCountInTargetFolder += diff;
            }

            msgsIds.push(msg.id);
            this.getStore().remove(msg);
        },  this);

        if (folder) {
            if(increaseUnreadCountInTargetFolder > 0){
                // update unread count of target folder (only when moving)
                folder.set('cache_unreadcount', folder.get('cache_unreadcount') + increaseUnreadCountInTargetFolder);
                if (foldersNeedUpdate) {
                    Tine.log.debug('Tine.Expressomail.GridPanel::moveOrDeleteMessages - Set cache status to pending for target folder ' + folder.get('globalname'));
                    folder.set('cache_status', 'pending');
                }
            }
            folder.set('cache_totalcount', folder.get('cache_totalcount') + msgsIds.length);
            folder.commit();
        }

        if (foldersNeedUpdate) {
            Tine.log.debug('Tine.Expressomail.GridPanel::moveOrDeleteMessages - update message cache for "pending" folders');
            this.app.checkMailsDelayedTask.delay(1000);
        }

        this.deleteQueue = this.deleteQueue.concat(msgsIds);
        this.pagingToolbar.refresh.disable();
        if (nextRecord !== null) {
            sm.selectRecords([nextRecord]);
        }
        this.waitForReload = true;
        var callbackFn = this.onAfterDelete.createDelegate(this, [msgsIds]);

        if (folder !== null || toTrash) {
            // move
            var targetFolderId = (toTrash) ? '_trash_' : folder.id;
            this.deleteTransactionId = Tine.Expressomail.messageBackend.moveMessages(filter, targetFolderId, {
                callback: callbackFn
            });
        } else {
            // delete
            this.deleteTransactionId = Tine.Expressomail.messageBackend.addFlags(filter, '\\Deleted', {
                callback: callbackFn
            });
        }
    },

    /**
     * get next message in grid
     *
     * @param {Ext.util.MixedCollection} msgs
     * @return Tine.Expressomail.Model.Message
     */
    getNextMessage: function(msgs) {

        var nextRecord = null;

        if (msgs.getCount() == 1 && this.getStore().getCount() > 1) {
            // select next message (or previous if it was the last or BACKSPACE)
            var lastIdx = this.getStore().indexOf(msgs.last()),
                direction = Ext.EventObject.getKey() == Ext.EventObject.BACKSPACE ? -1 : +1;

            nextRecord = this.getStore().getAt(lastIdx + 1 * direction);
            if (! nextRecord) {
                nextRecord = this.getStore().getAt(lastIdx + (-1) * direction);
            }
        }

        return nextRecord;
    },
    
    onEditClose: function(contact) {
        Tine.log.debug('Tine.Expressomail.GridPanel::onEditClose / arguments:' + contact);
        
        var toAdd = new Tine.Addressbook.Model.EmailAddress(Ext.util.JSON.decode(contact));
        var mailStore = Tine.Expressomail.getMailStore();
        mailStore.addSorted(toAdd);
        //mailStore.sort('email', 'ASC');
        mailStore.commitChanges();
        
    },

    /**
     * executed after a msg compose
     *
     * @param {String} composedMsg
     * @param {String} action
     * @param {Array}  [affectedMsgs]  messages affected
     * @param {String} [mode]
     */
    onAfterCompose: function(composedMsg, action, affectedMsgs, mode) {
        Tine.log.debug('Tine.Expressomail.GridPanel::onAfterCompose / arguments:');
        Tine.log.debug(arguments);

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

        var composerAccount = this.app.getAccountStore().getById(composedMsg.get('account_id')),
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
                    this.deleteTransactionId = Tine.Expressomail.messageBackend.addFlags(msg.id, '\\Deleted', {
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
     */
    onAfterDelete: function(ids) {
        this.deleteQueue = this.deleteQueue.diff(ids);
        this.editBuffer = this.editBuffer.diff(ids);

        this.movingOrDeleting = false;

        // if cursor is out of messages ranges then redirect to last page
        if((this.pagingToolbar.store.getTotalCount() > 0)
            && (this.pagingToolbar.cursor >= this.pagingToolbar.store.getTotalCount())) {

            Tine.log.debug('Tine.Expressomail.GridPanel::onAfterDelete() -> Redirecting to the last page after delete.');
            this.pagingToolbar.moveLast();
            this.focusSelectedMessage();
        }
        else {
            Tine.log.debug('Tine.Expressomail.GridPanel::onAfterDelete() -> Loading grid data after delete.');
            this.loadGridData({
                scope: this,
                callback: function(){
                    this.waitForReload = false;
                    this.focusSelectedMessage();
                },
                removeStrategy: 'keepBuffered',
                autoRefresh: true
            });
        }
    },

    /**
     * check if delete/move action is running atm
     *
     * @return {Boolean}
     */
    noDeleteRequestInProgress: function() {
        return (
            ! this.movingOrDeleting &&
            (! this.deleteTransactionId || ! Tine.Expressomail.messageBackend.isLoading(this.deleteTransactionId))
        );
    },

    /**
     * compose new message handler
     * @param encrypted open as encrypted message composer
     */
    onMessageCompose: function(encrypted) {
        var activeAccount = Tine.Tinebase.appMgr.get('Expressomail').getActiveAccount();
        
        var win = Tine.Expressomail.MessageEditDialog.openWindow({
            encrypted: encrypted === true?true:false,
            mailStoreData: Tine.Expressomail.getMailStoreData(Tine.Expressomail.getMailStore()),
            accountId: activeAccount ? activeAccount.id : null,
            editCloseCallback: this.onEditClose.createDelegate(this), // workaround for this to work properly
            listeners: {
                'update': this.onAfterCompose.createDelegate(this, ['compose', []], 1)
            }
        });
    },

    /**
     * forward message(s) handler
     * 
     * @param {bool} fetch
     * @param {bool} encrypted open as encrypted message composer
     */
    onMessageForward: function(fetch,encrypted) {
        var sm = this.getGrid().getSelectionModel(),
            msgs = sm.getSelections(),
            msgsData = [];
        
        var bodyIsFetched = true;
        Ext.each(msgs, function(msg) {
            msgsData.push(msg.data)
            if (!msg.bodyIsFetched()) bodyIsFetched = false;
        }, this);
        
        if (sm.getCount() > 0) {
            if (!bodyIsFetched) {
                    this.detailsPanel.getLoadMask().hide();
                    if(this.fetchtry < 4){
                        this.fetchtry++;
                        this.getLoadMask().show();
                        if (fetch !== false) sm.selectRecords(msgs);
                        this.dblClickDelayedTask = new Ext.util.DelayedTask(this.onMessageForward, this, [false,encrypted]);
                        this.dblClickDelayedTask.delay(1000);
                        return;
                    }
                }
            this.fetchtry =0;    
            var win = Tine.Expressomail.MessageEditDialog.openWindow({
                encrypted: encrypted || false,
                mailStoreData: Tine.Expressomail.getMailStoreData(Tine.Expressomail.getMailStore()),
                forwardMsgs : Ext.encode(msgsData),
                listeners: {
                    'update': this.onAfterCompose.createDelegate(this, ['forward', msgs], 1),
                    'addcontact': this.onEditClose.createDelegate(this)
                }
            });
            this.getLoadMask().hide();
        }
    },
            
    /**
     * reply message handler
     *
     * @param {bool} toAll
     * @param {bool} fetch
     * @param {bool} encrypted open as encrypted message composer
     */
    onMessageReplyTo: function(toAll, fetch, encrypted) {
        var sm = this.getGrid().getSelectionModel(),
            msg = sm.getSelected();

        if (msg) {
            if (!msg.bodyIsFetched()) {
                   this.detailsPanel.getLoadMask().hide();
                    if(this.fetchtry < 4){
                        this.fetchtry++;
                        this.getLoadMask().show();
                        if (fetch !== false) sm.selectRecords([msg]);
                        this.dblClickDelayedTask = new Ext.util.DelayedTask(this.onMessageReplyTo, this, [toAll,false,encrypted]);
                        this.dblClickDelayedTask.delay(1000);
                        return;
                    }
                }
            this.fetchtry =0;     
            var win = Tine.Expressomail.MessageEditDialog.openWindow({
                encrypted: encrypted || false,
                mailStoreData: Tine.Expressomail.getMailStoreData(Tine.Expressomail.getMailStore()),
                replyTo : Ext.encode(msg.data),
                replyToAll: toAll,
                listeners: {
                    update: this.onAfterCompose.createDelegate(this, ['reply', [msg]], 1),
                    addcontact: this.onEditClose.createDelegate(this)
                }
            });
        }
        this.getLoadMask().hide();
    },

    /**
     * called when a row gets selected
     *
     * @param {SelectionModel} sm
     * @param {Number} rowIndex
     * @param {Tine.Expressomail.Model.Message} record
     * @param {Number} retryCount
     *
     * TODO find a better way to check if body is fetched, this does not work correctly if a message is removed
     *       and the next one is selected automatically
     */
    onRowSelection: function(sm, rowIndex, record, retryCount) {
        if (sm.getCount() == 1 && ((! retryCount || retryCount < 5) && ! record.bodyIsFetched() || this.waitForReload)) {
            Tine.log.debug('Tine.Expressomail.GridPanel::onRowSelection() -> Deferring onRowSelection');
            retryCount = (retryCount) ? retryCount++ : 1;
            return this.onRowSelection.defer(250, this, [sm, rowIndex, record, retryCount+1]);
        }

        if (sm.getCount() == 1 && sm.isIdSelected(record.id) && !record.hasFlag('\\Seen')) {
            Tine.log.debug('Tine.Expressomail.GridPanel::onRowSelection() -> Selected unread message');
            Tine.log.debug(record);

            record.addFlag('\\Seen');
            record.mtime = new Date().getTime();
            Tine.Expressomail.messageBackend.addFlags(record.id, '\\Seen');
            this.app.getMainScreen().getTreePanel().decrementCurrentUnreadCount();

//            if (record.get('headers')['disposition-notification-to']) {
//                Ext.Msg.confirm(
//                    this.app.i18n._('Send Reading Confirmation'),
//                    this.app.i18n._('Do you want to send a reading confirmation message?'),
//                    function(btn) {
//                        if (btn == 'yes'){
//                            Tine.Expressomail.sendReadingConfirmation(record.id);
//                        }
//                    },
//                    this
//                );
//            }
        }
    },

    /**
     * row doubleclick handler
     *
     * - opens message edit dialog (if draft/template)
     * - opens message display dialog (everything else)
     *
     * @param {Tine.Expressomail.GridPanel} grid
     * @param {Row} row
     * @param {Event} e
     */
    onRowDblClick: function(grid, row, e) {
        if (this.isRowOutdated(row)) {
            // cannot open messages marked as outdated
            return;
        }
        var encrypted = this.detailsPanel.record.get('smimeEml') != '';
        var decrypted = this.detailsPanel.record.get('decrypted') || false;
        if (encrypted && !decrypted) {
            if (!this.detailsPanel.isDecrypting) {
                this.detailsPanel.reloadDetails();
            }
            this.detailsPanel.getLoadMask().hide();
            this.getLoadMask().show();
            this.detailsPanel.addListener('dblwindow', this.openDblClickWindow, this);
        }
        else {
            this.openDblClickWindow(grid, row, e);
        }
    },
    
    openDblClickWindow: function(grid, row, e){
        this.detailsPanel.un('dblwindow',this.openDblClickWindow, this);
        this.getLoadMask().hide();
                var record = this.grid.getSelectionModel().getSelected(),
            folder = this.app.getFolderStore().getById(record.get('folder_id')),
            account = this.app.getAccountStore().getById(folder.get('account_id')),
            action = (folder.get('globalname') == account.get('drafts_folder')) ? 'senddraft' :
                      folder.get('globalname') == account.get('templates_folder') ? 'sendtemplate' : null,
            win;
        
        var sm = this.getGrid().getSelectionModel(),
            msg = sm.getSelected();

        if (!record.bodyIsFetched()) {
            if (!msg.bodyIsFetched()) {
                   this.detailsPanel.getLoadMask().hide();
                    if(this.fetchtry < 4){
                        this.fetchtry++;
                        this.getLoadMask().show();
                        this.dblClickDelayedTask = new Ext.util.DelayedTask(this.onRowDblClick, this, [grid,row,e]);
                        this.dblClickDelayedTask.delay(1000);
                        return;
                    }
                }
         }     
        this.fetchtry =0;   
        // check folder to determine if mail should be opened in compose dlg
        if (action !== null) {
            win = Tine.Expressomail.MessageEditDialog.openWindow({
                mailStoreData: Tine.Expressomail.getMailStoreData(Tine.Expressomail.getMailStore()),
                draftOrTemplate: Ext.encode(record.data),
                listeners: {
                    scope: this,
                    'update': this.onAfterCompose.createDelegate(this, [action, [record]], 1),
                    'addcontact': this.onEditClose.createDelegate(this)
                    }
            });
        } else {
            win = Tine.Expressomail.MessageDisplayDialog.openWindow({
                mailStoreData: Tine.Expressomail.getMailStoreData(Tine.Expressomail.getMailStore()),
                record: Ext.encode(record.data),
                listeners: {
                    scope: this,
                    'update': this.onAfterCompose.createDelegate(this, ['compose', []], 1),
                    'remove': this.onRemoveInDisplayDialog,
                    'addcontact': this.onEditClose.createDelegate(this)
                    }
            });
        }
    },
    
    /**
     * When message edit window is closed cancel further autosave
     *
     */
    onMessageEditWindowDestroy: function() {
        this.saveDraftsInterval = 0; // prevent more save draft calls
        this.unloading = true;
        var grid = this.window.ref.getMainScreen().getCenterPanel();
        if (!this.saving) { // just check and save draft if it is not already doing it
            if (this.autoSaveDraftsEnabled) {
                this.checkDraftChanges();
            }
            else {
                if (this.checkDraftChanges(grid.saveOnDestroy.forced)) {
                    grid.confirmSaveDraft();
                }
            }
        }
    },

    confirmSaveDraft: function() {
        this.saveOnDestroy.confirm = true;
        Ext.MessageBox.confirm('', this.app.i18n._('Keep as a draft?'),
            function(btn) {
                if(btn === 'yes') {
                    if (this.isDraftFolderSelected && this.saveOnDestroy.record) {
                        this.updateGridPanel(this.saveOnDestroy.record);
                    }
                    else {
                        if (this.saveOnDestroy.error) {
                            this.showSaveDraftErrorMessage();
                        }
                    }
                    this.saveOnDestroy.save = true;
                }
                else {
                    if (this.saveOnDestroy.record) {
                        this.removeDraft(this.saveOnDestroy.record);
                    }
                    else {
                        this.saveOnDestroy.save = false;
                    }
                }
                this.saveOnDestroy.confirm = false;
                this.saveOnDestroy.started = true;
            },
        this);
    },

    /**
     * call recordproxy saveDraft and set callback functions
     *
     * @param {Tine.Tinebase.data.Record} record
     * @param {String} folderName
     * @param {Tine.Expressomail.MessageEditDialog} editwindow
     * @param {Boolean} forced
     */
    callSaveDraft: function(record, folderName, editwindow, forced) {
        // this is in GridPanel instead of MessageEditWindow
        // because of 'Browser' style window related issues in IE
        this.saveOnDestroy = {
            confirm: false, // true if confirmSaveDraft is in progress
            started: false, // true if either confirmSaveDraft or onSaveDraftSuccess are completed
            save: false, // true if user selected YES on confirmSaveDraft
            error: false, // true if onSaveDraftFailure has been called,
            forced: forced || false, // true if draft saving was called by clicking 'Save Draft' button
            record: null // message record
        };
        if (editwindow.isDraftFolderSelected) {
            this.isDraftFolderSelected = true;
            this.markRowOutdated(record.get('original_id'));
        }
        else {
            this.isDraftFolderSelected = false;
        }
        this.recordProxy.saveDraft(record, folderName, {
            scope: this,
            success: function(record) { this.onSaveDraftSuccess(record, editwindow); },
            failure: function(record) { this.onSaveDraftFailure(record, editwindow); },
            timeout: 60000 // 1 minute
        });
    },

    /**
     * process the result when save draft is successful
     *
     * @param {Tine.Tinebase.data.Record} record
     * @param {Tine.Expressomail.MessageEditDialog} editwindow
     */
    onSaveDraftSuccess: function(record, editwindow) {
        var unloading;
        try { // because editwindow could be closed yet
            unloading = editwindow.unloading;
        } catch (e) { // editwindow already closed, unloading must be true
            unloading = true;
        }
        if (this.isDraftFolderSelected) {
            if (this.autoSaveDraftsEnabled || this.saveOnDestroy.save || (this.saveOnDestroy.forced && !unloading)) {
                this.updateGridPanel(record);
            }
            else {
                this.saveOnDestroy.record = record;
            }
        }
        if (!this.autoSaveDraftsEnabled) {
            if (this.saveOnDestroy.started && !this.saveOnDestroy.save) {
                this.removeDraft(record);
            }
            else {
                this.saveOnDestroy.started = true;
            }
        }
        try { // because editwindow could be closed yet
            editwindow.record.commit(true);
            if (!editwindow.record.get('initial_id')) {
                editwindow.record.set('initial_id', record.get('draft_id'));
            }
            editwindow.record.set('draft_id', record.get('original_id'));
            editwindow.setMessageOnTitle(editwindow.notifyClear);
            editwindow.setSaveDraftsDelayedTask();
            editwindow.saving = false;
        }
        catch (e) { } // editwindow already closed, ignore and go on
    },

    /**
     * process the result when save draft has failed
     *
     * @param {Tine.Tinebase.data.Record} record
     * @param {Tine.Expressomail.MessageEditDialog} editwindow
     */
    onSaveDraftFailure: function(record, editwindow) {
        if (!this.autoSaveDraftsEnabled) {
            this.saveOnDestroy.error = true;
            if (this.saveOnDestroy.started && this.saveOnDestroy.save) {
                this.showSaveDraftErrorMessage();
            }
        }
        try { // because editwindow could be closed yet
            editwindow.setMessageOnTitle(editwindow.notifySaveDraftFailure);
            editwindow.setSaveDraftsDelayedTask();
            editwindow.saving = false;
        }
        catch (e) { } // editwindow already closed, ignore and go on
    },

    showSaveDraftErrorMessage: function() {
        Ext.MessageBox.show({
            title: this.app.i18n._('Failed to save draft!'),
            msg: this.app.i18n._('A draft could not be saved. Maybe the connection to the server was lost.'),
            buttons: Ext.MessageBox.OK,
            icon: Ext.MessageBox.WARNING
        });
    },

    /**
     * remove draft (setting flag to '\Deleted')
     *
     * @param {Tine.Tinebase.data.Record} record
     */
    removeDraft: function(record) {
        var id = record.get('original_id');
        if (id) {
            if (Ext.isObject(id)) {
                id = id.id;
            }
            this.deleteTransactionId = Tine.Expressomail.messageBackend.addFlags(id, '\\Deleted', {
                callback: function(msgId){
                    this.movingOrDeleting = false;
                }
            });
            if (this.saveOnDestroy.forced) {
                // draft in grid is outdated
                id = record.get('draft_id');
            }
            var msg = this.getStore().getById(id);
            this.getStore().remove(msg);
        }
    },

    /**
     * mark grid row as outdated
     *
     * @param {String} id
     */
    markRowOutdated: function(id) {
        if (id) {
            var msg = this.getStore().getById(id);
            msg.markDirty();
            var ix = this.getStore().indexOf(msg);
            this.getView().addRowClass(ix, 'x-grid-outdated-row');
        }
    },

    /**
     * returns true if grid row is marked as outdated
     *
     * @param {Integer} row
     * @returns {Boolean} true or false
     */
    isRowOutdated : function(row) {
        var r = this.getView().getRow(row);
        return (this.getView().fly(r).hasClass('x-grid-outdated-row'));
    },

    /**
     * update grid panel with record changes
     *
     * @param {Tine.Tinebase.data.Record} record
     */
    updateGridPanel: function(record) {
        var id = record.get('draft_id');
        if (id) {
            if (Ext.isObject(id)) {
                id = id.id;
            }
            var old_msg = this.getStore().getById(id);
            this.getStore().remove(old_msg);
        }
        var msg = record.copy();
        msg.set('id',record.get('original_id'));
        msg.id = record.get('original_id');
        this.addRecordToStore(msg);
    },

    /**
     * add a record to the grid store
     *
     * @param {Tine.Tinebase.data.Record} record
     */
    addRecordToStore: function(record) {
        var store = this.getStore();
        store.suspendEvents();
        var data = store.data.clone();
        store.data.add(record);
        store.sortData(store.sortInfo.field, store.sortInfo.direction);
        var index = store.data.indexOf(record);
        store.data = data;
        store.resumeEvents();
        store.insert(index, record);
    },

     /**
     * Opens the required EditDialog
     * @param {Object} actionButton the button the action was called from
     * @param {Tine.Tinebase.data.Record} record the record to display/edit in the dialog
     * @param {Array} plugins the plugins used for the edit dialog
     * @param {Object} additionalConfig plain Object, which will be applied to the edit dialog on initComponent
     * @return {Boolean}
     */
    onEditInNewWindow: function(button, record, plugins, additionalConfig) {
        if (! record) {
            if (button.actionType == 'edit' || button.actionType == 'copy') {
                if (! this.action_editInNewWindow || this.action_editInNewWindow.isDisabled()) {
                    // if edit action is disabled or not available, we also don't open a new window
                    return false;
                }
                var selectedRows = this.grid.getSelectionModel().getSelections();
                record = selectedRows[0];
            } else {
                record = new this.recordClass(this.recordClass.getDefaultData(), 0);
            }
        }

        // plugins to add to edit dialog
        var plugins = plugins ? plugins : [];
        
        var totalcount = this.selectionModel.getCount(),
            selectedRecords = [],
            fixedFields = (button.hasOwnProperty('fixedFields') && Ext.isObject(button.fixedFields)) ? Ext.encode(button.fixedFields) : null,
            editDialogClass = this.editDialogClass || Tine[this.app.appName][this.recordClass.getMeta('modelName') + 'EditDialog'],
            additionalConfig = additionalConfig ? additionalConfig : {};
        
        // add "multiple_edit_dialog" plugin to dialog, if required
        if (((totalcount > 1) && (this.multipleEdit) && (button.actionType == 'edit'))) {
            Ext.each(this.selectionModel.getSelections(), function(record) {
                selectedRecords.push(record.data);
            }, this );
            
            plugins.push({
                ptype: 'multiple_edit_dialog', 
                selectedRecords: selectedRecords,
                selectionFilter: this.selectionModel.getSelectionFilter(),
                isFilterSelect: this.selectionModel.isFilterSelect,
                totalRecordCount: totalcount
            });
        }
        
        var popupWindow = editDialogClass.openWindow(Ext.copyTo(
            this.editDialogConfig || {}, {
                encrypted: button.encrypted,
                mailStoreData: Tine.Expressomail.getMailStoreData(Tine.Expressomail.getMailStore()),
                plugins: plugins ? Ext.encode(plugins) : null,
                fixedFields: fixedFields,
                additionalConfig: Ext.encode(additionalConfig),
                record: editDialogClass.prototype.mode == 'local' ? Ext.encode(record.data) : record,
                copyRecord: (button.actionType == 'copy'),
                listeners: {
                        scope: this,
                    'update': ((this.selectionModel.getCount() > 1) && (this.multipleEdit)) ? this.onUpdateMultipleRecords : this.onUpdateRecord,
                    'addcontact': this.onEditClose.createDelegate(this)
                    }
            }, 'encrypted,mailStoreData,record,listeners,fixedFields,copyRecord,plugins,additionalConfig')
        );
        return true;
    },

    /**
     * message got removed in display dialog
     *
     * @param {} msgData
     */
    onRemoveInDisplayDialog: function (msgData) {
        var msg = this.getStore().getById(Ext.decode(msgData).id),
            folderId = msg ? msg.get('folder_id') : null,
            folder = folderId ? this.app.getFolderStore().getById(folderId) : null,
            accountId = folder ? folder.get('account_id') : null,
            account = accountId ? this.app.getAccountStore().getById(accountId) : null;

        this.getStore().remove(msg);
        this.onAfterDelete(null);
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
        this.detailsPanel.isNavKey = (e.getKey()==38 || e.getKey()==40);
        if (e.getKey()==e.ENTER) {
            // load/reload currently selected message
            this.detailsPanel.reloadDetails();
        }

        Tine.Expressomail.GridPanel.superclass.onKeyDown.call(this, e);
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
                    if (sm.isFilterSelect && sm.getCount() > 50 && folder.get('cache_status') !== 'pending') {
                        Tine.log.debug('Tine.Expressomail.GridPanel::moveOrDeleteMessages - Set cache status to pending for folder ' + folder.get('globalname'));
                        folder.set('cache_status', 'pending');
                    }
                    folder.commit();
                }
            }

            msg[action + 'Flag'](flag);

            this.addToEditBuffer(msg);
        }, this);

        if (sm.isFilterSelect && sm.getCount() > 50) {
            Tine.log.debug('Tine.Expressomail.GridPanel::moveOrDeleteMessages - Update message cache for "pending" folders');
            this.app.checkMailsDelayedTask.delay(1000);
        }

        // do request
        Tine.Expressomail.messageBackend[action+ 'Flags'](filter, flag);
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
     *  called after a new set of Records has been loaded
     *
     * @param  {Ext.data.Store} this.store
     * @param  {Array}          loaded records
     * @param  {Array}          load options
     * @return {Void}
     */
    onStoreLoad: function(store, records, options) {
        
        if (this.detailsPanel.getLoadMask()) {
            this.detailsPanel.getLoadMask().hide();
        }
        
        this.supr().onStoreLoad.apply(this, arguments);

        Tine.log.debug('Tine.Expressomail.GridPanel::onStoreLoad(): store loaded new records.');

        var folder = this.getCurrentFolderFromTree();
        if (folder && records.length < folder.get('cache_totalcount')) {
            Tine.log.debug('Tine.Expressomail.GridPanel::onStoreLoad() - Count mismatch: got ' + records.length + ' records for folder ' + folder.get('globalname'));
            Tine.log.debug(folder);
            folder.set('cache_status', 'pending');
            folder.commit();
            this.app.checkMailsDelayedTask.delay(1000);
        }

        this.updateQuotaBar();
    },

    /**
     * update quotaBar / only do it if we have a path filter with a single account id
     *
     * @param {Record} accountInbox
     */
    updateQuotaBar: function(accountInbox) {
        var accountId = this.extractAccountIdFromFilter();

        if (accountId === null) {
            Tine.log.debug('No or multiple account ids in filter. Resetting quota bar.');
            this.quotaBar.hide();
            return;
        }
        var treex = this.app.getMainScreen().getTreePanel(),
                selectedNodes = (treex) ? treex.getSelectionModel().getSelectedNodes() : null;
        if(selectedNodes) {
            var accountInbox = this.app.getFolderStore().queryBy(function(folder) {
                if(folder.get('id') == selectedNodes[0].id) return folder;
            }, this).first();
            if (!parseInt(accountInbox.get('quota_limit'), 10) || !parseInt(accountInbox.get('quota_usage'), 10)) {
                accountInbox = false;
            }
        }

        if (! accountInbox) {
            var accountInbox = this.app.getFolderStore().queryBy(function(folder) {
                return folder.isInbox() && (folder.get('account_id') == accountId);
            }, this).first();
        }
        if (accountInbox && parseInt(accountInbox.get('quota_limit'), 10) && accountId == accountInbox.get('account_id')) {
            Tine.log.debug('Showing quota info.');

            var limit = parseInt(accountInbox.get('quota_limit'), 10),
                usage = parseInt(accountInbox.get('quota_usage'), 10),
                left = limit - usage,
                text = String.format(this.app.i18n._('{0} %'), Ext.util.Format.number((usage/limit * 100), '00.0'));
            this.quotaBar.show();
            this.quotaBar.setWidth(75);
            this.quotaBar.updateProgress(usage/limit, text);

            Ext.QuickTips.register({
                target:  this.quotaBar,
                dismissDelay: 30000,
                title: this.app.i18n._('Your quota'),
                text: String.format(this.app.i18n._('{0} available (total: {1})'),
                    Ext.util.Format.fileSize(left * 1024),
                    Ext.util.Format.fileSize(limit * 1024)
                ),
                width: 200
            });
        } else {
            Tine.log.debug('No account inbox found or no quota info found.');
            this.quotaBar.hide();
        }
    },

    /**
     * get account id from filter (only returns the id if a single account id was found)
     *
     * @param {Array} filter
     * @return {String}
     */
    extractAccountIdFromFilter: function(filter) {
        if (! filter) {
            filter = this.filterToolbar.getValue();
        }

        // use first OR panel in case of filterPanel
        Ext.each(filter, function(filterData) {
            if (filterData.condition && filterData.condition == 'OR') {
                filter = filterData.filters[0].filters;
                return false;
            }
        }, this);

        // condition from filterPanel
        while (filter.filters || (Ext.isArray(filter) && filter.length > 0 && filter[0].filters)) {
            filter = (filter.filters) ? filter.filters : filter[0].filters;
        }

        var accountId = null,
            filterAccountId = null,
            accountIdMatch = null;

        for (var i = 0; i < filter.length; i++) {
            if (filter[i].field == 'path' && filter[i].operator == 'in') {
                for (var j = 0; j < filter[i].value.length; j++) {
                    accountIdMatch = filter[i].value[j].match(/^\/(?!allinboxes)([a-z0-9]*)/i);
                    if (accountIdMatch) {
                        filterAccountId = accountIdMatch[1];
                        if (accountId && accountId != filterAccountId) {
                            // multiple different account ids found!
                            return null;
                        } else {
                            accountId = filterAccountId;
                        }
                    }
                }
            }
        }

        return accountId;
    },

    /**
     * add new account button
     *
     * @param {Button} button
     * @param {Event} event
     */
    onAddAccount: function(button, event) {
        var popupWindow = Tine.Expressomail.AccountEditDialog.openWindow({
            record: null,
            listeners: {
                scope: this,
                'update': function(record) {
                    var account = new Tine.Expressomail.Model.Account(Ext.util.JSON.decode(record));

                    // add to registry
                    Tine.Expressomail.registry.get('preferences').replace('defaultEmailAccount', account.id);
                    // need to do this because store could be unitialized yet
                    var registryAccounts = Tine.Expressomail.registry.get('accounts');
                    registryAccounts.results.push(account.data);
                    registryAccounts.totalcount++;
                    Tine.Expressomail.registry.replace('accounts', registryAccounts);

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
     * @todo move this to Ext.ux.Printer as iframe driver
     * @param {Tine.Expressomail.GridDetailsPanel} details panel [optional]
     */
    onPrint: function(detailsPanel) {
        var id = Ext.id(),
            doc = document,
            frame = doc.createElement('iframe');

        Ext.fly(frame).set({
            id: id,
            name: id,
            style: {
                position: 'absolute',
                width: '210mm',
                height: '297mm',
                top: '-10000px',
                left: '-10000px'
            }
        });

        doc.body.appendChild(frame);

        Ext.fly(frame).set({
           src : Ext.SSL_SECURE_URL
        });

        var doc = frame.contentWindow.document || frame.contentDocument || WINDOW.frames[id].document,
            content = this.getDetailsPanelContentForPrinting(detailsPanel || this.detailsPanel);

        doc.open();
        doc.write(content);
        doc.close();

        frame.contentWindow.focus();
        frame.contentWindow.print();

        // removeing frame chrashes chrome
//        setTimeout(function(){Ext.removeNode(frame);}, 100);
    },

    /**
     * get detail panel content
     *
     * @param {Tine.Expressomail.GridDetailsPanel} details panel
     * @return {String}
     */
    getDetailsPanelContentForPrinting: function(detailsPanel) {
        // TODO somehow we have two <div class="preview-panel-expressomail"> -> we need to fix that and get the first element found
        var detailsPanels = detailsPanel.getEl().query('.preview-panel-expressomail');
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
     * @param {Tine.Expressomail.GridDetailsPanel} details panel [optional]
     */
    onPrintPreview: function(detailsPanel) {
        var content = this.getDetailsPanelContentForPrinting(detailsPanel || this.detailsPanel);

        var win = window.open('about:blank','_blank','width=500,height=500,scrollbars=yes,toolbar=yes,status=yes,menubar=yes');
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
                    (! onlyImportant || header == 'from' || header == 'to' || header == 'cc' || header == 'subject' || header == 'date'))
            {
                if (header == 'date') headers[header] = new Date(headers[header]).toLocaleString();
                result += '<b>' + i18n._(header) + ':</b> '
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
