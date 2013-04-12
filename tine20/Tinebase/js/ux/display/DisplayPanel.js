/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Ext.ux.display');

/**
 * @class       Ext.ux.display.DisplayPanel
 * @namespace   Ext.ux.display
 * @extends     Ext.form.FormPanel
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * 
 * <b>Panel for displaying information on record basis.</b>
 */
Ext.ux.display.DisplayPanel = Ext.extend(Ext.Panel, {
    
    /*
     * private
     */
    cls : 'x-ux-display',
    layout: 'ux.display',
    
    /**
     * holds all display fields of the panel
     * 
     * @type {Ext.util.MixedCollection}
     */
    fields: null,
    
    /**
     * initializes the component, builds this.fields, calls parent
     */
    initComponent: function() {
        Ext.ux.display.DisplayPanel.superclass.initComponent.call(this);
        
        this.fields = new Ext.util.MixedCollection();
        this.fields.addAll(this.findByType('ux.displayfield'));
        this.fields.addAll(this.findByType('ux.displaytextarea'));
    },
    
    /**
     * fills this fields with the corresponding record data
     * 
     * @param {Tine.Tinebase.data.Record} record
     */
    loadRecord: function(record) {
        this.fields.each(function(field) {
            var data = record.get(field.name) ? record.get(field.name) : '';
            field.setValue(data);
        });
    }
});

Ext.reg('ux.displaypanel', Ext.ux.display.DisplayPanel);
