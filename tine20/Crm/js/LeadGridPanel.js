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
     * ids of lead states that end a lead (needed to determine grid row class)
     */
    endingLeadStateIds: [],

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
     * returns view row class (scope is this.grid.view)
     */
    getViewRowClass: function (record, index, rowParams, store) {

        var className = Tine.Crm.LeadGridPanel.superclass.getViewRowClass(record, index, rowParams, store);

        var now = new Date();
        if (this.endingLeadStateIds && this.endingLeadStateIds.indexOf(record.get('leadstate_id')) == -1) {
            var rsd = record.get('resubmission_date');
            var esd = record.get('end_scheduled');
            if (((esd && (esd < now)) || (rsd && (rsd < now)))) {
                className += ' crm-highlight-task';
            }
        }

        return className;
    },

    /**
     * inits this cmp
     * @private
     */
    initComponent: function () {
        this.recordProxy = Tine.Crm.leadBackend;

        this.gridConfig.cm = this.getColumnModel();

        this.defaultFilters = [{field: 'leadstate_id', operator: 'notin', value: Tine.Crm.getEndedLeadStateIds()}];

        this.detailsPanel = new Tine.Crm.LeadGridDetailsPanel({
            grid: this
        });

        Tine.Crm.LeadGridPanel.superclass.initComponent.call(this);

        this.endingLeadStateIds = Tine.Crm.getEndedLeadStateIds();
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
                {header: this.app.i18n._('Tags'), id: 'tags', dataIndex: 'tags', width: 50, renderer: Tine.Tinebase.common.tagsRenderer, sortable: false},
                {header: this.app.i18n._('Lead name'), id: 'lead_name', dataIndex: 'lead_name', width: 200},
                {header: this.app.i18n._('Responsible'), id: 'lead_responsible', dataIndex: 'lead_responsible', width: 175, sortable: false, hidden: true, renderer: this.responsibleRenderer},
                {header: this.app.i18n._('Partner'), id: 'lead_partner', dataIndex: 'lead_partner', width: 175, sortable: false, renderer: this.partnerRenderer},
                {header: this.app.i18n._('Customer'), id: 'lead_customer', dataIndex: 'lead_customer', width: 175, sortable: false, renderer: this.customerRenderer},
                {header: this.app.i18n._('Leadstate'), id: 'leadstate_id', dataIndex: 'leadstate_id', width: 100, renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('Crm', 'leadstates')},
                {header: this.app.i18n._('Leadsource'), id: 'leadsource_id', dataIndex: 'leadsource_id', width: 100, renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('Crm', 'leadsources')},
                {header: this.app.i18n._('Probability'), id: 'probability', dataIndex: 'probability', width: 50, renderer: Ext.ux.PercentRenderer },
                {header: this.app.i18n._('Turnover'), id: 'turnover', dataIndex: 'turnover', width: 100, renderer: Ext.util.Format.money },

                {header: this.app.i18n._('Estimated end'), id: 'end_scheduled', dataIndex: 'end_scheduled', width: 100, renderer: Tine.Tinebase.common.dateRenderer, sortable: true },
                {header: this.app.i18n._('Probable Turnover'), id: 'probableTurnover', dataIndex: 'probableTurnover', width: 100, renderer: Ext.util.Format.money, sortable: false },
                {header: this.app.i18n._('Resubmission Date'), id: 'resubmission_date', dataIndex: 'resubmission_date', width: 100, renderer: Tine.Tinebase.common.dateRenderer, sortable: true }
                
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
    responsibleRenderer: function(value, metaData, record) {
        return Tine.Crm.LeadGridPanel.shortContactRenderer(record.get('relations'), 'RESPONSIBLE');
    },
    
    /**
     * render partner contact
     * 
     * @param {Array} value
     * @return {String}
     */
    partnerRenderer: function(value, metaData, record) {
        return Tine.Crm.LeadGridPanel.shortContactRenderer(record.get('relations'), 'PARTNER');
    },
    
    /**
     * render customer contact
     * 
     * @param {Array} value
     * @return {String}
     */
    customerRenderer: function(value, metaData, record) {
        return Tine.Crm.LeadGridPanel.shortContactRenderer(record.get('relations'), 'CUSTOMER');
    },

    /**
     * @private
     */
    initActions: function(){
        this.actions_import = this.app.featureEnabled('featureLeadImport') ?
            new Ext.Action({
                text: this.app.i18n._('Import leads'),
                disabled: false,
                handler: this.onImport,
                iconCls: 'action_import',
                scope: this,
                allowMultiple: true
            }) : false;

        this.supr().initActions.call(this);
    }
});

/**
 * contact column renderer
 * 
 * @param       {String} data
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
        if (data[index] && data[index].related_record) {
            var org = (data[index].related_record.org_name !== null ) ? data[index].related_record.org_name : '';
            return '<b>' + Ext.util.Format.htmlEncode(org) + '</b><br />' + Ext.util.Format.htmlEncode(data[index].related_record.n_fileas);
        }
    }
};
