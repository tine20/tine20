

Ext.ns('Ext.ux.display');

Ext.ux.display.DisplayField = Ext.extend(Ext.form.DisplayField, {
    htmlEncode: true,
    nl2br: false,
    
    renderer: function(v) {
        return v;
    },
    
    setRawValue : function(value) {
        var v = this.renderer(value);
        
        if(this.htmlEncode){
            v = Ext.util.Format.htmlEncode(v);
        }
        
        if (this.nl2br) {
            v = Ext.util.Format.nl2br(v);
        }
        
        return this.rendered ? (this.el.dom.innerHTML = (Ext.isEmpty(v) ? '' : v)) : (this.value = v);
    }

});

Ext.reg('ux.displayfield', Ext.ux.display.DisplayField);



Ext.ux.display.DisplayTextArea = Ext.extend(Ext.form.TextArea, {
    readOnly: true,
    cls: 'x-ux-display-textarea'
});

Ext.reg('ux.displaytextarea', Ext.ux.display.DisplayTextArea);