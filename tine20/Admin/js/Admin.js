/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: TagsPanel.js 2156 2008-04-25 09:42:05Z nelius_weiss $
 *
 * @todo        refactor this (split file, use new windows, ...)
 */
 
Ext.namespace('Tine.Admin');

Tine.Admin = function() {
	
	/**
	 * builds the admin applications tree
	 */
    var _getInitialTree = function(translation) { return [{
        text: translation.gettext('User'),
        cls: 'treemain',
        iconCls: 'admin-node-user',
        allowDrag: false,
        allowDrop: true,
        id: 'accounts',
        icon: false,
        children: [],
        leaf: null,
        expanded: true,
        dataPanelType: 'accounts',
        viewRight: 'accounts'
    },{
        text: translation.gettext('Groups'),
        cls: 'treemain',
        iconCls: 'admin-node-groups',
        allowDrag: false,
        allowDrop: true,
        id: 'groups',
        icon: false,
        children: [],
        leaf: null,
        expanded: true,
        dataPanelType: 'groups', 
        viewRight: 'accounts'
    },{
        text: translation.gettext('Computers'),
        cls: 'treemain',
        iconCls: 'admin-node-computers',
        allowDrag: false,
        allowDrop: true,
        id: 'computers',
        icon: false,
        children: [],
        leaf: null,
        expanded: true,
        dataPanelType: 'computers', 
        viewRight: 'computers'
    },{
        text: translation.gettext('Applications'),
		cls: "treemain",
        iconCls: 'admin-node-applications',
		allowDrag: false,
		allowDrop: true,
		id: "applications",
		icon: false,
		children: [],
		leaf: null,
		expanded: true,
		dataPanelType: "applications",
		viewRight: 'apps'
	},{
		text : translation.gettext('Access Log'),
		cls :"treemain",
        iconCls: 'admin-node-accesslog',
		allowDrag :false,
		allowDrop :true,
		id :"accesslog",
		icon :false,
		children :[],
		leaf :null,
		expanded :true,
		dataPanelType :"accesslog",
		viewRight: 'access_log'
	},{
        text : translation.gettext('Shared Tags'),
        cls :"treemain",
        iconCls: 'action_tag',
        allowDrag :false,
        allowDrop :true,
        id :"sharedtags",
        //icon :false,
        children :[],
        leaf :null,
        expanded :true,
        dataPanelType :"sharedtags",
        viewRight: 'shared_tags'
    },{
        text :translation.gettext('Roles'),
        cls :"treemain",
        iconCls: 'action_permissions',
        allowDrag :false,
        allowDrop :true,
        id :"roles",
        children :[],
        leaf :null,
        expanded :true,
        dataPanelType :"roles",
        viewRight: 'roles'
    }];};

	/**
     * creates the admin menu tree
     *
     */
    var _getAdminTree = function() 
    {
        var translation = new Locale.Gettext();
        translation.textdomain('Admin');
        
        var treeLoader = new Ext.tree.TreeLoader({
            dataUrl:'index.php',
            baseParams: {
                jsonKey: Tine.Tinebase.registry.get('jsonKey'),
                method: 'Admin.getSubTree',
                location: 'mainTree'
            }
        });
        treeLoader.on("beforeload", function(_loader, _node) {
            _loader.baseParams.node     = _node.id;
        }, this);
    
        var treePanel = new Ext.tree.TreePanel({
            title: translation.gettext('Admin'),
            id: 'admin-tree',
            iconCls: 'AdminIconCls',
            loader: treeLoader,
            rootVisible: false,
            border: false,
            autoScroll: true
        });
        
        // set the root node
        var treeRoot = new Ext.tree.TreeNode({
            text: 'root',
            draggable:false,
            allowDrop:false,
            id:'root'
        });
        treePanel.setRootNode(treeRoot);
        
        var initialTree = _getInitialTree(translation);
        
        for(var i=0; i<initialTree.length; i++) {
        	
        	var node = new Ext.tree.AsyncTreeNode(initialTree[i]);
        	
        	// check view right
        	if (initialTree[i].viewRight && !Tine.Tinebase.common.hasRight('view', 'Admin', initialTree[i].viewRight)) {
                node.disabled = true;
        	}
        	
            treeRoot.appendChild(node);
        }
        
        treePanel.on('click', function(_node, _event) {
        	
        	if (_node.disabled) {
        		return false;
        	}
        	
        	var currentToolbar = Tine.Tinebase.MainScreen.getActiveToolbar();

        	switch(_node.attributes.dataPanelType) {
                case 'accesslog':
                    if(currentToolbar !== false && currentToolbar.id == 'toolbarAdminAccessLog') {
                        Ext.getCmp('gridAdminAccessLog').getStore().load({params:{start:0, limit:50}});
                    } else {
                        Tine.Admin.AccessLog.Main.show();
                    }
                    
                    break;
                    
                case 'accounts':
                    if(currentToolbar !== false && currentToolbar.id == 'AdminUserToolbar') {
                        Ext.getCmp('AdminUserGrid').getStore().load({params:{start:0, limit:50}});
                    } else {
                        Tine.Admin.Users.Main.show();
                    }
                    
                    break;
                    
                case 'groups':
                    if(currentToolbar !== false && currentToolbar.id == 'AdminGroupsToolbar') {
                        Ext.getCmp('AdminGroupsGrid').getStore().load({params:{start:0, limit:50}});
                    } else {
                        Tine.Admin.Groups.Main.show();
                    }
                    
                    break;
                    
                case 'computers':
                    Tine.Admin.sambaMachine.show();
                    break;
                    
                case 'applications':
                    if(currentToolbar !== false && currentToolbar.id == 'toolbarAdminApplications') {
                    	Ext.getCmp('gridAdminApplications').getStore().load({params:{start:0, limit:50}});
                    } else {
                    	Tine.Admin.Applications.Main.show();
                    }
                    
                    break;
                    
                case 'sharedtags':
                    if(currentToolbar !== false && currentToolbar.id == 'AdminTagsToolbar') {
                        Ext.getCmp('AdminTagsGrid').getStore().load({params:{start:0, limit:50}});
                    } else {
                        Tine.Admin.Tags.Main.show();
                    }
                    
                    break;

                case 'roles':
                    if(currentToolbar !== false && currentToolbar.id == 'AdminRolesToolbar') {
                        Ext.getCmp('AdminRolesGrid').getStore().load({params:{start:0, limit:50}});
                    } else {
                        Tine.Admin.Roles.Main.show();
                    }
                    
                    break;
                    
            }
        }, this);

        treePanel.on('beforeexpand', function(_panel) {
            if(_panel.getSelectionModel().getSelectedNode() === null) {
                _panel.expandPath('/root');
                // don't open 'applications' if user has no right to manage apps
                if (Tine.Tinebase.common.hasRight('manage', 'Admin', 'accounts')) {
                    _panel.selectPath('/root/accounts');
                } else {
                    treeRoot.eachChild(function(node) {
                        if (Tine.Tinebase.common.hasRight('manage', 'Admin', node.attributes.viewRight)) {
                            _panel.selectPath('/root/' + node.id);
                            return;
                        }
                    }, this);
                }
            }
            _panel.fireEvent('click', _panel.getSelectionModel().getSelectedNode());
        }, this);

        treePanel.on('contextmenu', function(_node, _event) {
            _event.stopEvent();
            //_node.select();
            //_node.getOwnerTree().fireEvent('click', _node);
            /* switch(_node.attributes.contextMenuClass) {
                case 'ctxMenuContactsTree':
                    ctxMenuContactsTree.showAt(_event.getXY());
                    break;
            } */
        });

        return treePanel;
    };
    
    // public functions and variables
    return {
        getPanel: _getAdminTree
    };
    
}();

