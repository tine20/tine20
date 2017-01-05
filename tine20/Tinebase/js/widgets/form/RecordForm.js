/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.form');

/**
 * Generic 'Edit Record' form
 *
 * @namespace   Tine.widgets.form
 * @class       Tine.widgets.form.RecordForm
 * @extends     Ext.ux.form.ColumnFormPanel
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @constructor
 * @param {Object} config The configuration options.
 */

Tine.widgets.form.RecordForm = Ext.extend(Ext.ux.form.ColumnFormPanel, {
    /**
     * record definition class  (required)
     * @cfg {Ext.data.Record} recordClass
     */
    recordClass: null,

    initComponent: function() {
        var appName = this.recordClass.getMeta('appName'),
            app = Tine.Tinebase.appMgr.get(appName),
            fieldNames = this.recordClass.getFieldNames(),
            modelConfig = this.recordClass.getModelConfiguration(),
            fieldsToExclude = ['description', 'tags', 'notes', 'attachments', 'relations', 'customfields'];

        Ext.each(Tine.Tinebase.Model.genericFields, function(field) {fieldsToExclude.push(field.name)});
        fieldsToExclude.push(this.recordClass.getMeta('idProperty'));

        this.items = [];
        Ext.each(fieldNames, function(fieldName) {
            var fieldDefinition = modelConfig.fields[fieldName];
            // exclude: genericFields, idProperty, wellKnown(description, tags, customfields, relations, attachments, notes)
            if (fieldsToExclude.indexOf(fieldDefinition.fieldName) < 0 && ! fieldDefinition.shy) {
                var field = Tine.widgets.form.FieldManager.get(app, this.recordClass, fieldDefinition.fieldName);
                if (field) {
                    // apply basic layout
                    field.columnWidth = 1;
                    this.items.push([field]);
                }
            }
        }, this);

        Tine.widgets.form.RecordForm.superclass.initComponent.call(this);
    }

});

Ext.reg('recordform', Tine.widgets.form.RecordForm);