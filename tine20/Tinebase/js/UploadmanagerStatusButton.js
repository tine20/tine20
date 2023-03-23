/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Tinebase');

/**
 * user profile panel
 *
 * @namespace   Tine.Tinebase
 * @class       Tine.Tinebase.UploadManagerStatusButton
 * @extends     Ext.Button
 * @author      Ching En Cheng <c.cheng@metaways.de>
 */
Tine.Tinebase.UploadManagerStatusButton = Ext.extend(Ext.Button, {

    /**
     * @cfg {boolean} showIcon
     */
    showIcon: true,

    // private config overrides
    iconCls: 'action_upload_idle',

    /**
     * @property {String} status
     */
    status: 'idle',

    initComponent: async function () {
        this.hidden = !Tine.Tinebase.appMgr.isEnabled('Filemanager');
        this.app = Tine.Tinebase.appMgr.get('Filemanager');
        if (!this.app) return;
        const allTasks = await Tine.Tinebase.uploadManager.getAllTasks();
        const tasks = allTasks.filter(t => t.handler === 'FilemanagerUploadFileTask');
    
        this.update(tasks);
        Tine.Tinebase.UploadManagerStatusButton.superclass.initComponent.call(this);
    },
    
    handler: function() {
        Ext.ux.file.UploadManagementDialog.openWindow();
    },
    
    update(allTasks = null, uploading = false) {
        if (!allTasks) return;
        
        const fileTasks = allTasks.filter(t => t.handler === 'FilemanagerUploadFileTask');
        const total = fileTasks.length;
        const complete = fileTasks.filter(t => t.status === 'complete').length;
        const failed = fileTasks.filter(t => t.status === 'failed').length;
        
        const info = [
            '<table>',
                '<tr>',
                    '<td>', this.app.i18n._('Total    Uploads') + ':', '</td>', '<td>', total, '</td>',
                '</tr>',
                '<tr>',
                    '<td>', this.app.i18n._('Complete Uploads') + ':', '</td>', '<td>', complete, '</td>',
                '</tr>',
                '<tr>',
                    '<td>', this.app.i18n._('Failed   Uploads') + ':', '</td>', '<td>', failed, '</td>',
                '</tr>',
            '</table>'
        ];
        
        const iconClass = uploading ? 'action_upload_uploading' : failed > 0 ? 'action_upload_error' : 'action_upload_idle';
        
        this.setTooltip(info.join(''));
        this.setIconClass(iconClass);
    },
});

Ext.onReady(() => {
    Ext.ux.ItemRegistry.registerItem('Tine.Tinebase.MainMenu', Tine.Tinebase.UploadManagerStatusButton, 55);
});
