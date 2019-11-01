/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Felamimail.admin');

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.admin.ConvertAccountPanel
 * @extends     Ext.FormPanel
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

Tine.Felamimail.admin.ConvertAccountPanel = Ext.extend(Ext.FormPanel, {

    // private
    app: null,
    layout : 'fit',
    border : true,
    cls : 'tw-editdialog',
    labelAlign : 'top',
    bodyStyle:'padding:5px',
    anchor : '100% 100%',
    deferredRender : false,
    buttonAlign : null,
    bufferResize : 500,

    filter: null,
    account: null,
    // TODO add for user manual
    // canonicalName: ['', 'Felamimail', 'ConvertAccountPanel'].join(Tine.Tinebase.CanonicalPath.separator),
    
    /**
     * initializes the component
     */
    initComponent: function() {

        this.app = Tine.Tinebase.appMgr.get('Felamimail');
        this.title = this.app.i18n._('Convert this account to a shared account. Please set a new password for the account:');

        // init actions
        this.initActions();
        // init buttons and tbar
        this.initButtons();

        // get items for this dialog
        this.items = this.getFormItems();

        Tine.Felamimail.admin.ConvertAccountPanel.superclass.initComponent.call(this);
    },

    /**
     * initializes the actions
     */
    initActions: function() {
        this.action_cancel = new Ext.Action({
            text : i18n._('Cancel'),
            minWidth : 70,
            scope : this,
            handler : this.onCancel,
            iconCls : 'action_cancel'
        });

        this.action_update = new Ext.Action({
            text : i18n._('Ok'),
            minWidth : 70,
            scope : this,
            handler : this.onUpdate,
            iconCls : 'action_saveAndClose'
        });
    },

    /**
     * create the buttons
     * use preference settings for order of save and close buttons
     */
    initButtons : function() {
        this.fbar = ['->'];

        this.fbar.push(this.action_cancel, this.action_update);
    },

    /**
     * is called on cancel
     */
    onCancel: function() {
        this.fireEvent('cancel');
        this.onClose();
    },

    /**
     * closes the window
     */
    onClose: function() {
        this.purgeListeners();
        this.window.close();
    },

    /**
     * returns true if the form is valid, must be overridden
     * @return {Boolean}
     */
    isValid: function() {
        // TODO validate something here?
        return true;
    },

    /**
     * returns the items of the form, must be overridden
     * @return {Object} with the configured items
     */
    getFormItems: function() {
        // TODO add type chooser

        return [
            {
                fieldLabel: this.app.i18n._('New Password'),
                name: 'password',
                emptyText: 'password',
                xtype: 'tw-passwordTriggerField',
                inputType: 'password',
                allowBlank: false
            }
        ];
    },

    /**
     * is called when ok-button is pressed and edit dialog should be opened
     */
    onUpdate: function() {
        if (this.isValid()) {
            this.account.set('password', this.getForm().findField('password').getValue());

            Ext.MessageBox.wait(this.app.i18n._('Please wait'), this.app.i18n._('Converting Account ...'));
            Ext.Ajax.request({
                params: {
                    method: 'Admin.convertEmailAccount',
                    recordData: this.account.data
                },
                scope: this,
                success: function(result, request) {
                    Ext.MessageBox.hide();
                    this.onClose();
                },
                failure: function(response, request) {
                    Ext.MessageBox.hide();
                    Tine.Tinebase.ExceptionHandler.handleRequestException(response);
                }
            });
        }
    }
});

/**
 * Opens a new ConvertAccountPanel window
 *
 * @return {Ext.ux.Window}
 */
Tine.Felamimail.admin.ConvertAccountPanel.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        modal: true,
        width: 350,
        height: 120,
        name: 'Tine.Felamimail.admin.ConvertAccountPanel', // + add random id,
        contentPanelConstructor: 'Tine.Felamimail.admin.ConvertAccountPanel',
        contentPanelConstructorConfig: config
    });
    return window;
};
