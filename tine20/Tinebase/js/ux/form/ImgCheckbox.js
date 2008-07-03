/*
 *  Tine 2.0 - Ext.ux.form.ImgCheckbox - image checkbox
 * 
 * @package     Ext
 * @subpackage  ux
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: ImgCheckbox.js 1751 2008-07-03 12:28:26Z twadewitz $
 *
 */

Ext.ns('Ext.ux.form');
Ext.ux.form.ImgCheckbox = Ext.extend(Ext.form.Checkbox, {
     offCls:'imgcheckbox-off'
    ,onCls:'imgcheckbox-on'
    ,submitOffValue:'0'
    ,submitOnValue:'1'
    ,chk: '0'
    
    ,onRender:function(ct) {
        // call parent
        Ext.ux.form.ImgCheckbox.superclass.onRender.apply(this, arguments);

        // save tabIndex remove & re-create this.el
        var tabIndex = this.el.dom.tabIndex;
        var id = this.el.dom.id;
        this.el.remove();
        this.el = ct.createChild({tag:'input', type:'hidden', name:this.name, id:id});

        // update value of hidden field
        this.updateHidden();

        // adjust wrap class and create link with bg image to click on
        this.wrap.replaceClass('x-form-check-wrap', 'imgcheckbox-wrap');
        this.cbEl = this.wrap.createChild({
								tag:'a',
								href:'#',
								cls: ((this.chk == '1')? this.onCls : this.offCls)                                
								});

        // reposition boxLabel if any
        var boxLabel = this.wrap.down('label');
        if(boxLabel) {
            this.wrap.appendChild(boxLabel);
        }

        // support tooltip
        if(this.tooltip) {
            this.cbEl.set({qtip:this.tooltip});
        }

        // install event handlers
        this.wrap.on({click:{scope:this, fn:this.onClick, delegate:'a'}});
        this.wrap.on({keyup:{scope:this, fn:this.onClick, delegate:'a'}});

        // restore tabIndex
        this.cbEl.dom.tabIndex = tabIndex;
    } // eo function onRender

    ,onClick:function(e) {
        if(this.disabled || this.readOnly) {
            return;
        }
        if(!e.isNavKeyPress()) {
				switch (this.chk) {
				case '0': this.setValue('1'); break;
				case false: this.setValue('1'); break;                
				case '1':  this.setValue('0'); break;
				case true:  this.setValue('0'); break;                
				}
        }
    } // eo function onClick

    ,onDisable:function() {
        this.cbEl.addClass(this.disabledClass);
        this.el.dom.disabled = '1';
    } // eo function onDisable

    ,onEnable:function() {
        this.cbEl.removeClass(this.disabledClass);
        this.el.dom.disabled = '0';
    } // eo function onEnable

    ,setValue:function(val) {

			if('string' == typeof val) { //Match to the submit value
            this.chk = val === this.submitOnValue;
			} else { //Match to a boolean
            this.chk = !(!val);
         }

			//Redraw the checkbox
			this.redraw();
			
			//Notify that the state was changed
        this.fireEvent('check', this, this.chk);

    } // eo function setValue
	 
	 ,redraw: function() {

        if(this.rendered && this.cbEl) {
            this.updateHidden();
            this.cbEl.removeClass([this.offCls, this.onCls]);
            this.cbEl.addClass(
										 ((this.chk == '1')? this.onCls : this.offCls)
									);
        }
	 }

    ,updateHidden:function() {
        this.el.dom.value = (
							  ((this.chk =='1')? this.submitOnValue : this.submitOffValue)
                              );
    } // eo function updateHidden

    ,getValue:function() {
        return this.chk;
    } // eo function getValue

}); // eo extend

// register xtype
Ext.reg('imgcheckbox', Ext.ux.form.ImgCheckbox);
