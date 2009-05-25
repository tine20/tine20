/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
 * TODO         improve attachment download
 * TODO         add preference to show mails in html or text
 * TODO         replace 'mailto:' links and email addresses in message body with 'open compose tine mail dialog'
 * TODO         load record again when filter changed
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
                //'<tpl for="Body">',
                        '<div class="preview-panel-felamimail-headers" ext:qtip="{[this.showHeaders(values.headers)]}">',
                            '<b>' + _('Subject') + ':</b> {[this.encode(values.subject)]}<br/>',
                            '<b>' + _('From') + ':</b> {[this.encode(values.from)]}',
                        '</div>',
                        '<div class="preview-panel-felamimail-attachments">{[this.showAttachments(values.attachments)]}</div>',
                        '<div class="preview-panel-felamimail-body">{[this.showBody(values.body, values.headers)]}</div>',
                // '</tpl>',
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
            
            showBody: function(value, headers) {
                if (value) {
                    // TODO check preference
                    
                    if (headers['content-type'] && headers['content-type'].match(/text\/html/)) {
                        // TODO remove IMG tags?
                        value = Ext.util.Format.stripScripts(value);
                    } else {
                        value = Ext.util.Format.htmlEncode(value);
                        // it should be enough to replace only 2 or more spaces
                        value = value.replace(/ /g, '&nbsp;');
                        value = Ext.util.Format.nl2br(value);
                    }
                } else {
                    return '';
                }
                return value;
            },
            
            // TODO use this.gridpanel.formatHeaders() from grid (but how?)
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
                    //return this.gridpanel.formatHeaders(value, true);
                } else {
                    return '';
                }
            },
            
            // TODO add 'download all' button
            // TODO use popup or ajax request here?
            // TODO show better error message on fail
            showAttachments: function(value) {
                var result = (value.length > 0) ? '<b>' + _('Attachments') + ':</b> ' : '';
                var downloadLink = 'index.php?method=Felamimail.downloadAttachment&_messageId=';
                for (var i=0; i < value.length; i++) {
                    
                    result += '<a href="' 
                        + downloadLink + value[i].messageId 
                        + '&_partId=' + value[i].partId  
                        + '" ext:qtip="' + Ext.util.Format.htmlEncode(value[i]['content-type']) + '"'
                        + ' target="_blank"'
                        + '>' + value[i].filename + '</a> (' + Ext.util.Format.fileSize(value[i].size) + ')';
                }
                
                return result;
            }
        });
    }
});
