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
     * check if file exists on file creation
     * @cfg {Boolean} checkOverwrite
     */
    checkOverwrite: true,

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

        this.on('apply', async function() {
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

    onButtonApply: async function() {
        if (this.allowCreateNew && this.checkOverwrite && !await this.assertCheckOverwrite(this.nodes[0])) {
            return;
        }

        Tine.Filemanager.FilePickerDialog.superclass.onButtonApply.call(this);
    },

    assertCheckOverwrite: async function(node) {
        return new Promise((resolve) => {
            const loadMask = new Ext.LoadMask(this.getEl(), {
                msg: this.app.i18n._('Checking ...'),
                removeMask: true
            });
            loadMask.show();

            Tine.Filemanager.searchNodes([
                {field: 'path', operator: 'equals', value: node.get('path')}
            ]).then((results) => {
                loadMask.hide();
                const title = i18n._('Overwrite Existing File?');
                const msg = i18n._('Do you really want to overwrite the selected file?');
                Ext.MessageBox.confirm(title, msg, (btn) => {
                    resolve(btn === 'yes');
                });
            }).catch(() => {
                // NOTE: path filter throws an error in not existent
                resolve(true);
            })
        });
    },

    getEventData: function () {
        return this.nodes;
    },

    /**
     * Create a new filepicker and register listener
     * @returns {*}
     */
    getFilePicker: function () {
        if (! this.filePicker) {
            this.filePicker = new Tine.Filemanager.FilePicker({
                requiredGrants: this.requiredGrants,
                constraint: this.constraint,
                singleSelect: this.singleSelect,
                allowCreateNew: this.allowCreateNew,
                initialNewFileName: this.initialNewFileName,
                initialPath: this.initialPath
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
