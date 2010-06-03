/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.ns('Ext.ux', 'Ext.ux.form');

/**
 * A DateField with a secondary trigger button that clears the contents of the DateField
 *
 * @namespace   Ext.ux.form
 * @class       Ext.ux.form.ClearableDateField
 * @extends     Ext.form.DateField
 */
Ext.ux.form.ClearableDateField = Ext.extend(Ext.form.DateField, {
    initComponent : function(){
        Ext.ux.form.ClearableDateField.superclass.initComponent.call(this);

        this.triggerConfig = {
            tag:'span', cls:'x-form-twin-triggers',
            cn:[
                {tag: "img", src: Ext.BLANK_IMAGE_URL, cls: "x-form-trigger x-form-clear-trigger"},            
                {tag: "img", src: Ext.BLANK_IMAGE_URL, cls: "x-form-trigger x-form-date-trigger"}                            
            ]
        };
    },

    getTrigger : function(index){
        return this.triggers[index];
    },

    initTrigger : function(){
        var ts = this.trigger.select('.x-form-trigger', true);
        this.wrap.setStyle('overflow', 'hidden');
        var triggerField = this;
        ts.each(function(t, all, index){
            t.hide = function(){
                var w = triggerField.wrap.getWidth();
                this.dom.style.display = 'none';
                triggerField.el.setWidth(w-triggerField.trigger.getWidth());
            };
            t.show = function(){
                var w = triggerField.wrap.getWidth();
                this.dom.style.display = '';
                triggerField.el.setWidth(w-triggerField.trigger.getWidth());
            };
            var triggerIndex = 'Trigger'+(index+1);

            if(this['hide'+triggerIndex]){
                t.dom.style.display = 'none';
            }
            t.on("click", this['on'+triggerIndex+'Click'], this, {preventDefault:true});
            t.addClassOnOver('x-form-trigger-over');
            t.addClassOnClick('x-form-trigger-click');
        }, this);
        this.triggers = ts.elements;        
        this.triggers[0].hide();                   
    },
    
    validateValue : function(value) {
        if(value !== this.emptyText && value !== undefined && value.length > '1'){
            if (Ext.isArray(this.triggers) && this.triggers[0] && typeof this.triggers[0].show == 'function') {
                this.triggers[0].show();
            }
        }      
      
        return true;
    },    
    // clear contents of combobox
    onTrigger1Click : function() {
        this.reset();
        this.fireEvent('select', this, '' , '');
        this.triggers[0].hide();
    },
    // pass to original combobox trigger handler
    onTrigger2Click : function() {
        this.onTriggerClick();
    }
});
Ext.reg('extuxclearabledatefield', Ext.ux.form.ClearableDateField);