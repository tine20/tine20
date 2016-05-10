/*
 * Tine 2.0
 *
 * @package     Expressomail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.namespace('Tine.Expressomail');

/**
 * @namespace   Tine.Expressomail
 * @class       Tine.Expressomail.AccountEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 *
 * <p>Account Edit Dialog</p>
 * <p>
 * </p>
 *
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 *
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Expressomail.AccountEditDialog
 *
 */
Tine.Expressomail.AccountEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    /**
     * @private
     */
    windowNamePrefix: 'AccountEditWindow_',
    appName: 'Expressomail',
    recordClass: Tine.Expressomail.Model.Account,
    recordProxy: Tine.Expressomail.accountBackend,
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
        Tine.Expressomail.AccountEditDialog.superclass.onRecordLoad.call(this);

        // if account type == system disable most of the input fields
        if (this.record.get('type') == 'system') {
            this.getForm().items.each(function(item) {
                // only enable some fields
                switch(item.name) {
                    case 'signature':
                    case 'signature_position':
                    case 'display_format':
                        break;
                    default:
                        item.setDisabled(true);
                }
            }, this);
        }
    },    
	
    /**
     * uploadsuccess event for UploadImage plugin of HtmlEditor
     * 
     */
    onUploadSuccess : function(dialog, filename, resp_data, record) {
        if (resp_data.size > 16384) {
            // exceeded maximum image size for the signature
            dialog.showMessage( i18n._('Error'), i18n._('Signature image size cannot exceed 16384 bytes.') );
        }
        else {
            var cmp = dialog.cmp;
            if (!cmp.activated) {
                cmp.getEditorBody().focus();
                cmp.onFirstFocus();
            }
            var fileName = filename.replace(/[a-zA-Z]:[\\\/]fakepath[\\\/]/, '');
            var img_id = Math.round(Math.random()*1000000);
            var img = '<img id="user-signature-image-'+img_id+'" alt="'+fileName+'" src="data:image/jpeg;base64,'+resp_data.base64+'" />';
            var loc = /<img id="?user-signature-image-?[0-9]*"? alt="?([^\"]+)"? src="data:image\/jpeg;base64,([^"]+)">/i;
            // search for an image in the signature
            var pos = cmp.getValue().search(loc); 
            if (pos>=0) {
                // replace existing image with new one
                cmp.setValue(cmp.getValue().replace(loc, img));
            }
            else {
                // insert an image in the signature
                cmp.insertAtCursor(img);   
            }
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
            autoHeight: true,
            getDocMarkup: function(){
                var markup = '<span id="expressomail\-body\-signature">'
                    + '</span>';
                return markup;
            },
            plugins: [
                    Ext.apply(new Ext.ux.form.HtmlEditor.UploadImage(),
                    {
                        base64: 'yes',
                        uploadsuccess: this.onUploadSuccess
                    }),
                new Ext.ux.form.HtmlEditor.RemoveFormat()
            ]
        });

        var commonFormDefaults = {
            xtype: 'textfield',
            anchor: '100%',
            labelSeparator: '',
            maxLength: 256,
            columnWidth: 1
        };

        var disable = this.record.get('type') == 'system' ? true : false;

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
                formDefaults: commonFormDefaults,
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
                }, this.signatureEditor,
                {
                    fieldLabel: this.app.i18n._('Signature position'),
                    name: 'signature_position',
                    typeAhead     : false,
                    triggerAction : 'all',
                    lazyRender    : true,
                    editable      : false,
                    mode          : 'local',
                    forceSelection: true,
                    value: 'below',
                    xtype: 'combo',
                    store: [
                        ['above', this.app.i18n._('Above the quote')],
                        ['below',  this.app.i18n._('Below the quote')]
                    ]
                }
                ]]
            }
            , {

                title: this.app.i18n._('IMAP'),
                autoScroll: true,
                border: false,
                frame: true,
                xtype: 'columnform',
                formDefaults: commonFormDefaults,
                disabled: disable,
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
                formDefaults: commonFormDefaults,
                disabled: disable,
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
                formDefaults: commonFormDefaults,
                disabled: disable,
                items: [[{
                    fieldLabel: this.app.i18n._('Host'),
                    name: 'sieve_hostname',
                    maxLength: 64
                }, {
                    fieldLabel: this.app.i18n._('Port (Default: 2000)'),
                    name: 'sieve_port',
                    maxLength: 5,
                    xtype:'numberfield'
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
                formDefaults: commonFormDefaults,
                disabled: disable,
                items: [[{
                    fieldLabel: this.app.i18n._('Sent Folder Name'),
                    name: 'sent_folder',
                    xtype: 'expressomailfolderselect',
                    account: this.record,
                    maxLength: 64
                }, {
                    fieldLabel: this.app.i18n._('Trash Folder Name'),
                    name: 'trash_folder',
                    xtype: 'expressomailfolderselect',
                    account: this.record,
                    maxLength: 64
                }, {
                    fieldLabel: this.app.i18n._('Drafts Folder Name'),
                    name: 'drafts_folder',
                    xtype: 'expressomailfolderselect',
                    account: this.record,
                    maxLength: 64
                }, {
                    fieldLabel: this.app.i18n._('Templates Folder Name'),
                    name: 'templates_folder',
                    xtype: 'expressomailfolderselect',
                    account: this.record,
                    maxLength: 64
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
                        ['plain',  this.app.i18n._('Plain Text')],
                        ['content_type',  this.app.i18n._('Depending on content type (experimental)')]
                    ]
                }]]
            }
        ]
        };
            },

    /**
     * generic request exception handler
     *
     * @param {Object} exception
     */
    onRequestFailed: function(exception) {
        Tine.Expressomail.handleRequestException(exception);
        this.loadMask.hide();
    },
    
    onAfterApplyChanges: function(closeWindow) {
        this.window.rename(this.windowNamePrefix + this.record.id);
        this.loadMask.hide();
        this.saving = false;
        
        if (closeWindow) {
            this.window.fireEvent('saveAndClose');
            this.purgeListeners();
            this.window.close();
            if (Tine.Tinebase.registry.get('preferences').get('windowtype')=='Browser') {
                // reload mainscreen to make sure registry gets updated
                this.window.windowManager.getMainWindow().location = this.window.windowManager.getMainWindow().location.href.replace(/#+.*/, '');
            }
        }
    }
});

/**
 * Expressomail Account Edit Popup
 *
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
 Tine.Expressomail.AccountEditDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 620,
        height: 550,
        name: Tine.Expressomail.AccountEditDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Expressomail.AccountEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
