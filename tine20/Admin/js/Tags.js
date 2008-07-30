/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Admin.Tags');
Tine.Admin.Tags.Main = {
    
    actions: {
        addTag: null,
        editTag: null,
        deleteTag: null
    },
    
    handlers: {
        /**
         * onclick handler for addBtn
         */
        addTag: function(_button, _event) {
            Tine.Tinebase.Common.openWindow('tagWindow', "index.php?method=Admin.editTag&tagId=",650, 400);
        },

        /**
         * onclick handler for editBtn
         */
        editTag: function(_button, _event) {
            var selectedRows = Ext.getCmp('AdminTagsGrid').getSelectionModel().getSelections();
            var tagId = selectedRows[0].id;
            
            Tine.Tinebase.Common.openWindow('tagWindow', 'index.php?method=Admin.editTag&tagId=' + tagId,650, 400);
        },

        
        /**
         * onclick handler for deleteBtn
         */
        deleteTag: function(_button, _event) {
            Ext.MessageBox.confirm('Confirm', 'Do you really want to delete the selected tags?', function(_button){
                if (_button == 'yes') {
                
                    var tagIds = new Array();
                    var selectedRows = Ext.getCmp('AdminTagsGrid').getSelectionModel().getSelections();
                    for (var i = 0; i < selectedRows.length; ++i) {
                        tagIds.push(selectedRows[i].id);
                    }
                    
                    tagIds = Ext.util.JSON.encode(tagIds);
                    
                    Ext.Ajax.request({
                        url: 'index.php',
                        params: {
                            method: 'Admin.deleteTags',
                            tagIds: tagIds
                        },
                        text: 'Deleting tag(s)...',
                        success: function(_result, _request){
                            Ext.getCmp('AdminTagsGrid').getStore().reload();
                        }
                    });
                }
            });
        }    
    },
    
    initComponent: function()
    {
        this.actions.addTag = new Ext.Action({
            text: 'add tag',
            handler: this.handlers.addTag,
            iconCls: 'action_tag',
            scope: this
        });
        
        this.actions.editTag = new Ext.Action({
            text: 'edit tag',
            disabled: true,
            handler: this.handlers.editTag,
            iconCls: 'action_edit',
            scope: this
        });
        
        this.actions.deleteTag = new Ext.Action({
            text: 'delete tag',
            disabled: true,
            handler: this.handlers.deleteTag,
            iconCls: 'action_delete',
            scope: this
        });

    },
    
    displayTagsToolbar: function()
    {
        var quickSearchField = new Ext.ux.SearchField({
            id: 'quickSearchField',
            width:240,
            emptyText: 'enter searchfilter'
        }); 
        quickSearchField.on('change', function(){
            Ext.getCmp('AdminTagsGrid').getStore().load({
                params: {
                    start: 0,
                    limit: 50
                }
            });
        }, this);
        
        var tagsToolbar = new Ext.Toolbar({
            id: 'AdminTagsToolbar',
            split: false,
            height: 26,
            items: [
                this.actions.addTag, 
                this.actions.editTag,
                this.actions.deleteTag,
                '->', 
                'Search:', 
                ' ',
                quickSearchField
            ]
        });

        Tine.Tinebase.MainScreen.setActiveToolbar(tagsToolbar);
    },

    displayTagsGrid: function() 
    {
        // the datastore
        var dataStore = new Ext.data.JsonStore({
            baseParams: {
                method: 'Admin.getTags'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Tinebase.Model.Tag,
            // turn on remote sorting
            remoteSort: true
        });
        
        dataStore.setDefaultSort('name', 'asc');

        dataStore.on('beforeload', function(_dataStore) {
            _dataStore.baseParams.query = Ext.getCmp('quickSearchField').getRawValue();
        }, this);        
        
        // the paging toolbar
        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: 25,
            store: dataStore,
            displayInfo: true,
            displayMsg: 'Displaying tags {0} - {1} of {2}',
            emptyMsg: "No tags to display"
        }); 
        
        // the columnmodel
        var columnModel = new Ext.grid.ColumnModel([
            { resizable: true, id: 'color', header: 'color', dataIndex: 'color', width: 25, renderer: function(color){return '<div style="width: 8px; height: 8px; background-color:' + color + '; border: 1px solid black;">&#160;</div>';} },
            { resizable: true, id: 'name', header: 'Name', dataIndex: 'name', width: 200 },
            { resizable: true, id: 'description', header: 'Description', dataIndex: 'description', width: 500}
        ]);
        
        columnModel.defaultSortable = true; // by default columns are sortable
        
        // the rowselection model
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});

        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if(rowCount < 1) {
                // no row selected
                this.actions.deleteTag.setDisabled(true);
                this.actions.editTag.setDisabled(true);
            } else if(rowCount > 1) {
                // more than one row selected
                this.actions.deleteTag.setDisabled(false);
                this.actions.editTag.setDisabled(true);
            } else {
                // only one row selected
                this.actions.deleteTag.setDisabled(false);
                this.actions.editTag.setDisabled(false);
            }
        }, this);
        
        // the gridpanel
        var gridPanel = new Ext.grid.GridPanel({
            id: 'AdminTagsGrid',
            store: dataStore,
            cm: columnModel,
            tbar: pagingToolbar,     
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            enableColLock:false,
            loadMask: true,
            autoExpandColumn: 'description',
            border: false,
            view: new Ext.grid.GridView({
                autoFill: true,
                forceFit:true,
                ignoreAdd: true,
                emptyText: 'No tags to display'
            })            
            
        });
        
        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
            }
            var contextMenu = new Ext.menu.Menu({
                id:'ctxMenuTags', 
                items: [
                    this.actions.editTag,
                    this.actions.deleteTag,
                    '-',
                    this.actions.addTag 
                ]
            });
            contextMenu.showAt(_eventObject.getXY());
        }, this);
        
        gridPanel.on('rowdblclick', function(_gridPar, _rowIndexPar, ePar) {
            var record = _gridPar.getStore().getAt(_rowIndexPar);
            try {
                Tine.Tinebase.Common.openWindow('tagWindow', 'index.php?method=Admin.editTag&tagId=' + record.data.id,650, 400);
            } catch(e) {
                // alert(e);
            }
        }, this);

        // add the grid to the layout
        Tine.Tinebase.MainScreen.setActiveContentPanel(gridPanel);
    },
    
    /**
     * update datastore with node values and load datastore
     */
    loadData: function()
    {
        var dataStore = Ext.getCmp('AdminTagsGrid').getStore();
            
        dataStore.load({
            params:{
                start:0, 
                limit:50 
            }
        });
    },

    show: function() 
    {
        this.initComponent();
        
        var currentToolbar = Tine.Tinebase.MainScreen.getActiveToolbar();

        if(currentToolbar === false || currentToolbar.id != 'AdminTagsToolbar') {
            this.displayTagsToolbar();
            this.displayTagsGrid();
        }
        this.loadData();
    },
    
    reload: function() 
    {
        if(Ext.ComponentMgr.all.containsKey('AdminTagsGrid')) {
            setTimeout ("Ext.getCmp('AdminTagsGrid').getStore().reload()", 200);
        }
    }
};

