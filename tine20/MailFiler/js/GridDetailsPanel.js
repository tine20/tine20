/*
 * Tine 2.0
 * 
 * @package     MailFiler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Tine.MailFiler');

/**
 * @namespace   Tine.MailFiler
 * @class       Tine.MailFiler.GridDetailsPanel
 * @extends     Tine.widgets.grid.DetailsPanel
 * 
 * <p>Message Grid Details Panel</p>
 * <p>the details panel (shows message content)</p>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.MailFiler.GridDetailsPanel
 *
 * @todo        extend Tine.Felamimail.GridDetailsPanel to re-use message functionality
 */
 Tine.MailFiler.GridDetailsPanel = Ext.extend(Tine.widgets.grid.DetailsPanel, {
    
    /**
     * config
     * @private
     */
    defaultHeight: 350,
    currentId: null,
    record: null,
    app: null,
    i18n: null,
    
    fetchBodyTransactionId: null,
    
    /**
     * init
     * @private
     */
    initComponent: function() {
        this.initTemplate();
        this.initDefaultTemplate();

        Tine.MailFiler.GridDetailsPanel.superclass.initComponent.call(this);
    },

    /**
     * use default Tpl for default and multi view
     */
    initDefaultTemplate: function() {
        this.defaultTpl = new Ext.XTemplate(
            '<div class="preview-panel-felamimail">',
                '<div class="preview-panel-felamimail-body">{[values ? values.msg : ""]}</div>',
            '</div>'
        );
    },
    
    /**
     * add on click event after render
     * @private
     */
    afterRender: function() {
        Tine.MailFiler.GridDetailsPanel.superclass.afterRender.apply(this, arguments);
        this.body.on('click', this.onClick, this);
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
                    this.getMessageRecordPanel()
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
     * (on) update details
     * 
     * @param {Tine.MailFiler.Model.Node} record
     * @param {String} body
     * @private
     */
    updateDetails: function(record, body) {
        if (record.get('message') && Ext.isObject(record.get('message'))) {
            if (record.id === this.currentId) {
                // nothing to do
            } else if (! record.messageIsFetched()) {
                this.waitForContent(record, this.getMessageRecordPanel().body);
            } else {
                // FIXME update record in grid: currently the record is overwritten after the message was fetched ...
                this.record = record;
                this.attachments = this.record.get('message').attachments;
                this.currentId = record.id;
                this.setTemplateContent(this.record.get('message'), this.getMessageRecordPanel().body);
            }
        } else {
            this.layout.setActiveItem(this.getDefaultInfosPanel());
            this.showDefault(this.getDefaultInfosPanel().body);
            this.record = null;
        }
    },

     /**
      * wait for body content
      *
      * @param {Tine.MailFiler.Model.Node} record
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
                         Tine.MailFiler.fileRecordBackend.handleRequestException(exception);
                     }
                 },
                 scope: this
             });
             this.defaultTpl.overwrite(body, {msg: ''});
             this.getLoadMask().show();
         } else {
             this.getLoadMask().hide();
         }
     },

     /**
      * refetch message body
      *
      * @param {MailFiler model} record
      * @param {Function} callback
      * 
      * @todo switch to MailFiler functions
      */
     refetchBody: function(record, callback) {
         // cancel old request first
         if (this.fetchBodyTransactionId && ! Tine.MailFiler.fileRecordBackend.isLoading(this.fetchBodyTransactionId)) {
             Tine.log.debug('Tine.MailFiler.GridDetailsPanel::refetchBody -> cancelling current fetchBody request.');
             Tine.MailFiler.fileRecordBackend.abort(this.fetchBodyTransactionId);
         }
         Tine.log.debug('Tine.MailFiler.GridDetailsPanel::refetchBody -> calling fetchBody');
         this.fetchBodyTransactionId = Tine.MailFiler.fileRecordBackend.fetchBody(record, 'configured', callback);
     },

     /**
     * overwrite template with (body) content
     *
     * @param {Object} messageData
     * @param {String} body
     */
    setTemplateContent: function(messageData, body) {
        this.getLoadMask().hide();
        this.doLayout();
        this.tpl.overwrite(body, messageData);

        this.getEl().down('div').down('div').scrollTo('top', 0, false);
    },

    /**
     * init single message template (this.tpl)
     * @private
     */
    initTemplate: function() {
        
        this.tpl = new Ext.XTemplate(
            '<div class="preview-panel-felamimail">',
                '<div class="preview-panel-felamimail-headers">',
                    '<b>' + this.i18n._('Subject') + ':</b> {[this.encode(values.subject)]}<br/>',
                    '<b>' + this.i18n._('From') + ':</b>',
                    ' {[this.showFrom(values.from_email, values.from_name, "' + this.i18n._('Add') + '", "' 
                        + this.i18n._('Add contact to addressbook') + '")]}<br/>',
                    '<b>' + this.i18n._('Date') + ':</b> {[this.showDate(values.sent, values)]}',
                    '{[this.showRecipients(values.headers)]}',
                    //'{[this.showHeaders("' + this.i18n._('Show or hide header information') + '")]}',
                '</div>',
                '<div class="preview-panel-felamimail-attachments">{[this.showAttachments(values.attachments, values)]}</div>',
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
                return value;
            },
            
            showDate: function(sent, messageData) {
                var date = (sent) ? sent : messageData.received;
                return (date)
                    ? (Ext.isFunction(date.format) ? date.format('l') + ', ' + Tine.Tinebase.common.dateTimeRenderer(date) : date)
                    : '';
            },
            
            showFrom: function(email, name, addText, qtip) {
                if (! name) {
                    name = '';
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
                //result = '<a id="' + id + '" class="tinebase-email-link">' + result + '</a>'
                //result += ' <span ext:qtip="' + Tine.Tinebase.common.doubleEncode(qtip) + '" id="' + id + '" class="tinebase-addtocontacts-link">[+]</span>';
                return result;
            },
            
            showBody: function(body, messageData) {
                body = body || '';
                if (body && messageData.body_content_type == 'text/plain') {

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
                } else {
                    body = Ext.util.Format.nl2br(body);
                }
                return body;
            },
            
            showHeaders: function(qtip) {
                var result = ' <span ext:qtip="' + Tine.Tinebase.common.doubleEncode(qtip) + '" id="' + Ext.id() + ':show" class="tinebase-showheaders-link">[...]</span>';
                return result;
            },
            
            showRecipients: function(value) {
                if (value) {
                    var i18n = Tine.Tinebase.appMgr.get('MailFiler').i18n,
                        result = '';
                    for (header in value) {
                        if (value.hasOwnProperty(header) && (header == 'to' || header == 'cc' || header == 'bcc')) {
                            result += '<br/><b>' + i18n._hidden(Ext.util.Format.capitalize(header)) + ':</b> ' 
                                + Ext.util.Format.htmlEncode(value[header]);
                        }
                    }
                    return result;
                } else {
                    return '';
                }
            },
            
            showAttachments: function(attachments, messageData) {
                var result = (attachments && attachments.length > 0) ? '<b>' + this.app.i18n._('Attachments') + ':</b> ' : '';

                if (attachments) {
                    for (var i = 0, id, cls; i < attachments.length; i++) {
                        result += '<span id="' + Ext.id() + ':' + i + '" class="tinebase-download-link">'
                            + '<i>' + attachments[i].filename + '</i>'
                            + ' (' + Ext.util.Format.fileSize(attachments[i].size) + ')</span> ';
                    }
                }
                
                return result;
            }
        });
    },
    
    /**
     * on click for attachment download / compose dlg / edit contact dlg
     * 
     * @param {} e
     * @private
     */
    onClick: function(e) {
        var selectors = [
            'span[class=tinebase-download-link]',
            'a[class=tinebase-email-link]',
            'span[class=tinebase-addtocontacts-link]',
            'span[class=tinebase-showheaders-link]'
        ];
        
        // find the correct target
        for (var i=0, target=null, selector=''; i < selectors.length; i++) {
            target = e.getTarget(selectors[i]);
            if (target) {
                selector = selectors[i];
                break;
            }
        }
        
        Tine.log.debug('Tine.MailFiler.GridDetailsPanel::onClick found target:"' + selector + '".');
        
        switch (selector) {
            case 'span[class=tinebase-download-link]':
                var idx = target.id.split(':')[1],
                    // FIXME: somehow the message in the record is not persisted - this.attachments should be removed
                    // FIXME:   when this is working correctly
                    // attachment = this.record.get('message').attachments !== undefined
                    //    ? this.record.get('message').attachments[idx]
                    //    : null,
                    attachment = this.attachments !== undefined
                        ? this.attachments[idx]
                        : null,
                    nodeId = this.record.get('id');

                // TODO support 'message/rfc822'?
                // remove part id if set (that is the case in message/rfc822 attachments)
                //var messageId = (this.record.get('message').id.match(/_/)) ? this.record.get('message').id.split('_')[0] : this.record.get('message').messageuid;
                //if (attachment['content-type'] === 'message/rfc822') {
                //
                //    Tine.log.debug('Tine.MailFiler.GridDetailsPanel::onClick openWindow for:"' + messageId + '_' + attachment.partId + '".');
                //    // display message
                //    Tine.MailFiler.MessageDisplayDialog.openWindow({
                //        record: new Tine.MailFiler.Model.Message({
                //            id: messageId + '_' + attachment.partId
                //        })
                //    });
                //
                //} else {

                // download attachment
                if (attachment) {
                    new Ext.ux.file.Download({
                        params: {
                            requestType: 'HTTP',
                            method: 'MailFiler.downloadAttachment',
                            path: this.record.get('path'),
                            nodeId: nodeId,
                            partId: attachment.partId
                        }
                    }).start();
                } else {
                    Tine.log.warn('Attachment not found');
                }

                break;
        }
    }
});
