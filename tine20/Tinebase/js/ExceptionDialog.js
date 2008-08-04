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
    
    onSendReport: function() {
        Ext.Ajax.request({
            waitTitle: _('Please Wait!'),
            waitMsg: _('sending report...'),
            //url: 'http://www.tine20.org/bugreport.php',
            params: {
                msg: this.exceptionInfo.msg,
                trace: Ext.util.JSON.encode(this.exceptionInfo.trace),
                description: Ext.getCmp('tb-exceptiondialog-descrioption').getValue(),
                localtime: new Date().getTime()
            },
            scope: this,
            success: function(_result, _request){
                //var response = Ext.util.JSON.decode(_result.responseText);
                this.close(); 
                Ext.MessageBox.show({
                    title: _('Transmission Completed'),
                    msg: _('Your report has been send. Thanks for your contribution'),
                    buttons: Ext.MessageBox.OK,
                    icon: Ext.MessageBox.INFO
                });
            },
            failure: function(_result, _request){
                Ext.MessageBox.show({
                    title: _('Failure'),
                    msg: _('Your report could not be send.'),
                    buttons: Ext.MessageBox.OK,
                    icon: Ext.MessageBox.ERROR  
                });
            }
        });
    }
});