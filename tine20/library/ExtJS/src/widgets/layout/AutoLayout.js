/*!
 * Ext JS Library 3.1.1
 * Copyright(c) 2006-2010 Ext JS, LLC
 * licensing@extjs.com
 * http://www.extjs.com/license
 */
/**
 * @class Ext.layout.AutoLayout
 * <p>The AutoLayout is the default layout manager delegated by {@link Ext.Container} to
 * render any child Components when no <tt>{@link Ext.Container#layout layout}</tt> is configured into
 * a {@link Ext.Container Container}. ContainerLayout provides the basic foundation for all other layout
 * classes in Ext. It simply renders all child Components into the Container, performing no sizing or
 * positioning services. To utilize a layout that provides sizing and positioning of child Components,
 * specify an appropriate <tt>{@link Ext.Container#layout layout}</tt>.</p>
 */
Ext.layout.AutoLayout = Ext.extend(Ext.layout.ContainerLayout, {
    runLayout: function(){
        var ct = this.container;
        ct.doLayout();
        delete ct.layoutPending;
    }
});

Ext.Container.LAYOUTS['auto'] = Ext.layout.AutoLayout;
