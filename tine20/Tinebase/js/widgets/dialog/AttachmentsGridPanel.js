/*
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * TODO maybe we should generalize parts of this and have a common parent for this and Tine.widgets.relation.GenericPickerGridPanel
 * TODO add more columns
 * TODO allow to edit description
 */
Ext.ns('Tine.widgets.dialog');

/**
 * @namespace   Tine.widgets.dialog
 * @class       Tine.widgets.dialog.AttachmentsGridPanel
 * @extends     Tine.widgets.grid.FileUploadGrid
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
Tine.widgets.dialog.AttachmentsGridPanel = Ext.extend(Tine.widgets.grid.FileUploadGrid, {
    /**
     * @cfg for FileUploadGrid
     */
    filesProperty: 'attachments',
    
    /**
     * The calling EditDialog
     * @type Tine.widgets.dialog.EditDialog
     */
    editDialog: null,
    
    /**
     * title
     * 
     * @type String
     */
    title: null,
    
    /**
     * the record
     * @type Record
     */
    record: null,

    /**
     * @type Tinebase.Application
     */
    app: null,

    /**
     * @cfg {String} requiredGrant to make actions
     */
    requiredGrant: 'editGrant',

    /**
     * @cfg {Number} pos
     * position 300 = 100 + 100*2 -> means second one after app specific tabs
     */
    pos: 300,

    /* config */
    frame: true,
    border: true,
    autoScroll: true,
    layout: 'fit',
    canonicalName: 'AttachmentsGrid',
    registerEditDialogEvents: true,

    /**
     * initializes the component
     */
    initComponent: function() {
        this.record = this.editDialog.record;
        this.app = this.editDialog.app;
        this.title = this.i18nTitle = i18n._('Attachments');
        this.i18nFileString = i18n._('Attachment');
        
        Tine.widgets.dialog.MultipleEditDialogPlugin.prototype.registerSkipItem(this);

        if (this.registerEditDialogEvents) {
            this.editDialog.on('recordUpdate', this.onRecordUpdate, this);
            this.editDialog.on('load', this.onLoadRecord, this);
        }

        Tine.widgets.dialog.AttachmentsGridPanel.superclass.initComponent.call(this);
        
        this.initActions();
    },
    
    /**
     * get columns
     * @return Array
     */
    getColumns: function() {
        var columns = [{
            resizable: true,
            id: 'name',
            dataIndex: 'name',
            width: 150,
            header: i18n._('Name'),
            renderer: Ext.ux.PercentRendererWithName,
            sortable: true
        }, {
            resizable: true,
            id: 'size',
            dataIndex: 'size',
            width: 50,
            header: i18n._('Size'),
            renderer: Ext.util.Format.fileSize,
            sortable: true
        }, {
            resizable: true,
            id: 'contenttype',
            dataIndex: 'contenttype',
            width: 80,
            header: i18n._('Content Type'),
            sortable: true
        },{ id: 'creation_time',      header: i18n._('Creation Time'),         dataIndex: 'creation_time',         renderer: Tine.Tinebase.common.dateRenderer,     width: 80,
            sortable: true },
          { id: 'created_by',         header: i18n._('Created By'),            dataIndex: 'created_by',            renderer: Tine.Tinebase.common.usernameRenderer, width: 80,
            sortable: true }
        ];
        
        return columns;
    },
    
    /**
     * init store
     * @private
     */
    initStore: function () {
        this.store = new Ext.data.SimpleStore({
            fields: Tine.Tinebase.Model.Tree_Node
        });
    },
    
    /**
     * initActions
     */
    initActions: function () {
        this.action_download = new Ext.Action({
            requiredGrant: 'readGrant',
            allowMultiple: false,
            actionType: 'download',
            text: i18n._('Download'),
            handler: this.onDownload,
            iconCls: 'action_download',
            scope: this,
            disabled: true,
            hidden: !Tine.Tinebase.configManager.get('downloadsAllowed')
        });

        // TODO: does user need rights for Filemanager?
        if (Tine.Tinebase.appMgr.isEnabled('Filemanager')) {
            this.action_preview = Tine.Filemanager.nodeActionsMgr.get('preview', {
                initialApp: this.app,
                sm: this.getSelectionModel()
            });
        }

        this.actionUpdater.addActions([this.action_download, this.action_preview]);
        this.getTopToolbar().addItem(this.action_download);
        this.contextMenu.addItem(this.action_download);
        
        this.on('rowdblclick', this.onRowDbClick, this);
        this.on('keydown', this.onKeyDown, this);
    },

    onKeyDown: function(e) {
        var selectedRows = this.getSelectionModel().getSelections(),
            rowRecord = selectedRows[0];

        if (e.getKey() == e.SPACE && rowRecord.data.type == 'file') {
            this.action_preview.execute();
        }
    },

    onRowDbClick: function () {
        var _ = window.lodash,
            downloadsAllowed = Tine.Tinebase.configManager.get('downloadsAllowed');

        // TODO: does user need rights for Filemanager?
        if (! Tine.Tinebase.appMgr.isEnabled('Filemanager') && downloadsAllowed) {
            this.onDownload();
            return;
        }

        var prefs = Tine.Tinebase.appMgr.get('Filemanager').getRegistry().get('preferences');

        var selectedRows = this.getSelectionModel().getSelections(),
            rowRecord = selectedRows[0];

        if (prefs.get('dbClickAction') === 'download' && downloadsAllowed) {
            this.onDownload();
        } else if (prefs.get('dbClickAction') === 'preview' && rowRecord.data.type == 'file') {
            this.action_preview.execute();
        }
    },

    /**
     * is called from onApplyChanges of the edit dialog per save event
     * 
     * @param {Tine.widgets.dialog.EditDialog} dialog
     * @param {Tine.Tinebase.data.Record} record
     * @return {Boolean}
     */
    onRecordUpdate: function(dialog, record) {
        if (record.data.hasOwnProperty('attachments')) {
            Tine.Tinebase.common.assertComparable(record.data.attachments);
        }
        var attachments = Tine.Tinebase.common.assertComparable(this.getData());
        record.set('attachments', attachments);
    },
    
    /**
     * updates the title ot the tab
     * @param {Integer} count
     */
    updateTitle: function(count) {
        count = Ext.isNumber(count) ? count : this.store.getCount();
        this.setTitle(this.i18nTitle + ' (' + count + ')');
    },
    
    /**
     * populate store
     * 
     * @param {EditDialog} editDialog
     * @param {Record} record
     * @param {Function} ticketFn
     */
    onLoadRecord: function(editDialog, record, ticketFn) {
        var _ = window.lodash,
            interceptor = ticketFn(),
            attachments = record.get('attachments'),
            evalGrants = editDialog.evalGrants,
            hasRequiredGrant = !evalGrants || _.get(record, record.constructor.getMeta('grantsPath') + '.' + this.requiredGrant);

        this.store.removeAll();

        if (attachments && attachments.length > 0) {
            this.updateTitle(attachments.length);
            var attachmentRecords = [];
            
            Ext.each(attachments, function(attachment) {
                attachmentRecords.push(new Tine.Tinebase.Model.Tree_Node(attachment, attachment.id));
            }, this);
            this.store.add(attachmentRecords);
        } else {
            this.updateTitle(0);
        }
        
        // add other listeners after population
        if (this.store) {
            this.store.on('update', this.updateTitle, this);
            this.store.on('add', this.updateTitle, this);
            this.store.on('remove', this.updateTitle, this);
        }
        if (Ext.isFunction(interceptor)) {
            interceptor();
        }

        this.setReadOnly(! hasRequiredGrant);
    },

    /**
     * get attachments data as array
     * 
     * @return {Array}
     */
    getData: function() {
        var attachments = [];
        
        this.store.each(function(attachment) {
            attachments.push(attachment.data);
        }, this);

        return attachments;
    }
});
