/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
 Ext.ns('Tine', 'Tine.Tinebase');
 
 /**
  * @namespace  Tine.Tinebase
  * @class      Tine.Tinebase.PasswordChangeDialog
  * @extends    Ext.Window
  */
Tine.Tinebase.PasswordChangeDialog = Ext.extend(Ext.Window, {
    
    id: 'changePassword_window',
    closeAction: 'close',
    modal: true,
    width: 350,
    height: 230,
    minWidth: 350,
    minHeight: 230,
    layout: 'fit',
    plain: true,
    title: null,

    initComponent: function() {
        this.currentAccount = Tine.Tinebase.registry.get('currentAccount');
        this.title = (this.title !== null) ? this.title : String.format(_('Change Password For "{0}"'), this.currentAccount.accountDisplayName);
        
        this.items = new Ext.FormPanel({
            bodyStyle: 'padding:5px;',
            buttonAlign: 'right',
            labelAlign: 'top',
            anchor:'100%',
            id: 'changePasswordPanel',
            defaults: {
                xtype: 'textfield',
                inputType: 'password',
                anchor: '100%',
                allowBlank: false
            },
            items: [{
                id: 'oldPassword',
                fieldLabel: _('Old Password'), 
                name:'oldPassword'
            },{
                id: 'newPassword',
                fieldLabel: _('New Password'), 
                name:'newPassword'
            },{
                id: 'newPasswordSecondTime',
                fieldLabel: _('Repeat new Password'), 
                name:'newPasswordSecondTime'
            }],
            buttons: [{
                text: _('Cancel'),
                iconCls: 'action_cancel',
                handler: function() {
                    Ext.getCmp('changePassword_window').close();
                }
            }, {
                text: _('Ok'),
                iconCls: 'action_saveAndClose',
                handler: function() {
                    var form = Ext.getCmp('changePasswordPanel').getForm();
                    var values;
                    if (form.isValid()) {
                        values = form.getValues();
                        if (values.newPassword == values.newPasswordSecondTime) {
                            Ext.Ajax.request({
                                waitTitle: _('Please Wait!'),
                                waitMsg: _('changing password...'),
                                params: {
                                    method: 'Tinebase.changePassword',
                                    oldPassword: values.oldPassword,
                                    newPassword: values.newPassword
                                },
                                success: function(_result, _request){
                                    var response = Ext.util.JSON.decode(_result.responseText);
                                    if (response.success) {
                                        Ext.getCmp('changePassword_window').close();
                                        Ext.MessageBox.show({
                                            title: _('Success'),
                                            msg: _('Your password has been changed.'),
                                            buttons: Ext.MessageBox.OK,
                                            icon: Ext.MessageBox.INFO
                                        });
                                        Ext.Ajax.request({
                                            params: {
                                                method: 'Tinebase.updateCredentialCache',
                                                password: values.newPassword
                                            }
                                        });
                                    } else {
                                        Ext.MessageBox.show({
                                            title: _('Failure'),
                                            msg: response.errorMessage,
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
        
        Tine.Tinebase.PasswordChangeDialog.superclass.initComponent.call(this);
    }
});
