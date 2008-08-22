/*
 * Tine 2.0
 * 
 * @package     Tine
 * @subpackage  Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Tine.Tinebase.Registry');

/**
 * @todo make registration working again!
 */
Tine.Login = {
    
    /**
     * show the login dialog
     */
    showLoginDialog: function(_defaultUsername, _defaultPassword) {
        // turn on validation errors beside the field globally
        Ext.form.Field.prototype.msgTarget = 'side';  

    	var loginButtons = [{
            id: 'loginbutton',
            text: _('Login'),
            scope: this,
            handler: this.doLogin
        }];
        
        if ( false && userRegistration === true ) {
            loginButtons.push({
                text: _('Register'),
                handler: Tine.Login.UserRegistrationHandler
            });
        }
        
        var loginWindow = new Ext.Window({
            xtype: 'panel',
            layout: 'fit',
            modal: true,
            closable: false,
            resizable: false,
            
            width: 335,
            height: 220,
            title: _('Please enter your login data'),
            items: new Ext.FormPanel({
                frame:true,
                id: 'loginDialog',
                labelWidth: 130,
                defaults: {
                    xtype: 'textfield',
                    width: 170
                },
                items: [{
                    xtype: 'panel',
                    width: 250,
                    border: false,
                    html: '<img src="http://stats.tine20.org/tine_logo_enjoy.gif" width="250" height="43"/><br /><br />'
                }, new Tine.widgets.LangChooser({
                    
                }), {
                    fieldLabel: _('Username'),
                    id: 'username',
                    name: 'username',
                    value: _defaultUsername,
                }, {
                    inputType: 'password',
                    fieldLabel: _('Password'),
                    id: 'password',
                    name: 'password',
                    //allowBlank: false,
                    value: _defaultPassword,
                }]
            }),
            buttons: loginButtons
            
        });
        
        new Ext.Viewport({
            layout: 'fit',
            html: '',
            listeners: {
                scope: this,
                render: function() {
                    loginWindow.show();
                    Ext.getCmp('username').focus(false, 250);
                },
                resize: function() {
                    loginWindow.center();
                }
            }
        });
        
        Ext.getCmp('username').on('specialkey', function(_field, _event) {
        	if(_event.getKey() == _event.ENTER){
        		this.doLogin();
        	}
        }, this);

        Ext.getCmp('password').on('specialkey', function(_field, _event) {
            if(_event.getKey() == _event.ENTER){
                this.doLogin();
            }
        }, this);
    },
    
    /**
     * do the actual login
     */
    doLogin: function(){
    	var form = Ext.getCmp('loginDialog').getForm();
        var values = form.getValues();
        if (form.isValid()) {
            Ext.MessageBox.wait(_('Logging you in...'), _('Please wait'));
            
            Ext.Ajax.request({
                params : {
                    method: 'Tinebase.login',
                    username: values.username,
                    password: values.password
                },
                callback: function(request, httpStatus, response) {
                    var responseData = Ext.util.JSON.decode(response.responseText);
                    if (responseData.success === true) {
                        Ext.MessageBox.wait(_('Login successful. Loading Tine 2.0...'), _('Please wait!'));
                        window.location = window.location;
                    } else {
                        Ext.MessageBox.show({
                            title: _('Login failure'),
                            msg: _('Your username and/or your password are wrong!!!'),
                            buttons: Ext.MessageBox.OK,
                            icon: Ext.MessageBox.ERROR 
                        });
                    }
                }
            });
        }
    },
    
    UserRegistrationHandler: function () {
        var regWindow = new Tine.Tinebase.UserRegistration();
        regWindow.show();
    }
};