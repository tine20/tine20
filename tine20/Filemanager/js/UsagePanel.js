/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Filemanager');

/**
 * @namespace   Tine.Filemanager
 * @class       Tine.Filemanager.UsagePanel
 * @extends     Ext.Panel
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 *
 * @param       {Object} config
 * @constructor
 */
Tine.Filemanager.UsagePanel = Ext.extend(Ext.Panel, {

    layout: 'fit',
    border: false,

    initComponent: function() {
        this.app = this.app || Tine.Tinebase.appMgr.get('Filemanager');
        this.title = this.app.i18n._('Usage');

        this.byTypeStore = new Ext.data.ArrayStore({
            fields: [
                {name: 'type'},
                {name: 'size', type: 'int'},
                {name: 'revision_size', type: 'int'}
            ]
        });

        this.byUserStore = new Ext.data.ArrayStore({
            fields: [
                {name: 'user'},
                {name: 'size', type: 'int'},
                {name: 'revision_size', type: 'int'}
            ]
        });

        var bytesRenderer = Tine.Tinebase.common.byteRenderer.createDelegate(this, [2, undefined], 3),
            columns = [
            {header: this.app.i18n._('Size'), width: 150, sortable: true, renderer: bytesRenderer, dataIndex: 'size'}
        ];

        if (Tine.Tinebase.configManager.get('filesystem.modLogActive', 'Tinebase')) {
            columns.push({header: this.app.i18n._('Revision Size'), width: 150, sortable: true, renderer: bytesRenderer, dataIndex: 'revision_size'});
        }

        this.byTypeGrid = new Ext.grid.GridPanel({
            store: this.byTypeStore,
            columns: [
                {id:'type',header: this.app.i18n._('File Type'), width: 160, sortable: true, dataIndex: 'type'},
            ].concat(columns),
            stripeRows: true,
            autoExpandColumn: 'type',
            title: this.app.i18n._('Usage by File Type'),
            // baseCls: 'ux-arrowcollapse'
        });

        this.byUserGrid = new Ext.grid.GridPanel({
            store: this.byUserStore,
            columns: [
                {id:'user',header: this.app.i18n._('User'), width: 160, sortable: true, dataIndex: 'user'},
            ].concat(columns),
            stripeRows: true,
            autoExpandColumn: 'user',
            title: this.app.i18n._('Usage by User'),
            // baseCls: 'ux-arrowcollapse'
        });

        this.items = [{
            layout: 'vbox',
            border: false,
            defaults: {flex: 1},
            items: [this.byTypeGrid, this.byUserGrid]
        }];
        this.supr().initComponent.call(this);
    },

    afterRender: function() {
        this.supr().afterRender.call(this);

        var editDialog = this.findParentBy(function(c){return !!c.record}),
            record = editDialog ? editDialog.record : {};

        Tine.Filemanager.getFolderUsage(record.id, this.onFolderUsageLoad.createDelegate(this));
    },

    onFolderUsageLoad: function(response) {
        var _ = lodash,
            me = this,
            userNameMap = {};

        _.forEach(_.get(response, 'contacts', []), function(contact) {
            userNameMap[contact.id] = contact;
        });

        me.byTypeStore.loadData(_.map(_.get(response, 'type', []), function(o, type) {
            return [type, o.size, o.revision_size];
        }));

        me.byUserStore.loadData(_.map(_.get(response, 'createdBy', []), function(o, user) {
            return [lodash.get(userNameMap, user + '.n_fn' , me.app.i18n._('unknown')), o.size, o.revision_size];
        }));

    }
});

Ext.reg('Tine.Filemanager.UsagePanel', Tine.Filemanager.UsagePanel);