/*
 * Tine 2.0
 *
 * @package     MailFiler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.MailFiler');

/**
 * @namespace   Tine.MailFiler
 * @class       Tine.MailFiler.MailDetailsPanel
 * @extends     Ext.Panel
 *
 * @todo: Improve this and use in Felamimail (this one is more configurable()
 */
Tine.MailFiler.MailDetailsPanel = Ext.extend(Ext.Panel, {
    record: null,
    app: null,
    appName: null,
    nodeRecord: null,

    initComponent: function () {
        this.app = Tine.Tinebase.appMgr.get(this.appName);
        Tine.MailFiler.MailDetailsPanel.superclass.initComponent.call(this);
    },

    /**
     * add on click event after render
     * @private
     */
    afterRender: function () {
        Tine.MailFiler.MailDetailsPanel.superclass.afterRender.apply(this, arguments);
        this.body.on('click', this.onClick, this);
    },

    /**
     * init single message template (this.tpl)
     */
    initTemplate: function () {
        var me = this;

        this.tpl = new Ext.XTemplate(
            '<div class="preview-panel-mail">',
            '<div class="preview-panel-mail-headers">',
            '<b>' + this.app.i18n._('Subject') + ':</b> {[this.encode(values.subject)]}<br/>',
            '<b>' + this.app.i18n._('From') + ':</b>',
            ' {[this.showFrom(values.from_email, values.from_name, "' + this.app.i18n._('Add') + '", "'
            + this.app.i18n._('Add contact to addressbook') + '")]}<br/>',
            '<b>' + this.app.i18n._('Date') + ':</b> {[this.showDate(values.sent, values)]}',
            '{[this.showRecipients(values.headers)]}',
            '{[this.showHeaders("' + this.app.i18n._('Show or hide header information') + '")]}',
            '</div>',
            '<div class="preview-panel-mail-attachments">{[this.showAttachments(values.attachments, values)]}</div>',
            '<div class="preview-panel-mail-body">{values.body}</div>',
            '</div>', {
                app: me.app,
                panel: me,
                encode: function (value) {
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

                showDate: function (sent, recordData) {
                    var date = sent ? Date.parseDate(sent, Date.patterns.ISO8601Long) : Date.parseDate(recordData.received, Date.patterns.ISO8601Long);
                    return date.format('l') + ', ' + Tine.Tinebase.common.dateTimeRenderer(date);
                },

                showFrom: function (email, name, addText, qtip) {
                    if (name === null || name === undefined) {
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
                    result = '<a id="' + id + '" class="tinebase-email-link">' + result + '</a>'
                    result += ' <span ext:qtip="' + Tine.Tinebase.common.doubleEncode(qtip) + '" id="' + id + '" class="tinebase-addtocontacts-link">[+]</span>';
                    return result;
                },

                showHeaders: function (qtip) {
                    var result = ' <span ext:qtip="' + Tine.Tinebase.common.doubleEncode(qtip) + '" id="' + Ext.id() + ':show" class="tinebase-showheaders-link">[...]</span>';
                    return result;
                },

                showRecipients: function (value) {
                    if (value) {
                        var result = '';
                        for (header in value) {
                            if (value.hasOwnProperty(header) && (header == 'to' || header == 'cc' || header == 'bcc')) {
                                result += '<br/><b>' + this.app.i18n._hidden(Ext.util.Format.capitalize(header)) + ':</b> '
                                    + Ext.util.Format.htmlEncode(value[header]);
                            }
                        }
                        return result;
                    } else {
                        return '';
                    }
                },

                showAttachments: function (attachments) {
                    var result = (attachments.length > 0) ? '<b>' + this.app.i18n._('Attachments') + ':</b> ' : '';

                    for (var i = 0, id; i < attachments.length; i++) {
                        result += '<span id="' + Ext.id() + ':' + i + '" class="tinebase-download-link">'
                            + '<i>' + attachments[i].filename + '</i>'
                            + ' (' + Ext.util.Format.fileSize(attachments[i].size) + ')</span> ';
                    }

                    return result;
                }
            });
        this.tpl.apply(this.record);
    },

    /**
     * on click for attachment download / compose dlg / edit contact dlg
     *
     * @private
     */
    onClick: function (e) {
        var selectors = [
            'span[class=tinebase-download-link]',
            'a[class=tinebase-email-link]',
            'span[class=tinebase-addtocontacts-link]',
            'span[class=tinebase-showheaders-link]'
        ];

        // find the correct target
        for (var i = 0, target = null, selector = ''; i < selectors.length; i++) {
            target = e.getTarget(selectors[i]);
            if (target) {
                selector = selectors[i];
                break;
            }
        }

        switch (selector) {
            case 'span[class=tinebase-download-link]':
                var idx = target.id.split(':')[1],
                    attachment = this.record.attachments[idx];

                // remove part id if set (that is the case in message/rfc822 attachments)
                var messageId = (this.record.id.match(/_/)) ? this.record.id.split('_')[0] : this.record.id;

                if (attachment['content-type'] === 'message/rfc822') {
                    // display message
                    var window = Tine.Felamimail.MessageDisplayDialog.openWindow({
                        message: new Tine.Felamimail.Model.Message({
                            id: messageId + '_' + attachment.partId
                        })
                    });

                } else {
                    new Ext.ux.file.Download({
                        params: {
                            requestType: 'HTTP',
                            method: 'MailFiler.downloadAttachment',
                            path: this.nodeRecord.data.path,
                            nodeId: this.record.node_id,
                            partId: attachment.partId
                        }
                    }).start();
                }

                break;

            case 'a[class=tinebase-email-link]':
                // open compose dlg
                var email = target.id.split(':')[1];
                var defaults = Tine.Felamimail.Model.Message.getDefaultData();
                defaults.to = [email];
                defaults.body = Tine.Felamimail.getSignature();

                var message = new Tine.Felamimail.Model.Message(defaults, 0);
                Tine.Felamimail.MessageEditDialog.openWindow({
                    message: message
                });
                break;

            case 'span[class=tinebase-addtocontacts-link]':
                // open edit contact dlg

                // check if addressbook app is available
                if (!Tine.Addressbook || !Tine.Tinebase.common.hasRight('run', 'Addressbook')) {
                    return;
                }

                var id = Ext.util.Format.htmlDecode(target.id);
                var parts = id.split(':');

                Tine.Addressbook.ContactEditDialog.openWindow({
                    listeners: {
                        scope: this,
                        'load': function (editdlg) {
                            editdlg.record.set('email', parts[1]);
                            editdlg.record.set('n_given', parts[2]);
                            editdlg.record.set('n_family', parts[3]);
                        }
                    }
                });

                break;

            case 'span[class=tinebase-showheaders-link]':
                // show headers

                var parts = target.id.split(':');
                var targetId = parts[0];
                var action = parts[1];

                var html = '';
                if (action == 'show') {
                    var messageHeaders = this.record.headers;

                    for (header in messageHeaders) {
                        if (messageHeaders.hasOwnProperty(header) && (header != 'to' || header != 'cc' || header != 'bcc')) {
                            html += '<br/><b>' + header + ':</b> '
                                + Ext.util.Format.htmlEncode(messageHeaders[header]);
                        }
                    }

                    target.id = targetId + ':' + 'hide';

                } else {
                    html = ' <span ext:qtip="' + Ext.util.Format.htmlEncode(this.app.i18n._('Show or hide header information')) + '" id="'
                        + Ext.id() + ':show" class="tinebase-showheaders-link">[...]</span>'
                }

                target.innerHTML = html;

                break;
        }
    },

    /**
     * fills this fields with the corresponding message data
     *
     * @param {Tine.Tinebase.data.Record} record
     */
    loadRecord: function (record) {
        this.record = record.data.hasOwnProperty('message') ? record.data.message : record;
        this.nodeRecord = record;
        this.initTemplate();
        this.update(this.record);
    }
});
