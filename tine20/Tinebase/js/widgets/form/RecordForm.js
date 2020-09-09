/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2016-2017 Metaways Infosystems GmbH (http://www.metaways.de)
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

    /**
     * {Tine.widgets.dialog.EditDialog} editDialog
     */
    editDialog: null,

    initComponent: function() {
        var appName = this.recordClass.getMeta('appName'),
            app = Tine.Tinebase.appMgr.get(appName),
            fieldDefinitions = Tine.widgets.form.RecordForm.getFieldDefinitions(this.recordClass);

        this.items = [];

        // sometimes we need the instances from registry (e.g. printing)
        this.editDialog.recordForm = this;

        Ext.each(fieldDefinitions, function(fieldDefinition) {

            var field = Tine.widgets.form.FieldManager.get(app, this.recordClass, fieldDefinition.fieldName, 'editDialog');
            if (field) {
                // apply basic layout
                field.columnWidth = 1;
                // add edit dialog
                // TODO do this for all fields??
                if (this.editDialog) {
                    field.editDialog = this.editDialog;
                }

                this.items.push([field]);
            }
        }, this);

        Tine.widgets.form.RecordForm.superclass.initComponent.call(this);
    }
});

/**
 * get fieldDefinitions of all fields which should be present in recordForm
 *
 * @param recordClass
 * @return []
 */
Tine.widgets.form.RecordForm.getFieldDefinitions = function(recordClass) {
    var fieldNames = recordClass.getFieldNames(),
        modelConfig = recordClass.getModelConfiguration(),
        fieldsToExclude = ['description', 'tags', 'notes', 'attachments', 'relations', 'customfields'];

    Ext.each(Tine.Tinebase.Model.genericFields, function(field) {fieldsToExclude.push(field.name)});
    fieldsToExclude.push(recordClass.getMeta('idProperty'));

    return _.reduce(fieldNames, function(fieldDefinitions, fieldName) {
        var fieldDefinition = modelConfig.fields[fieldName];
        if (fieldsToExclude.indexOf(fieldDefinition.fieldName) < 0 && ! fieldDefinition.shy) {
            fieldDefinitions.push(fieldDefinition);
        }
        return fieldDefinitions;
    }, []);
};

Tine.widgets.form.RecordForm.getFormHeight = function(recordClass) {
    var dlgConstructor = Tine.widgets.dialog.EditDialog.getConstructor(recordClass),
        fieldDefinitions = Tine.widgets.form.RecordForm.getFieldDefinitions(recordClass),
        formHeight = 38+23+5; // btnfooter + tabpanel + paddings

    if (dlgConstructor) {
        // toolbar
        if (dlgConstructor.prototype.hasOwnProperty('initButtons')) {
            formHeight += 30;
        }
    }

    Ext.each(fieldDefinitions, function(fieldDefinition) {
        var app = Tine.Tinebase.appMgr.get(recordClass.getMeta('appName')),
            field = Tine.widgets.form.FieldManager.get(app, recordClass, fieldDefinition.fieldName, 'editDialog'),
            height = field.height+25 || 42;

        formHeight += height;
    });

    return formHeight;
};

Ext.reg('recordform', Tine.widgets.form.RecordForm);