
Ext.ns('Tine.widgets.dialog');

Tine.widgets.dialog.FileListDialog = function(config) {
    
    Tine.widgets.dialog.FileListDialog.superclass.constructor.call(this, config);
    
    this.options = config.options || {};
    this.scope = config.scope || window;
};

/**
 * @namespace   Tine.widgets.dialog
 * @class       Tine.widgets.dialog.FileListDialog
 * @extends     Ext.FormPanel
 */
Ext.extend(Tine.widgets.dialog.FileListDialog, Ext.FormPanel, {
    /**
     * @cfg {Array} options
     * @see {Ext.fom.CheckBoxGroup}
     */   
    options: null,
    /**
     * @cfg {Object} scope
     */
    scope: null,
    /**
     * @cfg {String} questionText defaults to i18n._('What would you like to do?')
     */
    questionText: null,
    /**
     * @cfg {String} invalidText defaults to i18n._('You need to select an option!')
     */
    invalidText: null,
    /**
     * @cfg {Function} handler
     */
    handler: Ext.emptyFn,
    /**
     * @cfg {Boolean} allowCancel
     */
    allowCancel: false,
    
    windowNamePrefix: 'FileListDialog',
    bodyStyle:'padding:5px',
    layout: 'fit',
    border: false,
    cls: 'tw-editdialog',
    anchor:'100% 100%',
    deferredRender: false,
    buttonAlign: null,
    bufferResize: 500,
    buttonOptions: ['Yes', 'No'],

    initComponent: function() {
        // init buttons and tbar
        this.initButtons();
        
        this.itemsName = this.id + '-fileListc';
        
        // get items for this dialog
        this.items = [{
            layout: 'hbox',
            border: false,
            layoutConfig: {
                align:'stretch'
            },
            items: [{
                border: false,
                layout: 'fit',
                flex: 1,
                autoScroll: true,
                items: [{
                    xtype: 'label',
                    border: false,
                    cls: 'ext-mb-text',
                    html: this.text
                }]
            }]
        }];
        
        this.supr().initComponent.call(this);
    },
    
    /**
     * init buttons
     * use preference settings for order of yes and no buttons
     */
    initButtons: function () {
        this.fbar = ['->'];
        
        if (this.buttonOptions.includes('No')) {
            this.noBtn = {
                xtype: 'button',
                text: i18n._('No'),
                minWidth: 70,
                scope: this,
                handler: this.onCancel,
                iconCls: 'action_cancel',
            };
            this.fbar.push(this.noBtn);
        }
        const output = ['Yes', 'Ok'].filter((obj) => this.buttonOptions.indexOf(obj) !== -1);
        if (output) {
            this.yesBtn = {
                xtype: 'button',
                text: i18n._(output[0]),
                minWidth: 70,
                scope: this,
                handler: this.onOk,
                iconCls: 'action_applyChanges'
            };
            this.fbar.push(this.yesBtn);
        }
    },
    
    onOk: function() {
        this.handler.call(this.scope, 'yes');
        this.window.close();
    },
    
    onCancel: function() {
        this.handler.call(this.scope, 'no');
        this.window.close();
    }
});

/**
 * grants dialog popup / window
 */
Tine.widgets.dialog.FileListDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: config.width || 400,
        height: config.height || 150,
        closable: false,
        name: Tine.widgets.dialog.FileListDialog.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.widgets.dialog.FileListDialog',
        contentPanelConstructorConfig: config,
        modal: true
    });
    return window;
};
