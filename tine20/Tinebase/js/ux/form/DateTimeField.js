/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Ext.ux', 'Ext.ux.form');

/**
 * A combination of datefield and timefield
 */
Ext.ux.form.DateTimeField = Ext.extend(Ext.form.DateField, {
    /*
    initComponent: function() {
        
        Ext.ux.form.DateTimeField.superclass.initComponent.call(this);
        
    },
    */
    clearTime: function() {
        this.timeField.setValue('00:00');
    },
    
    getValue: function() {
        
        var date = Ext.ux.form.DateTimeField.superclass.getValue.apply(this, arguments);
        var time = this.timeField.getValue();
        
        var timeParts = time.split(':');
        
        if (Ext.isDate(date) && timeParts.length >= 2) {
            date.clearTime();
            date.setHours(timeParts[0]);
            date.setMinutes(timeParts[1]);
        }
        return date;
    },
    
    onRender: function(ct, position) {
        Ext.ux.form.DateTimeField.superclass.onRender.call(this, ct, position);
        
        this.timeFieldEl = Ext.DomHelper.insertAfter(this.wrap.last(), {dom: 'div', style: {'position': 'absolute', 'top': '0px'}}, true);
        this.timeField = new Ext.form.TimeField({
            renderTo: this.timeFieldEl,
            readOnly: this.readOnly,
            hideTrigger: this.hideTrigger,
            disabled: this.disabled,
            tabIndex: this.tabIndex == -1 ? this.tabIndex : false
        });
    },
    
    onResize : function(w, h) {
        Ext.ux.form.DateTimeField.superclass.onResize.apply(this, arguments);
        
        var wrapWidth = this.wrap.getWidth();
        var totalFieldWidth = wrapWidth - 2*this.trigger.getWidth() - 10;
        
        var dateFieldWidth = totalFieldWidth * 0.6;
        var timeFieldWidth = totalFieldWidth * 0.4;
        
        this.el.setWidth(dateFieldWidth);
        this.timeField.getEl().setWidth(timeFieldWidth);
        
        this.timeFieldEl.setLeft(dateFieldWidth + this.trigger.getWidth() + 10);
        this.timeField.wrap.setWidth(timeFieldWidth + this.trigger.getWidth());
    },
    
    setDisabled: function(bool, what) {
        if (what !== 'time') {
            Ext.ux.form.DateTimeField.superclass.setDisabled.call(this, bool);
        }
        
        if (what !== 'date') {
            this.timeField.setDisabled(bool);
        }
    },
    
    setValue: function(value) {
        Ext.ux.form.DateTimeField.superclass.setValue.apply(this, arguments);
        this.timeField.setValue(value);
    }
});
Ext.reg('datetimefield', Ext.ux.form.DateTimeField);