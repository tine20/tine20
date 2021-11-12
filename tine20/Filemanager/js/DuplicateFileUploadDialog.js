/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Filemanager');

Tine.Filemanager.DuplicateFileUploadDialog = Ext.extend(Ext.FormPanel, {
    
    /**
     * @cfg {String} windowTitle
     * title text when openWindow is used
     */
    windowTitle: '',

    /**
     * @cfg {String} file name
     */
    fileName: '',

    /**
     * @cfg {String} file type
     */
    fileType: '',

    /**
     * @cfg {String} batchID of current upload
     */
    batchID: null,

    // private
    cls: 'tw-editdialog',
    layout: 'fit',
    bodyStyle:'padding:5px',
    border: false,
    anchor:'100% 100%',
    deferredRender: false,
    buttonAlign: null,
    bufferResize: 500,

    applyToAll: false,
    
    /**
     * @cfg {Function} handler
     */
    handler: Ext.emptyFn,
    /**
     * Constructor.
     */
    initComponent: function () {
        this.app = this.app || Tine.Tinebase.appMgr.get('Filemanager');

        this.questionText =  String.format(this.app.i18n._('File named {0} already exist in this location. Do you want to replace it with the current one?'), this.fileName);
        this.options =  [
            {text: this.app.i18n._('Apply to All'), name: 'apply_to_all'}
        ];
        
        this.initButtons();
        
        this.itemsName = this.id + '-radioItems';
        
        this.items = {
            layout: 'hbox',
            border: false,
            layoutConfig: {
                align:'stretch'
            },
            items : [
                {
                    border: false,
                    html: '<div class="x-window-dlg"><div class="ext-mb-icon ext-mb-question"></div></div>',
                    flex: 0,
                    width: 45
                },
                {
                    border: false,
                    layout: 'vbox',
                    flex: 1,
                    layoutConfig: {
                        align:'stretch'
                    },
                    items: [
                        {
                            xtype: 'label',
                            border: false,
                            cls: 'ext-mb-text',
                            html: this.questionText
                        }
                    ]
                }
            ]
        };
        
        Tine.Filemanager.DuplicateFileUploadDialog.superclass.initComponent.call(this);
    },

    afterRender: function () {
        Tine.Filemanager.DuplicateFileUploadDialog.superclass.afterRender.call(this);
    },

    /**
     * init buttons
     */
    initButtons: function() {
        this.fbar = [{
            xtype: 'checkbox',
            ctCls: 'checkbox-footbar',
            hideLabel: true,
            boxLabel: this.app.i18n._('Apply to All'),
            listeners: {
                'check': function(checkbox, value) {
                    this.applyToAll = value;
                    Tine.log.debug('Tine.Filemanager.DuplicateFileUploadDialog ::apply to all uploads -> ' + value);
                },
                scope: this
            }
        }, '->', {
            xtype: 'button',
            text: this.app.i18n._('Skip'),
            minWidth: 70,
            scope: this,
            handler: () => {
                this.handleApplyAll('skip');
            }
        }, {
            xtype: 'button',
            text: this.app.i18n._('Stop'),
            minWidth: 70,
            scope: this,
            handler: () => {
                this.handleApplyAll('stop');
            }
        }, {
            xtype: 'button',
            text: this.app.i18n._('Replace'),
            minWidth: 70,
            scope: this,
            handler: () => {
                this.handleApplyAll('replace');
            }
        }];
    },
    
    handleApplyAll: async function (button) {
        if (this.applyToAll || button === 'stop') {
            if (button === 'skip' || button === 'stop') {
                await Tine.Tinebase.uploadManager.stopBatchUploads(this.batchID);
            }

            if (button === 'replace') {
                await Tine.Tinebase.uploadManager.overwriteBatchUploads(this.batchID);
            }

            _.each(Tine.Filemanager.DuplicateFileUploadDialog.openWindow.stack, (window) => {
                window.handler.call(window.scope, button);
            });

            Tine.Filemanager.DuplicateFileUploadDialog.openWindow.stack = [];
        }
        
        this.handler.call(this.scope, button);
        this.window.close();
    }
});

/**
 * Creates a new pop up dialog/window (acc. configuration)
 *
 * @returns {null}
 */
Tine.Filemanager.DuplicateFileUploadDialog.openWindow =  function (config) {
    if (Tine.Filemanager.DuplicateFileUploadDialog.openWindow.current) {
        Tine.Filemanager.DuplicateFileUploadDialog.openWindow.stack.push(config);
        return ;
    }
    
    const constructor = 'Tine.Filemanager.DuplicateFileUploadDialog'
    const prototype = eval(constructor).prototype;
    this.app = this.app || Tine.Tinebase.appMgr.get('Filemanager');
    
    this.window = Tine.WindowFactory.getWindow({
        closable: false,
        title: this.app.i18n._('Overwrite Existing File?'),
        width: config.width || 400,
        height: config.height || 150,
        name: prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: constructor,
        contentPanelConstructorConfig: config,
        modal: true
    });

    this.window.on('close', function() {
        Tine.Filemanager.DuplicateFileUploadDialog.openWindow.current = null;

        if (Tine.Filemanager.DuplicateFileUploadDialog.openWindow.stack.length) {
            const config = Tine.Filemanager.DuplicateFileUploadDialog.openWindow.stack.pop();
            Tine.Filemanager.DuplicateFileUploadDialog.openWindow(config);
        }
    }, this);
    
    Tine.Filemanager.DuplicateFileUploadDialog.openWindow.current = this.window;
    return this.window;
}
Tine.Filemanager.DuplicateFileUploadDialog.openWindow.stack = [];

Ext.ux.ItemRegistry.registerItem('Tine.Filemanager.DuplicateFileUploadDialog', Tine.Filemanager.DuplicateFileUploadDialog);
