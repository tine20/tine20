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
 * @extends     Ext.Panel
 * @constructor
 * @param       {Object} config The configuration options.
 */
Tine.Tinebase.FileSelectionDialog = Ext.extend(Ext.Panel, {
    layout: 'fit',
    border: false,
    frame: false,

    /**
     * ok button action held here
     */
    okAction: null,

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
            activeTab: 1,
            plugins: [{
                ptype: 'ux.tabpanelkeyplugin'
            }],
            items: []
        };

        var me = this;
        this.okAction = new Ext.Action({
            disabled: true,
            text: 'Ok',
            iconCls: 'action_saveAndClose',
            minWidth: 70,
            handler: this.onOk.createDelegate(me),
            scope: this
        });

        this.fbar = [
            '->',
            this.okAction
        ];

        this.initFilemanager();
        this.initFileupload();

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
     * button handler
     */
    onOk: function () {
        this.fireEvent('selected', this.nodes);
        this.handler(this.nodes);
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
        this.okAction.setDisabled(false);
    },

    /**
     * If an invalid node was selected
     */
    onInvalidNodesSelected: function () {
        this.okAction.setDisabled(true);
    },

    /**
     * Creates a new pop up dialog/window (acc. configuration)
     *
     * @returns {null}
     */
    openWindow: function () {
        if (this.window) {
            return this.window;
        }

        this.window = Tine.WindowFactory.getWindow({
            title: this.title,
            closeAction: 'close',
            modal: true,
            width: 550,
            height: 500,
            layout: 'fit',
            plain: true,

            items: [
                this
            ]
        });

        return this.window;
    }
});