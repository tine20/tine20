/*
 * Tine 2.0
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2017-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import waitFor from "../../Tinebase/js/util/waitFor.es6";

Ext.ns('Tine.Felamimail');

import getFileAttachmentAction from './AttachmentFileAction';

/**
 * @param config
 * @constructor
 */
Tine.Felamimail.MailDetailsPanel = function(config) {
    Ext.apply(this, config);
    Tine.Felamimail.MailDetailsPanel.superclass.constructor.call(this);
};

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.MailDetailsPanel
 * @extends     Ext.Panel
 *
 * TODO         replace telephone numbers in emails with 'call contact' link
 * TODO         make only text body scrollable (headers should be always visible)
 * TODO         show image attachments inline
 * TODO         add 'download all' button
 * TODO         'from' to contact: check for duplicates
 */
Ext.extend(Tine.Felamimail.MailDetailsPanel, Ext.Panel, {

    /**
     * layout stuff
     */
    layout: 'vbox',
    layoutConfig: {
        align:'stretch'
    },
    border: false,

    record: null,
    app: null,
    i18n: null,

    // if this is given, we load the record from a node
    nodeRecord: null,

    // if this is true, add top toolbar with actions to open mail in message display dialog
    hasTopToolbar: true,

    initComponent: function () {
        this.app = Tine.Tinebase.appMgr.get('Felamimail');
        this.i18n = this.app.i18n;
        this.messageRecordPanel = new Ext.Panel({
            border: false,
            autoScroll: true,
            flex: 1
        });
        this.items = [
            this.messageRecordPanel
        ];

        this.initTemplate();

        if (this.hasTopToolbar) {
            this.initTopToolbar();
        }

        Tine.Felamimail.MailDetailsPanel.superclass.initComponent.call(this);
    },

    /**
     * init top toolbar for opening mails in fmail
     */
   initTopToolbar: function() {
        this.action_openInFmail = new Ext.Action({
            text: this.app.i18n._('Open in Felamimail'),
            minWidth: 70,
            scope: this,
            handler: this.onOpenInFmail,
            iconCls: this.app.appName + 'IconCls'
        });

       this.tbar = new Ext.Toolbar({
           items: [
               '->',
               this.action_openInFmail
           ]
       });
   },

    /**
     * open in Felamimail MessageDisplayDialog
     */
    onOpenInFmail: function() {
        if (this.nodeRecord) {
            // prepare message for forwarding in Tine.Felamimail.MessageEditDialog.handleAttachmentsOfExistingMessage
            this.record.set('from_node', this.nodeRecord.data);
        }

        Tine.Felamimail.MessageDisplayDialog.openWindow({
            record: this.record,
            // remove delete + save actions as this makes no sense if opened from another app
            hasDeleteAction: false,
            hasDownloadAction: false
        });
    },

    /**
     * add on click event after render
     * @private
     */
    afterRender: async function () {
        Tine.Felamimail.MailDetailsPanel.superclass.afterRender.apply(this, arguments);
        this.body.on('click', this.onClick, this);
        if (this.nodeRecord) {
            await this.loadRecord();
        }
    },

    getTemplateBody: function () {
        return this.messageRecordPanel.body;
    },

    getMessageRecordPanel: function() {
        return this.messageRecordPanel;
    },

    /**
     * fills this fields with the corresponding message data
     *
     * @param {Tine.Tinebase.data.Record|Object} record
     */
    loadRecord: async function (record) {
        if (record) {
            this.record = record;
            this.tpl.overwrite(this.messageRecordPanel.body, record.data);
            this.doLayout();

            // prefill attachmentCache, all attachments cache got saved the first time, to filemanager_attachmentcache
            const attachments = this.resolveAttachmentCache('Felamimail_Model_Message', this.record);
            this.record.set('attachments', attachments, this.record);
        } else if (this.nodeRecord) {
            await Tine.Felamimail.getMessageFromNode(this.nodeRecord.id)
                .then(async (response) => {
                    // this.record.id is not the original messageId, we can not get attachment from this record
                    this.record = Tine.Felamimail.messageBackend.recordReader({responseText: Ext.util.JSON.encode(response)});
                    this.tpl.overwrite(this.messageRecordPanel.body, this.record.data);
                    // prefill attachmentCache, all attachments cache got saved the first time, to filemanager_attachmentcache
                    //fixme: attachment cache created from Filemanager_Model_Node has size 0
                    const attachments = this.resolveAttachmentCache('Filemanager_Model_Node', this.record);
                    this.record.set('attachments', attachments, this.record);
                }).catch((exception) => {
                    Tine.log.debug(exception);
                    Tine.log.debug(exception);
                    // @todo add loadMask? move loadMask from GridDetailsPanel here?
                    // this.getLoadMask().hide();
                    // if (exception.code == 404) {
                    this.tpl.overwrite(this.messageRecordPanel.body, {msg: this.app.i18n._('Message not available.')});
                    // } else {
                    //     // @todo handle exception?
                    // }
                });
        }
    },

    /**
     * init single message template (this.tpl)
     * @private
     */
    initTemplate: function() {

        this.tpl = new Ext.XTemplate(
            '{[this.showSpamToolbar(values)]}',
            '<div class="preview-panel-felamimail">',
            '<div class="preview-panel-felamimail-headers">',
            '<b>' + this.i18n._('Subject') + ':</b> {[this.encode(values.subject)]}<br/>',
            '<b>' + this.i18n._('From') + ':</b>',
            ' {[this.showFrom(values.from_email, values.from_name, "' + this.i18n._('Add') + '", "'
            + this.i18n._('Add contact to addressbook') + '")]}<br/>',
            '<b>' + this.i18n._('Date') + ':</b> {[this.showDate(values.sent, values)]}',
            '{[this.showRecipients(values.headers)]}',
            '{[this.showHeaders("' + this.i18n._('Show or hide header information') + '")]}',
            '</div>',
            '<div class="preview-panel-felamimail-attachments">{[this.showAttachments(values.attachments, values)]}</div>',
            '<div class="preview-panel-felamimail-filelocations">{[this.showFileLocations(values)]}</div>',
            '<div class="preview-panel-felamimail-preparedPart"></div>',
            '<div class="preview-panel-felamimail-body">{[this.showBody(values.body, values)]}</div>',
            '</div>',{
                app: this.app,
                panel: this,
                encode: function(value) {
                    if (value) {
                        var encoded = Ext.util.Format.htmlEncode(value);
                        encoded = Ext.util.Format.nl2br(encoded);
                        // it should be enough to replace only 2 or more spaces
                        encoded = encoded.replace(/ /g, '&nbsp;');

                        return encoded;
                    } else {
                        return '';
                    }
                },
                showSpamToolbar: function(messageData) {
                    const app = Tine.Tinebase.appMgr.get('Felamimail');
                    const account = app.getAccountStore().getById(messageData.account_id);
                    const folder = app.getFolderStore().getById(messageData.folder_id);
                    if (!folder || !account || !messageData.is_spam_suspicions) return '';
                    
                    const html = '<span style="width: 60%; padding: 5px;">'
                        + app.i18n._('This message is probably SPAM. Please help to train your anti-SPAM system with a decision: "Yes, it is SPAM" or "No, it is not"') + '</span>';
                    const aboutAction = `<span id="action_about" class="felamimail-action"><span class="felamimail-location-icon action_about"></span></span>`;
                    const actions =
                        '<span id="spam_action_spam" class="felamimail-action"><span class="felamimail-location-icon felamimail-action-spam"></span><span>' + app.i18n._('Yes, it is SPAM') + '</span></span>' +
                        '<span id="spam_action_ham" class="felamimail-action"><span class="felamimail-location-icon felamimail-action-ham"></span><span>' + app.i18n._('No, it is not') + '</span></span>' ;
                    
                    return `<div id="spam_toolbar" class="felamimail_spam_suspicions_toolbar">${html}${aboutAction}<span>${actions}</span></div>`;
                },
                linkifyEmail(name, email) {
                    const id = Ext.id() + ':' + email + Ext.util.Format.htmlEncode(':' + Ext.util.Format.trim(name));
                    const address = name.length ? `${name} < ${email} >` : email;
                    return `<a id="${id}" class="tinebase-email-link">${address}</a>`;
                },
                showDate: function (sent, recordData) {
                    var date = sent
                        ? (Ext.isDate(sent) ? sent : Date.parseDate(sent, Date.patterns.ISO8601Long))
                        : Date.parseDate(recordData.received, Date.patterns.ISO8601Long);
                    return date ? date.format('l') + ', ' + Tine.Tinebase.common.dateTimeRenderer(date) : '';
                },
                showFrom: function(email, name, addText, qtip) {
                    if (! name) return '';
                    const emails = this.panel.record.get('from');
                    const fromEmail = emails[0] ?? [];
                    return this.linkifyEmail(fromEmail.name, fromEmail.email);
                },

                showBody: function(body, messageData) {
                    body = body || '';
                    if (body) {
                        var account = this.app.getActiveAccount();
                        if (account && (account.get('display_format') == 'plain' ||
                                (account.get('display_format') == 'content_type' && messageData.body_content_type == 'text/plain'))
                        ) {
                            var width = this.panel.body.getWidth()-25,
                                height = this.panel.body.getHeight()-90,
                                id = Ext.id();

                            if (height < 0) {
                                // sometimes the height is negative, fix this here
                                height = 500;
                            }

                            body = '<textarea ' +
                                'style="width: ' + width + 'px; height: ' + height + 'px; " ' +
                                'autocomplete="off" id="' + id + '" name="body" class="x-form-textarea x-form-field x-ux-display-background-border" readonly="" >' +
                                body + '</textarea>';
                        } else if (messageData.body_content_type != 'text/html' || messageData.body_content_type_of_body_property_of_this_record == 'text/plain') {
                            // message content is text and account format non-text
                            body = Ext.util.Format.nl2br(body);
                        } else {
                            Tine.Tinebase.common.linkifyText(body, function(linkified) {
                                var bodyEl = this.getMessageRecordPanel().getEl().query('div[class=preview-panel-felamimail-body]')[0];
                                Ext.fly(bodyEl).update(linkified);
                            }, this.panel);
                        }
                    }
                    return body;
                },

                showHeaders: function(qtip) {
                    var result = ' <span ext:qtip="' + Tine.Tinebase.common.doubleEncode(qtip) + '" id="' + Ext.id() + ':show" class="tinebase-showheaders-link">[...]</span>';
                    return result;
                },

                showRecipients: function(value) {
                    if (!value) return '';
                    
                    const i18n = Tine.Tinebase.appMgr.get('Felamimail').i18n;
                    let result = '';
                    for (const header in value) {
                        if (value.hasOwnProperty(header) && ['to', 'cc', 'bcc'].includes(header)) {
                            result += '<br/><b>' + i18n._hidden(Ext.util.Format.capitalize(header)) + ':</b> ';
                            const emails = this.panel.record.get(header);
                            //TODO: bcc only store email in \Zend_Mail::addBcc($email), do we want to change it ?
                            emails.forEach((emailData, idx) => {
                                if (idx > 0) result += ', ';
                                result += this.linkifyEmail(emailData?.name, emailData?.email);
                            })
                        }
                    }
                    return result;
                },

                showAttachments: function(attachments, messageData) {
                    const idPrefix = Ext.id();
                    const attachmentsStr = this.app.i18n._('Attachments');
                    if (!attachments) return '';
                    let result = (attachments.length > 0) ? `<span id=${idPrefix}:all class="tinebase-download-link tinebase-download-all"><b>${attachmentsStr}:</b><div class="tinebase-download-link-wait"></div></span>` : '';

                    for (var i=0, id, cls; i < attachments.length; i++) {
                        result += `<span id="${idPrefix}:${i}" class="tinebase-download-link">`
                            + '<i>' + attachments[i].filename + '</i>'
                            + ' (' + Ext.util.Format.fileSize(attachments[i].size) + ')<div class="tinebase-download-link-wait"></div></span> ';
                    }

                    return result;
                },

                showFileLocations: function(messageData) {
                    let fileLocations = _.get(messageData, 'fileLocations', []);

                    if (fileLocations.length) {
                        let app = Tine.Tinebase.appMgr.get('Felamimail');
                        let text = app.formatMessage('{locationCount, plural, one {This message is filed at the following location} other {This message is filed at the following locations}}: {locationsHtml}', {
                            locationCount: fileLocations.length,
                            locationsHtml: Tine.Felamimail.MessageFileAction.getFileLocationText(fileLocations, ', ')
                        });

                        return text;
                    } else {
                        return '';
                    }
                }
            });
    },

    /**
     * on click for attachment download / compose dlg / edit contact dlg
     *
     * @param {} e
     * @private
     */
    onClick: async function(e) {
        const selectors = [
            'span[class^=tinebase-download-link]',
            'a[class=tinebase-email-link]',
            'span[class=tinebase-showheaders-link]',
            'a[href^=#]',
            'span[class=felamimail-action]',
        ];

        // find the correct target
        const selectorIdx = _.findIndex(selectors, (sel) => {return e.getTarget(sel)});
        const selector = selectors[selectorIdx];
        const target = e.getTarget(selector);

        Tine.log.debug('Tine.Felamimail.GridDetailsPanel::onClick found target:"' + selector + '".');
        if (this.nodeRecord && !this.record.get('from_node')) {
            this.record.set('from_node', this.nodeRecord.data);
        }
        const sourceModel = this.record.get('from_node') ? 'Filemanager_Model_Node' : 'Felamimail_Model_Message';
        
        switch (selector) {
            case 'span[class^=tinebase-download-link]':
                if (! this.record.bodyIsFetched()) {
                    // sometimes there is bad timing and we do not have the attachments available -> refetch body
                    // @todo make this work again - move Tine.Felamimail.GridDetailsPanel.refetchBody here?
                    // this.refetchBody(this.record, this.onClick.createDelegate(this, [e]));
                    return;
                }
                // make sure we get the attachment caches
                const attachments = this.resolveAttachmentCache(sourceModel, this.record, true);
                const idx = target.id.split(':')[1];
                const selectedAttachments = idx !== 'all' ? [attachments[idx]] : attachments;
                // remove part id if set (that is the case in message/rfc822 attachments)
                const messageId = (this.record.id.match(/_/)) ? this.record.id.split('_')[0] : this.record.id;
                const menu = Ext.create({
                    xtype: 'menu',
                    plugins: [{
                        ptype: 'ux.itemregistry',
                        key:   'Tine.Felamimail.MailDetailPanel.AttachmentMenu'
                    }],
                    items: [{
                            text: this.app.i18n._('Open'),
                            iconCls: 'action_preview',
                            disabled: this.record.get('from_node'), // not implemented yet
                            hidden: selectedAttachments.length !== 1 || _.get(selectedAttachments, '[0]content-type') !== 'message/rfc822',
                            handler: () => {
                                // fixme: messageId might be filemanager node id
                                Tine.Felamimail.MessageDisplayDialog.openWindow({
                                    record: new Tine.Felamimail.Model.Message({
                                        id: messageId + '_' + selectedAttachments[0].partId
                                    })
                                });
                            }
                        }, {
                            text: this.app.i18n._('Preview'),
                            iconCls: 'action_preview',
                            hidden: !selectedAttachments.length 
                                || !Tine.Tinebase.appMgr.isEnabled('Filemanager')
                                || (_.get(selectedAttachments, '[0]content-type') === 'message/rfc822' && selectedAttachments.length === 1),
                            disabled: this.record.get('from_node'), // not implemented yet                           
                            handler: () => {
                                Tine.Filemanager.QuickLookPanel.openWindow({
                                    windowNamePrefix: `QuickLookPanel_${sourceModel}_Attachment_${idx}_`,
                                    record: this.record,
                                    initialApp: this.app,
                                    handleAttachments: this.quicklookHandleAttachments,
                                    sm: this.grid?.getGrid()?.getSelectionModel(),
                                    initialAttachmentIdx: idx !== 'all' ? +idx : 0,
                                });
                            }
                        }, {
                            xtype: 'menuseparator'
                        }, getFileAttachmentAction(async (locations, action, updatedAttachments = null) => {
                            const attachments = updatedAttachments ?? selectedAttachments;
                            
                            return await this.attachmentAnnimation(target,async () => {
                                if (locations === 'download') {
                                    Ext.ux.file.Download.start({
                                        params: {
                                            requestType: 'HTTP',
                                            method: 'Felamimail.downloadAttachments',
                                            id: messageId,
                                            partIds: _.map(attachments, 'partId'),
                                            model: sourceModel
                                        }
                                    });
                                } else {
                                    await Tine.Felamimail.fileAttachments(messageId, locations, attachments, sourceModel, true);
                                }
                                return attachments.length;
                            })

                        }, {
                            record: this.record,
                            attachments: selectedAttachments,
                        })
                    ]
                });
                
                const actionUpdater = new Tine.widgets.ActionUpdater({
                    recordClass: Tine.Felamimail.Model.Attachment,
                    evalGrants: false,
                    actions: menu
                });
                actionUpdater.updateActions(attachments.map((attachmentData) => {
                    return Tine.Tinebase.data.Record.setFromJson(Object.assign(attachmentData, {
                        id: `${messageId}:${attachmentData.partId}`,
                        messageId
                    }), Tine.Felamimail.Model.Attachment);
                }), messageId);

                menu.showAt(e.getXY());
                break;
            case 'span[class=tinebase-showheaders-link]':
                // show headers

                var parts = target.id.split(':');
                var targetId = parts[0];
                var action = parts[1];

                var html = '';
                if (action === 'show') {
                    const recordHeaders = this.record.get('headers');

                    for (let header in recordHeaders) {
                        if (recordHeaders.hasOwnProperty(header) && (header !== 'to' || header !== 'cc' || header !== 'bcc')) {
                            html += '<br/><b>' + header + ':</b> '
                                + Ext.util.Format.htmlEncode(recordHeaders[header]);
                        }
                    }

                    target.id = targetId + ':' + 'hide';

                } else {
                    html = ' <span ext:qtip="' + Ext.util.Format.htmlEncode(this.i18n._('Show or hide header information')) + '" id="'
                        + Ext.id() + ':show" class="tinebase-showheaders-link">[...]</span>'
                }

                target.innerHTML = html;

                break;
            case 'a[href^=#]':
                e.stopEvent();
                var anchor = this.getEl().query('#' + target.href.replace(/.*#/, ''));
                if (anchor.length) {
                    var scrollEl = Ext.fly(anchor[0]).findParent('.x-panel-body');
                    if (scrollEl) {
                        var box = Ext.fly(anchor[0]).getBox();
                        // TODO improve accuracy of scrolling
                        scrollEl.scrollTop = box.y - 180;
                    }
                }
                break;
            case 'span[class=felamimail-action]':
                const match = target.id.match(/^spam_action_(.*)/);
                if (match.length > 1) {
                    document.getElementById('spam_toolbar').remove();
                    this.app.getMainScreen().getCenterPanel().processSpamStrategy(match[1]);
                }
                if (target.id === 'action_about') {
                    Ext.Msg.alert(this.app.i18n._('Confirm SPAM Suspicion'),
                        Tine.Tinebase.configManager.get('spamInfoDialogContent', 'Felamimail')
                    );
                }
                break;
        }
    },
    
    resolveAttachmentCache(sourceModel, record, createPreviewInstantly = false) {
        const attachments = record.get('attachments');

        _.each(attachments, (attachment) => {
            if (!attachment.promises) attachment.promises = [];
            if (attachment?.isPreviewReady) return;
            const promise = new Promise(async (resolve) => {
                let validCache = null;
                await Promise.all(attachment.promises).then((responses) => {
                    validCache = responses.find((r) => {return r.isPreviewReady});
                });
                if (validCache) {
                    attachment.isPreviewReady = true;
                    return resolve(validCache);
                }
                const attachmentId = [sourceModel, record.id, attachment.partId].join(':');
                await Tine.Felamimail.getAttachmentCache(attachmentId, createPreviewInstantly)
                    .then(async (cache) => {
                        return resolve({
                            cache: new Tine.Tinebase.Model.Tree_Node(cache.attachments[0]),
                            createPreviewInstantly: createPreviewInstantly,
                            isPreviewReady: !!+cache.attachments[0].preview_count,
                        });
                    })
                    .catch((e) => {
                        console.error(e);
                        return resolve(attachment);
                    });
            })
            attachment.promises.push(promise);
        });
        record.set('attachments', attachments);
        return attachments;
    },

    // NOTE: runs in the scope of QuickLookPanel
    quicklookHandleAttachments: async function() {
        this.cardPanel.layout.setActiveItem(0); // wait cycle
        if (this.record?.isAttachmentCache) return; // key-nav
        // remove part id if set (that is the case in message/rfc822 attachments)
        const messageId = this.record.get('messageId') || ((String(this.record.id).match(/_/)) ? this.record.id.split('_')[0] : this.record.id);
        const sourceModel = this.record.get('from_node') ? 'Filemanager_Model_Node' : 'Felamimail_Model_Message';

        if (this.record.constructor.hasField('attachments')) {
            await waitFor(() => { return this.record.bodyIsFetched()});
            let attachments = this.record.get('attachments');
            if (! attachments.length) {
                // might happen with keyNav in preview panel
                this.record = new Tine.Tinebase.Model.Tree_Node({ name: 'Email has no attachemtns', path: '' });
                return;
            }
            
            // make sure we get the attachment caches
            attachments = Tine.Felamimail.MailDetailsPanel.prototype.resolveAttachmentCache(sourceModel, this.record, true);
            const initialAttachmentIdx = this.initialAttachmentIdx ?? 0;
            if (!attachments[initialAttachmentIdx].promises) return;
            await Promise.all(attachments[initialAttachmentIdx].promises).then((cachePromises) => {
                let resolvedAttachmentData = {};
                const validResponse = cachePromises.find((cachePromise) => {return cachePromise.isPreviewReady});
                if (validResponse) {
                    resolvedAttachmentData = validResponse.cache.data;
                } else {
                    const invalidResponse = cachePromises.find((cachePromise) => {return cachePromise?.cache});
                    if (invalidResponse) resolvedAttachmentData = invalidResponse.cache.data;
                }
                resolvedAttachmentData.id = `${this.record.id}:${attachments[initialAttachmentIdx].partId}`;
                resolvedAttachmentData.messageId = this.record.id;
                this.record = Tine.Tinebase.data.Record.setFromJson(resolvedAttachmentData, Tine.Tinebase.Model.Tree_Node);
                this.record.isAttachmentCache = true;
            })
        }
    },

    attachmentAnnimation: async function (target, workload) {
        Ext.fly(target).addClass('tinebase-download-link-anim');
        let result;
        try {
            result = await workload();
        } finally {
            Ext.fly(target).removeClass('tinebase-download-link-anim');
        }

        return result;
    },
});

Ext.reg('felamimaildetailspanel', Tine.Felamimail.MailDetailsPanel);
