/*
 * Tine 2.0
 *
 * @package     Expressomail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.namespace('Tine.Expressomail');

/**
 * @namespace   Tine.Expressomail
 * @class       Tine.Expressomail.FilterPanel
 * @extends     Tine.widgets.persistentfilter.PickerPanel
 *
 * <p>Expressomail Favorites Panel</p>
 *
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 *
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Expressomail.FilterPanel
 */
Tine.Expressomail.FilterPanel = Ext.extend(Tine.widgets.persistentfilter.PickerPanel, {
    filterModel: 'Expressomail_Model_MessageFilter'
});

/**
 * @namespace   Tine.Expressomail
 * @class       Tine.Expressomail.TreePanel
 * @extends     Ext.tree.TreePanel
 *
 * <p>Account/Folder Tree Panel</p>
 * <p>Tree of Accounts with folders</p>
 * <pre>
 * low priority:
 * TODO         make inbox/drafts/templates configurable in account
 * TODO         save tree state? @see http://examples.extjs.eu/?ex=treestate
 * TODO         disable delete action in account ctx menu if user has no manage_accounts right
 * </pre>
 *
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 *
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Expressomail.TreePanel
 *
 */
Tine.Expressomail.TreePanel = function(config) {
    Ext.apply(this, config);

    this.addEvents(
        /**
         * @event containeradd
         * Fires when a folder was added
         * @param {folder} the new folder
         */
        'containeradd',
        /**
         * @event containerdelete
         * Fires when a folder got deleted
         * @param {folder} the deleted folder
         */
        'containerdelete',
        /**
         * @event containerrename
         * Fires when a folder got renamed
         * @param {folder} the renamed folder
         */
        'containerrename',
        /**
         * @event beforecontainerrename
         * Fires before any folder is renamed
         * @param {node} the renamed folder node
         */
        'beforecontainerrename'
    );

    Tine.Expressomail.TreePanel.superclass.constructor.call(this);
};

