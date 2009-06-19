/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
 * TODO         replace telephone numbers in emails with 'call contact' link
 * TODO         make only text body scrollable (headers should be always visible)
 * TODO         don't show headers in tooltip, add collapsed panel or something like that
 * TODO         show image attachments inline
 * TODO         add 'download all' button
 * TODO         add preference to show mails in html or text?
 * 
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
    i18n: null,
    
    
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
                timeout: 120000, // 2 minutes
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
                '<div class="preview-panel-felamimail-headers">',
                    '<b>' + this.i18n._('Subject') + ':</b> {[this.encode(values.subject)]}<br/>',
                    '<b>' + this.i18n._('From') + ':</b>',
                    ' {[this.showFrom(values.from, "' + this.i18n._('Add') + '", "' 
                        + this.i18n._('Add contact to addressbook') + '")]}',
                    '{[this.showRecipients(values.headers)]}',
                    '{[this.showHeaders("' + this.i18n._('Show or hide header information') + '")]}',
                '</div>',
                '<div class="preview-panel-felamimail-attachments">{[this.showAttachments(values.attachments, "' 
                    + this.i18n._('Attachments') + '")]}</div>',
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
            
            showFrom: function(value, addText, qtip) {
                var result = this.encode(value);
                
                var email = value.match(/[a-z0-9_\+-\.]+@[a-z0-9-\.]+\.[a-z]{2,4}/i);
                
                // add link with 'add to contacts'
                if (email) {
                    var id = Ext.id() + ':' + email;
                    
                    var name = value.match(/^"*([a-z\-0-9\._]+)(,*) *([a-z\-0-9\._]+)/i);
                    var firstname = (name && name[1]) ? name[1] : '';
                    var lastname = (name && name[3]) ? name[3] : '';
                    
                    if (name && name[2] == ',') {
                        firstname = lastname;
                        lastname = name[1];
                    }
                    
                    id += ':' + firstname + ':' + lastname;
                    
                    result += ' <span ext:qtip="' + qtip + '" id="' + id + '" class="tinebase-addtocontacts-link">[+]</span>';
                }
                
                return result;
            },
            
            // -> check preference for mail content-type here
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
            
            showHeaders: function(qtip) {
                var result = ' <span ext:qtip="' + qtip + '" id="' + Ext.id() + ':show" class="tinebase-showheaders-link">[...]</span>';
                return result;
            },
            
            showRecipients: function(value) {
                if (value) {
                    var result = '';
                    for (header in value) {
                        if (value.hasOwnProperty(header) && (header == 'to' || header == 'cc' || header == 'bcc')) {
                            result += '<br/><b>' + header + ':</b> ' 
                                + Ext.util.Format.htmlEncode(value[header]);
                        }
                    }
                    return result;
                } else {
                    return '';
                }
            },
            
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
     * on click for attachment download / compose dlg / edit contact dlg
     * 
     * @param {} e
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
        
        switch (selector) {
            
            case 'span[class=tinebase-download-link]':
                // download attachment
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
                break;
                
            case 'a[class=tinebase-email-link]':
                // open compose dlg
                var email = target.id.split(':')[1];
                var defaults = Tine.Felamimail.Model.Message.getDefaultData();
                defaults.to = [email];
                defaults.body = Tine.Felamimail.getSignature();
                
                var record = new Tine.Felamimail.Model.Message(defaults, 0);
                var popupWindow = Tine.Felamimail.MessageEditDialog.openWindow({
                    record: record
                });
                break;
                
            case 'span[class=tinebase-addtocontacts-link]':
                // open edit contact dlg
            
                // TODO check for duplicates
            
                // check if addressbook app is available
                if (! Tine.Addressbook || ! Tine.Tinebase.common.hasRight('run', 'Addressbook')) {
                    return;
                }
            
                var parts = target.id.split(':');
                
                var popupWindow = Tine.Addressbook.ContactEditDialog.openWindow({
                    listeners: {
                        scope: this,
                        'load': function(editdlg) {
                            editdlg.contact.set('email', parts[1]);
                            editdlg.contact.set('n_given', parts[2]);
                            editdlg.contact.set('n_family', parts[3]);
                        }
                    }
                });
                
                break;
                
            case 'span[class=tinebase-showheaders-link]':
                // show headers
            
                //console.log(target);
            
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
                    //target['ext:qtip'] = 'hide';
                    
                } else {
                    html = ' <span ext:qtip="' + this.i18n._('Show or hide header information') + '" id="' 
                        + Ext.id() + ':show" class="tinebase-showheaders-link">[...]</span>'
                }
                
                target.innerHTML = html;
                
                break;
        }
    }
});
