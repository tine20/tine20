/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Filemanager');

/**
 * File picker dialog
 *
 * @namespace   Tine.Filemanager
 * @class       Tine.Filemanager.FilePickerDialog
 * @extends     Ext.Panel
 * @constructor
 * @param       {Object} config The configuration options.
 */
Tine.Filemanager.FilePickerDialog = Ext.extend(Ext.Panel, {
    layout: 'fit',
    border: false,
    okAction: null,

    node: null,

    windowNamePrefix: 'test',

    initComponent: function () {
        this.addEvents(
            /**
             * If the dialog will close and an valid node was selected
             * @param node
             */
            'selected'
        );

        this.items = [{
            layout: 'fit',
            items: [
                this.getFilePicker()
            ]
        }];

        var me = this;
        this.okAction = new Ext.Action({
            disabled: true,
            text: 'Ok',
            iconCls: 'action_saveAndClose',
            minWidth: 70,
            handler: this.onOk.createDelegate(me),
            scope: this
        });

        this.bbar = [
            this.okAction
        ];

        Tine.Filemanager.FilePickerDialog.superclass.initComponent.call(this);
    },

    /**
     * button handler
     */
    onOk: function () {
        this.fireEvent('selected', this.node);
        this.window.close();
    },

    /**
     * Create a new filepicker and register listener
     * @returns {*}
     */
    getFilePicker: function () {
        var picker = new Tine.Filemanager.FilePicker({
            constraint: 'file'
        });

        picker.on('nodeSelected', this.onNodeSelected.createDelegate(this));
        picker.on('invalidNodeSelected', this.onInvalidNodeSelected.createDelegate(this));

        return picker;
    },

    /**
     * If a node was selected
     * @param node
     */
    onNodeSelected: function (node) {
        this.node = node;
        this.okAction.setDisabled(false);
    },

    /**
     * If an invalid node was selected
     */
    onInvalidNodeSelected: function () {
        this.okAction.setDisabled(true);
    }
});

Tine.Filemanager.FilePickerDialog.openWindow = function (config) {
    return Tine.WindowFactory.getWindow({
        width: 480,
        height: 400,
        modal: true,
        name: Tine.Filemanager.FilePickerDialog.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Filemanager.FilePickerDialog',
        contentPanelConstructorConfig: config
    });
};
