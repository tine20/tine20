/**
 * Tine 2.0
 * 
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Dialer.js 4159 2008-09-02 14:15:05Z p.schuele@metaways.de $
 *
 * @todo        enable myphone edit dialog again
 */
 
Ext.namespace('Tine.Phone');

/**************************** panel ****************************************/

/**
 * entry point, required by tinebase
 * creates and returnes app tree panel
 */
Tine.Phone.getPanel = function(){
	
    var translation = new Locale.Gettext();
    translation.textdomain('Phone');

    // @todo generalise this for panel & main
    var editPhoneSettingsAction = new Ext.Action({
        text: translation._('Edit phone settings'),
        iconCls: 'PhoneIconCls',
        handler: function() {
        	Ext.Msg.alert('Sorry, this function is disabled at the moment');
        	/*
            var popupWindow = Tine.Phone.MyPhoneEditDialog.openWindow({
                record: this.ctxNode.record,
                listeners: {
                    scope: this,
                    'update': function(record) {
                        this.store.load({});
                    }
                }
            });
            */
        	
        	//Tine.Tinebase.common.openWindow('myPhonesWindow', 'index.php?method=Phone.editMyPhone&phoneId=' + this.ctxNode.id, 700, 300);
        },
        scope: this
    });
    
    var contextMenu = new Ext.menu.Menu({
        items: [
            editPhoneSettingsAction
        ]    
    });
    
    /*********** tree panel *****************/

    var treePanel = new Ext.tree.TreePanel({
        title: translation.gettext('Phone'),
        id: 'phone-tree',
        iconCls: 'PhoneIconCls',
        rootVisible: true,
        border: false,
        collapsible: true
    });
    
    /*********** root node *****************/
    
    var treeRoot = new Ext.tree.TreeNode({
        text: translation._('Phones'),
        cls: 'treemain',
        allowDrag: false,
        allowDrop: true,
        id: 'root',
        icon: false
    });
    treePanel.setRootNode(treeRoot);
    
    Tine.Phone.loadPhoneStore();           
        
    /******** tree panel handlers ***********/

    treePanel.on('click', function(node, event){
        Tine.Phone.Main.show(node);
    }, this);

    treePanel.on('contextmenu', function(node, event){
        this.ctxNode = node;
        if (node.id != 'root') {
            contextMenu.showAt(event.getXY());
        }
    }, this);
        
    treePanel.on('beforeexpand', function(panel) {    	
    	// expand root (Phones) node
        if(panel.getSelectionModel().getSelectedNode() === null) {
        	var node = panel.getRootNode();
        	node.select();
        	node.expand();
        } else {
        	panel.getSelectionModel().fireEvent('selectionchange', panel.getSelectionModel());
        }
    }, this);

    treePanel.getSelectionModel().on('selectionchange', function(_selectionModel) {
    	var node = _selectionModel.getSelectedNode();

        // TODO reactivate that
        // update toolbar
        /*
        var settingsButton = Ext.getCmp('phone-settings-button');
        if (settingsButton) {
            if(node && node.id != 'root') {
            	settingsButton.setDisabled(false);                     
            } else {
                settingsButton.setDisabled(true);
            }
        }
        */

        //node.getOwnerTree().selectPath(node.getPath());
        Tine.Phone.Main.show(node);
    }, this);
    
    return treePanel;
};

/**
 * load phones
 */
Tine.Phone.updatePhoneTree = function(store){
	
    var translation = new Locale.Gettext();
    translation.textdomain('Phone');

    // get tree root
    var treeRoot = Ext.getCmp('phone-tree').getRootNode();    

	// remove all children first
    treeRoot.eachChild(function(child){
    	treeRoot.removeChild(child);
    });
	
    // add phones to tree menu
    store.each(function(record){
        var label = (record.data.description == '') 
           ? record.data.macaddress 
           : Ext.util.Format.ellipsis(record.data.description, 30);
        var node = new Ext.tree.TreeNode({
            id: record.id,
            record: record,
            text: label,
            qtip: record.data.description,
            leaf: true
        });
        treeRoot.appendChild(node);
    });    
};

