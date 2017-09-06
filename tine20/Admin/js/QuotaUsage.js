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
            me = this;

        this.app = Tine.Tinebase.appMgr.get('Admin');

        this.loader = {
            directFn: Tine.Admin.searchQuotaNodes,
            // paramsAsHash: true,
            getParams : function(node, callback, scope) {
                var _ = window.lodash,
                    me = this,
                    path = node.getPath('name').replace(node.getOwnerTree().getRootNode().getPath(), '/'),
                    filter = [{field: 'path', operator: 'equals', value: path}];

                return [filter];
                // this.loadMask.show();
                Tine.Admin.searchQuotaNodes(filter, _.bind(callback, scope));
            },
            processResponse: function(response, node, callback, scope) {
                me.loadMask.hide();
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

        this.supr().initComponent.apply(this, arguments);
    },

    onRender : function(ct, position){
        this.loadMask = new Ext.LoadMask(ct, {msg: this.app.i18n._('Loading Quota Data')});
        this.loadMask.show();

        this.supr().onRender.apply(this, arguments);
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