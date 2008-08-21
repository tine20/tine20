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
 * Tine 2.0 ExtJS client Mainscreen.
 */
Tine.Tinebase.MainScreenClass = Ext.extend(Ext.Component, {
    
    /**
     * @cfg {String} Appname of default app
     */
    defaultAppName: 'Addressbook',
    
    /**
     * holds default app panel
     * @private
     */
    defaultAppPanel: null,
    
    /**
     * direct actions of MainScreen
     * @private
     */
    actions: {
        changePassword: null,
        logout: null
    },
    
    /**
     * @private
     */
    initComponent: function() {
        // init actions
        this.actions.changePassword = new Ext.Action({
            text: _('Change password'),
            handler: this.onChangePassword
            //disabled: true
        });
        
        this.actions.logout = new Ext.Action({
            text: _('Logout'),
            tooltip:  _('Logout from Tine 2.0'),
            id: 'tblogout',
            iconCls: 'action_logOut',
            handler: this.onLogout
        });
        
        // init main menu
        var tineMenu = new Ext.Toolbar({
            id: 'tineMenu',
            height: 26,
            items:[{
                text: 'Tine 2.0',
                menu: {
                    id: 'Tinebase_System_Menu',     
                    items: [
                        this.actions.changePassword, '-', this.actions.logout
                    ]                
                }
            }, {
                text: _('Admin'),
                id: 'Tinebase_System_AdminButton',
                iconCls: 'AddressbookTreePanel',
                disabled: true,
                menu: {
                    id: 'Tinebase_System_AdminMenu'
                }     
            }, {
                text: _('Preferences'),
                id: 'Tinebase_System_PreferencesButton',
                iconCls: 'AddressbookTreePanel',
                disabled: true,
                menu: {
                    id: 'Tinebase_System_PreferencesMenu'
                }
            }, '->', 
            this.actions.logout
        ]});
        
        // init footer
        var tineFooter = new Ext.Toolbar({
            id: 'tineFooter',
            height: 26,
            items:[
                sprintf(_('User: %s'), Tine.Tinebase.Registry.get('currentAccount').accountDisplayName), '-',
                sprintf(_('Timezone: %s'), Tine.Tinebase.Registry.get('timeZone')), '-',
                _('Language: '), new Tine.widgets.LangChooser({})
            ]
    
        });
    
        // init app chooser
        this.applicationArcordion = new Ext.Panel({
            //baseCls: 'appleftlayout',
            title: '&nbsp;',
            layout:'appleft',
            border: false,
            layoutConfig: {
                titleCollapse: true,
                hideCollapseTool: true
            },
            items: this.getPanels()
        });
                    
        // init generic mainscreen layout
        this.items = [{
            region: 'north',
            id:     'north-panel',
            split:  false,
            height: 52,
            border: false,
            layout:'border',
            items: [{
                region: 'north',
                height: 26,
                border: false,
                id:     'north-panel-1',
                items: [
                    tineMenu
                ]
            },{
                region: 'center',
                layout: 'card',
                activeItem: 0,
                height: 26,
                border: false,
                id:     'north-panel-2'
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
            containerScroll: true,
            collapseMode: 'mini',
            layout: 'fit',
            items: this.applicationArcordion
        }];
        
        Tine.Tinebase.MainScreenClass.superclass.initComponent.call(this);
    },

    /**
     * returns array of panels to display in south region
     * 
     * @private
     * @return {Array}
     */
    getPanels:  function() {
        var userApps = Tine.Tinebase.Registry.get('userApplications');
        
        var panels = [];
        var app, appPanel;
        for(var i=0; i<userApps.length; i++) {
            app = userApps[i];
            if(app.status != 'enabled') {
                continue;
            }
            try{
                appPanel = Tine[app.name].getPanel();
                panels.push(appPanel);
                
                if (i === 0 || app.name == this.defaultAppName) {
                    this.defaultAppPanel = appPanel;
                }
            } catch(e) {
                //console.log(_application + ' failed');
                //console.log(e);
            }
        }
        
        return panels;
    },
    
    /**
     * render the viewport
     * 
     * NOTE: We can't extend viewport directly, as we need to ensure, that the
     * viewport gets rendered before the contents of it :-)
     * @private
     */
    render: function() {
        var viewport = new Ext.Viewport({
            layout: 'border',
            items: this.items,
            listeners: {
                scope: this,
                render: this.ativateDefaultApp
            }
        });
    },
    
    ativateDefaultApp: function() {
        if (this.defaultAppPanel.rendered) {
            if(! this.defaultAppPanel.collapsed) {
                this.defaultAppPanel.fireEvent('beforeexpand', this.defaultAppPanel);
            }
            this.defaultAppPanel.expand();
        } else {
            this.ativateDefaultApp.defer(10, this);
        }
    },
    /**
     * @private
     */
    onChangePassword: function() {
        
        var passwordDialog = new Ext.Window({
            title: sprintf(_('Change Password For "%s"'), Tine.Tinebase.Registry.get('currentAccount').accountDisplayName),
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
                        window.location = window.location;
                    }
                });
            }
        });
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
        }
    }

});