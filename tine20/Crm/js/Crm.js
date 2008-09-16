/**
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 *              redesign by Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Crm');

/*************************************** CRM TREE PANEL *****************************************/

Tine.Crm = {
	
    /**
     * entry point, required by tinebase
     * creates and returnes app tree panel
     */
	getPanel: function()
	{
    	var tree = new Tine.widgets.container.TreePanel({
            id: 'crmTree',
            iconCls: 'CrmIconCls',
            title: 'CRM',
            itemName: 'Leads',
            containerName: 'Leads',
            appName: 'Crm',
            border: false
        });
            
        tree.on('click', function(node){        	
            Tine.Crm.Main.show(node);
        }, this);
            
        tree.on('beforeexpand', function(panel) {
            if(panel.getSelectionModel().getSelectedNode() === null) {
                panel.expandPath('/root/all');
                panel.selectPath('/root/all');
            }
            panel.fireEvent('click', panel.getSelectionModel().getSelectedNode());
        }, this);
        
        return tree;
	}
};


/*************************************** CRM GENERIC FNUCTIONS *****************************************/
/**
 * split the relations array in contacts and tasks and switch related_record and relation objects
 * 
 * @param array _relations
 * @param boolean _splitAll if set, all different relation types are splitted into arrays
 * @return Object with arrays containing the different relation types
 */
Tine.Crm.splitRelations = function(_relations, _splitAll) {
    var result = null;
            
    if (_splitAll) {
        result = {responsible: [], customer: [], partner: [], tasks: []};
    } else {
        result = {contacts: [], tasks: []};
    }

    if (!_relations) {
        return result;
    }
    
    for (var i=0; i < _relations.length; i++) {
        var newLinkObject = _relations[i]['related_record'];
        newLinkObject.relation = _relations[i];
        newLinkObject.relation_type = _relations[i]['type'].toLowerCase();

        if (!_splitAll && (newLinkObject.relation_type === 'responsible' 
          || newLinkObject.relation_type === 'customer' 
          || newLinkObject.relation_type === 'partner')) {
            result.contacts.push(newLinkObject);
        } else if (newLinkObject.relation_type === 'task') {                
            result.tasks.push(newLinkObject);
        } else {
            switch(newLinkObject.relation_type) {
                case 'responsible':
                    result.responsible.push(newLinkObject);
                    break;
                case 'customer':
                    result.customer.push(newLinkObject);
                    break;
                case 'partner':
                    result.partner.push(newLinkObject);
                    break;
            }
        }
    }
       
    return result;
};

/*************************************** CRM MAIN DIALOG *****************************************/

