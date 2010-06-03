/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
 
Ext.ns('Ext.ux.display');

/**
 * @class       Ext.ux.display.DisplayPanel
 * @namespace   Ext.ux.display
 * @extends     Ext.form.FormPanel
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 * 
 * <b>Panel for displaying information on record basis.</b>
 */
Ext.ux.display.DisplayPanel = Ext.extend(Ext.form.FormPanel, {
    cls : 'x-ux-display',
    
    layout: 'ux.display',
    
    loadRecord: function(record) {
        return this.getForm().loadRecord(record);
    },
    
    onRender: function() {
        this.supr().onRender.apply(this, arguments);
    }
});

Ext.reg('ux.displaypanel', Ext.ux.display.DisplayPanel);