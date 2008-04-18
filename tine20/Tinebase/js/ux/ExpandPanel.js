/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Ext.ux');

/**
 * Expandable fieldset
 * <p>This class provieds a expandable fieldset. The first item is allways visable, whereas 
 * all furthor items are colapsed by default<p>
 * <p>Example usage:</p>
 * <pre><code>
 var p = new Ext.ux.ExpandFieldSet({
     name: 'Expandable Panel',
     items: [
        {
            xtype: 'panel'
            html: '<p>This panel is always visable</p>'
        },
        {
            xtype: 'panel'
            html: '<p>This panel is colabsed by default</p>'
        }
     ]
 });
 * </code></pre>
 * <p>The <b>xtype</b> of this panel is <b>expanderfieldset</b>.
 * @todo Generalise this an inherit from Ext.Panel
 */
Ext.ux.ExpandFieldSet = Ext.extend(Ext.form.FieldSet, {
    isExpanded: true,
    
    /**
     * @private
     */
    initComponent: function(){
        Ext.ux.ExpandFieldSet.superclass.initComponent.call(this);
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
    },
    /**
     * @private
     */
    onRender : function(ct, position){
        Ext.ux.ExpandFieldSet.superclass.onRender.call(this, ct, position);
        
        this.expanderButton = this.getEl().createChild({
            cls: 'x-panel-collapsed'
        });
        this.expanderButton.createChild({
            cls: 'x-tool x-tool-toggle x-tool-toggle-expander'
        });
        
        this.expanderButton.on('click', function(){
            this.toggleExpandation();
        }, this);
    },
    /**
     * toggles visability of expand area
     */
    toggleExpandation: function(){
        this.isExpanded = !this.isExpanded;
        var panelCount = 0;
        this.items.each(function(item){
            if(panelCount > 0) {
                item[!this.isExpanded ? 'expand' : 'collapse']();
            }
            panelCount++;
        }, this);
        this.expanderButton[this.isExpanded ? 'addClass' : 'removeClass']('x-panel-collapsed');
    },
});
Ext.reg('expanderfieldset', Ext.ux.ExpandFieldSet);