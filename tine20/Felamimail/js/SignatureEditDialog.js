Tine.widgets.form.FieldManager.register('Felamimail', 'Signature', 'signature', {
    xtype: 'htmleditor',
    name: 'signature',
    defaultFont: Tine.Felamimail.registry.get('preferences').get('defaultfont') || 'tahoma',
    height: 300,
    getDocMarkup: function(){
        var markup = '<span id="felamimail\-body\-signature">'
            + '</span>';
        return markup;
    },
    plugins: [
        new Ext.ux.form.HtmlEditor.RemoveFormat()
    ]
}, Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG);