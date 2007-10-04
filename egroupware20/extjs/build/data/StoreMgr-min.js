/*
 * Ext JS Library 2.0 Alpha 1
 * Copyright(c) 2006-2007, Ext JS, LLC.
 * licensing@extjs.com
 * 
 * http://extjs.com/license
 */

Ext.StoreMgr=Ext.apply(new Ext.util.MixedCollection(),{register:function(){for(var A=0,B;B=arguments[A];A++){this.add(B)}},unregister:function(){for(var A=0,B;B=arguments[A];A++){this.remove(this.lookup(B))}},lookup:function(A){return typeof A=="object"?A:this.get(A)}});