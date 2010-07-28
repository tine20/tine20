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
 * @version     $Id$
 * 
 * @param       {Object} config
 * @constructor
 * Create a new MessageEditDialog
 */
 Tine.Felamimail.MessageEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    /**
     * @cfg {Array/String} bcc
     * initial config for bcc
     */
    bcc: null,
    
    /**
     * @cfg {String} body
     */
    msgBody: '',
    
    /**
     * @cfg {Array/String} cc
     * initial config for cc
     */
    cc: null,
    
    /**
     * @cfg {Array} of Tine.Felamimail.Model.Message (optionally encoded)
     * messages to forward
     */
    forwardMsgs: null,
    
    /**
     * @cfg {String} from account_id
     * the accout id this message is send from
     */
    from: null,
    
    /**
     * @cfg {Tine.Felamimail.Model.Message} (optionally encoded)
     * message to reply to
     */
    replyTo: null,
    
    /**
     * @cfg {Boolean} (defaults to false)
     */
    replyToAll: false,
    
    /**
     * @cfg {String} subject
     */
    subject: '',
    
    /**
     * @cfg {Array/String} to
     * initial config for to
     */
    to: null,
    
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
     * @private
     */
    initRecord: function() {
        this.decodeMsgs();
        
        if (! this.record) {
            this.record = new this.recordClass(Tine.Felamimail.Model.Message.getDefaultData(), 0);
        }
        
        this.initFrom();
        this.initRecipients();
        this.initSubject();
        //this.initAttachements();
        this.initBody();
        
        // legacy handling:...
        if (this.replyTo) {
            this.record.set('flags', '\\Answered');
            this.record.set('original_id', this.replyTo.id);
        } else if (this.forwardMsgs) {
            this.record.set('flags', 'Passed');
            this.record.set('original_id', this.forwardMsgs[0].id);
        }
        
    },
    
    initAttachements: function() {
        
    },
    
    /**
     * inits body from reply/forward/template
     * 
     * @param {Tine.Felamimail.Model.Message} [message] optional self callback when body needs to be fetched
     */
    initBody: function(message) {
        if (! this.record.get('body')) {
            if (! this.msgBody) {
                message = this.replyTo ? this.replyTo : 
                          this.forwardMsgs && this.forwardMsgs.length === 1 ? this.forwardMsgs[0] :
                          null;
                          
                if (message) {
                    if (! message.bodyIsFetched()) {
                        return this.recordProxy.fetchBody(message, this.initBody.createDelegate(this, [message]));
                    }
                    
                    this.msgBody = message.get('body');
                    
                    if (Tine.Felamimail.loadAccountStore().getById(this.record.get('from')).get('display_format') == 'plain') {
                        this.msgBody = Ext.util.Format.nl2br(this.msgBody);
                    }
                    
                    if (this.replyTo) {
                        this.msgBody = '<br/>' + Ext.util.Format.htmlEncode(this.replyTo.get('from')) + ' ' + this.app.i18n._('wrote') + ':<br/>'
                             + '<blockquote class="felamimail-body-blockquote">' + this.msgBody + '</blockquote><br/>';
                    } else if (this.forwardMsgs && this.forwardMsgs.length === 1) {
                        this.msgBody = '<br/>-----' + this.app.i18n._('Original message') + '-----<br/>'
                            + Tine.Felamimail.GridPanel.prototype.formatHeaders(this.forwardMsgs[0].get('headers'), false, true) + '<br/><br/>'
                            + this.msgBody + '<br/>';
                    }
                                
                }
            }
        
            this.record.set('body', this.msgBody + Tine.Felamimail.getSignature(this.record.get('from')));
        }
        
        delete this.msgBody;
        this.onRecordLoad();
    },
    
    /**
     * inits / sets sender of message
     */
    initFrom: function() {
        if (! this.record.get('from')) {
            if (! this.from) {
                var mainApp = Ext.ux.PopupWindowMgr.getMainWindow().Tine.Tinebase.appMgr.get('Felamimail'),
                    folderId = this.replyTo ? this.replyTo.get('folder_id') : 
                               this.forwardMsgs ? this.forwardMsgs[0].get('folder_id') : null,
                    folder = folderId ? mainApp.getFolderStore().getById(folderId) : null
                    accountId = folder ? folder.get('account_id') : null;
                    
                this.from = accountId || mainApp.getActiveAccount().id;
            }
            
            this.record.set('from', this.from);
        }
        delete this.from;
    },
    
    /**
     * inits to/cc/bcc
     */
    initRecipients: function() {
        if (this.replyTo) {
            var replyTo = this.replyTo.get('headers')['reply-to'];
            
            this.to = [replyTo ? replyTo : this.replyTo.get('headers')['from']];
                
            if (this.replyToAll) {
                this.to = this.to.concat(this.replyTo.get('to'));
                this.cc = this.replyTo.get('cc');
            } else {
                
            }
        }
        
        Ext.each(['to', 'cc', 'bcc'], function(field) {
            if (! this.record.get(field)) {
                this[field] = Ext.isArray(this[field]) ? this[field] : Ext.isString(this[field]) ? [this[field]] : [];
                this.record.set(field, this[field]);
            }
            delete this[field];
        }, this);
    },
    
    /**
     * sets / inits subject
     */
    initSubject: function() {
        if (! this.record.get('subject')) {
            if (! this.subject) {
                if (this.replyTo) {
                    this.subject = this.app.i18n._('Re:') + ' ' +  this.replyTo.get('subject');
                } else if (this.forwardMsgs) {
                    this.subject =  this.app.i18n._('Fwd:') + ' ';
                    this.subject += this.forwardMsgs.length === 1 ?
                        this.forwardMsgs[0].get('subject') :
                        String.format(this.app.i18n._('{0} Message', '{0} Messages', this.forwardMsgs.length));
                }
            }
            this.record.set('subject', this.subject);
        }
        
        delete this.subject;
    },
    
    /**
     * decode this.replyTo / this.forwardMsgs from interwindow json transport
     */
    decodeMsgs: function() {
        if (Ext.isString(this.record)) {
            this.recordClass = new this.recordClass(Ext.decode(this.record));
        }
        
        if (Ext.isString(this.replyTo)) {
            this.replyTo = new this.recordClass(Ext.decode(this.replyTo));
        }
        
        if (Ext.isString(this.forwardMsgs)) {
            var msgs = [];
            Ext.each(Ext.decode(this.forwardMsgs), function(msg) {
                msgs.push(new this.recordClass(msg));
            }, this);
            
            this.forwardMsgs = msgs;
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
        
        var title = this.app.i18n._('Compose email:');
        if (this.record.get('subject')) {
            title = title + ' ' + this.record.get('subject');
        }
        this.window.setTitle(title);
        
        this.getForm().loadRecord(this.record);
        
        this.loadMask.hide();
    },
        
    /**
     * executed when record gets updated from form
     * - add attachments to record here
     * 
     * @private
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
     * if 'from' is changed we need to update the signature
     * 
     * @param {} combo
     * @param {} newValue
     * @param {} oldValue
     * 
     * TODO improve that by checking first if value/ account id changed
     */
     onFromSelect: function(combo, record, index) {
        // get new signature
        var newSignature = Tine.Felamimail.getSignature(record.id);
        var signatureRegexp = new RegExp('<br><br><span id="felamimail\-body\-signature">\-\-<br>.*</span>');
        
        // update signature
        var bodyContent = this.htmlEditor.getValue();
        bodyContent = bodyContent.replace(signatureRegexp, newSignature);
        
        this.htmlEditor.setValue(bodyContent);
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     * 
     * @return {Object}
     * @private
     * 
     * TODO get css definitions from external stylesheet?
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
                            select: this.onFromSelect
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
                        //allowBlank: false,
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
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 700,
        name: Tine.Felamimail.MessageEditDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Felamimail.MessageEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
