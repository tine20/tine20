/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

require('./AbstractPlugin');
require('../../form/FileSelectionArea');

Ext.ns('Tine.Tinebase.widgets.file.SelectionDialog');

Tine.Tinebase.widgets.file.SelectionDialog.UploadPlugin = function(plugin) {
    Ext.apply(this, plugin);

    this.name = i18n._('From My Computer');
    
    if (this.uploadMode === 'select') {
        this.pluginPanel = new Tine.widgets.form.FileSelectionArea(Ext.apply({
            text: i18n._('Select or drop file to upload'),
        }, _.get(this, 'cmp.pluginConfig.' + this.plugin, {})));
        
        this.pluginPanel.on('fileSelected', this.onFilesSelected, this);
    } else {
        // use fileUploadGrid here, have nice uploadIcon in Background ;-)
        throw new Error('implement me');
    }

    this.cmp.addPlace(this);
};

Ext.extend(Tine.Tinebase.widgets.file.SelectionDialog.UploadPlugin, Tine.Tinebase.widgets.file.SelectionDialog.AbstractPlugin, {
    plugin: 'upload',
    iconCls: 'action_upload',
    
    /**
     * @cfg {String} uploadMode select|upload
     */
    uploadMode: 'select',

    getFileList: function() {
        if (this.uploadMode === 'select') {
            return this.pluginPanel.fileList;
        } else {
            // @todo return tempfiles
            throw new Error('implement me');
        }
    },

    onFilesSelected: function(fileList, event) {
        if (this.uploadMode === 'select') {
            this.cmp.onButtonApply();
        }
    },

    manageButtonApply: function(buttonApply) {
        if (this.uploadMode === 'select') {
            buttonApply.setDisabled(true);
        }
    }
});
