/*
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2009-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Sales');

/**
 * @namespace   Tine.Sales
 * @class       Tine.Sales.AdminPanel
 * @extends     Ext.FormPanel
 * @author      Alexander Stintzing <alex@stintzing.net>
 */
Tine.Sales.AdminPanel = Ext.extend(Ext.FormPanel, {
    appName : 'Sales',

    layout : 'fit',
    border : false,
    cls : 'tw-editdialog',

    labelAlign : 'top',

    anchor : '100% 100%',
    deferredRender : false,
    buttonAlign : null,
    bufferResize : 500,

    /**
     * init component
     */
    initComponent: function() {

        if (!this.app) {
            this.app = Tine.Tinebase.appMgr.get(this.appName);
        }

        Tine.log.debug('initComponent: appName: ', this.appName);
        Tine.log.debug('initComponent: app: ', this.app);

        // init actions
        this.initActions();
        // init buttons and tbar
        this.initButtons();

        // get items for this dialog
        this.items = this.getFormItems();
        
        Tine.Sales.AdminPanel.superclass.initComponent.call(this);
    },

    /**
     * init actions
     */
    initActions: function() {
        this.action_cancel = new Ext.Action({
            text : this.app.i18n._('Cancel'),
            minWidth : 70,
            scope : this,
            handler : this.onCancel,
            iconCls : 'action_cancel'
        });

        this.action_update = new Ext.Action({
            text : this.app.i18n._('OK'),
            minWidth : 70,
            scope : this,
            handler : this.onUpdate,
            iconCls : 'action_saveAndClose'
        });
    },

    /**
     * init buttons
     */
    initButtons : function() {
        this.fbar = [ '->', this.action_cancel, this.action_update ];
    },  

    /**
     * is called when the component is rendered
     * @param {} ct
     * @param {} position
     */
    onRender : function(ct, position) {
        this.loadMask = new Ext.LoadMask(ct, {msg: i18n._('Loading...')});
        Tine.Sales.AdminPanel.superclass.onRender.call(this, ct, position);

        // generalized keybord map for edit dlgs
        var map = new Ext.KeyMap(this.el, [ {
            key : [ 10, 13 ], // ctrl + return
            ctrl : true,
            fn : this.onUpdate,
            scope : this
        } ]);

    },
    
    /**
     * closes the window
     */
    onCancel: function() {
        this.fireEvent('cancel');
        this.purgeListeners();
        this.window.close();
    },

    /**
     * save record and close window
     */
    onUpdate : function() {
        Ext.Ajax.request({
            url: 'index.php',
            scope: this,
            params: {
                method: 'Sales.setConfig',
                config: this.getForm().getFieldValues(),
            },
            success : function(_result, _request) {
                this.loadMask.hide();
                // reload mainscreen to make sure registry gets updated
                window.location = window.location.href.replace(/#+.*/, '');
            },
            failure: function(result) {
                Tine.Tinebase.ExceptionHandler.handleRequestException(result)
            }
        });
    },

    /**
     * create and return form items
     * @return Object
     */
    getFormItems: function() {
        var config = Tine.Sales.registry.get('config');

        var currency = [
            ['EUR', this.app.i18n._('Euro (€)')]
        ];

        var currencyStore = new Ext.data.ArrayStore({
            fields: [
                'currency_id',
                'currency'
            ],
            data: currency,
            disabled: false
        });


        return {
            border: false,
            frame : false,
            layout: 'border',

            items: [{
                region: 'center',
                border: false,
                frame : false,
                
                xtype       : 'columnform',
                labelAlign  : 'top',
                formDefaults: {
                    xtype         :'textfield',
                    anchor        : '100%',
                    labelSeparator: '',
                    columnWidth   : 1/3
                },
                items: [
                    [
                        {
                            fieldLabel: this.app.i18n._(config.ownCurrency.definition.label),
                            name      : 'ownCurrency',
                            value     : config.ownCurrency.value ? config.ownCurrency.value : config.ownCurrency['default'],
                            xtype     : 'combo',
                            mode      : 'local',
                            scope     : this,
                            valueField: 'currency_id',
                            displayField: 'currency',
                            store     : currencyStore
                        }
                    ], [
                        {
                            fieldLabel: this.app.i18n._(config.contractNumberGeneration.definition.label),
                            name      : 'contractNumberGeneration',
                            value     : config.contractNumberGeneration.value ? config.contractNumberGeneration.value : config.contractNumberGeneration['default'],
                            xtype     : 'combo',
                            mode      : 'local',
                            forceSelection: true,
                            triggerAction: 'all',
                            store     : config.contractNumberGeneration.definition.options
                        }, {
                            fieldLabel: this.app.i18n._(config.contractNumberValidation.definition.label),
                            name      : 'contractNumberValidation',
                            value     : config.contractNumberValidation.value ? config.contractNumberValidation.value : config.contractNumberValidation['default'],
                            xtype     : 'combo',
                            mode      : 'local',
                            forceSelection: true,
                            triggerAction: 'all',
                            store     : config.contractNumberValidation.definition.options
                        }
                    ], [
                        {
                            fieldLabel: this.app.i18n._(config.productNumberGeneration.definition.label),
                            name      : 'productNumberGeneration',
                            value     : config.productNumberGeneration.value ? config.productNumberGeneration.value : config.productNumberGeneration['default'],
                            xtype     : 'combo',
                            mode      : 'local',
                            forceSelection: true,
                            triggerAction: 'all',
                            store     : config.productNumberGeneration.definition.options
                        }, {
                            fieldLabel: this.app.i18n._(config.productNumberValidation.definition.label),
                            name      : 'productNumberValidation',
                            value     : config.productNumberValidation.value ? config.productNumberValidation.value : config.productNumberValidation['default'],
                            xtype     : 'combo',
                            mode      : 'local',
                            forceSelection: true,
                            triggerAction: 'all',
                            store     : config.productNumberValidation.definition.options
                        }
                    ], [
                        {
                            fieldLabel: this.app.i18n._(config.productNumberPrefix.definition.label),
                            name      : 'productNumberPrefix',
                            value     : config.productNumberPrefix.value ? config.productNumberPrefix.value : config.productNumberPrefix['default'],
                        }, {
                            fieldLabel: this.app.i18n._(config.productNumberZeroFill.definition.label),
                            name      : 'productNumberZeroFill',
                            value     : config.productNumberZeroFill.value ? config.productNumberZeroFill.value : config.productNumberZeroFill['default'],
                            xtype     : 'uxspinner',
                            decimalPrecision: 0,
                            strategy  : new Ext.ux.form.Spinner.NumberStrategy({
                                incrementValue: 1,
                                alternateIncrementValue: 10,
                                minValue      : 0,
                                maxValue      : 100,
                                allowDecimals : 0
                            })
                        }
                    ]
                ]
            }]
        };
    } 
});

Tine.Sales.AdminPanel.openWindow = function(config) {
    var window = Tine.WindowFactory.getWindow({
        modal  : true,
        width  : 500,
        height : 250,
        contentPanelConstructor : 'Tine.Sales.AdminPanel',
        contentPanelConstructorConfig : config
    });
    return window;
};