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
 
Tine.Setup.ConfigManagerGridPanel = Ext.extend(Ext.Panel, {
    border: false,
    actionToolbar: new Ext.Toolbar({}),
    
    // fake a store to satisfy grid panel
    store: {
        load: function() {
        
        }
    },
    
    
    initComponent: function() {
        
        if (Tine.Setup.registry.get('configExists')) {
            if (Tine.Setup.registry.get('checkDB')) {
                this.html = 'Config file found! Database is Accessable!';
            } else {
                this.html = 'A Config file exists, but the database could not be accessed. Please check the config file!';
            }
        } else {
            this.html = 'No config file found. You need to copy the config.inc.php.dist to config.inc.php and adopt its contents';
        }
        
        Tine.Setup.ConfigManagerGridPanel.superclass.initComponent.call(this);
    }
}); 