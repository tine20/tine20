/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
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
 * TODO         mark to delete / to move messages
 * TODO         add flagged/'starred' filter
 * TODO         add show source code function
 * TODO         make doubleclick work again: show mail in new window (no edit dialog)
 * TODO         add pdf export
 * TODO         make 'from' column non-(line-)breaking
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
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
     * @private model cfg
     */
    evalGrants: false,
    filterSelectionDelete: true,
    showDeleteMask: false,
    
    /**
     * @private grid cfg
     */
    defaultSortInfo: {field: 'received', direction: 'DESC'},
    gridConfig: {
        //loadMask: true,
        autoExpandColumn: 'subject',
        // drag n drop
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
        var flags = record.get('flags');
        //console.log(flags);
        var className = '';
        if(flags !== null) {
            if (flags.match(/Flagged/)) {
                className += ' flag_flagged';
            }
            if (flags.match(/Deleted/)) {
                className += ' flag_deleted';
            }
        }
        if (flags === null || flags.match(/Seen/) === null) {
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
        this.filterToolbar.getQuickFilterPlugin().criteriaIgnores.push(
            {field: 'folder_id'}
        );
        
        Tine.Felamimail.GridPanel.superclass.initComponent.call(this);
        
        this.grid.getSelectionModel().on('rowselect', this.onRowSelection, this);
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
            text: this.app.i18n._('Write'),
            handler: this.onEditInNewWindow,
            iconCls: this.app.appName + 'IconCls',
            scope: this
        });

        this.action_reply = new Ext.Action({
            requiredGrant: 'readGrant',
            actionType: 'reply',
            text: this.app.i18n._('Reply'),
            handler: this.onEditInNewWindow,
            iconCls: 'action_email_reply',
            scope: this,
            disabled: true
        });

        this.action_replyAll = new Ext.Action({
            requiredGrant: 'readGrant',
            actionType: 'replyAll',
            text: this.app.i18n._('Reply To All'),
            handler: this.onEditInNewWindow,
            iconCls: 'action_email_replyAll',
            scope: this,
            disabled: true
        });

        this.action_forward = new Ext.Action({
            requiredGrant: 'readGrant',
            actionType: 'forward',
            text: this.app.i18n._('Forward'),
            handler: this.onEditInNewWindow,
            iconCls: 'action_email_forward',
            scope: this,
            disabled: true
        });

        this.action_flag = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Toggle Flag'),
            handler: this.onToggleFlag,
            flag: 'Flagged',
            iconCls: 'action_email_flag',
            allowMultiple: true,
            scope: this,
            disabled: true
        });
        
        this.action_markUnread = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Mark read/unread'),
            handler: this.onToggleFlag,
            flag: 'Seen',
            iconCls: 'action_mark_unread',
            allowMultiple: true,
            scope: this,
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
         this.action_print = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Print Message'),
            handler: this.onPrint,
            disabled:true,
            iconCls:'action_print',
            scope:this
         });
         this.action_printPreview = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Print Preview'),
            handler: this.onPrintPreview,
            disabled:true,
            iconCls:'action_printPreview',
            scope:this
         });
        
        this.actionUpdater.addActions([
            this.action_write,
            this.action_reply,
            this.action_replyAll,
            this.action_forward,
            this.action_flag,
            this.action_markUnread,
            this.action_deleteRecord,
            this.action_addAccount,
            this.action_print,
            this.action_printPreview
        ]);
        
        /*
        this.actions = [
            this.action_write,
            this.action_reply,
            this.action_replyAll,
            this.action_forward,
            this.action_flag,
            this.action_markUnread,
            this.action_deleteRecord,
            '-',
            this.action_addAccount,
            '->',
            this.filterToolbar.getQuickFilterField()
        ];
        
        this.actionToolbar = new Ext.Toolbar({
            split: false,
            items: this.actions
        });
        */
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
            filterModels: [
                {label: this.app.i18n._('Subject/From'),field: 'query',         operators: ['contains']},
                {label: this.app.i18n._('Subject'),     field: 'subject',       operators: ['contains']},
                {label: this.app.i18n._('From'),        field: 'from',          operators: ['contains']},
                {label: this.app.i18n._('To'),          field: 'to',            operators: ['contains']},
                {label: this.app.i18n._('Cc'),          field: 'cc',            operators: ['contains']},
                {label: this.app.i18n._('Bcc'),         field: 'bcc',           operators: ['contains']},
                {label: this.app.i18n._('Received'),    field: 'received',      valueType: 'date', pastOnly: true}
             ],
             defaultFilter: 'query',
             filters: [],
             plugins: [
                new Tine.widgets.grid.FilterToolbarQuickFilterPlugin()
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
            i18n: this.app.i18n
        });
    },
    
    getActionToolbar: function() {
        /*
        this.actions = [
            this.action_write,
            this.action_reply,
            this.action_replyAll,
            this.action_forward,
            this.action_flag,
            this.action_markUnread,
            this.action_deleteRecord,
            '-',
            this.action_addAccount,
            '->',
            this.filterToolbar.getQuickFilterField()
        ];
        */
        
        if (! this.actionToolbar) {
            this.actionToolbar = new Ext.Toolbar({
                defaults: {height: 55},
                items: [{
                    xtype: 'buttongroup',
                    columns: 9,
                    items: [
                        Ext.apply(new Ext.Button(this.action_write), {
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
                        Ext.apply(new Ext.Button(this.action_deleteRecord), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        }),
                        Ext.apply(new Ext.Button(this.action_print), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        }),
                        Ext.apply(new Ext.Button(this.action_printPreview), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
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
            width: 12,
            sortable: true,
            dataIndex: 'content_type',
            renderer: this.attachmentRenderer
        }, {
            id: 'flags',
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
            id: 'from',
            header: this.app.i18n._("From"),
            width: 200,
            sortable: true,
            dataIndex: 'from'
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
    flagRenderer: function(flags) {
        if (!flags) {
            return '';
        }
        
        var icons = [];
        if (flags.match(/Answered/)) {
            icons.push({src: 'images/oxygen/16x16/actions/mail-reply-sender.png', qtip: _('Answered')});
        }   
        if (flags.match(/Passed/)) {
            icons.push({src: 'images/oxygen/16x16/actions/mail-forward.png', qtip: _('Forwarded')});
        }   
        if (flags.match(/Recent/)) {
            icons.push({src: 'images/oxygen/16x16/actions/knewstuff.png', qtip: _('Recent')});
        }   
        
        var result = '';
        for (var i=0; i < icons.length; i++) {
            result = result + '<img class="FelamimailFlagIcon" src="' + icons[i].src + '" ext:qtip="' + icons[i].qtip + '">';
        }
        
        return result;
    },
    
    /**
     * update class and unread count in tree on select
     * - mark mail as read
     * - check if it was unread -> update tree and remove \Seen flag
     * 
     * @param {SelectionModel} selModel
     * @param {Number} rowIndex
     * @param {Tine.Felamimail.Model.Message} r
     */
    onRowSelection: function(selModel, rowIndex, record) {
        // toggle read/seen flag of mail (only if 1 selected row)
        if (selModel.getCount() == 1) {
            //console.log(record.data.flags);
            
            var regexp = new RegExp('[ \,]*\\\\Seen');
            if (record.data.flags === null || ! record.data.flags.match(regexp)) {
                //console.log('markasread');
                record.data.flags += ' \\Seen';
                this.app.getMainScreen().getTreePanel().updateUnreadCount(-1);
                Ext.get(this.grid.getView().getRow(rowIndex)).removeClass('flag_unread');
            }
        }
    },
    
    /**
     * row doubleclick handler
     * - overwrite default behaviour: do nothing
     * 
     * @param {Tine.Felamimail.GridPanel} grid
     * @param {Row} row
     * @param {Event} e
     */
    onRowDblClick: function(grid, row, e) {
        return false;
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
                    this.onEditInNewWindow({
                        actionType: 'add'
                    }, e);
                    e.preventDefault();
                    break;
                case e.R:
                    this.onEditInNewWindow({
                        actionType: 'reply'
                    }, e);
                    e.preventDefault();
                    break;
                case e.L:
                    this.onEditInNewWindow({
                        actionType: 'forward'
                    }, e);
                    e.preventDefault();
                    break;
            }
        }
        
        Tine.Felamimail.GridPanel.superclass.onKeyDown.call(this, e);
    },
    
    /**
     * generic edit in new window handler
     * - overwritten parent func
     * - action type edit: reply/replyAll/forward
     * 
     * @param {Button} button
     * @param {Event} event
     */
    onEditInNewWindow: function(button, event) {
        
        // check if account available first
        if (Tine.Felamimail.loadAccountStore().getCount() == 0) {
            // no account -> show message
            Ext.Msg.alert(this.app.i18n._('Error'), this.app.i18n._('No Account configured'));
            return;
        }
        
        var recordData = this.recordClass.getDefaultData();
        var recordId = 0;
        var selModel = this.grid.getSelectionModel();
        
        // set selected account as from
        recordData.from = this.app.getMainScreen().getTreePanel().getActiveAccount().data.id;
        
        if (    button.actionType == 'reply'
            ||  button.actionType == 'replyAll'
            ||  button.actionType == 'forward'
        ) {
            // reply / forward
            var selectedRows = selModel.getSelections();
            if (selectedRows.length == 0) {
                return;
            }
            var selectedRecord = selectedRows[0];
            
            if (! selectedRecord.data.headers['content-type']) {
                // record is not / not fully loaded -> defer
                // TODO check if details panel is loading?
                this.detailsPanel.onDetailsUpdate(selModel);
                this.onEditInNewWindow.defer(500, this, [button, event]);
                return;
            }
            
            recordData.id = recordId;
            recordData.original_id = selectedRecord.id;
            
            var body = (selectedRecord.data.headers['content-type'].match(/text\/html/)) 
                ? selectedRecord.get('body')
                : Ext.util.Format.nl2br(selectedRecord.get('body'));
                
            recordData.cc = [];
            recordData.to = [];
            switch (button.actionType) {
                case 'replyAll':
                    recordData.cc = selectedRecord.get('cc');
                    recordData.to = selectedRecord.get('to');
                    // fallthrough
                case 'reply':
                    if (selectedRecord.data.headers['reply-to']) {
                        // use reply-to header if available
                        recordData.to.push(selectedRecord.data.headers['reply-to']);
                    } else {
                        recordData.to.push(selectedRecord.get('from'));
                    }
                    
                    recordData.body = '<br/>' + Ext.util.Format.htmlEncode(selectedRecord.get('from')) + ' ' + _('wrote') + ':<br/>'
                        + '<blockquote class="felamimail-body-blockquote">' + body + '</blockquote><br/>';
                    recordData.subject = _('Re: ') + selectedRecord.get('subject');
                    recordData.flags = '\\Answered';
                    break;
                case 'forward':
                    if (selectedRecord.get('attachments').length > 0) {
                        // add message/rfc822 attachment if original message has attachments
                        recordData.attachments = [{
                            //name: this.app.i18n._('Original Message'),
                            name: Ext.util.Format.ellipsis(selectedRecord.get('subject'), 40),
                            type: 'message/rfc822',
                            size: selectedRecord.get('size')
                        }];
                        recordData.body = '<br/>';
                    } else {
                        // only show original message in body if it has no attachments
                        recordData.body = '<br/>-----' + _('Original message') + '-----<br/>'
                            + this.formatHeaders(selectedRecord.get('headers'), false, true) + '<br/><br/>'
                            + body + '<br/>';
                    }
                    recordData.subject = _('Fwd: ') + selectedRecord.get('subject');
                    recordData.flags = 'Passed';
                    break;
            }
            
        } else if (button.actionType == 'edit') {
            
            // show existing email
            var selectedRows = selModel.getSelections();
            if (selectedRows.length == 0) {
                return;
            }
            var selectedRecord = selectedRows[0];
            recordId = selectedRecord.id;
            recordData.id = recordId;
        
        } else {
            
            // new email
            recordData.body = '<br/>';
        }
        
        // add signature
        recordData.body += Tine.Felamimail.getSignature();
        
        var record = new this.recordClass(recordData, recordId);
        
        var popupWindow = Tine[this.app.appName][this.recordClass.getMeta('modelName') + 'EditDialog'].openWindow({
            record: record,
            listeners: {
                scope: this,
                'update': function(record) {
                    this.store.load({});
                    // TODO it would be better to select the record after the store has been reloaded but somehow this doesn't work
                    //if (selectedRecord) {
                    //    this.grid.getSelectionModel().selectRecords.defer(200, this, [[selectedRecord]]);
                    //}
                }
            }
        });
        
        // workaround for the problem that the actions are not disabled even when the store has been reloaded 
        //  and no record is selected
        // @see TODO above
        selModel.clearSelections();
    },
    
    /**
     * toggle flagged status of mail(s)
     * - Flagged/Seen
     * 
     * @param {Button} button
     * @param {Event} event
     */
    onToggleFlag: function(button, event) {
        
        var messages = this.grid.getSelectionModel().getSelections();
        
        if (messages.length == 0) {
            return;
        }
        
        var regexp = new RegExp('[ \,]*\\\\' + button.flag);
        var flagRegexp = new RegExp(button.flag);
        
        // check if set or clear flag and init some vars
        //var flagged = (messages[0].get('flags') !== null && messages[0].get('flags').match(/Flagged/) !== null);
        var flagged = (messages[0].get('flags') !== null && messages[0].get('flags').match(flagRegexp) !== null);

        if (button.flag == 'Flagged') {
            var flagClass = 'flag_flagged';
            //var method = (flagged) ? 'clearFlag' : 'setFlag';
        } else if (button.flag == 'Seen') {
            var flagClass = 'flag_unread';
            //var method = (flagged) ? 'setFlag' : 'clearFlag';
        }
        
        var method = (flagged) ? 'clearFlag' : 'setFlag';
        
        // loop messages and update flags
        var toUpdateIds = [];
        var index = 0;
        for (var i = 0; i < messages.length; ++i) {
            index = this.store.indexOfId(messages[i].data.id);
            if (flagged) {
                
                // update class
                if (button.flag == 'Seen') {
                    Ext.get(this.grid.getView().getRow(index)).addClass(flagClass);
                } else {
                    Ext.get(this.grid.getView().getRow(index)).removeClass(flagClass);
                }
                
                // remove flag
                if (messages[i].data.flags !== null) {
                    messages[i].data.flags = messages[i].data.flags.replace(regexp, '');
                    
                    // PUSH
                    toUpdateIds.push(messages[i].data.id);
                }
                
            } else {
                
                // update class
                if (button.flag == 'Seen') {
                    Ext.get(this.grid.getView().getRow(index)).removeClass(flagClass);
                } else {
                    Ext.get(this.grid.getView().getRow(index)).addClass(flagClass);
                }

                // add flag
                if (messages[i].data.flags === null) {
                    messages[i].data.flags = '\\' + button.flag;
                } else if (! messages[i].data.flags.match(regexp)) {
                    messages[i].data.flags = messages[i].data.flags + ',\\' + button.flag;
                }
                
                // PUSH
                toUpdateIds.push(messages[i].data.id);
            }
        }
                
        if (toUpdateIds.length > 0) {
            Ext.Ajax.request({
                params: {
                    method: 'Felamimail.' + method,
                    ids: toUpdateIds,
                    flag: '\\' + button.flag
                },
                success: function(_result, _request) {
                    // TODO update folder store
                    //this.app.getMainScreen().getTreePanel().updateFolderStatus();
                },
                failure: function(result, request){
                    Ext.MessageBox.alert(
                        this.app.i18n._('Failed'), 
                        this.app.i18n._('Some error occured while trying to update the messages.')
                    );
                },
                scope: this
            });
        }
    },
    
    /**
     * called after a new set of Records has been loaded
     * 
     * @param  {Ext.data.Store} store
     * @param  {Array}          loaded records
     * @param  {Array}          load options
     * @return {Void}
     */
    onStoreLoad: function(store, records, options) {
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
                    
                    // add to tree / store
                    var treePanel = this.app.getMainScreen().getTreePanel();
                    treePanel.addAccount(account);
                    treePanel.accountStore.add([account]);
                    
                    // add to registry
                    Tine.Felamimail.registry.get('preferences').replace('defaultEmailAccount', account.id);
                    // need to do this because store could be unitialized yet
                    var registryAccounts = Tine.Felamimail.registry.get('accounts');
                    registryAccounts.results.push(account.data);
                    registryAccounts.totalcount++;
                    Tine.Felamimail.registry.replace('accounts', registryAccounts);
                }
            }
        });        
    },

    /**
     * get update folder status after delete
     * 
     * TODO make marking of deleted messages work (how? use onDeleteRecords?)
     */
    onAfterDelete: function() {
        /*
        var selectedRows = this.grid.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; i++) {
            Ext.get(this.grid.getView().getRow(i)).addClass('flag_deleted');
        }
        */
        
        this.store.load({});
        this.app.checkMailsDelayedTask.delay(10000);
    },
    onPrint:function() {
        if(!Ext.get('felamimailPrintHelperIframe')) {
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
        buffer = '<html><head>';
        buffer+= '<title>'+this.app.i18n._('Print Preview')+'</title>';
        buffer+= '</head><body>';
        buffer+= this.detailsPanel.getEl().child('.preview-panel-felamimail').dom.innerHTML;
        buffer+= '</body></html>';
        Ext.get('felamimailPrintHelperIframe').dom.contentWindow.document.documentElement.innerHTML = buffer;
        Ext.get('felamimailPrintHelperIframe').dom.contentWindow.print();
    },
    onPrintPreview:function() {
        buffer = '<html><head>';
        buffer+= '<title>'+this.app.i18n._('Print Preview')+'</title>';
        buffer+= '</head><body>';
        buffer= this.detailsPanel.getEl().child('.preview-panel-felamimail').dom.innerHTML;
        buffer+= '</body></html>';
        win = window.open('about:blank',this.app.i18n._('Print Preview'),'width=500,height=500,scrollbars=yes,toolbar=yes,status=yes,menubar=yes');
        win.document.open()
        win.document.write(buffer);
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
