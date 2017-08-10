/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.MailFiler');

require('./nodeActions');

/**
 * File grid panel
 * 
 * @namespace   Tine.MailFiler
 * @class       Tine.MailFiler.NodeGridPanel
 * @extends     Tine.Filemanager.NodeGridPanel
 * 
 * <p>Node Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @param       {Object} config
 * @constructor
 * Create a new Tine.MailFiler.FileGridPanel
 */
Tine.MailFiler.NodeGridPanel = Ext.extend(Tine.Filemanager.NodeGridPanel, {

    recordClass: Tine.MailFiler.Model.Node,

    /**
     * message detail panel
     *
     * @type Tine.MailFiler.GridDetailsPanel
     * @property detailsPanel
     */
    detailsPanel: null,

    /**
     * inits this cmp
     * @private
     */
    initComponent: function() {
        this.initDetailsPanel();
        this.recordProxy = Tine.MailFiler.fileRecordBackend;

        Tine.MailFiler.NodeGridPanel.superclass.initComponent.call(this);
    },

    /**
     * the details panel (shows message content)
     *
     * @private
     */
    initDetailsPanel: function() {
        this.detailsPanel = new Tine.MailFiler.GridDetailsPanel({
            gridpanel: this,
            grid: this,
            app: this.app,
            i18n: this.app.i18n
        });
    },

    /**
     * returns cm
     *
     * @return Ext.grid.ColumnModel
     * @private
     *
     */
    getColumnModel: function(){
        var columns = [{
            id: 'tags',
            header: this.app.i18n._('Tags'),
            dataIndex: 'tags',
            width: 50,
            renderer: Tine.Tinebase.common.tagsRenderer,
            sortable: false,
            hidden: false
        },{
            id: 'name',
            header: this.app.i18n._("Name"),
            width: 70,
            sortable: true,
            dataIndex: 'name',
            renderer: Ext.ux.PercentRendererWithName
        },{
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
            dataIndex: 'subject',
            renderer: this.messageRenderer
        },{
            id: 'from_email',
            header: this.app.i18n._("From (Email)"),
            width: 100,
            sortable: true,
            dataIndex: 'from_email',
            renderer: this.messageRenderer
        },{
            id: 'from_name',
            header: this.app.i18n._("From (Name)"),
            width: 100,
            sortable: true,
            dataIndex: 'from_name',
            renderer: this.messageRenderer
        },{
            id: 'sender',
            header: this.app.i18n._("Sender"),
            width: 100,
            sortable: true,
            dataIndex: 'sender',
            hidden: true,
            renderer: this.messageRenderer
        },{
            id: 'to_flat',
            header: this.app.i18n._("To"),
            width: 150,
            sortable: true,
            dataIndex: 'to_flat',
            renderer: this.messageRenderer
        },{
            id: 'sent',
            header: this.app.i18n._("Sent"),
            width: 100,
            sortable: true,
            dataIndex: 'sent',
            hidden: true,
            renderer: this.messageRenderer
        },{
            id: 'received',
            header: this.app.i18n._("Received"),
            width: 100,
            sortable: true,
            dataIndex: 'received',
            renderer: this.messageRenderer
        },{
            id: 'size',
            header: this.app.i18n._("Size"),
            width: 40,
            sortable: true,
            dataIndex: 'size',
            hidden: true,
            renderer: Tine.Tinebase.common.byteRenderer.createDelegate(this, [2, true], 3)
        },{
            id: 'contenttype',
            header: this.app.i18n._("Contenttype"),
            width: 50,
            sortable: true,
            dataIndex: 'contenttype',
            hidden: true,
            renderer: function(value, metadata, record) {

                var app = Tine.Tinebase.appMgr.get('MailFiler');
                if(record.data.type == 'folder') {
                    return app.i18n._("Folder");
                }
                else {
                    return value;
                }
            }
        },{
            id: 'creation_time',
            header: this.app.i18n._("Creation Time"),
            width: 50,
            sortable: true,
            dataIndex: 'creation_time',
            hidden: true,
            renderer: Tine.Tinebase.common.dateTimeRenderer
        },{
            id: 'created_by',
            header: this.app.i18n._("Created By"),
            width: 50,
            sortable: true,
            dataIndex: 'created_by',
            renderer: Tine.Tinebase.common.usernameRenderer
        },{
            id: 'last_modified_time',
            header: this.app.i18n._("Last Modified Time"),
            width: 80,
            sortable: true,
            dataIndex: 'last_modified_time',
            hidden: true,
            renderer: Tine.Tinebase.common.dateTimeRenderer
        },{
            id: 'last_modified_by',
            header: this.app.i18n._("Last Modified By"),
            width: 50,
            sortable: true,
            dataIndex: 'last_modified_by',
            hidden: true,
            renderer: Tine.Tinebase.common.usernameRenderer
        }];

        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                resizable: true
            },
            columns: columns
        });
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
            result = '',
            record = record.get('message')
                ? new Tine.Felamimail.Model.Message(record.get('message'), record.get('message').id)
                : null;

        if (! record) {
            return '';
        }

        if (record.hasFlag('\\Answered')) {
            icons.push({src: 'images/oxygen/16x16/actions/mail-reply-sender.png', qtip: Ext.util.Format.htmlEncode(i18n._('Answered'))});
        }
        if (record.hasFlag('Passed')) {
            icons.push({src: 'images/oxygen/16x16/actions/mail-forward.png', qtip: Ext.util.Format.htmlEncode(i18n._('Forwarded'))});
        }
        if (record.hasFlag('Tine20')) {
            icons.push({src: 'images/favicon.png', qtip: Ext.util.Format.htmlEncode(i18n._('Tine20'))});
        }

        Ext.each(icons, function(icon) {
            result += '<img class="FelamimailFlagIcon" src="' + icon.src + '" ext:qtip="' + Tine.Tinebase.common.doubleEncode(icon.qtip) + '">';
        }, this);

        return result;
    },

    /**
     * renders message
     * @param {Integer} value
     * @param {Object} metadata
     * @param {Tine.Tinebase.data.Record} record
     * @param {String} field
     * @return {String}
     *
     * TODO should be generalized
     */
    messageRenderer: function (value, metadata, record) {
        var messageRecord = record ? new Tine.Felamimail.Model.Message(record.get('message'), record.get('message').id) : null,
            field = this.dataIndex,
            result = messageRecord ? messageRecord.get(field) : '';

        if ( field !== '') {
            switch (field) {
                case 'size':
                    result = Ext.util.Format.fileSize(result)
                    break;
                case 'received':
                case 'sent':
                    result = Tine.Tinebase.common.dateTimeRenderer(result);
                    break;
            }
        }
        return result;
    },

    /**
     * init actions with actionToolbar, contextMenu and actionUpdater
     * @private
     *
     * TODO use Filemanager actions if available
     */
    initActions: function() {

        this.initMailActions();

        this.action_createFolder = Tine.MailFiler.nodeActionsMgr.get('createFolder');
        this.action_editFile = Tine.MailFiler.nodeActionsMgr.get('edit');
        this.action_deleteRecord = Tine.MailFiler.nodeActionsMgr.get('delete');
        this.action_download = Tine.MailFiler.nodeActionsMgr.get('download');

        // FIXME: this is broken (see Filemanager)
        //this.action_goUpFolder = new Ext.Action({
        //    allowMultiple: true,
        //    actionType: 'goUpFolder',
        //    text: this.app.i18n._('Folder Up'),
        //    handler: this.onLoadParentFolder,
        //    iconCls: 'action_filemanager_folder_up',
        //    actionUpdater: function(action) {
        //        var _ = window.lodash,
        //            path = _.get(this, 'currentFolderNode.attributes.path', false);
        //
        //        action.setDisabled(path == '/');
        //    }.createDelegate(this),
        //    scope: this
        //});

        this.contextMenu = Tine.MailFiler.GridContextMenu.getMenu({
            nodeName: Tine.MailFiler.Model.Node.getRecordName(),
            actions: ['delete', 'download', 'edit'],
            scope: this,
            backend: 'MailFiler',
            backendModel: 'Node'
        });
        this.contextMenu.addItem(this.action_reply);
        this.contextMenu.addItem(this.action_replyAll);
        this.contextMenu.addItem(this.action_forward);

        this.folderContextMenu = Tine.MailFiler.GridContextMenu.getMenu({
            nodeName: this.app.i18n._(this.app.getMainScreen().getWestPanel().getContainerTreePanel().containerName),
            actions: ['delete', 'rename', 'edit'],
            scope: this,
            backend: 'MailFiler',
            backendModel: 'Node'
        });

        this.actionUpdater.addActions(this.contextMenu.items);
        this.actionUpdater.addActions(this.folderContextMenu.items);

        this.actionUpdater.addActions([
            this.action_reply,
            this.action_replyAll,
            this.action_forward,
            this.action_createFolder,
            //this.action_goUpFolder,
            this.action_download,
            this.action_deleteRecord,
            this.action_editFile,
            this.action_printmessage
       ]);
    },

    /**
     * init mail actions
     *
     * @private
     *
     * TODO use action manager
     */
    initMailActions: function() {

        var fMailApp = Tine.Tinebase.appMgr.get('Felamimail');

        this.action_reply = new Ext.Action({
            requiredGrant: 'readGrant',
            actionType: 'reply',
            text: this.app.i18n._('Reply'),
            handler: this.onMessageReplyTo.createDelegate(this, [false]),
            iconCls: 'action_email_reply',
            actionUpdater: this.updateMessageAction,
            disabled: true
        });

        this.action_replyAll = new Ext.Action({
            requiredGrant: 'readGrant',
            actionType: 'replyAll',
            text: this.app.i18n._('Reply To All'),
            handler: this.onMessageReplyTo.createDelegate(this, [true]),
            iconCls: 'action_email_replyAll',
            actionUpdater: this.updateMessageAction,
            disabled: true
        });

        this.action_forward = new Ext.Action({
            requiredGrant: 'readGrant',
            actionType: 'forward',
            text: this.app.i18n._('Forward'),
            handler: this.onMessageForward.createDelegate(this),
            iconCls: 'action_email_forward',
            actionUpdater: this.updateMessageAction,
            disabled: true
        });

        this.action_printmessage = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Print Message'),
            handler: this.onPrint.createDelegate(this, []),
            disabled: true,
            iconCls:'action_print',
            actionUpdater: this.updateMessageAction,
            scope:this
        });
    },


    /**
     * Ripped off felamimail
     *
     * @param detailsPanel
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

    onMessageReplyTo: function(toAll) {
        var sm = this.getGrid().getSelectionModel(),
            node = sm.getSelected();
            msg = node.get('message'),
            msgBody = Ext.util.Format.nl2br(msg.body);

        msgBody = '<br/>'
            + '<blockquote class="felamimail-body-blockquote">' + msgBody + '</blockquote><br/>';

        var date = msg.sent
            ? msg.sent
            : (msg.received) ? msg.received : new Date();

        var quote = String.format(this.app.i18n._('On {0}, {1} wrote'),
                Tine.Tinebase.common.dateTimeRenderer(date),
                Ext.util.Format.htmlEncode(msg.from_name)
            ) + ':';

        // pass all relevant params (body, subject, ...) to prevent mail loading in Felamimail
        var win = Tine.Felamimail.MessageEditDialog.openWindow({
            //record: msg,
            replyTo : Ext.encode(msg),
            replyToAll: toAll,
            msgBody: quote + msgBody
        });
    },

    onMessageForward: function() {
        var sm = this.getGrid().getSelectionModel(),
            node = sm.getSelected();
            msg = node.get('message'),
            msgBody = Ext.util.Format.nl2br(msg.body),
            quote = String.format('{0}-----' + this.app.i18n._('Original message') + '-----{1}',
                '<br /><b>',
                '</b><br />'),
            attachments = msg.attachments;

        Ext.each(attachments, function(attachment) {
            // set name and MailFiler path for fetching attachment from filesystem when sending
            attachment.name = attachment.filename;
            attachment.type = 'filenode';
            attachment.id = 'MailFiler'  + '|' + node.get('path') + '|' + msg.messageuid  + '|' + attachment.partId
        }, this);

        // pass all relevant params (body, subject, ...) to prevent mail loading in Felamimail
        var win = Tine.Felamimail.MessageEditDialog.openWindow({
            forwardMsgs : Ext.encode([msg]),
            attachments: attachments,
            msgBody: quote + msgBody
        });
    },

    /**
     * check file type for message actions
     *
     * @param action
     * @param grants
     * @param records
     * @returns {boolean}
     */
    updateMessageAction: function(action, grants, records) {
        var isFile = false;
        Ext.each(records, function (record) {
            if (record.get('type') == 'file') {
                isFile = true;
                return true;
            }
        });

        var disable = records.length > 1 || ! isFile;
        action.setDisabled(disable);
        return false;
    },

    /**
     * grid row doubleclick handler
     *
     * @param {Tine.MailFiler.NodeGridPanel} grid
     * @param {} row record
     * @param {Ext.EventObjet} e
     */
    onRowDblClick: function (grid, row, e) {
        var rowRecord = grid.getStore().getAt(row);

        if (rowRecord.data.type == 'file') {
            Tine.MailFiler.NodeEditDialog.openWindow({
                record: rowRecord
            });
        } else if (rowRecord.data.type == 'folder') {
            Tine.MailFiler.NodeGridPanel.superclass.onRowDblClick.call(this, grid, row, e);
        }
    },

    /**
     * get action toolbar
     *
     * @return {Ext.Toolbar}
     */
    getActionToolbar: function() {
        if (! this.actionToolbar) {
            this.actionToolbar = new Ext.Toolbar({
                defaults: {height: 55},
                items: [{
                    xtype: 'buttongroup',
                    layout: 'toolbar',
                    buttonAlign: 'left',
                    columns: 9,
                    defaults: {minWidth: 60},
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
                        Ext.apply(new Ext.Button(this.action_printmessage), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        }),
                        Ext.apply(new Ext.Button(this.action_editFile), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        }),
                        Ext.apply(new Ext.Button(this.action_deleteRecord), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        }),
                        Ext.apply(new Ext.Button(this.action_createFolder), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        }),
                        //Ext.apply(new Ext.Button(this.action_goUpFolder), {
                        //    scale: 'medium',
                        //    rowspan: 2,
                        //    iconAlign: 'top'
                        //}),
                        Ext.apply(new Ext.Button(this.action_download), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        })
                 ]
                }, this.getActionToolbarItems()]
            });


            if (this.filterToolbar && typeof this.filterToolbar.getQuickFilterField == 'function') {
                this.actionToolbar.add('->', this.filterToolbar.getQuickFilterField());
            }
        }

        this.actionToolbar.on('resize', this.onActionToolbarResize, this, {buffer: 250});
        this.actionToolbar.on('show', this.onActionToolbarResize, this);

        return this.actionToolbar;
    }
});
