/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.MailFiler.nodeActions');

/**
 * @singleton
 */
Tine.MailFiler.nodeActionsMgr = new (Ext.extend(Tine.widgets.ActionManager, {
    actionConfigs: Tine.MailFiler.nodeActions
}))();

// TODO: add more actions
// TODO: extend Filemanager.nodeActions

/**
 * create new folder, needs a single folder selection with addGrant
 */
Tine.MailFiler.nodeActions.CreateFolder = {
    app: 'MailFiler',
    requiredGrant: 'addGrant',
    allowMultiple: false,
    // actionType: 'add',
    text: 'Create Folder', // _('Create Folder')
    disabled: true,
    iconCls: 'action_create_folder',
    scope: this,
    handler: function() {
        var app = this.initialConfig.app,
            currentFolderNode = this.initialConfig.selections[0],
            nodeName = Tine.MailFiler.Model.Node.getContainerName();

        Ext.MessageBox.prompt(app.i18n._('New Folder'), app.i18n._('Please enter the name of the new folder:'), function(btn, text) {
            if(currentFolderNode && btn == 'ok') {
                if (! text) {
                    Ext.Msg.alert(String.format(app.i18n._('No {0} added'), nodeName), String.format(app.i18n._('You have to supply a {0} name!'), nodeName));
                    return;
                }

                var filename = currentFolderNode.get('path') + '/' + text;
                Tine.MailFiler.fileRecordBackend.createFolder(filename);
            }
        }, this);
    },
    actionUpdater: function(action, grants, records, isFilterSelect) {
        var enabled = !isFilterSelect
            && records && records.length == 1
            && records[0].get('type') == 'folder'
            && window.lodash.get(records, '[0].data.account_grants.addGrant', false);

        action.setDisabled(!enabled);
    }
};

/**
 * show native file select, upload files, create nodes
 * a single directory node with create grant has to be selected
 * for this action to be active
 */
// Tine.MailFiler.nodeActions.UploadFiles = {};

/**
 * single file or directory node with readGrant
 */
Tine.MailFiler.nodeActions.Edit = {
    app: 'MailFiler',
    requiredGrant: 'readGrant',
    allowMultiple: false,
    text: 'Edit Properties', // _('Edit Properties')
    iconCls: 'action_edit_file',
    disabled: true,
    // actionType: 'edit',
    scope: this,
    handler: function () {
        if(this.initialConfig.selections.length == 1) {
            Tine.MailFiler.NodeEditDialog.openWindow({record: this.initialConfig.selections[0]});
        }
    },
    actionUpdater: function(action, grants, records, isFilterSelect) {
        // run default updater
        Tine.widgets.ActionUpdater.prototype.defaultUpdater(action, grants, records, isFilterSelect);

        var _ = window.lodash,
            disabled = _.isFunction(action.isDisabled) ? action.isDisabled() : action.disabled;

        // if enabled check for not accessible node and disable
        if (! disabled) {
            action.setDisabled(window.lodash.reduce(records, function(disabled, record) {
                return disabled || record.isVirtual();
            }, false));
        }
    }
};

/**
 * single file or directory node with editGrant
 */
Tine.MailFiler.nodeActions.Rename = {
    app: 'MailFiler',
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
                Tine.MailFiler.Model.Node.getContainerName() :
                Tine.MailFiler.Model.Node.getRecordName();

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

                    // @TODO validate filename
                    var targetPath = record.get('path').replace(_.escapeRegExp(new RegExp(record.get('name')) +'$'), text);
                    Tine.MailFiler.fileRecordBackend.copyNodes([record], targetPath, true);

                }
            },
            scope: this,
            prompt: true,
            icon: Ext.MessageBox.QUESTION
        });
    }
};

/**
 * one or multiple nodes, all need deleteGrant
 */
Tine.MailFiler.nodeActions.Delete = {
    app: 'MailFiler',
    requiredGrant: 'deleteGrant',
    allowMultiple: true,
    text: 'Delete', // _('Delete')
    disabled: true,
    iconCls: 'action_delete',
    scope: this,
    handler: function (button, event) {
        var app = this.initialConfig.app,
            nodeName = '',
            nodes = this.initialConfig.selections;

        if (nodes && nodes.length) {
            for (var i = 0; i < nodes.length; i++) {
                var currNodeData = nodes[i].data;

                if (typeof currNodeData.name == 'object') {
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
            handler: function (button) {
                if (nodes && button == 'yes') {
                    Tine.MailFiler.fileRecordBackend.deleteItems(nodes);
                }

                for (var i = 0; i < nodes.length; i++) {
                    var node = nodes[i];

                    if (node.fileRecord) {
                        var upload = Tine.Tinebase.uploadManager.getUpload(node.fileRecord.get('uploadKey'));
                        upload.setPaused(true);
                        Tine.Tinebase.uploadManager.unregisterUpload(upload.id);
                    }

                }
            }
        });
    }
};

/**
 * one node with readGrant
 */
// Tine.MailFiler.nodeActions.Copy = {};

/**
 * one or multiple nodes with read, edit AND deleteGrant
 */
Tine.MailFiler.nodeActions.Move = {
    app: 'MailFiler',
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

        var filePickerDialog = new Tine.MailFiler.FilePickerDialog({
            title: app.i18n._('Move Items'),
            singleSelect: true,
            constraint: 'folder'
        });

        filePickerDialog.on('selected', function(nodes) {
            var node = nodes[0];
            Tine.MailFiler.fileRecordBackend.copyNodes(records, node.path, true);
        });

        filePickerDialog.openWindow();
    },
};

/**
 * one file node with download grant
 */
Tine.MailFiler.nodeActions.Download = {
    app: 'MailFiler',
    requiredGrant: 'downloadGrant',
    allowMultiple: false,
    actionType: 'download',
    text: 'Save locally', // _('Save locally')
    iconCls: 'action_filemanager_save_all',
    disabled: true,
    scope: this,
    handler: function() {
        Tine.Filemanager.downloadFile(this.initialConfig.selections[0], null, 'MailFiler');
    },
    actionUpdater: function(action, grants, records, isFilterSelect) {
        var enabled = !isFilterSelect
            && records && records.length == 1
            && records[0].get('type') != 'folder'
            && window.lodash.get(records, '[0].data.account_grants.downloadGrant', false);

        action.setDisabled(!enabled);
    }
};

///**
// * one file node with readGrant
// */
//Tine.MailFiler.nodeActions.Preview = {
//    app: 'MailFiler',
//    allowMultiple: false,
//    requiredGrant: 'readGrant',
//    text: 'Preview', // _('Preview')
//    disabled: true,
//    iconCls: 'previewIcon',
//    scope: this,
//    handler: function () {
//        var selections = this.initialConfig.selections;
//
//        if (selections.length > 0) {
//            var selection = selections[0];
//
//            if (selection && selection.get('type') === 'file') {
//                Tine.MailFiler.DocumentPreview.openWindow({
//                    record: selection
//                });
//            }
//        }
//    }
//};
//