/*********************************** TINE ADMIN ACCESS LOG  *******************************/
/*********************************** TINE ADMIN ACCESS LOG  *******************************/

Ext.namespace('Tine.Admin.AccessLog');
Tine.Admin.AccessLog.Main = function() {

    /**
     * onclick handler for edit action
     */
    var _deleteHandler = function(_button, _event) {
    	Ext.MessageBox.confirm(this.translation.gettext('Confirm'), this.translation.gettext('Do you really want to delete the selected access log entries?'), function(_button) {
    		if(_button == 'yes') {
    			var logIds = new Array();
                var selectedRows = Ext.getCmp('gridAdminAccessLog').getSelectionModel().getSelections();
                for (var i = 0; i < selectedRows.length; ++i) {
                    logIds.push(selectedRows[i].data.id);
                }
                
                Ext.Ajax.request( {
                    params : {
                        method : 'Admin.deleteAccessLogEntries',
                        logIds : logIds
                    },
                    callback : function(_options, _success, _response) {
                        if(_success === true) {
                        	var result = Ext.util.JSON.decode(_response.responseText);
                        	if(result.status == 'success') {
                                Ext.getCmp('gridAdminAccessLog').getStore().reload();
                        	}
                        }
                    }
                });
    		}
    	});
    };

    var _selectAllHandler = function(_button, _event) {
    	Ext.getCmp('gridAdminAccessLog').getSelectionModel().selectAll();
    };

    var _action_delete = new Ext.Action({
        text: 'delete entry',
        disabled: true,
        handler: _deleteHandler,
        iconCls: 'action_delete',
        scope: this
    });

    var _action_selectAll = new Ext.Action({
        text: 'select all',
        handler: _selectAllHandler
    });

    var _contextMenuGridAdminAccessLog = new Ext.menu.Menu({
        items: [
            _action_delete,
            '-',
            _action_selectAll 
        ]
    });

    var _createDataStore = function()
    {
        /**
         * the datastore for accesslog entries
         */
        var ds_accessLog = new Ext.data.JsonStore({
            url: 'index.php',
            baseParams: {
                method: 'Admin.getAccessLogEntries'
            },
            root: 'results',
            totalProperty: 'totalcount',
            storeId: 'adminApplications_accesslogStore',
            fields: [
                {name: 'sessionid'},
                {name: 'login_name'},
                {name: 'accountObject'},
                {name: 'ip'},
                {name: 'li', type: 'date', dateFormat: Date.patterns.ISO8601Long},
                {name: 'lo', type: 'date', dateFormat: Date.patterns.ISO8601Long},
                {name: 'id'},
                {name: 'account_id'},
                {name: 'result'}
            ],
            // turn on remote sorting
            remoteSort: true
        });
        
        ds_accessLog.setDefaultSort('li', 'desc');

        ds_accessLog.on('beforeload', function(_dataSource, _options) {
            _options = _options || {};
            _options.params = _options.params || {};
            _options.params.filter = Ext.getCmp('AccessLogQuickSearchField').getValue();

        	// paging toolbar only works with this properties in the options!
        	var paging = {
                'sort'  : _dataSource.getSortState() ? _dataSource.getSortState().field : Tine.Admin.AccessLog.Main.paging.sort,
                'dir'   : _dataSource.getSortState() ? _dataSource.getSortState().direction : Tine.Admin.AccessLog.Main.paging.dir,
                'start' : _options.params.start ? _options.params.start : Tine.Admin.AccessLog.Main.paging.start,
                'limit' : _options.params.limit ? _options.params.limit : Tine.Admin.AccessLog.Main.paging.limit
        	};

            _options.params.paging = paging;
        	
			var from = Date.parseDate(Ext.getCmp('adminApplications_dateFrom').getRawValue(), Ext.getCmp('adminApplications_dateFrom').format);
			_options.params.from   = from.format("Y-m-d\\T00:00:00");

            var to = Date.parseDate(Ext.getCmp('adminApplications_dateTo').getRawValue(), Ext.getCmp('adminApplications_dateTo').format);
            _options.params.to     = to.format("Y-m-d\\T23:59:59");
        }, this);        
        
        ds_accessLog.load({params:{start:0, limit:50}});
        
        return ds_accessLog;
    };

    var _showToolbar = function()
    {
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Admin');
        
        _action_delete.setText(this.translation.gettext('delete entry'));
        _action_selectAll.setText(this.translation.gettext('select all'));
        
        var AccessLogQuickSearchField = new Ext.ux.SearchField({
            id:        'AccessLogQuickSearchField',
            width:     200,
            emptyText: Tine.Tinebase.translation._hidden('enter searchfilter')
        }); 
        AccessLogQuickSearchField.on('change', function() {
            Ext.getCmp('gridAdminAccessLog').getStore().load({params:{start:0, limit:50}});
        });
        
        var currentDate = new Date();
        var oneWeekAgo = new Date(currentDate.getTime() - 604800000);
        
        var dateFrom = new Ext.form.DateField({
            id:             'adminApplications_dateFrom',
            allowBlank:     false,
            validateOnBlur: false,
            format:         Locale.getTranslationData('Date', 'medium'),
            value:          oneWeekAgo
        });
        var dateTo = new Ext.form.DateField({
            id:             'adminApplications_dateTo',
            allowBlank:     false,
            validateOnBlur: false,
            format:         Locale.getTranslationData('Date', 'medium'),
            value:          currentDate
        });
        
        var toolbar = new Ext.Toolbar({
            id: 'toolbarAdminAccessLog',
            split: false,
            height: 26,
            items: [
                _action_delete,'->',
                this.translation.gettext('Display from:') + ' ',
                ' ',
                dateFrom,
                new Ext.Toolbar.Spacer(),
                this.translation.gettext('to:') + ' ',
                ' ',
                dateTo,
                new Ext.Toolbar.Spacer(),
                '-',
                this.translation.gettext('Search:'), ' ',
/*                new Ext.ux.SelectBox({
                  listClass:'x-combo-list-small',
                  width:90,
                  value:'Starts with',
                  id:'search-type',
                  store: new Ext.data.SimpleStore({
                    fields: ['text'],
                    expandData: true,
                    data : ['Starts with', 'Ends with', 'Any match']
                  }),
                  displayField: 'text'
                }), */
                ' ',
                AccessLogQuickSearchField
            ]
        });
        
        Tine.Tinebase.MainScreen.setActiveToolbar(toolbar);
        
        dateFrom.on('valid', function(_dateField) {
            var oldFrom = Ext.StoreMgr.get('adminApplications_accesslogStore').baseParams.from;
            
            var from = Date.parseDate(
               Ext.getCmp('adminApplications_dateFrom').getRawValue(),
               Ext.getCmp('adminApplications_dateFrom').format
            );

            var to = Date.parseDate(
               Ext.getCmp('adminApplications_dateTo').getRawValue(),
               Ext.getCmp('adminApplications_dateTo').format
            );
            
            if(from.getTime() > to.getTime()) {
            	Ext.getCmp('adminApplications_dateTo').setRawValue(Ext.getCmp('adminApplications_dateFrom').getRawValue());
            }
            
            if (oldFrom != from.format("Y-m-d\\T00:00:00")) {
                Ext.getCmp('gridAdminAccessLog').getStore().load({params:{start:0, limit:50}});
            }
        });
        
        dateTo.on('valid', function(_dateField) {
            var oldTo = Ext.StoreMgr.get('adminApplications_accesslogStore').baseParams.to;
            
            var from = Date.parseDate(
               Ext.getCmp('adminApplications_dateFrom').getRawValue(),
               Ext.getCmp('adminApplications_dateFrom').format
            );

            var to = Date.parseDate(
               Ext.getCmp('adminApplications_dateTo').getRawValue(),
               Ext.getCmp('adminApplications_dateTo').format
            );
            
            if(from.getTime() > to.getTime()) {
                Ext.getCmp('adminApplications_dateFrom').setRawValue(Ext.getCmp('adminApplications_dateTo').getRawValue());
            }
            
            if (oldTo != to.format("Y-m-d\\T23:59:59")) {
                Ext.getCmp('gridAdminAccessLog').getStore().load({params:{start:0, limit:50}});
            }
        });
    };
    
    var _renderResult = function(_value, _cellObject, _record, _rowIndex, _colIndex, _dataStore) {
        var translation = new Locale.Gettext();
        translation.textdomain('Admin');
        
        var gridValue;
        
        switch (_value) {
            case '-3' :
                gridValue = translation.gettext('invalid password');
                break;

            case '-2' :
                gridValue = translation.gettext('ambiguous username');
                break;

            case '-1' :
                gridValue = translation.gettext('user not found');
                break;

            case '0' :
                gridValue = translation.gettext('failure');
                break;

            case '1' :
                gridValue = translation.gettext('success');
                break;
        }
        
        return gridValue;
    };

    /**
     * creates the address grid
     * 
     */
    var _showGrid = function() 
    {
    	_action_delete.setDisabled(true);
    	
        var dataStore = _createDataStore();
        
        var pagingToolbar = new Ext.PagingToolbar({ // inline paging toolbar
            pageSize: 50,
            store: dataStore,
            displayInfo: true,
            displayMsg: this.translation.gettext('Displaying access log entries {0} - {1} of {2}'),
            emptyMsg: this.translation.gettext("No access log entries to display")
        }); 
        
        var columnModel = new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                resizable: true
            },
            columns: [
                { header: this.translation.gettext('Session ID'), id: 'sessionid', dataIndex: 'sessionid', width: 200, hidden: true},
                { header: this.translation.gettext('Login Name'), id: 'login_name', dataIndex: 'login_name'},
                { header: this.translation.gettext('Name'), id: 'accountObject', dataIndex: 'accountObject', width: 170, sortable: false, renderer: Tine.Tinebase.common.usernameRenderer},
                { header: this.translation.gettext('IP Address'), id: 'ip', dataIndex: 'ip', width: 150},
                { header: this.translation.gettext('Login Time'), id: 'li', dataIndex: 'li', width: 130, renderer: Tine.Tinebase.common.dateTimeRenderer},
                { header: this.translation.gettext('Logout Time'), id: 'lo', dataIndex: 'lo', width: 130, renderer: Tine.Tinebase.common.dateTimeRenderer},
                { header: this.translation.gettext('Account ID'), id: 'account_id', dataIndex: 'account_id', width: 70, hidden: true},
                { header: this.translation.gettext('Result'), id: 'result', dataIndex: 'result', width: 110, renderer: _renderResult}
            ]
        });
        
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});
        
        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                _action_delete.setDisabled(true);
            } else if (Tine.Tinebase.common.hasRight('manage', 'Admin', 'access_log')) {
                _action_delete.setDisabled(false);
            }
        });
        
        var gridPanel = new Ext.grid.GridPanel({
            id: 'gridAdminAccessLog',
            store: dataStore,
            cm: columnModel,
            tbar: pagingToolbar,     
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            autoExpandColumn: 'login_name',
            border: false
        });
        
        Tine.Tinebase.MainScreen.setActiveContentPanel(gridPanel);

        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
                _action_delete.setDisabled(false);
            }
            _contextMenuGridAdminAccessLog.showAt(_eventObject.getXY());
        });
        
/*        gridPanel.on('rowdblclick', function(_gridPanel, _rowIndexPar, ePar) {
            var record = _gridPanel.getStore().getAt(_rowIndexPar);
        });*/
    };
        
    // public functions and variables
    return {
        show: function() {
            _showToolbar();
            _showGrid();    
            this.updateMainToolbar();        
        },
        
        /**
        * @cfg {Object} paging defaults
        */
	    paging: {
	        start: 0,
	        limit: 50,
	        sort: 'li',
	        dir: 'DESC'
	    },
         
        // @deprecated
	    updateMainToolbar : function() 
	    {
	    }
    };
    
}();
