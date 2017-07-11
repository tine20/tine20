/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.widgets.display');

/**
 * @class       Tine.widgets.display.RecordDisplayPanel
 * @namespace   Tine.widgets.display
 * @extends     Ext.ux.display.DisplayPanel
 *
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 *
 * Panel for displaying information of a singel record.
 */
Tine.widgets.display.RecordDisplayPanel = Ext.extend(Ext.ux.display.DisplayPanel, {

    /**
     * @cfg {Ext.data.Record} recordClass
     * record definition class
     */
    recordClass: null,

    /**
     * @property {String}
     */
    appName: null,
    /**
     * @property {String}
     */
    modelName: null,
    /**
     * @property {Tine.Tinebase.Application}
     */
    app: null,

    /* private */
    layout: 'fit',
    border: false,

    /**
     * initializes the component, builds this.fields, calls parent
     */
    initComponent: function() {
        this.appName = this.recordClass.getMeta('appName');
        this.modelName = this.recordClass.getMeta('modelName');

        this.app = Tine.Tinebase.appMgr.get(this.appName);

        var containerProperty = this.recordClass.getMeta('containerProperty');
        if (! this.containerRenderer && this.recordClass.hasField(containerProperty)) {
            this.containerRenderer = Tine.widgets.grid.RendererManager.get(this.appName, this.modelName, containerProperty, Tine.widgets.grid.RendererManager.CATEGORY_DISPLAYPANEL);
        }

        this.items = [{
            layout: 'vbox',
            border: false,
            layoutConfig: {
                align:'stretch'
            },
            items: [{
                layout: 'hbox',
                flex: 0,
                height: 16,
                border: false,
                style: 'padding-left: 5px; padding-right: 5px',
                layoutConfig: {
                    align:'stretch'
                },
                items: this.getHeadlineItems()
            }, {
                layout: 'hbox',
                flex: 1,
                border: false,
                layoutConfig: {
                    padding:'5',
                    align:'stretch'
                },
                defaults:{
                    margins:'0 5 0 0'
                },
                items: this.getBodyItems()
            }]
        }];

        Tine.widgets.display.RecordDisplayPanel.superclass.initComponent.call(this);
    },

    getHeadlineItems: function() {
        var headlineItems = [{
            flex: 0.5,
            xtype: 'ux.displayfield',
            name: this.recordClass.getMeta('titleProperty'),
            style: 'padding-top: 2px',
            cls: 'x-ux-display-header',
            htmlEncode: false,
            renderer: this.titleRenderer.createDelegate(this)
        }];

        if (this.recordClass.getMeta('containerProperty')) {
            headlineItems.push({
                flex: 0.5,
                xtype: 'ux.displayfield',
                style: 'text-align: right;',
                cls: 'x-ux-display-header',
                name: this.recordClass.getMeta('containerProperty'),
                htmlEncode: false,
                renderer: this.containerRenderer
            });
        }

        return headlineItems;
    },

    getBodyItems: function() {
        var modelConfig = this.recordClass.getModelConfiguration(),
            fieldsToExclude = ['tags', 'notes', 'attachments', 'relations', 'customfields',
                this.recordClass.getMeta('idProperty'),
                this.recordClass.getMeta('titleProperty'),
                this.recordClass.getMeta('containerProperty')
            ],
            fieldNames = this.recordClass.getFieldNames(),
            displayFields = [],
            displayAreas = [];

        Ext.each(Tine.Tinebase.Model.genericFields, function(field) {fieldsToExclude.push(field.name)});

        Ext.each(fieldNames, function(fieldName) {
            var fieldDefinition = modelConfig.fields[fieldName],
                fieldType = fieldDefinition.type || 'textfield',
                field = {
                    xtype: 'ux.displayfield',
                    name: fieldDefinition.fieldName,
                    fieldLabel: this.app.i18n._hidden(fieldDefinition.label || fieldDefinition.fieldName),
                };

            if (fieldsToExclude.indexOf(fieldDefinition.fieldName) < 0 && ! fieldDefinition.shy) {
                if (fieldType == 'text') {
                    Ext.apply(field, {
                        flex: 1,
                        cls: 'x-ux-display-background-border',
                        xtype: 'ux.displaytextarea'
                    });
                    displayAreas.push(field);
                } else {
                    var renderer = Tine.widgets.grid.RendererManager.get(this.appName, this.modelName, fieldDefinition.fieldName, Tine.widgets.grid.RendererManager.CATEGORY_DISPLAYPANEL);
                    if (renderer) {
                        field.renderer = renderer;
                    }
                    displayFields.push(field);
                }
            }
        }, this);

        // auto height
        this.defaultHeight = 25 +  displayFields.length * 18;

        return [{
            flex: 1,
            layout: 'ux.display',
            labelWidth: 150,
            autoScroll: true,
            layoutConfig: {
                background: 'solid'
            },
            items: [displayFields]
        }].concat(displayAreas);
    },

    loadRecord: function(record) {
        this.record = record;

        this.supr().loadRecord.apply(this, arguments);
    },

    titleRenderer: function(title) {
        return this.record ? Tine.Tinebase.EncodingHelper.encode(this.record.getTitle()) : Tine.Tinebase.EncodingHelper.encode(title);
    }
});

Ext.reg('ux.displaypanel', Ext.ux.display.DisplayPanel);
