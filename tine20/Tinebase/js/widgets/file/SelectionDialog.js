/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Tinebase.widgets.file');

/**
 * @TODO: what if user has no download right?
 *          - device might not have download/local grant (concept in the pipe) -> can be handled by downloadPlugin
 *          - user has no right for particular content (dest mode) -> needs to be handled by calling class
 *              -> have a flag for this? systemOnly?
 */
Tine.Tinebase.widgets.file.SelectionDialog = Ext.extend(Tine.Tinebase.dialog.Dialog, {
    /**
     * @cfg {String} mode one of source|target
     */
    mode: 'source',

    /**
     * @cfg {String} locationTypesEnabled list of enabled plugins
     */
    locationTypesEnabled: 'fm_node,local',

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
     * @cfg {String} initialPath
     * initial filemanager path
     */
    initialPath: null,

    /**
     * @cfg {String} fileName
     * @property {String} fileName
     * (initial) fileName
     */
    fileName: null,

    /**
     * @cfg {Object} pluginConfig
     * config to pass to specific plugin
     * {<pluginName>: {...}}
     */
    pluginConfig: null,

    /**
     * @cfg {String} windowTitle
     * translated window title
     */
    windowTitle: null,
    
    // private
    windowNamePrefix: 'Tinebase.widgets.file.SelectionDialog',
    windowWidth: 1024,
    windowHeight: 500,
    layout: 'border',
    border: false,
    
    initComponent: function () {
        this.locationPlugins = [];
        this.locationTypesEnabled = _.map(_.split(this.locationTypesEnabled, ','), _.trim);
        
        this.allowMultiple = this.mode === 'source' ? this.allowMultiple : false;
        
        const localIdx = _.indexOf(this.locationTypesEnabled, 'local');
        if (localIdx >= 0) {
            this.locationTypesEnabled[localIdx] = _.indexOf(['source', 'src'], this.mode) >= 0 ?
                'upload' : 'download';
        }
        
        // init locationType plugins
        this.locationTypesEnabled = _.filter(this.locationTypesEnabled, (type) => {
            
            if (! Tine.Tinebase.widgets.file.LocationTypePluginFactory.isRegistered(type)) {
                Tine.log.warn(`no LocationTypePlugin for "${type}" registered`);
                return false;
            }

            Tine.Tinebase.widgets.file.LocationTypePluginFactory.create(type, _.get(this.pluginConfig, type, {}))
                .then((plugin) => {
                    this.addLocationPlugin(plugin);
                });
            
            return true;
        });

        this.initLayout();

        this.windowTitle = this.windowTitle || 
            (this.constraint === 'file' ? (this.allowMultiple ? i18n._('Select files') : i18n._('Select file')) :
            (this.constraint === 'folder' ? (this.allowMultiple ? i18n._('Select folders') : i18n._('Select folder')) : 
            (this.allowMultiple ? i18n._('Select items') : i18n._('Select item'))
        ));

        this.window.setTitle(this.windowTitle);
        
        Tine.Tinebase.widgets.file.SelectionDialog.superclass.initComponent.call(this);
    },

    getEventData: function() {
        const fileList = this.activePlace.getFileList();
        
        _.each(fileList, (file) => {
            _.defaults(file, {
                mode: this.mode,
                type: this.activePlace.locationType,
                file_name: this.fileName
            });
        });
        
        return this.allowMultiple ? fileList : _.get(fileList, '[0]');
    },

    onButtonApply: async function() {
        if (await this.activePlace.validateSelection() === false) {
            return;
        }
        
        return Tine.Tinebase.widgets.file.SelectionDialog.superclass.onButtonApply.apply(this, arguments);
    },
    
    // note, locationPlugins are added async!
    addLocationPlugin: function(locationPlugin) {
        this.locationPlugins.push(locationPlugin);

        locationPlugin.treeNode = new Ext.tree.TreeNode({
            text: locationPlugin.name,
            iconCls: locationPlugin.iconCls
        });

        locationPlugin.index = this.locationTypesEnabled.indexOf(locationPlugin.plugin);
        
        let nodeInserted = false;
        _.each(this.locationPlugins, (p) => {
            if (p.index > locationPlugin.index) {
                this.getPluginSelectionTree().getRootNode().insertBefore(locationPlugin.treeNode, p.treeNode);
                nodeInserted = true;
            }
        });
        
        if (! nodeInserted) {
            this.getPluginSelectionTree().getRootNode().appendChild(locationPlugin.treeNode);
        }
        
        if (locationPlugin.locationType === this.locationTypesEnabled[0]) {
            locationPlugin.treeNode.select();
        }
    },

    onTreeSelectionChange: async function(sm, node) {
        // this.ensureSelected(node);
        
        const locationPlugin = _.find(this.locationPlugins, {treeNode: node});

        locationPlugin.manageButtonApply(this.buttonApply);
        
        const async = await import('async');
        await async.map(['pluginPanel', 'targetForm', 'optionsForm'], async (area) => {
            const cardPanel = _.get(this, area);
            const pluginArea = await locationPlugin.getSelectionDialogArea(area, this);
            
            if (pluginArea && !pluginArea.disabled) {
                if (cardPanel.items.indexOf(pluginArea) < 0) {
                    _.get(this, area).add(pluginArea);
                }
                
                cardPanel.layout.setActiveItem(pluginArea.id);
                cardPanel.show();
                if (_.indexOf(['north', 'south'], cardPanel.region) >= 0) {
                    const height = pluginArea.getHeight();
                    this[area].setHeight(height);
                    cardPanel.setHeight(height);
                }
                cardPanel.doLayout();
            } else {
                cardPanel.hide();
            }
        });
        
        // manage pluginSelection
        if (locationPlugin.locationType === 'fm_node') {
            locationPlugin.pluginPanel.westPanel.getPortalColumn().insert(0, this.getPluginSelectionTree());
            this.westPanel.hide();
        } else {
            this.westPanel.show();
            this.westPanel.insert(0, this.getPluginSelectionTree());
        }
        
        this.activePlace = locationPlugin;
        this.doLayout();
    },
    
    // // so odd!
    // ensureSelected: function(node) {
    //     if (! node.getUI().rendered) {
    //         return this.ensureSelected.defer(100, this, [node]);
    //     }
    //     const sm = node.getOwnerTree().getSelectionModel();
    //     sm.suspendEvents()
    //     node.select();
    //     sm.resumeEvents();
    // },
    
    getPluginSelectionTree: function() {
        if (! this.pluginSelectionTree) {
            this.pluginSelectionTree = Ext.create({
                xtype: 'treepanel',
                border: false,
                title: window.i18n._('Places'),
                baseCls: 'ux-arrowcollapse',
                collapsible: true,
                rootVisible: false,
                root: new Ext.tree.TreeNode({
                    expanded: true,
                    leaf: false,
                    id: 'root'
                })
            });

            this.pluginSelectionTree.getSelectionModel().on('selectionchange', this.onTreeSelectionChange, this);
        }
        
        return this.pluginSelectionTree;
    },
    
    initLayout: function() {
        this.defaults = {
            border: false
        };
        
        this.items = [{
            ref: 'targetForm',
            region: 'north',
            layout: 'card',
            hidden: this.mode === 'source'
        }, {
            ref: 'westPanel',
            region: 'west',
            width: 150,
            items: this.getPluginSelectionTree()
        }, {
            ref: 'pluginPanel',
            region: 'center',
            layout: 'card'
        }, {
            ref: 'optionsForm',
            region: 'south',
            layout: 'card',
            hidden: true
        }]; 
    }
});

Tine.Tinebase.widgets.file.SelectionDialog.openWindow = function (config) {
    const constructor = 'Tine.Tinebase.widgets.file.SelectionDialog'
    const prototype = eval(constructor).prototype;
    // we might need to jsonDecode first :-(
    const windowId = _.get(config, 'windowId') || _.get(config, 'record.id') || Ext.id();
    
    return Tine.WindowFactory.getWindow({
        width: prototype.windowWidth,
        height: prototype.windowHeight,
        name: prototype.windowNamePrefix + id,
        contentPanelConstructor: constructor,
        contentPanelConstructorConfig: config
    });
};
