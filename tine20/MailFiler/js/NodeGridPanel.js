/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Martin Jatho <m.jatho@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.MailFiler');

/**
 * File grid panel
 * 
 * @namespace   Tine.MailFiler
 * @class       Tine.MailFiler.NodeGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>Node Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Martin Jatho <m.jatho@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.MailFiler.FileGridPanel
 */
Tine.MailFiler.NodeGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    /**
     * @cfg filesProperty
     * @type String
     */
    filesProperty: 'files',
    
    /**
     * config values
     * @private
     */
    header: false,
    border: false,
    deferredRender: false,
    autoExpandColumn: 'name',
    showProgress: true,
    
    recordClass: Tine.MailFiler.Model.Node,
    hasDetailsPanel: true,
    evalGrants: true,

    /**
     * message detail panel
     *
     * @type Tine.MailFiler.GridDetailsPanel
     * @property detailsPanel
     */
    detailsPanel: null,

    /**
     * grid specific
     * @private
     */
    defaultSortInfo: {field: 'name', direction: 'DESC'},
    gridConfig: {
        autoExpandColumn: 'name',
        enableFileDialog: false,
        enableDragDrop: true,
        ddGroup: 'fileDDGroup'
    },
     
    ddGroup : 'fileDDGroup',  
    currentFolderNode : '/',
    
    /**
     * inits this cmp
     * @private
     */
    initComponent: function() {
        this.recordProxy = Tine.MailFiler.fileRecordBackend;
        
        this.gridConfig.cm = this.getColumnModel();
        
        this.defaultFilters = [
            {field: 'query', operator: 'contains', value: ''},
            {field: 'path', operator: 'equals', value: '/'}
        ];
        
        this.filterToolbar = this.filterToolbar || this.getFilterToolbar();
        this.filterToolbar.getQuickFilterPlugin().criteriaIgnores.push({field: 'path'});
        
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
        this.plugins.push({
            ptype: 'ux.browseplugin',
            multiple: true,
            scope: this,
            enableFileDialog: false,
            handler: this.onFilesSelect
        });

        this.initDetailsPanel();

        Tine.MailFiler.NodeGridPanel.superclass.initComponent.call(this);
    },
    
    initFilterPanel: function() {},

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
     * after render handler
     */
    afterRender: function() {
        Tine.MailFiler.NodeGridPanel.superclass.afterRender.call(this);
        this.initDropTarget();
        this.currentFolderNode = this.app.getMainScreen().getWestPanel().getContainerTreePanel().getRootNode();
    },
    
    /**
     * returns cm
     * 
     * @return Ext.grid.ColumnModel
     * @private
     * 
     * TODO    add more columns
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
            id: 'to',
            header: this.app.i18n._("To"),
            width: 150,
            sortable: true,
            dataIndex: 'to',
            hidden: true,
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
            record = new Tine.Felamimail.Model.Message(record.get('message'), record.get('message').id);

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
     * renders bytes for filesize
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
     * status column renderer
     * @param {string} value
     * @return {string}
     */
    statusRenderer: function(value) {
        return this.app.i18n._hidden(value);
    },
    
    /**
     * init ext grid panel
     * @private
     */
    initGrid: function() {
        Tine.MailFiler.NodeGridPanel.superclass.initGrid.call(this);
        
        if (this.usePagingToolbar) {
           this.initPagingToolbar();
        }
    },
    
    /**
     * inserts a quota Message when using old Browsers with html4upload
     */
    initPagingToolbar: function() {
        if(!this.pagingToolbar || !this.pagingToolbar.rendered) {
            this.initPagingToolbar.defer(50, this);
            return;
        }
        // old browsers
        if (!((! Ext.isGecko && window.XMLHttpRequest && window.File && window.FileList) || (Ext.isGecko && window.FileReader))) {
            var text = new Ext.Panel({padding: 2, html: String.format(this.app.i18n._('The max. Upload Filesize is {0} MB'), Tine.Tinebase.registry.get('maxFileUploadSize') / 1048576 )});
            this.pagingToolbar.insert(12, new Ext.Toolbar.Separator());
            this.pagingToolbar.insert(12, text);
            this.pagingToolbar.doLayout();
        }
    },
    
    /**
     * returns filter toolbar -> supress OR filters
     * @private
     */
    getFilterToolbar: function(config) {
        config = config || {};
        var plugins = [];
        if (! Ext.isDefined(this.hasQuickSearchFilterToolbarPlugin) || this.hasQuickSearchFilterToolbarPlugin) {
            this.quickSearchFilterToolbarPlugin = new Tine.widgets.grid.FilterToolbarQuickFilterPlugin();
            plugins.push(this.quickSearchFilterToolbarPlugin);
        }
        
        return new Tine.widgets.grid.FilterToolbar(Ext.apply(config, {
            app: this.app,
            recordClass: this.recordClass,
            filterModels: this.recordClass.getFilterModel().concat(this.getCustomfieldFilters()),
            defaultFilter: 'query',
            filters: this.defaultFilters || [],
            plugins: plugins
        }));
    },

    /**
     * init actions with actionToolbar, contextMenu and actionUpdater
     * @private
     */
    initActions: function() {

        this.initMailActions();

        this.action_editFile = new Ext.Action({
            requiredGrant: 'editGrant',
            allowMultiple: false,
            text: this.app.i18n._('Edit Properties'),
            handler: this.onEditFile,
            iconCls: 'action_edit_file',
            disabled: false,
            actionType: 'edit',
            scope: this
        });
        this.action_createFolder = new Ext.Action({
            requiredGrant: 'addGrant',
            actionType: 'reply',
            allowMultiple: true,
            text: this.app.i18n._('Create Folder'),
            handler: this.onCreateFolder,
            iconCls: 'action_create_folder',
            disabled: true,
            scope: this
        });
        
        this.action_goUpFolder = new Ext.Action({
            allowMultiple: true,
            actionType: 'goUpFolder',
            text: this.app.i18n._('Folder Up'),
            handler: this.onLoadParentFolder,
            iconCls: 'action_filemanager_folder_up',
            disabled: true,
            scope: this
        });
        
        this.action_download = new Ext.Action({
            requiredGrant: 'readGrant',
            allowMultiple: false,
            actionType: 'download',
            text: this.app.i18n._('Save locally'),
            handler: this.onDownload,
            iconCls: 'action_filemanager_save_all',
            disabled: true,
            scope: this
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

        this.action_printmessage = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Print Message'),
            handler: this.onPrint.createDelegate(this, []),
            disabled: true,
            iconCls:'action_print',
            actionUpdater: this.updateMessageAction,
            scope:this
        });

        this.contextMenu = Tine.MailFiler.GridContextMenu.getMenu({
            nodeName: Tine.MailFiler.Model.Node.getRecordName(),
            actions: ['delete',  'download', 'edit'],
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
            this.action_goUpFolder,
            this.action_download,
            this.action_deleteRecord,
            this.action_editFile,
            this.action_printmessage
       ]);
    },

    /**
     * init mail actions
     *
     * TODO add action updater functions
     *
     * @private
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
     * get the right contextMenu
     */
    getContextMenu: function(grid, row, e) {
        var r = this.store.getAt(row),
            type = r ? r.get('type') : null;
            
        return type === 'folder' ? this.folderContextMenu : this.contextMenu;
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
                        Ext.apply(new Ext.Button(this.action_goUpFolder), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        }),
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

        return this.actionToolbar;
    },
    
    /**
     * opens the edit dialog
     */
    onEditFile: function() {
        var selectionModel = this.getGrid().getSelectionModel();

        if (selectionModel.getCount() === 1) {
            var record = selectionModel.getSelected();
            var window = Tine.MailFiler.NodeEditDialog.openWindow({record: record});

            window.on('saveAndClose', function() {
                this.getGrid().store.reload();
            }, this);
        }
    },
    
    /**
     * create folder in current positionc
     * 
     * @param {Ext.Component} button
     * @param {Ext.EventObject} event
     */
    onCreateFolder: function(button, event) {
        var app = this.app,
            nodeName = Tine.MailFiler.Model.Node.getContainerName();
        
        Ext.MessageBox.prompt(this.app.i18n._('New Folder'), this.app.i18n._('Please enter the name of the new folder:'), function(_btn, _text) {
            var currentFolderNode = app.getMainScreen().getCenterPanel().currentFolderNode;
            if(currentFolderNode && _btn == 'ok') {
                if (! _text) {
                    Ext.Msg.alert(String.format(this.app.i18n._('No {0} added'), nodeName), String.format(this.app.i18n._('You have to supply a {0} name!'), nodeName));
                    return;
                }
                
                var filename = currentFolderNode.attributes.path + '/' + _text;
                Tine.MailFiler.fileRecordBackend.createFolder(filename);
                
            }
        }, this);  
    },
    
    /**
     * delete selected files / folders
     * 
     * @param {Ext.Component} button
     * @param {Ext.EventObject} event
     */
    onDeleteRecords: function(button, event) {
        var app = this.app,
            nodeName = '',
            sm = app.getMainScreen().getCenterPanel().selectionModel,
            nodes = sm.getSelections();
        
        if(nodes && nodes.length) {
            for(var i=0; i<nodes.length; i++) {
                var currNodeData = nodes[i].data;
                
                if(typeof currNodeData.name == 'object') {
                    nodeName += currNodeData.name.name + '<br />';
                }
                else {
                    nodeName += currNodeData.name + '<br />';
                }
            }
        }
        
        this.conflictConfirmWin = Tine.widgets.dialog.FileListDialog.openWindow({
            modal: true,
            allowCancel: false,
            height: 180,
            width: 300,
            title: app.i18n._('Do you really want to delete the following files?'),
            text: nodeName,
            scope: this,
            handler: function(button){
                if (nodes && button == 'yes') {
                    this.store.remove(nodes);
                    this.pagingToolbar.refresh.disable();
                    Tine.MailFiler.fileRecordBackend.deleteItems(nodes);
                }
            }
        }, this);
    },
    
    /**
     * go up one folder
     * 
     * @param {Ext.Component} button
     * @param {Ext.EventObject} event
     */
    onLoadParentFolder: function(button, event) {
        var app = this.app,
            currentFolderNode = app.getMainScreen().getCenterPanel().currentFolderNode;
        
        if(currentFolderNode && currentFolderNode.parentNode) {
            app.getMainScreen().getCenterPanel().currentFolderNode = currentFolderNode.parentNode;
            currentFolderNode.parentNode.select();
        }
    },
    
    /**
     * grid row doubleclick handler
     * 
     * @param {Tine.MailFiler.NodeGridPanel} grid
     * @param {} row record
     * @param {Ext.EventObjet} e
     */
    onRowDblClick: function(grid, row, e) {
        var app = this.app;
        var rowRecord = grid.getStore().getAt(row);
        
        if(rowRecord.data.type == 'file') {
            this.onEditFile();
        }
        
        else if (rowRecord.data.type == 'folder'){
            var treePanel = app.getMainScreen().getWestPanel().getContainerTreePanel();
            
            var currentFolderNode;
            if(rowRecord.data.path == '/personal/system') {
                currentFolderNode = treePanel.getNodeById('personal');
            }
            else if(rowRecord.data.path == '/shared') {
                currentFolderNode = treePanel.getNodeById('shared');
            }
            else if(rowRecord.data.path == '/personal') {
                currentFolderNode = treePanel.getNodeById('otherUsers');
            }
            else {
                currentFolderNode = treePanel.getNodeById(rowRecord.id);
            }
            if(currentFolderNode) {
                currentFolderNode.select();
                currentFolderNode.expand();
                app.getMainScreen().getCenterPanel().currentFolderNode = currentFolderNode;
            } else {
                // get ftb path filter
                this.filterToolbar.filterStore.each(function(filter) {
                    var field = filter.get('field');
                    if (field === 'path') {
                        filter.set('value', '');
                        filter.set('value', rowRecord.data);
                        filter.formFields.value.setValue(rowRecord.get('path'));
                        
                        this.filterToolbar.onFiltertrigger();
                        return false;
                    }
                }, this);
            }
        }
    }, 

    /**
     * on remove handler
     * 
     * @param {} button
     * @param {} event
     */
    onRemove: function (button, event) {
        var selectedRows = this.selectionModel.getSelections();
        for (var i = 0; i < selectedRows.length; i += 1) {
            this.store.remove(selectedRows[i]);
        }
    },
    
    /**
     * populate grid store
     * 
     * @param {} record
     */
    loadRecord: function (record) {
        if (record && record.get(this.filesProperty)) {
            var files = record.get(this.filesProperty);
            for (var i = 0; i < files.length; i += 1) {
                var file = new Ext.ux.file.Upload.file(files[i]);
                file.set('status', 'complete');
                file.set('nodeRecord', new Tine.MailFiler.Model.Node(file.data));
                this.store.add(file);
            }
        }
    },

    /**
     * download file
     * 
     * @param {} button
     * @param {} event
     */
    onDownload: function(button, event) {
        
        var app = Tine.Tinebase.appMgr.get('MailFiler'),
            grid = app.getMainScreen().getCenterPanel(),
            selectedRows = grid.selectionModel.getSelections();
        
        var fileRow = selectedRows[0];
               
        var downloadPath = fileRow.data.path;
        var downloader = new Ext.ux.file.Download({
            params: {
                method: 'MailFiler.downloadFile',
                requestType: 'HTTP',
                id: '',
                path: downloadPath
            }
        }).start();
    },
    
    /**
     * update grid nodeRecord with fileRecord data
     * 
     * @param nodeRecord
     * @param fileRecord
     */
    updateNodeRecord: function(nodeRecord, fileRecord) {
        for(var field in fileRecord.fields) {
            nodeRecord.set(field, fileRecord.get(field));
        }
        nodeRecord.fileRecord = fileRecord;
    },

    /**
     * init grid drop target
     * 
     * @TODO DRY cleanup
     */
    initDropTarget: function(){
        var ddrow = new Ext.dd.DropTarget(this.getEl(), {
            ddGroup : 'fileDDGroup',  
            
            notifyDrop : function(dragSource, e, data){
                
                if(data.node && data.node.attributes && !data.node.attributes.nodeRecord.isDragable()) {
                    return false;
                }
                
                var app = Tine.Tinebase.appMgr.get(Tine.MailFiler.fileRecordBackend.appName),
                    grid = app.getMainScreen().getCenterPanel(),
                    treePanel = app.getMainScreen().getWestPanel().getContainerTreePanel(),
                    dropIndex = grid.getView().findRowIndex(e.target),
                    target = grid.getStore().getAt(dropIndex),
                    nodes = data.selections ? data.selections : [data.node];
                
                if((!target || target.data.type === 'file') && grid.currentFolderNode) {
                    target = grid.currentFolderNode;
                }
                
                if(!target) {
                    return false;
                }
                
                for(var i=0; i<nodes.length; i++) {
                    if(nodes[i].id == target.id) {
                        return false;
                    }
                }
                
                var targetNode = treePanel.getNodeById(target.id);
                if(targetNode && targetNode.isAncestor(nodes[0])) {
                    return false;
                }
                
                Tine.MailFiler.fileRecordBackend.copyNodes(nodes, target, !e.ctrlKey);
                return true;
            },
            
            notifyOver : function( dragSource, e, data ) {
                if(data.node && data.node.attributes && !data.node.attributes.nodeRecord.isDragable()) {
                    return false;
                }
                
                var app = Tine.Tinebase.appMgr.get(Tine.MailFiler.fileRecordBackend.appName),
                    grid = app.getMainScreen().getCenterPanel(),
                    dropIndex = grid.getView().findRowIndex(e.target),
                    treePanel = app.getMainScreen().getWestPanel().getContainerTreePanel(),
                    target= grid.getStore().getAt(dropIndex),
                    nodes = data.selections ? data.selections : [data.node];
                
                if((!target || (target.data && target.data.type === 'file')) && grid.currentFolderNode) {
                    target = grid.currentFolderNode;
                }
                
                if(!target) {
                    return false;
                }
                
                for(var i=0; i<nodes.length; i++) {
                    if(nodes[i].id == target.id) {
                        return false;
                    }
                        }
                
                var targetNode = treePanel.getNodeById(target.id);
                if(targetNode && targetNode.isAncestor(nodes[0])) {
                    return false;
                }
                
                return this.dropAllowed;
            }
        });
    }
});
