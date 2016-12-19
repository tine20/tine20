/*
 * Tine 2.0
 *
 * @package     Expressomail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Expressomail');

/**
 * @namespace   Tine.Expressomail
 * @class       Tine.Expressomail.GridDetailsPanel
 * @extends     Tine.widgets.grid.DetailsPanel
 *
 * <p>Message Grid Details Panel</p>
 * <p>the details panel (shows message content)</p>
 *
 * TODO         replace telephone numbers in emails with 'call contact' link
 * TODO         make only text body scrollable (headers should be always visible)
 * TODO         show image attachments inline
 * TODO         add 'download all' button
 * TODO         'from' to contact: check for duplicates
 *
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Expressomail.GridDetailsPanel
 */
Tine.Expressomail.GridDetailsPanel = Ext.extend(Tine.widgets.grid.DetailsPanel, {

    /**
     * config
     * @private
     */
    defaultHeight: 350,
    currentId: null,
    record: null,
    app: null,
    i18n: null,
    isNavKey: false,
    isDecrypting: false,

    fetchBodyTransactionId: null,

    // model generics
    recordClass: Tine.Expressomail.Model.Rule,
    recordProxy: Tine.Expressomail.rulesBackend,

    /**
     * init
     * @private
     */
    initComponent: function() {
        this.initTemplate();
        this.initDefaultTemplate();
        //this.initTopToolbar();

        Tine.Expressomail.GridDetailsPanel.superclass.initComponent.call(this);
    },

    /**
     * use default Tpl for default and multi view
     */
    initDefaultTemplate: function() {
        this.defaultTpl = new Ext.XTemplate(
            '<div class="preview-panel-expressomail">',
                '<div class="preview-panel-expressomail-body">{[values ? values.msg : ""]}</div>',
            '</div>'
        );
    },

    /**
     * init bottom toolbar (needed for event invitations atm)
     *
     * TODO add buttons (show header, add to addressbook, create filter, show images ...) here
     */
//    initTopToolbar: function() {
//        this.tbar = new Ext.Toolbar({
//            hidden: true,
//            items: []
//        });
//    },

    /**
     * add on click event after render
     * @private
     */
    afterRender: function() {
        Tine.Expressomail.GridDetailsPanel.superclass.afterRender.apply(this, arguments);
        this.body.on('click', this.onClick, this);
    },

    fromApplet: function(data)
    {
        if (data) {
            // TODO: Change signedMessage to emlTosend
            this.record.set('eid', data.eid);
            this.record.set('body', data.body);
            this.record.set('attachments', data.attachments);
            this.record.set('decrypted', true);
            this.record.set('has_signature', data.has_signature);
            this.record.set('signature_info', data.signature_info);
            if (data.has_signature === true && !data.signature_info) {
                Tine.Tinebase.DigitalCertificateBackend.verifyCertificates(new Array(data.signatures[0].certificate),
                    {
                        callback: this.afterValidation.createDelegate(this)
                    });
            } else {
                this.updateDetails(this.record, '', true);
            }
        } else {
            Tine.log.debug('Operation Canceled!');
            this.loadMask.hide();
        }

    },

    afterValidation: function(request, success, response) {
        var data;
        if (success) {
            data = Ext.util.JSON.decode(response.responseText);
            // We should have only one certificate signing the message
            this.record.set('signature_info', {
                success: data.results[0].success,
                certificate: data.results[0].certificate,
                msgs: data.results[0].messages
            });
            this.fromApplet(this.record.data);
        } else {
            // TODO: Error message?
        }
    },

    /**
     * get panel for single record details
     *
     * @return {Ext.Panel}
     */
    getSingleRecordPanel: function() {
        if (! this.singleRecordPanel) {
            this.singleRecordPanel = new Ext.Panel({
                layout: 'vbox',
                layoutConfig: {
                    align:'stretch'
                },
                border: false,
                items: [
                    //this.getTopPanel(),
                    this.getMessageRecordPanel()
                    //this.getBottomPanel()
                ]
            });
        }
        return this.singleRecordPanel;
    },

    /**
     * get panel for single record details
     *
     * @return {Ext.Panel}
     */
    getMessageRecordPanel: function() {
        if (! this.messageRecordPanel) {
            this.messageRecordPanel = new Ext.Panel({
                border: false,
                autoScroll: true,
                flex: 1
            });
        }
        return this.messageRecordPanel;
    },

    /**
     * update details panel
     *
     * @param {Ext.grid.RowSelectionModel} sm
     */
    onDetailsUpdate: function(sm) {
        Tine.Expressomail.GridDetailsPanel.superclass.onDetailsUpdate.call(this, sm);
        this.scrollPreviewToTop();
    },

    /**
     * scroll preview panel to top
     */
    scrollPreviewToTop: function() {
        var els = this.getMessageRecordPanel().getEl().query('div[class=preview-panel-expressomail]');
        if (els) {
            els[0].parentElement.scrollTop = 0;
        }
    },

    /**
     * (on) update details
     *
     * @param {Tine.Expressomail.Model.Message} record
     * @param {String} body
     * @private
     */
    updateDetails: function(record, body) {
        var encrypted = record.get('smimeEml') != '';
        var decrypted = record.get('decrypted') || false;
        if (record.id === this.currentId) {
            // nothing to do
        } else if (! record.bodyIsFetched()) {
            this.waitForContent(record, this.getMessageRecordPanel().body);
        } else if (encrypted) {
            if (!decrypted && !this.isNavKey) {
                this.isDecrypting = true;
                Tine.Expressomail.addSecurityApplet('SecurityApplet'); // Add applet if not already
                Tine.Expressomail.toSecurityApplet(this.id, Ext.util.JSON.encode(record.data), 'DECRYPT'); // call applet
            } else {
                // fromApplet

                // TODO: set signature, attachments, etc
                this.setTemplateContent(record, this.getMessageRecordPanel().body);
                this.fireEvent('dblwindow');
                this.scrollPreviewToTop();
            }
            this.isNavKey = false;
        } else if (record === this.record) {
            this.setTemplateContent(record, this.getMessageRecordPanel().body);
            this.fireEvent('dblwindow');
            this.scrollPreviewToTop();
        }
    },

    /**
     * wait for body content
     *
     * @param {Tine.Expressomail.Model.Message} record
     * @param {String} body
     */
    waitForContent: function(record, body) {
        if (! this.grid || this.grid.getSelectionModel().getCount() == 1) {
            this.refetchBody(record, {
                success: this.updateDetails.createDelegate(this, [record, body]),
                failure: function (exception) {
                    Tine.log.debug(exception);
                    this.getLoadMask().hide();
                    if (exception.code == 404) {
                        this.defaultTpl.overwrite(body, {msg: this.app.i18n._('Message not available.')});
                    } else {
                        Tine.Expressomail.messageBackend.handleRequestException(exception);
                    }
                },
                scope: this
            });
            this.defaultTpl.overwrite(body, {msg: ''});
            this.getLoadMask().show();
            this.fireEvent('dblwindow');
        } else {
            this.getLoadMask().hide();
        }
    },

    /**
     * refetch message body
     *
     * @param {Tine.Expressomail.Model.Message} record
     * @param {Function} callback
     */
    refetchBody: function(record, callback) {
        // cancel old request first
        if (this.fetchBodyTransactionId && ! Tine.Expressomail.messageBackend.isLoading(this.fetchBodyTransactionId)) {
            Tine.log.debug('Tine.Expressomail.GridDetailsPanel::refetchBody -> cancelling current fetchBody request.');
            Tine.Expressomail.messageBackend.abort(this.fetchBodyTransactionId);
        }
        Tine.log.debug('Tine.Expressomail.GridDetailsPanel::refetchBody -> calling fetchBody');
        this.fetchBodyTransactionId = Tine.Expressomail.messageBackend.fetchBody(record, callback);
    },

    /**
     * overwrite template with (body) content
     *
     * @param {Tine.Expressomail.Model.Message} record
     * @param {String} body
     *
     * TODO allow other prepared parts than email invitations
     */
    setTemplateContent: function(record, body) {
        var encrypted = record.get('smimeEml') != '';
        this.currentId = record.id;
        this.getLoadMask().hide();

        this.doLayout();

        this.tpl.overwrite(body, record.data);
        this.getEl().down('div').down('div').scrollTo('top', 0, false);

        if (this.record.get('preparedParts') && this.record.get('preparedParts').length > 0) {
            Tine.log.debug('Tine.Expressomail.GridDetailsPanel::setTemplateContent about to handle preparedParts');
            this.handlePreparedParts(record);
        }

        if (!Tine.Tinebase.registry.get('preferences').get('windowtype')=='Browser') {
            this.app.mainScreen.GridPanel.reloadActionToolbar(encrypted);
        }
    },

    /**
     * init single message template (this.tpl)
     * @private
     */
    showErrorTemplate: function(error_message) {
        this.record.data.error_message = error_message;
        this.setTemplateContent(this.record, this.getMessageRecordPanel().body);
    },

    /**
     * handle invitation messages (show top + bottom panels)
     *
     * @param {Tine.Expressomail.Model.Message} record
     */
    handlePreparedParts: function(record) {
        var firstPreparedPart = this.record.get('preparedParts')[0],
            mimeType = String(firstPreparedPart.contentType).split(/[ ;]/)[0];

        // hack for "browser style" popups
        if(!Tine.Expressomail.MimeDisplayManager.getMainType(mimeType)){
            Tine.Expressomail.MimeDisplayManager.register('text/calendar', Tine.Calendar.iMIPDetailsPanel);
            Tine.Expressomail.MimeDisplayManager.register('text/readconf', Tine.Expressomail.ReadConfirmationDetailsPanel);
        }

        var mainType = Tine.Expressomail.MimeDisplayManager.getMainType(mimeType);

        if (! mainType) {
            Tine.log.info('Tine.Expressomail.GridDetailsPanel::handlePreparedParts nothing found to handle ' + mimeType);
            return;
        }

        var bodyEl = this.getMessageRecordPanel().getEl().query('div[class=preview-panel-expressomail-body]')[0],
            detailsPanel = Tine.Expressomail.MimeDisplayManager.create(mainType, {
                    preparedPart: firstPreparedPart
                });

        // quick hack till we have a card body here
        Ext.fly(bodyEl).update('');
        detailsPanel.render(bodyEl);
    },

    /**
     * init single message template (this.tpl)
     * @private
     */
    initTemplate: function() {

        this.tpl = new Ext.XTemplate(
            '<div class="preview-panel-expressomail">',
                '<div class="preview-panel-expressomail-headers {[(values.smimeEml ? "x-html-editor-encrypted" : "x-html-editor-decrypted")]}">',
                    '<b>' + this.i18n._('Subject') + ':</b> {[this.encode(values.subject)]}<br/>',
                    '<b>' + this.i18n._('From') + ':</b>',
                    ' {[this.showFrom(values.from_email, values.from_name, "' + this.i18n._('Add') + '", "'
                        + this.i18n._('Add contact to addressbook') + '")]}<br/>',
                    '<b>' + this.i18n._('Date') + ':</b> {[this.showDate(values.sent, values)]}',
                    '{[this.showRecipients(values.headers)]}',
                    '{[this.showHeaders("' + this.i18n._('Show or hide header information') + '")]}',
                '</div>',
                '<div class="preview-panel-felamimail-signature">{[this.showSignatureInfo(values.signature_info, values)]}</div>',
                '<div class="preview-panel-expressomail-attachments">{[this.showAttachments(values.attachments, values)]}</div>',
                '<div class="preview-panel-expressomail-body">{[this.showBody(values.body, values)]}</div>',
                '{[this.showErrorMessage(values.error_message, values)]}',
                '{[this.showDecryptButton(values)]}',
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
                return value;
            },

            showDate: function(sent, messageData) {
                var date = (sent) ? sent : messageData.received;
                return Tine.Tinebase.common.dateTimeRenderer(date);
            },

            showFrom: function(email, name, addText, qtip) {
                if (name === null) {
                    return '';
                }

                var result = this.encode(name + ' <' + email + '>');

                // add link with 'add to contacts'
                var id = Ext.id() + ':' + email;

                var nameSplit = name.match(/^"*([^,^ ]+)(,*) *(.+)/i);
                var firstname = (nameSplit && nameSplit[1]) ? nameSplit[1] : '';
                var lastname = (nameSplit && nameSplit[3]) ? nameSplit[3] : '';
                if (nameSplit && nameSplit[2] == ',') {
                    firstname = lastname;
                    lastname = nameSplit[1];
                }

                id += Ext.util.Format.htmlEncode(':' + Ext.util.Format.trim(firstname) + ':' + Ext.util.Format.trim(lastname));
                result += ' <span ext:qtip="' + Tine.Tinebase.common.doubleEncode(qtip) + '" id="' + id + '" class="tinebase-addtocontacts-link">[+]</span>';
                                //if no sieve hostname is defined, block sender feature is not enabled
                var account = this.app.getActiveAccount(),
                    sieve_hostname = account.get('sieve_hostname');
                if( sieve_hostname && (sieve_hostname !== null || sieve_hostname !== '') )
                {
                    result += ' <span ext:qtip="Bloquear remetente" id="' + id + '" class="expressomail-addsievefilter-link">[x]</span>';
                }
                return result;
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
                    }
                }
                return body;
            },

            showHeaders: function(qtip) {
                var result = ' <span ext:qtip="' + Tine.Tinebase.common.doubleEncode(qtip) + '" id="' + Ext.id() + ':show" class="tinebase-showheaders-link">[...]</span>';
                return result;
            },

            showRecipients: function(value) {
                if (value) {
                    var i18n = Tine.Tinebase.appMgr.get('Expressomail').i18n,
                        result = '';
                    for (header in value) {
                        if (value.hasOwnProperty(header) && (header == 'to' || header == 'cc' || header == 'bcc')) {
                            result += '<br/><b>' + i18n._hidden(Ext.util.Format.capitalize(header)) + ':</b> '
                                + Ext.util.Format.htmlEncode(value[header]).replace(/,(?!\s)/g,', ');
                        }
                    }
                    return result;
                } else {
                    return '';
                }
            },

            showAttachments: function(attachments, messageData) {

                if(attachments.length == 0) return '';

                var result = '<b> ' + this.app.i18n._('Attachments') + ':</b> ';

                var encrypted = messageData.smimeEml != '';

                for(var i=0; i < attachments.length; i++) {
                    if(encrypted) {
                        result +=
                            '<a id="' + Ext.id() + ':' + i +
                                '" href="https://local.expressov3.serpro.gov.br:8998/download/' + attachments[i].eid + '" target="_blank"> ' +
                                '<i>' + attachments[i].filename + '</i> ' +
                                '(' + Ext.util.Format.fileSize(attachments[i].size) + ')</a>  ';
                    }
                    else {
                        result +=
                            '<span id="' + Ext.id() + ':' + i +
                                '" class="tinebase-download-link"> ' +
                                '<i>' + attachments[i].filename.replace(/[a-zA-Z]:[\\\/]fakepath[\\\/]/, '') + '</i> ' +
                                '(' + Ext.util.Format.fileSize(attachments[i].size) + ')</span>  ';
                    }
                }

                if(attachments.length > 1) {
                    if(encrypted) {
                        result +=
                            '<a ext:qtip="' + this.app.i18n._('Download all attachments') +
                                '" id="' + Ext.id() + ':A" ' +
                                'href="https://local.expressov3.serpro.gov.br:8998/downloadZip/' + messageData.eid + '" target="_blank"> ' +
                                '[' + this.app.i18n._('All attachments') + ']</a>';
                    }
                    else {
                        result +=
                            '<span ext:qtip="' + this.app.i18n._('Download all attachments') +
                                '" id="' + Ext.id() + ':A" ' +
                                'class="tinebase-download-link"> ' +
                                '[' + this.app.i18n._('All attachments') + ']</span>';
                    }
                }
                else if(attachments.length == 1 && attachments[0].filename.substring(attachments[0].filename.length-4).toUpperCase() == '.EML') {
                    result +=
                        '<span ext:qtip="' + this.app.i18n._('Download attachment') +
                            '" id="' + Ext.id() + ':A" ' +
                            'class="tinebase-download-link">' +
                            '[' + this.app.i18n._('Download attachment') + ']</span>';
                }

                return result;
            },

            showSignatureInfo: function(signature_info, messageData) {
                var result = signature_info ? '<b>' + Ext.util.Format.htmlEncode(this.app.i18n._('Digital Signature')) + ':</b> ' : '';

                if (signature_info)
                {
                    if (signature_info.success)
                    {
                        result += Ext.util.Format.htmlEncode(this.app.i18n._('Verification Successful!'));

                    } else {
                        result += Ext.util.Format.htmlEncode(this.app.i18n._('Verification Failed!'));
                    }

                    result += ' <a ext:qtip="'+
                        Ext.util.Format.htmlEncode(this.app.i18n._('show details of digitial signature verification')) +
                        '" class="expressomail-smime-details">[...]</a>';
                }

                return result;
            },

            showErrorMessage: function(error_message, messageData) {
                var div = '<div class="preview-panel-error-message x-html-editor-encrypted-noicon">';
                var result = error_message ? div + '<p><b>' + Ext.util.Format.htmlEncode(error_message) + '</b></p></div>' : '';

                return result;
            },

            showDecryptButton: function(messageData) {
                if (messageData.smimeEml && !messageData.decrypted) {
                    result = '<div class="preview-panel-error-message x-html-editor-encrypted-noicon"><p><b>' +
                             '<a href="#" class="expressomail-decrypt-message">' + this.app.i18n._('Click here or press ENTER to decypher this message.') + '</a></b></p></div>';
                }
                else {
                    result = '';
                }

                return result;
            }
        });
    },

    reloadDetails: function() {
        // force the reload of currently selected message
        this.getLoadMask().show();
        this.currentId = null;
        this.updateDetails(this.record, this.getSingleRecordPanel().body);
    },

    blockSender: function(){

        var transResponse = Ext.util.JSON.decode(this.recordProxy.transId.conn.response);

        if(typeof(transResponse == 'undefined')) //IE 9 native type handler
            transResponse = Ext.util.JSON.decode(this.recordProxy.transId.conn.responseText);

        var resultCount = transResponse.result.totalcount;

        var defaultRule = [],
            result = [],
            condition,
            rules = [];

        for (i = 0; i < resultCount; i++ )
        {
            var idTmp = i + 1;
            transResponse.result.results[i].id = String(idTmp);
            rules.push(transResponse.result.results[i]);

//            rules[i].set('id', String(idTmp));

        }

        var defaultRule = new Tine.Expressomail.Model.Rule( Tine.Expressomail.Model.Rule.getDefaultData(),Ext.id() );

        defaultRule.set('action_type', 'discard');

        defaultRule.set('account_id', '');
        defaultRule.set('container_id', '');
        defaultRule.set('created_by', '');
        defaultRule.set('creation_time', '');
        defaultRule.set('deleted_by', '');
        defaultRule.set('deleted_time', '');

        var id = resultCount + 1;
        defaultRule.set('id', String(id));
        defaultRule.set('last_modified_by', '');
        defaultRule.set('last_modified_time', '');

        //var result = [], condition;
        condition = {
            test: 'address',
            header: 'from',
            comperator: 'contains',
            key: this.record.json.from_email
        };

        result.push(condition);

        defaultRule.set('conditions', result);

        //var rules = [];
        rules.push(defaultRule.data);

        Tine.Expressomail.rulesBackend.saveRules(this.record.json.account_id, rules, {
            scope: this,
            success: function(record) {
                    this.purgeListeners();
            },
            failure: Tine.Expressomail.handleRequestException.createSequence(function() {
                this.loadMask.hide();
            }, this),
            timeout: 150000
        });
    },

    certificateDetails: function(certificate) {

    },

    /**
     * on click for attachment download / compose dlg / edit contact dlg
     *
     * @param {} e
     * @private
     */
    onClick: function(e) {
        var selectors = [
            'a[class=expressomail-decrypt-message]',
            'span[class=tinebase-download-link]',
            'a[class=tinebase-email-link]',
            'a[class=expressomail-smime-details]',
            'span[class=tinebase-addtocontacts-link]',
            'span[class=tinebase-showheaders-link]',
            'span[class=expressomail-addsievefilter-link]'
        ];

        // find the correct target
        for (var i=0, target=null, selector=''; i < selectors.length; i++) {
            target = e.getTarget(selectors[i]);
            if (target) {
                selector = selectors[i];
                break;
            }
        }

        Tine.log.debug('Tine.Expressomail.GridDetailsPanel::onClick found target:"' + selector + '".');

        switch (selector) {
            case 'a[class=expressomail-decrypt-message]':
                this.reloadDetails();
                break;

            case 'span[class=tinebase-download-link]':
                var idx = target.id.split(':')[1],
                    attachment = this.record.get('attachments')[idx];

                if (! this.record.bodyIsFetched()) {
                    // sometimes there is bad timing and we do not have the attachments available -> refetch body
                    this.refetchBody(this.record, this.onClick.createDelegate(this, [e]));
                    return;
                }

                // remove part id if set (that is the case in message/rfc822 attachments)
                var messageId;

                if(idx == 'A'){
                    messageId = this.record.id;
                    new Ext.ux.file.Download({
                        params: {
                            requestType: 'HTTP',
                            method: 'Expressomail.downloadAttachment',
                            messageId: messageId,
                            partId: 'A',
                            getAsJson: false
                        }
                    }).start();
                } else {
                    messageId = (this.record.id.match(/_/)) ? this.record.id.split('_')[0] : this.record.id;
                    if (attachment['content-type'] === 'message/rfc822') {

                        Tine.log.debug('Tine.Expressomail.GridDetailsPanel::onClick openWindow for:"' + messageId + '_' + attachment.partId + '".');
                        // display message
                        Tine.Expressomail.MessageDisplayDialog.openWindow({
                            record: new Tine.Expressomail.Model.Message({
                                id: messageId + '_' + attachment.partId
                            })
                        });

                    } else {
                        // download attachment
                        new Ext.ux.file.Download({
                            params: {
                                requestType: 'HTTP',
                                method: 'Expressomail.downloadAttachment',
                                messageId: messageId,
                                partId: attachment.partId,
                                getAsJson: false
                            }
                        }).start();
                    }
                }
                break;

            case 'a[class=expressomail-smime-details]':
                var sigInfo = this.record.get('signature_info');
                var msg = '';

                if (typeof(sigInfo.certificate) == 'object') {
                    msg += '<span style="float:left;width:10em;text-align:left;font-weight:bold">'+
                            this.app.i18n._('Serial Number')+': </span> ' +
                            '<span>'+sigInfo.certificate.serialNumber+'</span><br />';
                    msg += '<span style="float:left;width:10em;text-align:left;font-weight:bold">'+
                            this.app.i18n._('Issuer')+': </span> ' +
                            '<span>'+sigInfo.certificate.issuerCn+'</span><br />';
                    msg += '<span style="float:left;width:10em;text-align:left;font-weight:bold">'+
                            this.app.i18n._('Owner')+': </span> ' +
                            '<span>'+sigInfo.certificate.cn+'</span><br />';
                    msg += '<span style="float:left;width:10em;text-align:left;font-weight:bold">'+
                            this.app.i18n._('Email')+': </span> ' +
                            '<span>'+sigInfo.certificate.email+'</span><br />';
                    msg += '<span style="float:left;width:10em;text-align:left;font-weight:bold">'+
                            this.app.i18n._('Valid From')+': </span> ' +
                            '<span>'+sigInfo.certificate.validFrom+'</span><br />';
                    msg += '<span style="float:left;width:10em;text-align:left;font-weight:bold">'+
                            this.app.i18n._('Valid To')+': </span> ' +
                            '<span>'+sigInfo.certificate.validTo+'</span><br />';
                }

                if (!sigInfo.success)  {
                    msg += '<br /><span style="font-weight:bold">'+this.app.i18n._('Errors')+': </span><br />';
                    for (var j = 0; j < sigInfo.msgs.length; j++) {
                        msg += '<span style="padding-left:6em;color:#FF0000">'+this.app.i18n._(sigInfo.msgs[j])
                            +'</span><br />';
                    }
                }

                Ext.MessageBox.show({
                    title:      this.app.i18n._('Verification Details'),
                    msg:        msg,
                    buttons:    Ext.Msg.OK,
                    width:      500
                });
                break;

            case 'a[class=tinebase-email-link]':
                // open compose dlg
                // support RFC 6068
                var mailto = target.id.split('123:')[1],
                    to = typeof mailto.split('?')[0] != 'undefined' ? mailto.split('?')[0].split(',') : [],
                    fields = typeof mailto.split('?')[1] != 'undefined' ? mailto.split('?')[1].split('&') : [],
                    subject = '', body = '', cc = [], bcc = [];

                var defaults = Tine.Expressomail.Model.Message.getDefaultData();

                Ext.each(fields, function(field){
                    var test = field.split('=');
                    switch (Ext.util.Format.lowercase(test[0]))
                    {
                        case 'subject':
                            subject = decodeURIComponent(test[1]);
                            break;
                        case 'body':
                            test[1] = test[1].replace(/%0A/g, '<br />'); // adding line breaks
                            body = decodeURIComponent(test[1]);
                            break;
                        case 'to':
                            to = Ext.isEmpty(to) ? test[1].split[','] : to;
                        case 'cc':
                            cc = Ext.isEmpty(cc) ? test[1].split[','] : cc;
                            break;
                        case 'bcc':
                            bcc = Ext.isEmpty(bcc) ? test[1].split[','] : bcc;
                            break;
                    }
                });

                defaults.to = to;
                defaults.cc = cc;
                defaults.bcc = bcc;
                defaults.subject = subject;
                defaults.body = body + Tine.Expressomail.getSignature();

                var record = new Tine.Expressomail.Model.Message(defaults, 0);
                var popupWindow = Tine.Expressomail.MessageEditDialog.openWindow({
                    record: record
                });
                break;

            case 'span[class=tinebase-addtocontacts-link]':
                // open edit contact dlg

                // check if addressbook app is available
                if (! Tine.Addressbook || ! Tine.Tinebase.common.hasRight('run', 'Addressbook')) {
                    return;
                }

                var id = Ext.util.Format.htmlDecode(target.id);
                var parts = id.split(':');

                var popupWindow = Tine.Addressbook.ContactEditDialog.openWindow({
                    listeners: {
                        scope: this,
                        'load': function(editdlg) {
                            editdlg.record.set('email', parts[1]);
                            editdlg.record.set('n_given', parts[2]);
                            editdlg.record.set('n_family', parts[3]);
                        }
                    }
                });

                break;

               case 'span[class=expressomail-addsievefilter-link]':

                var id = Ext.util.Format.htmlDecode(target.id);
                var parts = id.split(':');

                var account = this.app.getActiveAccount();

                //var id = Ext.id() + ':' + email;
                Ext.MessageBox.confirm(
                    this.app.i18n._('Block Sender'),
                    this.app.i18n._('Block incoming messages from this sender?'),
                    function (button) {
                        if (button == 'yes') {
                            Tine.Expressomail.rulesBackend.getRules(account.id, {
                                scope: this,
                                success: function(record) {
                                        this.blockSender();
                                        this.purgeListeners();
                                },
                                failure: Tine.Expressomail.handleRequestException.createSequence(function() {
                                    this.loadMask.hide();
                                }, this),
                                timeout: 6000,
                                parts: parts[1]

                            });
                        }
                    },
                    this
                );
                break;

            case 'span[class=tinebase-showheaders-link]':
                // show headers

                var parts = target.id.split(':');
                var targetId = parts[0];
                var action = parts[1];

                var html = '';
                if (action == 'show') {
                    var recordHeaders = this.record.get('headers');

                    for (header in recordHeaders) {
                        if (recordHeaders.hasOwnProperty(header) && (header != 'to' || header != 'cc' || header != 'bcc')) {
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
        }
    }
});
