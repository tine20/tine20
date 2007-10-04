/*
 * Ext JS Library 2.0 Alpha 1
 * Copyright(c) 2006-2007, Ext JS, LLC.
 * licensing@extjs.com
 * 
 * http://extjs.com/license
 */

Ext.Tip=Ext.extend(Ext.Panel,{frame:true,hidden:true,baseCls:"x-tip",floating:{shadow:true,shim:true,useDisplay:true,constrain:false},autoHeight:true,minWidth:40,maxWidth:300,shadow:"sides",defaultAlign:"tl-bl?",autoRender:true,quickShowInterval:250,afterRender:function(){Ext.Tip.superclass.afterRender.call(this);if(this.closable){this.addTool({id:"close",handler:this.hide,scope:this})}},showAt:function(A){Ext.Tip.superclass.show.call(this);if(this.measureWidth!==false){var B=this.body.getTextWidth();if(this.title){B=Math.max(B,this.header.child("span").getTextWidth(this.title))}B+=this.getFrameWidth()+(this.closable?20:0);this.setWidth(B.constrain(this.minWidth,this.maxWidth))}if(this.constrainPosition){A=this.el.adjustForConstraints(A)}this.setPagePosition(A[0],A[1])},showBy:function(A,B){if(!this.rendered){this.render(Ext.getBody())}this.showAt(this.el.getAlignToXY(A,B||this.defaultAlign))}});