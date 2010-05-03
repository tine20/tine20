
Ext.namespace('Tine.widgets.dialog');

Tine.widgets.dialog.MultiOptionsDialog = function(config) {
    
    Tine.widgets.dialog.MultiOptionsDialog.superclass.constructor.call(this, config);
    
    this.options = config.options || {};
    this.scope = config.scope || window;
};

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
    
    windowNamePrefix: 'MultiOptionsDialog',
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
        
        this.itemsName = this.id + '-radioItems';
        
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
                items: [{
                    xtype: 'label',
                    cls: 'ext-mb-text',
                    text: this.questionText || _('What would you like to do?')
                }, {
                    xtype: 'radiogroup',
                    hideLabel: true,
                    itemCls: 'x-check-group-alt',
                    columns: 1,
                    name: 'optionGroup',
                    items: this.getItems()
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
            text: _('Ok'),
            minWidth: 70,
            scope: this,
            handler: this.onOk,
            iconCls: 'action_saveAndClose'
        }, {
            xtype: 'button',
            text: _('Cancel'),
            minWidth: 70,
            scope: this,
            handler: this.onCancel,
            iconCls: 'action_cancel'
        }];
    },
    
    getItems: function() {
        var items = [];
        Ext.each(this.options, function(option) {
            items.push({
                checked: !! option.checked,
                fieldLabel: '',
                labelSeparator: '',
                boxLabel: option.text,
                name: this.itemsName,
                inputValue: option.name
            });
        }, this);
        
        return items;
    },
    
    onOk: function() {
        var field = this.getForm().findField('optionGroup');
        var selecedRadio = field.getValue();
        
        var option = selecedRadio ? selecedRadio.getGroupValue() : null;
        
        if (! option) {
            field.markInvalid(this.invalidText || _('You need to select an option!'));
            return;
        }
        this.handler.call(this.scope, option);
        this.window.close();
    },
    
    onCancel: function() {
        this.handler.call(this.scope, 'cancel');
        this.window.close();
    }
});

/**
 * grants dialog popup / window
 */
Tine.widgets.dialog.MultiOptionsDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 400,
        height: 150,
        closable: false,
        name: Tine.widgets.dialog.MultiOptionsDialog.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.widgets.dialog.MultiOptionsDialog',
        contentPanelConstructorConfig: config,
        modal: true
    });
    return window;
};