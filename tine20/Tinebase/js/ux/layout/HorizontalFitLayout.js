/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Ext.ux', 'Ext.ux.layout');

/**
 * @class Ext.ux.layout.HorizontalFitLayout
 * @extends Ext.layout.ContainerLayout
 * <p>This is a base class for layouts that contain a single item that automatically expands horizontally to fill 
 * the horizontal dimenson of the layout's container.  This class is intended to be extended or created via the 
 * layout:'hfit' {@link Ext.Container#layout} config, and should generally not need to be created directly via 
 * the new keyword.</p>
 * <p>FitLayout does not have any direct config options (other than inherited ones).  To fit a panel to a container
 * horizontally using Horizontal FitLayout, simply set layout:'hfit' on the container and add a multiple panel to it.
 * Example usage:</p>
 * <pre><code>
var p = new Ext.Panel({
    title: 'Horizontal Fit Layout',
    layout:'hfit',
    items: [{
        title: 'Inner Panel One',
        html: '&lt;p&gt;This is the firsts inner panel content&lt;/p&gt;',
        border: false
    },{
        title: 'Inner Panel Two',
        html: '&lt;p&gt;This is the seconds inner panel content&lt;/p&gt;',
        border: false
    }]
});
</code></pre>
 */
Ext.ux.layout.HorizontalFitLayout = Ext.extend(Ext.layout.ContainerLayout, {
    /**
     * @private
     */
    monitorResize:true,

    /**
     * @private
     */
    onLayout : function(ct, target){
        Ext.layout.FitLayout.superclass.onLayout.call(this, ct, target);
        if(!this.container.collapsed){
            ct.items.each(function(item){
                this.setItemSize(item,  target.getStyleSize());
            }, this);
        }
    },
    /**
     * @private
     */
    setItemSize : function(item, size){
        if(item && size.height > 0){ // display none?
            item.setWidth(size.width);
        }
    }
});
Ext.Container.LAYOUTS['hfit'] = Ext.ux.layout.HorizontalFitLayout;