Tine.Crm.Main = {

	/**
	 * main screen actions
	 */
    actions: {
        addLead: null,
        editLead: null,
        deleteLead: null,
        exportLead: null,
        addTask: null
    },

    /**
     * holds underlaying store
     */
    store: null,
    
    /**
     * holds paging information
     */
    paging: {
        start: 0,
        limit: 50,
        sort: 'lead_name',
        dir: 'ASC'
    },
    
    /**
     * holds current filters
     */
    filter: {
        containerType: 'personal',
        query: '',
        container: false,
        tag: false,
        probability: 0,
        leadstate: false
    },    

	handlers: {
		/**
		 * edit lead
		 */
        handlerEdit: function(){
            var _rowIndex = Ext.getCmp('gridCrm').getSelectionModel().getSelections();
            var lead = _rowIndex[0];
            Tine.Crm.LeadEditDialog.openWindow({lead:lead});
        },
        
        /**
         * delete lead
         */
        handlerDelete: function(){
            Ext.MessageBox.confirm('Confirm', Tine.Crm.Main.translation._('Are you sure you want to delete this lead?'), function(_button) {
                if(_button == 'yes') {            
                    var _rowIndexIds = Ext.getCmp('gridCrm').getSelectionModel().getSelections();            
                    var toDelete_Ids = [];
                
                    for (var i = 0; i < _rowIndexIds.length; ++i) {
                        toDelete_Ids.push(_rowIndexIds[i].data.id);
                    }
                    
                    var leadIds = Ext.util.JSON.encode(toDelete_Ids);
                
                    Ext.Ajax.request({
                        params: {
                            method: 'Crm.deleteLeads',
                            _leadIds: leadIds
                        },
                        text: Tine.Crm.Main.translation._('Deleting lead') + '...',
                        success: function(_result, _request){
                            Ext.getCmp('gridCrm').getStore().reload();
                        },
                        failure: function(result, request){
                            Ext.MessageBox.alert('Failed', Tine.Crm.Main.translation._('Some error occured while trying to delete the lead.'));
                        }
                    });
                }
            });
        },

        /**
         * onclick handler for exportBtn
         */
        exportLead: function(_button, _event) {
            var _rowIndexIds = Ext.getCmp('gridCrm').getSelectionModel().getSelections();            
            var toExportIds = [];
        
            for (var i = 0; i < _rowIndexIds.length; ++i) {
                toExportIds.push(_rowIndexIds[i].data.id);
            }
            
            var leadIds = Ext.util.JSON.encode(toExportIds);

            Tine.Tinebase.Common.openWindow('contactWindow', 'index.php?method=Crm.exportLead&_format=pdf&_leadIds=' + leadIds, 768, 1024);
        },
        
        /**
         * add task handler
         * 
         * @todo    save the link via json request here?
         */
        handlerAddTask: function(){
            Tine.Tasks.EditDialog.openWindow({
                relatedApp: 'Crm'
            });
        }
        
    },        

    /**
     * showCrmToolbar function
     */
    showCrmToolbar: function()
    {                
        var quickSearchField = new Ext.ux.SearchField({
            id: 'quickSearchField',
            width: 200,
            emptyText: this.translation._('Enter searchfilter')
        });
        
        quickSearchField.on('change', function(field){
            if(this.filter.query != field.getValue()){
                this.store.load({params: this.paging});
            }
        }, this);

            
       var filterComboLeadstate = new Ext.ux.form.ClearableComboBox({
            fieldLabel: this.translation._('Leadstate'), 
            blankText: this.translation._('Leadstate') + '...',
            emptyText: this.translation._('leadstate') + '...',
            id:'filterLeadstate',
            name:'leadstate',
            hideLabel: true,
            width: 180,   
            store: Tine.Crm.LeadState.getStore(),
            hiddenName: 'leadstate_id',
            valueField: 'id',
            displayField: 'leadstate',
            typeAhead: true,
            triggerAction: 'all',
            selectOnFocus:true,
            editable: false 
        }); 
       
        filterComboLeadstate.on('select', function() {
       	    this.store.load({params: this.paging});
        }, this);
      
        var filterComboProbability = new Ext.ux.PercentCombo({
            fieldLabel: this.translation._('Probability'), 
            blankText: this.translation._('Probability') + '...',            
            emptyText: this.translation._('Probability') + '...',
            id: 'filterProbability',
            name:'probability',
            hideLabel: true,            
            width:90            	
        });
                
		filterComboProbability.on('select', function() {
			this.store.load({params: this.paging});
		}, this);      
		
        /**
         * handlerToggleDetails function
         */          
        var handlerToggleDetails = function(toggle) {
        	//console.log(toggle.pressed);
            var gridView         = Ext.getCmp('gridCrm').getView();
            var gridColumnModel = Ext.getCmp('gridCrm').getColumnModel();
            
            if(toggle.pressed === true) {
                
                gridColumnModel.setRenderer(1, function(value, meta, record) {
                    return '<b>' + value + '</b><br /><br />' + record.data.description;
                } );                
                
                gridColumnModel.setRenderer(2, Tine.Crm.Main.renderer.detailedContact);
                gridColumnModel.setRenderer(3, Tine.Crm.Main.renderer.detailedContact);
                  
               gridView.refresh();              
               
            } else {
                
                gridColumnModel.setRenderer(1, function(value, meta, record) {
                    return value;
                } );                
                
                gridColumnModel.setRenderer(2, Tine.Crm.Main.renderer.shortContact);
                gridColumnModel.setRenderer(3, Tine.Crm.Main.renderer.shortContact);
                  
               gridView.refresh();                                
            }
        };
        
        // toolbar
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
                '->',
                new Ext.Button({
                    tooltip: this.translation._('Show details'),
                    enableToggle: true,
                    id: 'crmShowDetailsButton',
                    iconCls: 'showDetailsAction',
                    cls: 'x-btn-icon',
                    handler: handlerToggleDetails
                }),                    
                '-',
                new Ext.Button({
                    tooltip: this.translation._('Show closed leads'),
                    enableToggle: true,
                    iconCls: 'showEndedLeadsAction',
                    cls: 'x-btn-icon',
                    id: 'crmShowClosedLeadsButton',
                    handler: function(toggle) {                        
                        Ext.getCmp('gridCrm').getStore().reload();
                    }                    
                }),
                ' ',                
                filterComboLeadstate,
                ' ',
                filterComboProbability,                
                new Ext.Toolbar.Separator(),
                '->',
                ' ',
                quickSearchField
            ]
        });
        
        Tine.Tinebase.MainScreen.setActiveToolbar(toolbar);
    },
        
    /**
     * creates the grid
     * 
     */
    initGridPanel: function() 
    { 
        //var dataStore = this.createDataStore();
        
        var pagingToolbar = new Ext.PagingToolbar({ // inline paging toolbar
            pageSize: 50,
            store: this.store,
            displayInfo: true,
            displayMsg: this.translation._('Displaying leads {0} - {1} of {2}'),
            emptyMsg: this.translation._('No leads found.')
        }); 
        
        var ctxMenuGrid = new Ext.menu.Menu({
	        id:'ctxMenuGrid', 
	        items: [
	            this.actions.editLead,
	            this.actions.deleteLead,
	            this.actions.exportLead,
	            this.actions.addTask
	        ]
	    });

/*        var expander = new Ext.ux.grid.RowExpander({
            enableCaching: false,
            tpl : new Ext.Template(
                '<b>Notes:</b> {description}</div></td>',
                '<td class="x-grid3-col x-grid3-cell"><b>Activities:</b> </td>')
        }); */
        
        var columnModel = new Ext.grid.ColumnModel([
            
			{resizable: true, header: this.translation._('Lead id'), id: 'id', dataIndex: 'id', width: 20, hidden: true},
            {resizable: true, header: this.translation._('Lead name'), id: 'lead_name', dataIndex: 'lead_name', width: 200},
            {resizable: true, header: this.translation._('Partner'), id: 'lead_partner', dataIndex: 'partner', width: 175, sortable: false, renderer: Tine.Crm.Main.renderer.shortContact},
            {resizable: true, header: this.translation._('Customer'), id: 'lead_customer', dataIndex: 'customer', width: 175, sortable: false, renderer: Tine.Crm.Main.renderer.shortContact},
            {resizable: true, header: this.translation._('Leadstate'), id: 'leadstate_id', dataIndex: 'leadstate_id', sortable: false, width: 100, renderer: Tine.Crm.LeadState.Renderer},
            {resizable: true, header: this.translation._('Probability'), id: 'probability', dataIndex: 'probability', width: 50, renderer: Ext.util.Format.percentage },
            {resizable: true, header: this.translation._('Turnover'), id: 'turnover', dataIndex: 'turnover', width: 100, renderer: Ext.util.Format.euMoney }
        ]);
        
        columnModel.defaultSortable = true; // by default columns are sortable
        
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});
        
        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            // update toolbars
            Tine.widgets.ActionUpdater(_selectionModel, this.actions, 'container');
        }, this);
        
        var gridPanel = new Ext.grid.GridPanel({
            id: 'gridCrm',
            store: this.store,
            cm: columnModel,
            tbar: pagingToolbar, 
            stripeRows: true,  
            viewConfig: {
                forceFit:true
            },  
            /* plugins: expander, */                    
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            autoExpandColumn: 'lead_name',
            border: false,
            view: new Ext.grid.GridView({
                autoFill: true,
                forceFit:true,
                ignoreAdd: true,
                emptyText: this.translation._('No leads found.'),
                onLoad: Ext.emptyFn,
                listeners: {
                    beforerefresh: function(v) {
                        v.scrollTop = v.scroller.dom.scrollTop;
                    },
                    refresh: function(v) {
                        v.scroller.dom.scrollTop = v.scrollTop;
                    },
                }
            })            
        });
        
        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
                //this.actions.actionDelete.setDisabled(false);
            }
            ctxMenuGrid.showAt(_eventObject.getXY());
        });
        
        gridPanel.on('rowdblclick', function(_gridPanel, _rowIndexPar, ePar) {
            var record = _gridPanel.getStore().getAt(_rowIndexPar);
            Tine.Crm.LeadEditDialog.openWindow({lead: record});           
        });
       
        this.gridPanel = gridPanel;
        //Tine.Tinebase.MainScreen.setActiveContentPanel(gridPanel);
    },    
      
    /**
     * initComponent
     */
    initComponent: function()
    {
    	// set translation
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Crm');
    
        // set actions
        this.actions.addLead = new Ext.Action({
            requiredGrant: 'addGrant',
            text: this.translation._('Add lead'),
            tooltip: this.translation._('Add new lead'),
            iconCls: 'actionAdd',
            handler: function(){
                Tine.Crm.LeadEditDialog.openWindow({});                
            }   
        });
        
        this.actions.editLead = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.translation._('Edit lead'),
            tooltip: this.translation._('Edit selected lead'),
            disabled: true,
            handler: this.handlers.handlerEdit,
            iconCls: 'actionEdit',
            scope: this
        });
        
        this.actions.deleteLead = new Ext.Action({
            requiredGrant: 'deleteGrant',
            allowMultiple: true,
            singularText: 'Delete lead',
            pluralText: 'Delete leads',
            translationObject: this.translation,
            text: this.translation.ngettext('Delete lead', 'Delete leads', 1),
            tooltip: this.translation._('Delete selected leads'),
            disabled: true,
            handler: this.handlers.handlerDelete,
            iconCls: 'actionDelete',
            scope: this
        });
        
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
        
        // init grid store
        this.initStore();
        this.initGridPanel();
    },

    /**
     * init the leads json grid store
     */
    initStore: function(){
        this.store = new Ext.data.JsonStore({
            id: 'id',
            root: 'results',
            totalProperty: 'totalcount',
            fields: Tine.Crm.Model.Lead,
            remoteSort: true,
            baseParams: {
                method: 'Crm.searchLeads'
            },
            sortInfo: {
                field: 'lead_name',
                dir: 'ASC'
            }
        });
        
        // register store
        Ext.StoreMgr.add('LeadsGridStore', this.store);
        
        // prepare filter
        this.store.on('beforeload', function(store, options){
            
            // for some reasons, paging toolbar eats sort and dir
            if (store.getSortState()) {
                this.filter.sort = store.getSortState().field;
                this.filter.dir = store.getSortState().direction;
            } else {
                this.filter.sort = this.store.sort;
                this.filter.dir = this.store.dir;
            }
            this.filter.start = options.params.start;
            this.filter.limit = options.params.limit;
            
            // container
            var nodeAttributes = Ext.getCmp('crmTree').getSelectionModel().getSelectedNode().attributes || {};
            this.filter.containerType = nodeAttributes.containerType ? nodeAttributes.containerType : 'all';
            this.filter.container = nodeAttributes.container ? nodeAttributes.container.id : null;
            this.filter.owner = nodeAttributes.owner ? nodeAttributes.owner.accountId : null;

            // toolbar
            this.filter.showClosed = Ext.getCmp('crmShowClosedLeadsButton') ? Ext.getCmp('crmShowClosedLeadsButton').pressed : false;
            this.filter.probability = Ext.getCmp('filterProbability') ? Ext.getCmp('filterProbability').getValue() : '';
            this.filter.query = Ext.getCmp('quickSearchField') ? Ext.getCmp('quickSearchField').getValue() : '';
            this.filter.leadstate = Ext.getCmp('filterLeadstate') ? Ext.getCmp('filterLeadstate').getValue() : '';

            options.params.filter = Ext.util.JSON.encode(this.filter);
        }, this);
        
        this.store.on('datachanged', function(store) {
            // get partner & customers from relations
            store.each(function(record){
                var relations = Tine.Crm.splitRelations(record.data.relations, true);
                record.data.customer = relations.customer;
                record.data.partner = relations.partner;
            });        
        });
    },
    
    /**
     * show
     */
    show: function(_node) 
    {    	
        var currentToolbar = Tine.Tinebase.MainScreen.getActiveToolbar();
        if (currentToolbar === false || currentToolbar.id != 'crmToolbar') {
            if (!this.girdPanel) {
                this.initComponent();
            }
            Tine.Tinebase.MainScreen.setActiveContentPanel(this.gridPanel, true);
            this.store.load({
                params: this.paging
            });
            
            this.showCrmToolbar();
            this.updateMainToolbar();
        } else {
            // note: if node is clicked, it is not selected!
            _node.getOwnerTree().selectPath(_node.getPath());
        	this.store.load({params: this.paging});
        }
        
    },    
        
    /**
     * updateMainToolbar
     */
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
        if(Tine.Crm.rights.indexOf('admin') > -1) {
            adminButton.setDisabled(false);
        } else {
        	adminButton.setDisabled(true);
        }

        var preferencesButton = Ext.getCmp('tineMenu').items.get('Tinebase_System_PreferencesButton');
        preferencesButton.setIconClass('crmThumbnailApplication');
        adminButton.setDisabled(false);
    },

    /**
     * reload
     */
    reload: function() 
    {
        if(Ext.ComponentMgr.all.containsKey('gridCrm')) {
            setTimeout ("Ext.getCmp('gridCrm').getStore().reload()", 200);
        }
    },
    
    /**
     * renderer
     */
    renderer: 
    {
        shortContact: function(_data, _cell, _record, _rowIndex, _columnIndex, _store) {        	
            if( Ext.isArray(_data) && _data.length > 0 ) {
                var org = ( _data[0].org_name !== null ) ? _data[0].org_name : '';
                return '<b>' + Ext.util.Format.htmlEncode(org) + '</b><br />' + Ext.util.Format.htmlEncode(_data[0].n_fileas);
            }
        },          
        
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
    }    
}; // end of application (CRM MAIN DIALOG)
  
