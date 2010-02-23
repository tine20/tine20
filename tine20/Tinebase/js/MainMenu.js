Tine.Tinebase.MainMenu = Ext.extend(Ext.Toolbar, {
    id: 'tineMenu',
    height: 26,
            
    initComponent: function() {
        this.initActions();
        this.items = this.getItems();
        
        this.supr().initComponent.call(this);
    },
    
    getItems: function() {
        return [{
            text: Tine.title,
            menu: {
                id: 'Tinebase_System_Menu',     
                items: [
                    this.action_aboutTine,
                    '-',
                    this.action_changePassword,
                    this.action_installGoogleGears,
                    '-',
                    {
                        text: _('Debug Console (Ctrl + F11)'),
                        handler: Tine.Tinebase.common.showDebugConsole,
                        hidden: ! Tine.Tinebase.registry.get("version").buildType.match(/(DEVELOPMENT|DEBUG)/)
                    },
                    this.action_logout
                ]                
            }}/*, {
                text: _('Admin'),
                id: 'Tinebase_System_AdminButton',
                disabled: true,
                menu: {
                    id: 'Tinebase_System_AdminMenu'
                }     
            }*/,{
                text: _('Preferences'),
                id: 'Tinebase_System_PreferencesButton',
                disabled: false,
                handler: this.onEditPreferences
                /*,
                menu: {
                    id: 'Tinebase_System_PreferencesMenu',
                    items: [
                        //this.action_editPreferences
                    ]
                }*/
            }, '->', 
            this.action_logout
        ];
    },
    /**
     * initialize actions
     * @private
     */
    initActions: function() {
        this.action_aboutTine = new Ext.Action({
            text: String.format(_('About {0}'), Tine.title),
            handler: this.onAboutTine20,
            iconCls: 'action_about'
        });
        
        this.action_changePassword = new Ext.Action({
            text: _('Change password'),
            handler: this.onChangePassword,
            disabled: (Tine.Tinebase.registry.get('changepw') == '0'),
            iconCls: 'action_password'
        });
        
        this.action_installGoogleGears = new Ext.Action({
            text: _('Install Google Gears'),
            handler: this.onInstallGoogleGears,
            disabled: (window.google && google.gears)
        });
        
        /*
        this.action_editPreferences = new Ext.Action({
            text: _('Preferences'),
            handler: this.onEditPreferences,
            disabled: false,
            //id: 'Tinebase_System_PreferencesButton',
            iconCls: 'AddressbookTreePanel' //''action_preferences'
        });
        */

        this.action_logout = new Ext.Action({
            text: _('Logout'),
            tooltip:  String.format(_('Logout from {0}'), Tine.title),
            iconCls: 'action_logOut',
            handler: this.onLogout,
            scope: this
        });
    },
    
    /**
     * @private
     */
    onAboutTine20: function() {
        
        var version = (Tine.Tinebase.registry.get('version')) ? Tine.Tinebase.registry.get('version') : {
            codeName: 'unknown',
            packageString: 'unknown'
        };
        
        Ext.Msg.show({
            title: String.format(_('About {0}'), Tine.title),
            msg: 
                '<div class="tb-about-dlg">' +
                    '<div class="tb-about-img"><a href="http://www.tine20.org" target="_blank"><img src="' + Tine.Tinebase.LoginPanel.prototype.loginLogo + '" /></a></div>' +
                    '<div class="tb-about-version">Version: ' + version.codeName + '</div>' +
                    '<div class="tb-about-build">( ' + version.packageString + ' )</div>' +
                    '<div class="tb-about-copyright">Copyright: 2007-' + new Date().getFullYear() + '&nbsp;<a href="http://www.metaways.de" target="_blank">Metaways Infosystems GmbH</a></div>' +
                '</div>',
            width: 400,
            //height: 200,
            buttons: Ext.Msg.OK,
            animEl: 'elId'
        });
        /*
        Ext.Msg.show({
           title: _('About Tine 2.0'),
           msg: 'Version: 2009-02-10',
           buttons: Ext.Msg.OK,
           //fn: processResult,
           animEl: 'elId',
           icon: 'mb-about'
        });
        */
    },
    
    /**
     * @private
     */
    onChangePassword: function() {
        
        var passwordDialog = new Ext.Window({
            title: String.format(_('Change Password For "{0}"'), Tine.Tinebase.registry.get('currentAccount').accountDisplayName),
            id: 'changePassword_window',
            closeAction: 'close',
            modal: true,
            width: 350,
            height: 230,
            minWidth: 350,
            minHeight: 230,
            layout: 'fit',
            plain: true,
            items: new Ext.FormPanel({
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
            })
        });
        passwordDialog.show();  
    },
    
    /**
     * the install Google Gears handler function
     * @private
     */
    onInstallGoogleGears: function() {
        var message = _('Installing Gears will improve the performance of Tine 2.0 by caching all needed files locally on this computer.');
        Tine.WindowFactory.getWindow({
            width: 800,
            height: 400,
            url: "http://gears.google.com/?action=install&message=" + message
        });
    },

    /**
     * @private
     */
    onEditPreferences: function() {
        Tine.widgets.dialog.Preferences.openWindow({});
    },
    
    /**
     * the logout button handler function
     * @private
     */
    onLogout: function() {
        if (Tine.Tinebase.registry.get('confirmLogout') != '0') {
            Ext.MessageBox.confirm(_('Confirm'), _('Are you sure you want to logout?'), function(btn, text) {
                if (btn == 'yes') {
                    this._doLogout();
                }
            }, this);
        } else {
            this._doLogout();
        }
    },
    
    /**
     * logout user & redirect
     */
    _doLogout: function() {
        Ext.MessageBox.wait(_('Logging you out...'), _('Please wait!'));
        Ext.Ajax.request( {
            params : {
                method : 'Tinebase.logout'
            },
            callback : function(options, Success, response) {
                // remove the event handler
                // the reload() trigers the unload event
                var redirect = (Tine.Tinebase.registry.get('redirectUrl'));
                if (redirect && redirect != '') {
                    window.location = Tine.Tinebase.registry.get('redirectUrl');
                } else {
                    window.location = window.location.href.replace(/#+.*/, '');
                }
            }
        });        
    }
});

//Tine.Tinebase.MainMenu = function(config) {
//    this.isPrism = 'platform' in window;
//    Ext.apply(this, config);
//    // NOTE: Prism has no top menu yet ;-(
//    //       Only the 'icon menu' is implemented (right mouse button in dock(mac)/tray(other)
//    /*if (this.isPrism) {
//        window.platform.showNotification('title', 'text', 'images/clear-left.png');
//        this.menu = window.platform.icon().title = 'supertine';
//        //window.platform.menuBar.addMenu(“myMenu”);
//        
//        this.menu = window.platform.icon().menu;
//        window.platform.icon().menu.addMenuItem("myItem", "My Item", function(e) { window.alert("My Item!"); });
//        
//        var sub = this.menu.addSubmenu('mytest', 'mytest');
//        sub.addMenuItem('test', 'test', function() {alert('test');});
//        
//    } else { */
//        this.menu = new Ext.Toolbar({
//            id: this.id, 
//            height: this.height,
//            items: this.items
//        });
//        
//        return this.menu;
//    //}
//};


