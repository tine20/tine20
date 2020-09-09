/*
 * Tine 2.0
 *
 * @license      New BSD License
 * @author       loeppky - based on the work done by MaximGB in Ext.ux.UploadDialog (http://extjs.com/forum/showthread.php?t=21558)
 * @author       Michael Spahn <m.spahn@metaways.de>
 * @copyright    Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.ns('Ext.ux.file');

/**
 * @namespace   Ext.ux.file
 * @class       Ext.ux.file.BrowsePlugin
 * @param       {Object} config Configuration options
 */
Ext.ux.file.BrowsePlugin = function (config) {
    Ext.apply(this, config);
};

/**
 * @TODO: proxy mouse events
 */
Ext.ux.file.BrowsePlugin.prototype = {
    /**
     * @cfg {Boolean} multiple
     * allow multiple files to be selected (HTML 5 only)
     */
    multiple: false,
    /**
     * @cfg {Ext.Element} dropEl
     * element used as drop target if enableFileDrop is enabled
     */
    dropEl: null,
    /**
     * @cfg {Boolean} enableFileDrop
     * @see http://www.w3.org/TR/2008/WD-html5-20080610/editing.html
     *
     * enable drops from OS (defaults to true)
     */
    enableFileDrop: true,
    /**
     * @cfg {Boolean} enableFileDialog
     *
     * enable file dialog on click(defaults to true)
     */
    enableFileDialog: true,
    /**
     * @cfg {String} inputFileName
     * Name to use for the hidden input file DOM element.  Deaults to "file".
     */
    inputFileName: 'file',
    /**
     * @cfg {Function} onBrowseButtonClick
     */
    onBrowseButtonClick: Ext.emptyFn,
    /**
     * @property inputFileEl
     * @type Ext.Element
     * Element for the hidden file input.
     * @private
     */
    input_file: null,
    /**
     * @cfg handler
     * @type Function
     * The handler originally defined for the Ext.Button during construction using the "handler" config option.
     * We need to null out the "handler" property so that it is only called when a file is selected.
     * @private
     */
    handler: null,
    /**
     * @cfg scope
     * @type Object
     * The scope originally defined for the Ext.Button during construction using the "scope" config option.
     * While the "scope" property doesn't need to be nulled, to be consistent with handler, we do.
     * @private
     */
    scope: null,

    currentTreeNodeUI: null,

    /**
     * @see Ext.Button.initComponent
     */
    init: function (cmp) {
        this.component = cmp;

        if (cmp.handler && !Ext.isFunction(this.handler)) {
            this.handler = cmp.handler;
            cmp.handler = null;
            this.scope = cmp.scope || window;
            cmp.scope = null;
        }

        cmp.on('afterrender', this.onRender, this);
        cmp.on('destroy', this.onDestroy, this);
    },

    /**
     * drag and drop
     */
    onDragLeave: function (e) {
        e.stopPropagation();
        e.preventDefault();
        this.component.el.applyStyles(' background-color: transparent');
    },

    onDragOver: function (e) {
        e.stopPropagation();
        e.preventDefault();
        this.component.el.applyStyles(' background-color: #ebf0f5');
    },

    onDrop: function (e) {
        e.stopPropagation();
        e.preventDefault();
        this.component.el.applyStyles(' background-color: transparent');
        this.onBrowseButtonClick();
        var me = this;

        // easy cope with directories
        var fs = require('html5-file-selector'),
            dt = e.browserEvent.dataTransfer;
            fs.getDataTransferFiles(dt).then(function(files) {
                me.onInputFileChange(e, window.lodash.map(files, 'fileObject'));
            });
    },

    onRender: function () {
        var me = this;

        if (me.enableFileDialog) {
            me.component.on('enable', this.onEnable, this);
            me.component.on('disable', this.onDisable, this);
            me.component.el.dom.addEventListener('click', function () {
                
                // no input on split area
                if (me.component.split && me.component.el.getBox().right - Ext.EventObject.getXY()[0] < 18) {
                    return;
                }
                
                me.input_file.click();
            });
            me.createInputFile();
        }
        if (me.enableFileDrop && !Ext.isIE) {
            if (me.dropElSelector) {
                me.dropEl = me.component.el.up(me.dropElSelector);
            } else {
                me.dropEl = me.component.el;
            }

            me.dropEl.on('dragleave', me.onDragLeave, me);
            me.dropEl.on('dragover', me.onDragOver, me);
            me.dropEl.on('drop', me.onDrop, me);
        }
    },

    /**
     * clean up
     */
    onDestroy: function () {
        this.input_file = null;
        if (this.dropEl) {
            this.dropEl.un('dragleave', this.onDragLeave, this);
            this.dropEl.un('dragover', this.onDragOver, this);
            this.dropEl.un('drop', this.onDrop, this);
            this.dropEl = null;
        }
    },

    onEnable: function () {
        this.setDisabled(false);
    },

    onDisable: function () {
        this.setDisabled(true);
    },

    setDisabled: function (disabled) {
        this.input_file.disabled = disabled;
    },

    createInputFile: function () {
        this.input_file = document.createElement('input');
        this.input_file.setAttribute('name', this.inputFileName || Ext.id(this.component.el));
        this.input_file.setAttribute('type', 'file');
        this.input_file.setAttribute('style', 'display: none;');
        this.input_file.multiple = this.multiple;
        this.component.el.dom.appendChild(this.input_file);

        this.input_file.addEventListener('change', this.onInputFileChange.bind(this), false);
        this.input_file.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    },

    /**
     * Handler when inputFileEl changes value (i.e. a new file is selected).
     * @param {FileList} files when input comes from drop...
     * @private
     */
    onInputFileChange: function (e, files) {
        var _ = window.lodash;

        if (files) {
            this.files = files;
        } else {
            if (e.dataTransfer) {
                this.files = e.dataTransfer.files;
            } else {
                this.files = e.target.files;
            }
        }

        if (!_.isFunction(e.getTarget)) {
            // backwards compatibility
            e.getTarget = function () {
                return this.target;
            };
        }

        if (this.handler) {
            this.handler.call(this.scope, this, e);
        }
    },

    getFileList: function () {
        return this.files;
    },

    /**
     * @return {Ext.Element} the input file element
     */
    getInputFile: function () {
        return this.input_file;
    },

    /**
     * get file name
     * @return {String}
     */
    getFileName: function () {
        var file = this.getFileList()[0];
        return file.name ? file.name : file.fileName;
    },

    /**
     * returns file class based on name extension
     * @return {String} class to use for file type icon
     */
    getFileCls: function () {

        var fparts = this.getFileName().split('.');
        if (fparts.length === 1) {
            return '';
        }
        else {
            return fparts.pop().toLowerCase();
        }
    },

    isImage: function () {
        var cls = this.getFileCls();
        return (cls === 'jpg' || cls === 'gif' || cls === 'png' || cls === 'jpeg');
    }
};

Ext.preg('ux.browseplugin', Ext.ux.file.BrowsePlugin);
