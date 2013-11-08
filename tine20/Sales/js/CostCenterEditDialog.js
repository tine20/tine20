/*
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Sales');

/**
 * CostCenter edit dialog
 * 
 * @namespace   Tine.Sales
 * @class       Tine.Sales.CostCenterEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>CostCenter Edit Dialog</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.CostCenterGridPanel
 */
Tine.Sales.CostCenterEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    /**
     * @private
     */
    windowNamePrefix: 'CostCenterEditWindow_',
    appName: 'Sales',
    recordClass: Tine.Sales.Model.CostCenter,
    recordProxy: Tine.Sales.costcenterBackend,
    tbarItems: [],
    
    initComponent: function() {
        Tine.Sales.CostCenterEditDialog.superclass.initComponent.call(this);
    },
    /**
     * called on multiple edit
     * @return {Boolean}
     */
    isMultipleValid: function() {
        return true;
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
            defaults: {
                hideMode: 'offsets'
            },
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }],
            items:[
                {
                title: this.app.i18n.n_('Cost Center', 'Cost Centers', 1),
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
                        columnWidth: .333
                    },
                    items: [[{
                        columnWidth: .25,
                        fieldLabel: this.app.i18n._('Number'),
                        name: 'number',
                        xtype:  'numberfield',
                        multiEditable: false,
                        allowBlank: false
                    },{
                        columnWidth: .75,
                        fieldLabel: this.app.i18n._('Remark'),
                        name: 'remark',
                        allowBlank: false
                    }]
                    ]
                }]
            }]
        };
    }
});

/**
 * Sales Edit Popup
 */
Tine.Sales.CostCenterEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 650,
        height: 450,
        name: Tine.Sales.CostCenterEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Sales.CostCenterEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
