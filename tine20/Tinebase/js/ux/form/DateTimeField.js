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
Ext.ux.form.DateTimeField = Ext.extend(Ext.form.Field, {
    autoEl: 'div',
    value: '',
    
    initComponent: function() {
        Ext.ux.form.DateTimeField.superclass.initComponent.call(this);
        this.lastValues = [];
        
    },
    
    clearInvalid: function() {
        this.dateField.clearInvalid();
        this.timeField.clearInvalid();
    },
    
    clearTime: function() {
        var dateTime = this.getValue();
        if (Ext.isDate(dateTime)) {
            this.setValue(this.getValue().clearTime(true));
        } else {
            this.timeField.setValue(new Date().clearTime());
        }
        
    },
    
    getName: function() {
        return this.name;
    },
    
    getValue: function() {
        var date = this.dateField.getValue();
        // this is odd, why doesn't Ext.form.TimeField a Date datatype?
        var time = Date.parseDate(this.timeField.getValue(), this.timeField.format);
        
        if (Ext.isDate(date) && Ext.isDate(time)) {
            date = date.clone();
            date.clearTime();
            date = date.add(Date.HOUR, time.getHours());
            date = date.add(Date.MINUTE, time.getMinutes());
        }
        
        return date;
    },
    
    markInvalid: function(msg) {
        this.dateField.markInvalid(msg);
        this.timeField.markInvalid(msg);
    },
    
    onRender: function(ct, position) {
        //Ext.ux.form.DateTimeField.superclass.onRender.call(this, ct, position);
        this.el = document.createElement(this.autoEl);
        this.el.id = this.getId();
        this.el = Ext.get(this.el);
        ct.dom.insertBefore(this.el.dom, position);
        
        this.dateField = new Ext.form.DateField({
            renderTo: this.el,
            readOnly: this.readOnly,
            hideTrigger: this.hideTrigger,
            disabled: this.disabled,
            tabIndex: this.tabIndex == -1 ? this.tabIndex : false,
            listeners: {
                scope: this,
                change: this.onDateChange,
                select: this.onDateChange
            }
        });
        
        this.timeField = new Ext.form.TimeField({
            renderTo: this.el,
            readOnly: this.readOnly,
            hideTrigger: this.hideTrigger,
            disabled: this.disabled,
            tabIndex: this.tabIndex == -1 ? this.tabIndex : false,
            listeners: {
                scope: this,
                change: this.onTimeChange,
                select: this.onTimeChange
            }
        });
        
    },
    
    onDateChange: function() {
        var newValue = this.getValue();
        this.setValue(newValue);
        this.fireEvent('change', this, newValue, this.lastValues.length > 1 ? this.lastValues[this.lastValues.length-2] : '');
    },
    
    onResize : function(w, h) {
        Ext.ux.form.DateTimeField.superclass.onResize.apply(this, arguments);
        
        // needed for readonly
        this.el.setHeight(15);
        
        this.el.setStyle({'position': 'relative'});
        
        this.dateField.wrap.setStyle({'position': 'absolute'});
        this.dateField.setWidth(w * 0.55 -5);
        
        this.timeField.wrap.setStyle({'position': 'absolute'});
        this.timeField.setWidth(w * 0.45);
        this.timeField.wrap.setLeft(this.dateField.getWidth() + 5);
    },
    
    onTimeChange: function() {
        var newValue = this.getValue();
        this.setValue(newValue);
        this.fireEvent('change', this, newValue, this.lastValues.length > 1 ? this.lastValues[this.lastValues.length-2] : '');
    },
    
    setDisabled: function(bool, what) {
        if (what !== 'time') {
            this.dateField.setDisabled(bool);
        }
        
        if (what !== 'date') {
            this.timeField.setDisabled(bool);
        }
    },
    
    setRawValue: Ext.EmptyFn,
    
    setValue: function(value, skipHistory) {
        if (! skipHistory) {
            this.lastValues.push(value);
        }
        
        this.dateField.setValue(value);
        this.timeField.setValue(value);
    },
    
    undo: function() {
        if (this.lastValues.length > 1) {
            this.lastValues.pop();
            this.setValue(this.lastValues[this.lastValues.length-1], true);
        } else {
            this.reset();
        }
    }
});
Ext.reg('datetimefield', Ext.ux.form.DateTimeField);