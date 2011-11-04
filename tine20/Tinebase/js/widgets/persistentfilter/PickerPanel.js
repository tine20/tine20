/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.persistentfilter');

/**
 * @namespace   Tine.widgets.persistentfilter
 * @class       Tine.widgets.persistentfilter.PickerPanel
 * @extends     Ext.tree.TreePanel
 * 
 * <p>PersistentFilter Picker Panel</p>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.widgets.persistentfilter.PickerPanel
 */
Tine.widgets.persistentfilter.PickerPanel = Ext.extend(Ext.tree.TreePanel, {
    
    /**
     * @cfg {application}
     */
    app: null,
    
    /**
     * @cfg {String} filterMountId
     * mount point of persistent filter folder (defaults to null -> root node)
     */
    filterMountId: null,
    
    /**
     * @private
     */
    autoScroll: true,
    border: false,
    rootVisible: false,
    
    /**
     * grid favorites panel belongs to
     * @type Tine.widgets.grid.GridPanel
     */
    grid: null,
    
    /**
     * @private
     */
    initComponent: function() {
        this.store = this.store || Tine.widgets.persistentfilter.store.getPersistentFilterStore();
        
        this.store.on('update', this.onStoreUpdate, this);
        this.store.on('remove', this.onStoreRemove, this);
        this.store.on('add', this.onStoreAdd, this);
        
        this.loader = new Tine.widgets.persistentfilter.PickerTreePanelLoader({
            app : this.app,
            store: this.store
        });
        
        this.filterNode = this.filterNode || new Ext.tree.AsyncTreeNode({
            text: _('My favorites'),
            id: '_persistentFilters',
            leaf: false,
            expanded: true
        });
        
        if (this.filterMountId === null) {
            this.root = this.filterNode;
        }
        
        Tine.widgets.persistentfilter.PickerPanel.superclass.initComponent.call(this);
        
        this.on('click', function(node) {
            if (node.attributes.isPersistentFilter) {
                this.getFilterToolbar().fireEvent('change', this.getFilterToolbar());
                node.select();
                this.onFilterSelect(this.store.getById(node.id));
            } else if (node.id == '_persistentFilters') {
                node.expand();
                return false;
            }
        }, this);
        
        this.on('contextmenu', this.onContextMenu, this);
    },
    
    /**
     * @private
     */
    afterRender: function() {
        Tine.widgets.persistentfilter.PickerPanel.superclass.afterRender.call(this);
        
        if (this.filterMountId !== null) {
            this.getNodeById(this.filterMountId).appendChild(this.filterNode);
        }
        
        // due to dependencies isues we need to wait after render
        this.getFilterToolbar().on('change', this.onFilterChange, this);
    },
    
    /**
     * need to reload filter if records in store were updated (only if the have the same app id)
     */
    onStoreUpdate: function(store, record)
    {
        this.checkReload(record);
    },

    /**
     * need to reload filter if records in store were removed (only if the have the same app id)
     */
    onStoreRemove: function(store, record)
    {
        this.checkReload(record);
    },

    /**
     * need to reload filter if records in store were added (only if the have the same app id)
     */
    onStoreAdd: function(store, records)
    {
        var reload = false;
        Ext.each(records, function(record) {
            reload = this.checkReload(record);
            if (reload) {
                return;
            }
        }, this);
    },
    
   
    /**
     * reload nodes if filters for this app have changed
     * 
     * @param {PersistentFilter} record
     */
    checkReload: function(record) {
        if (record.get('application_id') === this.app.id && this.filterNode && this.filterNode.rendered) {
            this.filterNode.reload(function(callback) {
            });
            return true;
        }
    },
    
    /**
     * load grid from saved filter
     * 
     * NOTE: As all filter plugins add their data on the stores beforeload event
     *       we need a litte hack to only present a filterid.
     *       
     *       When a filter is selected, we register ourselve as latest beforeload,
     *       remove all filter data and paste our filter data. To ensure we are
     *       always the last listener, we directly remove the listener afterwards
     */
    onFilterSelect: function(persistentFilter) {
        var store = this.getGrid().getStore();
        
        // NOTE: this can be removed when all instances of filterplugins are removed
        store.on('beforeload', this.storeOnBeforeload, this);
        store.load({
            persistentFilter: persistentFilter
        });
    },
    
    /**
     * storeOnBeforeload
     * 
     * @param {} store
     * @param {} options
     */
    storeOnBeforeload: function(store, options) {
        options.params.filter = options.persistentFilter.get('filters');
        store.un('beforeload', this.storeOnBeforeload, this);
    },
    
    /**
     * called on filtertrigger of filter toolbar
     */
    onFilterChange: function() {
        this.getSelectionModel().clearSelections();
    },
    
    /**
     * returns additional ctx items
     * 
     * @TODO: make this a hooking approach!
     * 
     * @param {model.PersistentFilter}
     * @return {Array}
     */
    getAdditionalCtxItems: function(filter) {
        var items = [];
        
        var as = Tine.Tinebase.appMgr.get('ActiveSync');
        if (as) {
            items = items.concat(as.getPersistentFilterPickerCtxItems(this, filter));
        }
                
        return items;
    },
    
    /**
     * returns filter toolbar of mainscreen center panel of app this picker panel belongs to
     */
    getFilterToolbar: function() {
        if (! this.filterToolbar) {
            this.filterToolbar = this.getGrid().filterToolbar;
        }
        
        return this.filterToolbar;
    },
    
    /**
     * get grid
     * 
     * @return {Tine.widgets.grid.GridPanel}
     */
    getGrid: function() {
        if (! this.grid) {
            this.grid = this.app.getMainScreen().getCenterPanel();
        }
        
        return this.grid;
    },
    
    /**
     * returns persistent filter tree node
     * 
     * @return {Ext.tree.AsyncTreeNode}
     */
    getPersistentFilterNode: function() {
        return this.filterNode;
    },
    
    /**
     * handler for ctxmenu clicks on tree nodes
     * 
     * @param {Ext.tree.TreeNode} node
     * @param {Ext.EventObject} e
     */
    onContextMenu: function(node, e) {
        if (! node.attributes.isPersistentFilter) {
            return;
        }
        
        var record = this.store.getById(node.id);
        
        
        var im = Tine.Tinebase.common.hasRight('manage_shared_favorites', 'Addressbook');
        var isHidden = ! ( ((record.isShared()) && (im)) || (!record.isShared()));
        var shareItemHidden = ((record.data.created_by === null) || (!im)) ? true : false;
        
        if (record.isShared()) var shareItemText = _('Remove from shared favorites');
        else var shareItemText = _('Add to shared favorites');
        
        var menu = new Ext.menu.Menu({
            items: [{
                text: _('Delete Favorite'),
                iconCls: 'action_delete',
                hidden: isHidden,
                handler: this.onDeletePersistentFilter.createDelegate(this, [node, e])
            }, {
                text: _('Rename Favorite'),
                iconCls: 'action_edit',
                hidden: isHidden,
                handler: this.onRenamePersistentFilter.createDelegate(this, [node, e])
            }, {
                text: _('Overwrite Favorite'),
                iconCls: 'action_saveFilter',
                hidden: isHidden,
                handler: this.onOverwritePersistentFilter.createDelegate(this, [node, e])
            }, {
                text: shareItemText,
                iconCls: 'action_toggleshared',
                hidden: shareItemHidden,
                handler: this.onToggleShared.createDelegate(this, [node, e])
            }].concat(this.getAdditionalCtxItems(record))
        });
        
        menu.showAt(e.getXY());
        
    },
    
    /**
     * handler to delete filter
     * 
     * @param {Ext.tree.TreeNode} node
     */
    onDeletePersistentFilter: function(node) {
        Ext.MessageBox.confirm(_('Confirm'), String.format(_('Do you really want to delete the favorite "{0}"?'), node.text), function(_btn) {
            if ( _btn == 'yes') {
                Ext.MessageBox.wait(_('Please wait'), String.format(_('Deleting Favorite "{0}"' ), this.containerName , node.text));
                
                var record = this.store.getById(node.id);
                Tine.widgets.persistentfilter.model.persistentFilterProxy.deleteRecords([record], {
                    scope: this,
                    success: function(){
                        this.store.remove(record);
                        Ext.MessageBox.hide();
                    }
                });
            }
        }, this);
    },
    
    /**
     * handler to rename filter
     * 
     * @param {Ext.tree.TreeNode} node
     */
    onRenamePersistentFilter: function(node) {
        Ext.MessageBox.prompt(_('New Name'), String.format(_('Please enter the new name for favorite "{0}"?'), node.text), function(_btn, _newName){
            if ( _btn == 'ok') {
                if (_newName.length > 40) {
                    Ext.Msg.alert(_('Favorite not Saved'), _('You have to supply a shorter name! Names of favorite can only be up to 40 characters long.'));
                    return;
                }
                Ext.MessageBox.wait(_('Please wait'), String.format(_('Renaming Favorite "{0}"'), node.text));
                
                var record = this.store.getById(node.id);
                record.set('name', _newName);
                Tine.widgets.persistentfilter.model.persistentFilterProxy.saveRecord(record, {
                    scope: this,
                    success: function(updatedRecord){
                        this.store.remove(record);
                        this.store.addSorted(updatedRecord);
                        Ext.MessageBox.hide();
                    }
                });
            }
        }, this, false, node.text);
    },

    /**
     * handler to overwrite filter
     * 
     * @param {Ext.tree.TreeNode} node
     */
    onOverwritePersistentFilter: function(node) {
        Ext.MessageBox.confirm(_('Overwrite?'), String.format(_('Do you want to overwrite the favorite "{0}"?'), node.text), function(_btn){
            if ( _btn == 'yes') {
                Ext.MessageBox.wait(_('Please wait'), String.format(_('Overwriting Favorite "{0}"'), node.text));
                this.createOrUpdateFavorite(node.text);
            }
        }, this, false, node.text);
    },

    /**
     * handler for toggling shared
     * 
     * @param {Ext.tree.TreeNode} node
     */
    onToggleShared: function(node) {

    	record = this.store.getById(node.id);
    	// Record was shared, now is not shared: setting owner = current user
    	if(record.data.account_id === null) {
    		record.data.account_id = Tine.Tinebase.registry.get('currentAccount').accountId;
    		var savingText = String.format(_('Setting favorite "{0}" not shared'), record.data.name);
    	} else {
    		var savingText = String.format(_('Setting favorite "{0}" shared'), record.data.name);
    		record.data.account_id = null;
    	}

    	Ext.MessageBox.wait(_('Please wait'), savingText);
    	
    	Tine.widgets.persistentfilter.model.persistentFilterProxy.saveRecord(record, {
            scope: this,
            success: function(savedRecord){
                Ext.Msg.hide();
            }
        });
    	
    },
    
    /**
     * save persistent filter
     */
    saveFilter: function() {
        var ftb = this.getFilterToolbar();
        
        // recheck that current ftb is saveable
        if (! ftb.isSaveAllowed()) {
            Ext.Msg.alert(_('Could not save Favorite'), _('Your current view does not support favorites'));
            return;
        }
        
        var name = '';
       
        var newWindow = Tine.WindowFactory.getExtWindow({
        	
            title : _('Save Favorite'),
            width : 200,
            height : 240,
            contentPanelConstructor : 'Tine.widgets.persistentfilter.EditPersistentFilter',
            contentPanelConstructorConfig : null
        });
        
        newWindow.addListener('update',function(){Tine.log.debug('HALLO')});
        
//        Tine.log.debug('WINDOW:', newWindow.contentPanelConstructor);
        
        
//        Ext.MessageBox.prompt(_('save filter'), _('Please enter a name for the favorite'), function(btn, value) {
//            if (btn == 'ok') {
//                if (! value) {
//                    Ext.Msg.alert(_('Favorite not Saved'), _('You have to supply a name for the favorite!'));
//                    return;
//                } else if (value.length > 40) {
//                    Ext.Msg.alert(_('Favorite not Saved'), _('You have to supply a shorter name! Names of favorite can only be up to 40 characters long.'));
//                    return;
//                }
//                Ext.Msg.wait(_('Please Wait'), _('Saving favorite'));
//                this.createOrUpdateFavorite(value, ftb);
//            }
//        }, this, false, name);
    },
    
    /**
     * create or update a favorite
     * @param {String} name
     * @param {FilterToolbar} ftb
     */
    createOrUpdateFavorite: function(name, ftb) {
        if (! ftb) {
            ftb = this.getFilterToolbar();
        }
        
        Tine.log.debug(name);
        
        var model = this.filterModel;
        if (! model) {
            var recordClass = this.recordClass || this.treePanel ? this.treePanel.recordClass : ftb.store.reader.recordType;
            model = recordClass.getMeta('appName') + '_Model_' + recordClass.getMeta('modelName') + 'Filter';
        }
        
        var record = new Tine.widgets.persistentfilter.model.PersistentFilter({
            application_id: this.app.id,
            account_id:     Tine.Tinebase.registry.get('currentAccount').accountId,
            model:          model,
            filters:        ftb.getAllFilterData(),
            name:           name
        });
        
        Tine.widgets.persistentfilter.model.persistentFilterProxy.saveRecord(record, {
            scope: this,
            success: function(savedRecord){
                var existing = this.store.getById(savedRecord.id); 
                if (existing) {
                    this.store.remove(existing);
                }
                
                this.store.addSorted(savedRecord);
                Ext.Msg.hide();
                
                // reload this filter?
                this.selectFilter(savedRecord);
            }
        });
    },
    
    /**
     * select given persistent filter
     * 
     * @param {model.PersistentFilter} persistentFilter
     */
    selectFilter: function(persistentFilter) {
        if (! persistentFilter) {
            return;
        }
        this.getFilterToolbar().fireEvent('change', this.getFilterToolbar());
        var node = this.getNodeById(persistentFilter.id);
        if (node) {
            this.getSelectionModel().select(node);
        } else {
            // mark for selection
            this.getLoader().selectedFilterId = persistentFilter.id;
        }
        
        this.onFilterSelect(persistentFilter);
    }
    
});

