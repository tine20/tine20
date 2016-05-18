/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Tine.HumanResources');

/**
 * @class     Tine.HumanResources.ContractDetailsPanel
 * @namespace HumanResources
 * @extends   Tine.widgets.grid.DetailsPanel
 * @author    Alexander Stintzing <a.stintzing@metaways.de>
 */

Tine.HumanResources.ContractDetailsPanel = Ext.extend(Tine.widgets.grid.DetailsPanel, {
    
    app: null,
    
    /**
     * the model configuration
     * @type 
     */
    modelConfig: null,
    
    /**
     * @cfg {Number} defaultHeight
     * default Heights
     */
    defaultHeight: 110,
    
    initComponent: function() {
        Tine.HumanResources.ContractDetailsPanel.superclass.initComponent.call(this);
    },
    
    /**
     * main event details panel
     * 
     * @return {Ext.ux.display.DisplayPanel}
     */
    getSingleRecordPanel: function() {
        if (! this.singleRecordPanel) {
            var mc = this.modelConfig, items = [];
            for (var index = 0; index < mc.fieldKeys.length; index++) {
                var fieldConfig = mc.fields[mc.fieldKeys[index]];
                if (fieldConfig.showInDetailsPanel) {
                    items.push({
                        xtype: 'ux.displayfield',
                        name: fieldConfig.key,
                        fieldLabel: fieldConfig.hasOwnProperty('useGlobalTranslation') ? i18n._(fieldConfig.label) : this.app.i18n._(fieldConfig.label),
                        renderer: Tine.widgets.grid.RendererManager.get(mc.appName, mc.modelName, fieldConfig.key),
                        htmlEncode: false
                    });
                }
            }
            
            this.singleRecordPanel = new Ext.ux.display.DisplayPanel(
                this.wrapPanel(items, 110)
            );
        }
        return this.singleRecordPanel;
    },
    
    /**
     * update event details panel
     * 
     * @param {Tine.Tinebase.data.Record} record
     * @param {Mixed} body
     */
    updateDetails: function(record, body) {
        this.getSingleRecordPanel().loadRecord.defer(100, this.getSingleRecordPanel(), [record]);
    },
    
    /**
     * show default template
     * 
     * @param {Mixed} body
     */
    showDefault: function(body) {
        this.showMulti(this.grid.getSelectionModel());
    }
});
