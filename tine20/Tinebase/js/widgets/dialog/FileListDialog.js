
Ext.ns('Tine.widgets.dialog');

Tine.widgets.dialog.MultiOptionsDialog = function(config) {
    
    Tine.widgets.dialog.MultiOptionsDialog.superclass.constructor.call(this, config);
    
    this.options = config.options || {};
    this.scope = config.scope || window;
};

/**
 * @namespace   Tine.widgets.dialog
 * @class       Tine.widgets.dialog.MultiOptionsDialog
 * @extends     Ext.FormPanel
 */
Ext.extend(Tine.widgets.dialog.MultiOptionsDialog, Ext.FormPanel, {
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
     * @cfg {String} questionText defaults to _('What would you like to do?')
     */
    questionText: null,
    /**
     * @cfg {String} invalidText defaults to _('You need to select an option!')
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
    
    initComponent: function() {
        // init buttons and tbar
        this.initButtons();
        
        this.itemsName = this.id + '-fileList';
        
        // get items for this dialog
        this.items = [{
            layout: 'hbox',
            border: false,
            layoutConfig: {
                align:'stretch'
            },
            items: [{
                border: false,
                html: '<div class="x-window-dlg"><div class="ext-mb-icon ext-mb-question"></div></div>',
                flex: 0,
                width: 45
            }, {
                border: false,
                layout: 'fit',
                flex: 1,
                autoScroll: true,
                items: [{
                    xtype: 'label',
                    border: false,
                    cls: 'ext-mb-text',
                    html: this.fileText
                }]
            }]
        }];
        
        this.supr().initComponent.call(this);
    },
    
    /**
     * init buttons
     */
    initButtons: function() {
        this.fbar = ['->', {
            xtype: 'button',
            text: _('Yes'),
            minWidth: 70,
            scope: this,
            handler: this.onOk,
            iconCls: 'action_saveAndClose'
        }, {
            xtype: 'button',
            text: _('No'),
            minWidth: 70,
            scope: this
            handler: this.onCancel,
            iconCls: 'action_cancel'
        }];
    }
    
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
Tine.widgets.dialog.MultiOptionsDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: config.width || 400,
        height: config.height || 150,
        closable: false,
        name: Tine.widgets.dialog.MultiOptionsDialog.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.widgets.dialog.MultiOptionsDialog',
        contentPanelConstructorConfig: config,
        modal: true
    });
    return window;
};
