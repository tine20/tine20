/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @todo        refactor this (split file, use new windows, ...)
 */

/*global Ext, Tine, Locale*/

Ext.ns('Tine.Admin');

Tine.Admin.init = function () {
    var registeredItems = [];
    var panels = [];

    /**
     * builds the admin applications tree
     */
    var getInitialTree = function (translation) {
        
        var _ = window.lodash,
            tree = [{
            text: translation.ngettext('User', 'Users', 50),
            cls: 'treemain',
            iconCls: 'tinebase-accounttype-user',
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
            iconCls: 'tinebase-accounttype-group',
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
            iconCls: 'tinebase-accounttype-role',
            allowDrag: false,
            allowDrop: true,
            id: "roles",
            children: [],
            leaf: null,
            expanded: true,
            dataPanelType: "roles",
            viewRight: 'roles'
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
            text: translation.gettext('Containers'),
            cls: "treemain",
            iconCls: 'admin-node-containers',
            allowDrag: false,
            allowDrop: true,
            id: "containers",
            children: [],
            leaf: null,
            expanded: true,
            dataPanelType: "containers",
            viewRight: 'containers'
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
        }, {
            text: translation.gettext('Customfields'),
            cls: "treemain",
            iconCls: 'admin-node-customfields',
            allowDrag: false,
            allowDrop: true,
            id: "customfields",
            children: [],
            leaf: null,
            expanded: true,
            dataPanelType: "customfields",
            viewRight: 'customfields'
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
            hidden: ! Tine.Admin.registry.get('manageSAM'),
            viewRight: 'computers'
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
            text: translation.gettext('Server Information'),
            cls: "treemain",
            iconCls: 'admin-node-server-info',
            allowDrag: false,
            allowDrop: true,
            id: "serverinfo",
            children: [],
            leaf: null,
            expanded: true,
            dataPanelType: "serverinfo",
            viewRight: 'serverinfo'
        }];
        
        // TODO use hooking mechanism below
        if (Tine.Tinebase.appMgr.get('ActiveSync') && Tine.Tinebase.common.hasRight('manage_devices', 'ActiveSync')) {
            tree.push({
                text: translation.gettext('ActiveSync Devices'),
                pos: 850,
                cls: "treemain",
                iconCls: 'activesync-device-standard',
                allowDrag: false,
                allowDrop: true,
                id: "devices",
                children: [],
                leaf: null,
                expanded: true,
                dataPanelType: "devices"
            });
        }

        // TODO use hooking mechanism
        if (Tine.Tinebase.appMgr.get('Felamimail')
            && Tine.Tinebase.common.hasRight('view', 'Admin', 'manage_emailaccounts')
        ) {
            tree.push({
                text: translation.gettext('E-Mail Accounts'),
                //pos: 850,
                cls: "treemain",
                iconCls: 'FelamimailIconCls',
                allowDrag: false,
                allowDrop: true,
                id: "emailaccounts",
                children: [],
                leaf: null,
                expanded: true,
                dataPanelType: "emailaccounts",
                viewRight: 'emailaccounts'
            });
        }

        // TODO use hooking mechanism
        if (Tine.Tinebase.common.hasRight('view', 'Admin', 'manage_importexportdefinitions')
        ) {
            tree.push({
                text: translation.gettext('Import Export Definitions'),
                cls: "treemain",
                iconCls: 'admin-node-customfields',
                allowDrag: false,
                allowDrop: true,
                id: "importexportdefinitions",
                icon: false,
                children: [],
                leaf: null,
                expanded: true,
                dataPanelType: "importexportdefinitions",
            });
        }
        
        _.each(tree, function(item, idx) {
            item.pos = item.pos || (100 + 100 * idx);
        });
            _.each(registeredItems, function(item) {
            // NOTE: too early for appMgr :-(
            var app = item.appName ? Tine.Tinebase.appMgr.get(item.appName) : null,
                i18n = app ? app.i18n : translation;

            item.text = i18n._hidden(item.text);
            item.pos = item.pos || (200 + 100 * tree.length);

            tree.push(item);
        });


        return _.sortBy(tree, 'pos');
    };

    /**
     * register a new admin (tree) item
     * @param item
     */
    var registerItem = function(item) {
        item.dataPanelType = item.dataPanelType || Ext.id();

        registeredItems.push(Ext.applyIf(item, {
            cls: "treemain",
            allowDrag: false,
            allowDrop: true,
            children: [],
            leaf: null,
            expanded: true,
        }));
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

        for (var i = 0, rightSuffix; i < initialTree.length; i += 1) {
            rightSuffix = String(initialTree[i].viewRight).replace(/^view_/, '');
            if (rightSuffix !== 'undefined' && !(Tine.Tinebase.common.hasRight('view_' + rightSuffix, 'Admin')
                || Tine.Tinebase.common.hasRight('manage_' + rightSuffix, 'Admin')
            )) {
                initialTree[i].hidden = true;
            }
            var node = new Ext.tree.AsyncTreeNode(initialTree[i]);
            treeRoot.appendChild(node);
        }
        
        treePanel.on('click', function (node, event) {
            
            if (node === null|| node.disabled) {
                return false;
            }

            Ext.ux.layout.CardLayout.helper.setActiveCardPanelItem(
                Tine.Tinebase.appMgr.get('Admin').getMainScreen().moduleCardPanel,
                new Ext.Panel({html:'', border: false, frame: false}),
                true
            );

            var _ = window.lodash,
                me = this,
                item = node.attributes,
                dataPanelType = item.dataPanelType;

            switch (dataPanelType) {
            case 'accesslog':
                Tine.Admin.accessLog.show();
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
            case 'containers':
                Tine.Admin.container.show();
                break;
            case 'customfields':
                Tine.Admin.customfield.show();
                break;
            case 'importexportdefinitions':
                Tine.Admin.importexportdefinitions.show();
                break;
            case 'serverinfo':
                Tine.Admin.getServerInfo(function(response) {
                   Tine.log.debug('Tine.Admin.getServerInfo()');
                   
                   if (! this.infoPanel) {
                       this.infoPanel = new Ext.Panel({
                           canonicalName: 'ServerInfo',
                           html: response.html,
                           autoScroll: true
                       });
                   } else {
                       this.infoPanel.update(response.html);
                   }
                   if (! this.infoPanelToolbar) {
                       // TODO add correct Tine 2.0 Toolbar layout with border
                       this.infoPanelToolbar = new Ext.Toolbar({items: [{
                           text: translation.gettext('Refresh'),
                           handler: function() {
                               node.fireEvent('click', node);
                           },
                           iconCls: 'action_login',
                           scale: 'medium',
                           rowspan: 2,
                           iconAlign: 'top'
                       }]});
                   }
                   Tine.Tinebase.MainScreen.setActiveContentPanel(this.infoPanel, true);
                   Tine.Tinebase.MainScreen.setActiveToolbar(this.infoPanelToolbar, true);
                }, this);
                break;
            // TODO find a generic hooking mechanism
            case 'devices':
                Tine.ActiveSync.syncdevices.show();
                break;
            case 'emailaccounts':
                // TODO should be hidden if feature is disabled
                if (Tine.Tinebase.appMgr.get('Admin').featureEnabled('featureEmailAccounts')) {
                    Tine.Felamimail.admin.showAccountGridPanel();
                } else {
                    Ext.MessageBox.alert(translation.gettext('Disabled'), translation.gettext('Feature is disabled by configuration.'));
                }
                break;

            default:
                if (! panels[dataPanelType]) {
                    panels[dataPanelType] = new item.panel();
                }

                var contentPanel = panels[dataPanelType],
                    actionToolbar;

                if (! _.isFunction(contentPanel.getActionToolbar)) {
                    actionToolbar = new Ext.Toolbar();
                    contentPanel.getActionToolbar = function() {return actionToolbar};
                }

                Tine.Tinebase.appMgr.get('Admin').getMainScreen().setActiveContentPanel(contentPanel, true);
                Tine.Tinebase.appMgr.get('Admin').getMainScreen().setActiveToolbar(contentPanel.getActionToolbar(), true);
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
        });

        return treePanel;
    };

    Tine.Admin.registerItem = registerItem;
    Tine.Admin.getPanel = getAdminTree;
}();