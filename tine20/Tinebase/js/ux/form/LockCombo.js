/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: LockCombo.js 2256 2008-07-04 14:51:39Z twadewitz $
 *
 * @todo        switch lock and trigger icons (because only the trigger icon has a round upper right corner)
 */
 
Ext.ns('Ext.ux', 'Ext.ux.form');

/**
 * Generic widget for a twin trigger combo field
 *
 * @namespace   Ext.ux.form
 * @class       Ext.ux.form.LockCombo
 * @extends     Ext.form.ComboBox
 */
Ext.ux.form.LockCombo = Ext.extend(Ext.form.ComboBox, {
    /**
     * @cfg {String} paramName
     */
    paramName : 'query',
    /**
     * @cfg {Bool} selectOnFocus
     */
    selectOnFocus : true,
    /**
     * @cfg {String} emptyText
     */
    emptyText: 'select entry...',
    
	hiddenFieldId: '',
	
	hiddenFieldData: '',
	
    validationEvent:false,
    validateOnBlur:false,
    trigger1Class:'x-form-trigger',
    trigger2ClassLocked:'x-form-locked-trigger',
	trigger2ClassUnlocked:'x-form-unlocked-trigger',
    hideTrigger1:false,
    width:180,
    hasSearch : false,
    /**
     * @private
     */
    initComponent : function(){
        Ext.ux.form.LockCombo.superclass.initComponent.call(this);

        if(!this.hiddenFieldData) {
            this.hiddenFieldData = '1';
        }

        this.triggerConfig = {
            tag:'span', cls:'x-form-twin-triggers', cn:[
            {tag: "img", id:'trigger1', src: Ext.BLANK_IMAGE_URL, cls: "x-form-trigger " + this.trigger1Class},
            {tag: "img", id:'trigger2', src: Ext.BLANK_IMAGE_URL, cls: "x-form-trigger " }
        ]};  
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
	
			if(t.id == 'trigger2') {
				if(this.hiddenFieldData == '0') {
                    var _cssClass = this.trigger2ClassLocked.toString();
		            t.addClass(_cssClass);		
				}
				if(this.hiddenFieldData == '1' || !this.hiddenFieldData) {                                       
                    var _cssClass = this.trigger2ClassUnlocked.toString();
		            t.addClass(_cssClass);							
				}				
			}
	
            t.addClassOnOver('x-form-trigger-over');
            t.addClassOnClick('x-form-trigger-click');
        }, this);
        this.triggers = ts.elements;  
    },
	

    onRender:function(ct, position) {        
        Ext.ux.form.LockCombo.superclass.onRender.call(this, ct, position); // render the Ext.Button
        this.hiddenBox = ct.parent().createChild({tag:'input', type:'hidden', name: this.hiddenFieldId, id: this.hiddenFieldId, value: this.hiddenFieldData });        
        Ext.ComponentMgr.register(this.hiddenBox);
    },


	onTrigger1Click: function(){
        if(this.disabled){
            return;
        }
        if(this.isExpanded()){
            this.collapse();
            this.el.focus();
        }else {
            this.onFocus({});
            if(this.triggerAction == 'all') {
                this.doQuery(this.allQuery, true);
            } else {
                this.doQuery(this.getRawValue());
            }
            this.el.focus();
        }
    },
	
    onTrigger2Click : function(){

		var _currentValue = Ext.getCmp(this.hiddenFieldId).getValue();
        
        var ts = this.trigger.select('.x-form-trigger', true);	


		if (_currentValue == '0') {			
			Ext.getCmp(this.hiddenFieldId).dom.value = '1';
     
            var _cssClass = this.trigger2ClassUnlocked.toString();
			ts.each(function(t, all, index){
				if (t.id == 'trigger2') {
					t.dom.className = "x-form-trigger " + _cssClass;
				}
			});
		}
		else  {
			Ext.getCmp(this.hiddenFieldId).dom.value = '0';

            var _cssClass = this.trigger2ClassLocked.toString();
			ts.each(function(t, all, index){
				if (t.id == 'trigger2') {			
					t.dom.className = "x-form-trigger " + _cssClass;
				}
			});
		}
    }	
});
Ext.reg('lockCombo', Ext.ux.form.LockCombo);
