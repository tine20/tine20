/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

 Ext.ns('Tine.Expressodriver');

 /**
  * @namespace  Tine.Expressodriver
  * @class      Tine.Expressodriver.CredentialsDialog
  * @extends    Ext.Window
  */
Tine.Expressodriver.CredentialsDialog = Ext.extend(Ext.Window, {

    id: 'expressodriverCredentials_window',
    closeAction: 'close',
    modal: true,
    width: 350,
    height: 230,
    minWidth: 350,
    minHeight: 230,
    layout: 'fit',
    plain: true,
    title: null,
    adapterName: '',

    initComponent: function() {
        var app = Tine.Tinebase.appMgr.get('Expressodriver');
        this.title = (this.title !== null) ? this.title : app.i18n._('Credentials for Expressodriver');

        this.items = new Ext.FormPanel({
            bodyStyle: 'padding:5px;',
            buttonAlign: 'right',
            labelAlign: 'top',
            anchor:'100%',
            id: 'expressodriverCredentialsPanel',
            defaults: {
                xtype: 'textfield',
                inputType: 'password',
                anchor: '100%',
                allowBlank: false
            },
            items: [{
                id: 'password',
                fieldLabel: _('Password'),
                name:'password'
            },{
                id: 'passwordSecondTime',
                fieldLabel: _('Repeat Password'),
                name:'passwordSecondTime'
            }],
            buttons: [{
                text: _('Cancel'),
                iconCls: 'action_cancel',
                handler: function() {
                    Ext.getCmp('expressodriverCredentials_window').close();
                }
            }, {
                text: _('Ok'),
                iconCls: 'action_saveAndClose',
                handler: function() {
                    var form = Ext.getCmp('expressodriverCredentialsPanel').getForm();
                    var values;
                    if (form.isValid()) {
                        values = form.getValues();
                        if (values.password == values.passwordSecondTime) {
                            Ext.Ajax.request({
                                waitTitle: _('Please Wait!'),
                                waitMsg: _('changing password...'),
                                params: {
                                    method: 'Expressodriver.setCredentials',
                                    adapterName: Ext.getCmp('expressodriverCredentials_window').adapterName,
                                    password: values.password
                                },
                                success: function(_result, _request){
                                    var response = Ext.util.JSON.decode(_result.responseText);
                                    if (response.success) {
                                        Ext.getCmp('expressodriverCredentials_window').close();
                                        Ext.MessageBox.show({
                                            title: _('Success'),
                                            msg: app.i18n._('Authentication success.'),
                                            buttons: Ext.MessageBox.OK,
                                            icon: Ext.MessageBox.INFO
                                        });

                                        // reload grid and treepanel
                                        app.getMainScreen().getCenterPanel().grid.getStore().reload();

                                        var treeNode = app.getMainScreen().getWestPanel().getContainerTreePanel();
                                        var selected = treeNode.getSelectionModel().getSelectedNode();
                                        if (selected) {
                                            selected.reload();
                                        }

                                    } else {
                                        Ext.MessageBox.show({
                                            title: app.i18n._('Credentials error'),
                                            msg: app.i18n._(response.errorMessage),
                                            buttons: Ext.MessageBox.OK,
                                            icon: Ext.MessageBox.ERROR
                                        });
                                    }
                                }
                            });
                        } else {
                            Ext.MessageBox.show({
                                title: _('Failure'),
                                msg: _('The new passwords mismatch, please correct them.'),
                                buttons: Ext.MessageBox.OK,
                                icon: Ext.MessageBox.ERROR
                            });
                        }
                    }
                }
            }]
        });

        Tine.Expressodriver.CredentialsDialog.superclass.initComponent.call(this);
    }
});
