/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
Ext.ns('Ext.ux.form');

/**
 * @namespace   Ext.ux.form
 * @class       Ext.ux.form.BrowseButton
 * @extends     Ext.Button
 */
Ext.ux.BrowseButton = Ext.extend(Ext.Button, {

    initComponent: function() {
        this.plugins = this.plugins || [];
        this.plugins.push( new Ext.ux.file.BrowsePlugin({}));
        
        Ext.ux.BrowseButton.superclass.initComponent.call(this);
    }
});

Ext.reg('browsebutton', Ext.ux.BrowseButton);
