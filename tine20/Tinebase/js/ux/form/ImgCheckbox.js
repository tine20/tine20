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
    offCls:'imgcheckbox-off',
    onCls:'imgcheckbox-on',
    submitOffValue:'0',
    submitOnValue:'1',
    chk: '0',
    
    onRender:function(ct) {
        Ext.ux.form.ImgCheckbox.superclass.onRender.apply(this, arguments);

        // saving the tab-index, remove and recreate this.elment
        var tabIndex = this.el.dom.tabIndex;
        var id = this.el.dom.id;
        this.el.remove();
        this.el = ct.createChild({tag:'input', type:'hidden', name:this.name, id:id});

        // hidden field value update
        this.updateHidden();

        this.wrap.replaceClass('x-form-check-wrap', 'imgcheckbox-wrap');
        this.cbEl = this.wrap.createChild({
								tag:'a',
								href:'#',
								cls: ((this.chk == '1')? this.onCls : this.offCls)                                
								});

        // repositioning the boxLabel
        var boxLabel = this.wrap.down('label');
        if(boxLabel) {
            this.wrap.appendChild(boxLabel);
        }

        // supporting of tooltip
        if(this.tooltip) {
            this.cbEl.set({qtip:this.tooltip});
        }

        // installation of event handlers
        this.wrap.on({click:{scope:this, fn:this.onClick, delegate:'a'}});
        this.wrap.on({keyup:{scope:this, fn:this.onClick, delegate:'a'}});

        // restoring the tab index
        this.cbEl.dom.tabIndex = tabIndex;
    },

    onClick:function(e) {
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
    },

    onDisable:function() {
        this.cbEl.addClass(this.disabledClass);
        this.el.dom.disabled = '1';
    },

    onEnable:function() {
        this.cbEl.removeClass(this.disabledClass);
        this.el.dom.disabled = '0';
    },

    setValue:function(val) {

			if('string' == typeof val) 
            {
                this.chk = val === this.submitOnValue;
			} else 
            {
                this.chk = !(!val);
            }

			this.redraw();

        this.fireEvent('check', this, this.chk);

    },
	 
	redraw: function() {

        if(this.rendered && this.cbEl) {
            this.updateHidden();
            this.cbEl.removeClass([this.offCls, this.onCls]);
            this.cbEl.addClass(
    			 ((this.chk == '1')? this.onCls : this.offCls)
			);
        }
	 },

    updateHidden:function() {
        this.el.dom.value = (
	  	  ((this.chk =='1')? this.submitOnValue : this.submitOffValue)
        );
    },

    getValue:function() {
        return this.chk;
    }

}); 

// xtype registration
Ext.reg('imgcheckbox', Ext.ux.form.ImgCheckbox);
