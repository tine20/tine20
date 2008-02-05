/**
 * Extensions to Ext
 */

Ext.namespace('Ext.ux');

// --- A DateField with a secondary trigger button that clears the contents of the DateField
Ext.ux.ClearableDateField = Ext.extend(Ext.form.DateField, {
    initComponent : function(){
        Ext.ux.ClearableDateField.superclass.initComponent.call(this);

        this.triggerConfig = {
            tag:'span', cls:'x-form-twin-triggers',
            cn:[
                {tag: "img", src: Ext.BLANK_IMAGE_URL, cls: "x-form-trigger x-form-clear-trigger"},            
                {tag: "img", src: Ext.BLANK_IMAGE_URL, cls: "x-form-trigger"}                            
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
            this.triggers[0].show();
        }      
      
        return true;
    },    
    
    onTrigger1Click : function() {
        this.reset();
        this.fireEvent('select', this, '' , '');
        this.triggers[0].hide();
        },             // clear contents of combobox
    onTrigger2Click : function() {
        this.onTriggerClick();
        }   // pass to original combobox trigger handler

});

// --- A ComboBox with a secondary trigger button that clears the contents of the ComboBox
Ext.ux.ClearableComboBox = Ext.extend(Ext.form.ComboBox, {
    initComponent : function(){
        Ext.ux.ClearableComboBox.superclass.initComponent.call(this);

        this.triggerConfig = {
            tag:'span', cls:'x-form-twin-triggers', style:'padding-right:2px',  // padding needed to prevent IE from clipping 2nd trigger button
            cn:[
                {tag: "img", src: Ext.BLANK_IMAGE_URL, cls: "x-form-trigger x-form-clear-trigger"},            
                {tag: "img", src: Ext.BLANK_IMAGE_URL, cls: "x-form-trigger"}                            
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
    
    onTrigger1Click : function() {
        this.reset();
        this.fireEvent('select', this, '' , '');
        this.triggers[0].hide();
        },             // clear contents of combobox
    onTrigger2Click : function() {
        this.onTriggerClick();
        }   // pass to original combobox trigger handler

});