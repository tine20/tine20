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
Ext.namespace('Tine', 'Tine.Tinebase');

/**
 * Tine 2.0 library/ExtJS client Mainscreen.
 */
Tine.Tinebase.MainScreen = Ext.extend(Ext.Panel, {
    
    /**
     * @cfg {String} Appname of default app
     */
    defaultAppName: 'Addressbook',
    
    //private
    layout: 'border',
    border: false,
    
    /**
     * @private
     */
    initComponent: function() {
        this.onlineStatus = new Ext.ux.ConnectionStatus({});
        
        // init actions
        this.initActions();
        
        // init main menu
        this.tineMenu = new Tine.Tinebase.MainMenu({
            id: 'tineMenu',
            height: 26,
            items:[{
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
                }
            }, {
                text: _('Admin'),
                id: 'Tinebase_System_AdminButton',
                disabled: true,
                menu: {
                    id: 'Tinebase_System_AdminMenu'
                }     
            },{
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
        ]});
        
        
        
        // init footer
        var tineFooter = new Ext.Toolbar({
            id: 'tineFooter',
            height: 26,
            items:[
                String.format(_('User: {0}'), Tine.Tinebase.registry.get('currentAccount').accountDisplayName), 
                '->',
                this.onlineStatus
            ]
    
        });
        
        // get default app from preferences if available
        this.defaultAppName = (Tine.Tinebase.registry.get('preferences') && Tine.Tinebase.registry.get('preferences').get('defaultapp')) 
            ? Tine.Tinebase.registry.get('preferences').get('defaultapp') 
            : this.defaultAppName;
        
        // init app picker
        var allApps = Tine.Tinebase.appMgr.getAll();
        
        if (! Tine.Tinebase.appMgr.get(this.defaultAppName)) {
            var firstApp = allApps.first();
            if (firstApp) {
                this.defaultAppName = firstApp.appName;
            } else {
                Ext.Msg.alert(_('Sorry'), _('There are no applications enabled for you. Please contact your administrator.'));
            }
            
        }
        this.appPicker = new Tine.Tinebase.AppPicker({
            apps: allApps,
            defaultAppName: this.defaultAppName
        });
                    
        // init generic mainscreen layout
        var mainscreen = [{
            region: 'north',
            id:     'north-panel',
            split:  false,
            height: /*'platform' in window ? 26 :*/ 52,
            border: false,
            layout:'border',
            items: [/*'platform' in window ? {} :*/ {
                region: 'north',
                height: 26,
                border: false,
                id:     'north-panel-1',
                items: [
                    this.tineMenu
                ]
            },{
                region: 'center',
                layout: 'card',
                activeItem: 0,
                height: 26,
                border: false,
                id:     'north-panel-2',
                items: []
            }]
        }, {
            region: 'south',
            id: 'south',
            border: false,
            split: false,
            height: 26,
            initialSize: 26,
            items:[
                tineFooter
            ]
    /*          }, {
                    region: 'east',
                    id: 'east',
                    title: 'east',
                    split: true,
                    width: 100,
                    minSize: 100,
                    maxSize: 500,
                    autoScroll:true,
                    collapsible:true,
                    titlebar: true,
                    animate: true, */
        }, {
            region: 'center',
            id: 'center-panel',
            animate: true,
            useShim:true,
            border: false,
            layout: 'card'
        }, {
            region: 'west',
            id: 'west',
            split: true,
            width: 200,
            minSize: 100,
            maxSize: 300,
            border: false,
            collapsible:true,
            //containerScroll: true,
            collapseMode: 'mini',
            layout: 'fit',
            items: this.appPicker
        }];
        
        this.items = [{
            region: 'north',
            border: false,
            cls: 'tine-mainscreen-topbox',
            html: '<div class="tine-mainscreen-topbox-left"></div><div class="tine-mainscreen-topbox-middle"></div><div class="tine-mainscreen-topbox-right"></div>'
        }, {
            region: 'center',
            border: false,
            layout: 'border',
            items: mainscreen
        }];
        
        Tine.Tinebase.MainScreen.superclass.initComponent.call(this);
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
            disabled: !Tine.Tinebase.registry.get('changepw'),
            iconCls: 'action_password'
        });
        
        this.action_installGoogleGears = new Ext.Action({
            text: _('Install Google Gears'),
            handler: this.onInstallGoogleGears,
            disabled: (window.google && google.gears) ? true : false
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
            handler: this.onLogout
        });
    },
    
    onRender: function(ct, position) {
        Tine.Tinebase.MainScreen.superclass.onRender.call(this, ct, position);
        Tine.Tinebase.MainScreen = this;
        this.activateDefaultApp();
        
        // check for new version 
        if (Tine.Tinebase.common.hasRight('check_version', 'Tinebase')) {
            Tine.widgets.VersionCheck();
        }
    },
    
    activateDefaultApp: function() {
        if (this.appPicker.getTreeCardPanel().rendered) {
            var defaultApp = Tine.Tinebase.appMgr.get(this.defaultAppName);
        	defaultApp.getMainScreen().show();
            var postfix = (Tine.Tinebase.registry.get('titlePostfix')) ? Tine.Tinebase.registry.get('titlePostfix') : '';
            document.title = Tine.title + postfix  + ' - ' + defaultApp.getTitle();
        } else {
            this.activateDefaultApp.defer(10, this);
        }
    },
    
    /**
     * sets the active content panel
     * 
     * @param {Ext.Panel} _panel Panel to activate
     * @param {Bool} _keep keep panel
     */
    setActiveContentPanel: function(_panel, _keep) {
        // get container to which component will be added
        var centerPanel = Ext.getCmp('center-panel');
        _panel.keep = _keep;

        var i,p;
        if(centerPanel.items) {
            for (i=0; i<centerPanel.items.length; i++){
                p =  centerPanel.items.get(i);
                if (! p.keep) {
                    centerPanel.remove(p);
                }
            }  
        }
        if(_panel.keep && _panel.rendered) {
            centerPanel.layout.setActiveItem(_panel.id);
        } else {
            centerPanel.add(_panel);
            centerPanel.layout.setActiveItem(_panel.id);
            centerPanel.doLayout();
        }
    },
    
    /**
     * sets the active tree panel
     * 
     * @param {Ext.Panel} panel Panel to activate
     * @param {Bool} keep keep panel
     */
    setActiveTreePanel: function(panel, keep) {
        // get card panel to which component will be added
        var cardPanel =  this.appPicker.getTreeCardPanel();
        panel.keep = keep;
        
        // remove all panels which should not be keeped
        var i,p;
        if(cardPanel.items) {
            for (i=0; i<cardPanel.items.length; i++){
                p =  cardPanel.items.get(i);
                if (! p.keep) {
                    cardPanel.remove(p);
                }
            }  
        }
        
        // add or set given panel
        if(panel.keep && panel.rendered) {
            cardPanel.layout.setActiveItem(panel.id);
        } else {
            cardPanel.add(panel);
            cardPanel.layout.setActiveItem(panel.id);
            cardPanel.doLayout();
        }
        
    },
    
    /**
     * gets the currently displayed toolbar
     * 
     * @return {Ext.Toolbar}
     */
    getActiveToolbar: function() {
        var northPanel = Ext.getCmp('north-panel-2');

        if(northPanel.layout.activeItem && northPanel.layout.activeItem.el) {
            return northPanel.layout.activeItem.el;
        } else {
            return false;            
        }
    },
    
    /**
     * sets toolbar
     * 
     * @param {Ext.Toolbar}
     */
    setActiveToolbar: function(_toolbar, _keep) {
        var northPanel = Ext.getCmp('north-panel-2');
        _toolbar.keep = _keep;
        
        var i,t;
        if(northPanel.items) {
            for (i=0; i<northPanel.items.length; i++){
                t = northPanel.items.get(i);
                if (! t.keep) {
                    northPanel.remove(t);
                }
            }  
        }
        
        if(_toolbar.keep && _toolbar.rendered) {
            northPanel.layout.setActiveItem(_toolbar.id);
        } else {
            northPanel.add(_toolbar);
            northPanel.layout.setActiveItem(_toolbar.id);
            northPanel.doLayout();
        }
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
            title: _('About Tine 2.0'),
            msg: 
                '<div class="tb-about-dlg">' +
                    '<div class="tb-about-img"><a href="http://www.tine20.org" target="_blank"><img src="' + Tine.Login.loginLogo + '" /></a></div>' +
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
        Ext.MessageBox.confirm(_('Confirm'), _('Are you sure you want to logout?'), function(btn, text) {
            if (btn == 'yes') {
                Ext.MessageBox.wait(_('Logging you out...'), _('Please wait!'));
                Ext.Ajax.request( {
                    params : {
                        method : 'Tinebase.logout'
                    },
                    callback : function(options, Success, response) {
                        // remove the event handler
                        // the reload() trigers the unload event
                        var redirect = (Tine.Tinebase.registry.get('redirectUrl'));
                        if (redirect != '') {
                            window.location = Tine.Tinebase.registry.get('redirectUrl');
                        } else {
                            window.location = window.location.href.replace(/#+.*/, '');
                        }
                    }
                });
            }
        });
    }

});