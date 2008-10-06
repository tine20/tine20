/*
 * Tine 2.0
 * 
 * @package     Tine
 * @subpackage  Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Tine.widgets');

/**
 * check if newer version of Tine 2.0 is available
 * @class Tine.widgets.VersionCheck
 * @constructor
 */
Tine.widgets.VersionCheck = function() {
    
    var ds = new Ext.data.Store({
        proxy: new Ext.data.ScriptTagProxy({
            url: 'https://versioncheck.officespot.net/versionCheck.php'
        }),
        reader: new Ext.data.JsonReader({
            root: 'version'
        }, ['codename', 'packageString', 'releasedate', 'critical', 'build'])
    });
    ds.on('load', function(store, records) {
        var version = records[0];
        
        var local = Date.parseDate(Tine.Tinebase.Registry.get('version').releasetime, Date.patterns.ISO8601Long);
        var latest = Date.parseDate(version.get('releasetime'), Date.patterns.ISO8601Long);
        
        if (latest > local && Tine.Tinebase.hasRight('run', 'Tinebase')) {
            if (version.get('critical') == true) {
                Ext.MessageBox.show({
                    title: _('New version of Tine 2.0 available'), 
                    msg: sprintf(_('Version "%s" of Tine 2.0 is available.'), version.get('codename')) + "\n" +
                                 _("It's a critical update and must be installed as soon as possible!"),
                    width: 500,
                    buttons: Ext.Msg.OK,
                    icon: Ext.MessageBox.ERROR
                });
            } else {
                Ext.MessageBox.show({
                    title: _('New version of Tine 2.0 available'),
                    msg: sprintf(_('Version "%s" of Tine 2.0 is available.'), version.get('codename')) + "\n" +
                                 _('Please consider updating!'),
                    width: 400,
                    buttons: Ext.Msg.OK,
                    icon: Ext.MessageBox.INFO
                });
            }
        }
    }, this);
    
    ds.load({params: {version: Ext.util.JSON.encode(Tine.Tinebase.Registry.get('version'))}});
};
