/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Filemanager.nodeActions');

require('Filemanager/js/QuickLookPanel');

/**
 * @singleton
 */
Tine.Filemanager.nodeActionsMgr = new (Ext.extend(Tine.widgets.ActionManager, {
    actionConfigs: Tine.Filemanager.nodeActions
}))();

/**
 * helper for disabled field
 *
 * @param action
 * @param grants
 * @param records
 * @param isFilterSelect
 * @param filteredContainers
 * @returns {*}
 */
Tine.Filemanager.nodeActions.actionUpdater = function(action, grants, records, isFilterSelect, filteredContainers) {
    // run default updater
    Tine.widgets.ActionUpdater.prototype.defaultUpdater(action, grants, records, isFilterSelect);

    // check filtered node if nothing is selected
    action.initialConfig.filteredContainer = null;
    if (! _.get(records, 'length') && filteredContainers) {
        const filteredContainer = Tine.Tinebase.data.Record.setFromJson(filteredContainers[0], Tine.Filemanager.Model.Node);
        const filteredContainerGrants = _.get(filteredContainers,'[0].account_grants',{});

        records = [filteredContainer];
        Tine.widgets.ActionUpdater.prototype.defaultUpdater(action, filteredContainerGrants, records, false);

        action.initialConfig.filteredContainer = filteredContainer;
    }

    let disabled = _.isFunction(action.isDisabled) ? action.isDisabled() : action.disabled;

    // node specific checks (@TODO what about folders with quarantined contents?)
    disabled = window.lodash.reduce(records, function(disabled, record) {
        const isVirtual = _.isFunction(record.isVirtual) ? record.isVirtual() : false;
        const isQuarantined = !!+record.get('is_quarantined');
        const constraint = record.get('type') === action.initialConfig.constraint;
        return disabled
            || (!action.initialConfig.allowVirtual && isVirtual)
            || (!action.initialConfig.allowQuarantined && isQuarantined)
            || (action.initialConfig.constraint && !constraint);
    }, disabled);

    action.setDisabled(disabled);
};

// /**
//  * reload
//  */
// Tine.Filemanager.nodeActions.Reload = {
//     app: 'Filemanager',
//     text: 'Reload', // _('Reload'),
//     iconCls: 'x-tbar-loading',
//     handler: function() {
//         var record = this.initialConfig.selections[0];
//         // arg - does not trigger tree children reload!
//         Tine.Filemanager.fileRecordBackend.loadRecord(record);
//     }
// };

/**
 * create new folder, needs a single folder selection with addGrant
 */
Tine.Filemanager.nodeActions.CreateFolder = {
    app: 'Filemanager',
    requiredGrant: 'addGrant',
    allowMultiple: false,
    // actionType: 'add',
    text: 'Create Folder', // _('Create Folder')
    disabled: true,
    iconCls: 'action_create_folder',
    scope: this,
    handler: function() {
        var app = this.initialConfig.app,
            currentFolderNode = this.initialConfig.selections[0] || this.initialConfig.filteredContainer,
            currentPath = _.get(currentFolderNode, 'data.path'),
            nodeName = Tine.Filemanager.Model.Node.getContainerName();

        if (! currentPath) return;

        const grid = _.get(this, 'initialConfig.selectionModel.grid');
        if (grid) {
            const gridWdgt = grid.ownerCt.ownerCt;
            const newRecord = new Tine.Filemanager.Model.Node(Tine.Filemanager.Model.Node.getDefaultData({
                name: app.i18n._('New Folder'),
                type: 'folder'
            }));
            
            gridWdgt.newInlineRecord(newRecord, 'name', async (localRecord) => {
                const nodeData = await Tine.Filemanager.createNode(currentPath + '/' + localRecord.get('name'), 'folder', [], false);
                return Tine.Tinebase.data.Record.setFromJson(nodeData, Tine.Filemanager.Model.Node);
            });
        } else {
            Ext.MessageBox.prompt(app.i18n._('New Folder'), app.i18n._('Please enter the name of the new folder:'), function (btn, text) {
                if (currentFolderNode && btn === 'ok') {
                    if (!text) {
                        Ext.Msg.alert(String.format(app.i18n._('No {0} added'), nodeName), String.format(app.i18n._('You have to supply a {0} name!'), nodeName));
                        return;
                    }

                    var filename = currentPath + '/' + text;
                    Tine.Filemanager.fileRecordBackend.createFolder(filename);
                }
            }, this);
        }
    },
    actionUpdater: function(action, grants, records, isFilterSelect, filteredContainers) {
        var enabled = !isFilterSelect
            && records && records.length === 1
            && records[0].get('type') === 'folder'
            && window.lodash.get(records, '[0].data.account_grants.addGrant', false);

        if (! _.get(records, 'length') && filteredContainers) {
            enabled = _.get(filteredContainers, '[0].account_grants.addGrant', false);
            action.initialConfig.filteredContainer = Tine.Tinebase.data.Record.setFromJson(filteredContainers[0], Tine.Filemanager.Model.Node);
        }

        action.setDisabled(!enabled);
    }
};

