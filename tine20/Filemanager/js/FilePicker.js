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
 * Standalone file picker for selecting a node or a folder from _Filemanager_.
 * If you need a generic picker including up/download, attachments, ... use {Tine.Tinebase.widgets.file.SelectionDialog}
 */
Tine.Filemanager.FilePicker = Ext.extend(Ext.Container, {
    /**
     * @cfg {String} mode one of source|target
     */
    mode: 'source',

    /**
     * @cfg {Boolean} allowMultiple
     * allow to select multiple fiels at once (source mode only)
     */
    allowMultiple: true,

    /**
     * @cfg {String|RegExp}
     * A constraint allows to alter the selection behaviour of the picker, for example only allow to select files.
     * By default, file and folder are allowed to be selected, the concrete implementation needs to define it's purpose
     */
    constraint: null,

    /**
     * @cfg {Array} requiredGrants
     * grants which are required to select nodes
     */
    requiredGrants: null,

    /**
     * @cfg {String} fileName
     * @property {String} fileName
     * (initial) fileName
     */
    fileName: null,

    /**
     * initial path
     * @cfg {String} initialPath
     */
    initialPath: null,
    
    /**
     * @cfg {String} files
     * @property {String} files
     * (initial) files
     * files to be saved
     */
    files: [],
    
    // private
    app: null,
    layout: 'fit',
    treePanel: null,
    gridPanel: null,
    selection: [],

    /**
     * Constructor.
     */
    initComponent: function () {
        this.allowMultiple = this.hasOwnProperty('singleSelect') ? ! this.singleSelect : this.allowMultiple;
        this.requiredGrants = this.requiredGrants ? this.requiredGrants : (this.mode === 'source' ? ['readGrant'] : ['editGrant']);

        if (_.get(this.files, '[0].data')) {
            this.files = _.map(this.files, 'data');
        }
        _.each(this.files, (file) => {
            file.filename = file.filename || file.name;
        });

        if (!this.fileName && this.files.length === 1) {
            this.fileName = this.files[0]?.filename;
        }
        
        this.allowCreateNewFile = this.mode === 'target' && this.constraint !== 'folder' && (this.fileName || this.files.length === 1);
        
        var model = Tine.Filemanager.Model.Node;
        this.app = Tine.Tinebase.appMgr.get(model.getMeta('appName'));

        this.treePanel = this.getTreePanel();
        this.gridPanel = this.getGridPanel();

        this.addEvents(
            /**
             * @event nodeSelected
             * Fires when a file was selected which fulfills all constraints
             * @param nodeData selected node data
             */
            'nodeSelected',
            /**
             * @event forceNodeSelected
             * Fires when node was force selected (e.g. by dbl click)
             * @param nodeData selected node data
             */
            'forceNodeSelected',
            /**
             * @event invalidNodeSelected
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
                autoScroll: true,
                collapseMode: 'mini',
                items: [
                    this.westPanel = new Tine.widgets.mainscreen.WestPanel({
                        app: this.app,
                        contentType: 'Node',
                        NodeTreePanel: this.treePanel,
                        gridPanel: this.gridPanel,
                        // @todo needs filterToolBar to clear filter
                        hasFavoritesPanel: false
                    })
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
                hidden: !this.allowCreateNewFile,
                layout: 'hbox',
                height: 38,
                frame: true,
                defaults: {
                    height: 38,
                    border: false,
                    frame: true
                },
                items: [{
                    flex: 1,
                }, {
                    layout: 'form',
                    labelAlign: 'left',
                    width: 450,
                    frame: true,
                    items: {
                        xtype: 'textfield',
                        ref: '../../../fileNameField',
                        fieldLabel: 'Save as',
                        value: this.fileName,
                        width: 300,
                        enableKeyEvents: true,
                        validate: Ext.emptyFn,
                        listeners: {
                            keyup: this.checkState.createDelegate(this),
                            specialkey: (field, e) => {
                                if (e.getKey() === e.ENTER && this.validSelection) {
                                    this.fireEvent('forceNodeSelected', this.selection);
                                }
                            },
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
                }, {
                    flex: 1,
                    cls: 'x-form'
                }]
            }]
        }];
        
        Tine.Filemanager.FilePicker.superclass.initComponent.call(this);
    },

    afterRender: function() {
        Tine.Filemanager.FilePicker.superclass.afterRender.call(this);
    },

    checkState: function() {
        const field = this.fileNameField;
        const fileName = field.getValue();
        const basePathNodeRecord = this.treePanel.getSelectedContainer();
        const basePath = basePathNodeRecord?.path;
        
        if (!this.allowCreateNewFile) return;

        const node = {
            id: 'newFile',
            type: 'file',
            name: fileName,
            path: `${basePath}${fileName}`,
            recordId: basePathNodeRecord?.id
        };
        this.selection = [node];

        if(basePath && this.checkConstraint([node])) {
            if (this.selection[0] && this.selection[0].type === 'file') {
                field.clearInvalid();
                this.fileName = fileName;
                this.selection = [node];
                this.validSelection = true;
                this.assertRowSelection();
                this.fireEvent('nodeSelected', this.selection);
            }
        } else {
            this.selection = [];
            this.validSelection = false;
            this.fireEvent('invalidNodeSelected');
        }
    },

    /**
     * Updates selected element and triggers an event
     */
    updateSelection: function (nodes) {
        // If selection doesn't fullfil constraint, we don't throw a selectionChange event
        if (!this.checkConstraint(nodes)) {
            this.validSelection = false;
            this.fireEvent('invalidNodeSelected');
            return false;
        }

        //  Clear previous selection
        this.selection = [];
        
        _.each(nodes, (node) => {
            this.selection.push(node.data || node);
        });
        
        if (this.selection?.[0]?.path) {
            this.targetPath = this.selection[0].path;
    
            let interval = null;
            interval = window.setInterval(() => {
                const sm = this.treePanel.getSelectionModel();
                const selectedNode = sm.getSelectedNode();
                if (_.get(selectedNode, 'attributes.path') === this.targetPath) {
                    window.clearInterval(interval);
                    this.checkState();
                }
            }, 100)
        }

        if (this.mode === 'target' && this.selection.length) {
            if (this.selection[0].type === 'file') {
                this.fileNameField.setValue(this.selection[0].name);
            }
        }

        this.validSelection = true;
        this.fireEvent('nodeSelected', this.selection);
    },
    
    onNodeDblClick: function() {
        const gridPanel = this.getGridPanel();
        const selectionModel = gridPanel.getGrid().getSelectionModel();
        const record = selectionModel.getSelections();
        this.updateSelection(record);
        
        if (this.validSelection && this.selection[0].type !== 'folder') {
            this.fireEvent('forceNodeSelected', this.selection);
            return false;
        }
    },
    
    /**
     * @returns {Tine.Filemanager.NodeTreePanel}
     */
    getTreePanel: function () {
        if (this.treePanel) {
            return this.treePanel;
        }
        this.treePanel = new Tine.Filemanager.NodeTreePanel({
            height: 200,
            width: 200,
            readOnly: true,
            filterMode: 'filterToolbar',
            // fixme: NodeTreePanel fetches grid via app registry
            onSelectionChange: this.onTreeSelectionChange.createDelegate(this),
        });
    
        let interval = null;
        interval = window.setInterval(() => {
            const sm = this.treePanel.getSelectionModel();
            const selectedNode = sm.getSelectedNode();
            if (_.get(selectedNode, 'attributes.path') === this.initialPath) {
                window.clearInterval(interval);
                this.checkState();
            }
        }, 100)
        
        return this.treePanel;
    },
    
    onTreeSelectionChange(sm, nodes) {
        Tine.widgets.container.TreePanel.prototype.onSelectionChange.apply(this.treePanel, arguments);
        const treeNode = sm.getSelectedNode();
        this.updateSelection([
            _.get(treeNode, 'attributes')
        ]);
    },

    assertRowSelection: function() {
        const gridPanel = this.getGridPanel();
        const selectionModel = gridPanel.getGrid().getSelectionModel();
        const store = gridPanel.getStore();
        const fileIdx = store.find('name', this.fileName);
        
        if (fileIdx >= 0) {
            selectionModel.selectRow(fileIdx);
        } else {
            selectionModel.clearSelections();
        }
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
            readOnly: this.mode !== 'target',
            onRowDblClick: Tine.Filemanager.NodeGridPanel.prototype.onRowDblClick.createInterceptor(this.onNodeDblClick, this),
            enableDD: false,
            enableDrag: false,
            treePanel: this.getTreePanel(),
            hasQuickSearchFilterToolbarPlugin: false,
            stateIdSuffix: '-FilePicker',
            defaultFilters: defaultFilters,
            plugins: [this.getTreePanel().getFilterPlugin()]
        });

        if (this.allowCreateNewFile) {
            gridPanel.getStore().on('load', this.checkState, this);
        }
        
        gridPanel.getGrid().reconfigure(gridPanel.getStore(), this.getColumnModel());
        gridPanel.getGrid().getSelectionModel().singleSelect = !this.allowMultiple;
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
        let valid = true;

        _.each(nodes, (node) => {
            node = node.data ? node : new Tine.Filemanager.Model.Node(node);
            if (!this.checkNodeConstraint(node)) {
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

        if (_.isRegExp(this.constraint && node.get('type') === 'file')) {
            return node.get('path').match(this.constraint);
        }
        
        if (!Tine.Filemanager.Model.Node.isNameValid(node.get('name'))) {
            return false;
        }
        
        if (_.isFunction(this.constraint)) {
            return this.constraint(node)
        }
        
        return true;
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
        
        if (this.mode === 'target') {
            
            if (node.id === 'newFile') {
                const targetNodeData = _.get(this.treePanel.getSelectionModel().getSelectedNode(), 'attributes');
                const targetNode = targetNodeData ? new Tine.Filemanager.Model.Node(targetNodeData) : null;
                
                return targetNode && Tine.Filemanager.nodeActionsMgr.checkConstraints('create', targetNode, [node]);
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
     * helper function for users e.g. FilePickerDialog / SelectionDialog/FilemanagerPlugin
     * can be used after a final selection to check if a file exists (which might be hidden due to paging etc)
     * 
     * @return {Promise<unknown>}
     */
    validateSelection: async function(files = null) {
        if (this.mode !== 'target') {
            return true;
        }
    
        files = files ?? this.files;
        
        return new Promise(async (resolve) => {
            const loadMask = new Ext.LoadMask(this.getEl(), {
                msg: this.app.i18n._('Checking ...'),
                removeMask: true
            });
            loadMask.show();
            
            try {
                const path = _.get(this.selection, '[0].type') === 'file' ? Tine.Filemanager.Model.Node.dirname(_.get(this.selection, '[0].path')) : _.get(this.selection, '[0].path');
                const filter = [
                    {field: 'path', operator: 'equals', value: path},
                ];
                const field = this.fileNameField;
                this.fileName = field.getValue();

                const filenames = this.files.length > 1 ? _.map(files, 'filename') : [this.fileName];
                if (filenames.length > 0) filter.push({field: 'name', operator: 'in', value: filenames});
                
                const result = await Tine.Filemanager.searchNodes(filter);
                const fileCount = this.files.length;

                if (result.results.length > 0) {
                    const editDialog = this.findParentBy((c) => {return c instanceof Tine.Tinebase.dialog.Dialog});
                    const msg = this.app.formatMessage('{attachmentCount, plural, one {Do you really want to overwrite the existing file?} other {Do you really want to overwrite the existing files?}}',
                        {fileCount});
                    Ext.MessageBox.confirm(i18n._('Overwrite?'), msg, function (btn) {
                        resolve(btn === 'yes');
                    }, editDialog);
                } else {
                    resolve(true);
                }
            } catch (e) {
                // NOTE: path filter throws an error in not existent
                resolve(true);
            }
    
            loadMask.hide();
        });
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
        }, {
            id: 'creation_time',
            header: this.app.i18n._("Creation Time"),
            width: 100,
            sortable: true,
            dataIndex: 'creation_time',
            renderer: Tine.Tinebase.common.dateTimeRenderer,
            hidden: true
        },{
            id: 'last_modified_time',
            header: this.app.i18n._("Last Modified Time"),
            width: 100,
            sortable: true,
            dataIndex: 'last_modified_time',
            hidden: false,
            renderer: Tine.Tinebase.common.dateTimeRenderer
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
