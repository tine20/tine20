/*
 * Tine 2.0
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.ns('Tine', 'Tine.Setup');
 
/**
 * init ajax
 */
Tine.Tinebase.tineInit.initAjax = Tine.Tinebase.tineInit.initAjax.createInterceptor(function() {
    // setup calls can take quite a while
    Ext.Ajax.timeout = 300000;
    Tine.Tinebase.tineInit.requestUrl = 'setup.php'
    
    return true;
});

/**
 * init registry
 */
Tine.Tinebase.tineInit.initRegistry = Tine.Tinebase.tineInit.initRegistry.createInterceptor(function() {
    Tine.Tinebase.tineInit.getAllRegistryDataMethod = 'Setup.getAllRegistryData';
    Tine.Tinebase.tineInit.stateful = false;
    
    return true;
});

Tine.Tinebase.tineInit.checkSelfUpdate = Ext.emptyFn;

/**
 * render window
 */
Tine.Tinebase.tineInit.renderWindow = Tine.Tinebase.tineInit.renderWindow.createInterceptor(function() {
    var mainCardPanel = Ext.getCmp('tine-viewport-maincardpanel');
    
    // if a config file exists, the admin needs to login!        
    if (Tine.Setup.registry.get('configExists') && !Tine.Setup.registry.get('currentAccount')) {
        if (! Tine.loginPanel) {
            Tine.loginPanel = new Tine.Tinebase.LoginPanel({
                loginMethod: 'Setup.login',
                loginLogo: 'images/tine_logo_setup.png',
                scope: this,
                onLogin: function(response) {
                    Tine.Tinebase.tineInit.initList.initRegistry = false;
                    Tine.Tinebase.tineInit.initRegistry();
                    var waitForRegistry = function() {
                        if (Tine.Tinebase.tineInit.initList.initRegistry) {
                            Ext.MessageBox.hide();
                            Tine.Tinebase.tineInit.renderWindow();
                        } else {
                            waitForRegistry.defer(100);
                        }
                    };
                    waitForRegistry();
                }
            });
            mainCardPanel.layout.container.add(Tine.loginPanel);
        }
        mainCardPanel.layout.setActiveItem(Tine.loginPanel.id);
        Tine.loginPanel.doLayout();
        
        return false;
    }
        
    // fake a setup user
    var setupUser = {
        accountId           : 1,
        accountDisplayName  : Tine.Setup.registry.get('currentAccount'),
        accountLastName     : 'Admin',
        accountFirstName    : 'Setup',
        accountFullName     : 'Setup Admin'
    };
    Tine.Tinebase.registry.add('currentAccount', setupUser);
    
    // enable setup app
    Tine.Tinebase.registry.add('userApplications', [{
        name:   'Setup',
        status: 'enabled'
    }]);
    Tine.Tinebase.MainScreen.prototype.defaultAppName = 'Setup';
    
    return true;
});