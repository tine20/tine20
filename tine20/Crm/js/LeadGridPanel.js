/*
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
 */
 
Ext.namespace('Tine.Crm');

/**
 * Lead grid panel
 * 
 * @namespace   Tine.Crm
 * @class       Tine.Crm.GridPanel
 * @extends     Tine.Tinebase.widgets.app.GridPanel
 * 
 * <p>Lead Grid Panel</p>
 * <p><pre>
 * TODO         show related contacts in grid again
 * TODO         add toolbar buttons (export,  show closed leads, add task, ...) again
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Crm.GridPanel
 */
Tine.Crm.GridPanel = Ext.extend(Tine.Tinebase.widgets.app.GridPanel, {
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
        loadMask: true,
        autoExpandColumn: 'title'
    },
     
    /**
     * inits this cmp
     * @private
     */
    initComponent: function() {
        this.recordProxy = Tine.Crm.leadBackend;
        
        this.actionToolbarItems = this.getToolbarItems();
        this.gridConfig.cm = this.getColumnModel();
        this.filterToolbar = this.getFilterToolbar();
        
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
        
        Tine.Crm.GridPanel.superclass.initComponent.call(this);
        
        //this.action_addInNewWindow.setDisabled(! Tine.Tinebase.common.hasRight('manage', 'Crm', 'records'));
        //this.action_editInNewWindow.requiredGrant = 'editGrant';
        
    },
    
    /**
     * initialises filter toolbar
     * 
     * @return Tine.widgets.grid.FilterToolbar
     * @private
     */
    getFilterToolbar: function() {
        return new Tine.widgets.grid.FilterToolbar({
            filterModels: [
                {label: this.app.i18n._('Lead'),        field: 'query',    operators: ['contains']},
                {label: this.app.i18n._('Lead name'),   field: 'lead_name' },
                new Tine.Crm.LeadState.Filter({}),
                {label: this.app.i18n._('Probability'), field: 'probability', valueType: 'percentage'},
                {label: this.app.i18n._('Turnover'),    field: 'turnover', valueType: 'number', defaultOperator: 'greater'},
                new Tine.widgets.tags.TagFilter({app: this.app})
             ],
             defaultFilter: 'query',
             filters: []
        });
    },
    
    /**
     * returns cm
     * 
     * @return Ext.grid.ColumnModel
     * @private
     * 
     * TODO add LeadState.Renderer
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
                {header: this.app.i18n._('Partner'), id: 'lead_partner', dataIndex: 'partner', width: 175, sortable: false, renderer: this.shortContactRenderer},
                {header: this.app.i18n._('Customer'), id: 'lead_customer', dataIndex: 'customer', width: 175, sortable: false, renderer: this.shortContactRenderer},
                {header: this.app.i18n._('Leadstate'), id: 'leadstate_id', dataIndex: 'leadstate_id', sortable: false, width: 100/*, renderer: Tine.Crm.LeadState.Renderer*/},
                {header: this.app.i18n._('Probability'), id: 'probability', dataIndex: 'probability', width: 50, renderer: Ext.util.Format.percentage },
                {header: this.app.i18n._('Turnover'), id: 'turnover', dataIndex: 'turnover', width: 100, renderer: Ext.util.Format.euMoney }
            ]
        });
    },
    
    /**
     * status column renderer
     * @param {string} value
     * @return {string}
     */
    statusRenderer: function(value) {
        return this.app.i18n._hidden(value);
    },
    
    /**
     * contact column renderer
     * @param {string} value
     * @return {string}
     */
    shortContactRenderer: function(_data, _cell, _record, _rowIndex, _columnIndex, _store) {            
        if( Ext.isArray(_data) && _data.length > 0 ) {
            var org = ( _data[0].org_name !== null ) ? _data[0].org_name : '';
            return '<b>' + Ext.util.Format.htmlEncode(org) + '</b><br />' + Ext.util.Format.htmlEncode(_data[0].n_fileas);
        }
    },
    
    /**
     * return additional tb items
     * @private
     */
    getToolbarItems: function(){
        
        /*
        var toolbar = new Ext.Toolbar({
            id: 'crmToolbar',
            split: false,
            height: 26,
            items: [
                this.actions.addLead,
                this.actions.editLead,
                this.actions.deleteLead,
                //actions.actionAddTask,
                this.actions.exportLead,
                '-',
                new Ext.Button({
                    text: this.translation._('Show details'),
                    enableToggle: true,
                    id: 'crmShowDetailsButton',
                    iconCls: 'showDetailsAction',
                    //cls: 'x-btn-icon',
                    handler: handlerToggleDetails
                }),                    
                new Ext.Button({
                    text: this.translation._('Show closed leads'),
                    enableToggle: true,
                    iconCls: 'showEndedLeadsAction',
                    //cls: 'x-btn-icon',
                    id: 'crmShowClosedLeadsButton',
                    handler: function(toggle) {                        
                        Ext.getCmp('gridCrm').getStore().reload();
                    }                    
                })
            ]
        });
        
        handlerAddTask: function(){
            Tine.Tasks.EditDialog.openWindow({
                relatedApp: 'Crm'
            });
        }
        
        this.actions.exportLead = new Ext.Action({
            requiredGrant: 'readGrant',
            allowMultiple: true,
            text: this.translation._('Export as PDF'),
            tooltip: this.translation._('Export selected lead as PDF'),
            disabled: true,
            handler: this.handlers.exportLead,
            iconCls: 'action_exportAsPdf',
            scope: this
        });
        
        this.actions.addTask = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.translation._('Add task'),
            tooltip: this.translation._('Add task for selected lead'),
            handler: this.handlers.handlerAddTask,
            iconCls: 'actionAddTask',
            disabled: true,
            scope: this
        });

        */
        
        /*
        this.action_showClosedToggle = new Tine.widgets.grid.FilterButton({
            text: this.app.i18n._('Show closed'),
            iconCls: 'action_showArchived',
            field: 'showClosed'
        });
        */
        
        return [
            /*
            new Ext.Toolbar.Separator(),
            this.action_showClosedToggle
            */
        ];
    }    
    
    /// obsolete code follows
    /*
        // get relations
    this.store.on('datachanged', function(store) {
            // get partner & customers from relations
            store.each(function(record){
                var relations = Tine.Crm.splitRelations(record.data.relations, true);
                record.data.customer = relations.customer;
                record.data.partner = relations.partner;
            });        
        });
    
        // admin menu
    updateMainToolbar : function() 
    {
        var menu = Ext.menu.MenuMgr.get('Tinebase_System_AdminMenu');
        menu.removeAll();
        menu.add(
            // @todo    replace with standard popup windows
            {text: this.translation._('Lead states'), handler: Tine.Crm.LeadState.EditDialog},
            {text: this.translation._('Lead sources'), handler: Tine.Crm.LeadSource.EditDialog},
            {text: this.translation._('Lead types'), handler: Tine.Crm.LeadType.EditDialog},
            {text: this.translation._('Products'), handler: Tine.Crm.Product.EditDialog}
        );

        var adminButton = Ext.getCmp('tineMenu').items.get('Tinebase_System_AdminButton');
        adminButton.setIconClass('crmThumbnailApplication');
        if(Tine.Tinebase.common.hasRight('admin', 'Crm')) {
            adminButton.setDisabled(false);
        } else {
            adminButton.setDisabled(true);
        }
    },        
        // detailed contact renderer
        detailedContact: function(_data, _cell, _record, _rowIndex, _columnIndex, _store) {
            if(typeof(_data) == 'object' && !Ext.isEmpty(_data)) {
                var contactDetails = '', style = '';
                for(var i=0; i < _data.length; i++){
                    var org_name           = Ext.isEmpty(_data[i].org_name) === false ? _data[i].org_name : ' ';
                    var n_fileas           = Ext.isEmpty(_data[i].n_fileas) === false ? _data[i].n_fileas : ' ';
                    var adr_one_street     = Ext.isEmpty(_data[i].adr_one_street) === false ? _data[i].adr_one_street : ' ';
                    var adr_one_postalcode = Ext.isEmpty(_data[i].adr_one_postalcode) === false ? _data[i].adr_one_postalcode : ' ';
                    var adr_one_locality   = Ext.isEmpty(_data[i].adr_one_locality) === false ? _data[i].adr_one_locality : ' ';
                    var tel_work           = Ext.isEmpty(_data[i].tel_work) === false ? _data[i].tel_work : ' ';
                    var tel_cell           = Ext.isEmpty(_data[i].tel_cell) === false ? _data[i].tel_cell : ' ';
                    
                    if(i > 0) {
                        style = 'borderTop';
                    }
                    
                    contactDetails = contactDetails + '<table width="100%" height="100%" class="' + style + '">' +
                                         '<tr><td colspan="2">' + Ext.util.Format.htmlEncode(org_name) + '</td></tr>' +
                                         '<tr><td colspan="2"><b>' + Ext.util.Format.htmlEncode(n_fileas) + '</b></td></tr>' +
                                         '<tr><td colspan="2">' + Ext.util.Format.htmlEncode(adr_one_street) + '</td></tr>' +
                                         '<tr><td colspan="2">' + Ext.util.Format.htmlEncode(adr_one_postalcode) + ' ' + adr_one_locality + '</td></tr>' +
                                         '<tr><td width="50%">' + Tine.Crm.Main.translation._('Phone') + ': </td><td width="50%">' + Ext.util.Format.htmlEncode(tel_work) + '</td></tr>' +
                                         '<tr><td width="50%">' + Tine.Crm.Main.translation._('Cellphone') + ': </td><td width="50%">' + Ext.util.Format.htmlEncode(tel_cell) + '</td></tr>' +
                                         '</table> <br />';
                }
                
                return contactDetails;
            }
        }
     */
});
