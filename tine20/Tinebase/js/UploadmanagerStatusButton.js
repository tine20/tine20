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

    initComponent: function() {
        Tine.Tinebase.UploadManagerStatusButton.superclass.initComponent.call(this);
    },

    handler: function() {
        Ext.ux.file.UploadManagementDialog.openWindow();
    },
    
    uploadActive() {
        this.setIconClass('action_upload_uploading');
    },
    
    uploadIdle() {
        this.setIconClass('action_upload_idle');
    }
});

Ext.onReady(() => {
    Ext.ux.ItemRegistry.registerItem('Tine.Tinebase.MainMenu', Tine.Tinebase.UploadManagerStatusButton, 55);
});
