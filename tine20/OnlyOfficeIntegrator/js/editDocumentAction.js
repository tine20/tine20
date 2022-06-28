import {getType, isEditable} from "./OnlyOfficeTypes";
require('../styles/onlyoffice.less');

Promise.all([
    Tine.Tinebase.appMgr.isInitialised('Tinebase'),
    Tine.Tinebase.appMgr.isInitialised('Filemanager'),
    Tine.Tinebase.appMgr.isInitialised('OnlyOfficeIntegrator')
]).then(() => {
    const app = Tine.Tinebase.appMgr.get('OnlyOfficeIntegrator');
    
    const allowOpen = (fileNode) => {
        return getType(_.get(fileNode, 'data.name') || _.get(fileNode, 'data.filename'))
            && (_.get(fileNode, 'data.type') === 'file' || _.get(fileNode, 'data.tempFile') || _.get(fileNode, 'data.filename'))
            && !String(_.get(fileNode, 'data.contenttype')).match(/^vnd\.adobe\.partial-upload.*/)
            && !+_.get(fileNode, 'data.is_quarantined');
    };
    
    const actionConfig = {
        app: app,
        allowMultiple: false,
        disabled: true,
        iconCls: 'action_onlyoffice_edit',
        text: app.i18n._('Open Document'),

        emailInterceptor: async function(config) {
            const mask = await config.setWaitText(app.i18n._('Preparing Attachment...'));

            const emailAttachment = Tine.Tinebase.data.Record.setFromJson(config.recordData, Tine.Felamimail.Model.Attachment);
            const attachmentCache = await Tine.Felamimail.getAttachmentCache(['Felamimail_Model_Message', emailAttachment.get('messageId'), emailAttachment.get('partId')].join(':'));

            config.recordData = _.get(attachmentCache, 'attachments[0]');
            mask.hide();
        },

        handler: function () {
            const record = this.selections[0];
            const tempFile = record.get('tempFile');
            const isEmailAttachment = record.get('messageId');
            const recordData = tempFile ? JSON.stringify(tempFile) : record.toString();
            const win = Tine.OnlyOfficeIntegrator.OnlyOfficeEditDialog.openWindow({ recordData: recordData, id: record.id, contentPanelConstructorInterceptor: isEmailAttachment ? this.emailInterceptor : null });
        },

        actionUpdater: function (action, grants, records, isFilterSelect) {
            const fileName = _.get(records, '[0].data.name') || _.get(records, '[0].data.filename');
            const type = getType(fileName);
            const iconCls = isEditable(fileName)  && _.get(grants, 'editGrant') ? 'action_onlyoffice_edit' :
                type ? 'action_onlyoffice_view' : 'action_onlyoffice';
            const enabled = !isFilterSelect
                && records && records.length === 1
                && allowOpen(records[0]);

            action.setDisabled(!enabled);
            action.baseAction.setDisabled(!enabled); // WTF?

            action.setIconClass(iconCls);
            action.baseAction.setIconClass(iconCls);
        }
    };

    const editDocumentAction = new Ext.Action(_.assign(actionConfig, {scope: actionConfig}));

    // filemanager
    Ext.ux.ItemRegistry.registerItem('Filemanager-Node-GridPanel-ContextMenu', editDocumentAction, 2);
    Ext.ux.ItemRegistry.registerItem('Filemanager-Node-GridPanel-ActionToolbar-leftbtngrp', Ext.apply(new Ext.Button(editDocumentAction), {
        scale: 'medium',
        rowspan: 2,
        iconAlign: 'top'
    }), 2);

    // fmail
    Ext.ux.ItemRegistry.registerItem('Tine.Felamimail.MailDetailPanel.AttachmentMenu', editDocumentAction, 15);

    // upload grids
    Ext.ux.ItemRegistry.registerItem('Tinebase-FileUploadGrid-Toolbar', editDocumentAction, 5);
    Ext.ux.ItemRegistry.registerItem('Tinebase-FileUploadGrid-ContextMenu', editDocumentAction, 5);

    // preview panel
    Ext.ux.ItemRegistry.registerItem('Tine-Filemanager-DocumentPreview', editDocumentAction, 100);

    // dblclick actions
    Tine.on('filesystem.fileDoubleClick', (fileNode, dblClickHandlers) => {
        const dbClickPreference = Tine.Tinebase.registry.get('preferences').get('fileDblClickAction');
        
        if (allowOpen(fileNode) && dbClickPreference === 'openwithonlyoffice') {
            dblClickHandlers.push({
                prio: 1000,
                fn: _.bind(editDocumentAction.execute, editDocumentAction)
            })
        }
    });
});
