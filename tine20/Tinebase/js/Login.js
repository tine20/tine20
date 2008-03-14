/*
 * Tine 2.0
 * 
 * @package     Tine
 * @subpackage  Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Tine.Tinebase.Registry');

// turn on validation errors beside the field globally
Ext.form.Field.prototype.msgTarget = 'side';    

Tine.Login = {

    showLoginDialog: function(_defaultUsername, _defaultPassword) {
    	var loginButtons = [{
            text: 'Login',
            handler: Tine.Login.loginHandler 
        }];
        if ( userRegistration == true ) {
            loginButtons.push({
                text: 'Register',
                handler: Tine.Login.UserRegistrationHandler
            });
        }
        
        var loginDialog = new Ext.FormPanel({
            id: 'loginDialog',
            labelWidth: 75,
            url:'index.php',
            baseParams:{
            	method: 'Tinebase.login'
            },
            frame:true,
            title: 'Please enter your login data',
            bodyStyle:'padding:5px 5px 0',
            width: 350,
            defaults: {
            	width: 230
            },
            defaultType: 'textfield',
            items: [{
                fieldLabel: 'Username',
                id: 'username',
                name: 'username',
                value: _defaultUsername,
                width:225
            }, {
                inputType: 'password',
                fieldLabel: 'Password',
                id: 'password',
                name: 'password',
                //allowBlank: false,
                value: _defaultPassword,
                width:225
            }],
            buttons: loginButtons
        });
        
        loginDialog.render(document.body);

        Ext.Element.get('loginDialog').center();
        
        Ext.getCmp('username').on('specialkey', function(_field, _event) {
        	if(_event.getKey() == _event.ENTER){
        		Tine.Login.loginHandler();
        	}
        });

        Ext.getCmp('password').on('specialkey', function(_field, _event) {
            if(_event.getKey() == _event.ENTER){
                Tine.Login.loginHandler();
            }
        });
    },
    
    loginHandler: function(){
    	var loginDialog = Ext.getCmp('loginDialog');
    	
        if (loginDialog.getForm().isValid()) {
            loginDialog.getForm().submit({
                waitTitle: 'Please wait!', 
                waitMsg:'Logging you in...',
                params: {
                    jsonKey: Tine.Tinebase.jsonKey
                },
                success:function(form, action, o) {
                    Ext.MessageBox.wait('Login successful. Loading Tine 2.0...', 'Please wait!');
                    window.location.reload();
                },
                failure:function(form, action) {
                    Ext.MessageBox.show({
                        title: 'Login failure',
                        msg: 'Your username and/or your password are wrong!!!',
                        buttons: Ext.MessageBox.OK,
                        icon: Ext.MessageBox.ERROR /*,
                        fn: function() {} */
                    });
                }
            });
        };
    },
    
    UserRegistrationHandler: function () {
        var regWindow = new Tine.Tinebase.UserRegistration();
        regWindow.show();
    }
}