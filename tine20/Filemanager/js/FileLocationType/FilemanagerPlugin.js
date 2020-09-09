/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

require('widgets/file/LocationTypePlugin/Abstract');

Ext.ns('Tine.Filemanager.FileLocationType');

Tine.Filemanager.FileLocationType.FilemanagerPlugin = function(config) {
    Ext.apply(this, config);

    this.app = Tine.Tinebase.appMgr.get('Filemanager');
    this.name = this.app.getTitle();
};

Ext.extend(Tine.Filemanager.FileLocationType.FilemanagerPlugin, Tine.Tinebase.widgets.file.LocationTypePlugin.Abstract, {
    locationType: 'fm_node',
    iconCls: 'FilemanagerIconCls',

    /**
     * @property {Boolean} validSelection
     */
    validSelection: false,

    getSelectionDialogArea: async function(area, cmp) {
        if (! this.selectionDialogInitialised) {
            this.cmp = cmp;
            this.pluginPanel = new Tine.Filemanager.FilePicker(Ext.apply({
                mode: this.cmp.mode,
                allowMultiple: this.cmp.allowMultiple,
                constraint: this.cmp.constraint,
                requiredGrants: this.cmp.requiredGrants,
                fileName: this.cmp.fileName,
                initialPath: this.cmp.initialPath
            }, _.get(this, 'cmp.pluginConfig.' + this.plugin, {})));

            this.pluginPanel.on('nodeSelected', this.onNodesSelected, this);
            this.pluginPanel.on('forceNodeSelected', this.onForceNodesSelected, this);
            this.pluginPanel.on('invalidNodeSelected', this.onInvalidNodeSelected, this);

            this.selectionDialogInitialised = true;
        }
        return _.get(this, area);
    },
    
    onNodesSelected: function(nodes) {
        this.nodes = nodes;
        
        this.validSelection = true;
        this.manageButtonApply(this.cmp.buttonApply);
    },

    onForceNodesSelected: function(nodes) {
        this.cmp.onButtonApply();    
    },
    
    onInvalidNodeSelected: function(nodes) {
        this.validSelection = false;
        this.manageButtonApply(this.cmp.buttonApply);
    },

    getFileList: function() {
        return _.map(this.pluginPanel.selection, (node) => {
            return {
                fm_path: node.path,
                node_id: node
            };
        });
    },
    
    validateSelection: async function() {
        return this.pluginPanel.validateSelection();
    },
    
    manageButtonApply: function(buttonApply) {
        buttonApply.setDisabled(!this.validSelection);
    },

    getLocationName: function(location) {
        return location.fm_path;
    }
});
