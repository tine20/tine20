/*
 * Ext JS Library 2.0 Alpha 1
 * Copyright(c) 2006-2007, Ext JS, LLC.
 * licensing@extjs.com
 * 
 * http://extjs.com/license
 */

Ext.layout.FitLayout=Ext.extend(Ext.layout.ContainerLayout,{monitorResize:true,onLayout:function(A,B){Ext.layout.FitLayout.superclass.onLayout.call(this,A,B);this.setItemSize(this.activeItem||A.items.itemAt(0),B.getStyleSize())},setItemSize:function(B,A){if(B){B.setSize(A)}}});Ext.Container.LAYOUTS["fit"]=Ext.layout.FitLayout;