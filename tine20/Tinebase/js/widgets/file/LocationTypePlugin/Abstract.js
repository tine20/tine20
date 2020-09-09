/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Tinebase.widgets.file.LocationTypePlugin');


Tine.Tinebase.widgets.file.LocationTypePlugin.Abstract = function(plugin) {
    Ext.apply(this, plugin);
}

Tine.Tinebase.widgets.file.LocationTypePlugin.Abstract.prototype = {
    /**
     * @cfg {String} locationType
     * locationType name
     */
    locationType: '',
    
    /**
     * @cfg {String} name
     * _translated_ name of this locationType (required)
     */
    name: '',
    
    /**
     * @cfg {String} iconCls
     * iconCls for place selection
     */
    iconCls: '',
    
    /**
     * @param area one of pluginPanel|targetForm|optionsForm
     * @return {Promise<void>}
     */
    getSelectionDialogArea: async function(area, cmp) {},
    
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
    validateSelection: async function () {},

    /**
     * get string representation of given location
     *
     * @param {Object} location
     * @return {String} string representation of location
     */
    getLocationName: function(location) {}
};
