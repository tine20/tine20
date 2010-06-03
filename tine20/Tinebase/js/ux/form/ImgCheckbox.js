/*
 *  Tine 2.0 - mwImgCheckbox
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

/**
 * @namespace   Ext.ux.form
 * @class       Ext.ux.form.ImgCheckbox
 * @extends     Ext.form.Checkbox
 */
Ext.ux.form.ImgCheckbox = Ext.extend(Ext.form.Checkbox, {
    
    // initial value for cls class used for TRUE case
    trueCls:'imgCheckboxFalse',

    // initial value for cls class used for FALSE case    
    falseCls:'imgCheckboxTrue',
    
    // initial value for FALSE case    
    submitFalseValue:'0',

    // initial value for TRUE case        
    submitTrueValue:'1',
    
    // initial value box        
    trueValue: '0',
    
    
    
    getValue:function() {
        return this.trueValue;
    },
    
    setValue:function(_value) {

			if('string' == typeof _value) 
            {
                this.trueValue = _value === this.submitTrueValue;
			} else 
            {
                this.trueValue = !(!_value);
            }

			this.newRender();

        this.fireEvent('check', this, this.trueValue);

    },


    updateHiddenValue:function() {
        this.el.dom.value = (
	  	  ((this.trueValue =='1')? this.submitTrueValue : this.submitFalseValue)
        );
    },
    
        
	newRender: function() {

        if(this.rendered && this.cbEl) {
            this.updateHiddenValue();
            this.cbEl.removeClass([this.falseCls, this.trueCls]);
            this.cbEl.addClass(
    			 ((this.trueValue == '1')? this.trueCls : this.falseCls)
			);
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

    onClick:function(e) {
        if(this.disabled || this.readOnly) {
            return;
        }
        if(!e.isNavKeyPress()) {
				switch (this.trueValue) {
				case '0':     this.setValue('1'); break;
				case false:   this.setValue('1'); break;                
				case '1':     this.setValue('0'); break;
				case true:    this.setValue('0'); break;                
				}
        }
    },
    
    
    onRender:function(ct) {
        Ext.ux.form.ImgCheckbox.superclass.onRender.apply(this, arguments);

        var _tabIndex = this.el.dom.tabIndex;
        var _id = this.el.dom.id;
        this.el.remove();
        this.el = ct.createChild({tag:'input', type:'hidden', name:this.name, id:_id});

        this.updateHiddenValue();

        this.wrap.replaceClass('x-form-check-wrap', 'imgCheckboxWrapped');
        this.cbEl = this.wrap.createChild({ tag:'a', href:'#', cls: ((this.trueValue == '1')? this.trueCls : this.falseCls) });

        var _label = this.wrap.down('label');
        if(_label) {
            this.wrap.appendChild(_label);
        }

        if(this.tooltip) {
            this.cbEl.set({qtip:this.tooltip});
        }

        this.wrap.on({click:{scope:this, fn:this.onClick, delegate:'a'}});
        this.wrap.on({keyup:{scope:this, fn:this.onClick, delegate:'a'}});

        this.cbEl.dom.tabIndex = _tabIndex;
    }

}); 


Ext.reg('imgcheckbox', Ext.ux.form.ImgCheckbox);
