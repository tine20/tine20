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
     * @todo: maybe remove
     */
    windowNamePrefix: 'test',

    /**
     * Allow to select one or more node
     */
    singleSelect: true,

    /**
     * A constraint allows to alter the selection behaviour of the picker, for example only allow to select files.
     *
     * By default, file and folder are allowed to be selected, the concrete implementation needs to define it's purpose
     *
     * Valids constraints:
     *  - file
     *  - folder
     *  - null (take all)
     */
    constraint: null,

    /**
     * Constructor.
     */
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
            '->',
            this.okAction
        ];

        Tine.Filemanager.FilePickerDialog.superclass.initComponent.call(this);
    },

    /**
     * button handler
     */
    onOk: function () {
        this.fireEvent('selected', this.nodes);
        this.window.close();
    },

    /**
     * Create a new filepicker and register listener
     * @returns {*}
     */
    getFilePicker: function () {
        var picker = new Tine.Filemanager.FilePicker({
            constraint: this.constraint,
            singleSelect: this.singleSelect
        });

        picker.on('nodeSelected', this.onNodesSelected.createDelegate(this));
        picker.on('invalidNodeSelected', this.onInvalidNodesSelected.createDelegate(this));

        return picker;
    },

    /**
     * If a node was selected
     * @param node
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
        this.window = Tine.WindowFactory.getWindow({
            title: this.title,
            closeAction: 'close',
            modal: true,
            width: 550,
            height: 500,
            layout: 'fit',
            plain: true,
            bodyStyle: 'padding:5px;',

            items: [
                this
            ]
        });

        return this.window;
    }
});
