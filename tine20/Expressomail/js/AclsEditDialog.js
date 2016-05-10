/*
 * Tine 2.0
 *
 * @package     Expressomail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.namespace('Tine.Expressomail');

/**
 * @namespace   Tine.Expressomail
 * @class       Tine.Expressomail.AclsEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 *
 * <p>Folder ACL edit dialog</p>
 * <p>
 * </p>
 *
 * @author      Bruno Vieira Costa <bruno.vieira-costa@serpro.gov.br>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 *
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Expressomail.AclsEditDialog
 *
 */
Tine.Expressomail.AclsEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    /**
     * @private {Ext.data.JsonStore}
     */
    aclsStore: null,

    /**
     * @private
     */
    windowNamePrefix: 'AclsEditWindow_',
    loadRecord: false,
    tbarItems: [],
    evalAcls: false,

    /**
     * @private
     */
    initComponent: function() {

        this.aclStore =  new Ext.data.JsonStore({
            baseParams: {
                method: 'Expressomail.getFolderAcls',
                accountId: this.initialConfig.accountId,
                globalName: this.initialConfig.globalName
            },
            root: 'results',
            totalProperty: 'totalcount',
            //id: 'id',
            // use account_id here because that simplifies the adding of new records with the search comboboxes
            id: 'account_id',
            fields: Tine.Expressomail.Model.Acl,
            listeners: {
                scope: this,
                'beforeload': function () {
                    this.aclsLoadMask.show();
                },
                'load': function () {
                    this.aclsLoadMask.hide();
                }
            }
        });
        this.on('show', this.onShow, this);

        Tine.Expressomail.AclsEditDialog.superclass.initComponent.call(this);
    },

    /**
     * on dialog show
     */
    onShow: function() {
        this.aclsLoadMask = new Ext.LoadMask(this.ownerCt.container, {msg: i18n._('Loading ...')});
        this.aclStore.load();
    },

    /**
     * init record to edit
     *
     * - overwritten: we don't have a record here
     */
    initRecord: function() {
    },

    /**
     * returns dialog
     */
    getFormItems: function() {
        this.aclsGrid = new  Tine.Expressomail.AclsGrid({
            store: this.aclStore
        });

        return this.aclsGrid;
    },

    /**
     * @private
     */
    onApplyChanges: function(button, event, closeWindow) {
        Ext.MessageBox.wait(i18n._('Please wait'), i18n._('Updating Grants'));

        var acls = [];
        this.aclStore.each(function(_record){
            acls.push(_record.data);
        });

        Ext.Ajax.request({
            params: {
                method: 'Expressomail.setFolderAcls',
                accountId:this.initialConfig.accountId,
                globalName: this.initialConfig.globalName,
                acls: acls
            },
            scope: this,
            timeout: 300000, // 5 minutes
            success: function(_result, _request){
                Ext.MessageBox.hide();
                if (closeWindow || typeof closeWindow ==='undefined') {
                    this.purgeListeners();
                    this.window.close();
                    return;
                }

                var grants = Ext.util.JSON.decode(_result.responseText);
                this.grantsStore.loadData(grants, false);
            },
            failure: function(response, options) {
                var responseText = Ext.util.JSON.decode(response.responseText);

                if (responseText.data.code == 505) {
                    Ext.Msg.show({
                       title:   i18n._('Error'),
                       msg:     i18n._('You are not allowed to remove all admins for this container!'),
                       icon:    Ext.MessageBox.ERROR,
                       buttons: Ext.Msg.OK
                    });

                } else {
                    // call default exception handler
                    var exception = responseText.data ? responseText.data : responseText;
                    Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
                }
            }
        });
    }
});

/**
 * grants dialog popup / window
 */
Tine.Expressomail.AclsEditDialog.openWindow = function (config) {

    this.account = config.accountId;
    this.folderId = config.globalName;
    var window = Tine.WindowFactory.getWindow({
        width: 700,
        height: 450,
        name: Tine.Expressomail.AclsEditDialog.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Expressomail.AclsEditDialog',
        contentPanelConstructorConfig: config,
        modal: true
    });
    return window;
};

