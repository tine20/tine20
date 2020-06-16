/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

require('widgets/file/SelectionDialog/AbstractPlugin');

Ext.ns('Tine.Filemanager.FileSelectionDialog');

Tine.Filemanager.FileSelectionDialog.FilemanagerPlugin = function(plugin) {
    Ext.apply(this, plugin);

    this.app = Tine.Tinebase.appMgr.get('Filemanager');
    this.name = this.app.getTitle();
    
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
    
    this.cmp.addPlace(this);
};

Ext.extend(Tine.Filemanager.FileSelectionDialog.FilemanagerPlugin, Tine.Tinebase.widgets.file.SelectionDialog.AbstractPlugin, {
    plugin: 'filemanager',
    iconCls: 'FilemanagerIconCls',

    /**
     * @property {Boolean} validSelection
     */
    validSelection: false,
    
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
        // @TODO map certain properties only?
        return this.pluginPanel.selection;
    },

    validateSelection: async function() {
        return this.pluginPanel.validateSelection();
    },
    
    manageButtonApply: function(buttonApply) {
        buttonApply.setDisabled(!this.validSelection);
    },
});
