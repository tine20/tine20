/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:MessageEditDialog.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
 */
 
Ext.namespace('Tine.Felamimail');

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.MessageEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Message Compose Dialog</p>
 * <p>This dialog is for composing emails with recipients, body and attachments. 
 * you can choose from which account you want to send the mail.</p>
 * <p>
 * TODO         make email note editable
 * </p>
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 * 
 * @param       {Object} config
 * @constructor
 * Create a new MessageEditDialog
 */
 Tine.Felamimail.MessageEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'MessageEditWindow_',
    appName: 'Felamimail',
    recordClass: Tine.Felamimail.Model.Message,
    recordProxy: Tine.Felamimail.messageBackend,
    loadRecord: false,
    tbarItems: [],
    evalGrants: false,
    saveAndCloseButtonText: 'Send', // _('Send')
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     * 
     * @private
     */
    updateToolbars: function() {

    },
    
    /**
     * init record to edit
     * 
     * - overwritten to allow initialization from grid/onEditInNewWindow 
     * 
     * @private
     */
    initRecord: function() {
        if (this.record.id) {
            Tine.Felamimail.MessageEditDialog.superclass.initRecord.call(this);
        } else {
            this.onRecordLoad();
        }
    },
    
    /**
     * executed after record got updated from proxy
     * 
     * @private
     */
    onRecordLoad: function() {
        // interrupt process flow till dialog is rendered
        if (! this.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }
        
        // generalized keybord map for edit dlgs
        // TODO this is working in google chrome / check firefox
        // TODO FF: check why onRender() (from Tine.widgets.dialog.EditDialog) is not called in this dialog
        /*
        var map = new Ext.KeyMap(this.el, [
            {
                key: [10,13], // enter + return
                ctrl: true,
                fn: this.onSaveAndClose,
                scope: this
            }
        ]);
        */
        
        var title = this.app.i18n._('Compose email:');
        if (this.record.get('subject')) {
            title = title + ' ' + this.record.get('subject');
        }
        this.window.setTitle(title);
        
        if (this.record.id && this.record.get('content_type') && this.record.get('content_type').match(/text\/plain/)) {
            this.record.data.body = Ext.util.Format.nl2br(this.record.data.body);
        }
        
        this.getForm().loadRecord(this.record);
        
        this.loadMask.hide();
    },
        
    /**
     * executed when record gets updated from form
     * - add attachments to record here
     * 
     * @private
     * 
     * TODO add recipients here as well?
     */
    onRecordUpdate: function() {

        this.record.data.attachments = [];
        this.attachmentGrid.store.each(function(record) {
            this.record.data.attachments.push(record.data);
        }, this);
        
        Tine.Felamimail.MessageEditDialog.superclass.onRecordUpdate.call(this);

        /*
        if (this.record.data.note) {
            // show message box with note editing textfield
            //console.log(this.record.data.note);
            Ext.Msg.prompt(
                this.app.i18n._('Add Note'),
                this.app.i18n._('Edit Email Note Text:'), 
                function(btn, text) {
                    if (btn == 'ok'){
                        record.data.note = text;
                        // TODO set email note on contact
                    }
                }, 
                this,
                100, // height of input area
                this.record.data.body 
            );
        }
        */
    },
    
    /**
     * show error if request fails
     * 
     * @param {} response
     * @param {} request
     * @private
     * 
     * TODO mark field(s) invalid if for example email is incorrect
     * TODO add exception dialog on critical errors?
     */
    onRequestFailed: function(response, request) {
        Ext.MessageBox.alert(
            this.app.i18n._('Failed'), 
            String.format(this.app.i18n._('Could not send {0}.'), this.i18nRecordName) 
                + ' ( ' + this.app.i18n._('Error:') + ' ' + response.message + ')'
        ); 
        this.loadMask.hide();
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     * 
     * @return {Object}
     * @private
     * 
     * TODO get css definitions from extern stylesheet?
     */
    getFormItems: function() {
        
        this.recipientGrid = new Tine.Felamimail.RecipientGrid({
            fieldLabel: this.app.i18n._('Recipients'),
            record: this.record,
            i18n: this.app.i18n,
            hideLabel: true,
            anchor: '100% 90%'
        });
        
        this.attachmentGrid = new Tine.widgets.grid.FileUploadGrid({
            fieldLabel: this.app.i18n._('Attachments'),
            record: this.record,
            hideLabel: true,
            filesProperty: 'attachments',
            anchor: '100% 80%'
        });
        
        this.htmlEditor = new Ext.form.HtmlEditor({
            fieldLabel: this.app.i18n._('Body'),
            name: 'body',
            allowBlank: true,
            anchor: '100% 100%',
            getDocMarkup: function(){
                var markup = '<html>'
                    + '<head>'
                    + '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">'
                    + '<title></title>'
                    + '<style type="text/css">'
                        + '.felamimail-body-blockquote {'
                            + 'margin: 5px 10px 0 3px;'
                            + 'padding-left: 10px;'
                            + 'border-left: 2px solid #000088;'
                        + '} '
                        /*
                        + '.felamimail-body-signature {'
                            + 'font-size: 9px;'
                            + 'color: #888888;'
                        + '} '
                        */
                    + '</style>'
                    + '</head>'
                    + '<body>'
                    + '</body></html>';
        
                return markup;
            },
            plugins: [
            // TODO which plugins to activate?
                //new Ext.ux.form.HtmlEditor.Word(),  
                //new Ext.ux.form.HtmlEditor.Divider(),  
                //new Ext.ux.form.HtmlEditor.Table(),  
                //new Ext.ux.form.HtmlEditor.HR(),
                new Ext.ux.form.HtmlEditor.IndentOutdent(),  
                //new Ext.ux.form.HtmlEditor.SubSuperScript(),  
                new Ext.ux.form.HtmlEditor.RemoveFormat()
            ]
        });
        
        var accountStore = Tine.Felamimail.loadAccountStore();
        
        return {
            autoScroll: true,
            border: false,
            frame: true,
            layout: 'border',
            items: [{
                region: 'north',
                height: 160,
                resizable: true,
                layout: 'border',
                split: true,
                collapseMode: 'mini',
                header: false,
                collapsible: true,
                items: [{
                    region: 'north',
                    height: 40,
                    layout: 'form',
                    labelAlign: 'top',
                    items: [{
                        xtype:'combo',
                        name: 'from',
                        fieldLabel: this.app.i18n._('From'),
                        displayField: 'name',
                        valueField: 'id',
                        editable: false,
                        triggerAction: 'all',
                        anchor: '100%',
                        store: accountStore,
                        listeners: {
                            scope: this,
                            'change': function(combo, newValue, oldValue) {
                                // get new signature
                                var newSignature = Tine.Felamimail.getSignature(newValue);
                                var signatureRegexp = new RegExp('<br><br><span class="felamimail\-body\-signature">\-\-<br>.*$');
                                
                                // update signature
                                var bodyContent = this.htmlEditor.getValue();
                                bodyContent = bodyContent.replace(signatureRegexp, newSignature);
                                this.htmlEditor.setValue(bodyContent);
                            }
                        }
                    }]
                }, {
                    region: 'center',
                    layout: 'form',
                    items: [this.recipientGrid]
                }, {
                    region: 'south',
                    layout: 'form',
                    height: 40,
                    labelAlign: 'top',
                    items: [{
                        xtype:'textfield',
                        fieldLabel: this.app.i18n._('Subject'),
                        name: 'subject',
                        allowBlank: false,
                        enableKeyEvents: true,
                        anchor: '100%',
                        listeners: {
                            scope: this,
                            // update title on keyup event
                            'keyup': function(field, e) {
                                if (! e.isSpecialKey()) {
                                    this.window.setTitle(
                                        this.app.i18n._('Compose email:') + ' ' 
                                        + field.getValue()
                                    );
                                }
                            }
                        }
                    }]
                }]
            }, {
                region: 'center',
                layout: 'form',
                labelAlign: 'top',
                items: [this.htmlEditor]
            }, {
                region: 'south',
                layout: 'form',
                height: 130,
                split: true,
                collapseMode: 'mini',
                header: false,
                collapsible: true,
                items: [
                    this.attachmentGrid, 
                {
                    boxLabel: this.app.i18n._('Save contact note'),
                    name: 'note',
                    hideLabel: true,
                    xtype: 'checkbox'
                }]
            }]
        };
    },

    /**
     * is form valid (checks if attachments are still uploading)
     * 
     * @return {Boolean}
     * 
     * TODO check if recipient grid has more than 0 records 
     * TODO show better fitting error message if still uploading attachments
     */
    isValid: function() {
        var result = (! this.attachmentGrid.isUploading());
        
        return (result && Tine.Felamimail.MessageEditDialog.superclass.isValid.call(this));
    }
        
});

/**
 * Felamimail Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Felamimail.MessageEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 700,
        name: Tine.Felamimail.MessageEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Felamimail.MessageEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
