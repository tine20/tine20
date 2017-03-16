/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Filemanager');

/**
 * FilePicker component.
 *
 * Standalone file picker for selecting a node or a folder from filemanager.
 * The filepicker does require the filemanager to be enabled!
 *
 * The filepicker offers two events:
 *  - nodeSelected
 *  - invalidNodeSelected
 *
 *  @todo: remove border
 */
Tine.Filemanager.FilePicker = Ext.extend(Ext.Container, {
    app: null,

    layout: 'fit',

    treePanel: null,
    gridPanel: null,

    /**
     * Selected node
     */
    selection: null,

    lastClickedNode: null,

    /**
     * A constraint allows to alter the selection behaviour of the picker, for example only allow to select files.
     *
     * By default, file and folder are allowed to be selected, the concrete implementation needs to define it's purpose
     *
     * Valids constraints:
     *  - file
     *  - folder
     *  - null (take all)
     */
    constraint: null,

    /**
     * Constructor.
     */
    initComponent: function () {
        var model = Tine.Filemanager.Model.Node;
        this.app = Tine.Tinebase.appMgr.get(model.getMeta('appName'));

        this.treePanel = this.getTreePanel();
        this.gridPanel = this.getGridPanel();

        this.addEvents(
            /**
             * Fires when a file was selected which fulfills all constraints
             *
             * @param nodeData selected node data
             */
            'nodeSelected',
            /**
             * Fires if a node is selected which does not fulfill the provided constraints
             */
            'invalidNodeSelected'
        );

        this.items = [{
            layout: 'border',
            border: false,
            frame: false,
            items: [{
                layout: 'fit',
                region: 'west',
                frame: false,
                width: 200,
                border: false,
                split: true,
                collapsible: true,
                collapseMode: 'mini',
                items: [
                    this.treePanel
                ]
            }, {
                layout: 'fit',
                split: true,
                frame: false,
                region: 'center',
                width: 300,
                items: [
                    this.gridPanel
                ]
            }]
        }];

        Tine.Filemanager.FilePicker.superclass.initComponent.call(this);
    },

    /**
     * Updates selected element and triggers an event
     */
    updateSelection: function (node) {
        // If selection doesn't fullfil constraint, we don't throw a selectionChange event
        if (!this.checkConstraint(node)) {
            this.fireEvent('invalidNodeSelected');
            return false;
        }

        this.selection = node.data || node;

        this.fireEvent('nodeSelected', this, this.selection);
    },

    /**
     * @returns {Tine.Filemanager.NodeTreePanel}
     */
    getTreePanel: function () {
        if (this.treePanel) {
            return this.treePanel;
        }

        var me = this;
        var treePanel = new Tine.Filemanager.NodeTreePanel({
            height: 200,
            width: 200,
            readOnly: true,
            filterMode: 'filterToolbar'
        });

        treePanel.getSelectionModel().on('selectionchange', function (selectionModel, treeNode) {
            var treeNode = selectionModel.getSelectedNode();
            me.updateSelection(treeNode.attributes);
        });

        return treePanel;
    },

    /**
     * @returns {*}
     */
    getGridPanel: function () {
        if (this.gridPanel) {
            return this.gridPanel;
        }

        var me = this;

        var gridPanel = new Tine.Filemanager.NodeGridPanel({
            app: me.app,
            height: 200,
            width: 200,
            readOnly: true,
            hasQuickSearchFilterToolbarPlugin: false,
            stateIdPrefix: '-FilePicker',
            plugins: [this.getTreePanel().getFilterPlugin()]
        });
        gridPanel.getGrid().reconfigure(gridPanel.getStore(), this.getColumnModel());
        gridPanel.getGrid().getSelectionModel().on('rowselect', function (selModel) {
            var record = selModel.getSelected();
            me.updateSelection(record.data);
        });

        return gridPanel;
    },

    /**
     * Check if selection fits current constraint
     */
    checkConstraint: function (node) {
        // Minimum information to proceed here
        if (!node.path || !node.id) {
            return false;
        }

        // If no constraints apply, skip here
        if (this.constraint === null) {
            return true;
        }

        switch (this.constraint) {
            case 'file':
                if (node.type !== 'file') {
                    return false;
                }
                break;
            case 'folder':
                if (node.type !== 'folder') {
                    return false;
                }
                break;
        }

        return true;
    },

    /**
     * Customized column model for the grid
     * @returns {*}
     */
    getColumnModel: function () {
        var columns = [{
            id: 'name',
            header: this.app.i18n._("Name"),
            width: 70,
            sortable: true,
            dataIndex: 'name',
            renderer: Ext.ux.PercentRendererWithName
        }, {
            id: 'size',
            header: this.app.i18n._("Size"),
            width: 40,
            sortable: true,
            dataIndex: 'size',
            renderer: Tine.Tinebase.common.byteRenderer.createDelegate(this, [2, true], 3)
        }, {
            id: 'contenttype',
            header: this.app.i18n._("Contenttype"),
            width: 50,
            sortable: true,
            dataIndex: 'contenttype',
            renderer: function (value, metadata, record) {

                var app = Tine.Tinebase.appMgr.get('Filemanager');
                if (record.data.type == 'folder') {
                    return app.i18n._("Folder");
                }
                else {
                    return value;
                }
            }
        }];

        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                resizable: true
            },
            columns: columns
        });
    }
});
