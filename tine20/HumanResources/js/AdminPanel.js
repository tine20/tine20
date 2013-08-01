/*
 * Tine 2.0
 * 
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * TODO make config saving work and activate update action again
 */

Ext.ns('Tine.HumanResources');

/**
 * @namespace   Tine.HumanResources
 * @class       Tine.HumanResources.AdminPanel
 * @extends     Ext.FormPanel
 * @author      Alexander Stintzing <alex@stintzing.net>
 */
Tine.HumanResources.AdminPanel = Ext.extend(Ext.FormPanel, {
    appName : 'HumanResources',

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
        
        this.loadRecord();
        
        Tine.HumanResources.AdminPanel.superclass.initComponent.call(this);
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
        this.fbar = [ '->', this.action_cancel , this.action_update ];
    },  

    /**
     * is called when the component is rendered
     * @param {} ct
     * @param {} position
     */
    onRender : function(ct, position) {
        Tine.HumanResources.AdminPanel.superclass.onRender.call(this, ct, position);

        // generalized keybord map for edit dlgs
        new Ext.KeyMap(this.el, [ {
            key : [ 10, 13 ], // ctrl + return
            ctrl : true,
            fn : this.onUpdate,
            scope : this
        } ]);

        this.loadMaskCt = ct;
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
        
        // check date
        var date = this.getForm().findField('vacationExpires').getValue();
        dates = date.split(/-/);
        var date = new Date(2013, parseInt(dates[0])-1, dates[1]);
        
        if (!(parseInt(date.format('m')) == parseInt(dates[0]) && parseInt(date.format('d')) == parseInt(dates[1]))) {
            this.getForm().findField('vacationExpires').markInvalid(this.app.i18n._('Please use the following format: MM-DD'));
            return;
        }
        
        if (! this.loadMask) {
            this.loadMask = new Ext.LoadMask(this.loadMaskCt);
        }
        
        this.loadMask.msg = 'Saving...';
        this.loadMask.show();

        Ext.Ajax.request({
            url: 'index.php',
            scope: this,
            params: {
                method: 'HumanResources.setConfig',
                config: {
                    defaultFeastCalendar: this.getForm().findField('defaultFeastCalendar').getValue(),
                    vacationExpires: this.getForm().findField('vacationExpires').getValue()
                }
            },
            success : function(_result, _request) {
                this.loadMask.hide();
                // reload mainscreen to make sure registry gets updated
                window.location = window.location.href.replace(/#+.*/, '');
            }
        });
    },

    loadRecord: function() {
        
        if (! this.rendered) {
            this.loadRecord.defer(100, this);
            return false;
        }
        
        this.loadMask = new Ext.LoadMask(this.loadMaskCt);
        this.loadMask.show();
        
        var config = Tine.HumanResources.registry.get('config');
        
        var calId = config.defaultFeastCalendar.value;
        if (calId) {
            var request = Ext.Ajax.request({
                url: 'index.php',
                scope: this,
                params: {
                    method: 'Admin.getContainer',
                    id: calId
                },
                success : function(_result, _request) {
                    this.onRecordLoad(Ext.decode(_result.responseText), config.vacationExpires.value);
                }
            });
        } else {
            this.onRecordLoad(null, config.vacationExpires.value);
        }
    },
    
    onRecordLoad: function(defaultFeastCalendar, vacationExpires) {
        this.getForm().findField('defaultFeastCalendar').setValue(defaultFeastCalendar);
        this.getForm().findField('vacationExpires').setValue(vacationExpires);
        
        this.loadMask.hide();
    },
    
    /**
     * create and return form items
     * @return Object
     */
    getFormItems: function() {
        var dfc = Tine.HumanResources.registry.get('config').defaultFeastCalendar;
        
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
                        Tine.widgets.form.RecordPickerManager.get('Tinebase', 'Container', {
                            containerName: this.app.i18n._('Calendar'),
                            containersName: this.app.i18n._('Calendars'),
                            appName: 'Calendar',
                            requiredGrant: 'readGrant',
                            hideTrigger2: true,
                            allowBlank: false,
                            fieldLabel: this.app.i18n._(dfc.definition.label),
                            name: 'defaultFeastCalendar',
                            blurOnSelect: true
                        }),
                        new Ext.form.TextField({
                            fieldLabel: this.app.i18n._('Vacation expires'),
                            name: 'vacationExpires'
                        })
                    ] 
                }]
            }]
        };
    } 
});

Tine.HumanResources.AdminPanel.openWindow = function(config) {
    var window = Tine.WindowFactory.getWindow({
        modal: true,
        width : 240,
        height : 250,
        contentPanelConstructor : 'Tine.HumanResources.AdminPanel',
        contentPanelConstructorConfig : config
    });
    return window;
};