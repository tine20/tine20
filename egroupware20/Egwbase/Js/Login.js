    /*
 * Ext JS Library 1.0.1
 * Copyright(c) 2006-2007, Ext JS, LLC.
 * licensing@extjs.com
 * 
 * http://www.extjs.com/license
 */

var EGWNameSpace = EGWNameSpace || {};

EGWNameSpace.Login = function() {
    // turn on validation errors beside the field globally
    Ext.form.Field.prototype.msgTarget = 'side';
    
	// private functions
	var _createLoginDialog = function(_layout) {
		var loginDialog = new Ext.FormPanel({
            id: 'loginDialog',
            labelWidth: 75,
			url:'index.php',
            baseParams:{method: 'Egwbase.login'},
            frame:true,
            title: 'Please enter your login data',
            bodyStyle:'padding:5px 5px 0',
            width: 350,
            defaults: {width: 230},
            defaultType: 'textfield',
            items: [{
                fieldLabel: 'Username',
                name: 'username',
                name: 'username',
                value:'egwdemo',
                width:225
            }, {
                inputType: 'password',
                fieldLabel: 'Password',
                name: 'password',
                allowBlank:false,
                value:'demo',
                width:225
            }],
            buttons: [{
                text: 'Login',
                handler: function(){
                    if (loginDialog.form.isValid()) {
                        loginDialog.form.submit({
                            waitTitle: 'Please wait!', 
                            waitMsg:'Loging you in...',
                            success:function(form, action, o) {
                                Ext.MessageBox.wait('Login successful. Loading eGroupWare...', 'Please wait!');
                                window.location.reload();
                            },
                            failure:function(form, action) {
                                Ext.MessageBox.alert("Error", 'Login failed');
                            }
                        });
                    };
                }
            }]
		});
		
/*		loginDialog.addButton('Login', function (){
				loginDialog.submit({
					waitTitle: 'Please wait!',
					waitMsg:'Logging you in...',
					method: 'post',
					success:function(form, action, o) {
						Ext.MessageBox.wait('Login successful. Loading eGroupWare...', 'Please wait!');
						window.location.reload();
					},
					failure:function(form, action) {
						Ext.MessageBox.alert("Error",action.result.errorMessage);
					}
				});
		}, loginDialog);
	*/	
		loginDialog.render(document.body);

        //Ext.Element.get('loginMainDiv').fitToParent();
		Ext.Element.get('loginDialog').center();
		//Ext.Element.get('loginForm').boxWrap();
		//Ext.Element.get('loginMainDiv').animate();
	}
	
	// public functions
	return {
		// public functions
		showLoginDialog: _createLoginDialog
	}
}();