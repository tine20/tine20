/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

require('./MessageFileButton');

Ext.namespace('Tine.Felamimail');

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.MessageEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 *
 * <p>Message Compose Dialog</p>
 * <p>This dialog is for composing emails with recipients, body and attachments.
 * you can choose from which account you want to send the mail.</p>
 *
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
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
     * @cfg {String} accountId
     * the accout id this message is sent from
     */
    accountId: null,

    /**
     * @cfg {Tine.Felamimail.Model.Message} (optionally encoded)
     * message to reply to
     */
    replyTo: null,

    /**
     * @cfg {Tine.Felamimail.Model.Message} (optionally encoded)
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
     * json-encoded selection filter and to
     * @type {String} selectionFilter
     */
    selectionFilter: null,

    /**
     * holds default values for the record
     * @type {Object}
     */
    recordDefaults: null,

    /**
     * @type {String}
     */
    quotedPGPMessage: null,

    /**
     * @private
     */
    windowNamePrefix: 'MessageEditWindow_',
    appName: 'Felamimail',
    recordClass: Tine.Felamimail.Model.Message,
    recordProxy: Tine.Felamimail.messageBackend,
    loadRecord: false,
    evalGrants: false,
    hideAttachmentsPanel: true,

    bodyStyle: 'padding:0px',

    /**
     * overwrite update toolbars function (we don't have record grants)
     * @private
     */
    updateToolbars: Ext.emptyFn,

    //private
    initComponent: function () {
        var me = this;

        Tine.Felamimail.MessageEditDialog.superclass.initComponent.call(this);

        Tine.Felamimail.mailvelopeHelper.mailvelopeLoaded.then(function () {
            me.button_toggleEncrypt.setVisible(true);
        })['catch'](function () {
            Tine.log.info('mailvelope not available');
        });
    },


    /**
     * init buttons
     */
    initButtons: function () {
        this.fbar = [];

        this.action_send = new Ext.Action({
            text: this.app.i18n._('Send'),
            handler: this.onSaveAndClose,
            iconCls: 'FelamimailIconCls',
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
            disabled: false,
            scope: this
        });

        this.action_saveAsTemplate = new Ext.Action({
            text: this.app.i18n._('Save As Template'),
            handler: this.onSaveInFolder.createDelegate(this, ['templates_folder']),
            iconCls: 'action_saveAsTemplate',
            disabled: false,
            scope: this
        });

        this.button_fileMessage = new Tine.Felamimail.MessageFileButton({
            mode: 'selectOnly',
            composeDialog: this,
            listeners: {
                scope: this,
                selectionchange: this.onFileMessageSelectionChange
            }
        });

        this.action_toggleReadingConfirmation = new Ext.Action({
            text: this.app.i18n._('Reading Confirmation'),
            handler: this.onToggleReadingConfirmation,
            iconCls: 'felamimail-action-reading-confirmation',
            disabled: false,
            scope: this,
            enableToggle: true
        });
        this.button_toggleReadingConfirmation = Ext.apply(new Ext.Button(this.action_toggleReadingConfirmation), {
            tooltip: this.app.i18n._('Activate this toggle button to receive a reading confirmation.')
        });

        this.action_toggleEncrypt = new Ext.Action({
            text: this.app.i18n._('Encrypt Email'),
            toggleHandler: this.onToggleEncrypt,
            iconCls: 'felamimail-action-decrypt',
            disabled: false,
            pressed: false,
            hidden: true,
            scope: this,
            enableToggle: true
        });
        this.button_toggleEncrypt = Ext.apply(new Ext.Button(this.action_toggleEncrypt), {
            tooltip: this.app.i18n._('Encrypt email using Mailvelope')
        });

        this.action_massMailing = new Ext.Action({
            text: this.app.i18n._('Mass Mailing'),
            handler: this.onToggleMassMailing,
            iconCls: 'FelamimailIconCls',
            disabled: false,
            scope: this,
            enableToggle: true
        });
        this.button_massMailing = Ext.apply(new Ext.Button(this.action_massMailing), {
            tooltip: this.app.i18n._('Activate this toggle button to send the mail as separate mail to each recipient.')
        });

        this.tbar = new Ext.Toolbar({
            defaults: {height: 55},
            items: [{
                xtype: 'buttongroup',
                columns: 6,
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
                    this.button_fileMessage,
                    this.action_saveAsTemplate,
                    this.button_toggleReadingConfirmation,
                    this.button_toggleEncrypt,
                    this.button_massMailing
                ]
            }]
        });
    },


    /**
     * @private
     */
    initRecord: function () {
        this.decodeMsgs();

        this.recordDefaults = Tine.Felamimail.Model.Message.getDefaultData();

        if (this.mailAddresses) {
            this.recordDefaults.to = Ext.decode(this.mailAddresses);
        } else if (this.selectionFilter) {
            // put filter into to, cc or bcc of record and the loading be handled by resolveRecipientFilter
            var filterAndTo = Ext.decode(this.selectionFilter);
            this.record.set(filterAndTo.to.toLowerCase(), filterAndTo.filter);
        }

        if (!this.record) {
            this.record = new Tine.Felamimail.Model.Message(this.recordDefaults, 0);
        }
        this.initFrom();
        this.initRecipients();
        this.initSubject();
        this.initContent();

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

        if (this.record.get('massMailingFlag')) {
            this.button_massMailing.toggle();
        }

        Tine.log.debug('Tine.Felamimail.MessageEditDialog::initRecord() -> record:');
        Tine.log.debug(this.record);
    },

    /**
     * show loadMask (loadRecord is false in this dialog)
     * @param {} ct
     * @param {} position
     */
    onRender: function (ct, position) {
        Tine.Felamimail.MessageEditDialog.superclass.onRender.call(this, ct, position);
        this.showLoadMask();
    },

    isRendered: function () {
        var me = this;
        return new Promise(function (fulfill, reject) {
            if (me.rendered) {
                fulfill(true);
            } else {
                me.on('render', fulfill);
            }
        });
    },

    /**
     * handle attachments: attaches message when forwarding mails or
     *  keeps attachments as they are (if preference is set or draft/template)
     *
     * @param {Tine.Felamimail.Model.Message} message
     */
    handleAttachmentsOfExistingMessage: function (message) {
        if (message.get('attachments').length == 0) {
            return;
        }

        var attachments = [];
        if ((Tine[this.app.appName].registry.get('preferences').get('emlForward')
                && Tine[this.app.appName].registry.get('preferences').get('emlForward') == 0)
            || this.draftOrTemplate
        ) {

            Ext.each(message.get('attachments'), function (attachment) {
                attachment = {
                    name: attachment['filename'],
                    type: attachment['content-type'],
                    size: attachment['size'],
                    id: message.id + '_' + attachment['partId']
                };
                attachments.push(attachment);
            }, this);

        } else {
            var rfc822Attachment = {
                name: message.get('subject'),
                type: 'message/rfc822',
                size: message.get('size'),
                id: message.id
            }, node = message.get('from_node');
            if (node) {
                // @refactor use Ext.apply / lodash
                rfc822Attachment.type = 'file';
                rfc822Attachment.size = node.size;
                rfc822Attachment.attachment_type = 'attachment';
                rfc822Attachment.path = node.path;
                rfc822Attachment.name = node.name;
            }
            attachments = [rfc822Attachment];
        }

        this.record.set('attachments', attachments);
    },

    /**
     * inits body and attachments from reply/forward/template
     */
    initContent: function () {
        if (!this.record.get('body')) {
            var account = Tine.Tinebase.appMgr.get('Felamimail').getAccountStore().getById(this.record.get('account_id')),
                format = account && account.get('compose_format') != '' ? 'text/' + account.get('compose_format') : 'text/html';

            if (!this.msgBody) {
                var message = this.getMessageFromConfig();
                if (message) {
                    if (message.bodyIsFetched() && account.get('preserve_format')) {
                        // format of the received message. this is the format to perserve
                        format = message.get('body_content_type');
                    }
                    if (!message.bodyIsFetched() || format != message.getBodyType()) {
                        // self callback when body needs to be (re) fetched
                        return this.recordProxy.fetchBody(message, format, this.initContent.createDelegate(this));
                    }

                    this.setMessageBody(message, account, format);

                    if (this.isForwardedMessage() || this.draftOrTemplate) {
                        this.handleAttachmentsOfExistingMessage(message);
                    }
                }
            }

            this.addSignature(account, format);

            this.record.set('content_type', format);
            this.record.set('body', this.msgBody);
        }

        if (this.attachments) {
            this.handleExternalAttachments();
        }

        delete this.msgBody;

        this.onRecordLoad();
    },

    /**
     * handle attachments like external URLs (COSR)
     *
     * TODO: check if this overwrites existing attachments in some cases
     */
    handleExternalAttachments: function () {
        this.attachments = Ext.isArray(this.attachments) ? this.attachments : [this.attachments];
        var attachments = [];
        Ext.each(this.attachments, function (attachment) {

            // external URL with COSR header enabled
            if (Ext.isString(attachment)) {
                attachment = {
                    url: attachment
                };
            }

            attachments.push(attachment);
        }, this);

        this.record.set('attachments', attachments);
        delete this.attachments;
    },

    /**
     * set message body: converts newlines, adds quotes
     *
     * @param {Tine.Felamimail.Model.Message} message
     * @param {Tine.Felamimail.Model.Account} account
     * @param {String}                        format
     */
    setMessageBody: function (message, account, format) {
        var preparedParts = message.get('preparedParts');

        this.msgBody = message.get('body');

        if (preparedParts && preparedParts.length > 0) {
            if (preparedParts[0].contentType == 'application/pgp-encrypted') {
                this.quotedPGPMessage = preparedParts[0].preparedData;

                this.msgBody = this.msgBody + this.app.i18n._('Encrypted Content');

                var me = this;
                this.isRendered().then(function () {
                    me.button_toggleEncrypt.toggle();
                });
            }
        }

        if (this.replyTo) {
            if (format == 'text/plain') {
                this.msgBody = String('> ' + this.msgBody).replace(/\r?\n/g, '\n> ');
            } else {
                this.msgBody = '<br/>'
                    + '<blockquote class="felamimail-body-blockquote">' + this.msgBody + '</blockquote><br/>';
            }
        }
        this.msgBody = this.getQuotedMailHeader(format) + this.msgBody;
    },

    /**
     * returns true if message is forwarded
     *
     * @return {Boolean}
     */
    isForwardedMessage: function () {
        return (this.forwardMsgs && this.forwardMsgs.length === 1);
    },

    /**
     * add signature to message
     *
     * @param {Tine.Felamimail.Model.Account} account
     * @param {String} format
     */
    addSignature: function (account, format) {
        if (this.draftOrTemplate) {
            return;
        }

        var accountId = account ? this.record.get('account_id') : Tine.Felamimail.registry.get('preferences').get('defaultEmailAccount'),
            account = account ? account : this.app.getAccountStore().getById(accountId),
            signaturePosition = (account && account.get('signature_position')) ? account.get('signature_position') : 'below',
            signature = this.getSignature(account, format);

        if (signaturePosition == 'below') {
            this.msgBody += signature;
        } else {
            this.msgBody = signature + '<br/><br/>' + this.msgBody;
        }
    },

    /**
     * get account signature
     *
     * @param {Tine.Felamimail.Model.Account} account
     * @param {String} format
     */
    getSignature: function (account, format) {
        var accountId = account ? this.record.get('account_id') : Tine.Felamimail.registry.get('preferences').get('defaultEmailAccount'),
            account = account ? account : this.app.getAccountStore().getById(accountId),
            signaturePosition = (account && account.get('signature_position')) ? account.get('signature_position') : 'below',
            signature = Tine.Felamimail.getSignature(accountId);

        if (format == 'text/plain') {
            signature = Tine.Tinebase.common.html2text(signature);
        }

        return signature;
    },

    /**
     * inits / sets sender of message
     */
    initFrom: function () {
        if (!this.record.get('account_id')) {
            if (!this.accountId) {
                var message = this.getMessageFromConfig(),
                    folderId = message ? message.get('folder_id') : null,
                    folder = folderId ? Tine.Tinebase.appMgr.get('Felamimail').getFolderStore().getById(folderId) : null,
                    accountId = folder ? folder.get('account_id') : null;

                if (!accountId) {
                    var activeAccount = Tine.Tinebase.appMgr.get('Felamimail').getActiveAccount();
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
    afterRender: function () {
        Tine.Felamimail.MessageEditDialog.superclass.afterRender.apply(this, arguments);

        this.getEl().on(Ext.EventManager.useKeydown ? 'keydown' : 'keypress', this.onKeyPress, this);
        this.recipientGrid.on('specialkey', function (field, e) {
            this.onKeyPress(e);
        }, this);

        this.htmlEditor.on('keydown', function (e) {
            if (e.getKey() == e.ENTER && e.ctrlKey) {
                this.onSaveAndClose();
            } else if (e.getKey() == e.TAB && e.shiftKey) {
                this.subjectField.focus.defer(50, this.subjectField);
            }
        }, this);

        this.htmlEditor.on('toggleFormat', this.onToggleFormat, this);

        this.initHtmlEditorDD();
    },


    initHtmlEditorDD: function () {
        return;
        if (!this.htmlEditor.rendered) {
            return this.initHtmlEditorDD.defer(500, this);
        }

        this.htmlEditor.getDoc().addEventListener('dragover', function (e) {
            this.action_addAttachment.plugins[0].onBrowseButtonClick();
        }.createDelegate(this));

        this.htmlEditor.getDoc().addEventListener('drop', function (e) {
            this.action_addAttachment.plugins[0].onDrop(Ext.EventObject.setEvent(e));
        }.createDelegate(this));
    },

    /**
     * on key press
     * @param {} e
     * @param {} t
     * @param {} o
     */
    onKeyPress: function (e, t, o) {
        if ((e.getKey() == e.TAB || e.getKey() == e.ENTER) && !e.shiftKey) {
            if (e.getTarget('input[name=subject]')) {
                this.htmlEditor.focus.defer(50, this.htmlEditor);
            } else if (e.getTarget('input[type=text]')) {
                this.subjectField.focus.defer(50, this.subjectField);
            }
        }
    },

    /**
     * returns message passed with config
     *
     * @return {Tine.Felamimail.Model.Message}
     */
    getMessageFromConfig: function () {
        return this.replyTo ? this.replyTo :
            this.forwardMsgs && this.forwardMsgs.length === 1 ? this.forwardMsgs[0] :
                this.draftOrTemplate ? this.draftOrTemplate : null;
    },

    /**
     * inits to/cc/bcc
     */
    initRecipients: function () {
        if (this.replyTo) {
            this.initReplyRecipients();
        }

        Ext.each(['to', 'cc', 'bcc'], function (field) {
            if (this.draftOrTemplate) {
                this[field] = this.draftOrTemplate.get(field);
            }

            if (!this.record.get(field)) {
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
    initReplyRecipients: function () {
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
            if (!Ext.isArray(this.to)) {
                this.to = [this.to];
            }
            this.to = this.to.concat(this.replyTo.get('to'));
            this.cc = this.replyTo.get('cc');

            // remove own email and all non-email strings/objects from to/cc
            var account = Tine.Tinebase.appMgr.get('Felamimail').getAccountStore().getById(this.record.get('account_id')),
                ownEmailRegexp = new RegExp(window.lodash.escapeRegExp(account.get('email')));
            Ext.each(['to', 'cc'], function (field) {
                for (var i = 0; i < this[field].length; i++) {
                    if (!Ext.isString(this[field][i]) || !this[field][i].match(/@/) || ownEmailRegexp.test(this[field][i])) {
                        this[field].splice(i, 1);
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
    resolveRecipientFilter: function (field) {
        if (!Ext.isEmpty(this.record.get(field))
            && Ext.isObject(this.record.get(field)[0])
            && (this.record.get(field)[0].operator || this.record.get(field)[0].condition)
        ) {
            // found a filter
            var filter = this.record.get(field);
            this.record.set(field, []);

            this['AddressLoadMask'] = new Ext.LoadMask(Ext.getBody(), {msg: this.app.i18n._('Loading Mail Addresses')});
            this['AddressLoadMask'].show();

            Tine.Addressbook.searchContacts(filter, null, function (response) {
                var mailAddresses = Tine.Felamimail.GridPanelHook.prototype.getMailAddresses(response.results);

                this.record.set(field, mailAddresses);
                this.recipientGrid.syncRecipientsToStore([field], this.record, true, false);
                this['AddressLoadMask'].hide();

            }.createDelegate(this));
        }
    },

    /**
     * sets / inits subject
     */
    initSubject: function () {
        if (!this.record.get('subject')) {
            if (!this.subject) {
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
    setReplySubject: function () {
        var replyPrefix = 'Re: ',
            replySubject = (this.replyTo.get('subject')) ? this.replyTo.get('subject') : '',
            replySubject = replySubject.replace(/^((re|aw|antw|fwd|odp|sv|wg|tr|rép):\s*)*/i, replyPrefix);

        this.subject = replySubject;
    },

    /**
     * setForwardSubject -> this.subject
     */
    setForwardSubject: function () {
        this.subject = this.app.i18n._('Fwd:') + ' ';
        this.subject += this.forwardMsgs.length === 1 ?
            this.forwardMsgs[0].get('subject') :
            String.format(this.app.i18n._('{0} Message', '{0} Messages', this.forwardMsgs.length));
    },

    /**
     * decode this.replyTo / this.forwardMsgs from interwindow json transport
     */
    decodeMsgs: function () {
        if (Ext.isString(this.draftOrTemplate)) {
            this.draftOrTemplate = new this.recordClass(Ext.decode(this.draftOrTemplate));
        }

        if (Ext.isString(this.replyTo)) {
            this.replyTo = new this.recordClass(Ext.decode(this.replyTo));
        }

        if (Ext.isString(this.forwardMsgs)) {
            var msgs = [];
            Ext.each(Ext.decode(this.forwardMsgs), function (msg) {
                msgs.push(new this.recordClass(msg));
            }, this);

            this.forwardMsgs = msgs;
        }
    },

    /**
     * fix input fields layout
     */
    fixLayout: function () {
        if (!this.subjectField.rendered || !this.accountCombo.rendered || !this.recipientGrid.rendered) {
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

        var account = Tine.Tinebase.appMgr.get('Felamimail').getAccountStore().getById(this.record.get('account_id')),
            folderName = account.get(folderField);

        Tine.log.debug('onSaveInFolder() - Save message in folder ' + folderName);
        Tine.log.debug(this.record);

        if (!folderName || folderName == '') {
            Ext.MessageBox.alert(
                i18n._('Failed'),
                String.format(this.app.i18n._('{0} account setting empty.'), folderField)
            );
        } else if (this.attachmentGrid.isUploading()) {
            Ext.MessageBox.alert(
                i18n._('Failed'),
                this.app.i18n._('Files are still uploading.')
            );
        } else {
            this.loadMask.show();
            this.recordProxy.saveInFolder(this.record, folderName, {
                scope: this,
                success: function (record) {
                    this.fireEvent('update', Ext.util.JSON.encode(this.record.data));
                    this.purgeListeners();
                    this.window.close();
                },
                failure: Tine.Felamimail.handleRequestException.createInterceptor(function () {
                        this.hideLoadMask();
                    }, this
                ),
                timeout: 150000 // 3 minutes
            });
        }
    },

    /**
     * toggle mass mailing
     *
     * @param {} button
     * @param {} e
     */
    onToggleMassMailing: function (button, e) {
        var active = !this.record.get('massMailingFlag');

        this.record.set('massMailingFlag', active);

        if (active) {
            this.massMailingInfoText.show();
            this.doLayout();
        } else {
            this.massMailingInfoText.hide();
            this.doLayout();
        }
    },

    onFileMessageSelectionChange: function(btn, selection) {
        var text = this.app.formatMessage('{locationCount, plural, one {This message will be filed at the following location} other {This message will be filed at the following locations}}: {locationsHtml}', {
                locationCount: selection.length,
                locationsHtml: Tine.Felamimail.MessageFileButton.getFileLocationText(selection, ', ')
            });

        this.messageFileInfoText.update(text);
        this.messageFileInfoText.setVisible(selection.length);
        this.doLayout();
    },
    
    /**
     * toggle Request Reading Confirmation
     */
    onToggleReadingConfirmation: function () {
        this.record.set('reading_conf', (!this.record.get('reading_conf')));
    },

    onToggleEncrypt: function (btn, e) {
        btn.setIconClass(btn.pressed ? 'felamimail-action-encrypt' : 'felamimail-action-decrypt');

        var account = Tine.Tinebase.appMgr.get('Felamimail').getAccountStore().getById(this.record.get('account_id')),
            text = this.bodyCards.layout.activeItem.getValue() || this.record.get('body'),
            format = this.record.getBodyType(),
            textEditor = format == 'text/html' ? this.htmlEditor : this.textEditor;

        this.bodyCards.layout.setActiveItem(btn.pressed ? this.mailvelopeWrap : textEditor);

        if (btn.pressed) {
            var me = this,
                textMsg = Tine.Tinebase.common.html2text(text),
                quotedMailHeader = '';

            if (this.quotedPGPMessage) {
                textMsg = this.getSignature(account, 'text/plain');
                quotedMailHeader = Ext.util.Format.htmlDecode(me.getQuotedMailHeader('text/plain'));
                quotedMailHeader = quotedMailHeader.replace(/\n/, "\n>");

            }

            Tine.Felamimail.mailvelopeHelper.getKeyring().then(function (keyring) {
                mailvelope.createEditorContainer('#' + me.mailvelopeWrap.id, keyring, {
                    predefinedText: textMsg,
                    quotedMailHeader: quotedMailHeader,
                    quotedMail: me.quotedPGPMessage,
                    keepAttachments: true,
                    quota: 32 * 1024 * 1024
                }).then(function (editor) {
                    me.mailvelopeEditor = editor;
                });
            });

            this.southPanel.collapse();
            this.southPanel.setVisible(false);
            this.btnAddAttachemnt.setDisabled(true);
        } else {
            this.mailvelopeEditor = null;
            delete this.mailvelopeEditor;
            this.mailvelopeWrap.update('');

            this.southPanel.setVisible(true);
            this.btnAddAttachemnt.setDisabled(false);
        }
    },

    /**
     * toggle format
     */
    onToggleFormat: function () {
        var source = this.bodyCards.layout.activeItem,
            format = source.mimeType,
            target = format == 'text/plain' ? this.htmlEditor : this.textEditor,
            convert = format == 'text/plain' ?
                Ext.util.Format.nl2br :
                Tine.Tinebase.common.html2text;

        if (format.match(/^text/)) {
            this.bodyCards.layout.setActiveItem(target);
            target.setValue(convert(source.getValue()));
        } else {
            // ignore toggle request for encrypted content
        }
    },

    /**
     * get quoted mail header
     *
     * @param format
     * @returns {String}
     */
    getQuotedMailHeader: function (format) {
        if (this.replyTo) {
            var date = (this.replyTo.get('sent'))
                ? this.replyTo.get('sent')
                : ((this.replyTo.get('received')) ? this.replyTo.get('received') : new Date());

            return String.format(this.app.i18n._('On {0}, {1} wrote'),
                Tine.Tinebase.common.dateTimeRenderer(date),
                Ext.util.Format.htmlEncode(this.replyTo.get('from_name'))
            ) + ':\n';
        } else if (this.isForwardedMessage()) {
            return String.format('{0}-----' + this.app.i18n._('Original message') + '-----{1}',
                format == 'text/plain' ? '' : '<br /><b>',
                format == 'text/plain' ? '\n' : '</b><br />')
                + Tine.Felamimail.GridPanel.prototype.formatHeaders(this.forwardMsgs[0].get('headers'), false, true, format == 'text/plain')
                + (format == 'text/plain' ? '' : '<br /><br />');
        }

        return '';
    },

    /**
     * search for contacts as recipients
     */
    onSearchContacts: function () {
        Tine.Felamimail.RecipientPickerDialog.openWindow({
            record: Ext.encode(Ext.copyTo({}, this.record.data, ['subject', 'to', 'cc', 'bcc'])),
            listeners: {
                scope: this,
                'update': function (record) {
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
    onRecordLoad: function () {
        // interrupt process flow till dialog is rendered
        if (!this.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }

        var title = this.app.i18n._('Compose email:'),
            editor = this.record.get('content_type') == 'text/html' ? this.htmlEditor : this.textEditor;

        if (this.record.get('subject')) {
            title = title + ' ' + this.record.get('subject');
        }
        this.window.setTitle(title);

        if (!this.button_toggleEncrypt.pressed) {
            editor.setValue(this.record.get('body'));
            this.bodyCards.layout.setActiveItem(editor);
        }

        // to make sure we have all recipients (for example when composing from addressbook with "all pages" filter)
        var ticketFn = this.onAfterRecordLoad.deferByTickets(this),
            wrapTicket = ticketFn();
        this.fireEvent('load', this, this.record, ticketFn);
        wrapTicket();

        this.getForm().loadRecord(this.record);
        this.attachmentGrid.loadRecord(this.record);

        if (this.record.get('massMailingFlag')) {
            this.massMailingInfoText.show();
        }

        this.onAfterRecordLoad();
    },

    /**
     * overwrite, just hide the loadMask
     */
    onAfterRecordLoad: function () {
        (function() {
            this.checkStates();
            this.record.commit();
        }).defer(100, this);

        if (this.loadMask) {
            this.hideLoadMask();
        }
    },

    /**
     * executed when record gets updated from form
     * - add attachments to record here
     * - add alias / from
     *
     * @private
     */
    onRecordUpdate: function () {
        this.record.data.attachments = [];
        var attachmentData = null;

        var format = this.bodyCards.layout.activeItem.mimeType;
        if (format.match(/^text/)) {
            var editor = format == 'text/html' ? this.htmlEditor : this.textEditor;

            this.record.set('content_type', format);
            this.record.set('body', editor.getValue());
        }

        this.attachmentGrid.store.each(function (attachment) {
            var fileData = Ext.copyTo({}, attachment.data, ['tempFile', 'name', 'path', 'size', 'type', 'id', 'attachment_type', 'password']);
            this.record.data.attachments.push(fileData);
        }, this);

        var accountId = this.accountCombo.getValue(),
            account = this.accountCombo.getStore().getById(accountId),
            emailFrom = account.get('email');

        this.record.set('from_email', emailFrom);
        this.record.set('from_name', account.get('from'));

        Tine.Felamimail.MessageEditDialog.superclass.onRecordUpdate.call(this);

        this.record.set('account_id', account.get('original_id'));
        
        if (this.button_fileMessage.pressed) {
            this.record.set('fileLocations', this.button_fileMessage.getSelected());
        }

        // need to sync once again to make sure we have the correct recipients
        this.recipientGrid.syncRecipientsToRecord();
    },

    /**
     * init attachment grid + add button to toolbar
     */
    initAttachmentGrid: function () {
        if (!this.attachmentGrid) {
            this.attachmentGrid = new Tine.Felamimail.AttachmentUploadGrid({
                fieldLabel: this.app.i18n._('Attachments'),
                hideLabel: true,
                filesProperty: 'attachments',
                // TODO     think about that -> when we deactivate the top toolbar, we lose the dropzone for files!
                //showTopToolbar: false,
                anchor: '100% 95%'
            });

            // add file upload button to toolbar

            this.action_addAttachment = this.attachmentGrid.getAddAction();
            this.action_addAttachment.plugins[0].dropElSelector = 'div[id=' + this.id + ']';

            this.attachmentGrid.on('filesSelected', function (nodes) {
                this.southPanel.expand();
            }, this);

            this.btnAddAttachemnt = new Ext.Button(this.action_addAttachment);
            this.tbar.get(0).insert(1, Ext.apply(this.btnAddAttachemnt, {
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
    initAccountCombo: function () {
        var accountStore = Tine.Tinebase.appMgr.get('Felamimail').getAccountStore(),
            accountComboStore = new Ext.data.ArrayStore({
                fields: Tine.Felamimail.Model.Account
            });

        var aliasAccount = null,
            aliases = null,
            id = null;

        accountStore.each(function (account) {
            aliases = [account.get('email')];

            if (account.get('type') == 'system') {
                // add identities / aliases to store (for systemaccounts)
                var user = Tine.Tinebase.registry.get('currentAccount');
                if (user.emailUser && user.emailUser.emailAliases && user.emailUser.emailAliases.length > 0) {
                    aliases = aliases.concat(user.emailUser.emailAliases);
                }
            }

            for (var i = 0; i < aliases.length; i++) {
                id = (i == 0) ? account.id : Ext.id();
                aliasAccount = account.copy(id);
                if (i > 0) {
                    aliasAccount.data.id = id;
                    aliasAccount.set('email', aliases[i]);
                }
                aliasAccount.set('name', aliasAccount.get('name') + ' (' + aliases[i] + ')');
                aliasAccount.set('original_id', account.id);
                accountComboStore.add(aliasAccount);
            }
        }, this);

        this.accountCombo = new Ext.form.ComboBox({
            name: 'account_id',
            ref: '../../accountCombo',
            plugins: [Ext.ux.FieldLabeler],
            fieldLabel: this.app.i18n._('From'),
            displayField: 'name',
            valueField: 'id',
            editable: false,
            triggerAction: 'all',
            store: accountComboStore,
            mode: 'local',
            listeners: {
                scope: this,
                select: this.onFromSelect
            }
        });
    },

    /**
     * if 'account_id' is changed we need to update the signature
     *
     * @param {} combo
     * @param {} newValue
     * @param {} oldValue
     */
    onFromSelect: function (combo, record, index) {

        // get new signature
        var accountId = record.get('original_id');
        var newSignature = Tine.Felamimail.getSignature(accountId);
        var signatureRegexp = new RegExp('<br><br><span id="felamimail\-body\-signature">\-\-<br>.*</span>');

        // update signature
        var bodyContent = this.htmlEditor.getValue();
        bodyContent = bodyContent.replace(signatureRegexp, newSignature);

        // update reply-to
        var replyTo = record.get('reply_to');
        if (replyTo && replyTo != '') {
            this.replyToField.setValue(replyTo);
        }

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
    getFormItems: function () {

        this.initAttachmentGrid();
        this.initAccountCombo();

        this.recipientGrid = new Tine.Felamimail.RecipientGrid({
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
            collapsed: (this.record.bodyIsFetched() && (!this.record.get('attachments') || this.record.get('attachments').length == 0)),
            items: [this.attachmentGrid]
        });

        this.textEditor = new Ext.Panel({
            layout: 'fit',
            mimeType: 'text/plain',
            cls: 'felamimail-edit-text-plain',
            flex: 1,  // Take up all *remaining* vertical space
            setValue: function (v) {
                return this.items.get(0).setValue(v);
            },
            getValue: function () {
                return this.items.get(0).getValue();
            },
            tbar: ['->', {
                iconCls: 'x-edit-toggleFormat',
                tooltip: this.app.i18n._('Convert to formated text'),
                handler: this.onToggleFormat,
                scope: this
            }],
            items: [
                new Ext.form.TextArea({
                    fieldLabel: this.app.i18n._('Body'),
                    name: 'body_text'
                })
            ]
        });

        this.htmlEditor = new Tine.Felamimail.ComposeEditor({
            fieldLabel: this.app.i18n._('Body'),
            name: 'body_html',
            mimeType: 'text/html',
            flex: 1  // Take up all *remaining* vertical space
        });

        this.mailvelopeWrap = new Ext.Container({
            flex: 1,  // Take up all *remaining* vertical space
            mimeType: 'application/pgp-encrypted',
            getValue: function () {
                return '';
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
                        {
                            // mass mailing info text
                            cls: 'felamimail-compose-info',
                            html: this.app.i18n._('NOTE: This is mail will be sent as a mass mail, i.e. each recipient will get his or her own copy.'),
                            hidden: true,
                            ref: '../../massMailingInfoText',
                            padding: '2px',
                            height: 20
                        },
                        {
                            // message file info text
                            cls: 'felamimail-compose-info',
                            hidden: true,
                            ref: '../../messageFileInfoText',
                            padding: '2px',
                            height: 'auto'
                        },
                        this.accountCombo,
                        {
                            // extuxclearabletextfield would be better, but breaks the layout big tim
                            // TODO fix layout (equal width of input boxes)!
                            xtype: 'textfield',
                            plugins: [Ext.ux.FieldLabeler],
                            fieldLabel: this.app.i18n._('Reply-To Email'),
                            name: 'reply_to',
                            ref: '../../replyToField',
                            hidden: ! Tine.Tinebase.appMgr.get('Felamimail').featureEnabled('showReplyTo'),
                            emptyText: this.app.i18n._('Add email address here for reply-to'),
                            value: Tine.Tinebase.appMgr.get('Felamimail').getActiveAccount().get('reply_to') // reply-to from account or email
                        },
                        this.recipientGrid,
                        {
                            xtype: 'textfield',
                            plugins: [Ext.ux.FieldLabeler],
                            fieldLabel: this.app.i18n._('Subject'),
                            name: 'subject',
                            ref: '../../subjectField',
                            enableKeyEvents: true,
                            listeners: {
                                scope: this,
                                // update title on keyup event
                                'keyup': function (field, e) {
                                    if (!e.isSpecialKey()) {
                                        this.window.setTitle(
                                            this.app.i18n._('Compose email:') + ' '
                                            + field.getValue()
                                        );
                                    }
                                },
                                'focus': function (field) {
                                    this.subjectField.focus(true, 100);
                                }
                            }
                        }, {
                            layout: 'card',
                            ref: '../../bodyCards',
                            activeItem: 0,
                            flex: 1,
                            items: [
                                this.textEditor,
                                this.htmlEditor,
                                this.mailvelopeWrap
                            ]

                        }]
                }, this.southPanel]
        };
    },

    /**
     * is form valid (checks if attachments are still uploading / recipients set)
     *
     * @return {Boolean}
     */
    isValid: function () {
        var me = this;
        return Tine.Felamimail.MessageEditDialog.superclass.isValid.call(me).then(function () {
            if (me.attachmentGrid.isUploading()) {
                return Promise.reject(me.app.i18n._('Files are still uploading.'));
            }

            return me.validateRecipients();
        });
    },

    /**
     *
     * @return {Promise}
     */
    validateSystemlinkRecipients: function () {
        var me = this;

        return new Promise(function (fulfill, reject) {
            var recipients = [],
                resolvePromise = fulfill;

            me.recipientGrid.getStore().each(function (recipient) {
                var address = recipient.get('address');

                if (!address) {
                    return;
                }

                recipients.push(me.extractMailFromString(address));
            });

            var hasSystemlinks = false;

            me.attachmentGrid.getStore().each(function (attachment) {
                if (attachment.get('attachment_type') === 'systemlink_fm') {
                    hasSystemlinks = true;
                    return false;
                }
            });

            if (hasSystemlinks) {
                Tine.Felamimail.doMailsBelongToAccount(recipients).then(function (res) {
                    resolvePromise(Object.values(res))
                });
            } else {
                resolvePromise(false);
            }

        });
    },

    extractMailFromString: function (string) {
        string = String(string).trim();
        if (Ext.form.VTypes.email(string)) {
            return string;
        }

        var angleBracketExtraction = string.match(/<([^>;]+)>/i)[1];
        if (null !== angleBracketExtraction && Ext.form.VTypes.email(angleBracketExtraction)) {
            return angleBracketExtraction;
        }

        return string;
    },

    /**
     * generic apply changes handler
     * - NOTE: overwritten to check here if the subject is empty and if the user wants to send an empty message
     *
     * @param {Ext.Button} button
     * @param {Event} event
     * @param {Boolean} closeWindow
     */
    onApplyChanges: function (closeWindow, emptySubject, passwordSet, nonSystemAccountRecipients) {
        var me = this,
            _ = window.lodash;

        Tine.log.debug('Tine.Felamimail.MessageEditDialog::onApplyChanges()');

        this.loadMask.show();

        if (Tine.Tinebase.appMgr.isEnabled('Filemanager') && undefined === nonSystemAccountRecipients) {
            this.validateSystemlinkRecipients().then(function (mails) {
                me.onApplyChanges(closeWindow, emptySubject, passwordSet, mails)
            });
            return;
        } else if (_.isArray(nonSystemAccountRecipients) && nonSystemAccountRecipients.length > 0) {
            let records = _.filter(me.recipientGrid.getStore().data.items, function (rec) {
                let match = false;
                _.each(nonSystemAccountRecipients, function (mail) {
                    if (null !== rec.get('address').match(new RegExp(mail))) {
                        match = true;
                    }
                });

                return match;
            }.bind({'nonSystemAccountRecipients': nonSystemAccountRecipients}));

            _.each(records, function (rec) {
                var index = me.recipientGrid.getStore().indexOf(rec),
                    row = me.recipientGrid.view.getRow(index);

                row.classList.add('felamimail-is-external-recipient');
            });

            Ext.MessageBox.confirm(
                this.app.i18n._('Warning'),
                this.app.i18n._('Some attachments are of type "systemlinks" whereas some of the recipients (marked yellow), couldn\'t be validated to be accounts on this installation. Only recipients with an active account will be able to open those attachments.') + "<br /><br />" + this.app.i18n._('Do you really want to send?'),
                function (button) {
                    if (button == 'yes') {
                        me.onApplyChanges(closeWindow, emptySubject, passwordSet, false);
                    } else {
                        this.hideLoadMask();
                    }
                },
                this
            );
            return;
        }

        // If filemanager attachments are possible check if passwords are required to enter
        if (Tine.Tinebase.appMgr.isEnabled('Filemanager') && passwordSet !== true) {
            var attachmentStore = this.attachmentGrid.getStore();

            if (attachmentStore.find('attachment_type', 'download_protected_fm') !== -1) {
                var dialog = new Tine.Tinebase.widgets.dialog.PasswordDialog();
                dialog.openWindow();

                // password entered
                dialog.on('apply', function (password) {
                    attachmentStore.each(function (attachment) {
                        if (attachment.get('attachment_type') === 'download_protected_fm') {
                            attachment.data.password = password;
                        }
                    });

                    me.onApplyChanges(closeWindow, emptySubject, true, nonSystemAccountRecipients);
                });

                // user presses cancel in dialog => allow to submit again or edit mail and so on!
                dialog.on('cancel', function () {
                    this.hideLoadMask();
                }, this);
                return;
            }
        }

        if (!emptySubject && this.getForm().findField('subject').getValue() == '') {
            Tine.log.debug('Tine.Felamimail.MessageEditDialog::onApplyChanges - empty subject');
            Ext.MessageBox.confirm(
                this.app.i18n._('Empty subject'),
                this.app.i18n._('Do you really want to send a message with an empty subject?'),
                function (button) {
                    Tine.log.debug('Tine.Felamimail.MessageEditDialog::doApplyChanges - button: ' + button);
                    if (button == 'yes') {
                        this.onApplyChanges(closeWindow, true, true, nonSystemAccountRecipients);
                    } else {
                        this.hideLoadMask();
                    }
                },
                this
            );

            return;
        }

        Tine.log.debug('Tine.Felamimail.MessageEditDialog::doApplyChanges - call parent');
        this.doApplyChanges(closeWindow);
    },

    /**
     * checks recipients
     *
     * @return {Boolean}
     */
    validateRecipients: function () {
        var me = this;
        return new Promise(function (fulfill, reject) {
            var to = me.record.get('to'),
                cc = me.record.get('cc'),
                bcc = me.record.get('bcc'),
                all = [].concat(to).concat(cc).concat(bcc);

            if (all.length == 0) {
                reject(me.app.i18n._('No recipients set.'));
            }

            if (me.button_toggleEncrypt.pressed && me.mailvelopeEditor) {
                // always add own address so send message can be decrypted
                all.push(me.record.get('from_email'));

                all = all.map(function (item) {
                    return addressparser.parse(item.replace(/,/g, '\\\\,'))[0].address;
                });

                return Tine.Felamimail.mailvelopeHelper.getKeyring().then(function (keyring) {
                    keyring.validKeyForAddress(all).then(function (result) {
                        var missingKeys = [];
                        for (var address in result) {
                            if (!result[address]) {
                                missingKeys.push(address);
                            }
                        }

                        if (missingKeys.length) {
                            reject(String.format(
                                me.app.i18n._('Cannot encrypt message. Public keys for the following recipients are missing: {0}'),
                                Ext.util.Format.htmlEncode(missingKeys.join(', '))
                            ));
                        } else {
                            // NOTE: we sync message here as we have a promise at hand and onRecordUpdate is done before validation
                            return me.mailvelopeEditor.encrypt(all).then(function (armoredMessage) {
                                me.record.set('body', armoredMessage);
                                me.record.set('content_type', 'text/plain');
                                // NOTE: Server would spoil MIME structure with attachments
                                me.record.set('attachments', '');
                                me.record.set('has_attachment', false);
                                fulfill(true);
                            });
                        }
                    });
                });
            } else {
                fulfill(true);
            }
        });
    },

    /**
     * get validation error message
     *
     * @return {String}
     */
    getValidationErrorMessage: function () {
        return this.validationErrorMessage;
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
        width: 700,
        height: 700,
        name: Tine.Felamimail.MessageEditDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Felamimail.MessageEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