/*************************************** LEAD EDIT DIALOG ****************************************/

Tine.Crm.LeadEditDialog = Ext.extend(Tine.widgets.dialog.EditRecord, {
	
    /**
     * @cfg {Tine.Crm.Model.Lead} lead to edit
     */
    lead: null,
    
    /**
     * @private
     */
    windowNamePrefix: 'LeadEditWindow_',
    labelAlign: 'top',
    
	/**
	 * define actions
	 */
	actions: {
        addResponsible: null,
        addCustomer: null,
        addPartner: null,
        addContact: null,
        editContact: null,
        linkContact: null,
        unlinkContact: null,
        addTask: null,		
        editTask: null,
        linkTask: null,
        unlinkTask: null,
        unlinkProduct: null,
        exportLead: null
	},
	
    /**
     * event handlers
     */
    handlers: {   
       
        /**
         * unlink action handler for linked objects
         * 
         * remove selected objects from store
         * needs _button.gridId and _button.storeName
         */
        unlink: function(_button, _event) {        	        	                	
            var selectedRows = Ext.getCmp(_button.gridId).getSelectionModel().getSelections();
            var store = Ext.StoreMgr.lookup(_button.storeName);
            for (var i = 0; i < selectedRows.length; ++i) {
                store.remove(selectedRows[i]);
            }           
        },

        /**
         * onclick handler for addContact
         */
        addContact: function(_button, _event) {
            var contactWindow = Tine.Addressbook.ContactEditDialog.openWindow({});        	
            
            contactWindow.on('update', function(contact) {
                switch ( _button.contactType ) {
                	case 'responsible':
                	   contact.data.relation_type = 'responsible';
                	   break;
                    case 'customer':
                       contact.data.relation_type = 'customer';
                       break;
                    case 'partner':
                       contact.data.relation_type = 'partner';
                       break;
                }
                this.onContactUpdate(contact);
            }, this);
        },
            
        /**
         * onclick handler for editContact
         */
        editContact: function(_button, _event) {
            var selectedRows = Ext.getCmp('crmGridContacts').getSelectionModel().getSelections();
            
            var contactWindow = Tine.Addressbook.ContactEditDialog.openWindow({contact: selectedRows[0]});         
            contactWindow.on('update', this.onContactUpdate, this);            
        },

        /**
         * linkContact
         * 
         * link an existing contact -> create and activate contact picker/search dialog
         * @todo    add bigger & better search dialog later
         */
        linkContact: function(_button, _event) {
        	// create new contact search grid
        	var contactSearchGrid = this.getLinksGrid('ContactsSearch', this.translation._('Search Contacts'));
        	
        	// add grid to tabpanel & activate
        	var linkTabPanel = Ext.getCmp('linkPanelTop');
        	linkTabPanel.add(contactSearchGrid);
            linkTabPanel.activate(contactSearchGrid);            	
        },

        /**
         * onclick handler for add task
         * 
         */
        addTask: function(_button, _event) {
            var taskPopup = Tine.Tasks.EditDialog.openWindow({
                relatedApp: 'Crm'
            });
            taskPopup.on('update', this.onTaskUpdate, this);
        },
            
        /**
         * onclick handler for editBtn
         * 
         */
        editTask: function(_button, _event) {
            var selectedRows = Ext.getCmp('crmGridTasks').getSelectionModel().getSelections();
            var selectedTask = selectedRows[0];
            
            var taskPopup = Tine.Tasks.EditDialog.openWindow({
                task: selectedTask
            });
            taskPopup.on('update', this.onTaskUpdate, this);
        },
        
        /**
         * linkTask
         * 
         * link an existing task, open 'object' picker dialog
         * @todo implement
         */
        linkTask: function(_button, _event) {
            
        },

        /**
         * onclick handler for exportBtn
         */
        exportLead: function(_button, _event) {         
        	
        	var leadId = Ext.util.JSON.encode([_button.leadId]);
        	
            Tine.Tinebase.Common.openWindow('exportWindow', 'index.php?method=Crm.exportLead&_format=pdf&_leadIds=' + leadId, 768, 1024);
        }
    },
    
    /**
     * handler apply changes
     */
    handlerApplyChanges: function(_button, _event, _closeWindow) {
        var leadForm = this.getForm();
        var lead = this.lead;
        
        if(leadForm.isValid()) {  
            Ext.MessageBox.wait(this.translation._('Please wait'), this.translation._('Saving lead') + '...');                
            leadForm.updateRecord(lead);
            
            // get linked stuff
            lead = this.getAdditionalData(lead);

            Ext.Ajax.request({
                scope: this,
                params: {
                    method: 'Crm.saveLead', 
                    lead: Ext.util.JSON.encode(lead.data)
                },
                success: function(response) {
                    if(window.opener.Tine.Crm) {
                        window.opener.Tine.Crm.Main.reload();
                    } 
                    if (_closeWindow === true) {
                        window.setTimeout("window.close()", 400);
                    }
                    
                    this.onRecordLoad(response);

                    // update stores
                    var relations = Tine.Crm.splitRelations(this.lead.data.relations);
                    this.loadContactsStore(relations.contacts, true);        
                    this.loadTasksStore(relations.tasks, true);
                    this.loadProductsStore(lead.data.products, true);

                    Ext.MessageBox.hide();
                },
                failure: function ( result, request) { 
                    Ext.MessageBox.alert(this.translation._('Failed'), this.translation._('Could not save lead.')); 
                }
            });
        } else {
            Ext.MessageBox.alert('Errors', this.translation._('Please fix the errors noted.'));
        }
    },
    
    /**
     * update event handler for related contacts
     */
    onContactUpdate: function(contact) {
        var storeContacts = Ext.StoreMgr.lookup('ContactsStore');
        contact.id = contact.data.id;
        var myContact = storeContacts.getById(contact.id);
        if (myContact) {
            myContact.beginEdit();
            for (var p in contact.data) { 
                myContact.set(p, contact.get(p));
            }
            myContact.endEdit();
        } else {
            storeContacts.add(contact);
        }
    },
    
    /**
     * update event handler for related tasks
     */
    onTaskUpdate: function(task) {
        var storeTasks = Ext.StoreMgr.lookup('TasksStore');
        var myTask = storeTasks.getById(task.id);
        
        if (myTask) { 
            // copy values from edited task
            myTask.beginEdit();
            for (var p in task.data) { 
                myTask.set(p, task.get(p));
            }
            myTask.endEdit();
            
        } else {
            task.data.relation_type = 'task';
            storeTasks.add(task);        
        }
    },
    
    /**
     * getRelationData
     * get the record relation data (switch relation and related record)
     * 
     * @param   Object record with relation data
     * @return  Object relation with record data
     */
    getRelationData: function(record) {
        var relation = null; 
        
        if (record.data.relation) {
            relation = record.data.relation;
        } else {
        	// empty relation for new record
            relation = {};
        }

        // set the relation type
        if (!relation.type) {
            relation.type = record.data.relation_type.toUpperCase();
        }
        
        // do not do recursion!
        delete record.data.relation;
        //delete record.data.relation_type;
        
        // save record data        
        relation.related_record = record.data;
        
        return relation;
    },

	/**
     * getAdditionalData
     * collects additional data (start/end dates, linked contacts, ...)
     * 
     * @param   Tine.Crm.Model.Lead lead
     * @return  Tine.Crm.Model.Lead lead
     * 
     * @todo move relation handling .each() to extra function
     */
    getAdditionalData: function(lead) {
        // collect data of relations
    	var relations = [];
    	
    	// contacts
        var storeContacts = Ext.StoreMgr.lookup('ContactsStore');
        //console.log(storeContacts);
        storeContacts.each(function(record) {                     
            relations.push(this.getRelationData(record));
        }, this);
        
        // tasks
        var storeTasks = Ext.StoreMgr.lookup('TasksStore');    
        //console.log(storeTasks);
        storeTasks.each(function(record) {
            relations.push(this.getRelationData(record));
        }, this);
        
        //console.log(relations);        
        lead.data.relations = relations;
        
        // add products
        var linksProducts = [];
        var storeProducts = Ext.StoreMgr.lookup('ProductsStore');       

        storeProducts.each(function(record) {
            linksProducts.push(record.data);
        });
        
        lead.data.products = linksProducts;
        
        return lead;
    },
        
    /**
     * getLinksGrid
     * get the grids for contacts/tasks/products/...
     * 
     * @param   string  type
     * @param   string  grid title
     * @return  grid object
     * 
     * @todo    move to LeadEditDialog.js ?
     */
    getLinksGrid: function(_type, _title) {
    	// init vars
    	var storeName = _type + 'Store';
    	var columnModel = null;
    	var autoExpand = 'n_fileas';
    	var rowSelectionModel = null;
    	var bbarItems = [];
    	
    	// set the column / row selection model
    	switch ( _type ) {
    		
            /******************* contacts tabpanel ********************/                

    		case 'Contacts':
    		
                // @todo move that to renderer/addressbook ?
                columnModel = new Ext.grid.ColumnModel([
                    {id:'id', header: "id", dataIndex: 'id', width: 25, sortable: true, hidden: true },
                    {id:'n_fileas', header: this.translation._('Name'), dataIndex: 'n_fileas', width: 100, sortable: true, renderer: 
                        function(val, meta, record) {
                            var org_name           = Ext.isEmpty(record.data.org_name) === false ? record.data.org_name : ' ';
                            var n_fileas           = Ext.isEmpty(record.data.n_fileas) === false ? record.data.n_fileas : ' ';                            
                            var formated_return = '<b>' + Ext.util.Format.htmlEncode(n_fileas) + '</b><br />' + Ext.util.Format.htmlEncode(org_name);
                            
                            return formated_return;
                        }
                    },
                    {id:'contact_one', header: this.translation._("Address"), dataIndex: 'adr_one_locality', width: 160, sortable: false, renderer: function(val, meta, record) {
                            var adr_one_street     = Ext.isEmpty(record.data.adr_one_street) === false ? record.data.adr_one_street : ' ';
                            var adr_one_postalcode = Ext.isEmpty(record.data.adr_one_postalcode) === false ? record.data.adr_one_postalcode : ' ';
                            var adr_one_locality   = Ext.isEmpty(record.data.adr_one_locality) === false ? record.data.adr_one_locality : ' ';
                            var formated_return =  
                                Ext.util.Format.htmlEncode(adr_one_street) + '<br />' + 
                                Ext.util.Format.htmlEncode(adr_one_postalcode) + ' ' + Ext.util.Format.htmlEncode(adr_one_locality);
                        
                            return formated_return;
                        }
                    },
                    {id:'tel_work', header: this.translation._("Contactdata"), dataIndex: 'tel_work', width: 160, sortable: false, renderer: function(val, meta, record) {
                            var translation = new Locale.Gettext();
                            translation.textdomain('Crm');
                            var tel_work           = Ext.isEmpty(record.data.tel_work) === false ? translation._('Phone') + ': ' + record.data.tel_work : ' ';
                            var tel_cell           = Ext.isEmpty(record.data.tel_cell) === false ? translation._('Cellphone') + ': ' + record.data.tel_cell : ' ';          
                            var formated_return = tel_work + '<br/>' + tel_cell + '<br/>';
                            return formated_return;
                        }                        
                    },    
                    {
                        id:'relation_type', 
                        header: this.translation._("Type"), 
                        dataIndex: 'relation_type', 
                        width: 75, 
                        sortable: true,
                        renderer: Tine.Crm.contactType.Renderer,
                        editor: new Tine.Crm.contactType.ComboBox({
                            autoExpand: true,
                            blurOnSelect: true,
                            listClass: 'x-combo-list-small'
                        })
                    }
                ]);
                
                rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});
                rowSelectionModel.on('selectionchange', function(_selectionModel) {
                    var rowCount = _selectionModel.getCount();
                    this.actions.unlinkContact.setDisabled(!Tine.Crm.LeadEditDialog.lead.get('container').account_grants.editGrant || rowCount != 1);
                    this.actions.editContact.setDisabled(rowCount != 1);
                }, this);
                
                bbarItems = [                
                    this.actions.linkContact,                    
                    this.actions.addContact,
                    this.actions.unlinkContact
                ]; 
                
                break;
            
            /******************* contacts search tabpanel ********************/                
                
            case 'ContactsSearch':
            
                columnModel = new Ext.grid.ColumnModel([
                    {id:'id', header: "id", dataIndex: 'id', width: 25, sortable: true, hidden: true },
                    {id:'n_fileas', header: this.translation._('Name'), dataIndex: 'n_fileas', width: 120, sortable: true},
                    {id:'org_name', header: this.translation._('Organisation'), dataIndex: 'org_name', width: 120, sortable: true},
                    {
                        id:'relation_type', 
                        header: this.translation._("Type"), 
                        dataIndex: 'relation_type', 
                        width: 75, 
                        sortable: false,
                        renderer: Tine.Crm.contactType.Renderer,
                        editor: new Tine.Crm.contactType.ComboBox({
                            autoExpand: true,
                            blurOnSelect: true,
                            listClass: 'x-combo-list-small'
                        })
                    }
                ]);
                
                rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});
                
                storeName = 'ContactsStore';
                
                break;

            /******************* tasks tabpanel ********************/                

            case 'Tasks':
            
                columnModel = [{
                    id: 'summary',
                    header: this.translation._("Summary"),
                    width: 100,
                    sortable: true,
                    dataIndex: 'summary',
                    //editor: new Ext.form.TextField({
                    //  allowBlank: false
                    //}),
                    quickaddField: new Ext.form.TextField({
                        emptyText: this.translation._('Add a task...')
                    })
                }, {
                    id: 'due',
                    header: this.translation._("Due Date"),
                    width: 55,
                    sortable: true,
                    dataIndex: 'due',
                    renderer: Tine.Tinebase.Common.dateRenderer,
                    editor: new Ext.ux.form.ClearableDateField({
                        //format : 'd.m.Y'
                    }),
                    quickaddField: new Ext.ux.form.ClearableDateField({
                        //value: new Date(),
                        //format : "d.m.Y"
                    })
                }, {
                    id: 'priority',
                    header: this.translation._("Priority"),
                    width: 45,
                    sortable: true,
                    dataIndex: 'priority',
                    renderer: Tine.widgets.Priority.renderer,
                    editor: new Tine.widgets.Priority.Combo({
                        allowBlank: false,
                        autoExpand: true,
                        blurOnSelect: true
                    }),
                    quickaddField: new Tine.widgets.Priority.Combo({
                        autoExpand: true
                    })
                }, {
                    id: 'status_id',
                    header: this.translation._("Status"),
                    width: 45,
                    sortable: true,
                    dataIndex: 'status_id',
                    renderer: Tine.Tasks.status.getStatusIcon,
                    editor: new Tine.Tasks.status.ComboBox({
                        autoExpand: true,
                        blurOnSelect: true,
                        listClass: 'x-combo-list-small'
                    }),
                    quickaddField: new Tine.Tasks.status.ComboBox({
                        autoExpand: true
                    })
                }];
                
            	rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});
                rowSelectionModel.on('selectionchange', function(_selectionModel) {
                    var rowCount = _selectionModel.getCount();
                    this.actions.unlinkTask.setDisabled(!Tine.Crm.LeadEditDialog.lead.get('container').account_grants.editGrant || rowCount != 1);
                    this.actions.editTask.setDisabled(rowCount != 1);
                }, this);

                autoExpand = 'summary';
                
                bbarItems = [
                    this.actions.addTask,
                    this.actions.unlinkTask
                ]; 
                
                break;

            /******************* products tabpanel ********************/                
                
            case 'Products':
            
                columnModel = [
                {
                    header: this.translation._("Product"),
                    id: 'product_id',
                    dataIndex: 'product_id',
                    sortable: true,
                    width: 150,
                    editor: new Tine.Crm.Product.ComboBox({
                        store: Tine.Crm.Product.getStore() 
                    }),
                    quickaddField: new Tine.Crm.Product.ComboBox({
                        emptyText: this.translation._('Add a product...'),
                        store: Tine.Crm.Product.getStore(),
                        setPrice: true,
                        id: 'new-product_combo'
                    }),
                    renderer: Tine.Crm.Product.renderer
                },
                {
                    id: 'product_desc',
                    header: this.translation._("Description"),
                    //width: 100,
                    sortable: true,
                    dataIndex: 'product_desc',
                    editor: new Ext.form.TextField({
                        allowBlank: false
                    }),
                    quickaddField: new Ext.form.TextField({
                        allowBlank: false
                    })
                },
                {
                    id: 'product_price',
                    header: this.translation._("Price"),
                    dataIndex: 'product_price',
                    width: 80,
                    align: 'right',
                    editor: new Ext.form.NumberField({
                        allowBlank: false,
                        allowNegative: false,
                        decimalSeparator: ','
                        }),
                    quickaddField: new Ext.form.NumberField({
                        allowBlank: false,
                        allowNegative: false,
                        decimalSeparator: ',',
                        id: 'new-product_price'
                        }),  
                    renderer: Ext.util.Format.euMoney
                }                
                ];
                
                rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});
                rowSelectionModel.on('selectionchange', function(_selectionModel) {
                    var rowCount = _selectionModel.getCount();                    
                    if(rowCount < 1) {
                        this.actions.unlinkProduct.setDisabled(true);
                    } 
                    if (rowCount == 1) {
                        this.actions.unlinkProduct.setDisabled(false);
                    }    
                    if(rowCount > 1) {                
                        this.actions.unlinkProduct.setDisabled(false);
                    }
                }, this);
                
                autoExpand = 'product_desc';
                
                bbarItems = [
                    this.actions.unlinkProduct
                ]; 
                
                break;

        } // end switch

        // get store and create grid
        var gridStore = Ext.StoreMgr.lookup(storeName);  
        var grid = null;
        
        if ( _type === 'ContactsSearch' ) {
            grid = new Tine.widgets.GridPicker({
            	id: 'crmGrid' + _type,
            	title: _title,
                gridStore: gridStore,
                columnModel: columnModel,
                bbarItems: bbarItems,
                closable: true
            });
            
        } else if ( _type === 'Contacts' ) {
            grid = new Ext.grid.EditorGridPanel({
                id: 'crmGrid' + _type,
                title: _title,
                cm: columnModel,
                store: gridStore,
                selModel: rowSelectionModel,
                autoExpandColumn: autoExpand,
                bbar: bbarItems,
                clicksToEdit: 'auto'
            });            

            grid.on('rowdblclick', function(_gridPanel, _rowIndexPar, ePar) {
                var record = _gridPanel.getStore().getAt(_rowIndexPar);
                Tine.Addressbook.ContactEditDialog.openWindow({contact: record});           
            });
            
        } else if ( _type === 'Tasks') {        	
            grid = new Ext.ux.grid.QuickaddGridPanel({
                title: _title,
                id: 'crmGrid' + _type,
                border: false,
                store: gridStore,
                clicksToEdit: 'auto',
                bbar: bbarItems,
                enableColumnHide:false,
                enableColumnMove:false,
                sm: rowSelectionModel,
                loadMask: true,
                quickaddMandatory: 'summary',
                autoExpandColumn: 'summary',
                columns: columnModel,
                view: new Ext.grid.GridView({
                    autoFill: true,
                    forceFit:true,
                    ignoreAdd: true,
                    emptyText: this.translation._('No Tasks to display'),
                    onLoad: Ext.emptyFn,
                    listeners: {
                        beforerefresh: function(v) {
                            v.scrollTop = v.scroller.dom.scrollTop;
                        },
                        refresh: function(v) {
                            v.scroller.dom.scrollTop = v.scrollTop;
                        },
                    }
                })
            });
            
            grid.on('newentry', function(taskData){

                // add new task to store
                var gridStore = Ext.StoreMgr.lookup('TasksStore');      
                var newTask = taskData;
                newTask.relation_type = 'task';
                gridStore.loadData([newTask], true);
            	
                return true;
            }, this);
            
            // hack to get percentage editor working
            grid.on('rowclick', function(grid,row,e) {
                var cell = Ext.get(grid.getView().getCell(row,1));
                var dom = cell.child('div:last');
                while (cell.first()) {
                    cell = cell.first();
                    cell.on('click', function(e){
                        e.stopPropagation();
                        grid.fireEvent('celldblclick', grid, row, 1, e);
                    });
                }
            }, this);        
            
            grid.on('rowdblclick', function(_gridPanel, _rowIndexPar, ePar) {
                var record = _gridPanel.getStore().getAt(_rowIndexPar);
                Tine.Tasks.EditDialog.openWindow({task: record});
            });            

        } else if ( _type === 'Products') {        	
            grid = new Ext.ux.grid.QuickaddGridPanel({
                title: _title,
                id: 'crmGrid' + _type,
                border: false,
                store: gridStore,
                clicksToEdit: 'auto',
                bbar: bbarItems,
                enableColumnHide:false,
                enableColumnMove:false,
                sm: rowSelectionModel,
                loadMask: true,
                quickaddMandatory: 'product_id',
                autoExpandColumn: 'product_desc',
                columns: columnModel
            });
            
            grid.on('newentry', function(productData){

                // add new product to store
                var gridStore = Ext.StoreMgr.lookup('ProductsStore');      
                var newProduct = [productData];
                gridStore.loadData(newProduct, true);
                
                return true;
            }, this);
            
        } else {        	
            grid = {
                xtype:'grid',
                id: 'crmGrid' + _type,
                title: _title,
                cm: columnModel,
                store: gridStore,
                selModel: rowSelectionModel,
                autoExpandColumn: autoExpand,
                bbar: bbarItems
            };            
        }
        	
        return grid;       
    },
    
    /**
     * set context menu for link grid
     * 
     * @param   string _type (Contacts|Tasks|Products)
     */
    setLinksContextMenu: function(_type) {
    	// init vars
    	var rowItems = [];
    	var gridItems = [];
    	
        switch ( _type ) {
            case 'Contacts':
                var addNewItems = {
                    text: this.translation._('Add new contact'),
                    iconCls: 'actionAdd',
                    contactType: 'partner',
                    handler: this.handlers.addContact,
                    menu: {
                        items: [
                            this.actions.addResponsible,
                            this.actions.addCustomer,
                            this.actions.addPartner
                        ]
                    }
                }; 
                // items for row context menu
                rowItems = [
                    this.actions.editContact,
                    this.actions.unlinkContact,
                    '-',
                    this.actions.linkContact,
                    addNewItems
                ];
                // items for all grid context menu
                gridItems = [
                    this.actions.linkContact,
                    addNewItems
                ];
                break;
                
            case 'Tasks':
                // items for row context menu
                rowItems = [
                    this.actions.editTask,
                    this.actions.unlinkTask,
                    '-',
                    //this.actions.linkTask,
                    this.actions.addTask
                ];
                // items for all grid context menu
                gridItems = [
                    //this.actions.linkTask,
                    this.actions.addTask
                ];
                break;
                
            case 'Products':
                // items for row context menu
                rowItems = [
                    this.actions.unlinkProduct
                ];
                break;
        }
        
        var grid = Ext.getCmp('crmGrid' + _type);
        grid.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();

            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
            }
            
            var ctxMenuGrid = new Ext.menu.Menu({
                id:'ctxMenuGridRow' + _type, 
                items: rowItems
            });

            ctxMenuGrid.showAt(_eventObject.getXY());
        }, this);
        
        grid.on('contextmenu', function(_eventObject) {
            _eventObject.stopEvent();
            
            var ctxMenuGrid = new Ext.menu.Menu({
                id:'ctxMenuGrid' + _type, 
                items: gridItems
            });

            ctxMenuGrid.showAt(_eventObject.getXY());
        }, this);
        
    },
    
    /**
     * get linked contacts store and put it into store manager
     * 
     * @param   array _contacts
     * @param   boolean _reload reload or create new store
     */
    loadContactsStore: function(_contacts, _reload) {
    	var storeContacts = null;
    	
    	if (_reload) {
    	   	storeContacts = Ext.StoreMgr.lookup('ContactsStore');

    	   	// empty store and fill with data
    	   	storeContacts.removeAll();
    	   	
            if(_contacts) {
                storeContacts.loadData(_contacts, true);                    
            }
    	   	
    	} else {
    		
    		var contactFields = Tine.Addressbook.Model.ContactArray;
    		//console.log(contactFields);
    		contactFields.push({name: 'relation'});   // the relation object           
            contactFields.push({name: 'relation_type'});     
    		
            storeContacts = new Ext.data.JsonStore({
                id: 'id',
                fields: contactFields
            });
                
            if(_contacts) {
                storeContacts.loadData(_contacts, true);                    
            }
    
            storeContacts.setDefaultSort('type', 'asc');   
            
            Ext.StoreMgr.add('ContactsStore', storeContacts);
    	}
    },

    /**
     * get linked tasks store and put it into store manager
     * 
     * @param   array _tasks
     * @param   boolean _reload reload or create new store
     */
    loadTasksStore: function(_tasks, _reload) {
    	var storeTasks = null;
    	
    	if (_reload) {

    		storeTasks = Ext.StoreMgr.lookup('TasksStore');

            // empty store and fill with data
            storeTasks.removeAll();
    		
            if(_tasks) {
                storeTasks.loadData(_tasks);                    
            }

        } else {        	
        	
            var taskFields = Tine.Tasks.TaskArray;
            taskFields.push({name: 'relation'});   // the relation object           
            taskFields.push({name: 'relation_type'});     
        	
            storeTasks = new Ext.data.JsonStore({
                id: 'id',
                fields: taskFields
            });
                
            if(_tasks) {
                storeTasks.loadData(_tasks);                    
            }
            
            Ext.StoreMgr.add('TasksStore', storeTasks);
    	}
    },

    /**
     * get linked products store and put it into store manager
     * 
     * @param   array _products
     * @param   boolean _reload reload or create new store
     */
    loadProductsStore: function(_products, _reload) {
    	var storeProducts = null;
    	
    	if (_reload) {
    		
            storeProducts = Ext.StoreMgr.lookup('ProductsStore');

            // empty store and fill with data
            storeProducts.removeAll();
            
            if(_products) {
                storeProducts.loadData(_products);                    
            }

        } else {
    	
            storeProducts = new Ext.data.JsonStore({
                id: 'id',
                fields: Tine.Crm.Model.ProductLink
            });
                
            if(_products) {
                storeProducts.loadData(_products);                    
                //storeProducts.setDefaultSort('remark', 'asc');     
            }
            
            // update price if new product is chosen
            storeProducts.on('update', function(store, record, index) {
                if(record.data.product_id && !arguments[1].modified.product_price) {          
                    var st_productsAvailable = Tine.Crm.Product.getStore();
                    var preset_price = st_productsAvailable.getById(record.data.product_id);
                    record.data.product_price = preset_price.data.price;
                }
            }); 
            
            Ext.StoreMgr.add('ProductsStore', storeProducts);
    	}
    },    
    
    /**
     * initActions
     * sets the translation object and actions
     * 
     * @param   Tine.Crm.Model.Lead lead lead data
     */
    initActions: function(lead) {
        // contacts
        this.actions.addResponsible = new Ext.Action({
            requiredGrant: 'editGrant',
        	contactType: 'responsible',
            text: this.translation._('Add responsible'),
            tooltip: this.translation._('Add new responsible contact'),
            iconCls: 'contactIconResponsible',
            //disabled: true,
            scope: this,
            handler: this.handlers.addContact
        });
        
        this.actions.addCustomer = new Ext.Action({
            requiredGrant: 'editGrant',
            contactType: 'customer',
            text: this.translation._('Add customer'),
            tooltip: this.translation._('Add new customer contact'),
            iconCls: 'contactIconCustomer',
            //disabled: true,
            scope: this,
            handler: this.handlers.addContact
        });
        
        this.actions.addPartner = new Ext.Action({
            requiredGrant: 'editGrant',
            contactType: 'partner',
            text: this.translation._('Add partner'),
            tooltip: this.translation._('Add new partner contact'),
            iconCls: 'contactIconPartner',
            //disabled: true,
            scope: this,
            handler: this.handlers.addContact
        });

        // split button with all contact types
        this.actions.addContact = new Ext.SplitButton({
            requiredGrant: 'editGrant',
            contactType: 'customer',
            text: this.translation._('Add new contact'),
            tooltip: this.translation._('Add new customer contact'),
            iconCls: 'actionAdd',
            scope: this,
            handler: this.handlers.addContact,
            menu: {
                items: [
                    this.actions.addResponsible,
                    this.actions.addCustomer,
                    this.actions.addPartner
                ]
            }
        }); 
        
        this.actions.editContact = new Ext.Action({
            requiredGrant: 'editGrant',
            text: this.translation._('Edit contact'),
            tooltip: this.translation._('Edit selected contact'),
            disabled: true,
            iconCls: 'actionEdit',
            scope: this,
            handler: this.handlers.editContact
        });

        this.actions.linkContact = new Ext.Action({
            requiredGrant: 'editGrant',
            text: this.translation._('Add existing contact'),
            tooltip: this.translation._('Add existing contact to lead'),
            //disabled: true,
            iconCls: 'contactIconPartner',
            scope: this,
            handler: this.handlers.linkContact
        });
        
        this.actions.unlinkContact = new Ext.Action({
            requiredGrant: 'editGrant',
            text: this.translation._('Unlink contact'),
            tooltip: this.translation._('Unlink selected contacts'),
            disabled: true,
            iconCls: 'actionDelete',
            scope: this,
            gridId: 'crmGridContacts',
            storeName: 'ContactsStore',            
            handler: this.handlers.unlink
        });
        
        // tasks
        this.actions.addTask = new Ext.Action({
            requiredGrant: 'editGrant',
            text: this.translation._('Add task'),
            tooltip: this.translation._('Add new task'),
            iconCls: 'actionAdd',
            scope: this,
            handler: this.handlers.addTask
        });
        
        this.actions.editTask = new Ext.Action({
            requiredGrant: 'editGrant',
            text: this.translation._('Edit task'),
            tooltip: this.translation._('Edit selected task'),
            disabled: true,
            iconCls: 'actionEdit',
            scope: this,
            handler: this.handlers.editTask
        });
        
        this.actions.linkTask = new Ext.Action({
            requiredGrant: 'editGrant',
            text: this.translation._('Add task'),
            tooltip: this.translation._('Add existing task to lead'),
            disabled: true,
            iconCls: 'actionAddTask',
            scope: this,
            handler: this.handlers.linkTask
        });

        this.actions.unlinkTask = new Ext.Action({
            requiredGrant: 'editGrant',
            text: this.translation._('Remove tasks'),
            tooltip: this.translation._('Remove selected tasks'),
            disabled: true,
            iconCls: 'actionDelete',
            scope: this,
            gridId: 'crmGridTasks',
            storeName: 'TasksStore',            
            handler: this.handlers.unlink
        });

        // products
        this.actions.unlinkProduct = new Ext.Action({
            requiredGrant: 'editGrant',
            text: this.translation._('Remove products'),
            tooltip: this.translation._('Remove selected products'),
            disabled: true,
            iconCls: 'actionDelete',
            scope: this,
            gridId: 'crmGridProducts',
            storeName: 'ProductsStore',            
            handler: this.handlers.unlink
        });

        // other
        this.actions.exportLead = new Ext.Action({
            requiredGrant: 'editGrant',
            text: this.translation._('Export as PDF'),
            tooltip: this.translation._('Export as PDF'),
            iconCls: 'action_exportAsPdf',
            scope: this,
            handler: this.handlers.exportLead,
            leadId: lead.data.id
        });
    },
    
    /**
     * display the event edit dialog
     *
     * @param   array   _lead
     */
    initComponent: function() {	
        this.lead = this.lead ? this.lead : new Tine.Crm.Model.Lead({}, 0);
        
        Ext.Ajax.request({
            scope: this,
            success: this.onRecordLoad,
            params: {
                method: 'Crm.getLead',
                leadId: this.lead.id,
            }
        });
        
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Crm');
        
        // put lead data into model
        var lead = this.lead; //new Tine.Crm.Model.Lead(_lead);
        Tine.Crm.LeadEditDialog.lead = lead;
        
        Tine.Crm.Model.Lead.FixDates(lead);  
        
        this.initActions(lead);
        
        //console.log(lead.data);
        //console.log(lead.data.tasks);
        //console.log(lead.data.responsible);
    	
        /*********** INIT STORES *******************/
        
        var relations = Tine.Crm.splitRelations(lead.data.relations);
        this.loadContactsStore(relations.contacts);        
        this.loadTasksStore(relations.tasks);
        this.loadProductsStore(lead.data.products);
                
        /*********** the EDIT dialog ************/
        
        var addNoteButton = new Tine.widgets.activities.ActivitiesAddButton({});  
        
        this.tbarItems = [
            this.actions.exportLead,
            addNoteButton
        ];

        this.items = Tine.Crm.LeadEditDialog.getEditForm({
            contactsPanel: this.getLinksGrid('Contacts', this.translation._('Contacts')),
            tasksPanel: this.getLinksGrid('Tasks', this.translation._('Tasks')),
            productsPanel: this.getLinksGrid('Products', this.translation._('Products'))
        }, lead.data);
        
        // add context menu events
        this.setLinksContextMenu('Contacts');
        this.setLinksContextMenu('Tasks');
        this.setLinksContextMenu('Products');
        
        // fix to have the tab panel in the right height accross browsers
        Ext.getCmp('editMainTabPanel').on('afterlayout', function(container) {
            var height = this.getInnerHeight();
            //var height = Ext.getCmp('leadDialog').getInnerHeight();
            Ext.getCmp('editMainTabPanel').setHeight(660);
        });
        
        Tine.Crm.LeadEditDialog.superclass.initComponent.call(this);
    },
    
    /**
     * @private
     */
    onRender: function(ct, position) {
        Tine.Crm.LeadEditDialog.superclass.onRender.call(this, ct, position);
        Ext.MessageBox.wait(this.translation._('Loading Lead...'), _('Please Wait'));
    },
    
    /**
     * @private
     */
    onRecordLoad: function(response) {
        this.getForm().findField('lead_name').focus(false, 250);
        var recordData = Ext.util.JSON.decode(response.responseText);
        this.updateRecord(recordData);
        
        this.updateToolbars.defer(10, this, [this.lead, 'container']);
        Tine.widgets.ActionUpdater(this.lead, [
            this.actions.addResponsible,
            this.actions.addCustomer,
            this.actions.addPartner,
            this.actions.addContact,
            this.actions.linkContact,
            this.actions.addTask,
            this.actions.linkTask,
            this.actions.exportLead
        ], 'container');
        
        if (! this.lead.id) {
            window.document.title = this.translation.gettext('Add New Lead');
        } else {
            window.document.title = sprintf(this.translation._('Edit Lead "%s"'), this.lead.get('lead_name'));
        }
        
        this.getForm().loadRecord(this.lead);
        Ext.MessageBox.hide();
    },
    
    updateRecord: function(recordData) {
        this.lead = new Tine.Crm.Model.Lead(recordData, recordData.id ? recordData.id : 0);
        Tine.Crm.Model.Lead.FixDates(this.lead);
    }
}); // end of application CRM LEAD EDIT DIALOG

