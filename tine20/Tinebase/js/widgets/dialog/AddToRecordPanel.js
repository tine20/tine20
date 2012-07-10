/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.widgets.dialog');

/**
 * @namespace   Tine.widgets.dialog
 * @class       Tine.widgets.dialog.AddToRecordPanel
 * @extends     Ext.FormPanel
 * @author      Alexander Stintzing <alex@stintzing.net>
 */

Tine.widgets.dialog.AddToRecordPanel = Ext.extend(Ext.FormPanel, {

    /**
     * the name of the application the record to be added belongs to
     * @cfg {String}
     */
    appName : null,
    
    /**
     * the record class of the record the selected records should be added to
     * @cfg {Tine.Tinebase.data.Record}
     */
    recordClass: null,
    
    // private
    app: null,
    layout : 'fit',
    border : false,
    cls : 'tw-editdialog',
    labelAlign : 'top',
    anchor : '100% 100%',
    deferredRender : false,
    buttonAlign : null,
    bufferResize : 500,
    
    /**
     * initializes the component
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

        Tine.widgets.dialog.AddToRecordPanel.superclass.initComponent.call(this);
    },
    
    /**
     * initializes the actions
     */
    initActions: function() {
        this.action_cancel = new Ext.Action({
            text : _('Cancel'),
            minWidth : 70,
            scope : this,
            handler : this.onCancel,
            iconCls : 'action_cancel'
        });
        
        this.action_update = new Ext.Action({
            text : _('Ok'),
            minWidth : 70,
            scope : this,
            handler : this.onUpdate,
            iconCls : 'action_saveAndClose'
        });
    },

    /**
     * create the buttons
     */
    initButtons : function() {
        this.fbar = [ '->', this.action_cancel, this.action_update ];
    },
    
    /**
     * is called on render, creates keymap
     * @param {} ct
     * @param {} position
     */
    onRender : function(ct, position) {
        Tine.widgets.dialog.AddToRecordPanel.superclass.onRender.call(this, ct, position);

        // generalized keybord map for edit dlgs
        new Ext.KeyMap(this.el, [ {
            key : [ 10, 13 ], // ctrl + return
            ctrl : true,
            fn : this.onUpdate,
            scope : this
        } ]);

    },
    
    /**
     * is called on cancel
     */
    onCancel: function() {
        this.fireEvent('cancel');
        this.purgeListeners();
        this.window.close();
    },
    
    /**
     * returns true if the form is valid, must be overridden
     * @return {Boolean}
     */
    isValid: Ext.emptyFn,
    
    /**
     * returns the items of the form, must be overridden
     * @return {Object} with the configured items
     */
    getFormItems: Ext.emptyFn,
    
    /**
     * returns the records, which should be added. must be overridden when editDialog runs in remote mode
     * @return {Object} config for the record to edit
     */
    getAddToRecords: function() {
        return null;
    },
    
    /**
     * returns the configuration for the record. must be overridden when editDialog runs in local mode
     * @return {Object} config for the record to edit
     */
    getRecord: function() {
        return null;
    },
    
    /**
     * is called when ok-button is pressed and edit dialog should be opened
     */
    onUpdate: function() {
        if(this.isValid()) {
            if(this.app) {
                var ms = this.app.getMainScreen();
                if(ms) {
                    var cp = ms.getCenterPanel();
                }
            }
            cp.onEditInNewWindow({actionType: 'edit'}, this.getRecord() ? this.getRecord() : this.searchBox.selectedRecord, this.getAddToRecords());
            this.onCancel();
        }
    }
});