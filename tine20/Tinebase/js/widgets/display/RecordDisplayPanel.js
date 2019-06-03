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

    /**
     * @returns {Array.<*>}
     */
    getBodyItems: function() {
        var modelConfig = this.recordClass.getModelConfiguration(),
            typesToExclude = ['records'], // most likely never fetch from backend in any grid view!
            fieldsToExclude = ['alarms', 'tags', 'notes', 'attachments', 'relations', 'customfields',
                this.recordClass.getMeta('idProperty'),
                this.recordClass.getMeta('titleProperty'),
                this.recordClass.getMeta('containerProperty')
            ],
            fieldNames = this.recordClass.getFieldNames(),
            displayFields = [],
            displayAreas = [],
            textDisplayAreas = [],
            fieldDisplayArea = {
                flex: 1,
                layout: 'ux.display',
                labelWidth: 150,
                autoScroll: true,
                layoutConfig: {
                    background: 'solid'
                }
            };

        Ext.each(Tine.Tinebase.Model.genericFields, function(field) {fieldsToExclude.push(field.name)});

        Ext.each(fieldNames, function(fieldName) {
            var fieldDefinition = modelConfig.fields[fieldName],
                fieldType = fieldDefinition.type || 'textfield',
                field = {
                    xtype: 'ux.displayfield',
                    name: fieldDefinition.fieldName,
                    htmlEncode: false,
                    ctCls: 'tine-tinebase-recorddisplaypanel-displayfield',
                    fieldLabel: this.app.i18n._hidden(fieldDefinition.label || fieldDefinition.fieldName)
                };
            
            if (typesToExclude.indexOf(fieldDefinition.type) !== -1) {
                return;
            }

            if (fieldType === 'virtual') {
                field.fieldLabel = this.app.i18n._hidden(fieldDefinition.config.label);
                fieldType = fieldDefinition.config.type || 'textfield';
            }

            if (fieldsToExclude.indexOf(fieldDefinition.fieldName) < 0 && !fieldDefinition.shy) {
                if (fieldType === 'text' || fieldType === 'json') {
                    Ext.apply(field, {
                        flex: 1,
                        cls: 'x-ux-display-background-border',
                        xtype: 'ux.displaytextarea'
                    });

                    if (fieldType === 'json') {
                        Ext.apply(field, {
                            type: 'code/json'
                        });
                    }
                    textDisplayAreas.push(field);
                } else if (fieldType === 'image') {
                    // should be the first area
                    displayAreas.unshift({
                        width: 90,
                        layout: 'ux.display',
                        layoutConfig: {
                            background: 'solid'
                        },
                        items: [{
                            xtype: 'ux.displayfield',
                            name: fieldDefinition.fieldName,
                            cls: 'preview-panel-image',
                            anchor: '100% 100%',
                            hideLabel: true,
                            htmlEncode: false,
                            // TODO move image renderer to Tinebase
                            //renderer: Tine.widgets.grid.RendererManager.get(this.appName, this.modelName,
                            // fieldDefinition.fieldName, Tine.widgets.grid.RendererManager.CATEGORY_DISPLAYPANEL)
                            renderer: Tine.widgets.grid.RendererManager.get('Addressbook', 'Addressbook_Model_Contact',
                                fieldDefinition.fieldName, Tine.widgets.grid.RendererManager.CATEGORY_DISPLAYPANEL)
                        }]
                    });
                } else {
                    var renderer = Tine.widgets.grid.RendererManager.get(this.appName, this.modelName,
                        fieldDefinition.fieldName, Tine.widgets.grid.RendererManager.CATEGORY_DISPLAYPANEL);
                    if (renderer) {
                        // in case a rendered value contains newlines, we convert them into <br />
                        field.renderer = function() {
                            let rendererValue = this.renderer.apply(this.me, arguments);
                            if (rendererValue && window.lodash.isString(rendererValue) && rendererValue.includes("\n")) {
                                return Ext.util.Format.nl2br(rendererValue);
                            } else {
                                return rendererValue;
                            }
                        }.bind({me: this, renderer: renderer})
                        
                    }
                    displayFields.push(field);
                }
            }
        }, this);

        // auto height
        this.defaultHeight = 25 +  displayFields.length * 18;

        fieldDisplayArea.items = displayFields;
        displayAreas.push(fieldDisplayArea);
        return displayAreas.concat(textDisplayAreas);
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
