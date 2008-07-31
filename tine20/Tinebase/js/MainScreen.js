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
    initComponent: function() {
        var tineMenu = new Ext.Toolbar({
            id: 'tineMenu',
            height: 26,
            items:[{
                text: 'Tine 2.0',
                menu: {
                    id: 'Tinebase_System_Menu',     
                    items: [{
                        text: 'Change password',
                        handler: this.changePasswordHandler
                        //disabled: true
                    }, '-', {
                        text: 'Logout',
                        id: 'menulogout',
                        handler: this.logoutButtonHandler,
                        iconCls: 'action_logOut'
                    }]                
                }
            }, {
                text: 'Admin',
                id: 'Tinebase_System_AdminButton',
                iconCls: 'AddressbookTreePanel',
                disabled: true,
                menu: {
                    id: 'Tinebase_System_AdminMenu'
                }     
            }, {
                text: 'Preferences',
                id: 'Tinebase_System_PreferencesButton',
                iconCls: 'AddressbookTreePanel',
                disabled: true,
                menu: {
                    id: 'Tinebase_System_PreferencesMenu'
                }
            }, '->', {
                text: _('Logout'),
                id: 'tblogout',
                iconCls: 'action_logOut',
                //cls:     'x-btn-icon',
                tooltip: {text: _('Logout from Tine 2.0')},
                handler: this.logoutButtonHandler
            }]
    
        });
    
        var tineFooter = new Ext.Toolbar({
            id: 'tineFooter',
            height: 26,
            items:[
                'Account name: ' + Tine.Tinebase.Registry.get('currentAccount').accountDisplayName + ' ',
                'Timezone: ' +  Tine.Tinebase.Registry.get('timeZone')
            ]
    
        });
    
        var applicationToolbar = new Ext.Toolbar({
            id: 'applicationToolbar',
            height: 26
        });
        // default app
        applicationToolbar.on('render', function(){
            var appPanel = Ext.getCmp('Addressbook_Tree');
            if (appPanel) {
                appPanel.expand();
            }
        });
    
        var applicationArcordion = new Ext.Panel({
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
        
        this.items = [{
            region: 'north',
            id:     'north-panel',
            split:  false,
            height: 52,
            border: false,
            layout:'border',
            items: [{
                //title:  'North Panel 1',
                region: 'north',
                height: 26,
                border: false,
                id:     'north-panel-1',
                items: [
                    tineMenu
                ]
            },{
                region: 'center',
                height: 26,
                border: false,
                id:     'north-panel-2',
                items: [
                    applicationToolbar
                ]
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
            //layout:'accordion',
            defaults: {
                // applied to each contained panel
                // bodyStyle: 'padding:15px'
            },
            /*
            layoutConfig: {
                // layout-specific configs go here
                titleCollapse: true,
                animate: true,
                activeOnTop: true,
                hideCollapseTool: true
            },
            */
            items: applicationArcordion
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
        
        for(var i=0; i<userApps.length; i++) {
            var app = userApps[i];
            try{
                panels.push(Tine[app.name].getPanel());
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
        new Ext.Viewport({
            layout: 'border',
            items: this.items
        });
    },
    
    changePasswordHandler: function(_event) {
        
        var oldPassword = new Ext.form.TextField({
            inputType: 'password',
            hideLabel: false,
            id: 'oldPassword',
            fieldLabel:'old password', 
            name:'oldPassword',
            allowBlank: false,
            anchor: '100%',            
            selectOnFocus: true
        });
        
        var newPassword = new Ext.form.TextField({
            inputType: 'password',            
            hideLabel: false,
            id: 'newPassword',
            fieldLabel:'new password', 
            name:'newPassword',
            allowBlank: false,
            anchor: '100%',
            selectOnFocus: true      
        });
        
        var newPasswordSecondTime = new Ext.form.TextField({
            inputType: 'password',            
            hideLabel: false,
            id: 'newPasswordSecondTime',
            fieldLabel:'new password again', 
            name:'newPasswordSecondTime',
            allowBlank: false,
            anchor: '100%',           
            selectOnFocus: true                  
        });   

        var changePasswordForm = new Ext.FormPanel({
            baseParams: {method :'Tinebase.changePassword'},
            labelAlign: 'top',
            bodyStyle:'padding:5px',
      //      tbar: changePasswordToolbar,
            anchor:'100%',
            region: 'center',
            id: 'changePasswordPanel',
            deferredRender: false,
            items: [
                oldPassword,
                newPassword,
                newPasswordSecondTime
            ]
        });

        _savePassword = function() {
            if (changePasswordForm.getForm().isValid()) {

                var oldPassword = changePasswordForm.getForm().getValues().oldPassword;
                var newPassword = changePasswordForm.getForm().getValues().newPassword;
                var newPasswordRepeat = changePasswordForm.getForm().getValues().newPasswordSecondTime;
                
                if (newPassword == newPasswordRepeat) {
                    Ext.Ajax.request({
                        url: 'index.php',
                        waitTitle: 'Please wait!',
                        waitMsg: 'changing password...',
                        params: {
                            method: 'Tinebase.changePassword',
                            oldPassword: oldPassword,
                            newPassword: newPassword
                        },
                        success: function(form, action, o){
                            Ext.getCmp('changePassword_window').close(); 
                            Ext.MessageBox.show({
                                title: 'Success',
                                msg: 'Your password has been changed.',
                                buttons: Ext.MessageBox.OK,
                                icon: Ext.MessageBox.SUCCESS  /*,
                                fn: function() {} */
                            });
                        },
                        failure: function(form, action){
                            Ext.MessageBox.show({
                                title: 'Failure',
                                msg: 'Your old password is incorrect.',
                                buttons: Ext.MessageBox.OK,
                                icon: Ext.MessageBox.ERROR  /*,
                                fn: function() {} */
                            });
                        }
                    });
                } else {
                    Ext.MessageBox.show({
                        title: 'Failure',
                        msg: 'The new passwords mismatch, please correct them.',
                        buttons: Ext.MessageBox.OK,
                        icon: Ext.MessageBox.ERROR  /*,
                        fn: function() {} */
                    });    
                }
            }
          };
          
        var passwordDialog = new Ext.Window({
            title: 'Change password for ' + Tine.Tinebase.Registry.get('currentAccount').accountDisplayName,
            id: 'changePassword_window',
            closeAction: 'close',
            modal: true,
            width: 350,
            height: 230,
            minWidth: 350,
            minHeight: 230,
            layout: 'fit',
            plain: true,
            buttons: [
                {
                    text: 'Ok',
             //       iconCls: 'action_saveAndClose',
                    handler: _savePassword                    
                } ,
                {
                    text: 'Cancel',
//                    iconCls: 'action_saveAndClose',
                    handler: function() {
                        Ext.getCmp('changePassword_window').close();
                    }
                }
            ],
            bodyStyle: 'padding:5px;',
            buttonAlign: 'center'
        });
        passwordDialog.add(changePasswordForm);
        passwordDialog.show();            
    },
    
    /**
     * the logout button handler function
     * @private
     */
    logoutButtonHandler: function(_event) {
        Ext.MessageBox.confirm('Confirm', 'Are you sure you want to logout?', function(btn, text) {
            if (btn == 'yes') {
                Ext.MessageBox.wait('Logging you out...', 'Please wait!');
                Ext.Ajax.request( {
                    params : {
                        method : 'Tinebase.logout'
                    },
                    callback : function(options, bSuccess, response) {
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
        
        if(centerPanel.items) {
            for (var i=0; i<centerPanel.items.length; i++){
                var p =  centerPanel.items.get(i);
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
        
        if(northPanel.items) {
            return northPanel.items.get(0);
        } else {
            return false;
        }
    },
    
    /**
     * sets toolbar
     * 
     * @param {Ext.Toolbar}
     */
    setActiveToolbar: function(_toolbar) {
        var northPanel = Ext.getCmp('north-panel-2');
        if(northPanel.items) {
            for (var i=0; i<northPanel.items.length; i++){
                northPanel.remove(northPanel.items.get(i));
            }  
        }
        //var toolbarPanel = Ext.getCmp('applicationToolbar');
        
        northPanel.add(_toolbar);
        northPanel.doLayout();
    }

});