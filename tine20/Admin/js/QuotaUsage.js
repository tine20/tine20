/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Admin');

Tine.Admin.QuotaUsage = Ext.extend(Ext.ux.tree.TreeGrid, {
    enableDD: false,
    border: false,

    initComponent: function() {
        var _ = window.lodash,
            comp = this;

        this.app = Tine.Tinebase.appMgr.get('Admin');

        this.loader = {
            directFn: Tine.Admin.searchQuotaNodes,
            getParams : function(node, callback, scope) {
                var path = node.getPath('name').replace(comp.getRootNode().getPath(), '/').replace(/\/+/, '/'),
                    filter = [{field: 'path', operator: 'equals', value: path}];

                if (path == '/' && comp.buttonRefreshData) {
                    comp.buttonRefreshData.disable();
                }

                return [filter];
            },
            processResponse: function(response, node, callback, scope) {
                if (comp.buttonRefreshData) {
                    comp.buttonRefreshData.enable();
                }
                response.responseData = response.responseText.results;
                
                return Ext.ux.tree.TreeGridLoader.prototype.processResponse.apply(this, arguments);
            },
            createNode: function(attr) {
                var app = Tine.Tinebase.appMgr.getById(attr.name);

                attr.i18n_name = app ? app.getTitle() : attr.name;

                return Ext.ux.tree.TreeGridLoader.prototype.createNode.apply(this, arguments);
            }
        };

        this.columns = [{
            id: 'i18n_name',
            header: this.app.i18n._("Name"),
            width: 400,
            sortable: true,
            dataIndex: 'i18n_name'
        }, {
            id: 'size',
            header: this.app.i18n._("Size"),
            width: 60,
            sortable: true,
            dataIndex: 'size',
            tpl: new Ext.XTemplate('{size:this.byteRenderer}', {
                byteRenderer: Tine.Tinebase.common.byteRenderer.createDelegate(this, [2, undefined], 3)
            })
        }];

        if (Tine.Tinebase.configManager.get('filesystem.modLogActive', 'Tinebase')) {
            this.columns.push({
                id: 'revision_size',
                header: this.app.i18n._("Revision Size"),
                tooltip: this.app.i18n._("Total size of all available revisions"),
                width: 60,
                sortable: true,
                dataIndex: 'revision_size',
                tpl: new Ext.XTemplate('{revision_size:this.byteRenderer}', {
                    byteRenderer: Tine.Tinebase.common.byteRenderer.createDelegate(this, [2, undefined], 3)
                })
            });
        }

        this.tbar = [{
            ref: '../buttonRefreshData',
            tooltip: Ext.PagingToolbar.prototype.refreshText,
            iconCls: "x-tbar-loading",
            handler: this.onButtonRefreshData,
            scope: this
        }];

        this.on('beforecollapsenode', this.onBeforeCollapse, this);

        this.supr().initComponent.apply(this, arguments);
    },

    /**
     * require reload when node is collapsed
     */
    onBeforeCollapse: function(node) {
        node.removeAll();
        node.loaded = false;
    },

    onButtonRefreshData: function() {
        this.getRootNode().reload();
    }
});

Tine.Admin.registerItem({
    // _('Quota Usage')
    text: 'Quota Usage',
    iconCls: 'admin-node-quota-usage',
    pos: 750,
    viewRight: 'view_quota_usage',
    panel: Tine.Admin.QuotaUsage
});