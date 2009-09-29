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

Ext.namespace('Tine.Tinebase.registry');

/**
 * @todo make registration working again!
 * @todo re-facotre showLoginDialog to only create one window and show hide it on demand
 */
Tine.Login = {
    onLogin: function(){},
    scope: window,
    defaultUsername: '',
    defaultPassword: '',
    
    loginMethod: 'Tinebase.login',
    loginLogo: 'images/tine_logo.gif',
    
    
    /**
     * show the login dialog
     */
    showLoginDialog: function(config) {
        Ext.apply(this, config);
        
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
        
        this.loginWindow = new Ext.Window({
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
                    style: 'padding-left: 174px;',
                    width: 350,
                    border: false,
                    html: '<a target="_blank" href="http://www.tine20.org/" border="0"><img src="' + this.loginLogo +'" /></a>'
                }, new Tine.widgets.LangChooser({
                    
                }), {
                    fieldLabel: _('Username'),
                    id: 'username',
                    name: 'username',
                    selectOnFocus: true,
                    value: this.defaultUsername
                }, {
                    inputType: 'password',
                    fieldLabel: _('Password'),
                    id: 'password',
                    name: 'password',
                    //allowBlank: false,
                    selectOnFocus: true,
                    value: this.defaultPassword
                }]
            }),
            buttons: loginButtons
            
        });
        this.originalTitle = window.document.title;
        var postfix = (Tine.Tinebase.registry.get('titlePostfix')) ? Tine.Tinebase.registry.get('titlePostfix') : '';
        window.document.title = Tine.title + postfix + ' - ' + _('Please enter your login data');
        this.loginWindow.show();
        Ext.getCmp('username').focus(false, 250);
                    
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
        
        Tine.Tinebase.viewport.on('resize', function() {
            this.loginWindow.center();
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
                scope: this,
                params : {
                    method: this.loginMethod,
                    username: values.username,
                    password: values.password
                },
                callback: function(request, httpStatus, response) {
                    var responseData = Ext.util.JSON.decode(response.responseText);
                    if (responseData.success === true) {
                        Ext.MessageBox.wait(String.format(_('Login successful. Loading {0}...'), Tine.title), _('Please wait!'));
                        this.loginWindow.hide();
                        window.document.title = this.originalTitle;
                        this.onLogin.call(this.scope);
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