/**
 * Leads Edit Popup
 */
Tine.Crm.LeadEditDialog.openWindow = function (config) {
    config.lead = config.lead ? config.lead : new Tine.Crm.Model.Lead({}, 0);
    //var window = new Ext.ux.PopupWindowMgr.fly({
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 750,
        name: Tine.Crm.LeadEditDialog.prototype.windowNamePrefix + config.lead.id,
        layout: Tine.Crm.LeadEditDialog.prototype.windowLayout,
        itemsConstructor: 'Tine.Crm.LeadEditDialog',
        itemsConstructorConfig: config
    });
    return window;
};

/*************************************** CRM MODELS *********************************/

Ext.namespace('Tine.Crm.Model');

// lead
Tine.Crm.Model.Lead = Ext.data.Record.create([
    {name: 'id',            type: 'int'},
    {name: 'lead_name',     type: 'string'},
    {name: 'leadstate_id',  type: 'int'},
    {name: 'leadtype_id',   type: 'int'},
    {name: 'leadsource_id', type: 'int'},
    {name: 'container'                 },
    {name: 'start',         type: 'date', dateFormat: 'c'},
    {name: 'description',   type: 'string'},
    {name: 'end',           type: 'date', dateFormat: 'c'},
    {name: 'turnover',      type: 'int'},
    {name: 'probability',   type: 'int'},
    {name: 'end_scheduled', type: 'date', dateFormat: 'c'},
    {name: 'lastread'},
    {name: 'lastreader'},
    {name: 'responsible'},
    {name: 'customer'},
    {name: 'partner'},
    {name: 'tasks'},
    {name: 'relations'},
    {name: 'products'},
    {name: 'tags'},
    {name: 'notes'},
    {name: 'creation_time',      type: 'date', dateFormat: 'c'},
    {name: 'created_by',         type: 'int'                  },
    {name: 'last_modified_time', type: 'date', dateFormat: 'c'},
    {name: 'last_modified_by',   type: 'int'                  },
    {name: 'is_deleted',         type: 'boolean'              },
    {name: 'deleted_time',       type: 'date', dateFormat: 'c'},
    {name: 'deleted_by',         type: 'int'                  }
]);

// product link
Tine.Crm.Model.ProductLink = Ext.data.Record.create([
    {name: 'id'},
    {name: 'product_id'},
    {name: 'product_desc'},
    {name: 'product_price'}
]);


// work arround nasty ext date bug
// @todo is that still needed?
Tine.Crm.Model.Lead.FixDates = function(lead) {
    lead.data.start         = lead.data.start         ? Date.parseDate(lead.data.start, 'c')         : lead.data.start;
    lead.data.end           = lead.data.end           ? Date.parseDate(lead.data.end, 'c')           : lead.data.end;
    lead.data.end_scheduled = lead.data.end_scheduled ? Date.parseDate(lead.data.end_scheduled, 'c') : lead.data.end_scheduled;
};
        
