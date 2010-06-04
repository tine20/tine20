/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * TODO         split into two files (grid & edit dlg)
 * TODO         fix autoheight in edit dlg (use border layout?)
 */
 
Ext.ns('Tine.Admin.Tags');
Tine.Admin.Tags.Main = {
    
	//  references to created toolbar and grid panel
	tagsToolbar: null,
	gridPanel: null,
	
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
            Tine.Admin.Tags.EditDialog.openWindow({
                tag: null,
                listeners: {
                    scope: this,
                    'update': function(record) {
                        this.reload();
                    }
                }
            });
        },

        /**
         * onclick handler for editBtn
         */
        editTag: function(_button, _event) {
            var selectedRows = Ext.getCmp('AdminTagsGrid').getSelectionModel().getSelections();
            Tine.Admin.Tags.EditDialog.openWindow({ 
                tag: selectedRows[0],
                listeners: {
                    scope: this,
                    'update': function(record) {
                        this.reload();
                    }
                }
            });
        },
        
        /**
         * onclick handler for deleteBtn
         */
        deleteTag: function(_button, _event) {
            Ext.MessageBox.confirm(this.translation.gettext('Confirm'), this.translation.gettext('Do you really want to delete the selected tags?'), function(_button){
                if (_button == 'yes') {
                
                    var tagIds = new Array();
                    var selectedRows = Ext.getCmp('AdminTagsGrid').getSelectionModel().getSelections();
                    for (var i = 0; i < selectedRows.length; ++i) {
                        tagIds.push(selectedRows[i].id);
                    }
                    
                    tagIds = tagIds;
                    
                    Ext.Ajax.request({
                        url: 'index.php',
                        params: {
                            method: 'Admin.deleteTags',
                            tagIds: tagIds
                        },
                        text: this.translation.gettext('Deleting tag(s)...'),
                        success: function(_result, _request){
                            Ext.getCmp('AdminTagsGrid').getStore().reload();
                        }
                    });
                }
            }, this);
        }    
    },
    
    initComponent: function() {
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Admin');
        
        this.actions.addTag = new Ext.Action({
            text: this.translation.gettext('add tag'),
            handler: this.handlers.addTag,
            iconCls: 'action_tag',
            scope: this,
            disabled: !(Tine.Tinebase.common.hasRight('manage', 'Admin', 'shared_tags'))
        });
        
        this.actions.editTag = new Ext.Action({
            text: this.translation.gettext('edit tag'),
            disabled: true,
            handler: this.handlers.editTag,
            iconCls: 'action_edit',
            scope: this
        });
        
        this.actions.deleteTag = new Ext.Action({
            text: this.translation.gettext('delete tag'),
            disabled: true,
            handler: this.handlers.deleteTag,
            iconCls: 'action_delete',
            scope: this
        });

    },
    
    displayTagsToolbar: function()
    {
    	// if toolbar was allready created set active toolbar and return
    	if (this.tagsToolbar)
    	{
    		Tine.Tinebase.MainScreen.setActiveToolbar(this.tagsToolbar, true);
    		return;
    	}
    	
        var TagsQuickSearchField = new Ext.ux.SearchField({
            id: 'TagsQuickSearchField',
            width:240,
            emptyText: Tine.Tinebase.translation._hidden('enter searchfilter')
        }); 
        TagsQuickSearchField.on('change', function(){
            Ext.getCmp('AdminTagsGrid').getStore().load({
                params: {
                    start: 0,
                    limit: 50
                }
            });
        }, this);
        
        this.tagsToolbar = new Ext.Toolbar({
            id: 'AdminTagsToolbar',
            split: false,
            //height: 26,
            items: [{
				xtype: 'buttongroup',
				columns: 5,
				items: [
					Ext.apply(new Ext.Button(this.actions.addTag), {
						scale: 'medium',
						rowspan: 2,
						iconAlign: 'top'
					}), {xtype: 'tbspacer', width: 10},
					Ext.apply(new Ext.Button(this.actions.editTag), {
						scale: 'medium',
						rowspan: 2,
						iconAlign: 'top'
					}), {xtype: 'tbspacer', width: 10},
					Ext.apply(new Ext.Button(this.actions.deleteTag), {
						scale: 'medium',
						rowspan: 2,
						iconAlign: 'top'
					})
				]
			}, '->', 
                this.translation.gettext('Search:'), 
                ' ',
                TagsQuickSearchField
            ]
        });

        Tine.Tinebase.MainScreen.setActiveToolbar(this.tagsToolbar, true);
    },

    displayTagsGrid: function() 
    {
    	// if grid panel was allready created set active content panel and return
    	if (this.gridPanel)
    	{
    		Tine.Tinebase.MainScreen.setActiveContentPanel(this.gridPanel, true);
    		return;
    	}
    	
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

        dataStore.on('beforeload', function(_dataStore, _options) {
            _options = _options || {};
            _options.params = _options.params || {};
            _options.params.query = Ext.getCmp('TagsQuickSearchField').getValue();
        }, this);        
        
        // the paging toolbar
        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: 25,
            store: dataStore,
            displayInfo: true,
            displayMsg: this.translation.gettext('Displaying tags {0} - {1} of {2}'),
            emptyMsg: this.translation.gettext("No tags to display")
        }); 
        
        // the columnmodel
        var columnModel = new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                resizable: true
            },
            columns: [
                { id: 'color', header: this.translation.gettext('Color'), dataIndex: 'color', width: 25, renderer: function(color){return '<div style="width: 8px; height: 8px; background-color:' + color + '; border: 1px solid black;">&#160;</div>';} },
                { id: 'name', header: this.translation.gettext('Name'), dataIndex: 'name', width: 200 },
                { id: 'description', header: this.translation.gettext('Description'), dataIndex: 'description', width: 500}
            ]
        });
        
        // the rowselection model
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});

        rowSelectionModel.on('selectionchange', function(_selectionModel) {
            var rowCount = _selectionModel.getCount();

            if (Tine.Tinebase.common.hasRight('manage', 'Admin', 'shared_tags') ) {
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
            }
        }, this);
        
        // the gridpanel
        this.gridPanel = new Ext.grid.GridPanel({
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
                emptyText: this.translation.gettext('No tags to display')
            })            
        });
        
        this.gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
            }
            
            if (! this.contextMenu) {
	            this.contextMenu = new Ext.menu.Menu({
	                id: 'ctxMenuTags', 
	                items: [
	                    this.actions.editTag,
	                    this.actions.deleteTag,
	                    '-',
	                    this.actions.addTag 
	                ]
	            });
            }
            this.contextMenu.showAt(_eventObject.getXY());
        }, this);
        
        this.gridPanel.on('rowdblclick', function(_gridPar, _rowIndexPar, ePar) {
            var record = _gridPar.getStore().getAt(_rowIndexPar);
            Tine.Admin.Tags.EditDialog.openWindow({
                tag: record,
                listeners: {
                    scope: this,
                    'update': function(record) {
                        this.reload();
                    }
                }
            });
        }, this);

        // add the grid to the layout
        Tine.Tinebase.MainScreen.setActiveContentPanel(this.gridPanel, true);
    },
    
    /**
     * update datastore with node values and load datastore
     */
    loadData: function()
    {
        var dataStore = Ext.getCmp('AdminTagsGrid').getStore();
        dataStore.load({ params: { start:0, limit:50 } });
    },

    show: function() 
    {
        if (this.tagsToolbar === null || this.gridPanel) {
        	this.initComponent();
        }

		this.displayTagsToolbar();
        this.displayTagsGrid();

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

/**
 * @namespace   Tine.Admin.Tags
 * @class       Tine.Admin.Tags.EditDialog
 * @extends     Tine.widgets.dialog.EditRecord
 */
Tine.Admin.Tags.EditDialog = Ext.extend(Tine.widgets.dialog.EditRecord, {
    
    /**
     * var tag
     */
    tag: null,

    windowNamePrefix: 'AdminTagEditDialog_',
    id : 'tagDialog',
    layout: 'hfit',
    labelWidth: 120,
    labelAlign: 'top',
    
    handlerApplyChanges: function(_button, _event, _closeWindow) {
        var form = this.getForm();
        
        if(form.isValid()) {
            Ext.MessageBox.wait(this.translation.gettext('Please wait'), this.translation.gettext('Updating Tag'));
            
            var tag = this.tag;
            
            // fetch rights
            tag.data.rights = [];
            this.rightsStore.each(function(item){
                tag.data.rights.push(item.data);
            });
            
            // fetch contexts
            tag.data.contexts = [];
            var anycontext = true;
            var contextPanel = Ext.getCmp('adminSharedTagsContextPanel');
            contextPanel.getRootNode().eachChild(function(node){
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
                    tagData: tag.data
                },
                success: function(response) {
                    //if(this.window.opener.Tine.Admin.Tags) {
                    //    this.window.opener.Tine.Admin.Tags.Main.reload();
                    //}
                    this.fireEvent('update', Ext.util.JSON.encode(this.tag.data));
                    Ext.MessageBox.hide();
                    if(_closeWindow === true) {
                        this.window.close();
                    } else {
                        this.onRecordLoad(response);
                    }
                },
                failure: function ( result, request) { 
                    Ext.MessageBox.alert(this.translation.gettext('Failed'), this.translation.gettext('Could not save tag.')); 
                },
                scope: this 
            });
        } else {
            Ext.MessageBox.alert(this.translation.gettext('Errors'), this.translation.gettext('Please fix the errors noted.'));
        }
    },

    /**
     * function updateRecord
     */
    updateRecord: function(_tagData) {
        // if tagData is empty (=array), set to empty object because array wont work!
        if (_tagData.length === 0) {
            _tagData = {};
        }
        this.tag = new Tine.Tinebase.Model.Tag(_tagData, _tagData.id ? _tagData.id : 0);
        
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
        
        this.rightsStore.loadData({
            results:    _tagData.rights,
            totalcount: _tagData.rights.length
        });
        
        this.anyContext = !_tagData.contexts || _tagData.contexts.indexOf('any') > -1;
        this.createTreeNodes(_tagData.appList);
        this.getForm().loadRecord(this.tag);
    },

    /**
     * function updateToolbarButtons
     */
    updateToolbarButtons: function(_rights) {        
       /* if(_rights.editGrant === true) {
            Ext.getCmp('tagDialog').action_saveAndClose.enable();
            Ext.getCmp('tagDialog').action_applyChanges.enable();
        }
       */

    },
    
    createTreeNodes: function(appList) {
        // clear old childs
        var toRemove = [];
        this.rootNode.eachChild(function(node){
            toRemove.push(node);
        });
        
        for(var i=0, j=appList.length; i<j; i++){
            // don't duplicate tree nodes on 'apply changes'
            toRemove[i] ? toRemove[i].remove() : null;
            
            var appData = appList[i];
            if (appData.name == 'Tinebase') {
                continue;
            }
            // get translated app title
            var app = Tine.Tinebase.appMgr.get(appData.name)
            var appTitle = (app) ? app.getTitle() : appData.name;
            
            this.rootNode.appendChild(new Ext.tree.TreeNode({
                text: appTitle,
                id: appData.id,
                checked: this.anyContext || this.tag.get('contexts').indexOf(appData.id) > -1,
                leaf: true,
                icon: "s.gif"
            }));
        }
    },
    /**
     * function display
     */
    getFormContents: function() {

        this.rootNode = new Ext.tree.TreeNode({
            text: this.translation.gettext('Allowed Contexts'),
            expanded: true,
            draggable:false,
            allowDrop:false
        });
        var contextPanel = new Ext.tree.TreePanel({
            title: this.translation.gettext('Context'),
            id: 'adminSharedTagsContextPanel',
            rootVisible: true,
            border: false,
            root: this.rootNode
        });
        // sort nodes in context panel by text property
        new Ext.tree.TreeSorter(contextPanel, {
            folderSort: true,
            dir: "asc"
        });        
        
        this.rightsPanel = new Tine.widgets.account.PickerGridPanel({
            title: this.translation.gettext('Account Rights'),
            store: this.rightsStore,
            recordClass: Tine.Admin.Model.TagRight,
            hasAccountPrefix: true,
            selectType: 'both',
            selectTypeDefault: 'group',
            configColumns: [
                new Ext.ux.grid.CheckColumn({
                    header: this.translation.gettext('View'),
                    dataIndex: 'view_right',
                    width: 55
                }),
                new Ext.ux.grid.CheckColumn({
                    header: this.translation.gettext('Use'),
                    dataIndex: 'use_right',
                    width: 55
                })
            ]
        });

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
                            fieldLabel: this.translation.gettext('Tag Name'), 
                            name: 'name',
                            allowBlank: false,
                            maxLength: 40
                        }, {
                            columnWidth: .6,
                            name: 'description',
                            fieldLabel: this.translation.gettext('Description'),
                            anchor:'100%',
                            maxLength: 50
                        }, {
                            xtype: 'colorfield',
                            columnWidth: .1,
                            fieldLabel: this.translation.gettext('Color'),
                            name: 'color'
                            
                        }]        
                    ]
                },{
                    xtype: 'tabpanel',
                    height: 300,
                    activeTab: 0,
                    deferredRender: false,
                    defaults:{autoScroll:true},
                    border: true,
                    plain: true,                    
                    items: [
                        this.rightsPanel, 
                        contextPanel
                    ]
                }
            ]
        };
        
        return editTagDialog;
    },
    
    initComponent: function() {
        this.tag = this.tag ? this.tag : new Tine.Tinebase.Model.Tag({}, 0);
        
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Admin');
        
//        Ext.MessageBox.wait(this.translation._('Loading Tag...'), this.translation._('Please Wait'));
        Ext.Ajax.request({
            scope: this,
            success: this.onRecordLoad,
            params: {
                method: 'Admin.getTag',
                tagId: this.tag.id
            }
        });
        
        this.rightsStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'account_id',
            fields: Tine.Admin.Model.TagRight
        });
        
        this.items = this.getFormContents();
        Tine.Admin.Tags.EditDialog.superclass.initComponent.call(this);
    },
    
    onRender : function(ct, position){
        Tine.widgets.dialog.EditDialog.superclass.onRender.call(this, ct, position);
        
        // generalized keybord map for edit dlgs
        var map = new Ext.KeyMap(this.el, [
            {
                key: [10,13], // ctrl + return
                ctrl: true,
                fn: this.handlerApplyChanges.createDelegate(this, [true], true),
                scope: this
            }
        ]);

        this.loadMask = new Ext.LoadMask(ct, {msg: String.format(_('Transfering {0}...'), this.translation.gettext('Tag'))});
       	this.loadMask.show();
    },
    
    onRecordLoad: function(response) {
        this.getForm().findField('name').focus(false, 250);
        var recordData = Ext.util.JSON.decode(response.responseText);
        this.updateRecord(recordData);
        
        if (! this.tag.id) {
            window.document.title = this.translation.gettext('Add New Tag');
        } else {
            window.document.title = String.format(this.translation._('Edit Tag "{0}"'), this.tag.get('name'));
        }
        
//        Ext.MessageBox.hide();
        this.loadMask.hide();
    }    
    
});

/**
 * Admin Tag Edit Popup
 */
Tine.Admin.Tags.EditDialog.openWindow = function (config) {
    config.tag = config.tag ? config.tag : new Tine.Tinebase.Model.Tag({}, 0);
    var window = Tine.WindowFactory.getWindow({
        width: 650,
        height: 400,
        name: Tine.Admin.Tags.EditDialog.prototype.windowNamePrefix + config.tag.id,
        layout: Tine.Admin.Tags.EditDialog.prototype.windowLayout,
        contentPanelConstructor: 'Tine.Admin.Tags.EditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
