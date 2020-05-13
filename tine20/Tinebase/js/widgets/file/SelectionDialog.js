/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import('./SelectionDialog/InitUploadPlugin');
import('./SelectionDialog/InitDownloadPlugin');

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
     * @cfg {String} pluginsEnabled list of enabled plugins
     */
    pluginsEnabled: 'filemanager,local',

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
        this.places = [];
        this.pluginsEnabled = _.map(_.split(this.pluginsEnabled, ','), _.trim);
        
        const localIdx = _.indexOf(this.pluginsEnabled, 'local');
        if (localIdx >= 0) {
            this.pluginsEnabled[localIdx] = _.indexOf(['source', 'src'], this.mode) >= 0 ?
                'upload' : 'download';
        }
        
        // init plugins
        this.plugins = this.plugins || [];
        this.pluginsEnabled = _.filter(this.pluginsEnabled, (plugin) => {
            const pType = 'widgets.file.selectiondialog.' + plugin;
            
            if (! Ext.ComponentMgr.isPluginRegistered(pType)) {
                Tine.log.warn(`fileSelectionDialog: "${pType}" is not registered`);
                return false;
            }

            this.plugins.push(pType);
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
                plugin: this.activePlace.plugin,
                fileName: this.fileName
            })
        });
        
        return fileList;
    },

    onButtonApply: async function() {
        if (await this.activePlace.validateSelection() === false) {
            return;
        }
        
        return Tine.Tinebase.widgets.file.SelectionDialog.superclass.onButtonApply.apply(this, arguments);
    },
    
    // note, places are added async!
    addPlace: function(place) {
        this.places.push(place);

        place.treeNode = new Ext.tree.TreeNode({
            text: place.name,
            iconCls: place.iconCls
        });

        place.index = this.pluginsEnabled.indexOf(place.plugin);
        
        let nodeInserted = false;
        _.each(this.places, (p) => {
            if (p.index > place.index) {
                this.getPluginSelectionTree().getRootNode().insertBefore(place.treeNode, p.treeNode);
                nodeInserted = true;
            }
        });
        
        if (! nodeInserted) {
            this.getPluginSelectionTree().getRootNode().appendChild(place.treeNode);
        }
        
        if (place.plugin === this.pluginsEnabled[0]) {
            place.treeNode.select();
        }
    },

    onTreeSelectionChange: function(sm, node) {
        // this.ensureSelected(node);
        
        const place = _.find(this.places, {treeNode: node});

        place.manageButtonApply(this.buttonApply);
        
        _.each(['pluginPanel', 'targetForm', 'optionsForm'], (area) => {
            const cardPanel = _.get(this, area);
            const pluginArea = _.get(place, area);
            
            if (pluginArea) {
                if (cardPanel.items.indexOf(pluginArea) < 0) {
                    _.get(this, area).add(pluginArea);
                }
                
                cardPanel.layout.setActiveItem(pluginArea.id);
                cardPanel.show();
                if (_.indexOf(['north', 'south'], cardPanel.region) >= 0) {
                    cardPanel.setHeight(pluginArea.getHeight());
                }
                cardPanel.doLayout();
            } else {
                cardPanel.hide();
            }
        });
        
        // manage pluginSelection
        if (place.plugin === 'filemanager') {
            place.pluginPanel.westPanel.getPortalColumn().insert(0, this.getPluginSelectionTree());
            this.westPanel.hide();
        } else {
            this.westPanel.show();
            this.westPanel.insert(0, this.getPluginSelectionTree());
        }
        
        this.activePlace = place;
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
