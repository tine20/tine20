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
 */
Tine.Filemanager.FilePicker = Ext.extend(Ext.Container, {
    /**
     * Filemanager app
     * @private
     */
    app: null,

    /**
     * Layout.
     * @private
     */
    layout: 'fit',

    /**
     * NodeTreePanel instance
     * @private
     */
    treePanel: null,

    /**
     * NodeGridPanel instance
     * @private
     */
    gridPanel: null,

    /**
     * Selected nodes
     * @private
     */
    selection: [],

    /**
     * Last clicked node
     * @private
     */
    lastClickedNode: null,

    /**
     * Allow to select one or more node
     */
    singleSelect: true,

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
     * @cfg {Array} requiredGrants
     * grants which are required to select nodes
     */
    requiredGrants: ['readGrant'],

    /**
     * allow creation of new files
     * @cfg {Boolean} allowCreateNew
     */
    allowCreateNew: false,

    /**
     * initial fileName for new files
     * @cfg {String} initialNewFileName
     */
    initialNewFileName: '',

    /**
     * initial path
     * @cfg {String} initialPath
     */
    initialPath: null,

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
                header: false,
                collapseMode: 'mini',
                items: [
                    /*
                    @todo needs filterToolBar to clear filter
                    new Tine.widgets.mainscreen.WestPanel({
                        app: this.app,
                        contentType: 'Node',
                        NodeTreePanel: this.treePanel,
                        gridPanel: this.gridPanel
                    })
                    */
                    this.treePanel
                ]
            }, {
                layout: 'fit',
                split: true,
                frame: false,
                border: false,
                region: 'center',
                width: 300,
                items: [
                    this.gridPanel
                ]
            }, {
                region: 'north',
                border: false,
                hidden: !this.allowCreateNew,
                layout: 'vbox',
                // height: 58,
                height: 32,
                items: [{
                    layout: 'form',
                    labelAlign: 'left',
                    frame: true,
                    items: {
                        xtype: 'textfield',
                        ref: '../../../fileNameField',
                        fieldLabel: 'Save as',
                        value: this.initialNewFileName,
                        width: 300,
                        enableKeyEvents: true,
                        validate: Ext.emptyFn,
                        listeners: {
                            keyup: this.onFileNameFieldChange.createDelegate(this),
                            focus: (field) => {
                                const value = String(field.getValue());
                                let end = null;
                                if (_.isRegExp(this.constraint)) {
                                    const match = value.match(this.constraint);
                                    if (match) {
                                        end = match.index
                                    }
                                }

                                field.focus();
                                field.selectText(0, end);
                            }
                        }
                    }
                }/*, { // @TODO: must somehow cope with tree and grid selections...
                    xtype: 'toolbar',
                    items: [
                        this.gridPanel.action_createFolder
                    ]
                }*/],
            }]
        }];


        Tine.Filemanager.FilePicker.superclass.initComponent.call(this);
    },

    afterRender: function() {
        Tine.Filemanager.FilePicker.superclass.afterRender.call(this);
    },

    onFileNameFieldChange: function(field, e) {
        this.gridPanel.selectionModel.clearSelections();

        const basePath = _.get(this.treePanel.getSelectedContainer(), 'path')
        const node = new Tine.Filemanager.Model.Node({
            id: 'newFile',
            type: 'file',
            name: field.getValue(),
            path: basePath + '/' + field.getValue()
        });

        if(basePath && this.checkConstraint([node])) {
            field.clearInvalid();
            this.selection = [node];
            this.fireEvent('nodeSelected', this.selection);
        } else {
            field.markInvalid(_('Invalid Filename'));
            this.selection = [];
            this.fireEvent('invalidNodeSelected');
        }
    },

    /**
     * Updates selected element and triggers an event
     */
    updateSelection: function (nodes) {
        // If selection doesn't fullfil constraint, we don't throw a selectionChange event
        if (!this.checkConstraint(nodes)) {
            this.fireEvent('invalidNodeSelected');
            return false;
        }

        //  Clear previous selection
        this.selection = [];

        var me = this;
        Ext.each(nodes, function (node) {
            me.selection.push(node.data || node);
        });

        if (this.allowCreateNew && this.selection.length) {
            this.fileNameField.setValue(this.selection[0].name);
        }

        this.fireEvent('nodeSelected', this.selection);
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
            filterMode: 'filterToolbar',
            // fixme: NodeTreePanel fetches grid via app registry
            onSelectionChange: Tine.widgets.container.TreePanel.prototype.onSelectionChange
        });

        treePanel.getSelectionModel().on('selectionchange', function (selectionModel) {
            var treeNode = selectionModel.getSelectedNode();
            me.updateSelection([
                treeNode.attributes
            ]);
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

        let defaultFilters =  this.initialPath ? [
            {field: 'query', operator: 'contains', value: ''},
            {field: 'path', operator: 'equals', value: this.initialPath}
        ] : null;

        var gridPanel = new Tine.Filemanager.NodeGridPanel({
            app: me.app,
            height: 200,
            width: 200,
            border: false,
            frame: false,
            readOnly: !this.allowCreateNew,
            enableDD: false,
            enableDrag: false,
            treePanel: this.getTreePanel(),
            hasQuickSearchFilterToolbarPlugin: false,
            stateIdSuffix: '-FilePicker',
            defaultFilters: defaultFilters,
            plugins: [this.getTreePanel().getFilterPlugin()]
        });

        gridPanel.getGrid().reconfigure(gridPanel.getStore(), this.getColumnModel());
        gridPanel.getGrid().getSelectionModel().singleSelect = this.singleSelect;
        gridPanel.getGrid().getSelectionModel().on('rowselect', function (selModel) {
            var record = selModel.getSelections();
            me.updateSelection(record);
        });

        // Hide filter toolbar
        gridPanel.filterToolbar.hide();

        return gridPanel;
    },

    /**
     * Check if selection fits current constraint
     * @returns {boolean}
     */
    checkConstraint: function (nodes) {
        var me = this;
        var valid = true;

        Ext.each(nodes, function (node) {
            node = node.data ? node : new Tine.Filemanager.Model.Node(node);
            if (!me.checkNodeConstraint(node)) {
                valid = false;
                return false;
            }
        });

        return valid;
    },

    /**
     * Checks if a single node matches the constraints
     *
     * @param node
     * @returns {boolean}
     */
    checkNodeConstraint: function (node) {
        // Minimum information to proceed here
        if (!node.get('path') || !node.id) {
            return false;
        }

        if (! this.hasGrant(node, this.requiredGrants)) {
            return false;
        }

        // If no constraints apply, skip here
        if (this.constraint === null) {
            return true;
        }

        if (_.isString(this.constraint)) {
            if (this.constraint.match(/file|folder/)) {
                return node.get('type') === this.constraint;
            }

            var ext = node.get('path').split('.').pop(),
                allowedExts = this.constraint.split('|');

            return _.indexOf(allowedExts, ext) >= 0;
        }

        if (_.isRegExp(this.constraint)) {
            return node.get('path').match(this.constraint);
        }
    },

    /**
     * checkes if user has requested grant for given node
     *
     * @param {Tine.Filemanager.Model.Node} node
     * @param {Array} grants
     * @return bool
     */
    hasGrant: function(node, grants) {
        var _ = window.lodash,
            condition = true;

        if (this.allowCreateNew) {
            if (node.id === 'newFile') {
                node = new Tine.Filemanager.Model.Node(this.treePanel.getSelectedContainer());
                grants = ['addGrant'];
            } else {
                grants = ['editGrant'];
            }
        }

        _.each(grants, function(grant) {
            condition = condition && _.get(node, 'data.account_grants.' + grant, false);
            if (grant === 'addGrant' && node.isVirtual()) {
                condition = false;
            }
        });

        return condition;
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
            header: this.app.i18n._("Content type"),
            width: 50,
            sortable: true,
            dataIndex: 'contenttype',
            renderer: function (value, metadata, record) {

                var app = Tine.Tinebase.appMgr.get('Filemanager');
                if (record.data.type === 'folder') {
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
