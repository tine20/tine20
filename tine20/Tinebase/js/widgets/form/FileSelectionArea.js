/*
 * Tine 2.0
 *
 * @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Michael Spahn <m.spahn@metaways.de>
 * @copyright    Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.ns('Tine.widgets.form');

/**
 * FileSelectionArea - no upload, just selection here atm.
 *
 * @namespace   Tine.widgets.form
 * @class       Tine.widgets.form.FileSelectionArea
 * @extends     Ext.Button
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @constructor
 * @param {Object} config The configuration options.
 */
Tine.widgets.form.FileSelectionArea = Ext.extend(Tine.widgets.form.FileUploadButton, {
    cls: 'tw-FileSelectionArea',

    multiple: true,

    /**
     * init this upload button
     */
    initComponent: function () {
        this.events = [
            /**
             * @event fileSelected
             * Fires when a file was selected
             * @param {FileList}
             */
            'fileSelected'
        ];
        Tine.widgets.form.FileSelectionArea.superclass.initComponent.call(this);
    },

    // bye bye layout probs.
    doAutoWidth: Ext.emptyFn,

    /**
     * called when a file got selected
     *
     * @param {ux.BrowsePlugin} fileSelector
     * @param {Ext.EventObject} event
     */
    onFileSelect: function (fileSelector, event) {
        this.fileList = fileSelector.getFileList();
        this.fireEvent('fileSelected', this.fileList);
    }
});

Ext.reg('tw.uploadarea', Tine.widgets.form.FileSelectionArea);
