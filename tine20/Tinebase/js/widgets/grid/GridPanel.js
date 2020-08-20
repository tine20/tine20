/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.grid');

/**
 * tine 2.0 app grid panel widget
 * 
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.GridPanel
 * @extends     Ext.Panel
 * 
 * <p>Application Grid Panel</p>
 * <p>
 * </p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new GridPanel
 */
Tine.widgets.grid.GridPanel = function(config) {
    Ext.apply(this, config);

    this.gridConfig = this.gridConfig || {};
    this.defaultSortInfo = this.defaultSortInfo || {};
    this.defaultPaging = this.defaultPaging || {
        start: 0,
        limit: 50
    };

    // allow to initialize with string
    this.recordClass = Tine.Tinebase.data.RecordMgr.get(this.recordClass);

    if (! this.app && this.recordClass) {
        this.app = Tine.Tinebase.appMgr.get(this.recordClass.getMeta('appName'));
    }

    // autogenerate stateId
    if (this.stateful !== false && ! this.stateId) {
        this.stateId = this.recordClass.getMeta('appName') + '-' + this.recordClass.getMeta('recordName') + '-GridPanel';
    }

    if (this.stateId && Ext.isTouchDevice) {
        this.stateId = this.stateId + '-Touch';
    }

    Tine.widgets.grid.GridPanel.superclass.constructor.call(this);
};

