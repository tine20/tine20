/*
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
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
        
        this.loadConfig();
        
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
        this.loadMask = new Ext.LoadMask(ct, {msg: _('Loading...')});
        Tine.Sales.AdminPanel.superclass.onRender.call(this, ct, position);

        // generalized keybord map for edit dlgs
        var map = new Ext.KeyMap(this.el, [ {
            key : [ 10, 13 ], // ctrl + return
            ctrl : true,
            fn : this.onUpdate,
            scope : this
        } ]);

    },

    loadConfig: function() {
        Ext.Ajax.request({
            url : 'index.php',
            scope: this,
            params : {
                method : 'Sales.getConfig'
            },
            success : function(_result, _request) {
                try {
                    var result = Ext.decode(_result.responseText);   
                    if(result.hasOwnProperty('autogenerate_number')) {
                        this.getForm().findField('autogenerate_number').setValue(result.autogenerate_number);
                    }
                } catch (e) {
                    Tine.log.error('Tine.Sales.AdminPanel::loadRecord::successCallback');
                    Tine.log.error(e.stack ? e.stack : e);
                }
                this.loadMask.hide();
            }
        });
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
                config: this.getForm().findField('autogenerate_number').getValue()
            },
            success : function(_result, _request) {
                this.onCancel();
            }
        });
    },

    /**
     * create and return form items
     * @return Object
     */
    getFormItems: function() {
        return {
            border: false,
            frame:  false,
            layout: 'border',

            items: [{
                region: 'center',
                border: false,
                frame:  false,
                layout : {
                    align: 'stretch',
                    type:  'vbox'
                    },
                items: [{
                    layout:  'form',
                    margins: '10px 10px',
                    border:  false,
                    frame:   false,
                    items: [
                        {
                            fieldLabel: this.app.i18n._('Generate contract number'),
                            name: 'autogenerate_number',
                            xtype: 'combo',
                            mode: 'local',
                            forceSelection: true,
                            triggerAction: 'all',
                            store: [['auto', this.app.i18n._('auto')], ['manual', this.app.i18n._('manual')]]
                        }
                    ] 
                    }]

            }]
        };
    } 
});

Tine.Sales.AdminPanel.openWindow = function(config) {
    var window = Tine.WindowFactory.getWindow({
        modal: true,
        width : 240,
        height : 250,
        contentPanelConstructor : 'Tine.Sales.AdminPanel',
        contentPanelConstructorConfig : config
    });
    return window;
};