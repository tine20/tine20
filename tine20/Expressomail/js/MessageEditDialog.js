/*
 * Tine 2.0
 *
 * @package     Expressomail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.namespace('Tine.Expressomail');

/**
 * @namespace   Tine.Expressomail
 * @class       Tine.Expressomail.MessageEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 *
 * <p>Message Compose Dialog</p>
 * <p>This dialog is for composing emails with recipients, body and attachments.
 * you can choose from which account you want to send the mail.</p>
 * <p>
 * TODO         make email note editable
 * </p>
 *
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 *
 * @param       {Object} config
 * @constructor
 * Create a new MessageEditDialog
 */
Tine.Expressomail.MessageEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
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
     * @cfg {Array} of Tine.Expressomail.Model.Message (optionally encoded)
     * messages to forward
     */
    forwardMsgs: null,

    /**
     * @cfg {String} accountId
     * the accout id this message is sent from
     */
    accountId: null,

    /**
     * @cfg {Tine.Expressomail.Model.Message} (optionally encoded)
     * message to reply to
     */
    replyTo: null,

    /**
     * @cfg {Tine.Expressomail.Model.Message} (optionally encoded)
     * message to use as draft/template
     */
    draftOrTemplate: null,

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
     * validation error message
     * @type String
     */
    validationErrorMessage: '',

    /**
     * array with e-mail-addresses used as recipients
     * @type {Array}
     */
    mailAddresses: null,
    /**
     * json-encoded selection filter
     * @type {String} selectionFilter
     */
    selectionFilter: null,
    
    /**
     * holds default values for the record
     * @type {Object}
     */
    recordDefaults: null,

    /**
     * dynamic contacts checking message mask
     * @type {Object}
     */
    contactsCheckMask: null,
    
    /*** 
     *  Certificate store
     */
    certificatesStore: null,
    
    recipientsCertificates:  [],
    
    recipientsWithoutCertificates: [],
    /**
     * dialog is currently sending email
     * @type Boolean
     */
    sending: false,
    
    /**
     * @private
     */
    windowNamePrefix: 'MessageEditWindow_',
    appName: 'Expressomail',
    recordClass: Tine.Expressomail.Model.Message,
    recordProxy: Tine.Expressomail.messageBackend,
    loadRecord: false,
    evalGrants: false,

    mailStoreData: null,
    bodyStyle:'padding:0px',
    encrypted: false,
    eid: null,
    windowTitle: 'Compose email:',

    /**
     * overwrite update toolbars function (we don't have record grants)
     * @private
     */
    updateToolbars: Ext.emptyFn,
    editCloseCallback: Ext.emptyFn,
    keyEscrowCertificates: [],
    saveDraftsInterval: 0,
    isDraftFolderSelected: false,
    notifyClear: 0,
    notifySaveDraftOperation: 1,
    notifySaveDraftFailure: 2,
    isBodyLoaded: false,
    unloading: false,
    autoSaveDraftsEnabled: false,

    //private
    initComponent: function() {
        this.eid = this.rndEid();
        this.addEvents(
            /**
            * @event addcontact
            * @desc  Fired when contact is added
            * @param {Json String} data data of the contact
            */
            'addcontact'
        );
        Tine.Expressomail.MessageEditDialog.superclass.initComponent.call(this);
        // Allows new contacts to be available for immediate use (i.e. added in the cache)
        // except for IE<=8 that will be added when the cache is reloaded
        if(!Ext.isIE){
            this.on('addcontact', this.editCloseCallback);
        }
        this.on('save', this.onSave, this);
        this.on('show', this.onShow, this);
        if (!this.encrypted) {
            // will call the onMessageEditWindowDestroy on unload event even if autosave is disabled (interval=0)
            if (Tine.Tinebase.registry.get('preferences').get('windowtype')==='Browser') {
                this.window.popup.addEventListener('unload', this.window.ref.getMainScreen().getCenterPanel().onMessageEditWindowDestroy.createDelegate(this));
            }
            else {
                this.on('removed', this.window.ref.getMainScreen().getCenterPanel().onMessageEditWindowDestroy, this);
            }
        }
        if (Tine.Expressomail.registry.get("autoSaveDraftsInterval")) {
            // autosave is enabled (interval>0)
            this.saveDraftsInterval = Tine.Expressomail.registry.get("autoSaveDraftsInterval") * 1000; // convert s to ms
            this.autoSaveDraftsEnabled = true;
            this.window.ref.getMainScreen().getCenterPanel().autoSaveDraftsEnabled = true;
            this.saveDraftsIntervaledTask = new Ext.util.DelayedTask(this.checkDraftChanges, this);
            this.setSaveDraftsDelayedTask();
        } else {
            // autosave is enabled (interval=0)
            this.saveDraftsInterval = 0;
            this.autoSaveDraftsEnabled = false;
        }
    },

    /**
     * initialize required information for autosave
     */
    initDraft: function() {
        this.isDraftFolderSelected = false;
        try {
            var selNode = this.window.ref.getMainScreen().getWestPanel().TreePanel.getSelectionModel().getSelectedNode();
            if (selNode) {
                var account = this.app.getAccountStore().getById(this.record.get('account_id'));
                if (selNode.attributes.globalname === account.get('drafts_folder')) {
                    this.isDraftFolderSelected = true;
                    if (this.draftOrTemplate) {
                        this.record.set('draft_id', this.draftOrTemplate.get('id'));
                    }
                }
            }
        }
        catch (e) {
        }
    },

    /**
     * delay the save draft task
     *
     * @private
     */
    setSaveDraftsDelayedTask: function() {
        if (this.saveDraftsInterval>0) {
            this.saveDraftsIntervaledTask.delay(this.saveDraftsInterval);
        }
    },

    /**
     * remove draft from grid panel
     * @param {String} id
     *
     * @private
     */
    removeDraftFromGridPanel: function(id) {
        if (id && this.isDraftFolderSelected) {
            var grid = this.window.ref.getMainScreen().getCenterPanel();
            if (Ext.isObject(id)) {
                id = id.id;
            }
            var msg = grid.getStore().getById(id);
            grid.getStore().remove(msg);
        }
    },

    /**
     * set autosave message on title
     * @param {Integer} notify
     *
     * @private
     */
    setMessageOnTitle: function(notify) {
        if (this.saveDraftsInterval>0) {
            var msg;
            var notify_type = '';
            switch (notify) {
                case this.notifySaveDraftOperation:
                    msg = this.app.i18n._('Saving as a draft...');
                    break;
                case this.notifySaveDraftFailure:
                    msg = this.app.i18n._('Failed to save draft!');
                    notify_type = 'error';
                    break;
                case this.notifyClear:
                default:
                    msg = '';
            }
            if (Tine.Tinebase.registry.get('preferences').get('windowtype')==='Browser') {
                var title = this.app.i18n._(this.windowTitle) + msg;
                if (this.record.get('subject')) {
                    title = title + ' ' + this.record.get('subject');
                }
                this.window.setTitle(title);
            }
            else {
                // use notification tool
                this.window.setNotify(msg, notify_type);
            }
        }
    },

    /**
     * check changes to save draft in autosave
     *
     * @private
     */
    checkDraftChanges: function() {
        if (this.autoSaveDraftsEnabled) {
            this.saveDraftsIntervaledTask.cancel();
        }

        this.onRecordUpdate();

        var hasRecipient = new Array().concat(this.returnArrayContacts('to')).concat(this.returnArrayContacts('cc')).concat(this.returnArrayContacts('bcc')).length>0;
        var hasSubject = this.record.get('subject').length>0;
        var hasContent = this.htmlEditor.getEditorBody().innerHTML.replace(/\u200B/g,"").length>0;
        var hasAttachment = (this.record.get('attachments'))||false;
        var modified = this.record.modified;
        var wasModified = (modified && (modified.to!==undefined || modified.cc!==undefined || modified.cco!==undefined || modified.subject!==undefined || modified.body!==undefined || modified.attachments!==undefined));

        if (!this.sending && !this.attachmentGrid.isUploading() && wasModified && (hasRecipient || hasSubject || hasContent || hasAttachment)) {
            if (this.isDraftFolderSelected) {
                this.window.ref.getMainScreen().getCenterPanel().markRowOutdated(this.record.get('draft_id'));
            }
            this.callSaveDraft();
        }
        else {
            this.setSaveDraftsDelayedTask();
        }
    },

    /**
     * call save draft in gridpanel for autosave
     *
     * @private
     */
    callSaveDraft: function() {
        var account = Tine.Tinebase.appMgr.get('Expressomail').getAccountStore().getById(this.record.get('account_id')),
            folderName = account.get('drafts_folder');
        this.saving = true; // this will reset at GridPanel.callSaveDraft callbacks (success, failure)
        this.setMessageOnTitle(this.notifySaveDraftOperation);
        try {
            // draft saving must be executed in main window grid panel due to IE issues in browser window mode
            this.window.ref.getMainScreen().getCenterPanel().callSaveDraft(this.record, folderName, this);
        }
        catch(e) {
            this.setMessageOnTitle(this.notifySaveDraftFailure);
        }
    },

    /**
     * init buttons
     */
    initButtons: function() {
        this.fbar = [];

        this.action_send = new Ext.Action({
            text: this.app.i18n._('Send'),
            handler: this.encrypted ? this.onSaveAndClose : this.checkMessageSize,
            iconCls: 'ExpressomailIconCls',
            disabled: false,
            scope: this
        });

        this.action_searchContacts = new Ext.Action({
            text: this.app.i18n._('Search Recipients'),
            handler: this.onSearchContacts,
            iconCls: 'AddressbookIconCls',
            disabled: false,
            scope: this
        });

        this.action_saveAsDraft = new Ext.Action({
            text: this.app.i18n._('Save As Draft'),
            handler: this.onSaveInFolder.createDelegate(this, ['drafts_folder']),
            iconCls: 'action_saveAsDraft',
            disabled: (this.encrypted),
            scope: this
        });

        this.action_saveAsTemplate = new Ext.Action({
            text: this.app.i18n._('Save As Template'),
            handler: this.onSaveInFolder.createDelegate(this, ['templates_folder']),
            iconCls: 'action_saveAsTemplate',
            disabled: (this.encrypted),
            scope: this
        });

        // TODO think about changing icon onToggle
        this.action_saveEmailNote = new Ext.Action({
            text: this.app.i18n._('Save Email Note'),
            handler: this.onToggleSaveNote,
            iconCls: 'notes_noteIcon',
            disabled: (this.encrypted),
            scope: this,
            enableToggle: true
        });
        this.button_saveEmailNote = Ext.apply(new Ext.Button(this.action_saveEmailNote), {
            tooltip: this.app.i18n._('Activate this toggle button to save the email text as a note attached to the recipient(s) contact(s).')
        });

        this.action_toggleReadingConfirmation = new Ext.Action({
            text: this.app.i18n._('Reading Confirmation'),
            handler: this.onToggleReadingConfirmation,
            iconCls: 'expressomail-action-reading-confirmation',
            disabled: false,
            scope: this,
            enableToggle: true
        });
        this.button_toggleReadingConfirmation = Ext.apply(new Ext.Button(this.action_toggleReadingConfirmation), {
            tooltip: this.app.i18n._('Activate this toggle button to receive a reading confirmation.')
        });
        this.action_toggleMarkAsImportant = new Ext.Action({
            text: this.app.i18n._('Mark as Important'),
            handler: this.onToggleMarkAsImportant,
            iconCls: 'emblems_emblemImportant',
            disabled: false,
            scope: this,
            enableToggle: true
        });
        this.button_toggleMarkAsImportant = Ext.apply(new Ext.Button(this.action_toggleMarkAsImportant), {
            tooltip: this.app.i18n._('Activate this toggle button to mark this message as important.')
        });

        this.action_toggleSendAsPlain = new Ext.Action({
            text: this.app.i18n._('Send As Plain'),
            handler: this.onToggleSendAsPlain,
            iconCls: 'notes_noteIcon',
            disabled: false,
            scope: this,
            enableToggle: true
        });
        this.button_toggleSendAsPlain = Ext.apply(new Ext.Button(this.action_toggleSendAsPlain), {
            tooltip: this.app.i18n._('Activate this toggle button to send the message as text/plain')
        });
        
        this.action_toggleSignMail = new Ext.Action({
            text: this.app.i18n._('Digitally Sign Mail'),
            handler: this.onToggleSignMail,
            iconCls: 'notes_noteIcon', // TODO: change Icon
            disabled: false,
            scope: this,
            enableToggle: true,
            pressed: Tine.Expressomail.registry.get('preferences').get('alwaysSign') == "1" ? true : false
        });
        this.button_toggleSignMail = Ext.apply(new Ext.Button(this.action_toggleSignMail), {
            tooltip: this.app.i18n._('Activate this toggle button to sign a message on send')
        });

        this.action_toggleAddUnknownContacts = new Ext.Action({
            text: this.app.i18n._('Add Contacts'),
            handler: this.onToggleAddContacts,
            iconCls: 'expressomail-action-add-unknown-contacts',
            disabled: false,
            scope: this,
            pressed: true,
            enableToggle: true
        });
        this.button_toggleAddUnknownContacts = Ext.apply(new Ext.Button(this.action_toggleAddUnknownContacts), {
            tooltip: this.app.i18n._('Activate this toggle button to add unknown recipient to contacts.')
        });
        
        this.tbar = new Ext.Toolbar({
            defaults: {height: 55},
            items: [{
                xtype: 'buttongroup',
                columns: 7,
                items: [
                    Ext.apply(new Ext.Button(this.action_send), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top'
                    }),
                    Ext.apply(new Ext.Button(this.action_searchContacts), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top',
                        tooltip: this.app.i18n._('Click to search for and add recipients from the Addressbook.')
                    }),
                    this.action_saveAsDraft,
                    this.button_saveEmailNote,
                    this.action_saveAsTemplate,
                    this.button_toggleAddUnknownContacts,
                    this.button_toggleReadingConfirmation,
                    this.button_toggleSendAsPlain,
                    this.button_toggleMarkAsImportant,
                    this.button_toggleSignMail
                ]
            }]
        });
    },
    
    checkMessageSize: function() {
        try {
            this.contactsCheckMask = new Ext.LoadMask(this.ownerCt.body, {msg: this.app.i18n._('Calculating message size...')});
        }
        catch (e) {
            this.contactsCheckMask = new Ext.LoadMask(Ext.getBody(), {msg: this.app.i18n._('Calculating message size...')});
        }
        this.contactsCheckMask.show();
        
        var messageContent = new Tine.Expressomail.Model.MessageContent(
            Tine.Expressomail.Model.MessageContent.getDefaultData(), 0);
        
        messageContent.data.body = this.record.data.body ? this.record.data.body : this.htmlEditor.getValue();
        if (this.record.data.embedded_images) {
            var images = [];
            var image;
            for (var i=0; i<this.record.data.embedded_images.length; i++) {
                image = this.record.data.embedded_images[i];
                if (messageContent.data.body.indexOf(image.id) != -1) {
                    images.push({
                        id: image.id
                    });
                }
            }
            messageContent.data.embedded_images = images;
        }
        
        if (this.record.data.attachments) {
            messageContent.data.attachments = this.record.data.attachments;
        } else if (this.attachmentGrid) {
            var attachments = [];
            this.attachmentGrid.store.each(function(attachment) {
                attachments.push({
                    tempFile: Ext.ux.file.Upload.file.getFileData(attachment).tempFile
                });
            }, this);
            messageContent.data.attachments = attachments;
        }

        var that = this;
        
        Ext.Ajax.request({
            params: {
                method: 'Expressomail.calcMessageSize',
                recordData: messageContent
            },
            success: function (result) {
                var response = JSON.parse(result.responseText);
                
                if (response.maxMessageSize <= 0) {
                    // parameter maxMessageSize is not defined in config.inc.php
                    that.contactsCheckMask.hide();
                    that.onSaveAndClose();
                    return;
                }
                
                var totalSize =
                    response.textSize +
                    response.imageSize +
                    response.attachmentSize;
                var limitExceeded = totalSize > response.maxMessageSize;

                var maxMB = Ext.util.Format.number(response.maxMessageSize/(1024*1024), '0.00');
                var usedMB = Ext.util.Format.number(totalSize/(1024*1024), '0.00');

                that.contactsCheckMask.hide();

                if (limitExceeded) {
                    Ext.MessageBox.show({
                        title: that.app.i18n._('Message Size Limit Exceeded'),
                        msg: String.format(that.app.i18n._('Maximum allowed message size is {0} Mb.'), maxMB)
                                + '<br>' + 
                             String.format(that.app.i18n._('This message size is {0} Mb.'), usedMB),
                        buttons: Ext.MessageBox.OK,
                        icon: Ext.MessageBox.INFO
                    });
                } else {
                    that.onSaveAndClose();
                }
            },
            failure: function (err, details) {
                Ext.MessageBox.alert(that.app.i18n._('Failed'),
                    that.app.i18n._('Message size validation step failed.'));
                this.contactsCheckMask.hide();
            }
        });
    },
    
    /**
     * onSaveAndClose
     */
    onSaveAndClose: function() {
        this.fireEvent('saveAndClose');
        this.sending = true;
        //to clean temporary editor classes
        var editor_content = this.htmlEditor.getValue();
        editor_content = editor_content.replace(/editor-wysiwyg-[^'"]*/gi, "");
        this.htmlEditor.setValue(editor_content);

        this.onApplyChanges();
    },

    /**
     * closePendingMessages
     */
    closePendingMessages: function() {
        if (this.contactsCheckMask) {
            this.contactsCheckMask.hide();
        }
        this.fireEvent('close');
        this.loadMask.hide();
        this.window.close();
    },

    /**
     * returnArrayContacts
     */
    returnArrayContacts: function(field) {
        var fields = this.record.get(field);
        var ret = '';
        if (!(fields instanceof Array)){
            ret = new Array();
            Ext.iterate(fields, function(key, value) {
                ret.push(value);
            });
        }else {
            ret = fields;
        }
        return ret;
    },
    
    /**
     * Search certificates for mail recipients
     * 
     * @param operation String with type of operation SIGN | ENCRYPT | SIG_AND_ENCRYPT | DECRYPT
     * @param args other arguments
     * 
     */
    getRecipientsCertificates: function(operation, args) {
        
        var emailRecipients = new Array(),
            emails = new Array(),
            oper = operation,
            a = args,
            
            // getting logged in user's email
            account = this.app.getActiveAccount(),
            from = account.get('email');
            
        emailRecipients = emailRecipients.concat(this.returnArrayContacts('to'), this.returnArrayContacts('cc'), this.returnArrayContacts('bcc'));
        
        emails.push(from);
        var senderId = this.accountCombo.getValue(),
            senderAccount = this.accountCombo.getStore().getById(senderId),
            senderFrom = senderAccount.get('email');
            
        if (senderFrom !== from) {
            emails.push(senderFrom);
        }
        
        if (emailRecipients.length > 0) {
            var emailRegExp = /<([^>]*)>/;
            Ext.each(emailRecipients, function(email) {
                if (emailRegExp.exec(email)) {
                    if (RegExp.$1 != '') {
                        if (RegExp.$1 !== from) {
                            emails.push(RegExp.$1);
                        }
                    }
                }
                else {
                        if (email !== from){
                            emails.push(email);
                        }
                }
            }, this);
        }
        
        var filter = Tine.Addressbook.Model.EmailAddress.getFilterModel();
        filter.push({field: 'email', operator: 'in', value: emails});
        this.certificatesStore.baseParams.filter = filter;
        this.certificatesStore.load({
            callback: function() {
                // TODO: if notFound is not empty, ask user what to do. Cancel Sending? Remove Recipients?
                var notFound = new Array(); // If we haven't found certificate for e-mail
                
                Ext.each(emails, function(email) {
                    if (this.certificatesStore.find('email', email) == -1)
                    {
                        notFound.push(email);
                    }
                }, this);

                if(notFound.length > 0){
                    Ext.MessageBox.alert(_('Error'), this.app.i18n._("No valid certificate found for one or more of these email address.") 
                        + '\n' + notFound.join(',\n'), function(){
                        this.loadMask.hide();
                    },
                    this);
                } else {
                    var recipientscertificates =  new Array();
                    this.certificatesStore.each(function(record) {
                        recipientscertificates.push(record.get('certificate'));
                    }, this);

                    this.record.set('certificadosDestinatarios', recipientscertificates);
                    this.toSecurityApplet(oper, a);
                }
            },
            scope: this
        });
    },
    
    /**
     * getUnknownContactsFolderName
     */
    getUnknownContactsFolderName: function(){
        return this.app.i18n._('Unknown Contacts');
    },
    
    firstUpper: function(string)
    {
        var pieces = string.split(" ");
        for (var i=0; i < pieces.length; i++){
            pieces[i] = Ext.util.Format.capitalize(pieces[i]);
        }
        return pieces.join(" ");
    },
    
    relayToApplet: function(closeWindow) {
        
        if (this.record.get('sending_signed_mail') && this.encrypted){
            this.getRecipientsCertificates('SIGN_AND_ENCRYPT');
        } else if (this.record.get('sending_signed_mail') && !this.encrypted) {
            this.toSignatureApplet('SIGN');
        } else if (this.encrypted) {
            this.getRecipientsCertificates('ENCRYPT');
        } else {
            this.doApplyChanges(closeWindow);
        }
    },
    
    fromApplet: function(response, signatureData)
    {
        if (response) {
            // TODO: Change signedMessage to emlTosend
            this.record.set('signedMessage',response);
            this.doApplyChanges(true);
        } else {
            Tine.log.debug('Operation Canceled!');
            this.loadMask.hide();
        }
    
    },
 
    toSecurityApplet: function(operation)
    {
        if (Tine.Expressomail.registry.get('useKeyEscrow') && Ext.isEmpty(this.keyEscrowCertificates)) {
            // Get master digital certificate

            var store = new Tine.Tinebase.data.RecordStore({
                autoLoad: false,
                readOnly: true,
                proxy: Tine.Tinebase.DigitalCertificateBackend,
                recordClass: Tine.Tinebase.Model.DigitalCertificate
            });

            var failure = function(result) {
                var options = {
                        title: this.app.i18n._("Can't Send Encrypted Message"),
                        msg: this.app.i18n._('Reason:')
                                + '<br>'
                                + this.app.i18n._("The system's security policy blocked this operation.")
                                + '<br>'
                                + this.app.i18n._('Please, contact an administrator.'),
                        buttons: Ext.MessageBox.OK,
                        icon: Ext.MessageBox.WARNING
                    };

                Ext.MessageBox.show(options);
                this.loadMask.hide();
            }.createDelegate(this);
            store.proxy.getKeyEscrowCertificates({
                success: function(result) {
                    if (result.success === true && result.totalRecords > 0) {
                        var certs = new Array();
                        Ext.each(result.records, function(record) {
                            certs.push(record.data);
                        });
                        this.keyEscrowCertificates = certs;
                        this.record.set('keyEscrowCertificates', this.keyEscrowCertificates);
                        this.toSecurityApplet(operation);
                    } else {
                        failure(result);
                    }
                },
                failure: failure,
                scope: this
            });

            return;
        }

        this.onRecordUpdate();
        var data = Ext.util.JSON.encode(this.record.data);
        Tine.Expressomail.toSecurityApplet(this.id, data, operation);
    },
 
    /**
     * sending data to applet
     */
    toSignatureApplet: function(operation)
    {
        this.onRecordUpdate();
        var data = Ext.util.JSON.encode(this.record.data);
        try {
            document.getElementById('SignatureApplet').signMessage(Tine.Expressomail.fixIEUserAgent(), this.id, data);
        } catch (err) {
            Tine.log.debug('Tine.Expressomail.MessageEditDialog::toSignatureApplet() -> error:' + err.message);
        }
    },
            
    /**
     * @private
     */
    initRecord: function() {
        this.decodeMsgs();

        this.recordDefaults = Tine.Expressomail.Model.Message.getDefaultData();

        if (this.mailAddresses) {
            this.recordDefaults.to = Ext.decode(this.mailAddresses);
        } else if (this.selectionFilter) {
            this.on('load', this.fetchRecordsOnLoad, this);
        }

        if (! this.record) {
            this.record = new Tine.Expressomail.Model.Message(this.recordDefaults, 0);
        }
        this.initFrom();
        this.initRecipients();
        this.initSubject();
        this.initContent();
        this.initDraft();

        // legacy handling:...
        // TODO add this information to attachment(s) + flags and remove this
        if (this.replyTo) {
            this.record.set('flags', '\\Answered');
            this.record.set('original_id', this.replyTo.id);
        } else if (this.forwardMsgs) {
            this.record.set('flags', 'Passed');
            this.record.set('original_id', this.forwardMsgs[0].id);
        } else if (this.draftOrTemplate) {
            this.record.set('original_id', this.draftOrTemplate.id);
        }
        this.record.set('add_contacts', true);

        Tine.log.debug('Tine.Expressomail.MessageEditDialog::initRecord() -> record:');
        Tine.log.debug(this.record);
    },

    /**
     * show loadMask (loadRecord is false in this dialog)
     * @param {} ct
     * @param {} position
     */
    onRender : function(ct, position) {
        Tine.Expressomail.MessageEditDialog.superclass.onRender.call(this, ct, position);
        this.loadMask.show();
        
       this.recipientGrid.searchCombo.addListener('keyup',this.onF9,this);
       this.recipientGrid.searchCombo.addListener('afterrender',this.onSearchComboAfterRender,this);
    },
            
    onShow : function() {
        var addSecurityApplet = Tine.Expressomail.addSecurityApplet.createDelegate(this);
        if (this.encrypted) {
            this.windowTitle = this.app.i18n._('Compose email') + ' (' + this.app.i18n._('encrypted') + '):';
            addSecurityApplet('SecurityApplet');
            this.certificatesStore = new Tine.Tinebase.data.RecordStore({
                proxy: Tine.Addressbook.certificateBackend,
                recordClass: Tine.Addressbook.Model.Certificate
            });
        } else if (this.button_toggleSignMail.pressed) {
            this.record.set('sending_signed_mail', true);
            addSecurityApplet('SignatureApplet', this, 'east');
            if (this.isForwardedMessage()){
                this.getAttachmentsData();
            }
        }
    },

    onSearchComboAfterRender: function(){
         this.recipientGrid.searchCombo.action_searchuser = new Ext.Action({
            text: '<b>'+this.app.i18n._('Click here to more results.')+'</b>',
            handler: this.onSearchUsers,
            iconCls: 'AddressbookIconCls',
            scope: this
        });    

        
        this.recipientGrid.searchCombo.footer = this.recipientGrid.searchCombo.list.createChild();
        this.recipientGrid.searchCombo.tbar = new Ext.Toolbar({
            //defaults: {height: 55},
            items: [{
                xtype: 'buttongroup',
                items: [
                     Ext.apply(new Ext.Button(this.recipientGrid.searchCombo.action_searchuser), {
                         xtype: 'tbfill'
                    })
                ]}
             ],
             buttonAlign: 'center',
             columns: 1,
             renderTo: this.recipientGrid.searchCombo.footer
        });
       this.recipientGrid.searchCombo.assetHeight += this.recipientGrid.searchCombo.footer.getHeight();
    },
            
    onSearchUsers: function(){
         this.recipientGrid.searchCombo.fireEvent('blur', this.recipientGrid.searchCombo);
         this.onSearchContacts();

    },
    
    onDownloadAttachmentSuccess: function(response) {
        try {
            Tine.log.info('onDownloadAttachmentSuccess: DownloadSuccess');
            var responseObj = Ext.util.JSON.decode(response);
        } catch(e) {
            return this.onDownloadAttachmentFailed(e);
        }

        Ext.each(['attachments', 'embedded_images'], function(key){
            var position = Ext.each(this.record.get(key), function(item) {
                if (item.id == responseObj.id && item.partId == responseObj.partId) {
                    return false;
                }
            }, this);

            if (typeof(position) != 'undefined') {
                var array = this.record.get(key);
                var attach = array[position];
                Ext.copyTo(attach, responseObj, ['encoding', 'fileData']);
                if (attach.cid === null) delete attach.cid;
                array[position] = attach;
                this.record.set(key, array);
            }
        }, this);
    },

    onDownloadAttachmentFailed: function(response, attach) {
        Tine.log.err('onDownloadAttachmentFailed: Download Fail -> ' + response);
    },

    /**
    *  Get full attachments data, including content
    *  @param originalId String Original message's Id
    */
    getAttachmentsData: function(originalId) {

        Ext.each(['attachments', 'embedded_images'], function(key){
            Ext.each(this.record.get(key), function(attach) {
                // TODO: refactor this to get attachment from Frontend_Json
//                        Ext.Ajax.request({
//                            url: 'index.php',
//                            success: this.onDownloadAttachmentSuccess.createDelegate(this, [attach], true),
//                            failure: function(){alert('algum erro aconteceu')},
//                            params: {
//                                method: 'Expressomail.getAttachmentData',
//                                messageId: message.get('id'),
//                                partId: attach.partId,
//                                json: true
//                            }
//                        });
                if (Ext.isEmpty(attach.encoding) || Ext.isEmpty(attach.fileData)) {
                    var xhr = new XMLHttpRequest(); // Ok, we're forgetting that ie 5.5 and 6 existed

                    // TODO: review this code
                    var messageId = typeof originalId != 'undefined' ? originalId :
                        typeof this.record.get('id') != 'undefined' ? this.record.get('id') : this.record.get('original_id');
                    xhr.open('GET', 'index.php'
                        +'?method=Expressomail.downloadAttachment'
                        +'&messageId='+messageId
                        +'&partId='+attach.partId
                        +'&getAsJson=true', true);

                    var success = this.onDownloadAttachmentSuccess.createDelegate(this);
                    var failed = this.onDownloadAttachmentFailed.createDelegate(this);

                    xhr.onreadystatechange = function(e) {
                        if (this.readyState == 4) {
                            var httpStatus = this.status;
                            if ((httpStatus >= 200 && httpStatus < 300) || (Ext.isIE && httpStatus == 1223)) {
                                success(this.responseText);
                            } else {
                                failed(this.responseText);
                            }
                        }
                    };

                    xhr.send();
                }
            }, this);
        }, this);
    },
            

    /**
     * init attachments when forwarding message
     *
     * @param {Tine.Expressomail.Model.Message} message
     */
    initAttachements: function(message) {
        if (message.get('attachments').length > 0) {
            
            var Attachments = [];
            var embedded_images = [];
            for(i in message.get('attachments')){

                if(message.get('attachments')[i].size && message.get('attachments')[i]['content-type'] !== "application/pkcs7-signature"){

                    var attach = {
                        name: message.get('attachments')[i]['filename'],
                        type: message.get('attachments')[i]['content-type'],
                        size: message.get('attachments')[i]['size'],
                        partId:  message.get('attachments')[i]['partId'],
                        eid: message.get('attachments')[i]['eid'],
                        cid: message.get('attachments')[i]['cid'],
                        id: message.id
                    };
                    
                    if (message.get('attachments')[i].cid && message.get('attachments')[i]['content-type'].toLowerCase().indexOf('image') === 0) {
                        embedded_images.push(attach);
                    } else {
                        Attachments.push(attach);
                    }
                    
                }
            }
            this.record.set('attachments',Attachments);
            this.record.set('embedded_images', embedded_images);
        }
    },

    /**
     * inits body and attachments from reply/forward/template
     */
    initContent: function() {
        if (! this.record.get('body')) {
            var account = Tine.Tinebase.appMgr.get('Expressomail').getAccountStore().getById(this.record.get('account_id'));

            if (! this.msgBody) {
                var message = this.getMessageFromConfig();
                if (message) {
                    if (! message.bodyIsFetched()) {
                        // self callback when body needs to be fetched
                        return this.recordProxy.fetchBody(message, this.initContent.createDelegate(this));
                    }

                    this.setMessageBody(message, account);

                    if (this.isForwardedMessage() || this.draftOrTemplate) {
                        this.initAttachements(message);
                        if (this.encrypted && this.isDecrypted(message)) {
                            // message = message being forwarded
                            // At this point we don't have a complete message model in record
                            this.getAttachmentsData(message.get('id'));
                        }
                    }
                }
            }
            this.addSignature(account);

            this.record.set('body', this.msgBody);
        }

        delete this.msgBody;
        this.onRecordLoad();
    },

    /**
     * set message body: converts newlines, adds quotes
     *
     * @param {Tine.Expressomail.Model.Message} message
     * @param {Tine.Expressomail.Model.Account} account
     */
    setMessageBody: function(message, account) {
        this.msgBody = message.get('body');

        if (account.get('display_format') == 'plain' || (account.get('display_format') == 'content_type' && message.get('body_content_type') == 'text/plain')) {
            this.msgBody = Ext.util.Format.nl2br(this.msgBody);
        }

        if (this.replyTo) {
            this.setMessageBodyReply();
        } else if (this.isForwardedMessage()) {
            this.setMessageBodyForward();
        }
    },

    /**
     * set message body for reply message
     */
    setMessageBodyReply: function() {
        var date = (this.replyTo.get('sent'))
            ? this.replyTo.get('sent')
            : ((this.replyTo.get('received')) ? this.replyTo.get('received') : new Date());

       this.msgBody = '<br/><br/>'
          + String.format(this.app.i18n._('On {0}, {1} wrote'),
            Tine.Tinebase.common.dateTimeRenderer(date),
            Ext.util.Format.htmlEncode(this.replyTo.get('from_name'))
        ) + ':<br/>'
          + '<blockquote class="expressomail-body-blockquote">' + this.msgBody + '</blockquote><br/>';
    },

    /**
    *
    */
    isDecrypted: function(message) {
        return Ext.isEmpty(message.get('smimeEml')) ||
            typeof message.get('decrypted') == 'undefined' || message.get('decrypted');
    },

    /**
     * returns true if message is forwarded
     *
     * @return {Boolean}
     */
    isForwardedMessage: function() {
        return (this.forwardMsgs && this.forwardMsgs.length === 1);
    },

    /**
     * set message body for forwarded message
     */
    setMessageBodyForward: function() {
        this.msgBody = '<br/>-----' + this.app.i18n._('Original message') + '-----<br/>'
            + Tine.Expressomail.GridPanel.prototype.formatHeaders(this.forwardMsgs[0].get('headers'), false, true) + '<br/><br/>'
            + this.msgBody + '<br/>';
    },

    /**
     * add signature to message
     *
     * @param {Tine.Expressomail.Model.Account} account
     */
    addSignature: function(account) {
        if (this.draftOrTemplate || ! account) {
            return;
        }

        var signaturePosition = (account.get('signature_position')) ? account.get('signature_position') : 'below',
            signature = Tine.Expressomail.getSignature(this.record.get('account_id'));
        if (signaturePosition == 'below') {
            this.msgBody += signature;
        } else {
            this.msgBody = signature + '<br/><br/>' + this.msgBody;
        }
    },

    /**
     * inits / sets sender of message
     */
    initFrom: function() {
        if (! this.record.get('account_id')) {
            if (! this.accountId) {
                var message = this.getMessageFromConfig(),
                    folderId = message ? message.get('folder_id') : null,
                    folder = folderId ? Tine.Tinebase.appMgr.get('Expressomail').getFolderStore().getById(folderId) : null,
                    accountId = folder ? folder.get('account_id') : null;

                if (! accountId) {
                    var activeAccount = Tine.Tinebase.appMgr.get('Expressomail').getActiveAccount();
                    accountId = (activeAccount) ? activeAccount.id : null;
                }

                this.accountId = accountId;
            }

            this.record.set('account_id', this.accountId);
        }
        delete this.accountId;
    },

    /**
     * after render
     */
    afterRender: function() {
        Tine.Expressomail.MessageEditDialog.superclass.afterRender.apply(this, arguments);

        this.getEl().on(Ext.EventManager.useKeydown ? 'keydown' : 'keypress', this.onKeyPress, this);
        this.recipientGrid.on('specialkey', function(field, e) {
            this.onKeyPress(e);
        }, this);

        this.htmlEditor.on('keydown', function(e) {
            if (e.getKey() == e.ENTER && e.ctrlKey) {
                this.checkMessageSize();
            } else if (e.getKey() == e.TAB && e.shiftKey) {
                this.subjectField.focus.defer(50, this.subjectField);
            }
        }, this);

        if (!Ext.isIE) {
            this.initHtmlEditorDD();
        }
    },
    
    initHtmlEditorDD: function() {
        if (! this.htmlEditor.rendered) {
            return this.initHtmlEditorDD.defer(500, this);
        }

        var isFileDrag = function(e) {
            var fileDragging = false;
            if (Ext.isGecko) {
                fileDragging  = e.dataTransfer.types.contains('application/x-moz-file');
            }
            else {
                Ext.each(e.dataTransfer.items, function(item) {
                    if (item.kind == 'file') {
                        fileDragging = true;
                    }
                });
            }
            if (fileDragging) {
                return true;
            }
            return false;
        };

        this.htmlEditor.getDoc().addEventListener('dragover', function(e) {
            if (isFileDrag(e)) {
                this.action_addAttachment.plugins[0].onBrowseButtonClick();
            }
        }.createDelegate(this));

        this.htmlEditor.getDoc().addEventListener('drop', function(e) {
            if (isFileDrag(e)) {
                this.action_addAttachment.plugins[0].onDrop(Ext.EventObject.setEvent(e));
            }
        }.createDelegate(this));
    },

    onDestroy: function() {
        this.supr().onDestroy.apply(this, arguments);
        this.app.mainScreen.GridPanel.focusSelectedMessage();
    },

    /**
     * on key press
     * @param {} e
     * @param {} t
     * @param {} o
     */
    onKeyPress: function(e, t, o) {
        if ((e.getKey() == e.TAB || e.getKey() == e.ENTER) && ! e.shiftKey) {
            if (e.getTarget('input[name=subject]')) {
                this.htmlEditor.focus.defer(50, this.htmlEditor);
            } else if (e.getTarget('input[type=text]')) {
                this.subjectField.focus.defer(50, this.subjectField);
            }
        }
    },
            
    onF9:function(e, t, o) {
        
        if (t.keyCode == Ext.EventObject.F9) {
               this.recipientGrid.searchCombo.fireEvent('blur', this.recipientGrid.searchCombo);
               this.onSearchContacts();
        }
        
    },

    /**
     * returns message passed with config
     *
     * @return {Tine.Expressomail.Model.Message}
     */
    getMessageFromConfig: function() {
        return this.replyTo ? this.replyTo :
            this.forwardMsgs && this.forwardMsgs.length === 1 ? this.forwardMsgs[0] :
            this.draftOrTemplate ? this.draftOrTemplate : null;
    },

    /**
     * inits to/cc/bcc
     */
    initRecipients: function() {
        if (this.replyTo) {
            this.initReplyRecipients();
        }

        Ext.each(['to', 'cc', 'bcc'], function(field) {
            if (this.draftOrTemplate) {
                this[field] = this.draftOrTemplate.get(field);
            }

            if (! this.record.get(field)) {
                this[field] = Ext.isArray(this[field]) ? this[field] : Ext.isString(this[field]) ? [this[field]] : [];
                this.record.set(field, Ext.unique(this[field]));
            }
            delete this[field];

            this.resolveRecipientFilter(field);

        }, this);
    },

    /**
     * init recipients from reply/replyToAll information
     */
    initReplyRecipients: function() {
        var replyTo = this.replyTo.get('headers')['reply-to'];

        if (replyTo) {
            this.to = replyTo;
        } else {
            var toemail = '<' + this.replyTo.get('from_email') + '>';
            if (this.replyTo.get('from_name') && this.replyTo.get('from_name') != this.replyTo.get('from_email')) {
                this.to = this.replyTo.get('from_name') + ' ' + toemail;
            } else {
                this.to = toemail;
            }
        }

        if (this.replyToAll) {
            if (! Ext.isArray(this.to)) {
                this.to = [this.to];
            }
            this.to = this.to.concat(this.replyTo.get('to'));
            this.cc = this.replyTo.get('cc');

            // remove own email and all non-email strings/objects from to/cc
            var account = Tine.Tinebase.appMgr.get('Expressomail').getAccountStore().getById(this.record.get('account_id')),
                ownEmailRegexp = new RegExp(account.get('email'));
            Ext.each(['to', 'cc'], function(field) {
                if (this[field]) {
                    for (var i=0; i < this[field].length; i++) {
                        if (! Ext.isString(this[field][i]) || ! this[field][i].match(/@/) || ownEmailRegexp.test(this[field][i])) {
                            this[field].splice(i, 1);
                        }
                    }
                }
            }, this);
        }
    },

    /**
     * resolve recipient filter / queries addressbook
     *
     * @param {String} field to/cc/bcc
     */
    resolveRecipientFilter: function(field) {
        if (! Ext.isEmpty(this.record.get(field)) && Ext.isObject(this.record.get(field)[0]) &&  this.record.get(field)[0].operator) {
            // found a filter
            var filter = this.record.get(field);
            this.record.set(field, []);

            this['AddressLoadMask'] = new Ext.LoadMask(Ext.getBody(), {msg: this.app.i18n._('Loading Mail Addresses')});
            this['AddressLoadMask'].show();

            Tine.Addressbook.searchContacts(filter, null, function(response) {
                var mailAddresses = Tine.Expressomail.GridPanelHook.prototype.getMailAddresses(response.results);

                this.record.set(field, mailAddresses);
                this.recipientGrid.syncRecipientsToStore([field], this.record, true, false);
                this['AddressLoadMask'].hide();

            }.createDelegate(this));
        }
    },

    /**
     * sets / inits subject
     */
    initSubject: function() {
        if (! this.record.get('subject')) {
            if (! this.subject) {
                if (this.replyTo) {
                    this.setReplySubject();
                } else if (this.forwardMsgs) {
                    this.setForwardSubject();
                } else if (this.draftOrTemplate) {
                    this.subject = this.draftOrTemplate.get('subject');
                }
            }
            this.record.set('subject', this.subject);
        }

        delete this.subject;
    },

    /**
     * setReplySubject -> this.subject
     *
     * removes existing prefixes + just adds 'Re: '
     */
    setReplySubject: function() {
        var replyPrefix = 'Re: ',
            replySubject = (this.replyTo.get('subject')) ? this.replyTo.get('subject') : '',
            replySubject = replySubject.replace(/^((re|aw|antw|fwd|odp|sv|wg|tr):\s*)*/i, replyPrefix);

        this.subject = replySubject;
    },

    /**
     * setForwardSubject -> this.subject
     */
    setForwardSubject: function() {
        this.subject =  this.app.i18n._('Fwd:') + ' ';
        this.subject += this.forwardMsgs.length === 1 ?
            this.forwardMsgs[0].get('subject') :
            String.format(this.app.i18n._('{0} Message', '{0} Messages', this.forwardMsgs.length));
    },

    /**
     * decode this.replyTo / this.forwardMsgs from interwindow json transport
     */
    decodeMsgs: function() {
        if (Ext.isString(this.draftOrTemplate)) {
            this.draftOrTemplate = new this.recordClass(Ext.decode(this.draftOrTemplate));
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
     * fix input fields layout
     */
    fixLayout: function() {
        if (! this.subjectField.rendered || ! this.accountCombo.rendered || ! this.recipientGrid.rendered) {
            return;
        }

        var scrollWidth = this.recipientGrid.getView().getScrollOffset();
        this.subjectField.setWidth(this.subjectField.getWidth() - scrollWidth + 1);
        this.accountCombo.setWidth(this.accountCombo.getWidth() - scrollWidth + 1);
    },

    /**
     * save message in folder
     *
     * @param {String} folderField
     */
    onSaveInFolder: function (folderField) {
        this.onRecordUpdate();

        var account = Tine.Tinebase.appMgr.get('Expressomail').getAccountStore().getById(this.record.get('account_id')),
            folderName = account.get(folderField);

        if (! folderName || folderName == '') {
            Ext.MessageBox.alert(
                this.app.i18n._('Failed'),
                String.format(this.app.i18n._('{0} account setting empty.'), folderField)
            );
        } else if (this.isValid()) {
            this.loadMask.show();
            this.recordProxy.saveInFolder(this.record, folderName, {
                scope: this,
                success: function(record) {
                    this.fireEvent('update', Ext.util.JSON.encode(this.record.data));
                    this.purgeListeners();
                    this.loadMask.hide();
                },
                failure: this.onRequestFailed,
                timeout: 150000 // 3 minutes
            });
        } else {
            Ext.MessageBox.alert(_('Errors'), _('Please fix the errors noted.'));
        }
    },

    /**
     * toggle save note
     *
     * @param {} button
     * @param {} e
     */
    onToggleSaveNote: function (button, e) {
        this.record.set('note', (! this.record.get('note')));
    },

    /**
     * toggle add contacts
     *
     * @param {} button
     * @param {} e
     */
    onToggleAddContacts: function (button, e) {
        this.record.set('add_contacts', (! this.record.get('add_contacts')));
    },

       /**
     * toggle mark as Important Message
     */
    onToggleMarkAsImportant: function () {
        this.record.set('importance', (! this.record.get('importance')));
    },

    /**
     * toggle Request Reading Confirmation
     */
    onToggleReadingConfirmation: function () {
        this.record.set('reading_conf', (! this.record.get('reading_conf')));
    },
    onToggleSendAsPlain: function () {
        var sending_plain = (! this.record.get('sending_plain'));
        this.record.set('sending_plain', sending_plain);
        this.record.set('embedded_images', []);
        this.htmlEditor.disableItems(sending_plain);
        if (!Ext.isIE9) {
            this.htmlEditor.readOnly = sending_plain;
        }
    },
    
    onToggleSignMail: function () {
        this.record.set('sending_signed_mail', (! this.record.get('sending_signed_mail')));
        if (!this.encrypted) {
            Tine.Expressomail.addSecurityApplet('SignatureApplet', this, 'east');
            this.getAttachmentsData();
        }
    },
    
    /**
     * search for contacts as recipients
     */
    onSearchContacts: function() {
        Tine.Expressomail.RecipientPickerDialog.openWindow({
            query: this.recipientGrid.lastActiveEditor?this.recipientGrid.lastActiveEditor.field.lastQuery:(this.recipientGrid.activeEditor?this.recipientGrid.activeEditor.field.lastQuery:null),
            record: new this.recordClass(Ext.copyTo({}, this.record.data, ['subject', 'to', 'cc', 'bcc']), Ext.id()),
            listeners: {
                scope: this,
                'update': function(record) {
                    var messageWithRecipients = Ext.isString(record) ? new this.recordClass(Ext.decode(record)) : record;
                    this.recipientGrid.syncRecipientsToStore(['to', 'cc', 'bcc'], messageWithRecipients, true, true);
                }
            }
        });
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
        var title = this.app.i18n._(this.windowTitle);
        if (this.record.get('subject')) {
            title = title + ' ' + this.record.get('subject');
        }
        this.window.setTitle(title);

        this.getForm().loadRecord(this.record);
        
        if(!this.sending)
            this.attachmentGrid.loadRecord(this.record);

        if (this.record.get('note') && this.record.get('note') == '1') {
            this.button_saveEmailNote.toggle();
        }
        var ticketFn = this.onAfterRecordLoad.deferByTickets(this),
            wrapTicket = ticketFn();

        this.fireEvent('load', this, this.record, ticketFn);
        wrapTicket();
    },

    /**
     * overwrite, just hide the loadMask
     */
    onAfterRecordLoad: function() {
        // after all have been initialized, correct record.body to reflect field
        // dirty should not be true as record is being loaded with initial data
        if (this.htmlEditor.getEditorBody().innerHTML.replace(/\u200B/g,"").length>0 && !this.isBodyLoaded) {
            this.isBodyLoaded = true;
            this.record.set('body',this.htmlEditor.getEditorBody().innerHTML);
            this.record.commit(true);
        }
        if (this.loadMask) {
            this.loadMask.hide();
        }
    },

    /**
     * executed when record gets updated from form
     * - add attachments to record here
     * - add alias / from
     *
     * @private
     */
    onRecordUpdate: function() {

        if (this.record.get('sending_plain')){
            this.record.set('content_type','text/plain');
        }

        this.record.data.attachments = [];
        var attachmentData = null;

        this.attachmentGrid.store.each(function(attachment) {
            this.record.data.attachments.push(Ext.ux.file.Upload.file.getFileData(attachment));
        }, this);

        var accountId = this.accountCombo.getValue(),
            account = this.accountCombo.getStore().getById(accountId),
            emailFrom = account.get('email'),
            nameFrom = account.get('from'),
            senderAccount = account.get('senderAccount');
            
        this.record.set('from_email', emailFrom);
        this.record.set('from_name', nameFrom);
        this.record.set('sender_account', senderAccount);

        Tine.Expressomail.MessageEditDialog.superclass.onRecordUpdate.call(this);

        this.record.set('account_id', account.get('original_id'));

        // need to sync once again to make sure we have the correct recipients
        this.recipientGrid.syncRecipientsToRecord();
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
        this.sending = false;
        this.loadMask.hide();
    },

    /**
     * init attachment grid + add button to toolbar
     */
    initAttachmentGrid: function() {
        if (! this.attachmentGrid) {

            this.attachmentGrid = new Tine.Expressomail.FileUploadGrid({
                messageRecord: this.record,
                fieldLabel: this.app.i18n._('Attachments'),
                hideLabel: true,
                filesProperty: 'attachments',
                // TODO     think about that -> when we deactivate the top toolbar, we lose the dropzone for files!
                //showTopToolbar: false,
                anchor: '100% 95%',
                parent: this
            });
            
            // add file upload button to toolbar
            this.action_addAttachment = this.attachmentGrid.getAddAction();
            this.action_addAttachment.plugins[0].dropElSelector = 'div[id=' + this.id + ']';
            this.action_addAttachment.plugins[0].onBrowseButtonClick = function() {
                this.southPanel.expand();
            }.createDelegate(this);

            this.tbar.get(0).insert(1, Ext.apply(new Ext.Button(this.action_addAttachment), {
                scale: 'medium',
                rowspan: 2,
                iconAlign: 'top'
            }));
        }
    },
    
    /**
     * init account (from) combobox
     *
     * - need to create a new store with an account record for each alias
     */
    initAccountCombo: function() {
        var accountStore = Tine.Tinebase.appMgr.get('Expressomail').getAccountStore(),
            accountComboStore = new Ext.data.ArrayStore({
                fields   : Tine.Expressomail.Model.Account
            });

        var aliasAccount = null,
            aliases = null,
            id = null

        var otherAccounts = Tine.Expressomail.registry.get('extraSenderAccounts');

        accountStore.each(function(account) {
            aliases = [ account.get('email') ];

            if (account.get('type') == 'system') {
                // add identities / aliases to store (for systemaccounts)
                var user = Tine.Tinebase.registry.get('currentAccount');
                if (user.emailUser && user.emailUser.emailAliases && user.emailUser.emailAliases.length > 0) {
                    aliases = aliases.concat(user.emailUser.emailAliases);
                }
            }
            for(other in otherAccounts.results){
                if(otherAccounts.results[other].accountEmailAddress){
                    id = Ext.id();
                    var otherAccount = account.copy(id);
                    otherAccount.data.id = id;
                    otherAccount.set('email', otherAccounts.results[other].accountEmailAddress);
                    otherAccount.set('name', otherAccounts.results[other].accountFullName+ ' ('+otherAccounts.results[other].accountEmailAddress+')');
                    otherAccount.set('from', otherAccounts.results[other].accountFullName);
                    otherAccount.set('original_id', account.id);
                    otherAccount.set('senderAccount', otherAccounts.results[other].accountLoginName);
                    accountComboStore.add(otherAccount);
                }
            }

            for (var i = 0; i < aliases.length; i++) {
                id = (i == 0) ? account.id : Ext.id();
                aliasAccount = account.copy(id);
                if (i > 0) {
                    aliasAccount.data.id = id;
                    aliasAccount.set('email', aliases[i]);
                }
                aliasAccount.set('name', aliasAccount.get('name') + ' (' + aliases[i] +')');
                aliasAccount.set('original_id', account.id);
                accountComboStore.add(aliasAccount);
            }
        }, this);

        this.accountCombo = new Ext.form.ComboBox({
            name: 'account_id',
            ref: '../../accountCombo',
            plugins: [ Ext.ux.FieldLabeler ],
            fieldLabel: this.app.i18n._('From'),
            displayField: 'name',
            valueField: 'id',
            editable: false,
            triggerAction: 'all',
            store: accountComboStore,
            mode: 'local',
            //validator: this.encrypted ? this.populateCertificateStore : null,
            //invalidText: this.app.i18n._("No valid certificate found for one or more of these email address."),
            listeners: {
                scope: this,
                select: this.onFromSelect
                
            }
        });
    },

//    /**
//    *
//    */
//    onAfterAccountValidation: function(combo, msg) {
//        combo.validator = 
//    },

    /**
     * if 'account_id' is changed we need to update the signature
     *
     * @param {} combo
     * @param {} newValue
     * @param {} oldValue
     */
     onFromSelect: function(combo, record, index) {

        // get new signature
        var accountId = record.get('original_id');
        var newSignature = Tine.Expressomail.getSignature(accountId);
        var signatureRegexp = new RegExp('<br><br><span id="expressomail\-body\-signature">\-\-<br>.*</span>');

        // update signature
        var bodyContent = this.htmlEditor.getValue();
        bodyContent = bodyContent.replace(signatureRegexp, newSignature);

        this.htmlEditor.setValue(bodyContent);
    },

    /**
     * returns dialog
     *
     * NOTE: when this method gets called, all initialisation is done.
     *
     * @return {Object}
     * @private
     */
    getFormItems: function() {

        this.initAttachmentGrid();
        this.initAccountCombo();

        this.recipientGrid = new Tine.Expressomail.RecipientGrid({
            record: this.record,
            i18n: this.app.i18n,
            hideLabel: true,
            composeDlg: this,
            autoStartEditing: !this.AddressLoadMask
        });

        this.southPanel = new Ext.Panel({
            region: 'south',
            layout: 'form',
            height: 150,
            split: true,
            collapseMode: 'mini',
            header: false,
            collapsible: true,
            collapsed: (this.record.bodyIsFetched() && (! this.record.get('attachments') || this.record.get('attachments').length == 0)),
            items: [this.attachmentGrid]
        });

        this.htmlEditor = new Tine.Expressomail.ComposeEditor({
            fieldLabel: this.app.i18n._('Body'),
            flex: 1,
            messageEdit: this,
            listeners : {
                scope : this,
                initialize : function(editor) {
                    // after all have been initialized, correct record.body to reflect field
                    // dirty should not be true as record is being loaded with initial data
                    if (this.htmlEditor.getEditorBody().innerHTML.replace(/\u200B/g,"").length>0 && !this.isBodyLoaded) {
                        this.isBodyLoaded = true;
                        this.record.set('body',this.htmlEditor.getEditorBody().innerHTML);
                        this.record.commit(true);
                    }
                    if (this.replyTo) {
                        editor.focus();
                    }
                }
            }
        });
        
        return {
            border: false,
            frame: true,
            layout: 'border',
            items: [
                {
                region: 'center',
                layout: {
                    align: 'stretch',  // Child items are stretched to full width
                    type: 'vbox'
                },
                listeners: {
                    'afterlayout': this.fixLayout,
                    scope: this
                },
                items: [
                    this.accountCombo,
                    this.recipientGrid,
                {
                    xtype:'textfield',
                    plugins: [ Ext.ux.FieldLabeler ],
                    fieldLabel: this.app.i18n._('Subject'),
                    name: 'subject',
                    ref: '../../subjectField',
                    enableKeyEvents: true,
                    listeners: {
                        scope: this,
                        // update title on keyup event
                        'keyup': function(field, e) {
                            if (! e.isSpecialKey()) {
                                this.window.setTitle(
                                    this.app.i18n._(this.windowTitle) + ' '
                                    + field.getValue()
                                );
                            }
                        },
                        'focus': function(field) {
                            this.subjectField.focus(true, 100);
                        }
                    }
                }, this.htmlEditor
                ]
            }, 
            this.southPanel]
        };
        
        //return items;
        
        
    },

    /**
     * is form valid (checks if attachments are still uploading / recipients set)
     *
     * @return {Boolean}
     */
    isValid: function() {
        this.validationErrorMessage = Tine.Expressomail.MessageEditDialog.superclass.getValidationErrorMessage.call(this);

        var result = true;

        if (this.attachmentGrid.isUploading()) {
            result = false;
            this.validationErrorMessage = this.app.i18n._('Files are still uploading.');
        }

        if (result && this.sending) {
            result = this.validateRecipients();
        }
        

        return (result && Tine.Expressomail.MessageEditDialog.superclass.isValid.call(this));
    },

    /**
     * generic apply changes handler
     * - NOTE: overwritten to check here if the subject is empty and if the user wants to send an empty message
     *
     * @param {Ext.Button} button
     * @param {Event} event
     * @param {Boolean} closeWindow
     *
     * TODO add note editing textfield here
     */
    onApplyChanges: function(closeWindow) {
        Tine.log.debug('Tine.Expressomail.MessageEditDialog::onApplyChanges()');

        this.loadMask.show();
       // var subjectOk = (this.getForm().findField('subject').getValue() == '') ? false : true;
        if (this.getForm().findField('subject').getValue() == '') {
            Tine.log.debug('Tine.Expressomail.MessageEditDialog::onApplyChanges - empty subject');
            Ext.MessageBox.confirm(
                this.app.i18n._('Empty subject'),
                this.app.i18n._('Do you really want to send a message with an empty subject?'),
                function (button) {
                    Tine.log.debug('Tine.Expressomail.MessageEditDialog::doApplyChanges - button: ' + button);
                    if (button == 'yes') {
                        this.relayToApplet(closeWindow);
                    } else {
                        this.loadMask.hide();
                    }
                },
                this
            );
        }else{
            Tine.log.debug('Tine.Expressomail.MessageEditDialog::doApplyChanges - call parent');
            this.relayToApplet(closeWindow);
        }
        
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
     * onRequestSuccess (doApplyChanges success handler)
     */
    onRequestSuccess: function(record) {
        if(!(window.opener && !window.opener.closed) && !window.isMainWindow){
            // opener window was closed, so we have to close this window immediately
            this.purgeListeners();
            this.loadMask.hide();
            window.open('', '_self', '');
            window.close();
        }

        this.supr().onRequestSuccess.apply(this, arguments);

        this.removeDraftFromGridPanel(record.get('draft_id'));

        if (this.button_toggleAddUnknownContacts.pressed) {
            // reload personal contacts node on Addressbook
            var addressbookApp = Tine.Tinebase.appMgr.get('Addressbook');
            if (addressbookApp) {
                var addressbookTreePanel = addressbookApp.getMainScreen().getWestPanel().getContainerTreePanel();
                var parentNode = addressbookTreePanel.getNodeById('personal');
                if (parentNode) {
                    parentNode.reload();
                }
            }
        }

        if (this.record.get('add_contacts') && record.get('added_contacts')!=0) {
            if (record.get('added_contacts')==-1) {
                Ext.Msg.alert( _('Add Contacts'),
                                this.app.i18n._('Error saving unknown contacts.') + ' ' +
                                this.app.i18n._('One or more contacts have not been added to the folder ') + this.getUnknownContactsFolderName(), this.closePendingMessages, this);
            }
            else { //record.get('added_contacts')>0
                this.closePendingMessages();
            }
        }
        else {
            this.closePendingMessages();
        }

    },

    /**
     * checks recipients
     *
     * @return {Boolean}
     */
    validateRecipients: function() {
        var result = false;

        // Validate each recipient
        Ext.each(['to', 'cc', 'bcc'], function(type) {
            var recipients = this.record.get(type);
            var iRec = 0;
            while (iRec < recipients.length) {
                recipients[iRec] = recipients[iRec].trim();
                if (recipients[iRec].length) {
                    result = true;
                    iRec++;
                }
                else
                    recipients.splice(iRec,1);
            }
        }, this);
        
        if (this.record.get('to').length == 0 && this.record.get('cc').length == 0 && this.record.get('bcc').length == 0) {
            this.validationErrorMessage = this.app.i18n._('No recipients set.');
            result = false;
        }

        return result;
    },

    /**
     * get validation error message
     *
     * @return {String}
     */
    getValidationErrorMessage: function() {
        return this.validationErrorMessage;
    },

    /**
     * fills the recipient grid with the records gotten from this.fetchRecordsOnLoad
     * @param {Array} contacts
     */
    fillRecipientGrid: function(contacts) {
        this.recipientGrid.addRecordsToStore(contacts, 'to');
        this.recipientGrid.setFixedHeight(true);
    },

    /**
     * fetches records to send an email to
     */
    fetchRecordsOnLoad: function(dialog, record, ticketFn) {
        var interceptor = ticketFn(),
            sf = Ext.decode(this.selectionFilter);

        Tine.log.debug('Fetching additional records...');
        Tine.Addressbook.contactBackend.searchRecords(sf, null, {
            scope: this,
            success: function(result) {
                this.fillRecipientGrid(result.records);
                interceptor();
            }
        });
        this.addressesLoaded = true;
    },

    clearSensitiveDataFromRecord: function(){
        this.record.set('attachments', []);
        this.record.set('bcc', []);
        this.record.set('body', '');
        this.record.set('cc', []);
        this.record.set('certificadosDestinatarios', []);
        this.record.set('embedded_images', []);
        this.record.set('subject', '');
        this.record.set('to', '');
    },

    /**
     * is called from onApplyChanges
     * @param {Boolean} closeWindow
     * 
     * @todo Maybe its just sufficent to override onRecordUpdate() and apply the changes there instead of override all sendRequest
     */
    doApplyChanges: function(closeWindow) {
        // we need to sync record before validating to let (sub) panels have 
        // current data of other panels
        this.onRecordUpdate();
        
        // at this point each entry at array 'to' should be of type 'string',
        // but in IE9 they are objects
        for(var i=0; i<this.record.data.to.length; i++) {
            if (typeof(this.record.data.to[i]) != 'string') {
                try {
                    this.record.data.to[i] = this.record.data.to[i].toString();
                } catch (err) {
                    Tine.log.debug('Tine.Expressomail.MessageEditDialog::doApplyChanges() -> error:' + err.message);
                }
            }
        }
        
        // quit copy mode
        this.copyRecord = false;
        
        if (this.isValid()) {
            if (this.mode !== 'local') {

                if (this.encrypted){
                    // clear data from record
                    this.clearSensitiveDataFromRecord();
                }

                this.recordProxy.saveRecord(this.record, {
                    scope: this,
                    success: function(record){this.onRequestSuccess(record,closeWindow)},
                    failure: this.onRequestFailed,
                    timeout: 300000 // 5 minutes
                }, {
                    duplicateCheck: this.doDuplicateCheck
                });
            } else {
                this.onRecordLoad();
                var ticketFn = this.onAfterApplyChanges.deferByTickets(this, [closeWindow]),
                    wrapTicket = ticketFn();
            
                this.fireEvent('update', Ext.util.JSON.encode(this.record.data), this.mode, this, ticketFn);
                wrapTicket();
            }
        } else {
            this.loadMask.hide();
            Ext.MessageBox.alert(_('Errors'), this.getValidationErrorMessage());
            this.sending = false;
        }
    },
    
    /**
     * validate Sender
     */
    senderValidation: function(value) {
        var email, 
            emailRegExp = /(\(|<)([^>^)]*)(\)|>)/;
            
        emailRegExp.exec(value.trim());
        if (RegExp.$2 != '') {
            email = RegExp.$2;
        } else {
            email = value;
        }
        
        // this.refOwner should be MessagEditDialog object
        this.validator = this.refOwner.populateCertificateStore; // reset old validator
        if (this.refOwner.certificatesStore.find('email', email) == -1) {
            return false;
        }
        return true;
    },
    
    
    /**
     * populates the certificatesStore
     */
    populateCertificateStore: function(value) {
        var emails = new Array(),
            comboEmails = new Array();
            
        comboEmails = value.split(',');
        if (comboEmails.length > 0) {
            var emailRegExp = /(\(|<)([^>^)]*)(\)|>)/;
            Ext.each(comboEmails, function(email) {
                if (emailRegExp.exec(email.trim())) {
                    if (RegExp.$2 != '') {
                        emails.push(RegExp.$2);
                    }
                }
                else {
                        emails.push(email.trim());
                }
            }, this);
        }

        var filter = Tine.Addressbook.Model.EmailAddress.getFilterModel();
        filter.push({field: 'email', operator: 'in', value: emails});
        this.refOwner.certificatesStore.baseParams.filter = filter;
        this.refOwner.certificatesStore.load({
            callback: function(records, requestOptions, success) {
                // Change validator to call the correct validation function
                this.validator = this.refOwner.senderValidation; // this.refOwner should be MessagEditDialog object
                this.validate();
            },
            scope: this,
            add: true
        });
        return true;
    },

    rndEid : function() {
        var x = '';
        while(x.length < 32) {
            x += Math.random().toString(16).slice(2,16);
        }
        return x.substr(0, 32);
    }

});

/**
 * Expressomail Edit Popup
 *
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Expressomail.MessageEditDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        notifiable: true,
        width: 829,
        height: 700,
        ref: Tine.Tinebase.appMgr.get('Expressomail'),
        name: Tine.Expressomail.MessageEditDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Expressomail.MessageEditDialog',
        contentPanelConstructorConfig: config
    });
    if((config.encrypted || Tine.Expressomail.registry.get('preferences').get('alwaysSign') == "1") && !Tine.Expressomail.AppletLoadaded){
        window.hide();
    }
    return window;
};
