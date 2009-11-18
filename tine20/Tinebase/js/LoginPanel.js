/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
 
Ext.namespace('Tine.Tinebase');

/**
 * @class Tine.Tinebase.LoginPanel
 * @namespace Tine.Tinebase
 * @extends Ext.Panel
 * @author Cornelius Weiss <c.weiss@metaways.de>
 * @version $Id$
 */
Tine.Tinebase.LoginPanel = Ext.extend(Ext.Panel, {
    
    /**
     * @cfg {String} defaultUsername prefilled username
     */
    defaultUsername: '',
    
    /**
     * @cfg {String} defaultPassword prefilled password
     */
    defaultPassword: '',
    
    /**
     * @cfg {String} loginMethod server side login method
     */
    loginMethod: 'Tinebase.login',
    
    /**
     * @cfg {String} loginLogo logo to show
     */
    loginLogo: 'images/tine_logo.gif',
    
    /**
     * @cfg {String} onLogin callback after successfull login
     */
    onLogin: Ext.emptyFn,
    
    /**
     * @cfg {String} scope scope of login callback
     */
    scope: null,
    
    layout: 'fit',
    border: false,
    
    /**
     * return loginPanel
     * 
     * @return {Ext.FromPanel}
     */
    getLoginPanel: function() {
        if (! this.loginPanel) {
            this.loginPanel = new Ext.FormPanel({
                frame:true,
                labelWidth: 90,
                cls: 'tb-login-panel',
                items: [{
                    cls: 'tb-login-lobobox',
                    border: false,
                    html: '<a target="_blank" href="http://www.tine20.org/" border="0"><img src="' + this.loginLogo +'" /></a>'
                }, {
                    xtype: 'label',
                    cls: 'tb-login-big-label',
                    text: _('Login')
                },{
                    cls: 'tb-login-big-label-spacer',
                    border: false,
                    html: ''
                }, new Tine.widgets.LangChooser({
                    width: 170,
                    tabindex: 1
                }), {
                    xtype: 'textfield',
                    tabindex: 2,
                    width: 170,
                    fieldLabel: _('Username'),
                    id: 'username',
                    name: 'username',
                    selectOnFocus: true,
                    value: this.defaultUsername,
                    listeners: {render: function(field){field.focus(false, 250);}}
                }, {
                    xtype: 'textfield',
                    tabindex: 3,
                    width: 170,
                    inputType: 'password',
                    fieldLabel: _('Password'),
                    id: 'password',
                    name: 'password',
                    //allowBlank: false,
                    selectOnFocus: true,
                    value: this.defaultPassword
                },{
                    cls: 'tb-login-button-spacer',
                    border: false,
                    html: ''
                }, {
                    xtype: 'button',
                    width: 120,
                    text: _('Login'),
                    scope: this,
                    handler: this.onLoginPress
                }]
            });
        }
        
        return this.loginPanel;
    },
    
    initComponent: function() {
        this.tinePanel = new Ext.Panel({
            border: false
            //html: 'links'
        });
        this.surveyPanel = new Ext.Panel({
            border: false
            //html: 'surveys'
        });
        
        this.initLayout();
        
        this.supr().initComponent.call(this);
    },
    
    /**
     * initialize base layout
     */
    initLayout: function() {
        
        this.items = [{
            layout: 'vbox',
            border: false,
            layoutConfig: {
                align:'stretch'
            },
            items: [{
                border: false,
                flex: 0,
                height: 140
            }, {
                layout: 'hbox',
                flex: 1,
                border: false,
                layoutConfig: {
                    align: 'stretch'
                },
                items: [{
                    flex: 7,
                    border: false,
                    layout: 'hbox',
                    layoutConfig: {
                        align: 'stretch'
                    },
                    items: [{
                        flex: 1,
                        border: false
                    }, {
                        flex: 0,
                        border: false,
                        width: 460,
                        items: this.getLoginPanel()
                    }]
                }, {
                    layout: 'vbox',
                    border: false,
                    flex: 3,
                    items: [
                        this.tinePanel,
                        this.surveyPanel
                    ]
                }]
            }]
        }]; 
    },
    
    /**
     * do the actual login
     */
    onLoginPress: function(){
        var form = this.getLoginPanel().getForm();
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
                        window.document.title = this.originalTitle;
                        this.onLogin.call(this.scope);
                    } else {
                        Ext.MessageBox.show({
                            title: _('Login failure'),
                            msg: _('Your username and/or your password are wrong!!!'),
                            buttons: Ext.MessageBox.OK,
                            icon: Ext.MessageBox.ERROR,
                            fn: function() {
                                this.getLoginPanel().getForm().findField('username').focus(true);
                            }.createDelegate(this)
                        });
                    }
                }
            });
        }
    },
    
    onRender: function(ct, position) {
        this.supr().onRender.apply(this, arguments);
        
        this.map = new Ext.KeyMap(this.el, [{
            key : [10, 13],
            scope : this,
            fn : this.onLoginPress
        }]);
        
        this.originalTitle = window.document.title;
        var postfix = (Tine.Tinebase.registry.get('titlePostfix')) ? Tine.Tinebase.registry.get('titlePostfix') : '';
        window.document.title = Tine.title + postfix + ' - ' + _('Please enter your login data');
    }
});