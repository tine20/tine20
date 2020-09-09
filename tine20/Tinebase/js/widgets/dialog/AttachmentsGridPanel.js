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
        
        postal.subscribe({
            channel: "recordchange",
            topic: 'Tinebase.Tree_Node.*',
            callback: this.onAttachmentChanges.createDelegate(this)
        });

        this.on('rowdblclick', this.onRowDbClick, this);
        this.on('keydown', this.onKeyDown, this);
    },

    /**
     * bus notified about record changes
     */
    onAttachmentChanges: function(data, e) {
        const record = Tine.Tinebase.data.Record.setFromJson(data, Tine.Tinebase.Model.Tree_Node);
        const existingRecord = this.store.getById(data.id);

        if (existingRecord && e.topic.match(/\.update/)) {
            const idx = this.store.indexOf(existingRecord);
            const isSelected = this.selModel.isSelected(idx);
            const oldRecord = this.store.getAt(idx);
            // BE does not set the path / resolve properly
            record.data.path = oldRecord.data.path;
            record.data.created_by = oldRecord.data.created_by;

            if (idx >= 0) {
                this.store.removeAt(idx);
                this.store.insert(idx, [record]);
            } else {
                this.store.add([record]);
            }

            if (isSelected) {
                this.selModel.selectRow(this.store.indexOfId(record.id), true);
            }

        } else if (existingRecord && e.topic.match(/\.delete/)) {
            this.store.remove(existingRecord);
        } else {
            this.store.add([record]);
        }
        // NOTE: grid doesn't update selections itself
        this.actionUpdater.updateActions(this.selModel, [this.record.data]);
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
            sortable: true,
            renderer: function(value, meta, record) {
                return _.get(record, 'data.contenttype', _.get(record, 'data.type'));
            }
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
    
    onKeyDown: function(e) {
        var selectedRows = this.getSelectionModel().getSelections(),
            rowRecord = selectedRows[0];

        if (e.getKey() == e.SPACE && rowRecord.data.type == 'file') {
            this.action_preview.execute();
        }
    },

    // NOTE: this method is mixed in Tine.Filemanager.NodeGridPanel
    onRowDbClick: function (grid, row, e) {
        const rowRecord = grid.getStore().getAt(row);

        if (rowRecord.data.type === 'folder') {
            this.expandFolder(rowRecord);
        } else if (!this.readOnly) {
            const dblClickHandlers = [{
                prio: 100,
                fn: _.bind(this.action_preview.execute, this.action_preview)
            }];

            if (Tine.Tinebase.configManager.get('downloadsAllowed')
                && Tine.Tinebase.registry.get('preferences').get('fileDblClickAction') === 'download' ) {

                dblClickHandlers[0].fn = _.bind(this.action_download.execute, this.action_download);
            }

            Tine.emit('filesystem.fileDoubleClick', rowRecord, dblClickHandlers);

            const dblClickHandler = _.last(_.sortBy(dblClickHandlers, ['prio']));
            if (dblClickHandler && _.isFunction(dblClickHandler.fn)) {
                dblClickHandler.fn.call(dblClickHandler.scope);
            }

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
        this.actionUpdater.updateActions(this.selModel, [record.data]);
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