Ext.extend(Tine.Expressomail.TreePanel, Ext.tree.TreePanel, {

    /**
     * @property app
     * @type Tine.Expressomail.Application
     */
    app: null,

    /**
     * @property accountStore
     * @type Ext.data.JsonStore
     */
    accountStore: null,

    /**
     * @type Ext.data.JsonStore
     */
    folderStore: null,

    /**
     * @cfg {String} containerName
     */
    containerName: 'Folder',

    /**
     * TreePanel config
     * @private
     */
    rootVisible: false,

    /**
     * drag n drop
     */
    enableDrop: true,
    ddGroup: 'mailToTreeDDGroup',

    /**
     * @cfg
     */
    border: false,
    recordClass: Tine.Expressomail.Model.Account,
    filterMode: 'filterToolbar',

    /**
     * is needed by Tine.widgets.mainscreen.WestPanel to fake container tree panel
     */
    selectContainerPath: Ext.emptyFn,
    updateGridDelay: 500,

    /**
     * init
     * @private
     */
    initComponent: function() {
        // get folder store
        this.folderStore = Tine.Tinebase.appMgr.get('Expressomail').getFolderStore();

        // init tree loader
        this.loader = new Tine.Expressomail.TreeLoader({
            folderStore: this.folderStore,
            app: this.app
        });

        // set the root node
        this.root = new Ext.tree.TreeNode({
            text: 'default',
            draggable: false,
            allowDrop: false,
            expanded: true,
            leaf: false,
            id: 'root'
        });

        // add account nodes
        this.initAccounts();

        // init drop zone
        this.dropConfig = {
            ddGroup: this.ddGroup || 'TreeDD',
            appendOnly: this.ddAppendOnly === true,
            notifyEnter : function() {this.isDropSensitive = true;}.createDelegate(this),
            notifyOut : function() {this.isDropSensitive = false;}.createDelegate(this),
            onNodeOver : function(n, dd, e, data) {
                var node = n.node;

                // auto node expand check (only for non-account nodes)
                if(!this.expandProcId && node.attributes.allowDrop && node.hasChildNodes() && !node.isExpanded()){
                    this.queueExpand(node);
                } else if (! node.attributes.allowDrop) {
                    this.cancelExpand();
                }
                return node.attributes.allowDrop ? 'tinebase-tree-drop-move' : false;
            },
            isValidDropPoint: function(n, dd, e, data){
                return n.node.attributes.allowDrop;
            }
        };

        // init selection model (multiselect)
        this.selModel = new Ext.tree.MultiSelectionModel({});

        // init context menu TODO use Ext.apply
        var initCtxMenu = Tine.Expressomail.setTreeContextMenus.createDelegate(this);
        initCtxMenu();

        // add listeners
        this.on('beforeclick', this.onBeforeClick, this);
        this.on('click', this.onClick, this);
        this.on('contextmenu', this.onContextMenu, this);
        this.on('beforenodedrop', this.onBeforenodedrop, this);
        this.on('append', this.onAppend, this);
        this.on('containeradd', this.onFolderAdd, this);
        this.on('containerrename', this.onFolderRename, this);
        this.on('beforecontainerrename', this.onBeforeFolderRename, this);
        this.on('containerdelete', this.onFolderDelete, this);
        this.selModel.on('selectionchange', this.onSelectionChangeDelay, this);
        this.folderStore.on('update', this.onUpdateFolderStore, this);

        // call parent::initComponent
        Tine.Expressomail.TreePanel.superclass.initComponent.call(this);
    },

    /**
     * add accounts from registry as nodes to root node
     * @private
     */
    initAccounts: function() {
        this.accountStore = this.app.getAccountStore();
        this.accountStore.each(this.addAccount, this);
        this.accountStore.on('update', this.onAccountUpdate, this);
    },

    /**
     * init extra tool tips
     */
    initToolTips: function() {
        this.folderTip = new Ext.ToolTip({
            target: this.getEl(),
            delegate: 'a.x-tree-node-anchor',
            renderTo: document.body,
            listeners: {beforeshow: this.updateFolderTip.createDelegate(this)}
        });

        this.folderProgressTip = new Ext.ToolTip({
            target: this.getEl(),
            delegate: '.expressomail-node-statusbox-progress',
            renderTo: document.body,
            listeners: {beforeshow: this.updateProgressTip.createDelegate(this)}
        });

        this.folderUnreadTip = new Ext.ToolTip({
            target: this.getEl(),
            delegate: '.expressomail-node-statusbox-unread',
            renderTo: document.body,
            listeners: {beforeshow: this.updateUnreadTip.createDelegate(this)}
        });
    },

    onSharingUpdate: function(node, shares, recursive) {
        var added = [],
            removed = [],
            path = node.attributes.path,
            record = this.folderStore.getAt(this.folderStore.findExact(
                    'path', path));

        Ext.each(shares, function(share){
            if (node.attributes.sharing_with.indexOf(share) === -1) {
                added.push(share);
            }
        }, this);
        Ext.each(node.attributes.sharing_with, function(share){
            if (shares.indexOf(share) === -1) {
                removed.push(share);
            }
        }, this);

        var editValue = function(_record){
            if (_record.get('can_share') && (!Ext.isEmpty(added) || !Ext.isEmpty(removed))) {
                var currentShares = _record.get('sharing_with').slice(0); // Cloning array
                _record.beginEdit();
                Ext.each(added, function(share){
                    if (currentShares.indexOf(share) === -1){
                        currentShares.push(share);
                    }
                }, this);
                Ext.each(removed, function(share){
                    var index = currentShares.indexOf(share);
                    if (index !== -1){
                        currentShares.splice(index, 1);
                    }
                }, this);
                _record.set('sharing_with', currentShares);
                _record.endEdit();
            }
        };
        if (recursive && !node.isLeaf()) {
            var records = this.folderStore.query('parent_path', path);
            records.add(record);
            records.each(editValue);
        } else {
            editValue.call(this, record);
        }
    },

    /**
     * called when a selection gets changed
     *
     * @param {SelectionModel} sm
     * @param {Object} node
     */
    onSelectionChangeDelay: function(sm, nodes) {
        if (this.selectionChangeDelayedTask) {
            this.selectionChangeDelayedTask.cancel();
        }
        this.selectionChangeDelayedTask = new Ext.util.DelayedTask(this.onSelectionChange, this, [sm, nodes]);
        this.selectionChangeDelayedTask.delay(this.updateGridDelay);
    },

    /**
     * called when tree selection changes
     *
     * @param {} sm
     * @param {} node
     */
    onSelectionChange: function(sm, nodes) {

        //Ignore non-selectable node
        if((typeof(nodes[0]) !== 'undefined')
            && !nodes[0].attributes.is_selectable
        ){
            return;
        }
        if (this.filterMode == 'gridFilter' && this.filterPlugin) {
            this.filterPlugin.onFilterChange();
        }
        if (this.filterMode == 'filterToolbar' && this.filterPlugin) {

            // get filterToolbar
            var ftb = this.filterPlugin.getGridPanel().filterToolbar;
            // in case of filterPanel
            ftb = ftb.activeFilterPanel ? ftb.activeFilterPanel : ftb;

            if (! ftb.rendered) {
                this.onSelectionChange.defer(150, this, [sm, nodes]);
                return;
            }

            // remove path filter
            var filter = this.getFilterPlugin().getFilter();
            var test = filter.value.length == 1 && filter.value[0].match(/^\/[A-z0-9]*$/);
            ftb.supressEvents = true;
            ftb.filterStore.each(function(filter) {
                if (!test) {
                    ftb.deleteFilter(filter);
                }
            }, this);
            ftb.supressEvents = false;

            // returns if account is selected
            if (filter.field == 'path' && (Ext.isEmpty(filter.value) || test))
            {
                return;
            }
            
            // set ftb filters according to tree selection
            ftb.addFilter(new ftb.record(filter));

            // prevent unnecessary loading of nodes that aren't selected anymore
            if (!(sm.selNodes==nodes)) {
                return;
            }

            // Prevent empty path filters from trigger searchMessage
            if (Ext.isEmpty(filter.value)){
                ftb.supressEvents = true;
            }
            ftb.onFiltertrigger();
            ftb.supressEvents = false;

            // finally select the selected node, as filtertrigger clears all selections
            sm.suspendEvents();
            Ext.each(nodes, function(node) {
                sm.select(node, Ext.EventObject, true);
            }, this);
            sm.resumeEvents();
        }
    },

    onFilterChange: Tine.widgets.container.TreePanel.prototype.onFilterChange,

   /**
     * returns a filter plugin to be used in a grid
     * @private
     */
    getFilterPlugin: function() {
        if (!this.filterPlugin) {
            this.filterPlugin = new Tine.widgets.tree.FilterPlugin({
                treePanel: this,
                field: 'path',
                nodeAttributeField: 'path',
                singleNodeOperator: 'in'
            });
        }

        this.filterPlugin.override( {
            setValue: function(filters) {
                if (! this.selectNodes) {
                    return null;
                }

                var sm = this.treePanel.getSelectionModel();

                // prevent unnecessary loading of nodes that aren't selected anymore
                if (!(sm.selNodes==this.selectNodes)) {
                    return;
                }

                Tine.widgets.tree.FilterPlugin.superclass.setValue.call(this, filters);
            }
        });

        return this.filterPlugin;
    },

    /**
     * convert containerPath to treePath
     *
     * @param {String}  containerPath
     * @return {String} treePath
     */
    getTreePath: function(path) {
        return '/root' + path;
    },

    /**
     * @private
     *
     * expand default account and select INBOX
     */
    afterRender: function() {
        Tine.Expressomail.TreePanel.superclass.afterRender.call(this);
        this.initToolTips();
        this.selectInbox();

        if (this.filterMode == 'filterToolbar' && this.filterPlugin) {
            this.filterPlugin.getGridPanel().filterToolbar.on('change', this.onFilterChange, this);
        }
    },

    /**
     * select inbox of account
     *
     * @param {Record} account
     */
    selectInbox: function(account) {
        var accountId = (account) ? account.id : Tine.Expressomail.registry.get('preferences').get('defaultEmailAccount');

        this.expandPath('/root/' + accountId + '/', null, function(success, parentNode) {
            Ext.each(parentNode.childNodes, function(node) {
                if (Ext.util.Format.lowercase(node.attributes.localname) == 'inbox') {
                    node.select();
                    return false;
                }
            }, this);
        });
    },

    /**
     * called when an account record updates
     *
     * @param {Ext.data.JsonStore} store
     * @param {Tine.Expressomail.Model.Account} record
     * @param {String} action
     */
    onAccountUpdate: function(store, record, action) {
        if (action === Ext.data.Record.EDIT) {
            this.updateAccountStatus(record);
        }
    },

    /**
     * on append node
     *
     * render status box
     *
     * @param {Tine.Expressomail.TreePanel} tree
     * @param {Ext.Tree.TreeNode} node
     * @param {Ext.Tree.TreeNode} appendedNode
     * @param {Number} index
     */
    onAppend: function(tree, node, appendedNode, index) {
        appendedNode.ui.render = appendedNode.ui.render.createSequence(function() {
            var app = Tine.Tinebase.appMgr.get('Expressomail'),
                folder = app.getFolderStore().getById(appendedNode.id);

            if (folder) {
                app.getMainScreen().getTreePanel().addStatusboxesToNodeUi(this);
                app.getMainScreen().getTreePanel().updateFolderStatus(folder);
            }
        }, appendedNode.ui);
    },

    /**
     * add status boxes
     *
     * @param {Object} nodeUi
     */
    addStatusboxesToNodeUi: function(nodeUi) {
        if (nodeUi.elNode.lastChild.className!=="expressomail-node-statusbox") {
            Ext.DomHelper.insertAfter(nodeUi.elNode.lastChild, {tag: 'span', 'class': 'expressomail-node-statusbox', cn:[
                {'tag': 'img', 'src': Ext.BLANK_IMAGE_URL, 'class': 'expressomail-node-statusbox-progress'},
                {'tag': 'span', 'class': 'expressomail-node-statusbox-unread'}
            ]});
        }
    },

    /**
     * on before click handler
     * - accounts are not clickable because fetching all messages of account is too expensive
     * - skip event for folders that are not selectable
     *
     * @param {Ext.tree.AsyncTreeNode} node
     */
    onBeforeClick: function(node) {
        if (this.accountStore.getById(node.id) || ! this.app.getFolderStore().getById(node.id).get('is_selectable')) {
            return false;
        }
    },

    /**
     * on click handler
     *
     * - expand node
     * - update filter toolbar of grid
     * - start check mails delayed task
     *
     * @param {Ext.tree.AsyncTreeNode} node
     * @private
     */
    onClick: function(node) {
        if (node.expandable) {
            node.expand();
        }

        if (node.id && node.id != '/' && node.attributes.globalname != '') {
            var folder = this.app.getFolderStore().getById(node.id);
            this.app.checkMailsDelayedTask.delay(0);
        }
    },

    setDisabledContextMenuItem: function(menu, iconClsName, disable) {
        menu.items.each(function(item) {
            if (item.iconCls === iconClsName) {
                item.setDisabled(disable);
            }
        });
    },

    /**
     * show context menu for folder tree
     *
     * items:
     * - create folder
     * - rename folder
     * - delete folder
     * - ...
     *
     * @param {} node
     * @param {} event
     * @private
     */
    onContextMenu: function(node, event) {
        this.ctxNode = node;
        if (!node.ui || node.ui.elNode.className.search(/x-tree-node-loading/) >= 0) {
            // don't show context menu while node is still loading
            return;
        }

        var folder = this.app.getFolderStore().getById(node.id),
            account = folder ? this.accountStore.getById(folder.get('account_id')) :
                               this.accountStore.getById(node.id);

        if (! folder) {
            // edit/remove account
            if (account.get('ns_personal') !== 'default') {
                this.contextMenuAccount.items.each(function(item) {
                    // check account personal namespace -> disable 'add folder' if namespace is other than root
                    if (item.iconCls == 'action_add') {
                        item.setDisabled(account.get('ns_personal') != '');
                    }
                    // disable filter rules/vacation if no sieve hostname is set
                    if (item.iconCls == 'action_email_replyAll' || item.iconCls == 'action_email_forward') {
                        item.setDisabled(account.get('sieve_hostname') == null || account.get('sieve_hostname') == '');
                    }
                });

                this.contextMenuAccount.showAt(event.getXY());
            }
        } else {
            var is_shared = folder.get('parent').indexOf('user'),
                export_folder_enabled = Tine.Expressomail.registry.get('enableMailDirExport'),
                can_share = folder.get('can_share'),
                action = 'action_managePermissions';
            if ((is_shared < 0) && (export_folder_enabled)){
                if (folder.get('globalname') === account.get('trash_folder')) {
                    this.setDisabledContextMenuItem(this.contextMenuTrashExp, action, !can_share);
                    this.contextMenuTrashExp.showAt(event.getXY());
                } else if (! folder.get('is_selectable')){
                    this.unselectableFolder.showAt(event.getXY());
                } else if (folder.get('system_folder')) {
                    this.setDisabledContextMenuItem(this.contextMenuSystemFolderExp, action, !can_share);
                    this.contextMenuSystemFolderExp.showAt(event.getXY());
                } else if(folder.get('has_children')){
                    this.setDisabledContextMenuItem(this.contextMenuUserFolderChildrenExp, action, !can_share);
                    this.contextMenuUserFolderChildrenExp.showAt(event.getXY());
                } else {
                    this.setDisabledContextMenuItem(this.contextMenuUserFolderExp, action, !can_share);
                    this.contextMenuUserFolderExp.showAt(event.getXY());
                }
            }
            else{
                if (folder.get('globalname') === account.get('trash_folder')) {
                    this.setDisabledContextMenuItem(this.contextMenuTrash, action, !can_share);
                    this.contextMenuTrash.showAt(event.getXY());
                } else if (! folder.get('is_selectable')){
                    this.unselectableFolder.showAt(event.getXY());
                } else if (folder.get('system_folder')) {
                    this.setDisabledContextMenuItem(this.contextMenuSystemFolder, action, !can_share);
                    this.contextMenuSystemFolder.showAt(event.getXY());
                } else if(folder.get('has_children')){
                    this.setDisabledContextMenuItem(this.contextMenuUserFolderChildren, action, !can_share);
                    this.contextMenuUserFolderChildren.showAt(event.getXY());
                } else {
                    this.setDisabledContextMenuItem(this.contextMenuUserFolder, action, !can_share);
                    this.contextMenuUserFolder.showAt(event.getXY());
                }
            }
        }
    },

    /**
     * mail(s) got dropped on node
     *
     * @param {Object} dropEvent
     * @private
     */
    onBeforenodedrop: function(dropEvent) {
        var targetFolderId = dropEvent.target.attributes.folder_id,
            targetFolder = this.app.getFolderStore().getById(targetFolderId);

        this.app.getMainScreen().getCenterPanel().moveSelectedMessages(targetFolder, false);
        return true;
    },

    /**
     * cleanup on destruction
     */
    onDestroy: function() {
        this.folderStore.un('update', this.onUpdateFolderStore, this);
    },

    /**
     * folder store gets updated -> update tree nodes
     *
     * @param {Tine.Expressomail.FolderStore} store
     * @param {Tine.Expressomail.Model.Folder} record
     * @param {String} operation
     */
    onUpdateFolderStore: function(store, record, operation) {
        if (operation === Ext.data.Record.EDIT) {
            this.updateFolderStatus(record);
        }
    },

    /**
     * folders sort function
     *
     * @param {Tine.Expressomail.Model.Folder} node1
     * @param {Tine.Expressomail.Model.Folder} node2
     * @return {Number} 
     */
    sortFolders: function(node1, node2) {
        if (node1.attributes.system_folder && !node2.attributes.system_folder) {
            // system folders come first
            return -1;
        }
        if (node2.attributes.system_folder && !node1.attributes.system_folder) {
            // system folders come first
            return 1;
        }
        // sort folders
        if (node1.text.toUpperCase() > node2.text.toUpperCase()) {
            return 1;
        }
        else if (node1.text.toUpperCase() < node2.text.toUpperCase()) {
            return -1;
        }
        return 0;
    },

    /**
     * add new folder to the store
     *
     * @param {Object} folderData
     */
    onFolderAdd: function(folderData) {
        var recordData = Ext.copyTo({}, folderData, Tine.Expressomail.Model.Folder.getFieldNames());
        var newRecord = Tine.Expressomail.folderBackend.recordReader({responseText: Ext.util.JSON.encode(recordData)});

        var node = this.getNodeById(newRecord.id);
        node.ui.beforeLoad(newRecord); // show loading icon

        Tine.log.debug('Added new folder:' + newRecord.get('globalname'));

        this.folderStore.add([newRecord]);

        var parentId = newRecord.get('parent_path').split('/').pop(),
            parentNode = this.getNodeById(parentId);
        if (! (parentNode instanceof Ext.tree.AsyncTreeNode)) {
            this.app.getFolderStore().getById(parentId).set('has_children', true);
            parentNode.attributes.has_children = true;
            parentNode.attributes.leaf = false;
            parentNode.leaf = false;
            Ext.apply(parentNode,Ext.tree.AsyncTreeNode.prototype); // convert to AsyncTreeNode
            this.folderStore.asyncQuery('parent_path', parentNode.attributes.path, function(node){
                node.reload(); // reload parent node
            }, [parentNode], this, this.folderStore);
        }
        else {
            node.ui.afterLoad(newRecord); // hide loading icon
        }
        parentNode.sort(this.sortFolders); // sort childnodes from parent
        
        this.initNewFolderNode(newRecord);
    },

    /**
     * init new folder node
     *
     * @param {Tine.Expressomail.Model.Folder} newRecord
     */
    initNewFolderNode: function(newRecord) {
        // update paths in node
        var appendedNode = this.getNodeById(newRecord.id);

        if (! appendedNode) {
            // node is not yet rendered -> reload parent
            var parentId = newRecord.get('parent_path').split('/').pop(),
                parentNode = this.getNodeById(parentId);
            parentNode.reload(function() {
                this.initNewFolderNode(newRecord);
            }, this);
            return;
        }

        appendedNode.attributes.path = newRecord.get('path');
        appendedNode.attributes.parent_path = newRecord.get('parent_path');

        // add unreadcount/progress/tooltip
        this.addStatusboxesToNodeUi(appendedNode.ui);
        this.updateFolderStatus(newRecord);
    },
    
    /**
     * prepare folder node in tree panel
     *
     * @param {Object} node
     */
    onBeforeFolderRename: function(node){
        
        this.app.getFolderStore().getById(node.id).set('is_selectable',false);
        node.collapse();
        node.ui.beforeLoad(node);
        
        var selectedNode = this.getSelectionModel().getSelectedNode(),
            isSelectedNode = (selectedNode && node.id == selectedNode.id);
        
        if(isSelectedNode){
            this.app.getMainScreen().getCenterPanel().grid.getSelectionModel().clearSelections();
            this.app.getMainScreen().getCenterPanel().getStore().removeAll();
        }
    },

    /**
     * rename folder in the store
     *
     * @param {Object} folderData
     */
    onFolderRename: function(folderData) {
        
        if(folderData.recent === this.ctxNode.attributes.globalname){
            folderData.recent = '';
            var oldId = this.ctxNode.id;
            var node = this.getNodeById(oldId);

            node.ui.beforeLoad(node); // show loading icon

            this.folderStore.resetQueryAndRemoveRecords('parent_path',  node.attributes.path + node.account_id);

            var recordData = Ext.copyTo({}, folderData, Tine.Expressomail.Model.Folder.getFieldNames());
            var newRecord = Tine.Expressomail.folderBackend.recordReader({responseText: Ext.util.JSON.encode(recordData)});
            this.folderStore.add([newRecord]);
            this.folderStore.remove(this.folderStore.getById(oldId));

            node.setId(newRecord.get('id'));
            node.attributes.path = newRecord.get('path');
            node.attributes.parent_path = newRecord.get('parent_path');
            node.attributes.id = newRecord.get('id');
            node.attributes.folder_id = newRecord.get('id');
            node.attributes.globalname = newRecord.get('globalname');
            node.attributes.localname = newRecord.get('localname');
            node.attributes.text = newRecord.get('text');

            var sm = this.getSelectionModel();
            var selectedNode = sm.getSelectedNode();

            this.getSelectionModel().clearSelections();
            node.removeAll();
            
            var key = this.folderStore.getKey('parent_path', node.attributes.path);
            this.folderStore.queriesDone.remove(key);
            
            this.folderStore.asyncQuery('parent_path', node.attributes.path, function(node){
                if(!node.isLeaf()){
                    node.reload(); // reload node
                }
                else {
                    node.ui.afterLoad(node); // hide loading icon
                }
                var parentId = newRecord.get('parent_path').split('/').pop(),
                    parentNode = this.getNodeById(parentId);
                parentNode.sort(this.sortFolders); // sort childnodes from parent
                
               // if renamed node is the selectedNode, so select it and reload messages
               if(node.id == selectedNode.id){
                   sm.select(selectedNode, Ext.EventObject, true);
               }
                
            }, [node], this, this.folderStore);

            Tine.log.debug('Renamed folder:' + newRecord.get('globalname'));
        }
    },
    
    /**
     * remove deleted folder from the store
     *
     * @param {Object} folderData
     */
    onFolderDelete: function(folderData) {
        var parentId = folderData.parent_path.split('/').pop();
        var parentNode = this.app.getMainScreen().getTreePanel().getNodeById(parentId);
        parentNode.ui.beforeLoad(parentNode); // show loading icon
        
        // if we deleted account, remove it from account store
        if (folderData.record && folderData.record.modelName === 'Account') {
            this.accountStore.remove(this.accountStore.getById(folderData.id));
        }
        
        this.folderStore.remove(this.folderStore.getById(folderData.id));

        if(parentNode.childNodes.length == 0){
            this.app.getFolderStore().getById(parentId).set('has_children', false);
            parentNode.attributes.expandable = false;
            parentNode.attributes.has_children = false;
            parentNode.attributes.leaf = true;
            parentNode.leaf = true;
            this.folderStore.asyncQuery('parent_path', parentNode.attributes.path, function(node){
                node.reload(); // reload parent node
            }, [parentNode], this, this.folderStore);
        }
        else {
            parentNode.ui.afterLoad(parentNode); // hide loading icon
        }
    },

    /**
     * returns tree node id the given el is child of
     *
     * @param  {HTMLElement} el
     * @return {String}
     */
    getElsParentsNodeId: function(el) {
        return Ext.fly(el, '_treeEvents').up('div[class^=x-tree-node-el]').getAttribute('tree-node-id', 'ext');
    },

    /**
     * updates account status icon in this tree
     *
     * @param {Tine.Expressomail.Model.Account} account
     */
    updateAccountStatus: function(account) {
        var imapStatus = account.get('imap_status'),
            node = this.getNodeById(account.id),
            ui = node ? node.getUI() : null,
            nodeEl = ui ? ui.getEl() : null;

        Tine.log.info('Account ' + account.get('name') + ' updated with imap_status: ' + imapStatus);
        if (node && node.ui.rendered) {
            var statusEl = Ext.get(Ext.DomQuery.selectNode('span[class=expressomail-node-accountfailure]', nodeEl));
            if (! statusEl) {
                // create statusEl on the fly
                statusEl = Ext.DomHelper.insertAfter(ui.elNode.lastChild, {'tag': 'span', 'class': 'expressomail-node-accountfailure'}, true);
                statusEl.on('click', function() {
                    Tine.Expressomail.folderBackend.handleRequestException(account.getLastIMAPException());
                }, this);
            }

            statusEl.setVisible(imapStatus === 'failure');
        }
    },

    /**
     * updates folder status icons/info in this tree
     *
     * @param {Tine.Expressomail.Model.Folder} folder
     */
    updateFolderStatus: function(folder) {
        var unreadcount = folder.get('cache_unreadcount'),
            progress    = Math.round(folder.get('cache_job_actions_done') / folder.get('cache_job_actions_est') * 10) * 10,
            node        = this.getNodeById(folder.id),
            ui = node ? node.getUI() : null,
            nodeEl = ui ? ui.getEl() : null,
            cacheStatus = folder.get('cache_status'),
            lastCacheStatus = folder.modified ? folder.modified.cache_status : null,
            isSelected = folder.isCurrentSelection();

        this.setUnreadClass(folder.id);

        if (node && node.ui.rendered) {
            var domNode = Ext.DomQuery.selectNode('span[class=expressomail-node-statusbox-unread]', nodeEl);
            if (domNode) {

                // update unreadcount + visibity
                Ext.fly(domNode).update(unreadcount).setVisible(unreadcount > 0);

                // update progress
                var progressEl = Ext.get(Ext.DomQuery.selectNode('img[class^=expressomail-node-statusbox-progress]', nodeEl));
                progressEl.removeClass(['expressomail-node-statusbox-progress-pie', 'expressomail-node-statusbox-progress-loading']);
                if (! Ext.isNumber(progress)) {
                    progressEl.setStyle('background-position', 0 + 'px');
                    progressEl.addClass('expressomail-node-statusbox-progress-loading');
                } else {
                    progressEl.setStyle('background-position', progress + '%');
                    progressEl.addClass('expressomail-node-statusbox-progress-pie');
                }
                progressEl.setVisible(isSelected && cacheStatus !== 'complete' && cacheStatus !== 'disconnect' && progress !== 100 && lastCacheStatus !== 'complete');
            }
        }
        if (node) {
            if (Boolean(folder.get('sharing_with').length) !== Boolean(node.attributes.sharing_with.length)) {
                var classesMap = [
                    'x-tree-node-expanded',
                    'x-tree-node-collapsed',
                    'expressomail-node-trash',
                    'expressomail-node-trash-full',
                    'expressomail-node-sent',
                    'expressomail-node-inbox',
                    'expressomail-node-drafts',
                    'expressomail-node-templates',
                    'expressomail-node-junk',
                    'expressomail-node-remote',
                    'expressomail-node-remote-open'
                ];

                // Find classes to change
                var foundClasses = new Array();
                Ext.each(classesMap, function(item){
                    if (Ext.fly(node.getUI().elNode).hasClass(item)){
                        foundClasses.push(item);
                    } else if (Ext.fly(node.getUI().elNode).hasClass(item + '-overlay-share')) {
                        foundClasses.push(item + '-overlay-share');
                    }
                });

                Ext.each(foundClasses, function(oldCls){
                    var newCls = Boolean(folder.get('sharing_with').length) ? oldCls + '-overlay-share'
                                                                    : oldCls.replace('-overlay-share', '');

                    if (Boolean(folder.get('sharing_with').length)
                        && !Ext.fly(node.getUI().elNode).hasClass("x-tree-node-leaf"))
                    {
                        Ext.fly(node.getUI().elNode).addClass("x-tree-node-leaf");
                    } else if (!Boolean(folder.get('sharing_with').length)
                                && Ext.fly(node.getUI().elNode).hasClass("x-tree-node-leaf"))
                    {
                        if (!node.getUI().wasLeaf) {
                            Ext.fly(node.getUI().elNode).removeClass("x-tree-node-leaf");
                        }
                    }
                    Ext.fly(node.getUI().elNode).replaceClass(oldCls, newCls);
                }, this);
            }
            node.attributes.sharing_with = folder.get('sharing_with');
        }

    },

    /**
     * set unread class of folder node and parents
     *
     * @param {Tine.Expressomail.Model.Folder} folder
     *
     * TODO make it work correctly for parents (use events) and activate again
     */
    setUnreadClass: function(folderId) {
        var folder              = this.app.getFolderStore().getById(folderId),
            node                = this.getNodeById(folderId),
            isUnread            = folder.get('cache_unreadcount') > 0,
            hasUnreadChildren   = folder.get('unread_children').length > 0;

        if (node && node.ui.rendered) {
            var ui = node.getUI();
            ui[(isUnread || hasUnreadChildren) ? 'addClass' : 'removeClass']('expressomail-node-unread');
        }

        // get parent, update and call recursivly
//        var parentFolder = this.app.getFolderStore().getParent(folder);
//        if (parentFolder) {
//            // need to create a copy of the array here (and make sure it is unique)
//            var unreadChildren = Ext.unique(parentFolder.get('unread_children'));
//
//            if (isUnread || hasUnreadChildren) {
//                unreadChildren.push(folderId);
//            } else {
//                unreadChildren.remove(folderId);
//            }
//            parentFolder.set('unread_children', unreadChildren);
//            this.setUnreadClass(parentFolder.id);
//        }
    },

    /**
     * updates the given tip
     * @param {Ext.Tooltip} tip
     */
    updateFolderTip: function(tip) {
        var folderId = this.getElsParentsNodeId(tip.triggerElement),
            folder = this.app.getFolderStore().getById(folderId);
        if ((folder && !this.isDropSensitive) && (folder.get('client_access_time') != '')) {
            var limit = parseInt(folder.get('quota_limit'), 10),
                usage = parseInt(folder.get('quota_usage'), 10),
                left = limit - usage,
                percentage = String.format(this.app.i18n._('{0} %'), Ext.util.Format.number((left / limit * 100), '00.0'));
            var info = [
                '<table>',
                    '<tr>',
                        '<td>', this.app.i18n._('Total Messages:'), '</td>',
                        '<td>', folder.get('cache_totalcount'), '</td>',
                    '</tr>',
                    '<tr>',
                        '<td>', this.app.i18n._('Unread Messages:'), '</td>',
                        '<td>', folder.get('cache_unreadcount'), '</td>',
                    '</tr>',
                    '<tr>',
                        '<td>', this.app.i18n._('Name on Server:'), '</td>',
                        '<td>', folder.get('globalname'), '</td>',
                    '</tr>',
                    '<tr>',
                        '<td>', this.app.i18n._('Your quota'), ':</td>',
                        '<td>', isNaN(limit * 1024) ? "-" : Ext.util.Format.fileSize(limit * 1024), '</td>',
                    '</tr>',
                    '<tr>',
                        '<td>', this.app.i18n._('Available quota'), ' :</td>',
                        '<td>', isNaN(left * 1024) ? "-" : Ext.util.Format.fileSize(left * 1024),
                        ' (',  percentage,')</td>',
                    '</tr>',
                     '<tr>',
                        '<td>', this.app.i18n._('Last update:'), '</td>',
                        '<td>', Tine.Tinebase.common.dateTimeRenderer(folder.get('client_access_time')), '</td>',
                    '</tr>',
                '</table>'
            ];
            tip.body.dom.innerHTML = info.join('');
        } else {
            return false;
        }
    },

    /**
     * updates the given tip
     * @param {Ext.Tooltip} tip
     */
    updateProgressTip: function(tip) {
        var folderId = this.getElsParentsNodeId(tip.triggerElement),
            folder = this.app.getFolderStore().getById(folderId),
            progress = Math.round(folder.get('cache_job_actions_done') / folder.get('cache_job_actions_est') * 100);
        if (! this.isDropSensitive) {
            tip.body.dom.innerHTML = String.format(this.app.i18n._('Fetching messages... ({0}%% done)'), progress);
        } else {
            return false;
        }
    },

    /**
     * updates the given tip
     * @param {Ext.Tooltip} tip
     */
    updateUnreadTip: function(tip) {
        var folderId = this.getElsParentsNodeId(tip.triggerElement),
            folder = this.app.getFolderStore().getById(folderId),
            count = folder.get('cache_unreadcount');

        if (! this.isDropSensitive) {
            tip.body.dom.innerHTML = String.format(this.app.i18n.ngettext('{0} unread message', '{0} unread messages', count), count);
        } else {
            return false;
        }
    },

    /**
     * decrement unread count of currently selected folder
     */
    decrementCurrentUnreadCount: function() {
        var store  = Tine.Tinebase.appMgr.get('Expressomail').getFolderStore(),
            node   = this.getSelectionModel().getSelectedNode(),
            folder = node ? store.getById(node.id) : null;

        if (folder) {
            folder.set('cache_unreadcount', parseInt(folder.get('cache_unreadcount'), 10) -1);
            folder.commit();
        }
    },

    /**
     * add account record to root node
     *
     * @param {Tine.Expressomail.Model.Account} record
     */
    addAccount: function(record) {

        var node = new Ext.tree.AsyncTreeNode({
            id: record.data.id,
            path: '/' + record.data.id,
            record: record,
            globalname: '',
            draggable: false,
            allowDrop: false,
            expanded: false,
            text: Ext.util.Format.htmlEncode(record.get('name')),
            qtip: Tine.Tinebase.common.doubleEncode(record.get('host')),
            leaf: false,
            cls: 'expressomail-node-account',
            delimiter: record.get('delimiter'),
            ns_personal: record.get('ns_personal'),
            account_id: record.data.id,
            listeners: {
                scope: this,
                load: function(node) {
                    var account = this.accountStore.getById(node.id);
                    this.updateAccountStatus(account);
                }
            }
        });

        // we don't want appending folder effects
        this.suspendEvents();
        this.root.appendChild(node);
        this.resumeEvents();
    },

    /**
     * get active account by checking selected node
     * @return Tine.Expressomail.Model.Account
     */
    getActiveAccount: function() {
        var result = null;
        var node = this.getSelectionModel().getSelectedNode();
        if (node) {
            var accountId = node.attributes.account_id;
            result = this.accountStore.getById(accountId);
        }

        return result;
    }
});
