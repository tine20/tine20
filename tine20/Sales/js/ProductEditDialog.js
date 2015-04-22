/*
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Sales');

/**
 * Product edit dialog
 * 
 * @namespace   Tine.Sales
 * @class       Tine.Sales.ProductEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Product Edit Dialog</p>
 * <p><pre>
 * TODO         make category a combobox + get data from settings
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.ProductGridPanel
 */
Tine.Sales.ProductEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    windowWidth: 800,
    windowHeight: 600,
    displayNotes: true,
    
    onRecordLoad: function() {
        Tine.Sales.ProductEditDialog.superclass.onRecordLoad.call(this);
        
        if (! this.copyRecord && ! this.record.id) {
            this.window.setTitle(this.app.i18n._('Add New Product'));
        }
    },
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     */
    getFormItems: function() {
        return {
            xtype: 'tabpanel',
            border: false,
            plain:true,
            activeTab: 0,
            border: false,
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }],
            items:[
                {
                title: this.app.i18n.n_('Product', 'Products', 1),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'border',
                items: [{
                    region: 'center',
                    xtype: 'columnform',
                    labelAlign: 'top',
                    formDefaults: {
                        xtype:'textfield',
                        anchor: '100%',
                        labelSeparator: '',
                        columnWidth: 1/3
                    },
                    items: [[{
                        name: 'number',
                        fieldLabel: this.app.i18n._('Product Number'),
                        columnWidth: 1/3
                    }, {
                        name: 'gtin',
                        fieldLabel: this.app.i18n._('GTIN'),
                        columnWidth: 1/3
                    }, new Tine.Tinebase.widgets.keyfield.ComboBox({
                        app: 'Sales',
                        keyFieldName: 'productCategory',
                        fieldLabel: this.app.i18n._('Category'),
                        name: 'category',
                        columnWidth: 1/3
                    })], [{
                        columnWidth: 1,
                        fieldLabel: this.app.i18n._('Name'),
                        name: 'name',
                        allowBlank: false
                    }], [{
                        columnWidth: 1,
                        fieldLabel: this.app.i18n._('Manufacturer'),
                        name: 'manufacturer'
                    }], [{
                        xtype: 'extuxnumberfield',
                        fieldLabel: this.app.i18n._('Purchaseprice'),
                        decimalSeparator: Tine.Tinebase.registry.get('decimalSeparator'),
                        decimalPrecision: 2,
                        suffix: ' €',
                        name: 'purchaseprice',
                        allowNegative: false,
                        allowBlank: true
                    }, {
                        xtype: 'extuxnumberfield',
                        fieldLabel: this.app.i18n._('Salesprice'),
                        decimalSeparator: Tine.Tinebase.registry.get('decimalSeparator'),
                        decimalPrecision: 2,
                        suffix: ' €',
                        name: 'salesprice',
                        allowNegative: false,
                        allowBlank: true
                    }, this.getAccountableCombo()],
                    [{
                        columnWidth: 0.5,
                        name: 'lifespan_start',
                        xtype: 'datefield',
                        fieldLabel: this.app.i18n._('Lifespan start')
                    }, {
                        columnWidth: 0.5,
                        xtype: 'datefield',
                        name: 'lifespan_end',
                        fieldLabel: this.app.i18n._('Lifespan end')
                    }], [{
                        columnWidth: 1,
                        fieldLabel: this.app.i18n._('Description'),
                        emptyText: this.app.i18n._('Enter description...'),
                        name: 'description',
                        xtype: 'textarea',
                        height: 150
                    }]]
                }, {
                    // activities and tags
                    layout: 'accordion',
                    animate: true,
                    region: 'east',
                    width: 210,
                    split: true,
                    collapsible: true,
                    collapseMode: 'mini',
                    header: false,
                    margins: '0 5 0 5',
                    border: true,
                    items: [
                        new Tine.widgets.tags.TagPanel({
                            app: 'Sales',
                            border: false,
                            bodyStyle: 'border:1px solid #B5B8C8;'
                        })
                    ]
                }]
            },
            new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            })
            ]
        };
    },
    
    /**
     * creates the accountable combo box
     * 
     * @return {Ext.form.ComboBox}
     */
    getAccountableCombo: function() {
        if (! this.accountableCombo) {
            var data = [];
            var id = 0;

            Ext.each(Tine.Sales.AccountableRegistry.getArray(), function(rel) {
                
                var app = Tine.Tinebase.appMgr.get(rel.appName);
                var tr = app.i18n._(rel.appName + rel.modelName);
                
                data.push([rel.appName + '_Model_' + rel.modelName, tr]);
                id++;
            });

            this.accountableCombo = new Ext.ux.form.ClearableComboBox({
                store: new Ext.data.ArrayStore({
                    fields: ['key', 'modelName'],
                    data: data
                }),
                fieldLabel: this.app.i18n._('Accountable'),
                allowBlank: false,
                forceSelection: true,
                value: 'Sales_Model_Product',
                displayField: 'modelName',
                valueField: 'key',
                name: 'accountable',
                columnWidth: 1/3,
                mode: 'local'
            });

        }
        return this.accountableCombo;
    }
});
