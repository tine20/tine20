/*
 * Tine 2.0
 * 
 * @package     mobileClient
 * @subpackage  Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.onReady(function() {
    new Ext.Viewport({
        id: 'mobileViewport',
        layout: 'card',
        activeItem: 0,
        items: [
            Tine.mobileClient.getSettingsPanel()
        ]
    });
});

/**
 * Settings Panel
 */
Tine.mobileClient.getSettingsPanel = function() { 
    return new Ext.FormPanel({
        id: 'mobileSettingsPanel',
        title: 'Settings',
        tbar: [
            '->',
            {text: 'Save', handler: function() {
                var form = Ext.getCmp('mobileSettingsPanel').getForm();
                var connection = new Tine.mobileClient.Connection({
                    url: form.findField('url').getValue()
                });
                
                var username = form.findField('username').getValue();
                var password = form.findField('passoword').getValue();
                connection.login(username, password, function() {
                    var viewport = Ext.getCmp('mobileViewport');
                    var tasksApp = Ext.getCmp('mobileTaskAppPanel');
                    if (! tasksApp) {
                        tasksApp = Tine.mobileClient.Tasks.getAppPanel();
                        viewport.add(tasksApp);
                    }
                    viewport.layout.setActiveItem(tasksApp.id);
                });
            }}
        ],
        labelAlign: 'top',
        defaults: {
            anchor: '100%'
        },
        items: [{
            xtype: 'textfield',
            name: 'url',
            fieldLabel: 'Url',
            value: '/tine20/index.php'
        }, {
            xtype: 'textfield',
            name: 'username',
            fieldLabel: 'Username',
            value: 'tine20admin'
        }, {
            xtype: 'textfield',
            name: 'passoword',
            inputType: 'password',
            fieldLabel: 'Password',
            value: 'lars'
        }, {
            xtype: 'checkbox',
            name: 'storeLoginData',
            fieldLabel: 'Save Login Information',
            disabled: true
        }]
    })
};