/**************************** dialer form / function *******************************/
/**
 * dial function
 * - opens the dialer window if multiple phones/lines are available
 * - directly calls dial json function if number is set and just 1 phone and line are available
 * 
 * @param string phone number to dial
 * 
 * @todo use window factory later
 */
Tine.Phone.dialPhoneNumber = function(number) {
	
	var phonesStore = Tine.Phone.loadPhoneStore();
	var lines = phonesStore.getAt(0).data.lines;
    
    // check if only one phone / one line exists and numer is set
	if (phonesStore.getTotalCount() == 1 && lines.length == 1 && number) {
		// call Phone.dialNumber
        Ext.Ajax.request({
            url: 'index.php',
            params: {
                method: 'Phone.dialNumber',
                number: number,
                phoneId: phonesStore.getAt(0).id,
                lineId: lines[0].id 
            },
            success: function(_result, _request){
                // success
            },
            failure: function(result, request){
                // show error message?
            }
        });                

    } else {	

    	// open dialer box (with phone and lines selection)
        var dialerPanel = new Tine.Phone.DialerPanel({
            number: (number) ? number : null
        });           
        var dialer = new Ext.Window({
            //title: this.translation._('Dial phone number'),
            title: 'Dial phone number',
            id: 'dialerWindow',
            modal: true,
            width: 400,
            height: 150,
            layout: 'hfit',
            plain:true,
            bodyStyle:'padding:5px;',
            closeAction: 'close',
            items: [dialerPanel]        
        });
    
        dialer.show();          	
	}
};

/**
 * dialer form
 * 
 * @todo use macaddress or description/name as display value?
 */