/**
 * show native file select, upload files, create nodes
 * a single directory node with create grant has to be selected
 * for this action to be active
 */
// Tine.Filemanager.nodeActions.UploadFiles = {};

/**
 * single file or directory node with readGrant
 */
Tine.Filemanager.nodeActions.Edit = {
    app: 'Filemanager',
    requiredGrant: 'readGrant',
    allowMultiple: false,
    text: 'Edit Properties', // _('Edit Properties')
    iconCls: 'action_edit_file',
    disabled: true,
    // actionType: 'edit',
    scope: this,
    handler: function () {
        const selectedNode = _.get(this, 'initialConfig.selections[0]') ||_.get(this, 'initialConfig.filteredContainer')
        if(selectedNode) {
            Tine.Filemanager.NodeEditDialog.openWindow({record: selectedNode});
        }
    },
    actionUpdater: Tine.Filemanager.nodeActions.actionUpdater
};

/**
 * single file or directory node with editGrant
 */
Tine.Filemanager.nodeActions.Rename = {
    app: 'Filemanager',
    requiredGrant: 'editGrant',
    allowMultiple: false,
    text: 'Rename', // _('Rename')
    iconCls: 'action_rename',
    disabled: true,
    // actionType: 'edit',
    scope: this,
    handler: function () {
        var _ = window.lodash,
            app = this.initialConfig.app,
            record = this.initialConfig.selections[0],
            nodeName = record.get('type') == 'folder' ?
                Tine.Filemanager.Model.Node.getContainerName() :
                Tine.Filemanager.Model.Node.getRecordName();

        Ext.MessageBox.show({
            title: String.format(i18n._('Rename {0}'), nodeName),
            msg: String.format(i18n._('Please enter the new name of the {0}:'), nodeName),
            buttons: Ext.MessageBox.OKCANCEL,
            value: record.get('name'),
            fn: function (btn, text) {
                if (btn == 'ok') {
                    if (!text) {
                        Ext.Msg.alert(String.format(i18n._('Not renamed {0}'), nodeName), String.format(i18n._('You have to supply a {0} name!'), nodeName));
                        return;
                    }

                    this.initialConfig.executor(record, text);
                }
            },
            scope: this,
            prompt: true,
            icon: Ext.MessageBox.QUESTION
        });
    },

    executor: function(record, text) {
        // @TODO validate filename
        var targetPath = record.get('path').replace(new RegExp(_.escapeRegExp(record.get('name')) +'$'), text);
        Tine.Filemanager.fileRecordBackend.copyNodes([record], targetPath, true);
    }
};

/**
 * single file or directory node with editGrant
 */
Tine.Filemanager.nodeActions.SystemLink = {
    app: 'Filemanager',
    requiredGrant: 'readGrant',
    allowMultiple: false,
    allowVirtual: true,
    allowQuarantined: true,
    text: 'System Link', // _('System Link')
    iconCls: 'action_system_link',
    disabled: true,
    // actionType: 'edit',
    scope: this,
    handler: function () {
        var _ = window.lodash,
            app = this.initialConfig.app,
            record = _.get(this, 'initialConfig.selections[0]') ||_.get(this, 'initialConfig.filteredContainer');

        Ext.MessageBox.show({
            title: i18n._('System Link'),
            // minWidth:
            maxWidth: screen.availWidth,
            msg: '<b>' + app.i18n._('Use this link to share the entry with other system users') + ':</b><br>'
                    + record.getSystemLink(),
            buttons: Ext.MessageBox.OK,
            // value: record.getSystemLink(),
            // prompt: true,
            icon: Ext.MessageBox.INFO
        });
    },
    actionUpdater: Tine.Filemanager.nodeActions.actionUpdater
};

/**
 * one or multiple nodes, all need deleteGrant
 */
Tine.Filemanager.nodeActions.Delete = {
    app: 'Filemanager',
    requiredGrant: 'deleteGrant',
    allowMultiple: true,
    text: 'Delete', // _('Delete')
    disabled: true,
    iconCls: 'action_delete',
    scope: this,
    handler: function (button, event) {
        var app = this.initialConfig.app,
            nodeName = '',
            nodes = _.get(this, 'initialConfig.selections', []).length ?
                _.get(this, 'initialConfig.selections', []) :
                _.compact([_.get(this, 'initialConfig.filteredContainer')]);

        if (nodes && nodes.length) {
            for (var i = 0; i < nodes.length; i++) {
                var currNodeData = nodes[i].data;
                nodeName += Tine.Tinebase.EncodingHelper.encode(typeof currNodeData.name == 'object' ?
                    currNodeData.name.name :
                    currNodeData.name) + '<br />';
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
            handler: function (button) {
                if (nodes && button == 'yes') {
                    Tine.Filemanager.fileRecordBackend.deleteItems(nodes);
                }

                for (var i = 0; i < nodes.length; i++) {
                    var node = nodes[i];

                    if (node.fileRecord) {
                        var upload = Tine.Tinebase.uploadManager.getUpload(node.fileRecord.get('uploadKey'));
                        if (upload) {
                            upload.setPaused(true);
                            Tine.Tinebase.uploadManager.unregisterUpload(upload.id);
                        }
                    }

                }
            }
        });
    },
    actionUpdater: Tine.Filemanager.nodeActions.actionUpdater
};

