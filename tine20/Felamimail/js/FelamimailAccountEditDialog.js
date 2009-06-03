/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:MessageEditDialog.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
 * TODO         add smtp credentials
 */
 
Ext.namespace('Tine.Felamimail');

/**
 * 
 * @class Tine.Felamimail.AccountEditDialog
 * @extends Tine.widgets.dialog.EditDialog
 */
Tine.Felamimail.AccountEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'AccountEditWindow_',
    appName: 'Felamimail',
    recordClass: Tine.Felamimail.Model.Account,
    recordProxy: Tine.Felamimail.accountBackend,
    loadRecord: false,
    tbarItems: [],
    evalGrants: false,
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     */
    updateToolbars: function() {

    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     */
    getFormItems: function() {
        return {
            xtype: 'tabpanel',
            id: 'account-edit-tabpanel',
            border: false,
            plain:true,
            activeTab: 0,
            listeners: {
                scope: this,
                tabchange: function(panel, tab) {
                    // we need this as workaround as form is not initialized/filled with defaults in inactive tabs :(
                    // TODO find a better way for that because we can't check validity of 
                    //    fields in other tabs when they haven't been initialized
                    if (! tab.hasbeenselected) {
                        this.getForm().loadRecord(this.record);
                        tab.hasbeenselected = true;
                    }
                }
            },
            items:[{               
                title: this.app.i18n._('Account'),
                autoScroll: true,
                border: false,
                frame: true,
                xtype: 'columnform',
                formDefaults: {
                    xtype:'textfield',
                    anchor: '90%',
                    labelSeparator: '',
                    maxLength: 256,
                    columnWidth: 1
                },
                items: [[{
                    fieldLabel: this.app.i18n._('Account Name'),
                    name: 'name',
                    allowBlank: false
                }, {
                    fieldLabel: this.app.i18n._('User Email'),
                    name: 'email',
                    allowBlank: false,
                    vtype: 'email'
                }, {
                    fieldLabel: this.app.i18n._('User Name (From)'),
                    name: 'from'
                }, {
                    fieldLabel: this.app.i18n._('Signature'),
                    name: 'signature',
                    xtype: 'textarea',
                    height: 120
                }]]
            }, {               
                title: this.app.i18n._('IMAP'),
                autoScroll: true,
                border: false,
                frame: true,
                xtype: 'columnform',
                formDefaults: {
                    xtype:'textfield',
                    anchor: '90%',
                    labelSeparator: '',
                    maxLength: 256,
                    columnWidth: 1
                },
                items: [[ {
                    fieldLabel: this.app.i18n._('Host'),
                    name: 'host',
                    allowBlank: false
                }, {
                    fieldLabel: this.app.i18n._('Port'),
                    name: 'port',
                    allowBlank: false,
                    maxLength: 5,
                    xtype: 'numberfield'
                }, {
                    fieldLabel: this.app.i18n._('Secure Connection'),
                    name: 'secure_connection',
                    typeAhead     : false,
                    triggerAction : 'all',
                    lazyRender    : true,
                    editable      : false,
                    mode          : 'local',
                    forceSelection: true,
                    value: 'none',
                    xtype: 'combo',
                    store: [
                        ['none', _('None')],
                        ['tls',  _('TLS')],
                        ['ssl',  _('SSL')]
                    ]
                },{
                    fieldLabel: this.app.i18n._('Username'),
                    name: 'user',
                    allowBlank: false
                }, {
                    fieldLabel: this.app.i18n._('Password'),
                    name: 'password',
                    allowBlank: false,
                    inputType: 'password'
                }]]
            }, {               
                title: this.app.i18n._('SMTP'),
                autoScroll: true,
                border: false,
                frame: true,
                xtype: 'columnform',
                formDefaults: {
                    xtype:'textfield',
                    anchor: '90%',
                    labelSeparator: '',
                    maxLength: 256,
                    columnWidth: 1
                },
                items: [[ {
                    fieldLabel: this.app.i18n._('Host'),
                    name: 'smtp_hostname'
                    //allowBlank: false
                }, {
                    fieldLabel: this.app.i18n._('Port'),
                    name: 'smtp_port',
                    //allowBlank: false,
                    maxLength: 5,
                    xtype:'numberfield'
                }, {
                    fieldLabel: this.app.i18n._('Secure Connection'),
                    name: 'smtp_secure_connection',
                    typeAhead     : false,
                    triggerAction : 'all',
                    lazyRender    : true,
                    editable      : false,
                    mode          : 'local',
                    //forceSelection: true,
                    value: 'none',
                    xtype: 'combo',
                    store: [
                        ['none', _('None')],
                        ['tls',  _('TLS')],
                        ['ssl',  _('SSL')]
                    ]
                }, {
                    fieldLabel: this.app.i18n._('Authentication'),
                    name: 'smtp_auth',
                    typeAhead     : false,
                    triggerAction : 'all',
                    lazyRender    : true,
                    editable      : false,
                    mode          : 'local',
                    //forceSelection: true,
                    xtype: 'combo',
                    value: 'login',
                    store: [
                        ['login',  _('Login')],
                        ['plain',  _('Plain')]
                    ]
                }/*,{
                    fieldLabel: this.app.i18n._('Username'),
                    name: 'smtp_user',
                    allowBlank: false
                }, {
                    fieldLabel: this.app.i18n._('Password'),
                    name: 'smtp_password',
                    allowBlank: false,
                    inputType: 'password'
                }*/]]
            }]
        };
    }
});

/**
 * Felamimail Edit Popup
 */
Tine.Felamimail.AccountEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 400,
        height: 400,
        name: Tine.Felamimail.AccountEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Felamimail.AccountEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