/**
 * @namespace   Tine.widgets.persistentfilter
 * @class       Tine.widgets.persistentfilter.EditPersistentFilter
 * @extends     Ext.FormPanel
 * 
 * <p>PersistentFilter Edit Dialog</p>
 * 
 * @author      Alexander Stintzing <c.weiss@metaways.de>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * 
 * @param       {Object} config
 */

Tine.widgets.persistentfilter.EditPersistentFilter = Ext.extend(Ext.FormPanel, {

    layout : 'fit',
    border : false,
    cls : 'tw-editdialog',  
    
    bodyStyle : 'padding:5px',
    labelAlign : 'top',

    anchor : '100% 100%',
    deferredRender : false,
    buttonAlign : null,
    bufferResize : 500, 
    
    // private
    initComponent : function() {
   	
        this.addEvents('cancel', 'save', 'close');
        // init actions
        
        this.initActions();
        // init buttons and tbar
        
        this.initButtons();
        // get items for this dialog
        this.items = this.getFormItems();

        Tine.widgets.persistentfilter.EditPersistentFilter.superclass.initComponent.call(this);
    },

    /**
     * init actions
     */
    initActions : function() {
        this.action_save = new Ext.Action({
            text : _('OK'),
            minWidth : 70,
            scope : this,
            handler : this.onSave,
            iconCls : 'action_saveAndClose'
        });
        this.action_cancel = new Ext.Action({
            text : _('Cancel'),
            minWidth : 70,
            scope : this,
            handler : this.onCancel,
            iconCls : 'action_cancel'
        });
    },

    initButtons : function() {

        this.fbar = [ '->', this.action_cancel, this.action_save ];

    },

    onRender : function(ct, position) {
        Tine.widgets.dialog.EditDialog.superclass.onRender.call(this, ct, position);

        // generalized keybord map for edit dlgs
        var map = new Ext.KeyMap(this.el, [ {
            key : [ 13 ],
            ctrl : false,
            fn : this.onSave,
            scope : this
        } ]);

    },

    onSave : function() {
    	var rec = {};

    	// Name of the favorite
    	if(this.inputTitle.getValue()) {
    	   if(this.inputTitle.getValue().length < 40) {
    	   	   rec.name = this.inputTitle.getValue();
    	   } else {
    	   	   Ext.Msg.alert(_('Favorite not Saved'), _('You have to supply a shorter name! Names of favorite can only be up to 40 characters long.'));
    	   	   this.onCancel();
    	   }
        } else {
        	Ext.Msg.alert(_('Favorite not Saved'), _('You have to supply a name for the favorite!'));
            this.onCancel();
        }

        // Description of the favorite
        if(this.inputDescription.getValue().length) {
        	rec.description = this.inputDescription.getValue();
        }
        
        rec.account_id = Tine.Tinebase.registry.get('currentAccount').accountId;
        
        // Favorite Checkbox
        if(Tine.Tinebase.common.hasRight('manage_shared_favorites', 'Addressbook')) {
            if(this.inputCheck.getValue()) {
            	rec.account_id = null;        	
            }
        }  
         
//        this.purgeListeners();
        this.window.close();
        this.window.fireEvent('update');
//        return rec;
    },
    
    onCancel : function() {
        this.fireEvent('cancel');
        this.purgeListeners();
        this.window.close();
    },

    getFormItems : function() {
        
        this.inputTitle = new Ext.form.TextField({
            allowBlank: false,
            fieldLabel: _('Title'),
            regex: /.{1,40}/i        
        });
        
        this.inputDescription = new Ext.form.TextField({
            allowBlank: false,
            fieldLabel: _('Description'),
            regex: /.{1,255}/i        
        });        
        
        this.inputCheck = new Ext.form.Checkbox({
            fieldLabel: _('Share this Favorite')
        });
        
        return {
            border : false,
            frame : true,
            layout : 'form',
            items : [ {
                region : 'center',
                layout : {
                    align: 'stretch',
                    type: 'vbox'
                }

            }, this.inputTitle, this.inputDescription, this.inputCheck ]
        };
    }

});

