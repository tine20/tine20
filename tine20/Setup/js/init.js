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
 
/*
Tine.Tinebase.tineInit.renderWindow = function() {
    Ext.Msg.alert('Tine 2.0 Setup', 'Press OK to do nothing');
}
*/

/**
 * init ajax
 */
Tine.Tinebase.tineInit.initAjax = Tine.Tinebase.tineInit.initAjax.createInterceptor(function() {
    Tine.Tinebase.tineInit.requestUrl = 'setup.php'
    
    return true;
});

/**
 * init registry
 */
Tine.Tinebase.tineInit.initRegistry = Tine.Tinebase.tineInit.initRegistry.createInterceptor(function() {
    Tine.Tinebase.tineInit.initList.getAllRegistryDataMethod = 'Setup.getAllRegistryData';
    
    return true;
});

/**
 * render window
 */
Tine.Tinebase.tineInit.renderWindow = Tine.Tinebase.tineInit.renderWindow.createInterceptor(function() {
    
    // fake a setup user
    var setupUser = new Tine.Tinebase.Model.User({
        accountId           : 0,
        accountDisplayName  : 'Setup Admin',
        accountLastName     : 'Admin',
        accountFirstName    : 'Setup',
        accountFullName     : 'Setup Admin'
    }, 0);
    Tine.Tinebase.registry.add('currentAccount', setupUser);
    
    // enable setup app
    Tine.Tinebase.registry.add('userApplications', [{
        name:   'Setup',
        status: 'enabled'
    }]);
    Tine.Tinebase.MainScreen.prototype.defaultAppName = 'Setup';
    
    
    return true;
});