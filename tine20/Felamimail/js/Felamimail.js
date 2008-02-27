Ext.namespace('Tine.Felamimail');

Tine.Felamimail = function() {
    
    var _getFolderPanel = function() 
    {
        var treeLoader = new Ext.tree.TreeLoader({
            dataUrl:'index.php',
            baseParams: {
                jsonKey: Tine.Tinebase.Registry.get('jsonKey'),
                method: 'Felamimail.getSubTree',
                location: 'mainTree'
            }
        });
        treeLoader.on("beforeload", function(_loader, _node) {
            _loader.baseParams.accountId    = _node.attributes.accountId;
            _loader.baseParams.folderName   = _node.attributes.folderName;
        }, this);
    
        var treePanel = new Ext.tree.TreePanel({
            title: 'Email',
            id: 'felamimail-tree',
            iconCls: 'FelamimailTreePanel',
            loader: treeLoader,
            rootVisible: false,
            border: false
        });
        
        // set the root node
        var treeRoot = new Ext.tree.TreeNode({
            text: 'root',
            draggable:false,
            allowDrop:false,
            id:'root'
        });
        treePanel.setRootNode(treeRoot);

        for(var i=0; i<Tine.Felamimail.initialTree.length; i++) {
            treeRoot.appendChild(new Ext.tree.AsyncTreeNode(Tine.Felamimail.initialTree[i]));
        }
        
        treePanel.on('click', function(_node, _event) {
            Tine.Felamimail.Email.show(_node);
        }, this);

        treePanel.on('beforeexpand', function(_panel) {
            if(_panel.getSelectionModel().getSelectedNode() === null) {
                _panel.expandPath('/root');
                _panel.selectPath('/root/account1');
            }
            _panel.fireEvent('click', _panel.getSelectionModel().getSelectedNode());
        }, this);

        treePanel.on('contextmenu', function(_node, _event) {
            _event.stopEvent();
            //_node.select();
            //_node.getOwnerTree().fireEvent('click', _node);
            //console.log(_node.attributes.contextMenuClass);
            /* switch(_node.attributes.contextMenuClass) {
                case 'ctxMenuContactsTree':
                    ctxMenuContactsTree.showAt(_event.getXY());
                    break;
            } */
        });

        return treePanel;
    };


    // public stuff
    return {
        getPanel: _getFolderPanel
    };
    
}(); // end of application


/**
 * the class which handles the email part
 */