Ext.extend(Tine.widgets.grid.GridPanel, Ext.Panel, {
    /**
     * @cfg {Tine.Tinebase.Application} app
     * instance of the app object (required)
     */
    app: null,
    /**
     * @cfg {Boolean} asAdminModule is the panel in an admin context? this is passed to the edit dialog, too
     */
    asAdminModule: false,
    /**
     * @cfg {Object} gridConfig
     * Config object for the Ext.grid.GridPanel
     */
    gridConfig: null,
    /**
     * @cfg {Array} customColumnData
     * Config Array for customizing column model columns
     */
    customColumnData: null,
    /**
     * @cfg {Ext.data.Record} recordClass
     * record definition class  (required)
     */
    recordClass: null,
    /**
     * @cfg {Ext.data.DataProxy} recordProxy
     */
    recordProxy: null,
    /**
     * @cfg {Tine.widgets.grid.FilterToolbar} filterToolbar
     */
    filterToolbar: null,
    /**
     * @cfg {Boolean} evalGrants
     * should grants of a grant-aware records be evaluated (defaults to true)
     */
    evalGrants: true,
    /**
     * @cfg {Boolean} filterSelectionDelete
     * is it allowed to deleteByFilter?
     */
    filterSelectionDelete: false,
    /**
     * @cfg {Object} defaultSortInfo
     */
    defaultSortInfo: null,
    /**
     * @cfg {Object} storeRemoteSort
     */
    storeRemoteSort: true,
    /**
     * @cfg {Boolean} usePagingToolbar 
     */
    usePagingToolbar: true,
    /**
     * @cfg {Object} defaultPaging 
     */
    defaultPaging: null,
    /**
     * @cfg {Object} pagingConfig
     * additional paging config
     */
    pagingConfig: null,
    /**
     * @cfg {Tine.widgets.grid.DetailsPanel} detailsPanel
     * if set, it becomes rendered in region south 
     */
    detailsPanel: null,
    /**
     * @cfg {Array} i18nDeleteQuestion 
     * spechialised strings for deleteQuestion
     */
    i18nDeleteQuestion: null,
    /**
     * @cfg {String} i18nAddRecordAction 
     * spechialised strings for add action button
     */
    i18nAddActionText: null,
    /**
     * @cfg {String} i18nEditRecordAction 
     * specialised strings for edit action button
     */
    i18nEditActionText: null,
    /**
     * @cfg {String} i18nMoveActionText
     * specialised strings for move action button
     */
    i18nMoveActionText: null,
    /**
     * @cfg {Array} i18nDeleteRecordAction 
     * specialised strings for delete action button
     */
    i18nDeleteActionText: null,
    /**
     * Tree panel referenced to this gridpanel
     */
    treePanel: null,

    /**
     * if this resides in a editDialog, this property holds it
     * if it is so, the grid can't save records itsef, just update
     * the editDialogs record property holding these records
     * 
     * @cfg {Tine.widgets.dialog.EditDialog} editDialog
     */
    editDialog: null,
    
    /**
     * if this resides in an editDialog, this property defines the 
     * property of the record of the editDialog, holding these records
     * 
     * @type {String} editDialogRecordProperty
     */
    editDialogRecordProperty: null,
    
    /**
     * config passed to edit dialog to open from this grid
     * 
     * @cfg {Object} editDialogConfig
     */
    editDialogConfig: null,

    /**
     * the edit dialog class to open from this grid
     * 
     * @cfg {String} editDialogClass
     */
    editDialogClass: null,

    /**
     * @cfg {String} i18nEmptyText 
     */
    i18nEmptyText: null,

    /**
     * @cfg {String} newRecordIcon 
     * icon for adding new records button
     */
    newRecordIcon: null,

    /**
     * @cfg {Boolean} i18nDeleteRecordAction 
     * update details panel if context menu is shown
     */
    updateDetailsPanelOnCtxMenu: true,

    /**
     * @cfg {Number} autoRefreshInterval (seconds)
     */
    autoRefreshInterval: 300,

    /**
     * @cfg {Boolean} hasFavoritesPanel 
     */
    hasFavoritesPanel: true,
    
    /**
     * @cfg {Boolean} hasQuickSearchFilterToolbarPlugin 
     */
    hasQuickSearchFilterToolbarPlugin: true,

    /**
     * display selections helper on paging tb
     * @cfg {Boolean} displaySelectionHelper
     */
    displaySelectionHelper: true,

    /**
     * disable 'select all pages' in paging toolbar
     * @cfg {Boolean} disableSelectAllPages
     */
    disableSelectAllPages: false,

    /**
     * enable if records should be multiple editable
     * @cfg {Boolean} multipleEdit
     */
    multipleEdit: false,
    
    /**
     * set if multiple edit requires special right
     * @type {String}  multipleEditRequiredRight
     */
    multipleEditRequiredRight: null,
    
    /**
     * enable if selection of 2 records should allow merging
     * @cfg {Boolean} duplicateResolvable
     */
    duplicateResolvable: false,
    
    /**
     * @property autoRefreshTask
     * @type Ext.util.DelayedTask
     */
    autoRefreshTask: null,

    /**
     * @type Boolean
     * @property updateOnSelectionChange
     */
    updateOnSelectionChange: true,

    /**
     * @type Boolean
     * @property copyEditAction
     * 
     * TODO activate this by default
     */
    copyEditAction: false,

    /**
     * @cfg {Boolean} moveAction
     * activate moveAction
     */
    moveAction: true,

    /**
     * disable delete confirmation by default
     *
     * @type Boolean
     * @property disableDeleteConfirmation
     */
    disableDeleteConfirmation: false,

    /**
     * @type Ext.Toolbar
     * @property actionToolbar
     */
    actionToolbar: null,

    /**
     * @type Ext.ux.grid.PagingToolbar
     * @property pagingToolbar
     */
    pagingToolbar: null,

    /**
     * @type Ext.Menu
     * @property contextMenu
     */
    contextMenu: null,

    /**
     * @property lastStoreTransactionId 
     * @type String
     */
    lastStoreTransactionId: null,

    /**
     * @property editBuffer  - array of ids of records edited since last explicit refresh
     * @type Array of ids
     */
    editBuffer: null,

    /**
     * @property deleteQueue - array of ids of records currently being deleted
     * @type Array of ids
     */
    deleteQueue: null,

    /**
     * configuration object of model from application starter
     * @type object
     */
    modelConfig: null,
    
    /**
     * group grid by this property
     * 
     * @type {String}
     */
    groupField: null,
    
    /**
     * header template for the grouping view, if needed
     * 
     * @type String
     */
    groupTextTpl: null,

    /**
     * cols to exclude
     *
     * @type String[]
     * @cfg hideColumns
     */
    hideColumns: null,

    /**
     * @property selectionModel
     * @type Tine.widgets.grid.FilterSelectionModel
     */
    selectionModel: null,
    
    /**
     * add records from other applications using the split add button
     * - activated by default
     * 
     * @type Boolean
     * @property splitAddButton
     */
    splitAddButton: true,


    /**
     * do initial load (by loading default favorite) after render
     *
     * @type Boolean
     */
    initialLoadAfterRender: true,

    /**
     * add "create new record" button
     * 
     * @type Boolean
     * @property addButton
     */
    addButton: true,
    
    layout: 'border',
    border: false,
    stateful: true,

    stateIdSuffix: '',

    /**
     * Makes the grid readonly, this means, no dialogs, no actions, nothing else than selection, no dbclick
     */
    readOnly: false,

    /**
     * extend standard initComponent chain
     * 
     * @private
     */
    initComponent: function(){
        // init some translations
        this.i18nRecordName = this.i18nRecordName ? this.i18nRecordName : this.recordClass.getRecordName();
        this.i18nRecordsName = this.i18nRecordsName ? this.i18nRecordsName : this.recordClass.getRecordsName();
        this.i18nContainerName = this.i18nContainerName ? this.i18nContainerName : this.recordClass.getContainerName();
        this.i18nContainersName = this.i18nContainersName ? this.i18nContainersName : this.recordClass.getContainersName();
        
        this.i18nEmptyText = this.i18nEmptyText ||
            this.i18nContainersName
            ? String.format(i18n._("There could not be found any {0}. Please try to change your filter-criteria, view-options or the {1} you search in."), this.i18nRecordsName, (this.i18nContainersName ? this.i18nContainersName : this.i18nRecordsName))
            : String.format(i18n._("There could not be found any {0}. Please try to change your filter-criteria, view-options or change the module you search in."), this.i18nRecordsName);

        this.i18nEditActionText = this.i18nEditActionText ? this.i18nEditActionText : [String.format(i18n.ngettext('Edit {0}', 'Edit {0}', 1), this.i18nRecordName), String.format(i18n.ngettext('Edit {0}', 'Edit {0}', 2), this.i18nRecordsName)];

        this.editDialogConfig = this.editDialogConfig || {};
        this.editBuffer = [];
        this.deleteQueue = [];

        this.hideColumns = this.hideColumns || [];
        
        // init generic stuff
        if (this.modelConfig) {
            this.initGeneric();
        }
        
        this.initFilterPanel();

        this.bufferedLoadGridData = Function.createBuffered(this.loadGridData, 100, this);

        this.initStore();
        
        this.initGrid();

        // init actions
        this.actionUpdater = new Tine.widgets.ActionUpdater({
            recordClass: this.recordClass,
            evalGrants: this.evalGrants
        });

        if (!this.readOnly) {
            this.initActions();
        }

        this.initLayout();

        // for some reason IE looses split height when outer layout is layouted
        if (Ext.isIE6 || Ext.isIE7) {
            this.on('show', function() {
                if (this.layout.rendered && this.detailsPanel) {
                    var height = this.detailsPanel.getSize().height;
                    this.layout.south.split.setCurrentSize(height);
                }
            }, this);
        }

        if (this.detailsPanel) {
            this.on('resize', this.onContentResize, this, {buffer: 100});
        }

        if (this.listenMessageBus) {
            this.initMessageBus();
        }

        Tine.widgets.grid.GridPanel.superclass.initComponent.call(this);
    },

    initMessageBus: function() {
        postal.subscribe({
            channel: "recordchange",
            topic: [this.recordClass.getMeta('appName'), this.recordClass.getMeta('modelName'), '*'].join('.'),
            callback: this.onRecordChanges.createDelegate(this)
        });
    },

    /**
     * bus notified about record changes
     */
    onRecordChanges: function(data, e) {
        var existingRecord = this.store.getById(data.id);
        if (existingRecord && e.topic.match(/\.update/)) {
            // NOTE: local mode saves again (and again...)
            this.onUpdateRecord(JSON.stringify(data)/*, 'local'*/);
        } else if (existingRecord && e.topic.match(/\.delete/)) {
            this.store.remove(existingRecord);
        } else {
            // we can't evaluate the filters on client side to check compute if this affects us
            // so just lets reload
            this.bufferedLoadGridData({
                removeStrategy: 'keepBuffered'
            });
        }
        // NOTE: grid doesn't update selections itself
        this.actionUpdater.updateActions(this.grid.getSelectionModel(), this.getFilteredContainers());
    },

    /**
     * returns canonical path part
     * @returns {string}
     */
    getCanonicalPathSegment: function () {
        var pathSegment = '';
        if (this.canonicalName) {
            // simple segment e.g. when used in a dialog
            pathSegment = this.canonicalName;
        } else if (this.recordClass) {
            // auto segment
            pathSegment = [this.recordClass.getMeta('modelName'), 'Grid'].join(Tine.Tinebase.CanonicalPath.separator);
        }

        return pathSegment;
    },

    onContentResize: function() {
        // make sure details panel doesn't hide grid
        if (this.detailsPanel && this.grid) {
            var gridHeight = this.grid.getHeight(),
                detailsHeight = this.detailsPanel.getHeight();

            if (detailsHeight/2 > gridHeight) {
                var newDetailsHeight = this.getHeight() *.4;
                this.layout.south.panel.setHeight(newDetailsHeight);
                this.doLayout();
            }
        }
    },

    /**
     * initializes generic stuff when used with ModelConfiguration
     */
    initGeneric: function() {
        if (this.modelConfig) {
            
            Tine.log.debug('init generic gridpanel with config:');
            Tine.log.debug(this.modelConfig);

            // TODO move to uiConfig
            if (this.modelConfig.hasOwnProperty('multipleEdit') && (this.modelConfig.multipleEdit === true)) {
                this.multipleEdit = true;
                this.multipleEditRequiredRight = (this.modelConfig.hasOwnProperty('multipleEditRequiredRight'))
                    ? this.modelConfig.multipleEditRequiredRight
                    : null;
            }

            // TODO move to uiConfig
            if (this.modelConfig.hasOwnProperty('copyEditAction') && (this.modelConfig.copyEditAction === true)) {
                this.copyEditAction = true;
            }
        }
        
        // init generic columnModel
        this.initGenericColumnModel();
    },
    
    /**
     * initialises the filter panel 
     * 
     * @param {Object} config
     */
    initFilterPanel: function(config) {
        config = Ext.apply(config || {}, this.filterConfig);

        if (! this.filterToolbar && ! this.editDialog) {
            var filterModels = [];
            if (this.modelConfig) {
                filterModels = this.getCustomfieldFilters();
            } else if (Ext.isFunction(this.recordClass.getFilterModel)) {
                filterModels = this.recordClass.getFilterModel().concat(this.getCustomfieldFilters());
            }
            this.filterToolbar = new Tine.widgets.grid.FilterPanel(Ext.apply({}, {
                app: this.app,
                recordClass: this.recordClass,
                allowSaving: true,
                filterModels: filterModels,
                defaultFilter: this.recordClass.getMeta('defaultFilter') ? this.recordClass.getMeta('defaultFilter') : 'query',
                filters: this.defaultFilters || []
            }, config));
            
            this.plugins = this.plugins || [];
            this.plugins.push(this.filterToolbar);
        }
    },

    /**
     * initializes the generic column model on auto bootstrap
     */
    initGenericColumnModel: function() {
        var _ = window.lodash;

        if (this.modelConfig) {
            var columns = [],
                appName = this.recordClass.getMeta('appName'),
                modelName = this.recordClass.getMeta('modelName');

            Ext.each(this.modelConfig.fieldKeys, function(key) {
                var config = Tine.widgets.grid.ColumnManager.get(appName, modelName, key, 'mainScreen');
                
                // @todo thats just a hotfix!
                if (['relations', 'customfields'].indexOf(key) !== -1) {
                    return;
                }

                if (this.hideColumns.indexOf(key) !== -1) {
                    return;
                }

                if (config) {
                    columns.push(config);
                }
            }, this);
            
            if (this.modelConfig.hasCustomFields) {
                columns = columns.concat(this.getCustomfieldColumns());
            }

            if (_.find(columns, {dataIndex: 'attachments'})) {
                var attachCol = _.find(columns, {dataIndex: 'attachments'});
                _.remove(columns, attachCol);
                columns.unshift(attachCol);
            }

            _.forEachRight(_.filter(this.modelConfig.fields, {type: 'image'}), function(field) {
                var imgCol = _.find(columns, {dataIndex: field.key});
                if (imgCol) {
                    _.remove(columns, imgCol);
                    columns.unshift(imgCol);
                }
            });

            columns = columns.concat(this.getCustomColumns());
            columns = this.customizeColumns(columns);
            
            this.gridConfig.cm = new Ext.grid.ColumnModel({
                defaults: {
                    resizable: true
                },
                columns: columns
            });
        }
    },
    
    /**
     * template method to allow adding custom columns
     * 
     * @return {Array}
     */
    getCustomColumns: function() {
        return [];
    },

    /**
     * allows to customize columns
     *
     * @param columns Array
     * @returns {Array}
     */
    customizeColumns: function(columns) {
        if (this.customColumnData) {
            var _ = window.lodash;

            _.forEach(this.customColumnData, function(value) {
                var column = _.find(columns, { id: value.id });
                if (column) {
                    // apply custom cfg
                    column = Ext.applyIf(column, value);
                }
            });

            Tine.log.debug('Tine.widgets.grid.GridPanel.customizeColumns - applied custom column config:');
            Tine.log.debug(columns);
        }

        return columns;
    },
    
    /**
     * @private
     * 
     * NOTE: Order of items matters! Ext.Layout.Border.SplitRegion.layout() does not
     *       fence the rendering correctly, as such it's impotant, so have the ftb
     *       defined after all other layout items
     */
    initLayout: function() {
        this.items = [{
            ref: 'centerPanel',
            region: 'center',
            xtype: 'panel',
            layout: 'fit',
            border: false,
            tbar: this.pagingToolbar,
            items: this.grid
        }];


        // add detail panel
        if (this.detailsPanel) {

            // just in case it's a config only
            this.detailsPanel = Ext.ComponentMgr.create(this.detailsPanel);

            this.items.push({
                ref: 'southPanel',
                region: 'south',
                border: false,
                collapsible: true,
                collapseMode: 'mini',
                header: false,
                split: true,
                layout: 'fit',
                height: this.detailsPanel.defaultHeight ? this.detailsPanel.defaultHeight : 125,
                items: this.detailsPanel,
                canonicalName: 'DetailsPanel'

            });
            this.detailsPanel.doBind(this.grid);
        }

        // add filter toolbar
        if (this.filterToolbar) {
            this.items.push({
                region: 'north',
                border: false,
                autoScroll: true,
                items: this.filterToolbar,
                listeners: {
                    scope: this,
                    afterlayout: function(ct) {
                        ct.suspendEvents();
                        ct.setHeight(Math.min(120, this.filterToolbar.getHeight() + (ct.topToolbar ? ct.topToolbar.getHeight() : 0)));
                        ct.getEl().child('div[class^="x-panel-body"]', true).scrollTop = 1000000;
                        ct.ownerCt.layout.layout();
                        ct.resumeEvents();
                    }
                }
            });
        }

        if (this.ownActionToolbar) {
            this.items.push({
                region: 'north',
                height: 55,
                border: false,
                items: this.actionToolbar
            });
        }

    },

    gridPrintRenderer: function() {
        Ext.ux.Printer.print(this.getGrid());
    },
    
    /**
     * init actions with actionToolbar, contextMenu and actionUpdater
     * 
     * @private
     */
    initActions: function() {
        if (! this.newRecordIcon) {
            Ext.each((this.recordClass ? [this.recordClass.getMeta('appName') + this.recordClass.getMeta('modelName')] : []).concat([
                this.app.appName + 'IconCls',
                'ApplicationIconCls'
            ]), function(cls) {
                if (Ext.util.CSS.getRule('.' + cls)) {
                    this.newRecordIcon = cls;
                    return false;
                }
            }, this);
        }

        var services = Tine.Tinebase.registry.get('serviceMap').services;
        
        this.action_editInNewWindow = new Ext.Action({
            requiredGrant: 'readGrant',
            requiredMultipleGrant: 'editGrant',
            requiredMultipleRight: this.multipleEditRequiredRight,
            text: this.i18nEditActionText ? this.i18nEditActionText[0] : String.format(i18n._('Edit {0}'), this.i18nRecordName),
            singularText: this.i18nEditActionText ? this.i18nEditActionText[0] : String.format(i18n._('Edit {0}'), this.i18nRecordName),
            pluralText:  this.i18nEditActionText ? this.i18nEditActionText[1] : String.format(i18n.ngettext('Edit {0}', 'Edit {0}', 1), this.i18nRecordsName),
            disabled: true,
            translationObject: this.i18nEditActionText ? this.app.i18n : i18n,
            actionType: 'edit',
            handler: this.onEditInNewWindow.createDelegate(this, [{actionType: 'edit'}]),
            iconCls: 'action_edit',
            scope: this,
            allowMultiple: this.multipleEdit
        });

        this.action_editCopyInNewWindow = new Ext.Action({
            hidden: ! this.copyEditAction,
            requiredGrant: 'readGrant',
            text: String.format(i18n._('Copy {0}'), this.i18nRecordName),
            disabled: true,
            actionType: 'copy',
            handler: this.onEditInNewWindow.createDelegate(this, [{actionType: 'copy'}]),
            iconCls: 'action_editcopy',
            scope: this
        });

        this.action_addInNewWindow = (this.addButton) ? new Ext.Action({
            requiredGrant: 'addGrant',
            actionType: 'add',
            text: this.i18nAddActionText ? this.app.i18n._hidden(this.i18nAddActionText) : String.format(i18n._('Add {0}'), this.i18nRecordName),
            handler: this.onEditInNewWindow.createDelegate(this, [{actionType: 'add'}]),
            iconCls: 'action_add',
            scope: this
        }) : null;

        this.actions_print = new Ext.Action({
            requiredGrant: 'readGrant',
            text: i18n._('Print Page'),
            disabled: false,
            handler: this.gridPrintRenderer,
            iconCls: 'action_print',
            scope: this,
            allowMultiple: true
        });

        this.initDeleteAction(services);

        this.action_move = new Ext.Action({
            requiredGrant: 'editGrant',
            requiredMultipleGrant: 'editGrant',
            requiredMultipleRight: this.multipleEditRequiredRight,
            singularText: this.i18nMoveActionText ? this.i18nMoveActionText[0] : String.format(i18n.ngettext('Move {0}', 'Move {0}', 1), this.i18nRecordName),
            pluralText: this.i18nMoveActionText ? this.i18nMoveActionText[1] : String.format(i18n.ngettext('Move {0}', 'Move {0}', 1), this.i18nRecordsName),
            translationObject: this.i18nMoveActionText ? this.app.i18n : i18n,
            text: this.i18nMoveActionText ? this.i18nMoveActionText[0] : String.format(i18n.ngettext('Move {0}', 'Move {0}', 1), this.i18nRecordName),
            disabled: true,
            hidden: !this.moveAction || !this.recordClass.getMeta('containerProperty'),
            actionType: 'edit',
            handler: this.onMoveRecords,
            scope: this,
            iconCls: 'action_move',
            allowMultiple: this.multipleEdit
        });


        this.action_tagsMassAttach = new Tine.widgets.tags.TagsMassAttachAction({
            hidden:         ! this.recordClass.getField('tags'),
            selectionModel: this.grid.getSelectionModel(),
            recordClass:    this.recordClass,
            updateHandler:  this.loadGridData.createDelegate(this),
            app:            this.app
        });

        this.action_tagsMassDetach = new Tine.widgets.tags.TagsMassDetachAction({
            hidden:         ! this.recordClass.getField('tags'),
            selectionModel: this.grid.getSelectionModel(),
            recordClass:    this.recordClass,
            updateHandler:  this.loadGridData.createDelegate(this),
            app:            this.app
        });

        this.action_resolveDuplicates = new Ext.Action({
            requiredGrant: null,
            text: String.format(i18n._('Merge {0}'), this.i18nRecordsName),
                iconCls: 'action_resolveDuplicates',
                scope: this,
                handler: this.onResolveDuplicates,
                disabled: false,
                actionUpdater: function(action, grants, records) {
                    if (records && (records.length != 2)) action.setDisabled(true);
                    else action.setDisabled(false);
                }
        });

        this.initExports();
        this.initImports();

        // add actions to updater
        this.actionUpdater.addActions([
            this.action_addInNewWindow,
            this.action_editInNewWindow,
            this.action_move,
            this.action_editCopyInNewWindow,
            this.action_deleteRecord,
            this.action_tagsMassAttach,
            this.action_tagsMassDetach,
            this.action_resolveDuplicates
        ]);

        // init actionToolbar (needed for correct fitertoolbar init atm -> fixme)
        this.getActionToolbar();
    },

    initExports: function() {
        if (this.actions_export !== undefined) return;

        var _ = window.lodash,
            exportFunction = this.app.name + '.export' + this.recordClass.getMeta('modelName') + 's',
            additionalItems = [];

        // create items from available export formats (depricated -> use definitions)
        if (this.modelConfig && this.modelConfig['export']) {
            if (this.modelConfig['export'].supportedFormats) {
                Ext.each(this.modelConfig['export'].supportedFormats, function (format, i) {
                    additionalItems.unshift(new Tine.widgets.grid.ExportButton({
                        // TODO format toUpper
                        text: String.format(i18n._('Export as {0}'), format),
                        format: format,
                        iconCls: 'tinebase-action-export-' + format,
                        exportFunction: exportFunction,
                        order: i * 10,
                        gridPanel: this
                    }));
                }, this);
            }
        }

        // exports from export definitions
        this.actions_export = Tine.widgets.exportAction.getExportButton(this.recordClass, {
            exportFunction: exportFunction,
            gridPanel: this
        }, Tine.widgets.exportAction.SCOPE_MULTI, additionalItems);

        if (this.actions_export) {
            this.actionUpdater.addActions([this.actions_export]);
        }
    },

    initImports: function() {
        if (this.actions_import !== undefined) return;


        if (
                // create items from available import formats (depricated -> use definitions)
                this.modelConfig && this.modelConfig['import']
                // imports from import definitions
                || Tine.widgets.importAction.getImports(this.recordClass).length) {

            this.actions_import = new Ext.Action({
                requiredGrant: 'addGrant',
                text: i18n._('Import items'),
                disabled: false,
                handler: this.onImport,
                iconCls: 'action_import',
                scope: this,
                allowMultiple: true
            });

            this.actionUpdater.addActions([this.actions_import]);
        }

    },

    /**
     * import inventory items
     *
     * @param {Button} btn
     */
    onImport: function(btn) {
        var treePanel = this.treePanel || this.app.getMainScreen().getWestPanel().getContainerTreePanel(),
            _ = window.lodash;

        var container = _.get(this.modelConfig, 'import.defaultImportContainerRegistryKey', false);

        if (container) {
            container = treePanel.getDefaultContainer(container);
        } else if (Ext.isFunction(this.getDefaultContainer)) {
            container = this.getDefaultContainer();
        }

        Tine.widgets.dialog.ImportDialog.openWindow({
            appName: this.app.name,
            modelName: this.recordClass.getMeta('modelName'),
            defaultImportContainer: container,
            listeners: {
                scope: this,
                'finish': function() {
                    this.loadGridData({
                        preserveCursor:     false,
                        preserveSelection:  false,
                        preserveScroller:   false,
                        removeStrategy:     'default'
                    });
                }
            }
        });
    },

    /**
     * initializes the delete action
     * 
     * @param {Object} services the rpc service map from the registry
     */
    initDeleteAction: function(services) {
        // note: unprecise plural form here, but this is hard to change
        this.action_deleteRecord = new Ext.Action({
            requiredGrant: 'deleteGrant',
            allowMultiple: true,
            singularText: this.i18nDeleteActionText ? this.i18nDeleteActionText[0] : String.format(i18n.ngettext('Delete {0}', 'Delete {0}', 1), this.i18nRecordName),
            pluralText: this.i18nDeleteActionText ? this.i18nDeleteActionText[1] : String.format(i18n.ngettext('Delete {0}', 'Delete {0}', 1), this.i18nRecordsName),
            translationObject: this.i18nDeleteActionText ? this.app.i18n : i18n,
            text: this.i18nDeleteActionText ? this.i18nDeleteActionText[0] : String.format(i18n.ngettext('Delete {0}', 'Delete {0}', 1), this.i18nRecordName),
            handler: this.onDeleteRecords,
            disabled: true,
            iconCls: 'action_delete',
            scope: this
        });
        // if nested in a editDialog (dependent record), the service won't exist
        if (! this.editDialog) {
            this.disableDeleteActionCheckServiceMap(services);
        }
    },
    
    /**
     * disable delete action if no delete method was found in serviceMap
     * 
     * @param {Object} services the rpc service map from the registry
     * 
     * TODO this should be configurable as not all grids use remote delete
     */
    disableDeleteActionCheckServiceMap: function(services) {
        if (services) {
            var serviceKey = this.app.name + '.delete' + this.recordClass.getMeta('modelName') + 's';
            if (! services.hasOwnProperty(serviceKey)) {
                this.action_deleteRecord.setDisabled(1);
                this.action_deleteRecord.initialConfig.actionUpdater = function(action) {
                    Tine.log.debug("disable delete action because no delete method was found in serviceMap");
                    action.setDisabled(1);
                }
            }
        }
    },

    /**
     * init store
     * @private
     */
    initStore: function() {
        if (this.store) {
            // store is already initialized
            return;
        }

        if (this.recordProxy) {
            if (! this.defaultSortInfo.field) {
                if (this.modelConfig && this.modelConfig.defaultSortInfo) {
                    this.defaultSortInfo = this.modelConfig.defaultSortInfo;
                } else if (this.recordClass) {
                    var titleProperty = this.recordClass.getMeta('titleProperty'),
                        defaultSortField = Ext.isArray(titleProperty) ? titleProperty[1][0] : titleProperty;
                    this.defaultSortInfo = this.recordClass.hasField(titleProperty) ? {
                        field: defaultSortField,
                        order: 'DESC'
                    } : null;
                }
            }

            var storeClass = this.groupField ? Ext.data.GroupingStore : Ext.data.Store;
            this.store = new storeClass({
                fields: this.recordClass,
                proxy: this.recordProxy,
                reader: this.recordProxy.getReader(),
                remoteSort: this.storeRemoteSort,
                sortInfo: this.defaultSortInfo,
                groupField: 'month',
                listeners: {
                    scope: this,
                    'add': this.onStoreAdd,
                    'remove': this.onStoreRemove,
                    'update': this.onStoreUpdate,
                    'beforeload': this.onStoreBeforeload,
                    'load': this.onStoreLoad,
                    'beforeloadrecords': this.onStoreBeforeLoadRecords,
                    'loadexception': this.onStoreLoadException
                }
            });
        } else {
            this.store = new Tine.Tinebase.data.RecordStore({
                recordClass: this.recordClass
            });
        }

        // init autoRefresh
        this.autoRefreshTask = new Ext.util.DelayedTask(this.loadGridData, this, [{
            removeStrategy: 'keepBuffered',
            autoRefresh: true
        }]);
    },

    /**
     * returns view row class
     */
    getViewRowClass: function(record, index, rowParams, store) {
        var noLongerInFilter = record.not_in_filter;

        var className = '';
        if (noLongerInFilter) {
            className += 'tine-grid-row-nolongerinfilter';
        }
        return className;
    },    

    /**
     * new entry event -> add new record to store
     * 
     * @param {Object} recordData
     * @return {Boolean}
     */
    onStoreNewEntry: function(recordData) {
        var initialData = null;
        if (Ext.isFunction(this.recordClass.getDefaultData)) {
            initialData = Ext.apply(this.recordClass.getDefaultData(), recordData);
        } else {
            initialData = recordData;
        }
        var record = new this.recordClass(initialData);
        this.store.insert(0 , [record]);

        if (this.usePagingToolbar) {
            this.pagingToolbar.refresh.disable();
        }
        this.recordProxy.saveRecord(record, {
            scope: this,
            success: function(newRecord) {
                this.store.suspendEvents();
                this.store.remove(record);
                this.store.insert(0 , [newRecord]);
                this.store.resumeEvents();

                this.addToEditBuffer(newRecord);

                this.loadGridData({
                    removeStrategy: 'keepBuffered'
                });
            }
        });

        return true;
    },

    /**
     * adds given record and starts editing
     * 
     * @param localRecord
     * @param editProperty
     * @param proxyFn
     * @return {Promise<void>}
     */
    newInlineRecord: async function(localRecord, editProperty, proxyFn) {
        localRecord.noProxy = true;
        this.store.addUnique(localRecord, editProperty);
        this.grid.getSelectionModel().selectRow(this.store.indexOf(localRecord));
        this.grid.startEditingRecord(localRecord, editProperty);
        const ed = this.grid.activeEditor;
        ed.field.selectText(0, String(ed.field.getValue()).lastIndexOf('.'));
        ed.startValue = Math.random();

        const onCancelEdit = (e) => {
            this.grid.un('afteredit', onAfterEdit);
            this.store.remove(localRecord);
        };
        ed.on('canceledit', onCancelEdit, this, {single: true});

        const onAfterEdit = async (o) => {
            ed.un('canceledit', onCancelEdit);

            this.pagingToolbar.refresh.disable();
            this.store.remove(localRecord);
            this.store.addSorted(localRecord);
            this.grid.getSelectionModel().selectRow(this.store.indexOf(localRecord));
            
            const remoteRecord = await proxyFn(localRecord);
            this.store.remove(localRecord);
            this.store.addSorted(remoteRecord);
            this.grid.getSelectionModel().selectRow(this.store.indexOf(remoteRecord));
            this.pagingToolbar.refresh.enable();
        }
        this.grid.on('afteredit', onAfterEdit, this, {single: true, buffer: 100});
    },
    
    /**
     * header is clicked
     * 
     * @param {Object} grid
     * @param {Number} colIdx
     * @param {Event} e
     * @return {Boolean}
     */
    onHeaderClick: function(grid, colIdx, e) {

        Ext.apply(this.store.lastOptions, {
            preserveCursor:     true,
            preserveSelection:  true, 
            preserveScroller:   true, 
            removeStrategy:     'default'
        });
    },

    /**
     * called when Records have been added to the Store
     */
    onStoreAdd: function(store, records, index) {
        this.store.totalLength += records.length;
        if (this.pagingToolbar) {
            this.pagingToolbar.updateInfo();
        }
    },
    
    /**
     * called when a Record has been removed from the Store
     */
    onStoreRemove: function(store, record, index) {
        this.store.totalLength--;
        if (this.pagingToolbar) {
            this.pagingToolbar.updateInfo();
        }
    },
    
    /**
     * called when the store gets updated, e.g. from editgrid
     * 
     * @param {Ext.data.store} store
     * @param {Tine.Tinebase.data.Record} record
     * @param {String} operation
     */
    onStoreUpdate: function(store, record, operation) {
        if (record.noProxy) return;
        switch (operation) {
            case Ext.data.Record.EDIT:
                this.addToEditBuffer(record);
                
                if (this.usePagingToolbar) {
                    this.pagingToolbar.refresh.disable();
                }
                // don't save these records. Add them to the parents' record store
                if (this.editDialog) {
                    var items = [];
                    store.each(function(item) {
                        items.push(item.data);
                    });
                    
                    this.editDialog.record.set(this.editDialogRecordProperty, items);
                    this.editDialog.fireEvent('updateDependent');
                } else if (this.recordProxy) {
                    this.recordProxy.saveRecord(record, {
                        scope: this,
                        success: function(updatedRecord) {
                            store.commitChanges();
    
                            // update record in store to prevent concurrency problems
                            record.data = updatedRecord.data;
    
                            this.loadGridData({
                                removeStrategy: 'keepBuffered'
                            });
                        }
                    });
                    break;
                }
            case Ext.data.Record.COMMIT:
                //nothing to do, as we need to reload the store anyway.
                break;
        }
    },

    /**
     * called before store queries for data
     */
    onStoreBeforeload: function(store, options) {

        // define a transaction
        this.lastStoreTransactionId = options.transactionId = Ext.id();

        options.params = options.params || {};
        // always start with an empty filter set!
        // this is important for paging and sort header!
        options.params.filter = [];

        if (! options.removeStrategy || options.removeStrategy !== 'keepBuffered') {
            this.editBuffer = [];
        }

        if (! options.preserveSelection) {
            this.actionUpdater.updateActions([]);
        }

        // fix nasty paging tb
        Ext.applyIf(options.params, this.defaultPaging);
    },

    /**
     * called after a new set of Records has been loaded
     * 
     * @param  {Ext.data.Store} this.store
     * @param  {Array}          loaded records
     * @param  {Array}          load options
     * @return {Void}
     */
    onStoreLoad: function(store, records, options) {
        // we always focus the first row so that keynav starts in the grid
        // this resets scroller ;-( -> need a better solution
//        if (this.store.getCount() > 0) {
//            this.grid.getView().focusRow(0);
//        }

        // restore selection
        if (Ext.isArray(options.preserveSelection)) {
            Ext.each(options.preserveSelection, function(record) {
                var row = this.store.indexOfId(record.id);
                if (row >= 0) {
                    this.grid.getSelectionModel().selectRow(row, true);
                }
            }, this);
        }
        this.actionUpdater.updateActions(this.grid.getSelectionModel(), this.getFilteredContainers());

        // restore scroller
        if (Ext.isNumber(options.preserveScroller)) {
            this.grid.getView().scroller.dom.scrollTop = options.preserveScroller;
        }

        // reset autoRefresh
        if (window.isMainWindow && this.autoRefreshInterval) {
            this.autoRefreshTask.delay(this.autoRefreshInterval * 1000);
        }
    },

    /**
     * gets currently displayed container in case a container filter is set
     * NOTE: this data is unresolved as it comes from filter and not through json convert!
     */
    getFilteredContainers: function() {
        const containerFilter = _.find(_.get(this, 'store.reader.jsonData.filter[0].filters[0].filters', {}), {field: this.recordClass.getMeta('containerProperty')});
        const operator = _.get(containerFilter, 'operator', '');
        const value = operator.match(/equals|in/) ? _.get(containerFilter, 'value', null) : null;

        return value && _.isArray(value) ? [value] : value;
    },

    /**
     * on store load exception
     * 
     * @param {Tine.Tinebase.data.RecordProxy} proxy
     * @param {String} type
     * @param {Object} error
     * @param {Object} options
     */
    onStoreLoadException: function(proxy, type, error, options) {

        // reset autoRefresh
        if (window.isMainWindow && this.autoRefreshInterval) {
            this.autoRefreshTask.delay(this.autoRefreshInterval * 5000);
        }

        if (this.usePagingToolbar && this.pagingToolbar.refresh) {
            this.pagingToolbar.refresh.enable();
        }

        if (! options.autoRefresh) {
            proxy.handleRequestException(error);
        } else {
            Tine.log.debug('Tine.widgets.grid.GridPanel::onStoreLoadException -> auto refresh failed.');
        }
    },

    /**
     * onStoreBeforeLoadRecords
     * 
     * @param {Object} o
     * @param {Object} options
     * @param {Boolean} success
     * @param {Ext.data.Store} store
     */
    onStoreBeforeLoadRecords: function(o, options, success, store) {

        if (this.lastStoreTransactionId && options.transactionId && this.lastStoreTransactionId !== options.transactionId) {
            Tine.log.debug('onStoreBeforeLoadRecords - cancelling old transaction request.');
            return false;
        }

        // save selection -> will be applied onLoad
        if (options.preserveSelection) {
            options.preserveSelection = this.grid.getSelectionModel().getSelections();
        }

        // save scroller -> will be applied onLoad
        if (options.preserveScroller && this.grid.getView().scroller && this.grid.getView().scroller.dom) options.preserveScroller = this.grid.getView().scroller.dom.scrollTop;

        // apply removeStrategy
        if (! options.removeStrategy || options.removeStrategy === 'default') {
            return true;
        }

        var records = [],
            recordsIds = [],
            recordToLoadCollection = new Ext.util.MixedCollection();

        // fill new collection
        Ext.each(o.records, function(record) {
            recordToLoadCollection.add(record.id, record);
        });

        // assemble update & keep
        this.store.each(function(currentRecord) {
            var recordToLoad = recordToLoadCollection.get(currentRecord.id);
            if (recordToLoad) {
                // we replace records that are the same, because otherwise this would not work for local changes
                if (recordToLoad.isObsoletedBy(currentRecord)) {
                    records.push(currentRecord);
                    recordsIds.push(currentRecord.id);
                } else {
                    records.push(recordToLoad);
                    recordsIds.push(recordToLoad.id);
                }
            } else if (options.removeStrategy === 'keepAll' || (options.removeStrategy === 'keepBuffered' && this.editBuffer.indexOf(currentRecord.id) >= 0)) {
                var copiedRecord = currentRecord.copy();
                copiedRecord.not_in_filter = true;
                records.push(copiedRecord);
                recordsIds.push(currentRecord.id);
            }
        }, this);
        
        // assemble adds
        recordToLoadCollection.each(function(record, idx) {
            if (recordsIds.indexOf(record.id) == -1 && this.deleteQueue.indexOf(record.id) == -1) {
                var lastRecord = recordToLoadCollection.itemAt(idx-1);
                var lastRecordIdx = lastRecord ? recordsIds.indexOf(lastRecord.id) : -1;
                records.splice(lastRecordIdx+1, 0, record);
                recordsIds.splice(lastRecordIdx+1, 0, record.id);
            }
        }, this);

        o.records = records;
        
        // hide current records from store.loadRecords()
        // @see 0008210: email grid: set flag does not work sometimes
        this.store.clearData();
    },

    /**
     * perform the initial load of grid data
     */
    initialLoad: function() {
        var defaultFavorite = Tine.widgets.persistentfilter.model.PersistentFilter.getDefaultFavorite(
                this.app.appName, this.recordClass.prototype.modelName
            ),
            favoritesPanel  = this.app.getMainScreen()
            && typeof this.app.getMainScreen().getWestPanel === 'function'
            && typeof this.app.getMainScreen().getWestPanel().getFavoritesPanel === 'function'
            && this.hasFavoritesPanel
                ? this.app.getMainScreen().getWestPanel().getFavoritesPanel()
                : null;

        if (defaultFavorite && favoritesPanel) {
            favoritesPanel.selectFilter(defaultFavorite);
        } else {
            if (! this.editDialog) {
                this.store.load.defer(10, this.store, [ typeof this.autoLoad == 'object' ? this.autoLoad : undefined]);
            } else {
                // editDialog exists, so get the records from there.
                var items = this.editDialog.record.get(this.editDialogRecordProperty);
                if (Ext.isArray(items)) {
                    Ext.each(items, function(item) {
                        var record = this.recordProxy.recordReader({responseText: Ext.encode(item)});
                        this.store.addSorted(record);
                    }, this);
                }
            }
        }

        if (this.usePagingToolbar && this.recordProxy) {
            this.pagingToolbar.refresh.disable.defer(10, this.pagingToolbar.refresh);
        }
    },

    /**
     * init ext grid panel
     * @private
     */
    initGrid: function() {
        var preferences = Tine.Tinebase.registry.get('preferences');

        if (preferences) {
            this.gridConfig = Ext.applyIf(this.gridConfig || {}, {
                stripeRows: preferences.get('gridStripeRows') ? preferences.get('gridStripeRows') : false,
                loadMask: preferences.get('gridLoadMask') ? preferences.get('gridLoadMask') : false
            });
            
            // added paging number of result read from settings
            if (preferences.get('pageSize') != null) {
                this.defaultPaging = {
                    start: 0,
                    limit: parseInt(preferences.get('pageSize'), 10)
                };
            }
        }

        // generic empty text
        this.i18nEmptyText = i18n.gettext('No data to display');
        
        // init sel model
        if (! this.selectionModel) {
            this.selectionModel = new Tine.widgets.grid.FilterSelectionModel({
                store: this.store,
                gridPanel: this
            });
        }

        this.selectionModel.on('selectionchange', function(sm) {
            this.actionUpdater.updateActions(sm, this.getFilteredContainers());

            this.ctxNode = this.selectionModel.getSelections();
            if (this.updateOnSelectionChange && this.detailsPanel) {
                this.detailsPanel.onDetailsUpdate(sm);
            }
        }, this);

        if (this.usePagingToolbar) {
            this.pagingToolbar = new Ext.ux.grid.PagingToolbar(Ext.apply({
                pageSize: this.defaultPaging && this.defaultPaging.limit ? this.defaultPaging.limit : 50,
                store: this.store,
                displayInfo: true,
                displayMsg: i18n._('Displaying records {0} - {1} of {2}').replace(/records/, this.i18nRecordsName),
                emptyMsg: String.format(i18n._("No {0} to display"), this.i18nRecordsName),
                displaySelectionHelper: this.displaySelectionHelper,
                sm: this.selectionModel,
                disableSelectAllPages: this.disableSelectAllPages,
                nested: this.editDialog ? true : false
            }, this.pagingConfig));
            // mark next grid refresh as paging-refresh
            this.pagingToolbar.on('beforechange', function() {
                this.grid.getView().isPagingRefresh = true;
            }, this);
        }

        // which grid to use?
        // TODO find a better way to configure quickadd grid
        var Grid = null;
        if (this.gridConfig.quickaddMandatory) {
            Grid = Ext.ux.grid.QuickaddGridPanel;
            this.gridConfig.validate = true;
        } else {
            Grid = (this.gridConfig.gridType || Ext.grid.GridPanel);
        }

        this.gridConfig.store = this.store;

        // activate grid header menu for column selection
        this.gridConfig.plugins = this.gridConfig.plugins ? this.gridConfig.plugins : [];
        this.gridConfig.plugins.push(new Ext.ux.grid.GridViewMenuPlugin({}));
        this.gridConfig.enableHdMenu = false;

        if (this.stateful) {
            this.gridConfig.stateful = true;
            this.gridConfig.stateId  = this.stateId + '-Grid' + this.stateIdSuffix;
        }

        this.grid = new Grid(Ext.applyIf(this.gridConfig, {
            border: false,
            store: this.store,
            sm: this.selectionModel,
            parentScope: this,
            view: this.createView(),
            recordClass: this.recordClass
        }));

        // init various grid / sm listeners
        this.grid.on('keydown',     this.onKeyDown,         this);
        this.grid.on('rowclick',    this.onRowClick,        this);
        this.grid.on('rowdblclick', this.onRowDblClick,     this);
        this.grid.on('newentry',    this.onStoreNewEntry,   this);
        this.grid.on('headerclick', this.onHeaderClick,   this);

        this.grid.on('rowcontextmenu', this.onRowContextMenu, this);

    },

    /**
     * creates and returns the view for the grid
     * 
     * @return {Ext.grid.GridView}
     */
    createView: function() {
        // init view
        
        if (this.groupField && ! this.groupTextTpl) {
            this.groupTextTpl = '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "' + i18n._("Records") + '" : "' + i18n._("Record") + '"]})';
        }
        
        var viewClass = this.groupField ? Ext.grid.GroupingView : Ext.grid.GridView;
        var view =  new viewClass({
            getRowClass: this.getViewRowClass.bind(this),
            autoFill: true,
            forceFit:true,
            ignoreAdd: true,
            emptyText: this.i18nEmptyText,
            groupTextTpl: this.groupTextTpl,
            onLoad: Ext.grid.GridView.prototype.onLoad.createInterceptor(function() {
                if (this.grid.getView().isPagingRefresh) {
                    this.grid.getView().isPagingRefresh = false;
                    return true;
                }

                return false;
            }, this)
        });
        
        return view;
    },
    
    /**
     * executed after outer panel rendering process
     */
    afterRender: function() {
        Tine.widgets.grid.GridPanel.superclass.afterRender.apply(this, arguments);
        if (this.initialLoadAfterRender) {
            this.initialLoad();
        }
    },

    /**
     * trigger store load with grid related options
     * 
     * TODO rethink -> preserveCursor and preserveSelection might conflict on page breaks!
     * TODO don't reload details panel when selection is preserved
     * 
     * @param {Object} options
     */
    loadGridData: function(options) {
        options = options || {};

        Ext.applyIf(options, {
            callback:           Ext.emptyFn,
            scope:              this,
            params:             {},

            preserveCursor:     true, 
            preserveSelection:  true, 
            preserveScroller:   true, 
            removeStrategy:     'default'
        });

        if (options.preserveCursor && this.usePagingToolbar) {
            options.params.start = this.pagingToolbar.cursor;
        }

        this.store.load(options);
    },

    /**
     * get action toolbar
     * 
     * @return {Ext.Toolbar}
     */
    getActionToolbar: function() {
        if (! this.actionToolbar) {
            var additionalItems = this.getActionToolbarItems();

            var items = [];
            
            if (this.action_addInNewWindow) {
                if (this.splitAddButton) {
                    items.push(Ext.apply(
                        new Ext.SplitButton(this.action_addInNewWindow), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top',
                            arrowAlign:'right',
                            menu: new Ext.menu.Menu({
                                items: [],
                                plugins: [{
                                    ptype: 'ux.itemregistry',
                                    key:   'Tine.widgets.grid.GridPanel.addButton'
                                }]
                            })
                        })
                    );
                } else {
                    items.push(Ext.apply(
                        new Ext.Button(this.action_addInNewWindow), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        })
                    );
                }
            }
            
            if (this.action_editInNewWindow) {
                items.push(Ext.apply(
                    new Ext.Button(this.action_editInNewWindow), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top'
                    })
                );
            }
            
            if (this.action_deleteRecord) {
                items.push(Ext.apply(
                    new Ext.Button(this.action_deleteRecord), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top'
                    })
                );
            }
            
            if (this.actions_print) {
                items.push(Ext.apply(
                    new (this.actions_print.initialConfig && this.actions_print.initialConfig.menu ? Ext.SplitButton : Ext.Button) (this.actions_print), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top'
                    })
                );
            }

            var importExportButtons = [];

            if (this.actions_export) {
                importExportButtons.push(Ext.apply(new Ext.Button(this.actions_export), {
                    scale: 'small',
                    rowspan: 1,
                    iconAlign: 'left'
                }));
            }
            if (this.actions_import) {
                importExportButtons.push(Ext.apply(new Ext.Button(this.actions_import), {
                    scale: 'small',
                    rowspan: 1,
                    iconAlign: 'left'
                }));
            }

            if (importExportButtons.length > 0) {
                items.push({
                    xtype: 'buttongroup',
                    columns: 1,
                    frame: false,
                    items: importExportButtons
                });
            }

            this.actionToolbar = new Ext.Toolbar({
                canonicalName: [this.recordClass.getMeta('modelName'), 'ActionToolbar'].join(Tine.Tinebase.CanonicalPath.separator),
                items: [{
                    xtype: 'buttongroup',
                    layout: 'toolbar',
                    buttonAlign: 'left',
                    enableOverflow: true,
                    plugins: [{
                        ptype: 'ux.itemregistry',
                        key:   this.app.appName + '-' + this.recordClass.prototype.modelName + '-GridPanel-ActionToolbar-leftbtngrp'
                    }],
                    items: items.concat(Ext.isArray(additionalItems) ? additionalItems : [])
                }].concat(Ext.isArray(additionalItems) ? [] : [additionalItems])
            });

            this.actionToolbar.on('resize', this.onActionToolbarResize, this, {buffer: 250});
            this.actionToolbar.on('show', this.onActionToolbarResize, this);

            if (this.filterToolbar && typeof this.filterToolbar.getQuickFilterField == 'function') {
                this.actionToolbar.add('->', this.filterToolbar.getQuickFilterField());
            } 
        }

        return this.actionToolbar;
    },

    onActionToolbarResize: function(tb) {
        if (! tb.rendered) return;
        var actionGrp = tb.items.get(0),
            availableWidth = tb.getBox()['width'] - 5,
            maxNeededWidth = Ext.layout.ToolbarLayout.prototype.triggerWidth + 10;

        tb.items.each(function(c, idx) {
            if (idx > 0 && !c.isFill) {
                availableWidth -= c.getPositionEl().dom.parentNode.offsetWidth;
            }
        }, this);

        actionGrp.items.each(function(c) {
            maxNeededWidth += Ext.layout.ToolbarLayout.prototype.getItemWidth(c);
        });

        actionGrp.setWidth(Math.min(availableWidth, maxNeededWidth));

    },

    /**
     * template fn for subclasses to add custom items to action toolbar
     * 
     * @return {Array/Object}
     */
    getActionToolbarItems: function() {
        var items = this.actionToolbarItems || [];

        if (! Ext.isEmpty(items)) {
            // legacy handling! subclasses should register all actions when initializing actions
            this.actionUpdater.addActions(items);
        }

        return items;
    },

    /**
     * returns rows context menu
     * 
     * @param {Ext.grid.GridPanel} grid
     * @param {Number} row
     * @param {Ext.EventObject} e
     * @return {Ext.menu.Menu}
     */
    getContextMenu: function(grid, row, e) {

        if (! this.contextMenu) {
            var items = [];
            
            if (this.action_addInNewWindow) items.push(this.action_addInNewWindow);
            if (this.action_editInNewWindow) items.push(this.action_editInNewWindow);
            if (this.action_editCopyInNewWindow) items.push(this.action_editCopyInNewWindow);
            if (this.action_move) items.push(this.action_move);
            if (this.action_deleteRecord) items.push(this.action_deleteRecord);

            if (this.duplicateResolvable) {
                items.push(this.action_resolveDuplicates);
            }

            if (this.actions_export) {
                items.push('-', this.actions_export);
            }

            if (this.action_tagsMassAttach && ! this.action_tagsMassAttach.hidden) {
                items.push('-', this.action_tagsMassAttach, this.action_tagsMassDetach);
            }

            // lookup additional items
            items = items.concat(this.getContextMenuItems());

            // New record of another app
            this.newRecordMenu = new Ext.menu.Menu({
                items: [],
                plugins: [{
                    ptype: 'ux.itemregistry',
                    key:   this.app.appName + '-' + this.recordClass.prototype.modelName + '-GridPanel-ContextMenu-New'
                }]
            });

            this.newRecordAction = new Ext.Action({
                text: i18n._('New...'),
                hidden: ! this.newRecordMenu.items.length,
                iconCls: this.app.getIconCls(),
                scope: this,
                menu: this.newRecordMenu
            });

            items.push(this.newRecordAction);

            // Add to record of another app            
            this.addToRecordMenu = new Ext.menu.Menu({
                items: [],
                plugins: [{
                    ptype: 'ux.itemregistry',
                    key:   this.app.appName + '-' + this.recordClass.prototype.modelName + '-GridPanel-ContextMenu-Add'
                }]
            });

            this.addToRecordAction = new Ext.Action({
                text: i18n._('Add to...'),
                hidden: ! this.addToRecordMenu.items.length,
                iconCls: this.app.getIconCls(),
                scope: this,
                menu: this.addToRecordMenu
            });

            items.push(this.addToRecordAction);

            this.contextMenu = new Ext.menu.Menu({
                items: items,
                plugins: [{
                    ptype: 'ux.itemregistry',
                    key:   this.app.appName + '-' + this.recordClass.prototype.modelName + '-GridPanel-ContextMenu'
                }, {
                    ptype: 'ux.itemregistry',
                    key:   'Tinebase-MainContextMenu'
                }]
            });
        }

        return this.contextMenu;
    },

    /**
     * template fn for subclasses to add custom items to context menu
     * 
     * @return {Array}
     */
    getContextMenuItems: function() {
        var items = this.contextMenuItems || [];

        if (! Ext.isEmpty(items)) {
            // legacy handling! subclasses should register all actions when initializing actions
            this.actionUpdater.addActions(items);
        }

        return items;
    },

    /**
     * get modlog columns
     * 
     * shouldn' be used anymore
     * @TODO: use applicationstarter and modelconfiguration
     * 
     * @deprecated
     * @return {Array}
     */
    getModlogColumns: function() {
        var result = [
            { id: 'creation_time',      header: i18n._('Creation Time'),         dataIndex: 'creation_time',         renderer: Tine.Tinebase.common.dateRenderer,        hidden: true, sortable: true },
            { id: 'created_by',         header: i18n._('Created By'),            dataIndex: 'created_by',            renderer: Tine.Tinebase.common.usernameRenderer,    hidden: true, sortable: true },
            { id: 'last_modified_time', header: i18n._('Last Modified Time'),    dataIndex: 'last_modified_time',    renderer: Tine.Tinebase.common.dateRenderer,        hidden: true, sortable: true },
            { id: 'last_modified_by',   header: i18n._('Last Modified By'),      dataIndex: 'last_modified_by',      renderer: Tine.Tinebase.common.usernameRenderer,    hidden: true, sortable: true }
        ];

        return result;
    },

    /**
     * get custom field columns for column model
     * 
     * @return {Array}
     */
    getCustomfieldColumns: function() {
        var modelName = this.recordClass.getMeta('appName') + '_Model_' + this.recordClass.getMeta('modelName'),
            cfConfigs = Tine.widgets.customfields.ConfigManager.getConfigs(this.app, modelName),
            result = [];

        Ext.each(cfConfigs, function(cfConfig) {
            result.push({
                id: cfConfig.id,
                header: cfConfig.get('definition').label,
                dataIndex: 'customfields',
                renderer: Tine.widgets.customfields.Renderer.get(this.app, cfConfig),
                sortable: false,
                hidden: true
            });
        }, this);

        return result;
    },

    /**
     * get custom field filter for filter toolbar
     * 
     * @return {Array}
     */
    getCustomfieldFilters: function() {
        var modelName = this.recordClass.getMeta('appName') + '_Model_' + this.recordClass.getMeta('modelName'),
            cfConfigs = Tine.widgets.customfields.ConfigManager.getConfigs(this.app, modelName),
            result = [];
        Ext.each(cfConfigs, function(cfConfig) {
            try {
                var cfDefinition = cfConfig.get('definition');
                switch (cfDefinition.type) {
                    case 'record':
                        if (_.get(window, cfDefinition.recordConfig.value.records)) {
                            result.push({
                                filtertype: 'foreignrecord',
                                label: cfDefinition.label,
                                app: this.app,
                                ownRecordClass: this.recordClass,
                                foreignRecordClass: eval(cfDefinition.recordConfig.value.records),
                                linkType: 'foreignId',
                                ownField: 'customfield:' + cfConfig.id,
                                pickerConfig: cfDefinition.recordConfig.additionalFilterSpec ? {
                                    additionalFilterSpec: cfDefinition.recordConfig.additionalFilterSpec
                                } : null
                            });
                        }
                        break;
                    default:
                        result.push({filtertype: 'tinebase.customfield', app: this.app, cfConfig: cfConfig});
                        break;
                }

            } catch (e) {
                Tine.log.warn('CustomfieldFilters ' + cfDefinition.label + ' doesnt create');

            }
        }, this);


        return result;
    },

    /**
     * returns filter toolbar
     * @private
     * @deprecated
     * 
     * TODO this seems to be legacy code that is only used in some apps (Calendar, Felamimail, ...)
     *   -> should be removed
     *   -> we use initFilterPanel() now
     */
    getFilterToolbar: function(config) {
        config = config || {};
        return new Tine.widgets.grid.FilterPanel(Ext.apply(config, {
            app: this.app,
            recordClass: this.recordClass,
            filterModels: this.recordClass.getFilterModel().concat(this.getCustomfieldFilters()),
            defaultFilter: 'query',
            filters: this.defaultFilters || []
        }));
    },

    /**
     * return store from grid
     * 
     * @return {Ext.data.Store}
     */
    getStore: function() {
        return this.grid.getStore();
    },

    /**
     * return view from grid
     * 
     * @return {Ext.grid.GridView}
     */
    getView: function() {
        return this.grid.getView();
    },

    /**
     * return grid
     * 
     * @return {Ext.ux.grid.QuickaddGridPanel|Ext.grid.GridPanel}
     */
    getGrid: function() {
        return this.grid;
    },

    /**
     * key down handler
     * @private
     */
    onKeyDown: function(e){
        // no keys for quickadds etc.
        if (e.getTarget('input') || e.getTarget('textarea')) return;

        // do not catch modified keys (CTRL, ALT)
        if (e.ctrlKey || e.altKey) return;

        switch (e.getKey()) {
            case e.A:
                // select only current page
                this.grid.getSelectionModel().selectAll(true);
                e.preventDefault();
                break;
            case e.C:
                if (this.action_editCopyInNewWindow && !this.action_editCopyInNewWindow.isDisabled()) {
                    this.onEditInNewWindow.call(this, {
                        actionType: 'copy'
                    });
                    e.preventDefault();
                }
                break;
            case e.E:
                if (this.action_editInNewWindow && !this.action_editInNewWindow.isDisabled()) {
                    this.onEditInNewWindow.call(this, {
                        actionType: 'edit'
                    });
                    e.preventDefault();
                }
                break;
            case e.N:
                if (this.action_addInNewWindow && !this.action_addInNewWindow.isDisabled()) {
                    this.onEditInNewWindow.call(this, {
                        actionType: 'add'
                    });
                    e.preventDefault();
                }
                break;
            case e.F:
                if (this.filterToolbar && this.hasQuickSearchFilterToolbarPlugin) {
                    e.preventDefault();
                    this.filterToolbar.getQuickFilterPlugin().quickFilter.focus();
                }
                break;
            case e.R:
                this.bufferedLoadGridData();
                break;
            default:
                if ([e.BACKSPACE, e.DELETE].indexOf(e.getKey()) !== -1) {
                    if (!this.grid.editing && !this.grid.adding && !this.action_deleteRecord.isDisabled()) {
                        this.onDeleteRecords.call(this);
                        e.preventDefault();
                    }
                }
                if (e.browserEvent.key === '?') {
                    // TODO only show keys of actions that are available
                    var helpText = i18n._('A: select all visible rows') + '<br/>' +
                        i18n._('C: copy record') + '<br/>' +
                        i18n._('E: edit record') + '<br/>' +
                        i18n._('N: new record') + '<br/>' +
                        i18n._('F: find') + '<br/>' +
                        i18n._('ESC: focus grid') + '<br/>' +
                        i18n._('R: reload grid') + '<br/>';
                    Ext.MessageBox.show({
                        title: i18n._('Grid Panel Key Bindings'),
                        msg: helpText,
                        buttons: Ext.Msg.OK,
                        icon: Ext.MessageBox.INFO
                    });
                }
        }
    },

    /**
     * row click handler
     * 
     */
    onRowClick: function(grid, row, e) {
        var _ = window.lodash,
            sm = grid.getSelectionModel();

        /* TODO check if we need this in IE
        // hack to get percentage editor working
        var cell = Ext.get(grid.getView().getCell(row,1));
        var dom = cell.child('div:last');
        while (cell.first()) {
            cell = cell.first();
            cell.on('click', function(e){
                e.stopPropagation();
                grid.fireEvent('celldblclick', grid, row, 1, e);
            });
        }
        */

        // fix selection of one record if shift/ctrl key is not pressed any longer
        if (e.button === 0 && !e.shiftKey && !e.ctrlKey && !e.getTarget('.x-grid3-row-checker')) {
            if (sm.getCount() == 1 && sm.isSelected(row)) {
                // return;
            } else {
                sm.clearSelections();
                sm.selectRow(row, false);
                grid.view.focusRow(row);
            }
        }

        if (e.getTarget('.action_attach')) {
            if (Tine.Tinebase.appMgr.isEnabled('Filemanager')) {
                const record = this.getStore().getAt(row);
                if (record.get('attachments').length === 0) {
                    return;
                }
                const firstAttachment = new Tine.Tinebase.Model.Tree_Node(record.get('attachments')[0]);
                Tine.Filemanager.QuickLookPanel.openWindow({
                    record: firstAttachment,
                    initialApp: this.app,
                    sm: sm
                });
            }
        }
    },
    
    /**
     * row doubleclick handler
     * 
     * @param {} grid
     * @param {} row
     * @param {} e
     */
    onRowDblClick: function(grid, row, e) {
        this.onEditInNewWindow.call(this, {actionType: 'edit'});
    }, 

    /**
     * called on row context click
     * 
     * @param {Ext.grid.GridPanel} grid
     * @param {Number} row
     * @param {Ext.EventObject} e
     */
    onRowContextMenu: function(grid, row, e) {
        e.stopEvent();
        var selModel = grid.getSelectionModel();
        if (!selModel.isSelected(row)) {
            // disable preview update if config option is set to false
            this.updateOnSelectionChange = this.updateDetailsPanelOnCtxMenu;
            selModel.selectRow(row);
        }

        var contextMenu = this.getContextMenu(grid, row, e);

        if (contextMenu) {
            contextMenu.showAt(e.getXY());
        }

        // reset preview update
        this.updateOnSelectionChange = true;
    },
    
    /**
     * Opens the required EditDialog
     * @param {Object} actionButton the button the action was called from
     * @param {Tine.Tinebase.data.Record} record the record to display/edit in the dialog
     * @param {Array} plugins the plugins used for the edit dialog
     * @param {Object} additionalConfig plain Object, which will be applied to the edit dialog on initComponent
     * @return {Boolean}
     */
    onEditInNewWindow: function(button, record, plugins, additionalConfig) {
        if (! record) {
            if (button.actionType == 'edit' || button.actionType == 'copy') {
                if (! this.action_editInNewWindow || this.action_editInNewWindow.isDisabled()) {
                    // if edit action is disabled or not available, we also don't open a new window
                    return false;
                }
                var selectedRows = this.grid.getSelectionModel().getSelections();
                record = selectedRows[0];
            } else {
                record = this.createNewRecord();
            }
        }

        // plugins to add to edit dialog
        var plugins = plugins ? plugins : [];
        
        var totalcount = this.selectionModel.getCount(),
            selectedRecords = [],
            fixedFields = (button.hasOwnProperty('fixedFields') && Ext.isObject(button.fixedFields)) ? Ext.encode(button.fixedFields) : null,
            editDialogClass = this.editDialogClass || Tine.widgets.dialog.EditDialog.getConstructor(this.recordClass),
            additionalConfig = additionalConfig ? additionalConfig : {};
        
        // add "multiple_edit_dialog" plugin to dialog, if required
        if (((totalcount > 1) && (this.multipleEdit) && (button.actionType == 'edit'))) {
            Ext.each(this.selectionModel.getSelections(), function(record) {
                selectedRecords.push(record.data);
            }, this );
            
            plugins.push({
                ptype: 'multiple_edit_dialog', 
                selectedRecords: selectedRecords,
                selectionFilter: this.selectionModel.getSelectionFilter(),
                isFilterSelect: this.selectionModel.isFilterSelect,
                totalRecordCount: totalcount
            });
        }

        Tine.log.debug('GridPanel::onEditInNewWindow');
        Tine.log.debug(record);
        
        var popupWindow = editDialogClass.openWindow(Ext.copyTo(
            this.editDialogConfig || {}, {
                plugins: Ext.encode(plugins),
                fixedFields: fixedFields,
                additionalConfig: Ext.encode(additionalConfig),
                record: editDialogClass.prototype.mode == 'local' ? Ext.encode(record.data) : record,
                recordId: record.getId(),
                copyRecord: (button.actionType == 'copy'),
                asAdminModule: this.asAdminModule,
                listeners: {
                    scope: this,
                    'update': ((this.selectionModel.getCount() > 1) && (this.multipleEdit)) ? this.onUpdateMultipleRecords : this.onUpdateRecord
                }
            }, 'record,recordId,listeners,fixedFields,copyRecord,plugins,additionalConfig,asAdminModule')
        );
        return true;
    },

    /**
     * create new record
     *
     * @returns {Tine.Tinebase.data.Record}
     */
    createNewRecord: function() {
        return new this.recordClass(this.recordClass.getDefaultData(), 0);
    },

    /**
     * is called after multiple records have been updated
     */
    onUpdateMultipleRecords: function() {
        this.store.reload();
    },

    /**
     * on update after edit
     * 
     * @param {String|Tine.Tinebase.data.Record} record
     * @param {String} mode
     */
    onUpdateRecord: function(record, mode) {
        if (! this.rendered) {
            return;
        }

        if (! mode && ! this.recordProxy) {
            // proxy-less = local if not defined otherwise
            mode = 'local';
        }
        
        if (Ext.isString(record)) {
            record = this.recordProxy
                ? this.recordProxy.recordReader({responseText: record})
                : Tine.Tinebase.data.Record.setFromJson(record, this.recordClass);

        } else if (record && Ext.isFunction(record.copy)) {
            record = record.copy();
        }

        if (record.id === 0) {
            // we need to set a id != 0 to make identity handling in stores possible
            // TODO add config for this behaviour?
            record.id = 'new-' + Ext.id();
            record.setId(record.id);
        }

        Tine.log.debug('Tine.widgets.grid.GridPanel::onUpdateRecord() -> record:');
        Tine.log.debug(record, mode);

        if (record && Ext.isFunction(record.copy)) {
            var idx = this.getStore().indexOfId(record.id),
                isSelected = this.getGrid().getSelectionModel().isSelected(idx),
                store = this.getStore();

            if (idx >= 0) {
                // only run do this in local mode as we reload the store in remote mode
                // NOTE: this would otherwise delete the record if a record proxy exists!
                if (mode == 'local') {
                    store.removeAt(idx);
                    store.insert(idx, [record]);
                }
            } else {
                this.getStore().add([record]);
            }

            // sort new/edited record
            store.remoteSort = false;
            store.sort(store.sortInfo.field, store.sortInfo.direction)
            store.remoteSort = this.storeRemoteSort;

            if (isSelected) {
                this.getGrid().getSelectionModel().selectRow(store.indexOfId(record.id), true);
            }

            this.addToEditBuffer(record);
        }

        if (mode == 'local') {
            this.onStoreUpdate(this.getStore(), record, Ext.data.Record.EDIT);
        } else {
            this.bufferedLoadGridData({
                removeStrategy: 'keepBuffered',
            });
        }
    },

    onMoveRecords: function() {
        var containerSelectDialog = new Tine.widgets.container.SelectionDialog({
            recordClass: this.recordClass
        });
        containerSelectDialog.on('select', function(dlg, node) {
            var sm = this.grid.getSelectionModel(),
                records = sm.getSelections(),
                recordIds = [].concat(records).map(function(v){ return v.id; }),
                filter = sm.getSelectionFilter(),
                containerId = node.attributes.id,
                i18nItems = this.app.i18n.n_hidden(this.recordClass.getMeta('recordName'), this.recordClass.getMeta('recordsName'), records.length);

            if (! this.moveMask) {
                this.moveMask = new Ext.LoadMask(this.grid.getEl(), {
                    msg: String.format(i18n._('Moving {0}'), i18nItems)
                });
            }
            this.moveMask.show();

            // move records to folder
            Ext.Ajax.request({
                params: {
                    method: 'Tinebase_Container.moveRecordsToContainer',
                    targetContainerId: containerId,
                    filterData: filter,
                    model: this.recordClass.getMeta('modelName'),
                    applicationName: this.recordClass.getMeta('appName')
                },
                scope: this,
                success: function() {
                    this.refreshAfterEdit(recordIds);
                    this.onAfterEdit(recordIds);
                },
                failure: function (exception) {
                    this.refreshAfterEdit(recordIds);
                    this.loadGridData();
                    Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
                }
            });
        }, this);
    },

    /**
     * is called to resolve conflicts from 2 records
     */
    onResolveDuplicates: function() {
        // TODO: allow more than 2 records      
        if (this.grid.getSelectionModel().getSelections().length != 2) return;
        
        var selections = [];
        Ext.each(this.grid.getSelectionModel().getSelections(), function(sel) {
            selections.push(sel.data);
        });
        
        var window = Tine.widgets.dialog.DuplicateMergeDialog.getWindow({
            selections: Ext.encode(selections),
            appName: this.app.name,
            modelName: this.recordClass.getMeta('modelName')
        });
        
        window.on('contentschange', function() { this.store.reload(); }, this);
    },
    
    /**
     * add record to edit buffer
     * 
     * @param {String|Tine.Tinebase.data.Record} record
     */
    addToEditBuffer: function(record) {

        var recordData = (Ext.isString(record)) ? Ext.decode(record) : record.data,
            id = recordData[this.recordClass.getMeta('idProperty')];

        if (this.editBuffer.indexOf(id) === -1) {
            this.editBuffer.push(id);
        }
    },

    /**
     * generic delete handler
     */
    onDeleteRecords: function(btn, e) {
        var sm = this.grid.getSelectionModel();

        if (sm.isFilterSelect && ! this.filterSelectionDelete) {
            Ext.MessageBox.show({
                title: i18n._('Not Allowed'),
                msg: i18n._('You are not allowed to delete all pages at once'),
                buttons: Ext.Msg.OK,
                icon: Ext.MessageBox.INFO
            });

            return;
        }
        var records = sm.getSelections();

        if (this.disableDeleteConfirmation || (Tine[this.app.appName].registry.get('preferences')
            && Tine[this.app.appName].registry.get('preferences').get('confirmDelete') !== null
            && Tine[this.app.appName].registry.get('preferences').get('confirmDelete') == 0)
        ) {
            // don't show confirmation question for record deletion
            this.deleteRecords(sm, records);
        } else {
            var recordNames = records[0].getTitle();
            if (records.length > 1) {
                recordNames += ', ...';
            }

            var i18nQuestion = this.i18nDeleteQuestion ?
                this.app.i18n.n_hidden(this.i18nDeleteQuestion[0], this.i18nDeleteQuestion[1], records.length) :
                String.format(i18n.ngettext('Do you really want to delete the selected record ({0})?',
                    'Do you really want to delete the selected records ({0})?', records.length), recordNames);
            Ext.MessageBox.confirm(i18n._('Confirm'), i18nQuestion, function(btn) {
                if (btn == 'yes') {
                    this.deleteRecords(sm, records);
                }
            }, this);
        }
    },

    /**
     * delete records
     * 
     * @param {SelectionModel} sm
     * @param {Array} records
     */
    deleteRecords: function(sm, records) {
        // directly remove records from the store (only for non-filter-selection)
        if (Ext.isArray(records) && ! (sm.isFilterSelect && this.filterSelectionDelete)) {
            Ext.each(records, function(record) {
                this.store.remove(record);
            });
            // if nested in an editDialog, just change the parent record
            if (this.editDialog) {
                var items = [];
                this.store.each(function(item) {
                    items.push(item.data);
                });
                this.editDialog.record.set(this.editDialogRecordProperty, items);
                this.editDialog.fireEvent('updateDependent');
                return;
            }
        }

        if (this.recordProxy) {
            if (this.usePagingToolbar) {
                this.pagingToolbar.refresh.disable();
            }

            var i18nItems = this.app.i18n.n_hidden(this.recordClass.getMeta('recordName'), this.recordClass.getMeta('recordsName'), records.length),
                recordIds = [].concat(records).map(function(v){ return v.id; });

            if (sm.isFilterSelect && this.filterSelectionDelete) {
                if (! this.deleteMask) {
                    this.deleteMask = new Ext.LoadMask(this.grid.getEl(), {
                        msg: String.format(i18n._('Deleting {0}'), i18nItems) + ' ' + i18n._('... This may take a long time!')
                    });
                }
                this.deleteMask.show();
            }

            this.deleteQueue = this.deleteQueue.concat(recordIds);

            var options = {
                scope: this,
                success: function() {
                    this.refreshAfterDelete(recordIds);
                    this.onAfterDelete(recordIds);
                },
                failure: function (exception) {
                    this.onDeleteFailure(recordIds, exception);
                }
            };

            if (sm.isFilterSelect && this.filterSelectionDelete) {
                this.recordProxy.deleteRecordsByFilter(sm.getSelectionFilter(), options);
            } else {
                this.recordProxy.deleteRecords(records, options);
            }
        }
    },

    /**
     * refresh after delete (hide delete mask or refresh paging toolbar)
     */
    refreshAfterDelete: function(ids) {
        this.deleteQueue = this.deleteQueue.diff(ids);

        if (this.deleteMask) {
            this.deleteMask.hide();
        }

        if (this.usePagingToolbar) {
            this.pagingToolbar.refresh.show();
        }
    },

    /**
     * do something after deletion of records
     * - reload the store
     * 
     * @param {Array} [ids]
     */
    onAfterDelete: function(ids) {
        this.editBuffer = this.editBuffer.diff(ids);

        this.bufferedLoadGridData({
            removeStrategy: 'keepBuffered'
        });
    },

    /**
     * do something on delete failure
     *
     * @param ids
     * @param exception
     */
    onDeleteFailure: function(ids, exception) {
        this.refreshAfterDelete(ids);
        this.loadGridData();
        Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
    },

    /**
     * refresh after edit/move
     */
    refreshAfterEdit: function(ids) {
        this.editBuffer = this.editBuffer.diff(ids);

        if (this.moveMask) {
            this.moveMask.hide();
        }
        if (this.editMask) {
            this.editMask.hide();
        }

        if (this.usePagingToolbar) {
            this.pagingToolbar.refresh.show();
        }
    },

    /**
     * do something after edit of records
     *
     * @param {Array} [ids]
     */
    onAfterEdit: function(ids) {
        this.editBuffer = this.editBuffer.diff(ids);
        this.bufferedLoadGridData({
            removeStrategy: 'keepBuffered'
        });
    }
});
