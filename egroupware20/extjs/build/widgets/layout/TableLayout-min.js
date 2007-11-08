/*
 * Ext JS Library 2.0 RC 1
 * Copyright(c) 2006-2007, Ext JS, LLC.
 * licensing@extjs.com
 * 
 * http://extjs.com/license
 */

Ext.layout.TableLayout=Ext.extend(Ext.layout.ContainerLayout,{monitorResize:false,setContainer:function(A){Ext.layout.TableLayout.superclass.setContainer.call(this,A);this.currentRow=0;this.currentColumn=0},onLayout:function(C,E){var D=C.items.items,A=D.length,F,B;if(!this.table){E.addClass("x-table-layout-ct");this.table=E.createChild({tag:"table",cls:"x-table-layout",cellspacing:0,cn:{tag:"tbody"}},null,true);this.renderAll(C,E)}},getRow:function(A){var B=this.table.tBodies[0].childNodes[A];if(!B){B=document.createElement("tr");this.table.tBodies[0].appendChild(B)}return B},getNextCell:function(C){var B=document.createElement("td"),A;if(!this.columns){A=this.getRow(0)}else{if(this.currentColumn!==0&&(this.currentColumn%this.columns===0)){A=this.getRow(++this.currentRow);this.currentColumn=(C.colspan||1)}else{A=this.getRow(this.currentRow);this.currentColumn+=(C.colspan||1)}}if(C.colspan){B.colSpan=C.colspan}if(C.rowspan){B.rowSpan=C.rowspan}B.className="x-table-layout-cell";A.appendChild(B);return B},renderItem:function(C,A,B){if(C&&!C.rendered){C.render(this.getNextCell(C))}},isValidParent:function(B,A){return true}});Ext.Container.LAYOUTS["table"]=Ext.layout.TableLayout;