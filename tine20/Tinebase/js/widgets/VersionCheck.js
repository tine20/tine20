/*
 * Tine 2.0
 * 
 * @package     Tine
 * @subpackage  Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.widgets');

/**
 * check if newer version of Tine 2.0 is available
 * 
 * @namespace   Tine.widgets
 * @class       Tine.widgets.VersionCheck
 * @constructor
 */
Tine.widgets.VersionCheck = function() {
    
    var ds = new Ext.data.Store({
        proxy: new Ext.data.ScriptTagProxy({
            url: 'https://versioncheck.tine20.net/versionCheck/versionCheck.php'
        }),
        reader: new Ext.data.JsonReader({
            root: 'version'
        }, ['codeName', 'packageString', 'releaseTime', 'critical', 'build'])
    });
    ds.on('load', function(store, records) {
        if (! Tine.Tinebase.registry.get('version')) {
            return false;
        }
        
        var availableVersion = records[0];
            installedVersion = Tine.Tinebase.registry.get('version');
        
        Tine.log.debug('Available Tine version:');
        Tine.log.debug(availableVersion);
        Tine.log.debug('Installed Tine version:');
        Tine.log.debug(installedVersion);
        
        var local = Date.parseDate(installedVersion.releaseTime, Date.patterns.ISO8601Long);
        var latest = Date.parseDate(availableVersion.get('releaseTime'), Date.patterns.ISO8601Long);
        
        if (latest > local && Tine.Tinebase.common.hasRight('run', 'Tinebase')) {
            var versionString = availableVersion.get('codeName') + ' ' + availableVersion.get('packageString');
            if (availableVersion.get('critical') == true) {
                Ext.MessageBox.show({
                    title: i18n._('New version of Tine 2.0 available'),
                    msg: String.format(i18n._('Version "{0}" of Tine 2.0 is available.'), versionString) + "\n" +
                                 i18n._("It's a critical update and must be installed as soon as possible!"),
                    width: 500,
                    buttons: Ext.Msg.OK,
                    icon: Ext.MessageBox.ERROR
                });
            } else {
                Ext.MessageBox.show({
                    title: i18n._('New version of Tine 2.0 available'),
                    msg: String.format(i18n._('Version "{0}" of Tine 2.0 is available.'), versionString) + "\n" +
                                 i18n._('Please consider updating!'),
                    width: 400,
                    buttons: Ext.Msg.OK,
                    icon: Ext.MessageBox.INFO
                });
            }
        }
    }, this);
    
    ds.load({params: {version: Ext.util.JSON.encode(Tine.Tinebase.registry.get('version'))}});
};
