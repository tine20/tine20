
Ext.ns('Ext.ux.display');

Ext.ux.display.DisplayPanel = Ext.extend(Ext.form.FormPanel, {
    cls : 'x-ux-display',
    
    layout: 'ux.display',
    
    loadRecord: function(record) {
        return this.getForm().loadRecord(record);
    },
    
    onRender: function() {
        this.supr().onRender.apply(this, arguments);
        
        //console.log('x-ux-display');
        //this.el.addClass('x-ux-display');
    }
});

Ext.reg('ux.displaypanel', Ext.ux.display.DisplayPanel);