Tine.Phone.DialerPanel = Ext.extend(Ext.form.FormPanel, {
	
	id: 'dialerPanel',
	translation: null,
	
	// initial phone number
	number: null,
	
	// config settings
    defaults: {
        xtype: 'textfield',
        anchor: '100%',
        allowBlank: false
    },	
	bodyStyle: 'padding:5px;',	
	buttonAlign: 'right',
	    
	phoneStore: null,
	linesStore: null,
	
    // private
    initComponent: function(){
        
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Phone');    
        
        // set stores
        this.phoneStore = Tine.Phone.loadPhoneStore();
        
        //this.setLineStore(this.phoneStore.getAt(0).id);
        this.setLineStore(null);
        
        /***************** form fields *****************/
        
        this.items = [new Tine.widgets.customfields.CustomfieldsCombo({
                fieldLabel: this.translation._('Phone'),
                store: this.phoneStore,
                mode: 'local',
                editable: false,
                stateful: true,
   //             stateId: 'lastPhoneLine',
                stateEvents: ['select'],
                displayField:'description',
                valueField: 'id',
                id: 'phoneId',
                name: 'phoneId',
                triggerAction: 'all',
                listeners: {                
                	scope: this,
                	
                    // reload lines combo on change
                    select: function(combo, newValue, oldValue){
                        //console.log('set line store for ' + newValue.data.id);
                        this.setLineStore(newValue.data.id);
                    }
                }
            }),{
            	xtype: 'combo',
                fieldLabel: this.translation._('Line'),
                name: 'lineId',
                displayField:'linenumber',
                valueField: 'id',
                mode: 'local',
                store: this.linesStore,
                triggerAction: 'all',
                disabled: (this.linesStore.getCount() <= 1)
            },{
                fieldLabel: this.translation._('Number'),
                name: 'phoneNumber'
            }
        ];
        
        /******************* action buttons ********************/
        
        // cancel action
        this.cancelAction = new Ext.Action({   
            text: this.translation._('Cancel'),
            iconCls: 'action_cancel',
            handler : function(){
                Ext.getCmp('dialerWindow').close();
            }
        });
        	
        // dial action
		this.dialAction = new Ext.Action({
            scope: this,
            text: this.translation._('Dial'),
            iconCls: 'action_DialNumber',
            handler : function(){   
                var form = this.getForm();
                
                if (form.isValid()) {
                    Ext.Ajax.request({
                        url: 'index.php',
                        params: {
                            method: 'Phone.dialNumber',
                            number: form.findField('phoneNumber').getValue(),
                            phoneId: form.findField('phoneId').getValue(),
                            lineId: form.findField('lineId').getValue() 
                        },
                        success: function(_result, _request){
                            Ext.getCmp('dialerWindow').close();
                        },
                        failure: function(result, request){
                            // show error message?
                        }
                    });                
                }
            }
        });

        this.buttons = [
            this.cancelAction,
            this.dialAction
        ];
        
        /************** other initialisation ****************/
        
        this.initMyFields.defer(300, this);        

        Tine.Phone.DialerPanel.superclass.initComponent.call(this);        
    },
    
    /**
     * init form fields
     * 
     * @todo add prefered phone/line selections
     */
    initMyFields: function() {
    	// focus number field or set initial value
    	if (this.number != null) {
            this.getForm().findField('phoneNumber').setValue(this.number);
    	} else {
    		this.getForm().findField('phoneNumber').focus();
    	}

        // get combos
        var phoneCombo = this.getForm().findField('phoneId'); 
        var lineCombo = this.getForm().findField('lineId'); 
        
        // select first combo values
		if(! phoneCombo.getState()) {
            phoneCombo.setValue(this.phoneStore.getAt(0).id);
        } else {
            // update line store again (we need this, because it is changed when dlg is opened the second time)
            this.setLineStore(phoneCombo.getValue());
        }
        this.getForm().findField('lineId').setValue(this.linesStore.getAt(0).id);
    },
    
    /**
     * get values from phones store
     */
    setLineStore: function(phoneId) {
    	
    	if (this.linesStore == null) {
    	   this.linesStore = new Ext.data.Store({});
    	} else {
    		// empty store
    		this.linesStore.removeAll();
    	}
        
    	var form = this.getForm();
        
        if (phoneId == null) {
            if (form) {
                phoneId = form.findField('phoneId').getValue();
            } else {
                // get first phone
                phoneId = this.phoneStore.getAt(0).id;
            }
        }
    	
    	var phone = this.phoneStore.getById(phoneId);
    	for(var i=0; i<phone.data.lines.length; i++) {
    		var lineRecord = new Tine.Voipmanager.Model.SnomLine(phone.data.lines[i], phone.data.lines[i].id);
            this.linesStore.add(lineRecord);
    	}
    	
        // disable lineCombo if only 1 line available
    	if (form) {
            var lineCombo = form.findField('lineId'); 
            lineCombo.setDisabled((this.linesStore.getCount() <= 1));
            
            // set first line
            lineCombo.setValue(this.linesStore.getAt(0).id);
    	}         
    }
});

/**************************** main ****************************************/
/**
 * phone main view
 * 
 * @todo show phone calls
 */
