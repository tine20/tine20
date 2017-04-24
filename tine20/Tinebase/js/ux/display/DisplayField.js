/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Ext.ux.display');

/**
 * @class       Ext.ux.display.DisplayField
 * @namespace   Ext.ux.display
 * @extends     Ext.form.DisplayField
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * 
 * <b>Field for displaying information in a displaypanel</b>
 */
Ext.ux.display.DisplayField = Ext.extend(Ext.form.DisplayField, {
    htmlEncode: true,
    nl2br: false,

    // don't enumerate as form field
    markInvalid: null,

    renderer: function(v) {
        return v;
    },

    // try to apply grid compatible renderer
    applyRenderer: function(v) {
        var parentWithRecord = this.findParentBy(function(c) {return !!c.record}),
            metadata = {css: {}, attr: {}},
            record = parentWithRecord ? parentWithRecord.record : null,
            store = record ? record.store : null;

        return this.renderer(v, metadata, record, 0, 0, store);
    },

    setRawValue : function(value) {
        var v = this.applyRenderer(value);

        if (String(v) == '[object Object]') {
            v = '...';
            this.hide();
        }
        
        if(this.htmlEncode){
            v = Ext.util.Format.htmlEncode(v);
        }
        
        if (this.nl2br) {
            v = Ext.util.Format.nl2br(v);
        }
        
        return this.rendered ? (this.el.dom.innerHTML = (Ext.isEmpty(v) ? '' : v)) : (this.value = v);
    }

});

Ext.reg('ux.displayfield', Ext.ux.display.DisplayField);
