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
 * CostCenter grid panel
 * 
 * @namespace   Tine.Sales
 * @class       Tine.Sales.CostCenterGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>CostCenter Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stinting <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * 
 * Create a new Tine.Sales.CostCenterGridPanel
 */
Tine.Sales.CostCenterGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    // model generics
    recordClass: Tine.Sales.Model.CostCenter,
    
    // grid specific
    defaultSortInfo: {field: 'remark', dir: 'ASC'},
    gridConfig: {
        autoExpandColumn: 'remark'
    },
    
    multipleEdit: true,
    
    initComponent: function() {
        this.recordProxy = Tine.Sales.costcenterBackend;
        this.gridConfig.columns = this.getColumns();
        Tine.Sales.CostCenterGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * returns cm
     * @private
     */
    getColumns: function(){
        return [{
            id: 'number',
            header: this.app.i18n._("Number"),
            width: 100,
            sortable: true,
            dataIndex: 'number'
        },{
            id: 'remark',
            header: this.app.i18n._("Remark"),
            width: 200,
            sortable: true,
            dataIndex: 'remark'
        }].concat(this.getModlogColumns())
    }
});