/**
 * @namespace   Tine.widgets.persistentfilter
 * @class       Tine.widgets.persistentfilter.PickerTreePanelLoader
 * @extends     Tine.widgets.tree.Loader
 * 
 * <p>PersistentFilter Picker Panel Tree Loader</p>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.widgets.persistentfilter.PickerTreePanelLoader
 */
Tine.widgets.persistentfilter.PickerTreePanelLoader = Ext.extend(Tine.widgets.tree.Loader, {
    
    /**
     * @cfg {Ext.data.Store} store
     */
    store: null,
    
    /**
     * @cfg {String} selectedFilterId id to autoselect
     */
    selectedFilterId: null,
    
    /**
     * 
     * @param {Ext.tree.TreeNode} node
     * @param {Function} callback Function to call after the node has been loaded. The
     * function is passed the TreeNode which was requested to be loaded.
     * @param (Object) scope The cope (this reference) in which the callback is executed.
     * defaults to the loaded TreeNode.
     */
    requestData : function(node, callback, scope) {
        if(this.fireEvent("beforeload", this, node, callback) !== false) {
            var recordCollection = this.store.query('application_id', this.app.id);
            
            node.beginUpdate();
            recordCollection.each(function(record) {
                var n = this.createNode(record.copy().data);
                if (n) {
                    node.appendChild(n);
                }
            }, this);
            node.endUpdate();
            
            this.runCallback(callback, scope || node, [node]);

        }  else {
            // if the load is cancelled, make sure we notify
            // the node that we are done
            this.runCallback(callback, scope || node, []);
        }
    },
    
    inspectCreateNode: function(attr) {
        var isPersistentFilter = !!attr.model && !!attr.filters;

        var isShared = ((attr.account_id === null) && (attr.created_by !== null)) ? true : false;
        
        var addText = '';
        if(isShared) addText = _(' (shared)'); 
        
        if (isPersistentFilter) {
            Ext.apply(attr, {
                isPersistentFilter: isPersistentFilter,
                text: Ext.util.Format.htmlEncode(this.app.i18n._hidden(attr.name)),
                qtip: Ext.util.Format.htmlEncode(attr.description ? this.app.i18n._hidden(attr.description) + addText : addText),
                selected: attr.id === this.selectedFilterId,
                id: attr.id,
                leaf: attr.leaf === false ? attr.leaf : true,
                cls: 'tinebase-westpanel-node-favorite'
            });
        }
    }
});
