/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Felamimail');

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.AccountEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Account Edit Dialog</p>
 * <p>
 * </p>
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Felamimail.AccountEditDialog
 * 
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
     * @private
     */
    updateToolbars: function() {

    },
    
    /**
     * executed after record got updated from proxy
     * 
     * -> only allow to change some of the fields if it is a system account
     */
    onRecordLoad: function() {
        Tine.Felamimail.AccountEditDialog.superclass.onRecordLoad.call(this);
        
        // if account type == system disable most of the input fields
        if (this.record.get('type') == 'system') {
            this.getForm().items.each(function(item) {
                // only enable some fields
                switch(item.name) {
                    case 'name':
                    case 'signature':
                    case 'intelligent_folders':
                    case 'has_children_support':
                        break;
                    default:
                        item.setDisabled(true);
                }
            }, this);
        }
    },    
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     * @private
     */
    getFormItems: function() {

        this.signatureEditor = new Ext.form.HtmlEditor({
            fieldLabel: this.app.i18n._('Signature'),
            name: 'signature',
            allowBlank: true,
            height: 220,
            getDocMarkup: function(){
                var markup = '<span id="felamimail\-body\-signature">'
                    + '</span>';
                return markup;
            },
            plugins: [
                new Ext.ux.form.HtmlEditor.RemoveFormat()
            ]
        });
        
        return {
            xtype: 'tabpanel',
            deferredRender: false,
            border: false,
            activeTab: 0,
            items: [{
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
                    fieldLabel: this.app.i18n._('Organization'),
                    name: 'organization'
                }, this.signatureEditor
                /*, {
                    fieldLabel: this.app.i18n._('Signature'),
                    name: 'signature',
                    xtype: 'textarea',
                    height: 120,
                    maxLength: 2048
                }*/]]
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
                items: [[{
                    fieldLabel: this.app.i18n._('Host'),
                    name: 'host',
                    allowBlank: false
                }, {
                    fieldLabel: this.app.i18n._('Port (Default: 143 / SSL: 993)'),
                    name: 'port',
                    allowBlank: false,
                    maxLength: 5,
                    xtype: 'numberfield'
                }, {
                    fieldLabel: this.app.i18n._('Secure Connection'),
                    name: 'ssl',
                    typeAhead     : false,
                    triggerAction : 'all',
                    lazyRender    : true,
                    editable      : false,
                    mode          : 'local',
                    forceSelection: true,
                    value: 'none',
                    xtype: 'combo',
                    store: [
                        ['none', this.app.i18n._('None')],
                        ['tls',  this.app.i18n._('TLS')],
                        ['ssl',  this.app.i18n._('SSL')]
                    ]
                },{
                    fieldLabel: this.app.i18n._('Username'),
                    name: 'user',
                    allowBlank: false
                }, {
                    fieldLabel: this.app.i18n._('Password'),
                    name: 'password',
                    emptyText: 'password',
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
                }, {
                    fieldLabel: this.app.i18n._('Port (Default: 25)'),
                    name: 'smtp_port',
                    maxLength: 5,
                    xtype:'numberfield',
                    allowBlank: false
                }, {
                    fieldLabel: this.app.i18n._('Secure Connection'),
                    name: 'smtp_ssl',
                    typeAhead     : false,
                    triggerAction : 'all',
                    lazyRender    : true,
                    editable      : false,
                    mode          : 'local',
                    value: 'none',
                    xtype: 'combo',
                    store: [
                        ['none', this.app.i18n._('None')],
                        ['tls',  this.app.i18n._('TLS')],
                        ['ssl',  this.app.i18n._('SSL')]
                    ]
                }, {
                    fieldLabel: this.app.i18n._('Authentication'),
                    name: 'smtp_auth',
                    typeAhead     : false,
                    triggerAction : 'all',
                    lazyRender    : true,
                    editable      : false,
                    mode          : 'local',
                    xtype: 'combo',
                    value: 'login',
                    store: [
                        ['none',    this.app.i18n._('None')],
                        ['login',   this.app.i18n._('Login')],
                        ['plain',   this.app.i18n._('Plain')]
                    ]
                },{
                    fieldLabel: this.app.i18n._('Username (optional)'),
                    name: 'smtp_user'
                }, {
                    fieldLabel: this.app.i18n._('Password (optional)'),
                    name: 'smtp_password',
                    emptyText: 'password',
                    inputType: 'password'
                }]]
            }, {
                title: this.app.i18n._('Sieve'),
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
                    fieldLabel: this.app.i18n._('Host'),
                    name: 'sieve_hostname',
                    maxLength: 64
                }, {
                    fieldLabel: this.app.i18n._('Port (Default: 2000)'),
                    name: 'sieve_port',
                    maxLength: 64
                }, {
                    fieldLabel: this.app.i18n._('Secure Connection'),
                    name: 'sieve_ssl',
                    typeAhead     : false,
                    triggerAction : 'all',
                    lazyRender    : true,
                    editable      : false,
                    mode          : 'local',
                    value: 'none',
                    xtype: 'combo',
                    store: [
                        ['none', this.app.i18n._('None')],
                        ['tls',  this.app.i18n._('TLS')]
                    ]
                }]]
            }, {
                title: this.app.i18n._('Other Settings'),
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
                    fieldLabel: this.app.i18n._('Sent Folder Name'),
                    name: 'sent_folder',
                    maxLength: 64
                }, {
                    fieldLabel: this.app.i18n._('Trash Folder Name'),
                    name: 'trash_folder',
                    maxLength: 64
                }, {
                    fieldLabel: this.app.i18n._('Show Intelligent Folders'),
                    name: 'intelligent_folders',
                    typeAhead     : false,
                    triggerAction : 'all',
                    lazyRender    : true,
                    editable      : false,
                    mode          : 'local',
                    forceSelection: true,
                    value: '0',
                    xtype: 'combo',
                    store: [
                        ['0', this.app.i18n._('No')],
                        ['1',  this.app.i18n._('Yes')]
                    ]
                }, {
                    fieldLabel: this.app.i18n._('Display Format'),
                    name: 'display_format',
                    typeAhead     : false,
                    triggerAction : 'all',
                    lazyRender    : true,
                    editable      : false,
                    mode          : 'local',
                    forceSelection: true,
                    value: 'html',
                    xtype: 'combo',
                    store: [
                        ['html', this.app.i18n._('HTML')],
                        ['plain',  this.app.i18n._('Plain Text')]
                    ]
                }]]
            }]
        };
    }
});

/**
 * Felamimail Account Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
 Tine.Felamimail.AccountEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 400,
        height: 600,
        name: Tine.Felamimail.AccountEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Felamimail.AccountEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
