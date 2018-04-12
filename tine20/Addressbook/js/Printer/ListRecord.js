Ext.ns('Tine.Addressbook.Printer');

Tine.Addressbook.Printer.ListRenderer = Ext.extend(Ext.ux.Printer.EditDialogRenderer, {

    stylesheetPath: 'Tinebase/css/widgets/print.css',

    generateMemberBody: function(component) {
        var renderer = new Ext.ux.Printer.GridPanelRenderer();

        return new Promise(function (fulfill, reject) {
            var data = renderer.extractData(component, component.store.data.items);
            renderer.generateBody(component).then(function(bodyHtml) {
                fulfill(new Ext.XTemplate(
                    bodyHtml
                ).apply(data));
            });
        });
    },

    generateBody: function(component, data) {
        var i18n = Tine.Tinebase.appMgr.get('Addressbook').i18n;
        var me = this;

        return new Promise(function (fulfill, reject) {
            me.generateMemberBody(component.memberGridPanel).then(function(memberHtml) {
                fulfill(new Ext.XTemplate(
                    '<div class="rp-print-single">',
                    '{[Tine.widgets.printer.headerRenderer()]}',
                    '<div class="rp-print-single-summary">',
                    '<span class="adb-print-single-value">{name}</span>',
                    '</div>',
                    '<div class="rp-print-single-details-row">',
                    '{[this.fieldRenderer("", "List type")]}',
                    '{[this.keyFieldRenderer("listType", values.list_type)]}',
                    '</div>',
                    '<div class="rp-print-single-details-row">',
                    '{[this.fieldRenderer(values.description, "Description")]}',
                    '</div>',
                    '</br>',
                    memberHtml,
                    '</br>',
                    '{[this.customFieldRenderer(values.customfields)]}',
                    '{[this.relationRenderer(values.relations)]}',
                    '</div>',

                    {
                        keyFieldRenderer: function (keyField, values) {
                            return Tine.Tinebase.widgets.keyfield.Renderer.render('Addressbook', keyField, values);
                        },
                        customFieldRenderer: function (values) {
                            var customFields = '';

                            customFields = Tine.widgets.customfields.Renderer.renderAll('Addressbook', Tine.Addressbook.Model.List, values);
                            if(customFields) {
                                return '<div class="rp-print-single-block-heading">' + i18n._('Customfields') + '</div>' + customFields + '</br>';
                            }
                        },
                        fieldRenderer: function (fieldValue, label) {
                            return Tine.widgets.printer.fieldRenderer('Addressbook', Tine.Addressbook.Model.list, fieldValue, label);
                        },
                        relationRenderer: function (values) {
                            var relations = '';

                            relations = Tine.widgets.relation.Renderer.renderAll(values);
                            if (relations) {
                                return '<div class="rp-print-single-block-heading">' + i18n._('Related to') + '</div>' + relations  + '</br>';
                            }
                        }
                    }).apply(data));
            });
        });
    }
});