Tine.Felamimail.Email = function() {

    /**
     * onclick handler for edit action
     */
    var _deleteHandler = function(_button, _event) {
        Ext.MessageBox.confirm('Confirm', 'Do you really want to delete the selected access log entries?', function(_button) {
            if(_button == 'yes') {
                var logIds = new Array();
                var selectedRows = Ext.getCmp('gridAdminAccessLog').getSelectionModel().getSelections();
                for (var i = 0; i < selectedRows.length; ++i) {
                    logIds.push(selectedRows[i].id);
                }
                
                Ext.data.Connection().request( {
                    url : 'index.php',
                    method : 'post',
                    scope : this,
                    params : {
                        method : 'Admin.deleteAccessLogEntries',
                        logIds : Ext.util.JSON.encode(logIds)
                    },
                    callback : function(_options, _success, _response) {
                        if(_success === true) {
                            var result = Ext.util.JSON.decode(_response.responseText);
                            if(result.success === true) {
                                Ext.getCmp('gridAdminAccessLog').getStore().reload();
                            }
                        }
                    }
                });
            }
        });
    };

    var _flagHandler = function(_button, _event) {
        var messageIds = new Array();
        var selectedRows = Ext.getCmp('gridFelamimail').getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; ++i) {
            messageIds.push(selectedRows[i].id);
        }
        
        Ext.data.Connection().request( {
            url : 'index.php',
            method : 'post',
            scope : this,
            params : {
                method : 'Felamimail.flagMessages',
                accountId: '',
                folder: '',
                flag: 'Flagged',
                messageIds : Ext.util.JSON.encode(messageIds)
            },
            callback : function(_options, _success, _response) {
                if(_success === true) {
                    var result = Ext.util.JSON.decode(_response.responseText);
                    if(result.success === true) {
                        Ext.getCmp('gridFelamimail').getStore().reload();
                    }
                }
            }
        });
    };

    var _selectAllHandler = function(_button, _event) {
        Ext.getCmp('gridAdminAccessLog').getSelectionModel().selectAll();
    };
    
    var _action_new = new Ext.Action({
        text: 'new email',
        handler: _deleteHandler,
        iconCls: 'action_email_new'
    });

    var _action_delete = new Ext.Action({
        text: 'delete email',
        disabled: true,
        handler: _deleteHandler,
        iconCls: 'action_delete'
    });

    var _action_flag = new Ext.Action({
        text: 'flag mail',
        disabled: true,
        handler: _flagHandler,
        iconCls: 'action_email_flag'
    });

    var _action_reply = new Ext.Action({
        text: 'reply sender',
        disabled: true,
        handler: _deleteHandler,
        iconCls: 'action_email_reply'
    });

    var _action_replyAll = new Ext.Action({
        text: 'reply all',
        disabled: true,
        handler: _deleteHandler,
        iconCls: 'action_email_replyAll'
    });

    var _action_forward = new Ext.Action({
        text: 'forward message',
        disabled: true,
        handler: _deleteHandler,
        iconCls: 'action_email_forward'
    });

    var _action_previousMessage = new Ext.Action({
        text: 'previous',
        disabled: true,
        handler: _deleteHandler,
        iconCls: 'action_email_previuosMessage'
    });

    var _action_nextMessage = new Ext.Action({
        text: 'next',
        disabled: true,
        handler: _deleteHandler,
        iconCls: 'action_email_nextMessage'
    });

    var _action_selectAll = new Ext.Action({
        text: 'select all',
        handler: _selectAllHandler
    });

    var _contextMenuGrid = new Ext.menu.Menu({
        items: [
            _action_delete,
            _action_reply,
            _action_replyAll,
            _action_forward,
            '-',
            _action_selectAll 
        ]
    });

    var _createDataStore = function()
    {
        /**
         * the datastore for accesslog entries
         */
        var dataStore = new Ext.data.JsonStore({
            url: 'index.php',
            baseParams: {
                method:     'Felamimail.getEmailOverview',
                jsonKey: Tine.Tinebase.Registry.get('jsonKey')
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'uid',
            fields: [
                {name: 'uid', type: 'int'},
                {name: 'subject'},
                {name: 'from'},
                {name: 'to'},
                {name: 'sent'},
                {name: 'received'},
                {name: 'size', type: 'int'},
                {name: 'attachment'},
                {name: 'seen'},
                {name: 'answered'},
                {name: 'deleted'},
                {name: 'flagged'}
            ],
            // turn on remote sorting
            remoteSort: true
        });
        
        dataStore.setDefaultSort('uid', 'desc');

        dataStore.on('beforeload', function(_dataStore) {
            _dataStore.baseParams.filter = Ext.getCmp('quickSearchField').getRawValue();
        });        
        
        //dataStore.load({params:{start:0, limit:50}});
        
        return dataStore;
    };

    var _showToolbar = function()
    {
        var quickSearchField = new Ext.app.SearchField({
            id:        'quickSearchField',
            width:     200,
            emptyText: 'enter searchfilter'
        }); 
        quickSearchField.on('change', function() {
            Ext.getCmp('gridFelamimail').getStore().load({params:{start:0, limit:50}});
        });
        
        var toolbar = new Ext.Toolbar({
            id: 'toolbarFelamimail',
            split: false,
            height: 26,
            items: [
                _action_new,
                '-',
                _action_delete,
                _action_flag,
                '-',
                _action_reply,
                _action_replyAll,
                _action_forward,
                '-',
                _action_previousMessage,
                _action_nextMessage,
                '->',
                'Search:', ' ',
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
                quickSearchField
            ]
        });
        
        Tine.Tinebase.MainScreen.setActiveToolbar(toolbar);
    };
    
    var _renderDateTime = function(_data, _cell, _record, _rowIndex, _columnIndex, _store) {
    	var date = Date.parseDate(_data, 'c');
    	
    	return date.format('d.m.Y h:i:s');
    };

  	var _renderAddress = function(_data, _cell, _record, _rowIndex, _columnIndex, _store) {
        var emailAddress = _data[0][2] + '@' + _data[0][3];
        
        if(_data[0][0]) {
            _cell.attr = 'ext:qtip="' +  _data[0][0] + ' - ' + emailAddress + '"';
            return _data[0][0];
            return _data[0][0] + ' - ' + emailAddress + '';
        } else {
            _cell.attr = 'ext:qtip="' + emailAddress + '"';
            return emailAddress;
        }
    };


    /**
     * creates the address grid
     * 
     */
    var _showGrid = function() 
    {
        //_action_delete.setDisabled(true);
        
        var dataStore = _createDataStore();
        
        var pagingToolbar = new Ext.PagingToolbar({ // inline paging toolbar
            pageSize: 50,
            store: dataStore,
            displayInfo: true,
            displayMsg: 'Displaying messages {0} - {1} of {2}',
            emptyMsg: "No messages to display"
        }); 
        
        var columnModel = new Ext.grid.ColumnModel([
            {resizable: false, header: 'UID', id: 'uid', dataIndex: 'uid', width: 20, hidden: false},
            {resizable: false, header: 'Attachment', dataIndex: 'attachment', width: 20},
            {resizable: true, header: 'Subject', id: 'subject', dataIndex: 'subject'},
            {resizable: true, header: 'From', dataIndex: 'from', width: 200, renderer: _renderAddress},
            {resizable: true, header: 'To', dataIndex: 'to', width: 200, hidden: true},
            {resizable: true, header: 'Sent', dataIndex: 'sent', renderer: _renderDateTime},
            {resizable: true, header: 'Received', dataIndex: 'received', renderer: _renderDateTime},
            {resizable: true, header: 'Size', dataIndex: 'size', renderer:Ext.util.Format.fileSize }
        ]);
        
        columnModel.defaultSortable = true; // by default columns are sortable
        
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});
        
        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                // no row selected
                _action_delete.setDisabled(true);
                _action_flag.setDisabled(true);
                _action_forward.setDisabled(true);
                _action_nextMessage.setDisabled(true);
                _action_previousMessage.setDisabled(true);
                _action_reply.setDisabled(true);
                _action_replyAll.setDisabled(true);
            } else if(rowCount > 1) {
                // more than one row selected
                _action_delete.setDisabled(false);
                _action_flag.setDisabled(false);
                _action_forward.setDisabled(false);
                _action_nextMessage.setDisabled(true);
                _action_previousMessage.setDisabled(true);
                _action_reply.setDisabled(true);
                _action_replyAll.setDisabled(true);
            } else {
                // only one row selected
                _action_delete.setDisabled(false);
                _action_flag.setDisabled(false);
                _action_forward.setDisabled(false);
                _action_nextMessage.setDisabled(true);
                _action_previousMessage.setDisabled(true);
                _action_reply.setDisabled(false);
                _action_replyAll.setDisabled(false);                
            }
        });
        
        var gridPanel = new Ext.grid.GridPanel({
            id: 'gridFelamimail',
            store: dataStore,
            cm: columnModel,
            tbar: pagingToolbar,     
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            autoExpandColumn: 'subject',
            border: false
        });
        
        Tine.Tinebase.MainScreen.setActiveContentPanel(gridPanel);

        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
                _action_delete.setDisabled(false);
            }
            _contextMenuGrid.showAt(_eventObject.getXY());
        });
        
/*        gridPanel.on('rowdblclick', function(_gridPanel, _rowIndexPar, ePar) {
            var record = _gridPanel.getStore().getAt(_rowIndexPar);
        });*/
    };

    /**
     * update datastore with node values and load datastore
     */
    var _loadData = function(_node)
    {
        var dataStore = Ext.getCmp('gridFelamimail').getStore();
        
        // we set them directly, because this properties also need to be set when paging
        dataStore.baseParams.accountId    = _node.attributes.accountId;
        dataStore.baseParams.folderName   = _node.attributes.folderName;
        
        dataStore.load({
            params:{
                start:0, 
                limit:50 
            }
        });
    };
        
    // public functions and variables
    return {
        show: function(_node) {
            var currentToolbar = Tine.Tinebase.MainScreen.getActiveToolbar();

            if(currentToolbar === false || currentToolbar.id != 'toolbarFelamimail') {
                _showToolbar();
                _showGrid(_node);
            }
            _loadData(_node);
        }
    };
    
}();
