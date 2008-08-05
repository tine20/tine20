/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
 Ext.namespace('Tine', 'Tine.Tinebase');
 
Tine.Tinebase.ExceptionDialog = Ext.extend(Ext.Window, {
    
    width: 400,
    height: 600,
    xtype: 'panel',
    layout: 'fit',
    plain: true,
    closeAction: 'close',
    autoScroll: true,
    
    
    initComponent: function() {
        
        this.title = _('Abnormal End');
        this.items = new Ext.FormPanel({
                id: 'tb-exceptiondialog-frompanel',
                bodyStyle: 'padding:5px;',
                buttonAlign: 'right',
                labelAlign: 'top',
                autoScroll: true,
                buttons: [{
                    text: _('Cancel'),
                    iconCls: 'action_cancel',
                    scope: this,
                    handler: function() {
                        this.close();
                    }
                }, {
                    text: _('Send Report'),
                    iconCls: 'action_saveAndClose',
                    scope: this,
                    handler: this.onSendReport
                }],
                items: [{
                    xtype: 'panel',
                    border: false,
                    html: '<div class="tb-exceptiondialog-text">' + 
                              '<p>' + _('An error occurred, the program ended abnormal.') + '</p>' +
                              '<p>' + _('The last action you made was potentially not performed correctly.') + '</p>' +
                              '<p>' + _('Please help improving this software and notify the vendor. Include a brief description of what you where doing when the error occoured.') + '</p>' + 
                          '</div>'
                }, {
                    id: 'tb-exceptiondialog-descrioption',
                    height: 200,
                    xtype: 'textfield',
                    fieldLabel: _('Description'),
                    name: 'description',
                    anchor: '95%',
                    readOnly: false
                }, {
                    xtype: 'panel',
                    width: this.width * .88,
                    layout: 'form',
                    collapsible: true,
                    collapsed: true,
                    title: _('Show Details:'),
                    defaults: {
                        xtype: 'textfield',
                        readOnly: true,
                        anchor: '95%',
                    },
                    html:  '<div class="tb-exceptiondialog-details">' +
                                '<p class="tb-exceptiondialog-msg">' + this.exceptionInfo.msg + '</p>' +
                                '<p class="tb-exceptiondialog-trace">' + this.exceptionInfo.traceHTML + '</p>' +
                           '</div>'
                }]
        });
        
        Tine.Tinebase.ExceptionDialog.superclass.initComponent.call(this);
    },
    
    /**
     * send the report to tine20.org bugracker
     * 
     * NOTE: due to same domain policy, we need to send data via a img get request
     * @private
     */
    onSendReport: function() {
        var baseUrl = 'http://www.tine20.org/bugreport.php';
        var hash = this.geerateHash();

        var info = {
           msg:  this.exceptionInfo,
           trace: this.exceptionInfo.traceHTML
        };
        var chunks = this.strChunk(Ext.util.JSON.encode(info), 1000);
        
        var img = [];
        for (var i=0;i<chunks.length;i++) {
            var part = i+1 + '/' + chunks.length;
            var data = {data : this.base64encode('hash=' + hash + '&part=' + part + '&data=' + chunks[i])};
            
            var url = baseUrl + '?' + Ext.urlEncode(data);
            img.push(Ext.DomHelper.insertFirst(this.el, {tag: 'img', src: url, hidden: true}, true));
        }
        
        Ext.MessageBox.show({
            title: _('Transmission Completed'),
            msg: _('Your report has been send. Thanks for your contribution'),
            buttons: Ext.MessageBox.OK,
            icon: Ext.MessageBox.INFO
        });
    },
    /**
     * @private
     */
    strChunk: function(str, chunklen) {
        var chunks = [];
        
        var numChunks = Math.ceil(str.length / chunklen);
        for (var i=0;i<str.length; i+=chunklen) {
            chunks.push(str.substr(i,chunklen))
        }
        return chunks;
    },
    /**
     * @private
     */
    geerateHash: function(){
        // if the time isn't unique enough, the addition 
        // of random chars should be
        var t = String(new Date().getTime()).substr(4);
        var s = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        for(var i = 0; i < 4; i++){
            t += s.charAt(Math.floor(Math.random()*26));
        }
        return t;
    },
    

    /**
     * base 64 encode given string
     * @private
     */
    base64encode : function (input) {
        var keyStr = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
        var output = "";
        var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
        var i = 0;

        while (i < input.length) {

            chr1 = input.charCodeAt(i++);
            chr2 = input.charCodeAt(i++);
            chr3 = input.charCodeAt(i++);

            enc1 = chr1 >> 2;
            enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
            enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
            enc4 = chr3 & 63;

            if (isNaN(chr2)) {
                enc3 = enc4 = 64;
            } else if (isNaN(chr3)) {
                enc4 = 64;
            }

            output = output +
            keyStr.charAt(enc1) + keyStr.charAt(enc2) +
            keyStr.charAt(enc3) + keyStr.charAt(enc4);

        }

        return output;
    }

});