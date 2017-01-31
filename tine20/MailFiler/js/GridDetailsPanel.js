/*
 * Tine 2.0
 * 
 * @package     MailFiler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2016 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * @param {Tine.MailFiler.Model.Message} record
     * @param {String} body
     * @private
     */
    updateDetails: function(record, body) {
        this.nodeRecord = record;
        if (record.get('message') && Ext.isObject(record.get('message'))) {
            this.record = new Tine.Felamimail.Model.Message(record.get('message'), record.get('message').id);

            if (this.record.id === this.currentId) {
                // nothing to do
            } else {
                this.setTemplateContent(this.record, this.getMessageRecordPanel().body);
            }
        } else {
            this.layout.setActiveItem(this.getDefaultInfosPanel());
            this.showDefault(this.getDefaultInfosPanel().body);
            this.record = null;
        }
    },
    
    /**
     * overwrite template with (body) content
     * 
     * @param {Tine.MailFiler.Model.Message} record
     * @param {String} body
     */
    setTemplateContent: function(record, body) {
        this.currentId = record.id;
        this.getLoadMask().hide();

        this.doLayout();

        this.tpl.overwrite(body, record.data);

        this.getEl().down('div').down('div').scrollTo('top', 0, false);

        if (this.record.get('preparedParts') && this.record.get('preparedParts').length > 0) {
            Tine.log.debug('Tine.MailFiler.GridDetailsPanel::setTemplateContent about to handle preparedParts');
            this.handlePreparedParts(record);
        }
    },
    
    /**
     * handle invitation messages (show top + bottom panels)
     * 
     * @param {Tine.MailFiler.Model.Message} record
     */
    handlePreparedParts: function(record) {
        var firstPreparedPart = this.record.get('preparedParts')[0],
            mimeType = String(firstPreparedPart.contentType).split(/[ ;]/)[0],
            mainType = Tine.MailFiler.MimeDisplayManager.getMainType(mimeType);
            
        if (! mainType) {
            Tine.log.info('Tine.MailFiler.GridDetailsPanel::handlePreparedParts nothing found to handle ' + mimeType);
            return;
        }
        
        var bodyEl = this.getMessageRecordPanel().getEl().query('div[class=preview-panel-felamimail-body]')[0],
            detailsPanel = Tine.MailFiler.MimeDisplayManager.create(mainType, {
                detailsPanel: this,
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
                    attachment = this.record.get('attachments')[idx],
                    nodeId = this.nodeRecord.get('id');

                // TODO support 'message/rfc822'?
                // remove part id if set (that is the case in message/rfc822 attachments)
                //var messageId = (this.record.id.match(/_/)) ? this.record.id.split('_')[0] : this.record.messageuid;
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
                new Ext.ux.file.Download({
                    params: {
                        requestType: 'HTTP',
                        method: 'MailFiler.downloadAttachment',
                        path: this.nodeRecord.get('path'),
                        nodeId: nodeId,
                        partId: attachment.partId
                    }
                }).start();

                break;
        }
    }
});
