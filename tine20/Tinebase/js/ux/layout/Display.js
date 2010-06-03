/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
 
Ext.ns('Ext.ux.layout');

/**
 * @namespace   Ext.ux.layout
 * @class       Ext.ux.layout.DisplayLayout
 * @extends     Ext.layout.FormLayout
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 * 
 * <b>Layout for displaying information in a displaypanel</b>
 */
Ext.ux.layout.DisplayLayout = Ext.extend(Ext.layout.FormLayout, {
    background: 'none',
    
    onLayout : function(ct, target) {
        Ext.ux.layout.DisplayLayout.superclass.onLayout.apply(this, arguments);
        
        target.addClass('x-ux-display-background-' + this.background);
        
        if (this.declaration && ! this.declEl) {
            this.declEl = target.createChild({dom: 'div', html: this.declaration, 'class': 'x-ux-display-declaration x-ux-display-background-declaration'});
        }
    },
    
    destroy: function() {
        if (this.declEl) {
            this.declEl.remove();
        }
        
        Ext.ux.layout.DisplayLayout.superclass.destroy.call(this);
    }
});

Ext.Container.LAYOUTS['ux.display'] = Ext.ux.layout.DisplayLayout;