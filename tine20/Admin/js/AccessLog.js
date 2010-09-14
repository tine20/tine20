/**
 * Tine 2.0
 * 
 * @package     Admin
 * @subpackage  AccessLog
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philip Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: AccessLog.js 14798 2010-06-04 11:02:23Z airmike23@gmail.com $
 *
 * TODO         split this into two files (grid + edit dlg)
 * TODO         refactor this (don't use Ext.getCmp, etc.)
 */

Ext.ns('Tine.Admin.AccessLog');

Tine.Admin.AccessLog.Main = function() {

	// references to created toolbar and grid panel
	var toolbar = null;
	var gridPanel = null;
	
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
        
        //ds_accessLog.load({params:{start:0, limit:50}});
        
        return ds_accessLog;
    };

    var _showToolbar = function()
    {
    	// if toolbar was allready created set activate toolbar and return
    	if (toolbar) {
    		Tine.Tinebase.MainScreen.setActiveToolbar(toolbar, true);
    		return;
    	}
    	
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
        
        toolbar = new Ext.Toolbar({
            id: 'toolbarAdminAccessLog',
            split: false,
            //height: 26,
            items: [{
				xtype: 'buttongroup',
				columns: 1,
				items: [
					Ext.apply(new Ext.Button(_action_delete), {
						scale: 'medium',
						rowspan: 2,
						iconAlign: 'top'
					})
				]
			}, '->',
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
//				new Ext.ux.SelectBox({
//					listClass:'x-combo-list-small',
//                  	width:90,
//                  	value:'Starts with',
//                  	id:'search-type',
//                  	store: new Ext.data.SimpleStore({
//                    	fields: ['text'],
//                    	expandData: true,
//                    	data : ['Starts with', 'Ends with', 'Any match']
//                  	}),
//                  	displayField: 'text'
//                }),
                ' ',
                AccessLogQuickSearchField
            ]
        });
        
        Tine.Tinebase.MainScreen.setActiveToolbar(toolbar, true);
        
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
    	// if grid panel was allready created set active content panel and return
    	if (gridPanel)
    	{
    		Tine.Tinebase.MainScreen.setActiveContentPanel(gridPanel, true);
    		return;
    	}
    	
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
                { header: this.translation.gettext('Login Time'), id: 'li', dataIndex: 'li', width: 140, renderer: Tine.Tinebase.common.dateTimeRenderer},
                { header: this.translation.gettext('Logout Time'), id: 'lo', dataIndex: 'lo', width: 140, renderer: Tine.Tinebase.common.dateTimeRenderer},
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
        
        gridPanel = new Ext.grid.GridPanel({
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
        
        Tine.Tinebase.MainScreen.setActiveContentPanel(gridPanel, true);

        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
                _action_delete.setDisabled(false);
            }
            _contextMenuGridAdminAccessLog.showAt(_eventObject.getXY());
        });
        
//		gridPanel.on('rowdblclick', function(_gridPanel, _rowIndexPar, ePar) {
//        	var record = _gridPanel.getStore().getAt(_rowIndexPar);
//        });
    };
        
    // public functions and variables
    return {
        show: function() 
        {
            _showToolbar();
            _showGrid();   
            
            this.loadData();
        },
        
	    loadData: function()
	    {
	        var dataStore = Ext.getCmp('gridAdminAccessLog').getStore();
	        dataStore.load({ params: { start:0, limit:50 } });
	    },
	    
	    reload: function() 
		{
		    if(Ext.ComponentMgr.all.containsKey('gridAdminAccessLog')) {
		        setTimeout ("Ext.getCmp('gridAdminAccessLog').getStore().reload()", 200);
		    }
		},
        
        /**
        * @cfg {Object} paging defaults
        */
	    paging: {
	        start: 0,
	        limit: 50,
	        sort: 'li',
	        dir: 'DESC'
	    }
    };
    
}();