/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import RecordEditFieldTriggerPlugin from "../../Tinebase/js/widgets/form/RecordEditFieldTriggerPlugin";

/**
 * quicklook panel file action for attachments
 */
Tine.Tinebase.appMgr.isInitialised('Felamimail').then(async () => {

    const app = Tine.Tinebase.appMgr.get('Felamimail');
    Ext.ux.ItemRegistry.registerItem('Tine-Filemanager-QuicklookPanel', getFileAttachmentAction(async (locations, action, attachments) => {
        const record = _.get(action, 'ownerCt.ownerCt.selection[0]', _.get(action, 'selection[0]'));

        if (locations === 'download') {
            Ext.ux.file.Download.start({ url: Tine.Filemanager.Model.Node.getDownloadUrl(record) });
        } else {
            const srcLocation = {
                type: 'attachment',
                model: 'Felamimail_Model_AttachmentCache',
                record_id: record.get('path').split('/')[3],
                file_name: record.get('name')
            };
            await Tine.Tinebase.copyNodes([srcLocation], locations[0], true);
            return 1;
        }
    }, {
        hidden: true,
        actionUpdater: (action, grants, records, isFilterSelect) => {
            action.menu?.items.each((item) => {
                item.selection = records;
            });
            const isMailAttachment = String(_.get(records, '[0].data.path')).match(/^\/records\/Felamimail_Model_AttachmentCache/);
            action.baseAction.setHidden(!isMailAttachment);
        },
    }), 50);
});

/**
 * @param {Function} fileFn
 * @param config
 * @returns {Ext.Action}
 */
const getFileAttachmentAction = (fileFn, config) => {
    const app = Tine.Tinebase.appMgr.get('Felamimail');

    return new Ext.Action(Object.assign({
        text: app.i18n._('Save As'),
        iconCls: 'action_filemanager_save_all',
        menu: [{
            text: app.i18n._('File (in Filemanager) ...'),
            hidden: !Tine.Tinebase.common.hasRight('run', 'Filemanager'),
            disabled: config.record?.get('from_node'), // not implemented @see \Felamimail_Controller_Message_File::fileAttachments
            handler: (action, e) => {
                // details panel vs. messageDisplayDialog
                const attachments = config.attachments || action.selection;

                // we don't set constrain here because user should be able to select both folder and file as target
                const filePickerDialog = new Tine.Filemanager.FilePickerDialog({
                    mode: 'target',
                    singleSelect: true,
                    requiredGrants: ['addGrant'],
                    files: attachments,
                    initialPath: Tine.Tinebase.container.getMyFileNodePath() + '/',
                });

                filePickerDialog.on('selected', async (nodes) => {
                    if (attachments.length === 1) {
                        attachments[0].filename = filePickerDialog.filePicker.fileName;
                    }
                    
                    const type = _.get(nodes, '[0].type');
                    let path = _.get(nodes, '[0].path');
                    let recordId =  _.get(nodes[0], 'nodeRecord.data', nodes[0]);
                    
                    //attachment data only accept folder node as location
                    if (type === 'file') {
                        path = Tine.Filemanager.Model.Node.dirname(path);
                        recordId = nodes[0]?.parent_id ?? _.get(nodes[0], 'recordId');
                    }

                    const attachmentCount = await fileFn([{
                        type: 'fm_node',
                        model: 'Filemanager_Model_Node',
                        fm_path: path,
                        record_id: recordId,
                        file_name: attachments.length === 1 ? filePickerDialog.filePicker.fileName : null
                    }], action, attachments);

                    const msg = app.formatMessage('{attachmentCount, plural, one {Attachment was saved} other {# Attachments where saved}}',
                        {attachmentCount});
                    Ext.ux.MessageBox.msg(app.formatMessage('Success'), msg);
                });
                
                filePickerDialog.openWindow();
            }
        }, {
            text: app.i18n._('Attachment (of Record)'),
            disabled: config.record?.get('from_node'), // not implemented @see \Felamimail_Controller_Message_File::fileAttachments
            listeners: {
                render: (cmp) => {
                    cmp.menu.add(_.reduce(Tine.Tinebase.data.RecordMgr.items, (menu, model) => {
                        if (model.hasField('attachments') && model.getMeta('appName') !== 'Felamimail') {
                            menu.push({
                                text: model.getRecordName() + ' ...',
                                iconCls: model.getIconCls(),
                                handler: async (action, e) => {
                                    let attachmentRecordsData = [];
                                    if (config?.attachments) {
                                        await _.reduce(config?.attachments, async (prev, attachment, id)=> {
                                            return prev.then(async () => {
                                                await Promise.all(attachment.promises);
                                                return attachmentRecordsData.push(attachment.cache.data);
                                            })
                                        }, Promise.resolve());
                                    }
                                    var pickerDialog = Tine.WindowFactory.getWindow({
                                        layout: 'fit',
                                        width: 250,
                                        height: 100,
                                        padding: '5px',
                                        modal: true,
                                        title: app.i18n._('Save as Record Attachment'),
                                        items: new Tine.Tinebase.dialog.Dialog({
                                            listeners: {
                                                apply: async (fileTarget) => {
                                                    const attachmentCount = await fileFn([fileTarget], action);
                                                    const msg = app.formatMessage('{attachmentCount, plural, one {Attachment was saved} other {# Attachments where saved}}',
                                                        {attachmentCount});
                                                    Ext.ux.MessageBox.msg(app.formatMessage('Success'), msg);
                                                }
                                            },
                                            getEventData: function (eventName) {
                                                if (eventName === 'apply') {
                                                    var attachRecord = this.getForm().findField('attachRecord').selectedRecord;
                                                    return {
                                                        type: 'attachment',
                                                        model: model.getPhpClassName(),
                                                        record_id: attachRecord.data,
                                                    };
                                                }
                                            },
                                            items: Tine.widgets.form.RecordPickerManager.get(model.getMeta('appName'), model.getMeta('modelName'), {
                                                fieldLabel: model.getRecordName(),
                                                name: 'attachRecord',
                                                plugins: [new RecordEditFieldTriggerPlugin({
                                                    editDialogMode: 'remote',
                                                    attachments: attachmentRecordsData,
                                                })]
                                            })
                                        })
                                    })
                                }
                            });
                        }
                        return menu;
                    }, []));
            }},
            menu: []
        }, {
            xtype: 'menuseparator',
            hidden: !Tine.Tinebase.configManager.get('downloadsAllowed')
        }, {
            text: app.i18n._('Download'),
            iconCls: 'action_download',
            hidden: !Tine.Tinebase.configManager.get('downloadsAllowed'),
            handler: async (action, e) => {
                await fileFn('download', action);
            }
        }]
    }, config));
};

export default getFileAttachmentAction
