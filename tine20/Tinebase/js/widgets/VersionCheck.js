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

Tine.widgets.VersionCheck = function() {
    
    var ds = new Ext.data.Store({
        proxy: new Ext.data.ScriptTagProxy({
            url: 'http://localhost/versionCheck.php'
        }),
        reader: new Ext.data.JsonReader({
            root: 'version',
        }, ['codename', 'packageString', 'releasedate', 'critical', 'build'])
    });
    ds.on('load', function(store, records) {
        var version = records[0];
        console.log(version.data);
    }, this);
    
    ds.load({params: {version: Ext.util.JSON.encode(Tine.Build)}});
}