/*
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Crm');

/**
 * Lead grid panel
 * 
 * @namespace   Tine.Crm
 * @class       Tine.Crm.LeadGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>Lead Grid Panel</p>
 * <p><pre>
 * TODO         add products to grid?
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Crm.LeadGridPanel
 */
Tine.Crm.LeadGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    /**
     * record class
     * @cfg {Tine.Crm.Model.Lead} recordClass
     */
    recordClass: Tine.Crm.Model.Lead,
    
    /**
     * eval grants
     * @cfg {Boolean} evalGrants
     */
    evalGrants: true,
    
    /**
     * grid specific
     * @private
     */
    defaultSortInfo: {field: 'lead_name', direction: 'DESC'},
    gridConfig: {
        autoExpandColumn: 'title',
        // drag n drop
        enableDragDrop: true,
        ddGroup: 'containerDDGroup'
    },
     
    /**
     * inits this cmp
     * @private
     */
    initComponent: function() {
        this.recordProxy = Tine.Crm.leadBackend;
        
        this.gridConfig.cm = this.getColumnModel();
        
        this.defaultFilters = [ {field: 'leadstate_id', operator: 'notin', value: Tine.Crm.LeadState.getClosedStatus()} ];
        this.filterToolbar = this.getFilterToolbar();
        
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
        
        this.detailsPanel = new Tine.Crm.LeadGridDetailsPanel({
            grid: this
        });
        
        Tine.Crm.LeadGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * add custom items to action toolbar
     * 
     * @return {Object}
     */
    getActionToolbarItems: function() {
        return [
            Ext.apply(new Ext.SplitButton(this.actions_exportLead), {
                scale: 'medium',
                rowspan: 2,
                iconAlign: 'top',
                arrowAlign:'right'
            })
        ];
    },
    
    /**
     * add custom items to context menu
     * 
     * @return {Array}
     */
    getContextMenuItems: function() {
        var items = [
            '-',
            this.actions_exportLead
        ];
        return items;
    },
    
    /**
     * returns cm
     * 
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumnModel: function(){
        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: true
            },
            columns: [
                {header: this.app.i18n._('Lead id'), id: 'id', dataIndex: 'id', width: 20, hidden: true},
                {header: this.app.i18n._('Tags'), id: 'tags', dataIndex: 'tags', width: 50, renderer: Tine.Tinebase.common.tagsRenderer, sortable: false},
                {header: this.app.i18n._('Lead name'), id: 'lead_name', dataIndex: 'lead_name', width: 200},
                {header: this.app.i18n._('Responsible'), id: 'lead_responsible', dataIndex: 'relations', width: 175, sortable: false, hidden: true, renderer: this.responsibleRenderer},
                {header: this.app.i18n._('Partner'), id: 'lead_partner', dataIndex: 'relations', width: 175, sortable: false, renderer: this.partnerRenderer},
                {header: this.app.i18n._('Customer'), id: 'lead_customer', dataIndex: 'relations', width: 175, sortable: false, renderer: this.customerRenderer},
                {header: this.app.i18n._('Leadstate'), id: 'leadstate_id', dataIndex: 'leadstate_id', sortable: false, width: 100, renderer: Tine.Crm.LeadState.Renderer},
                {header: this.app.i18n._('Probability'), id: 'probability', dataIndex: 'probability', width: 50, renderer: Ext.util.Format.percentage },
                {header: this.app.i18n._('Turnover'), id: 'turnover', dataIndex: 'turnover', width: 100, renderer: Ext.util.Format.euMoney },
                {header: this.app.i18n._('Probable Turnover'), id: 'probableTurnover', dataIndex: 'probableTurnover', width: 100, renderer: Ext.util.Format.euMoney, sortable: false }
            ].concat(this.getModlogColumns().concat(this.getCustomfieldColumns()))
        });
    },

    /**
     * render responsible contact
     * 
     * @param {Array} value
     * @return {String}
     * 
     * TODO use another renderer (with email, phone, ...) here?
     */
    responsibleRenderer: function(value) {
        return Tine.Crm.LeadGridPanel.shortContactRenderer(value, 'RESPONSIBLE');
    },
    
    /**
     * render partner contact
     * 
     * @param {Array} value
     * @return {String}
     */
    partnerRenderer: function(value) {
        return Tine.Crm.LeadGridPanel.shortContactRenderer(value, 'PARTNER');
    },
    
    /**
     * render customer contact
     * 
     * @param {Array} value
     * @return {String}
     */
    customerRenderer: function(value) {
        return Tine.Crm.LeadGridPanel.shortContactRenderer(value, 'CUSTOMER');
    },

    /**
     * @private
     */
    initActions: function(){
        this.actions_exportLead = new Ext.Action({
            text: this.app.i18n._('Export Lead'),
            iconCls: 'action_export',
            scope: this,
            requiredGrant: 'readGrant',
            disabled: true,
            allowMultiple: true,
            menu: {
                items: [
                    new Tine.widgets.grid.ExportButton({
                        text: this.app.i18n._('Export as PDF'),
                        iconCls: 'action_exportAsPdf',
                        format: 'pdf',
                        exportFunction: 'Crm.exportLead',
                        gridPanel: this
                    }),
                    new Tine.widgets.grid.ExportButton({
                        text: this.app.i18n._('Export as CSV'),
                        iconCls: 'tinebase-action-export-csv',
                        format: 'csv',
                        exportFunction: 'Crm.exportLead',
                        gridPanel: this
                    }),
                    new Tine.widgets.grid.ExportButton({
                        text: this.app.i18n._('Export as ODS'),
                        iconCls: 'tinebase-action-export-ods',
                        format: 'ods',
                        exportFunction: 'Crm.exportLead',
                        gridPanel: this
                    }),
                    new Tine.widgets.grid.ExportButton({
                        text: this.app.i18n._('Export as XLS'),
                        iconCls: 'tinebase-action-export-xls',
                        format: 'xls',
                        exportFunction: 'Crm.exportLead',
                        gridPanel: this
                    })
                ]
            }
        });
        
        this.actionUpdater.addActions([
            this.actions_exportLead
        ]);
        
        this.supr().initActions.call(this);
    }    
});

/**
 * contact column renderer
 * 
 * @param       {String} value
 * @param       {String} type (CUSTOMER|PARTNER)
 * @return      {String}
 * 
 * @namespace   Tine.Crm
 */
Tine.Crm.LeadGridPanel.shortContactRenderer = function(data, type) {

    if( Ext.isArray(data) && data.length > 0) {
        var index = 0;
        
        // get correct relation type from data (contact) array and show first matching record (org_name + n_fileas)
        while (index < data.length && data[index].type != type) {
            index++;
        }
        if (data[index]) {
            var org = (data[index].related_record.org_name !== null ) ? data[index].related_record.org_name : '';
            return '<b>' + Ext.util.Format.htmlEncode(org) + '</b><br />' + Ext.util.Format.htmlEncode(data[index].related_record.n_fileas);
        }
    }
};
