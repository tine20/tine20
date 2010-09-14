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

/*global Ext, Tine, Locale*/

Ext.ns('Tine.Admin');

Tine.Admin = function () {
	
	/**
	 * builds the admin applications tree
	 */
    var getInitialTree = function (translation) { 
    	
    	return [{
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
	    }, {
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
	    }, {
	        text: translation.gettext('Roles'),
	        cls: "treemain",
	        iconCls: 'action_permissions',
	        allowDrag: false,
	        allowDrop: true,
	        id: "roles",
	        children: [],
	        leaf: null,
	        expanded: true,
	        dataPanelType: "roles",
	        viewRight: 'roles'
	    }, {
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
	    }, {
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
		}, {
			text: translation.gettext('Access Log'),
			cls: "treemain",
	        iconCls: 'admin-node-accesslog',
			allowDrag: false,
			allowDrop: true,
			id: "accesslog",
			icon: false,
			children: [],
			leaf: null,
			expanded: true,
			dataPanelType: "accesslog",
			viewRight: 'access_log'
		}, {
	        text: translation.gettext('Shared Tags'),
	        cls: "treemain",
	        iconCls: 'action_tag',
	        allowDrag: false,
	        allowDrop: true,
	        id: "sharedtags",
	        //icon :false,
	        children: [],
	        leaf: null,
	        expanded: true,
	        dataPanelType: "sharedtags",
	        viewRight: 'shared_tags'
	    }];
	};

	/**
     * creates the admin menu tree
     *
     */
    var getAdminTree = function () {
    	
        var translation = new Locale.Gettext();
        translation.textdomain('Admin');
        
        var treeLoader = new Ext.tree.TreeLoader({
            dataUrl: 'index.php',
            baseParams: {
                jsonKey: Tine.Tinebase.registry.get('jsonKey'),
                method: 'Admin.getSubTree',
                location: 'mainTree'
            }
        });
        treeLoader.on("beforeload", function (loader, node) {
            loader.baseParams.node = node.id;
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
            draggable: false,
            allowDrop: false,
            id: 'root'
        });
        treePanel.setRootNode(treeRoot);
        
        var initialTree = getInitialTree(translation);
        
        for (var i = 0; i < initialTree.length; i += 1) {
        	var node = new Ext.tree.AsyncTreeNode(initialTree[i]);
        	
        	// check view right
        	if (initialTree[i].viewRight && !Tine.Tinebase.common.hasRight('view', 'Admin', initialTree[i].viewRight)) {
                node.disabled = true;
        	}
        	
            treeRoot.appendChild(node);
        }
        
        treePanel.on('click', function (node, event) {
        	
        	if (node.disabled) {
        		return false;
        	}
        	
        	var currentToolbar = Tine.Tinebase.MainScreen.getActiveToolbar();

        	switch (node.attributes.dataPanelType) 
        	{
            case 'accesslog':
				Tine.Admin.AccessLog.Main.show();
                break;
                
            case 'accounts':
                Tine.Admin.user.show();
                break;
                
            case 'groups':
                Tine.Admin.Groups.Main.show();
                break;
                
            case 'computers':
                Tine.Admin.sambaMachine.show();
                break;
                
            case 'applications':
				Tine.Admin.Applications.Main.show();
                break;
                
            case 'sharedtags':
				Tine.Admin.Tags.Main.show();
                break;

            case 'roles':
				Tine.Admin.Roles.Main.show();
                break;
            }
        }, this);

        treePanel.on('beforeexpand', function (panel) {
            if (panel.getSelectionModel().getSelectedNode() === null) {
                panel.expandPath('/root');
                // don't open 'applications' if user has no right to manage apps
                if (Tine.Tinebase.common.hasRight('manage', 'Admin', 'accounts')) {
                    panel.selectPath('/root/accounts');
                } else {
                    treeRoot.eachChild(function (node) {
                        if (Tine.Tinebase.common.hasRight('manage', 'Admin', node.attributes.viewRight)) {
                            panel.selectPath('/root/' + node.id);
                            return;
                        }
                    }, this);
                }
            }
            panel.fireEvent('click', panel.getSelectionModel().getSelectedNode());
        }, this);

        treePanel.on('contextmenu', function (node, event) {
            event.stopEvent();
            //node.select();
            //node.getOwnerTree().fireEvent('click', _node);
            /* switch(node.attributes.contextMenuClass) {
                case 'ctxMenuContactsTree':
                    ctxMenuContactsTree.showAt(event.getXY());
                    break;
            } */
        });

        return treePanel;
    };
    
    // public functions and variables
    return {
        getPanel: getAdminTree
    };
    
}();