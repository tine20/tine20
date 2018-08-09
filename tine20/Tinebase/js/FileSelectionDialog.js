/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Tinebase');

/**
 * File picker dialog
 *
 * @namespace   Tine.Tinebase
 * @class       Tine.Tinebase.FileSelectionDialog
 * @extends     Tine.Tinebase.dialog.Dialog
 * @constructor
 * @param       {Object} config The configuration options.
 */
Tine.Tinebase.FileSelectionDialog = Ext.extend(Tine.Tinebase.dialog.Dialog, {
    layout: 'fit',

    /**
     * Dialog window
     */
    window: null,

    /**
     * Window title
     */
    title: null,

    /**
     * Hide panel header by default
     */
    header: false,

    /**
     * The validated and choosen node
     */
    nodes: null,

    /**
     * Filepicker singleton
     */
    filePicker: null,

    windowNamePrefix: 'FileSelectionDialog_',
    cls: 'tw-editdialog',

    /**
     * Constructor.
     */
    initComponent: function () {
        this.items = {
            xtype: 'tabpanel',
            border: false,
            plain: true,
            activeTab: 0,
            plugins: [{
                ptype: 'ux.tabpanelkeyplugin'
            }],
            items: []
        };


        this.fbar = [
            '->',
            this.okAction
        ];

        this.initFileupload();
        this.initFilemanager();

        Tine.Tinebase.FileSelectionDialog.superclass.initComponent.call(this);
    },

    handler: function (fileSelector, e) {
        throw new Error('Handler not implemented.');
    },

    initFilemanager: function () {
        if (!Tine.Tinebase.appMgr.isEnabled('Filemanager')) {
            return;
        }

        this.items.items.push({
            layout: 'fit',
            title: i18n._('Filemanager'),
            items: [
                this.getFilePicker()
            ]
        });
    },

    getEventData: function () {
        return this.nodes;
    },
    
    afterRender: function () {
        Tine.Tinebase.widgets.dialog.PasswordDialog.superclass.afterRender.call(this);
        this.buttonApply.setDisabled(true);
    },

    initFileupload: function () {
        this.fileSelectionArea = new Tine.widgets.form.FileSelectionArea({
            text: i18n._('Select or drop file to upload'),
            region: 'center',
            margins: {top: 5, right: 5, bottom: 5, left: 5}
        });

        this.items.items.push({
            layout: 'border',
            title: i18n._('Fileupload'),
            items: [
                this.fileSelectionArea
            ]
        });

        this.fileSelectionArea.on('fileSelected', this.onFileLocalfileSelected.createDelegate(this));
    },

    onFileLocalfileSelected: function (selector, e) {
        this.handler(selector, e);
        this.window.close();
    },
    

    /**
     * Create a new filepicker and register listener
     * @returns {*}
     */
    getFilePicker: function () {
        if (this.filePicker === null) {
            this.filePicker = new Tine.Filemanager.FilePicker({
                constraint: 'file',
                singleSelect: false
            });

            this.filePicker.on('nodeSelected', this.onNodesSelected.createDelegate(this));
            this.filePicker.on('invalidNodeSelected', this.onInvalidNodesSelected.createDelegate(this));
        }

        return this.filePicker;
    },

    /**
     * If a node was selected
     * @param nodes
     */
    onNodesSelected: function (nodes) {
        this.nodes = nodes;
        this.buttonApply.setDisabled(false);
    },

    /**
     * If an invalid node was selected
     */
    onInvalidNodesSelected: function () {
        this.buttonApply.setDisabled(true);
    },

    onButtonApply: function() {
        this.handler(this.nodes);
        Tine.Tinebase.FileSelectionDialog.superclass.onButtonApply.apply(this, arguments);
    },
    
    /**
     * Creates a new pop up dialog/window (acc. configuration)
     *
     * @returns {null}
     */
    openWindow: function (config) {
        if (this.window) {
            return this.window;
        }

        config = config || {};

        this.window = Tine.WindowFactory.getWindow(Ext.apply({
            title: this.windowTitle,
            closeAction: 'close',
            modal: true,
            width: 550,
            height: 500,
            plain: true,
            layout: 'fit',
            items: [
                this
            ]
        }, config));

        return this.window;
    }
});