Tine.Phone.Main = {
	/**
	 * translations object
	 */
	translation: null,
	
    /**
     * holds underlaying store
     */
    store: null,
	
    /**
     * @cfg {Object} paging defaults
     */
    paging: {
        start: 0,
        limit: 50,
        sort: 'start',
        dir: 'DESC'
    },
        
	/**
	 * action buttons
	 */
	actions: 
	{
	   	dialNumber: null,
	   	editPhoneSettings: null
	},
	
	/**
	 * init component function
	 */
	initComponent: function()
    {
    	this.translation = new Locale.Gettext();
        this.translation.textdomain('Phone');    
    	
        this.actions.dialNumber = new Ext.Action({
            text: this.translation._('Dial number'),
            tooltip: this.translation._('Initiate a new outgoing call'),
            handler: this.handlers.dialNumber,
            iconCls: 'action_DialNumber',
            scope: this
        });
    	
        // @todo generalise this for panel & main
        this.actions.editPhoneSettings = new Ext.Action({
            id: 'phone-settings-button',
            //text: translation._('Edit phone settings'),
        	text: this.translation._('Edit phone settings'),
            iconCls: 'PhoneIconCls',
            handler: function() {
                Ext.Msg.alert('Sorry, this function is disabled at the moment');
                /*
            	// get selected node id
            	var node = Ext.getCmp('phone-tree').getSelectionModel().getSelectedNode();
            	
                Tine.Tinebase.common.openWindow('myPhonesWindow', 'index.php?method=Phone.editMyPhone&phoneId=' + node.id, 700, 300);
                */
            },
            scope: this,
            disabled: true
        });
        
        this.initStore();
    },
    
    handlers: 
    {
    	dialNumber: function(_button, _event) {
    		var number = '';
    		var grid = Ext.getCmp('Phone_Callhistory_Grid');
    		if (grid) {
    		    record = grid.getSelectionModel().getSelected();
    		    if (record) {
    		        number = record.data.destination;
    		    }
    		}
    		
    		Tine.Phone.dialPhoneNumber(number);
    	}
    },
    
    renderer: {
    	direction: function(_data, _cell, _record, _rowIndex, _columnIndex, _store) {
    		var translation = new Locale.Gettext();
            translation.textdomain('Phone');
             
    		switch(_data) {
    			case 'in':
                    return "<img src='images/call-incoming.png' width='12' height='12' alt='contact' ext:qtip='" + translation._('Incoming call') + "'/>";
                    break;
                    
                case 'out':
                    return "<img src='images/call-outgoing.png' width='12' height='12' alt='contact' ext:qtip='" + translation._('Outgoing call') + "'/>";
                    break;
    		}
    	},
        destination: function(_data, _cell, _record, _rowIndex, _columnIndex, _store) {
            if (_data.toString().toLowerCase() == 'unknown') {
                var translation = new Locale.Gettext();
                translation.textdomain('Phone');
                _data = translation.gettext('unknown number');
            }
            return _data;
        }
    },
 
    displayToolbar: function()
    {
        var quickSearchField = new Ext.ux.SearchField({
            id: 'callhistoryQuickSearchField',
            width:240,
            emptyText: this.translation._('enter searchfilter')
        }); 
        quickSearchField.on('change', function(){
            this.store.load({});
        }, this);
        
        var toolbar = new Ext.Toolbar({
            id: 'Phone_Toolbar',
            split: false,
            height: 26,
            items: [
                this.actions.dialNumber, 
                this.actions.editPhoneSettings,
                '->', 
                this.translation._('Search:'), 
                ' ',
                quickSearchField
            ]
        });

        Tine.Tinebase.MainScreen.setActiveToolbar(toolbar);
    },
    
    /**
     * init the calls json grid store
     * 
     * @todo add more filters (phone, line, ...)
     * @todo use new filter toolbar later
     */
    initStore: function() {

        this.store = new Ext.data.JsonStore({
            id: 'id',
            //autoLoad: false,
            root: 'results',
            totalProperty: 'totalcount',
            fields: Tine.Phone.Model.Call,
            remoteSort: true,
            baseParams: {
                method: 'Phone.searchCalls'
            },
            sortInfo: {
                field: this.paging.sort,
                direction: this.paging.dir
            }
        });
        
        // register store
        Ext.StoreMgr.add('CallsGridStore', this.store);
        
        // prepare filter
        this.store.on('beforeload', function(store, options){
            if (!options.params) {
                options.params = {};
            }
            
            // paging toolbar only works with this properties in the options!
            options.params.sort  = store.getSortState() ? store.getSortState().field : this.paging.sort;
            options.params.dir   = store.getSortState() ? store.getSortState().direction : this.paging.dir;
            options.params.start = options.params.start ? options.params.start : this.paging.start;
            options.params.limit = options.params.limit ? options.params.limit : this.paging.limit;            
            options.params.paging = Ext.util.JSON.encode(options.params);
                        
            // add quicksearch and phone_id filter
            var quicksearchField = Ext.getCmp('callhistoryQuickSearchField');
            var node = Ext.getCmp('phone-tree').getSelectionModel().getSelectedNode() || null;            
            
            var filter = [{ 
            	   field: 'query',
            	   operator: 'contains',
            	   value: quicksearchField.getValue()
        	}];
	        
	        if (node !== null && node.id != 'root') {
	        	filter.push({ 
                    field: 'phone_id',
                    operator: 'equals',
                    value: node.id
                });
	        }
            
            options.params.filter = Ext.util.JSON.encode(filter);
            
        }, this);
    },
    
    /**
     * display the callhistory grid
     * 
     * @todo add context menu and row doubleclick
     * @todo add new filterToolbar
     */
    displayGrid: function() 
    {
        // the filter toolbar
    	/*
        var filterToolbar = new Tine.widgets.grid.FilterToolbar({
            id : 'callhistoryFilterToolbar',
            filterModels: [
                {label: this.translation._('Source or Destination'),    field: 'query',    operators: ['contains']}
             ],
             defaultFilter: 'query',
             filters: []
        });
        
        filterToolbar.on('filtertrigger', function() {
            this.store.load({});
        }, this);
        */
        
        // the paging toolbar
        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: 50,
            store: this.store,
            displayInfo: true,
            displayMsg: this.translation._('Displaying calls {0} - {1} of {2}'),
            emptyMsg: this.translation._("No calls to display")
        }); 
        
        // the columnmodel
        var columnModel = new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                resizable: true
            },
            columns: [
                { id: 'direction', header: this.translation._('Direction'), dataIndex: 'direction', width: 20, renderer: this.renderer.direction },
                { id: 'source', header: this.translation._('Source'), dataIndex: 'source', hidden: true },
                { id: 'callerid', header: this.translation._('Caller Id'), dataIndex: 'callerid' },
                { id: 'destination', header: this.translation._('Destination'), dataIndex: 'destination', renderer: this.renderer.destination },
                { id: 'start', header: this.translation._('Start'), dataIndex: 'start', renderer: Tine.Tinebase.common.dateTimeRenderer },
                { id: 'connected', header: this.translation._('Connected'), dataIndex: 'connected', renderer: Tine.Tinebase.common.dateTimeRenderer, hidden: true },
                { id: 'disconnected', header: this.translation._('Disconnected'), dataIndex: 'disconnected', renderer: Tine.Tinebase.common.dateTimeRenderer, hidden: true  },
                { id: 'duration', header: this.translation._('Duration'), dataIndex: 'duration', width: 40 },
                { id: 'ringing', header: this.translation._('Ringing'), dataIndex: 'ringing', width: 40, hidden: true },
                { id: 'id', header: this.translation._('Call ID'), dataIndex: 'id', hidden: true}
            ]
        });
        
        columnModel.defaultSortable = true; // by default columns are sortable
        
        // the rowselection model
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});
        
        // the gridpanel
        var gridPanel = new Ext.grid.GridPanel({
            id: 'Phone_Callhistory_Grid',
            store: this.store,
            cm: columnModel,
            tbar: pagingToolbar,     
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            autoExpandColumn: 'destination',
            border: false,
            view: new Ext.grid.GridView({
                autoFill: true,
                forceFit:true,
                ignoreAdd: true,
                emptyText: this.translation._('No calls to display')
            })            
            
        });
        
        rowSelectionModel.on('selectionchange', function(sm) {
            this.actions.dialNumber.setDisabled(sm.getCount() > 1);
            
            if (sm.getCount() == 1) {
                var record = sm.getSelected();
                var number = record ? record.get('destination') : false;
                this.actions.dialNumber.setDisabled(! Tine.Phone.utils.isCallable(number));
            }
        }, this);
        
        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
            }
            
            var contextMenu = new Ext.menu.Menu({
                id:'ctxMenuCall', 
                items: [
                    this.actions.dialNumber
                ]
            });
            contextMenu.showAt(_eventObject.getXY());
        }, this);
        
        gridPanel.on('rowdblclick', function(_gridPar, _rowIndexPar, ePar) {
            var record = _gridPar.getStore().getAt(_rowIndexPar);
            var number = record.data.destination;
            
            Tine.Phone.dialPhoneNumber(number);
        }, this);

        // add the grid to the layout
        Tine.Tinebase.MainScreen.setActiveContentPanel(gridPanel);
    },

    /**
     * update main toolbar
     * 
     * @todo what about the admin button?
     * @todo adopt to new application pattern (see addressbook, tasks, ...)
     */
    updateMainToolbar : function() 
    {
        var menu = Ext.menu.MenuMgr.get('Tinebase_System_AdminMenu');
        menu.removeAll();

        var adminButton = Ext.getCmp('tineMenu').items.get('Tinebase_System_AdminButton');
        adminButton.setIconClass('PhoneTreePanel');
        adminButton.setDisabled(true);
    },
    
	show: function(_node) 
	{	
        var currentToolbar = Tine.Tinebase.MainScreen.getActiveToolbar();

        if(currentToolbar === false || currentToolbar.id != 'Phone_Toolbar') {
            this.initComponent();
            this.displayToolbar();
            this.store.load({});
            this.displayGrid();
            this.updateMainToolbar();
        } else {
        	this.store.load({});
        }
	}
};

