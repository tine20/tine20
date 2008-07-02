/**
 * Ext.ux.form.TriCheckbox - Tri-state checkbox
 *
 * @license Ext.ux.form.TriCheckbox is licensed under the terms of
 * the Open Source LGPL 3.0 license.  Commercial use is permitted to the extent
 * that the code/component(s) do NOT become part of another Open Source or Commercially
 * licensed development library or toolkit without explicit permission.
 * 
 * License details: http://www.gnu.org/licenses/lgpl.html
 */

/**
  * 
  * Default css:
  * .tricheckbox-wrap {
  *     line-height: 18px;
  *     padding-top:2px;
  * }
  * .tricheckbox-wrap a {
  *     display:block;
  *     width:16px;
  *     height:16px;
  *     float:left;
  * }
  * .x-toolbar .tricheckbox-wrap {
  *     padding: 0 0 2px 0;
  * }
  * .tricheckbox-on {
  *     background:transparent url(./ext/resources/images/default/menu/checked.gif) no-repeat 0 0;
  * }
  * .tricheckbox-off {
  *     background:transparent url(./ext/resources/images/default/menu/unchecked.gif) no-repeat 0 0;
  * }
  * .tricheckbox-inherit-on {
  *     background:transparent url(./ext/resources/images/default/menu/checked.gif) no-repeat 0 0;
  * }
  * .tricheckbox-inherit-off {
  *     background:transparent url(./ext/resources/images/default/menu/unchecked.gif) no-repeat 0 0;
  * }
  * .tricheckbox-disabled {
  *     opacity: 0.5;
  *     -moz-opacity: 0.5;
  *     filter: alpha(opacity=50);
  *     cursor:default;
  * }
  *
  * @class Ext.ux.TriCheckbox
  * @extends Ext.form.Checkbox
  */
Ext.ns('Ext.ux.form');
Ext.ux.form.TriCheckbox = Ext.extend(Ext.form.Checkbox, {
     offCls:'tricheckbox-off'
    ,onCls:'tricheckbox-on'
    ,inheritOnCls:'tricheckbox-inherit-on'
    ,inheritOffCls:'tricheckbox-inherit-off'
	 
    ,disabledClass:'tricheckbox-disabled'
    ,submitOffValue:'false'
    ,submitOnValue:'true'
	 ,submitInheritedValue:''
	 ,inheritedValue: false //Iherited checkbox state is checked or not
    ,chk: null //Default to inherited
	 ,allowInherited: true //Toggles the inherited/standard behaviour

    ,onRender:function(ct) {
        // call parent
        Ext.ux.form.TriCheckbox.superclass.onRender.apply(this, arguments);

			//Set the initial value
			this.chk = (this.allowInherited ? null : false);

        // save tabIndex remove & re-create this.el
        var tabIndex = this.el.dom.tabIndex;
        var id = this.el.dom.id;
        this.el.remove();
        this.el = ct.createChild({tag:'input', type:'hidden', name:this.name, id:id});

        // update value of hidden field
        this.updateHidden();

        // adjust wrap class and create link with bg image to click on
        this.wrap.replaceClass('x-form-check-wrap', 'tricheckbox-wrap');
        this.cbEl = this.wrap.createChild({
								tag:'a',
								href:'#',
								cls: ((this.chk == null && this.allowInherited)
										? (this.chk ? this.inheritOnCls : this.inheritOffCls)
										: (this.chk ? this.onCls : this.offCls)
										)
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
				case null: this.setValue(false); break;					
				case false: this.setValue(true); break;
				case true: (this.allowInherited ? this.setValue(null) : this.setValue(false)); break;
				}
        }
    } // eo function onClick

    ,onDisable:function() {
        this.cbEl.addClass(this.disabledClass);
        this.el.dom.disabled = true;
    } // eo function onDisable

    ,onEnable:function() {
        this.cbEl.removeClass(this.disabledClass);
        this.el.dom.disabled = false;
    } // eo function onEnable

	 ,setInheritedValue: function(val) {
		if (val == null) { //Inherited
			this.inheritedValue = (this.allowInherited ? null : false);
		} else { //Match to a boolean
			this.inheritedValue = !(!val);
		}

		//Redraw the checkbox
		this.redraw();
	 }
    ,setValue:function(val) {

			if (val == null) { //Inherited
				this.chk = (this.allowInherited ? null : false);
			} else if('string' == typeof val) { //Match to the submit value
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
            this.cbEl.removeClass([this.offCls, this.onCls, this.inheritOffCls, this.inheritOnCls]);
            this.cbEl.addClass(
										 ((this.chk == null && this.allowInherited)
										 ? (this.inheritedValue ? this.inheritOnCls : this.inheritOffCls)
										 : (this.chk ? this.onCls : this.offCls)
										 )
									);
        }
	 }

    ,updateHidden:function() {
        this.el.dom.value = (
									  (this.chk == null && this.allowInherited)
									  ? this.submitInheritedValue
									  : (this.chk ? this.submitOnValue : this.submitOffValue)
									);
    } // eo function updateHidden

    ,getValue:function() {
        return this.chk;
    } // eo function getValue

}); // eo extend

// register xtype
Ext.reg('tricheckbox', Ext.ux.form.TriCheckbox);
