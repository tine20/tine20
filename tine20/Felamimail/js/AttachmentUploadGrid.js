/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Felamimail');

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.AttachmentUploadGrid
 * @extends     Ext.grid.GridPanel
 *
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 */
Tine.Felamimail.AttachmentUploadGrid = Ext.extend(Tine.widgets.grid.FileUploadGrid, {
    /**
     * Store with all valid attachment types
     */
    attachmentTypeStore: null,
    app: null,

    clicksToEdit: 1,
    currentRecord: null,

    initComponent: function () {
        this.app = this.app || Tine.Tinebase.appMgr.get('Felamimail');
        this.events = [
            /**
             * Fired once files where selected from filemanager or from a local source
             */
            'filesSelected'
        ];

        this.attachmentTypeStore = new Ext.data.JsonStore({
            fields: ['id', 'name'],
            data: this.getAttachmentMethods()
        });

        Tine.Felamimail.AttachmentUploadGrid.superclass.initComponent.call(this);

        this.on('beforeedit', this.onBeforeEdit.createDelegate(this));
        this.store.on('add', this.onStoreAddRecords, this);

        if (this.action_rename) {
            _.set(this, 'action_rename.initialConfig.actionUpdater', this.renameActionUpdater);
        }
    },

    onStoreAddRecords: function(store, rs, idx) {
        _.each(rs, (r) => {
            _.set(r, 'data.attachment_type', 'attachment');
        });
    },

    renameActionUpdater: function(action, grants, records, isFilterSelect, filteredContainers) {
        const isTempfile = !!_.get(records, '[0].data.tempFile');
        const enabled = !!isTempfile;

        action.setDisabled(!enabled);
        action.baseAction.setDisabled(!enabled);
    },

    onBeforeEdit: function (e) {
        var record = e.record;
        this.currentRecord = record;
    },

    getAttachmentMethods: function () {
        var methods = [{
            id: 'attachment',
            name: this.app.i18n._('Attachment')
        }];

        if (!Tine.Tinebase.appMgr.isEnabled('Filemanager')) {
            return methods;
        }

        methods = methods.concat([{
                id: 'download_public_fm',
                name: this.app.i18n._('Filemanager (Download link)')
            }, {
                id: 'download_protected_fm',
                name: this.app.i18n._('Filemanager (Download link, password)')
            }, {
                id: 'systemlink_fm',
                name: this.app.i18n._('Filemanager (Systemlink)')
            }]
        );

        return methods;
    },

    /**
     * Override columns
     */
    getColumns: function () {
        var me = this;

        var combo = new Ext.form.ComboBox({
            blurOnSelect: true,
            expandOnFocus: true,
            listWidth: 250,
            minListWidth: 250,
            mode: 'local',
            value: 'attachment',
            displayField: 'name',
            valueField: 'id',
            store: me.attachmentTypeStore,
            disableKeyFilter: true,
            queryMode: 'local'
        });

        combo.doQuery = function (q, forceAll, uploadGrid) {
            this.store.clearFilter();

            this.store.filterBy(function (record, id) {
                var _ = window.lodash;

                if (_.get(uploadGrid.currentRecord, 'data.type') === 'file' && !_.get(uploadGrid.currentRecord, 'data.account_grants.downloadGrant', true) && id === 'attachment') {
                    return false;
                }

                // only fm files can be system links
                if (_.get(uploadGrid.currentRecord, 'data.type') !== 'file' && id === 'systemlink_fm') {
                    return false
                }

                // if no grants, then its not from fm
                if (!_.get(uploadGrid.currentRecord, 'data.account_grants.publishGrant', true) && id.startsWith('download_')) {
                    return false;
                }

                return true;
            }.createDelegate(this, [uploadGrid.currentRecord], true));

            this.onLoad();
        }.createDelegate(combo, [this], true);

        return [{
            id: 'attachment_type',
            dataIndex: 'attachment_type',
            sortable: true,
            width: 250,
            header: this.app.i18n._('Attachment Type'),
            tooltip: this.app.i18n._('Click icon to change'),
            listeners: {},
            value: 'attachment',
            renderer: function (value) {
                if (!value) {
                    return null;
                }

                var record = me.attachmentTypeStore.getById(value);

                if (!record) {
                    return null;
                }

                return Tine.Tinebase.common.cellEditorHintRenderer(record.get('name'));
            },
            editor: combo
        }, {
            resizable: true,
            id: 'name',
            dataIndex: 'name',
            flex: 1,
            header: i18n._('name'),
            renderer: Ext.ux.PercentRendererWithName
        }, {
            resizable: true,
            id: 'size',
            dataIndex: 'size',
            width: 70,
            header: i18n._('size'),
            renderer: Ext.util.Format.fileSize
        }, {
            resizable: true,
            id: 'type',
            dataIndex: 'type',
            width: 70,
            header: i18n._('type')
        }]
    },

    onFilesSelect: function (fileSelector, e) {
        if (window.lodash.isArray(fileSelector)) {
            this.onFileSelectFromFilemanager(fileSelector);
            return;
        }

        var files = fileSelector.getFileList();
        Ext.each(files, function (file) {

            var upload = new Ext.ux.file.Upload({
                file: file,
                fileSelector: fileSelector
            });

            var uploadKey = Tine.Tinebase.uploadManager.queueUpload(upload);
            var fileRecord = Tine.Tinebase.uploadManager.upload(uploadKey);

            upload.on('uploadfailure', this.onUploadFail, this);
            upload.on('uploadcomplete', this.onUploadComplete, fileRecord);
            upload.on('uploadstart', Tine.Tinebase.uploadManager.onUploadStart, this);

            if (fileRecord.get('status') !== 'failure') {
                // overriden because of this
                fileRecord.data.attachment_type = 'attachment';
                this.store.add(fileRecord);
            }
        }, this);

        this.fireEvent('filesSelected');
    },

    onFileSelectFromFilemanager: function (nodes) {
        var me = this;

        Ext.each(nodes, function (node) {
            var record = new Tine.Filemanager.Model.Node(node);

            if (me.store.find('name', record.get('name')) === -1) {
                // Overriden because of this
                record.data.attachment_type = 'systemlink_fm';
                me.store.add(record);
            } else {
                Ext.MessageBox.show({
                    title: i18n._('Failure'),
                    msg: i18n._('This file is already attached to this record.'),
                    buttons: Ext.MessageBox.OK,
                    icon: Ext.MessageBox.ERROR
                });
            }
        });

        this.fireEvent('filesSelected');
    }
});
