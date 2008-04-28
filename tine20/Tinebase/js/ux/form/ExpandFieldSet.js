/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Ext.ux', 'Ext.ux.form');

/**
 * Expandable Panel
 * <p>This class provieds a expandable fieldset. The first item is allways visable, whereas 
 * all furthor items are colapsed by default<p>
 * <p>Example usage:</p>
 * <pre><code>
 var p = new Ext.ux.ExpandFieldSet({
     name: 'Expandable Panel',
     items: [
        {
            xtype: 'panel'
            html: 'This panel is always visable'
        },
        {
            xtype: 'panel'
            html: 'This panel is colabsed by default'
        }
     ]
 });
 * </code></pre>
 * <p>The <b>xtype</b> of this panel is <b>expanderfieldset</b>.
 * @todo Generalise this an inherit from Ext.Panel
 */
Ext.ux.form.ExpandFieldSet = Ext.extend(Ext.form.FieldSet, {
    /**
     * @private
     */
    initComponent: function(){
        Ext.ux.form.ExpandFieldSet.superclass.initComponent.call(this);
        
        var panelCount = 0;
        this.items.each(function(item){
            if(panelCount > 0) {
                item.collapsed = true;
                item.on('expand', function(){
                    var innerWidth = this.getInnerWidth();
                    item.setWidth(innerWidth);
                }, this);
            }
            panelCount++;
        }, this);
        this.collapsed = true;
    },
    onRender : function(ct, position){
        Ext.ux.form.ExpandFieldSet.superclass.onRender.call(this, ct, position);
        this.el.addClass('x-tool-expand');
    },
    
    expand: function(animate) {
        var panelCount = 0;
        this.items.each(function(item){
            if(panelCount > 0) {
                item.expand(animate);
            }
            panelCount++;
        }, this);
        this.el.removeClass('x-tool-expand');
        this.el.addClass('x-tool-collapse');
        //this.el.addClass('x-tool-minimize');
        this.collapsed = false;
    },
    collapse: function(animate) {
        var panelCount = 0;
        this.items.each(function(item){
            if(panelCount > 0) {
                item.collapse(animate);
            }
            panelCount++;
        }, this);
        //this.el.addClass(this.collapsedCls);
        this.el.removeClass('x-tool-collapse');
        this.el.addClass('x-tool-expand');
        
        this.collapsed = true;
    }
    
    

});

Ext.reg('expanderfieldset', Ext.ux.form.ExpandFieldSet);