/**************************** store ****************************************/

/**
 * get user phones store
 *
 * @return Ext.data.JsonStore with phones
 */
Tine.Phone.loadPhoneStore = function(reload) {
	
    var store = Ext.StoreMgr.get('UserPhonesStore');
    
    if (!store) {
        // create store (get from initial data)
        store = new Ext.data.JsonStore({
            fields: Tine.Voipmanager.Model.SnomPhone,

            // initial data from http request
            data: Tine.Phone.registry.get('Phones'),
            autoLoad: true,
            id: 'id'
        });
        
        Ext.StoreMgr.add('UserPhonesStore', store);
        
        Tine.Phone.updatePhoneTree(store);
        
    } 
    
    /*else if (reload == true) {
    	
    	store.on('load', Tine.Phone.updatePhoneTree, this);
    	//store.load();
    }*/
    
    return store;
};

/**************************** models ****************************************/

Ext.namespace('Tine.Phone.Model');

/**
 * Model of a call
 */
Tine.Phone.Model.Call = Ext.data.Record.create([
    { name: 'id' },
    { name: 'line_id' },
    { name: 'phone_id' },
    { name: 'callerid' },
    { name: 'start', type: 'date', dateFormat: Date.patterns.ISO8601Long  },
    { name: 'connected', type: 'date', dateFormat: Date.patterns.ISO8601Long  },
    { name: 'disconnected', type: 'date', dateFormat: Date.patterns.ISO8601Long  },
    { name: 'duration' },
    { name: 'ringing' },
    { name: 'direction' },
    { name: 'source' },
    { name: 'destination' }
]);


/***************************** utils ****************************************/

Ext.namespace('Tine.Phone.utils');

/**
 * checks if given argument is syntactically a callable/valid number
 * 
 * @todo: synchrosize this with the asterisk rules
 */
Tine.Phone.utils.isCallable = function(number) {
    return ! number.toString().replace(/^\+|[ \-\/]/g, '').match(/[^0-9]/);
};