/*********************************** EDIT DIALOG ********************************************/

Tine.Admin.Tags.EditDialog = {
    
    /**
     * var handlers
     */
     handlers: {
        applyChanges: function(_button, _event, _closeWindow) 
        {
            var form = Ext.getCmp('tagDialog').getForm();
            
            if(form.isValid()) {
                Ext.MessageBox.wait('Please wait', 'Updating Memberships');
                
                var tag = Tine.Admin.Tags.EditDialog.tagRecord;
                
                // fetch rights
                tag.data.rights = [];
                var rightsStore = Ext.StoreMgr.lookup('adminSharedTagsRights');
                rightsStore.each(function(item){
                    tag.data.rights.push(item.data);
                });
                
                // fetch contexts
                tag.data.contexts = [];
                var anycontext = true;
                var confinePanel = Ext.getCmp('adminSharedTagsConfinePanel');
                confinePanel.getRootNode().eachChild(function(node){
                    if (node.attributes.checked) {
                        tag.data.contexts.push(node.id);
                    } else {
                        anycontext = false;
                    }
                });
                if (anycontext) {
                    tag.data.contexts = ['any'];
                }
                
                form.updateRecord(tag);
                
                Ext.Ajax.request({
                    params: {
                        method: 'Admin.saveTag', 
                        tagData: Ext.util.JSON.encode(tag.data)
                    },
                    success: function(_result, _request) {
                        if(window.opener.Tine.Admin.Tags) {
                            window.opener.Tine.Admin.Tags.Main.reload();
                        }
                        if(_closeWindow === true) {
                            window.close();
                        } else {
                            this.updateTagRecord(Ext.util.JSON.decode(_result.responseText).updatedData);
                            form.loadRecord(this.tagRecord);
                            
                            Ext.MessageBox.hide();
                        }
                    },
                    failure: function ( result, request) { 
                        Ext.MessageBox.alert('Failed', 'Could not save tag.'); 
                    },
                    scope: this 
                });
                    
                
            } else {
                Ext.MessageBox.alert('Errors', 'Please fix the errors noted.');
            }
        },

        saveAndClose: function(_button, _event) 
        {
            this.handlers.applyChanges(_button, _event, true);
        },

        deleteTag: function(_button, _event) 
        {
            var tagIds = Ext.util.JSON.encode([Tine.Admin.Tags.EditDialog.tagRecord.data.id]);
                
            Ext.Ajax.request({
                url: 'index.php',
                params: {
                    method: 'Admin.deleteTags', 
                    tagIds: tagIds
                },
                text: 'Deleting tag...',
                success: function(_result, _request) {
                    if(window.opener.Tine.Admin.Tags) {
                        window.opener.Tine.Admin.Tags.Main.reload();
                    }
                    window.close();
                }/*,
                failure: function ( result, request) { 
                    Ext.MessageBox.alert('Failed', 'Some error occured while trying to delete the tag.'); 
                }*/
            });                           
        }
        
     },
     
    /**
     * var tagRecord
     */
    tagRecord: null,
    

    /**
     * function updateTagRecord
     */
    updateTagRecord: function(_tagData)
    {
        // if tagData is empty (=array), set to empty object because array wont work!
        if (_tagData.length === 0) {
            _tagData = {};
        }
        this.tagRecord = new Tine.Tinebase.Model.Tag(_tagData);
    },

    /**
     * function updateToolbarButtons
     */
    updateToolbarButtons: function(_rights)
    {        
       /* if(_rights.editGrant === true) {
            Ext.getCmp('tagDialog').action_saveAndClose.enable();
            Ext.getCmp('tagDialog').action_applyChanges.enable();
        }
       */

    },
    
    /**
     * function display
     * 
     * @param   _tagData
     * @param   _tagMembers
     * 
     */
    display: function(_tagData, _appList) 
    {

        /******* THE contexts box ********/
        var anyContext = !_tagData.contexts || _tagData.contexts.indexOf('any') > -1;
        
        var rootNode = new Ext.tree.TreeNode({
            text: 'Allowed Contexts',
            expanded: true,
            draggable:false,
            allowDrop:false
        });
        var confinePanel = new Ext.tree.TreePanel({
            id: 'adminSharedTagsConfinePanel',
            rootVisible: true,
            border: false,
            root: rootNode
        });

        for(var i=0, j=_appList.length; i<j; i++){
            var app = _appList[i];
            if (app.name == 'Tinebase' /*|| app.status == 'disabled'*/) {
                continue;
            }
            //console.log(app);
            
            rootNode.appendChild(new Ext.tree.TreeNode({
                text: app.name,
                id: app.id,
                checked: anyContext || _tagData.contexts.indexOf(app.id) > -1,
                leaf: true,
                icon: "s.gif"
            }));
        }
        
        /******* THE rights box ********/
        if (!_tagData.rights) {
            _tagData.rights = [{
                tag_id: '', //todo!
                account_name: 'Anyone',
                account_id: 0,
                account_type: 'anyone',
                view_right: true,
                use_right: true
            }];
        }
        var rightsStore = new Ext.data.JsonStore({
            storeId: 'adminSharedTagsRights',
            baseParams: {
                method: 'Admin.getTagRights',
                containerId: _tagData.id
            },
            root: 'results',
            totalProperty: 'totalcount',
            fields: [ 'account_name', 'account_id', 'account_type', 'view_right', 'use_right' ]
        });
        rightsStore.loadData({
            results:    _tagData.rights,
            totalcount: _tagData.rights.length
        });
        
        
        var rightsPanel = new Tine.widgets.account.ConfigGrid({
            //height: 300,
            accountPickerType: 'both',
            accountListTitle: 'Account Rights',
            configStore: rightsStore,
            hasAccountPrefix: true,
            configColumns: [
                new Ext.ux.grid.CheckColumn({
                    header: 'View',
                    dataIndex: 'view_right',
                    width: 55
                }),
                new Ext.ux.grid.CheckColumn({
                    header: 'Use',
                    dataIndex: 'use_right',
                    width: 55
                })
            ]
        });
        
        /******* THE edit dialog ********/

        /** quick hack for a color chooser **/
        var colorPicker = new Ext.form.ComboBox({
            listWidth: 150,
            readOnly:true,
            editable: false,
            name: 'color',
            fieldLabel: 'Color',
            columnWidth: .1
        });
        
        colorPicker.colorPalette = new Ext.ColorPalette({
            border: true
        });
        colorPicker.colorPalette.on('select', function(cp, color) {
            color = '#' + color;
            colorPicker.setValue(color);
            colorPicker.onTriggerClick();
        }, this);
        
        colorPicker.onTriggerClick = function() {
            if(this.disabled){
                return;
            }
            if(this.isExpanded()){
                this.collapse();
                this.el.focus();
            }else {
                colorPicker.initList();
                colorPicker.list.alignTo(colorPicker.wrap, colorPicker.listAlign);
                colorPicker.list.show();
                //if (typeof(colorPicker.colorPalette.render) == 'function') {
                    colorPicker.colorPalette.render(colorPicker.list);
                //}
            }
        };
        colorPicker.setValue = function(color) {
            colorPicker.el.setStyle('background', color);
            colorPicker.color = color;
        }
        colorPicker.getValue = function() {
            return colorPicker.color;
        }
        /** end of color chooser **/
        
        var editTagDialog = {
            layout:'hfit',
            border:false,
            width: 600,
            height: 350,
            items:[{
                    xtype: 'columnform',
                    border: false,
                    autoHeight: true,
                    items:[
                        [{
                            columnWidth: .3,
                            fieldLabel:'Tag Name', 
                            name:'name',
                            allowBlank: false
                        }, {
                            columnWidth: .6,
                            name: 'description',
                            fieldLabel: 'Description',
                            anchor:'100%'
                        },
                        colorPicker
                        ]        
                    ]
                },{
                    xtype: 'tabpanel',
                    //autoHeight: true,
                    height: 300,
                    activeTab: 0,
                    deferredRender: false,
                    defaults:{autoScroll:true},
                    border: true,
                    plain: true,                    
                    items: [{
                        title: 'Rights',
                        items: [rightsPanel]
                    },{
                        title: 'Context',
                        items: [confinePanel]
                    }]
                }
            ]
        };
        
        /******* build panel & viewport & form ********/
               
        // Ext.FormPanel
        var dialog = new Tine.widgets.dialog.EditRecord({
            id : 'tagDialog',
            layout: 'hfit',
            labelWidth: 120,
            labelAlign: 'top',
            handlerScope: this,
            handlerApplyChanges: this.handlers.applyChanges,
            handlerSaveAndClose: this.handlers.saveAndClose,
            handlerDelete: this.handlers.deleteTag,
            handlerExport: this.handlers.exportTag,
            items: editTagDialog
        });

        var viewport = new Ext.Viewport({
            layout: 'border',
            frame: true,
            items: dialog
        });

        this.updateTagRecord(_tagData);
        //this.updateToolbarButtons(_tagData.grants);       

        dialog.getForm().loadRecord(this.tagRecord);
        
    } // end display function     
    
};
