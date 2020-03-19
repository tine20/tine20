/*
 * Tine 2.0
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/*global Ext, Tine*/

Ext.ns('Tine', 'Tine.Setup');

/**
 * local storage prefix for Setup
 */
Tine.Tinebase.tineInit.initAjax.lsPrefix = Tine.Tinebase.common.getUrl('path') + 'TineSetup';

/**
 * init ajax
 */
Tine.Tinebase.tineInit.initAjax = Tine.Tinebase.tineInit.initAjax.createInterceptor(function () {
    // setup calls can take quite a while
    Ext.Ajax.timeout = 900000; // 15 mins
    Tine.Tinebase.tineInit.requestUrl = 'setup.php';
    
    return true;
});

/**
 * init registry
 */
Tine.Tinebase.tineInit.initRegistry = Tine.Tinebase.tineInit.initRegistry.createInterceptor(async function () {
    await Tine.Tinebase.tineInit.clearRegistry();
    Tine.Tinebase.tineInit.getAllRegistryDataMethod = 'Setup.getAllRegistryData';
    Tine.Tinebase.tineInit.jsonKeyCookieId = 'TINE20SETUPJSONKEY';
    Tine.Tinebase.tineInit.stateful = false;
});

Tine.Tinebase.tineInit.onRegistryLoad = Tine.Tinebase.tineInit.onRegistryLoad.createInterceptor(function () {
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
    Tine.Tinebase.MainScreenPanel.prototype.defaultAppName = 'Setup';
    Tine.Tinebase.MainScreenPanel.prototype.hideAppTabs = true;

    return true;
});

/**
 * render window
 */
Tine.Tinebase.tineInit.renderWindow = Tine.Tinebase.tineInit.renderWindow.createInterceptor(function () {
    var mainCardPanel = Tine.Tinebase.viewport.tineViewportMaincardpanel;
    
    // if a config file exists, the admin needs to login!        
    if (Tine.Setup.registry.get('configExists') && !Tine.Setup.registry.get('currentAccount')) {
        Tine.loginPanel = new Tine.Tinebase.LoginPanel({
            loginMethod: 'Setup.login',
            headsUpText: window.i18n._('Setup'),
            scope: this,
            onLogin: async function (response) {
                await Tine.Tinebase.tineInit.initRegistry(true);
                Ext.MessageBox.hide();
                Tine.Tinebase.tineInit.renderWindow();
            }
        });
        mainCardPanel.layout.container.add(Tine.loginPanel);
        mainCardPanel.layout.setActiveItem(Tine.loginPanel.id);
        Tine.loginPanel.doLayout();
        
        return false;
    }
});

