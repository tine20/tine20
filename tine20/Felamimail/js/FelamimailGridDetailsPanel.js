/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
 * TODO         add preference to show mails in html or text?
 * TODO         replace telephone numbers in emails with 'call contact' link
 * TODO         add 'add sender to contacts'
 * TODO         make only text body scrollable (headers should be always visible)
 */
 
Ext.namespace('Tine.Felamimail');

/**
 * the details panel (shows message content)
 * 
 * @class Tine.Felamimail.GridDetailsPanel
 * @extends Tine.widgets.grid.DetailsPanel
 */
Tine.Felamimail.GridDetailsPanel = Ext.extend(Tine.widgets.grid.DetailsPanel, {
    
    defaultHeight: 300,
    currentId: null,
    record: null,
    il8n: null,
    
    /**
     * init
     */
    initComponent: function() {

        // init detail template
        this.initTemplate();
        
        // use default Tpl for default and multi view
        this.defaultTpl = new Ext.XTemplate(
            '<div class="preview-panel-felamimail-body">',
                '<div class="Mail-Body-Content"></div>',
            '</div>'
        );
        
        Tine.Felamimail.GridDetailsPanel.superclass.initComponent.call(this);
    },

    /**
     * add on click event after render
     */
    afterRender: function() {
        Tine.Felamimail.GridDetailsPanel.superclass.afterRender.apply(this, arguments);
        
        this.body.on('click', this.onClick, this);
    },
    
    /**
     * (on) update details
     * 
     * @param {} record
     * @param {} body
     */
    updateDetails: function(record, body) {
        // check if new record has been selected
        if (record.id !== this.currentId) {                
            this.currentId = record.id;
            Tine.Felamimail.messageBackend.loadRecord(record, {
                scope: this,
                success: function(message) {
                    // save more values?
                    record.data.body        = message.data.body;                            
                    record.data.flags       = message.data.flags;
                    record.data.headers     = message.data.headers;
                    record.data.attachments = message.data.attachments;
                    record.data.to          = message.data.to;
                    record.data.cc          = message.data.cc;
                    record.data.bcc         = message.data.bcc;
                    
                    this.tpl.overwrite(body, message.data);
                    this.getEl().down('div').down('div').scrollTo('top', 0, false);
                    this.getLoadMask().hide();
                }
            });
            this.getLoadMask().show();
            
        } else {
            this.tpl.overwrite(body, record.data);
        }
    },
    
    /**
     * init single message template (this.tpl)
     */
    initTemplate: function() {
        this.tpl = new Ext.XTemplate(
            '<div class="preview-panel-felamimail">',
                '<div class="preview-panel-felamimail-headers" ext:qtip="{[this.showHeaders(values.headers)]}">',
                    '<b>' + this.il8n._('Subject') + ':</b> {[this.encode(values.subject)]}<br/>',
                    '<b>' + this.il8n._('From') + ':</b> {[this.encode(values.from)]}',
                '</div>',
                '<div class="preview-panel-felamimail-attachments">{[this.showAttachments(values.attachments, "' 
                    + this.il8n._('Attachments') + '")]}</div>',
                '<div class="preview-panel-felamimail-body">{[this.showBody(values.body, values.headers, values.attachments)]}</div>',
            '</div>',{
            
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
            
            // TODO check preference for mail content-type?
            // TODO show image attachments inline
            showBody: function(value, headers, attachments) {
                if (value) {
                    //console.log(headers);
                    if (headers['content-type']
                        && (headers['content-type'].match(/text\/html/) 
                            || headers['content-type'].match(/multipart\/alternative/)
                            || headers['content-type'].match(/multipart\/signed/)
                        )
                    ) {
                        // should be already purified ... but just as precaution
                        value = Ext.util.Format.stripScripts(value);
                    } else {
                        //value = Ext.util.Format.htmlEncode(value);
                        value = Ext.util.Format.nl2br(value);
                    }
                    
                    // add images inline
                    /*
                    var inlineAttachments = '';
                    for (var i=0, id; i < attachments.length; i++) {
                        console.log(attachments[i]);
                    }
                    
                    if (inlineAttachments != '') {
                        value = value + '<hr>' + inlineAttachments;
                    }
                    */
                    
                } else {
                    return '';
                }
                return value;
            },
            
            showHeaders: function(value) {
                if (value) {
                    var result = '';
                    for (header in value) {
                        if (value.hasOwnProperty(header)) {
                            result += '<b>' + header + ':</b> ' 
                                + Ext.util.Format.htmlEncode(Ext.util.Format.ellipsis(value[header], 40)) + '<br/>';
                        }
                    }
                    return result;
                } else {
                    return '';
                }
            },
            
            // TODO add 'download all' button
            showAttachments: function(value, text) {
                var result = (value.length > 0) ? '<b>' + text + ':</b> ' : '';
                for (var i=0, id; i < value.length; i++) {
                    id = Ext.id() + ':' + value[i].partId;
                    result += '<span id="' + id + '" class="tinebase-download-link">' 
                        + '<i>' + value[i].filename + '</i>' 
                        + ' (' + Ext.util.Format.fileSize(value[i].size) + ')</span> ';
                }
                
                return result;
            }
        });
    },
    
    /**
     * on click for attachment download
     * 
     * @param {} e
     */
    onClick: function(e) {
        // download attachment
        var target = e.getTarget('span[class=tinebase-download-link]');
        if (target) {
            var partId = target.id.split(':')[1];
            var downloader = new Ext.ux.file.Download({
                params: {
                    requestType: 'HTTP',
                    method: 'Felamimail.downloadAttachment',
                    messageId: this.record.id,
                    partId: partId
                }
            });
            downloader.start();
        } else {
            // open email compose dialog
            var target = e.getTarget('a[class=tinebase-email-link]');
            if (target) {
                var email = target.id.split(':')[1];
                var defaults = Tine.Felamimail.Model.Message.getDefaultData();
                defaults.to = [email];
                defaults.body = Tine.Felamimail.getSignature();
                
                var record = new Tine.Felamimail.Model.Message(defaults, 0);
                var popupWindow = Tine.Felamimail.MessageEditDialog.openWindow({
                    record: record
                });
            }
        }
    }
});
