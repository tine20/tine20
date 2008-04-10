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
 * Expandable panel
 * <p>This class provieds a expandable panel. The first item is allways visable, whereas 
 * all furthor items are colapsed by default<p>
 * <p>Example usage:</p>
 * <pre><code>
 var p = new Ext.ux.ExpandPanel({
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
 * <p>The <b>xtype</b> of this panel is <b>expandpanel</b>.
 */
Ext.ux.ExpandPanel = Ext.extend(Ext.Panel, {
    isExpanded: true,
    
    /**
     * @private
     */
    initComponent: function(){
        Ext.ux.ExpandPanel.superclass.initComponent.call(this);
    },
    /**
     * @private
     */
    onRender : function(ct, position){
        Ext.ux.ExpandPanel.superclass.onRender.call(this, ct, position);
        this.expandArea = this.items.items[1];
        
        this.expanderButton = this.getEl().createChild({
            cls: 'x-tool x-tool-toggle'
        });
        this.expanderButton.on('click', function(){
            this.toggleExpandation();
        }, this);
        
        this.toggleExpandation();
    },
    /**
     * toggles visability of expand area
     */
    toggleExpandation: function(){
        this.isExpanded = !this.isExpanded;
        var panelCount = 0;
        this.items.each(function(item){
            if(panelCount > 0) {
                item.setVisible(this.isExpanded);
            }
            panelCount++;
        }, this);
        
        if (this.isExpanded){
            this.getEl().removeClass('x-panel-collapsed');
        } else {
            this.getEl().addClass('x-panel-collapsed');
        }
    }
});
Ext.reg('expanderpanel', Ext.ux.ExpandPanel);