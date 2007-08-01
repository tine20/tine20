/*
 * Ext JS Library 1.0.1
 * Copyright(c) 2006-2007, Ext JS, LLC.
 * licensing@extjs.com
 * 
 * http://www.extjs.com/license
 */

var EGWNameSpace = EGWNameSpace || {};

EGWNameSpace.Login = function() {

	// private functions
	var _createLoginDialog = function(_layout) {
		var loginDialog = new Ext.form.Form({
			labelAlign: 'top',
			url:'index.php?method=Egwbase.login'
		});
		
		loginDialog.column(
			{width:250},
			new Ext.form.TextField({
				fieldLabel: 'Username',
				name: 'username',
				allowBlank:false,
				value:'egwdemo',
				width:225
			})
		);
		loginDialog.column(
			{width:250},
			new Ext.form.TextField({
				inputType: 'password',
				fieldLabel: 'Password',
				name: 'password',
				allowBlank:false,
				value:'demo',
				width:225
			})
		);
		
		loginDialog.addButton('Login', function (){
			if (loginDialog.isValid()) {
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
			}
		}, loginDialog);
		
		loginDialog.render('loginForm');

                //Ext.Element.get('loginMainDiv').fitToParent();
		Ext.Element.get('loginMainDiv').center();
		Ext.Element.get('loginForm').boxWrap();
		//Ext.Element.get('loginMainDiv').animate();
	}
	
	// public functions
	return {
		// public functions
		showLoginDialog: function() {
			_createLoginDialog();
		}
	}
}();