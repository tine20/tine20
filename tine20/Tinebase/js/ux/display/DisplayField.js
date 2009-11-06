

Ext.ns('Ext.ux.display');

Ext.ux.display.DisplayField = Ext.extend(Ext.form.DisplayField, {
    htmlEncode: true,
    
    renderer: function(v) {
        return v;
    },
    
    setRawValue : function(value) {
        var renderedValue = this.renderer(value);
        
        return this.supr().setRawValue.call(this, renderedValue);
    }

});

Ext.reg('ux.displayfield', Ext.ux.display.DisplayField);



Ext.ux.display.DisplayTextArea = Ext.extend(Ext.form.TextArea, {
    readOnly: true,
    cls: 'x-ux-display-textarea'
});

Ext.reg('ux.displaytextarea', Ext.ux.display.DisplayTextArea);