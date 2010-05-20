/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
Ext.ns('Tine.Tinebase');

/**
 * Tine 2.0 jsclient main menu
 * 
 * @namespace   Tine.Tinebase
 * @class       Tine.Tinebase.MainMenu
 * @extends     Ext.Toolbar
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */
Tine.Tinebase.MainMenu = Ext.extend(Ext.Toolbar, {
    /**
     * @cfg {Boolean} showMainMenu
     */
    showMainMenu: false,    
    style: {'padding': '0px 2px'},
    cls: 'tbar-mainmenu',
    
    /**
     * @type Array
     * @property mainActions
     */
    mainActions: null,
    
    initComponent: function() {
        this.initActions();
        this.onlineStatus = new Ext.ux.ConnectionStatus({
            showIcon: false
        });
        
        this.items = this.getItems();
        
        var buttonTpl = new Ext.Template(
            '<table id="{4}" cellspacing="0" class="x-btn {3}"><tbody class="{1}">',
            '<tr><td class="x-btn-ml"><i>&#160;</i></td><td class="x-btn-mc"><em class="{2}" unselectable="on"><button type="{0}"></button></em></td><td class="x-btn-mr"><i>&#160;</i></td></tr>',
            '</tbody></table>'
        ).compile();
        
        Ext.each(this.items, function(item) {
            item.template = buttonTpl;
        }, this);
        
        this.supr().initComponent.call(this);
    },
    
    /*
    afterRender: function() {
        this.supr().afterRender.apply(this, arguments);
        
        this.items.each(function(item) {
            Ext.select('.tine-mainscreen-mainmenu .x-toolbar .x-btn tbody tr:first-child').remove();
        }, this);
    },
    */
    
    getItems: function() {
        return [{
            text: Tine.title,
            hidden: !this.showMainMenu,
            menu: {
                id: 'Tinebase_System_Menu', 
                items: this.getMainActions()
        }},
        '->', {
            text: String.format(_('User: {0}'), Tine.Tinebase.registry.get('currentAccount').accountDisplayName),
            menu: this.getUserActions(),
            menuAlign: 'tr-br'
        },
        this.onlineStatus,
        this.action_logout];
    },
    
    /**
     * returns all main actions
     * 
     * @return {Array}
     */
    getMainActions: function() {
        if (! this.mainActions) {
            this.mainActions = [
                this.action_aboutTine,
                '-',
                this.action_installGoogleGears,
                this.action_showDebugConsole,
                '-',
                this.getUserActions()
            ];
        }
        return this.mainActions;
    },
    
    getUserActions: function() {
        if (! this.userActions) {
            this.userActions = [
                this.action_showPreferencesDialog,
                this.action_changePassword,
                this.action_logout
            ];
        }
        return this.userActions;
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
        
        this.action_installGoogleGears = new Ext.Action({
            text: _('Install Google Gears'),
            handler: this.onInstallGoogleGears,
            disabled: (window.google && google.gears)
        });
        
        this.action_showDebugConsole = new Ext.Action({
            text: _('Debug Console (Ctrl + F11)'),
            handler: Tine.Tinebase.common.showDebugConsole,
            hidden: ! Tine.Tinebase.registry.get("version").buildType.match(/(DEVELOPMENT|DEBUG)/)
        });
        
        this.action_showPreferencesDialog = new Ext.Action({
            text: _('Preferences'),
            disabled: false,
            handler: this.onEditPreferences
        });
        
        this.action_changePassword = new Ext.Action({
            text: _('Change password'),
            handler: this.onChangePassword,
            disabled: (Tine.Tinebase.registry.get('changepw') == '0'),
            iconCls: 'action_password'
        });
        
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
                    '<div class="tb-about-img"><a href="' + Tine.weburl + '" target="_blank"><img src="' + Tine.Tinebase.LoginPanel.prototype.loginLogo + '" /></a></div>' +
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
        
        var passwordDialog = Tine.WindowFactory.getWindow({
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


