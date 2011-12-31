
Ext.ns('Ext.ux');

Ext.ux.MonthPickerPlugin = function(config) {
    Ext.apply(this, config || {});
    
};

Ext.ux.MonthPickerPlugin.prototype = {

//    changed: false,
    
	init: function(picker) {
	   picker.onRender = picker.onRender.createSequence(this.onRender, picker);
       picker.update = picker.update.createSequence(this.update, picker);
	},

    onRender: function(picker) {
        var el = Ext.DomQuery.selectNode('table[class=x-date-inner]', this.getEl().dom);
        Ext.DomHelper.applyStyles(el, 'display: none');

        this.mbtn.disable();
        this.mbtn.el.child('em').removeClass('x-btn-arrow');
        
        this.mbtn.removeClass('x-item-disabled');
    },
    
    update: function(picker) {
        this.fireEvent('change');
    }
    
};

Ext.preg('monthPickerPlugin', Ext.ux.MonthPickerPlugin);