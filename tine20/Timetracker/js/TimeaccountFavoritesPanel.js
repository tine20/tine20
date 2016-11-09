/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <M.Spahn@bitExpert.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2016 bitExpert AG (http://bitexpert.de)
 *
 */

Ext.ns('Tine', 'Tine.Timetracker');

/**
 * @namespace   Tine.Timetracker
 * @class       Tine.Timetracker.TreePanel
 * @extends     Tine.Tinebase.Application
 * Timetracker TreePanel<br>
 *
 * @author Michael Spahn <M.Spahn@bitExpert.de>
 *
 */
Tine.Timetracker.TimeaccountFavoritesPanel = function (config) {
    Ext.apply(this, config);
    Tine.Timetracker.TimeaccountFavoritesPanel.superclass.constructor.call(this);
};

Ext.extend(Tine.Timetracker.TimeaccountFavoritesPanel, Ext.tree.TreePanel, {
    /**
     * Autoscrolling
     */
    autoScroll: true,

    /**
     * Border
     */
    border: false,

    /**
     * Context menu for leaf
     */
    contextMenuLeaf: null,

    /**
     * Delete fav action
     */
    action_delete: null,

    /**
     * The current node of context menu
     */
    ctxNode: null,

    /**
     * init this treePanel
     */
    initComponent: function () {
        if (!this.app) {
            this.app = Tine.Tinebase.appMgr.get('Timetracker');
        }

        var favorites = this.getTimetrackerNodes();

        this.root = {
            path: '/',
            cls: 'tinebase-tree-hide-collapsetool',
            text: this.app.i18n._('Timeaccount Favorites'),
            iconCls: 'folder',
            expanded: true,
            children: favorites
        };

        this.initContextMenu();

        this.on('dblclick', this.onDoubleClick, this);
        this.on('contextmenu', this.onContextMenu, this);

        Tine.Timetracker.TimeaccountFavoritesPanel.superclass.initComponent.call(this);
    },

    /**
     * Set up context menu
     */
    initContextMenu: function () {
        this.action_delete = new Ext.Action({
            text: String.format(i18n.ngettext('Delete {0}', 'Delete {0}', 1), this.app.i18n._('Favorite')),
            iconCls: 'action_delete',
            handler: this.deleteNode,
            scope: this,
            allowMultiple: false
        });

        this.contextMenuLeaf = new Ext.menu.Menu({
            items: [
                this.action_delete
            ]
        });
    },

    /**
     * Delete selected favorite
     *
     * @returns {boolean}
     */
    deleteNode: function() {
        if (!this.ctxNode.attributes && !this.ctxNode.attributes.favId) {
            return false;
        }

        Ext.Ajax.request({
            url: 'index.php',
            scope: this,
            params: {
                method: 'Timetracker.deleteTimeAccountFavorite',
                favId: this.ctxNode.attributes.favId
            },
            success : function(_result, _request) {
                if (_result.responseText) {
                    Tine.Timetracker.registry.set('timeaccountFavorites', JSON.parse(_result.responseText).timeaccountFavorites);

                    var rootNode = Ext.getCmp('TimeaccountFavoritesPanel').getRootNode();

                    if (!rootNode.hasChildNodes()) {
                        rootNode.removeAll();
                    }

                    rootNode.removeAll();
                    rootNode.appendChild(Tine.Timetracker.registry.get('timeaccountFavorites'));

                    rootNode.expand();
                }
            },
            failure: function(result) {
                Tine.Tinebase.ExceptionHandler.handleRequestException(result);
            }
        });
    },

    /**
     * @param node
     * @param event
     * @returns {boolean}
     */
    onContextMenu: function (node, event) {
        if (!node.leaf) {
            return false;
        }

        this.ctxNode = node;

        this.contextMenuLeaf.showAt(event.getXY());
    },

    /**
     * get core data nodes
     ** @returns Array
     */
    getTimetrackerNodes: function () {
        if (!Tine.Timetracker.registry.get('timeaccountFavorites')) {
            return [];
        }

        return Tine.Timetracker.registry.get('timeaccountFavorites');
    },

    /**
     * @param node
     * @param e
     * @returns {boolean}
     */
    onDoubleClick: function (node, e) {
        if (!node.leaf) {
            return false;
        }

        var record = new Tine.Timetracker.Model.Timesheet(Tine.Timetracker.Model.Timesheet.getDefaultData(), 0);
        record.set('timeaccount_id', node.attributes.timeaccount);

        var popupWindow = Tine.Timetracker.TimesheetEditDialog.openWindow({
                plugins: null,
                record: record,
                recordId: null
            }
        );
    }
});