/**
 * one node with readGrant
 */
// Tine.Filemanager.nodeActions.Copy = {};

/**
 * one or multiple nodes with read, edit AND deleteGrant
 */
Tine.Filemanager.nodeActions.Move = {
    app: 'Filemanager',
    requiredGrant: 'editGrant',
    allowMultiple: true,
    text: 'Move', // _('Move')
    disabled: true,
    actionType: 'edit',
    scope: this,
    iconCls: 'action_move',
    handler: function() {
        var app = this.initialConfig.app,
            i18n = app.i18n,
            records = this.initialConfig.selections;

        var filePickerDialog = new Tine.Filemanager.FilePickerDialog({
            windowTitle: app.i18n._('Move Items'),
            singleSelect: true,
            constraint: 'folder'
        });

        filePickerDialog.on('apply', function(node) {
            Tine.Filemanager.fileRecordBackend.copyNodes(records, node, true);
        });

        filePickerDialog.openWindow();
    }
};

/**
 * one file node with download grant
 */
Tine.Filemanager.nodeActions.Download = {
    app: 'Filemanager',
    requiredGrant: 'downloadGrant',
    allowMultiple: false,
    actionType: 'download',
    text: 'Save locally', // _('Save locally')
    iconCls: 'action_filemanager_save_all',
    disabled: true,
    scope: this,
    init: function() {
        this.hidden = !Tine.Tinebase.configManager.get('downloadsAllowed');
    },
    handler: function() {
        Tine.Filemanager.downloadFile(this.initialConfig.selections[0]);
    },
    actionUpdater: function(action, grants, records, isFilterSelect) {

        var enabled = !isFilterSelect
            && records && records.length == 1
            && records[0].get('type') != 'folder'
            && window.lodash.get(records, '[0].data.account_grants.downloadGrant', false);

        action.setDisabled(!enabled);
    }
};

/**
 * one file node with readGrant
 */
Tine.Filemanager.nodeActions.Preview = {
    app: 'Filemanager',
    allowMultiple: false,
    requiredGrant: 'readGrant',
    text: 'Preview', // _('Preview')
    disabled: true,
    iconCls: 'action_preview',
    scope: this,
    constraint: 'file',
    handler: function () {
        var selection = _.get(this, 'initialConfig.selections[0]') ||_.get(this, 'initialConfig.filteredContainer');

        if (selection) {
            if (selection.get('type') === 'file') {
                Tine.Filemanager.QuickLookPanel.openWindow({
                    record: selection,
                    initialApp: this.initialConfig.initialApp || null,
                    sm: this.initialConfig.sm
                });
            }
        }
    },
    actionUpdater: Tine.Filemanager.nodeActions.actionUpdater
};

/**
 * one node with publish grant
 */
Tine.Filemanager.nodeActions.Publish = {
    app: 'Filemanager',
    allowMultiple: false,
    requiredGrant: 'publishGrant',
    text: 'Publish', // _('Publish')
    disabled: true,
    iconCls: 'action_publish',
    scope: this,
    handler: function() {
        var app = this.initialConfig.app,
            i18n = app.i18n,
            selected = _.get(this, 'initialConfig.selections[0]') ||_.get(this, 'initialConfig.filteredContainer');

        if (! selected) {
            return;
        }

        var passwordDialog = new Tine.Tinebase.widgets.dialog.PasswordDialog({
            allowEmptyPassword: true,
            locked: false,
            questionText: i18n._('Download links can be protected with a password. If no password is specified, anyone who knows the link can access the selected files.')
        });
        passwordDialog.openWindow();

        passwordDialog.on('apply', function (password) {
            var date = new Date();
            date.setDate(date.getDate() + 30);

            var record = new Tine.Filemanager.Model.DownloadLink({
                node_id: selected.id,
                expiry_time: date,
                password: password
            });

            Tine.Filemanager.downloadLinkRecordBackend.saveRecord(record, {
                success: function (record) {
                    Tine.Filemanager.FilePublishedDialog.openWindow({
                        title: selected.data.type === 'folder' ? app.i18n._('Folder has been published successfully') : app.i18n._('File has been published successfully'),
                        record: record,
                        password: password
                    });
                }, failure: Tine.Tinebase.ExceptionHandler.handleRequestException, scope: this
            });
        }, this);
    },
    actionUpdater: Tine.Filemanager.nodeActions.actionUpdater
};

/**
 * one or multiple file nodes currently uploaded
 */
Tine.Filemanager.nodeActions.PauseUploadAction = {};

/**
 * one or multiple file nodes currently upload paused
 */
Tine.Filemanager.nodeActions.ResumeUploadAction = {};

/**
 * one or multiple file nodes currently uploaded or upload paused
 * @TODO deletes node as well?
 */
Tine.Filemanager.nodeActions.CancelUploadAction = {};
