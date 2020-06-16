/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Tinebase.widgets.file.SelectionDialog');

/**
 * NOTE: you need to register your plugin
 *       see {Tine.Tinebase.widgets.file.SelectionDialog.InitUploadPlugin} as example
 */
Tine.Tinebase.widgets.file.SelectionDialog.AbstractPlugin = function(plugin) {
    Ext.apply(this, plugin);
}

Tine.Tinebase.widgets.file.SelectionDialog.AbstractPlugin.prototype = {
    /**
     * @cfg {String} plugin
     * internal plugin name
     */
    plugin: '',
    
    /**
     * @cfg {String} name
     * _translated_ name of this plugin (required)
     */
    name: '',
    
    /**
     * @cfg {String} iconCls
     * iconCls for place selection
     */
    iconCls: '',

    /**
     * @cfg {Ext.Panel} pluginPanel
     */
    pluginPanel: null,

    /**
     * @cfg {Ext.Panel} targetForm
     * optional northPanel
     */
    targetForm: null,

    /**
     * @cfg {Ext.Panel} optionsForm
     * optional southPanel
     */
    optionsForm: null,
    
    /**
     * @return {Array}
     */
    getFileList: function() {},

    /**
     * manage state of apply button
     * use buttonApply.setDisabled({Boolean}) to manage state
     * @param {Ext.Action} buttonApply
     */
    manageButtonApply: function(buttonApply) {},

    /**
     * called before dialog closes, return false to suppress selection
     * @return {Promise<void>}
     */
    validateSelection: async function () {}
};
