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
 * @extends     Tine.Tinebase.dialog.Dialog
 * @constructor
 * @param       {Object} config The configuration options.
 */
Tine.Filemanager.FilePickerDialog = Ext.extend(Tine.Tinebase.dialog.Dialog, {
    layout: 'fit',

    /**
     * Dialog window
     */
    window: null,

    /**
     * The validated and chosen node
     */
    nodes: null,

    /**
     * Allow to select one or more node
     */
    singleSelect: true,

    /**
     * allow creation of new files
     * @cfg {Boolean} allowCreateNew
     */
    allowCreateNew: false,

    /**
     * initial fileName for new files
     * @cfg {String} initialNewFileName
     */
    initialNewFileName: '',

    /**
     * initial path
     * @cfg {String} initialPath
     */
    initialPath: null,

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
     * @cfg {Array} requiredGrants
     * grants which are required to select nodes
     */
    requiredGrants: ['readGrant'],

    windowNamePrefix: 'FilePickerDialog_',

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

        this.on('apply', function() {
            this.fireEvent('selected', this.nodes);
        }, this);

        this.app = this.app || Tine.Tinebase.appMgr.get('Filemanager');

        if (! this.windowTitle) {
            switch(this.constraint) {
                case 'file':
                    this.windowTitle = this.singleSelect ? this.app.i18n._('Select a file') : this.app.i18n._('Select files');
                    break;
                case 'folder':
                    this.windowTitle = this.singleSelect ? this.app.i18n._('Select a folder') : this.app.i18n._('Select folders');
                    break;
                default:
                    this.windowTitle = this.singleSelect ? this.app.i18n._('Select an item') : this.app.i18n._('Select items');
                    break;
            }
        }

        this.windowTitle = this.windowTitle || this.app.i18n._('')
        Tine.Filemanager.FilePickerDialog.superclass.initComponent.call(this);
    },

    getEventData: function () {
        return this.nodes;
    },

    /**
     * Create a new filepicker and register listener
     * @returns {*}
     */
    getFilePicker: function () {
        var picker = new Tine.Filemanager.FilePicker({
            requiredGrants: this.requiredGrants,
            constraint: this.constraint,
            singleSelect: this.singleSelect,
            allowCreateNew: this.allowCreateNew,
            initialNewFileName: this.initialNewFileName,
            initialPath: this.initialPath
        });

        picker.on('nodeSelected', this.onNodesSelected.createDelegate(this));
        picker.on('invalidNodeSelected', this.onInvalidNodesSelected.createDelegate(this));

        return picker;
    },

    /**
     * If a node was selected
     * @param nodes
     */
    onNodesSelected: function (nodes) {
        this.nodes = nodes;
        this.buttonApply.setDisabled(false);
    },

    afterRender: function () {
        Tine.Filemanager.FilePickerDialog.superclass.afterRender.apply(this, arguments);
        this.buttonApply.setDisabled(true);
    },
    
    /**
     * If an invalid node was selected
     */
    onInvalidNodesSelected: function () {
        this.buttonApply.setDisabled(true);
    },

    /**
     * Creates a new pop up dialog/window (acc. configuration)
     *
     * @returns {null}
     */
    openWindow: function (config) {
        this.window = Tine.WindowFactory.getWindow(_.assign({
            title: this.windowTitle,
            modal: true,
            width: 550,
            height: 500,
            layout: 'fit',
            plain: true,
            items: [
                this
            ]
        }, config));

        return this.window;
    }
});
