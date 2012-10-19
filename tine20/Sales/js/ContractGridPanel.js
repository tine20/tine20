/*
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Sales');

/**
 * Contract grid panel
 * 
 * @namespace   Tine.Sales
 * @class       Tine.Sales.ContractGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>Contract Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.ContractGridPanel
 */
Tine.Sales.ContractGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    // model generics
    recordClass: Tine.Sales.Model.Contract,
    
    // grid specific
    defaultSortInfo: {field: 'title', dir: 'ASC'},
    gridConfig: {
        autoExpandColumn: 'title'
    },
    
    multipleEdit: true,
    
    initComponent: function() {
        this.recordProxy = Tine.Sales.contractBackend;
        
        this.gridConfig.columns = this.getColumns();
        this.initFilterToolbar();
        this.plugins.push(this.filterToolbar);
        
        Tine.Sales.ContractGridPanel.superclass.initComponent.call(this);
        this.action_addInNewWindow.actionUpdater = function() {
            var defaultContainer = this.app.getRegistry().get('defaultContainer');
            this.action_addInNewWindow.setDisabled(! defaultContainer.account_grants[this.action_addInNewWindow.initialConfig.requiredGrant]);
        }
    },
    
    /**
     * initialises filter toolbar
     */
    initFilterToolbar: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            recordClass: this.recordClass,
            filterModels: Tine.Sales.Model.Contract.getFilterModel(),
            defaultFilter: 'query',
            filters: [],
            plugins: [
                new Tine.widgets.grid.FilterToolbarQuickFilterPlugin()
            ]
        });
    },    
    
    /**
     * returns cm
     * @private
     * 
     * @todo    add more columns
     */
    getColumns: function(){
        return [{
            id: 'number',
            header: this.app.i18n._("Contract number"),
            width: 100,
            sortable: true,
            dataIndex: 'number'
        },{
            id: 'title',
            header: this.app.i18n._("Title"),
            width: 200,
            sortable: true,
            dataIndex: 'title'
        },{
            id: 'status',
            header: this.app.i18n._("Status"),
            width: 100,
            sortable: true,
            dataIndex: 'status',
            renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('Sales', 'contractStatus')
        },{
            id: 'cleared',
            header: this.app.i18n._("Cleared"),
            width: 15,
            sortable: true,
            dataIndex: 'cleared',
            renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('Sales', 'contractCleared')
        },{
            id: 'cleared_in',
            header: this.app.i18n._("Cleared in"),
            width: 100,
            sortable: true,
            dataIndex: 'cleared_in'
        }
        ];
    }
});
