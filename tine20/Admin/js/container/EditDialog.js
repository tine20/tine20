/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
/*global Ext, Tine*/

Ext.ns('Tine.Admin.container');

/**
 * @namespace   Tine.Admin.container
 * @class       Tine.Admin.ContainerEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Container Edit Dialog</p>
 * <p>
 * </p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Admin.ContainerEditDialog
 */
Tine.Admin.ContainerEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'containerEditWindow_',
    appName: 'Admin',
    recordClass: Tine.Admin.Model.Container,
    recordProxy: Tine.Admin.containerBackend,
    evalGrants: false,
    
    /**
     * executed after record got updated from proxy
     */
    onRecordLoad: function () {
        Tine.Admin.ContainerEditDialog.superclass.onRecordLoad.apply(this, arguments);
        
        // load grants store if editing record
        if (this.record && this.record.id) {
			this.grantsStore.loadData({
	            results:    this.record.get('account_grants'),
	            totalcount: this.record.get('account_grants').length
	        });
        }
    },    
    
    /**
     * executed when record gets updated from form
     */
    onRecordUpdate: function () {
        Tine.Admin.ContainerEditDialog.superclass.onRecordUpdate.apply(this, arguments);
        
        // get grants from grants grid
        this.record.set('account_grants', '');
        var grants = [];
        this.grantsStore.each(function(grant){
            grants.push(grant.data);
        });
        this.record.set('account_grants', grants);
    },
    
    /**
     * create grants store + grid
     * 
     * @return {Tine.widgets.container.GrantsGrid}
     */
    initGrantsGrid: function () {
        this.grantsStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'account_id',
            fields: Tine.Tinebase.Model.Grant
        });
       
        this.grantsGrid = new Tine.widgets.container.GrantsGrid({
			flex: 1,
            store: this.grantsStore,
            grantContainer: this.record.data,
            alwaysShowAdminGrant: true
        });
        
        return this.grantsGrid;
    },
    
    /**
     * returns dialog
     */
    getFormItems: function () {
        return {
            layout: 'vbox',
            layoutConfig: {
			    align: 'stretch',
			    pack: 'start'
			},
            border: false,
            items: [{
                xtype: 'columnform',
                border: false,
                autoHeight: true,
                items: [[{
                    columnWidth: 0.3,
                    fieldLabel: this.app.i18n._('Name'), 
                    name: 'name',
                    allowBlank: false,
                    maxLength: 40
                }, {
                    columnWidth: 0.6,
                    name: 'description',
                    fieldLabel: this.app.i18n._('Description'),
                    anchor: '100%',
                    maxLength: 50
                }, {
                    xtype: 'colorfield',
                    columnWidth: 0.1,
                    fieldLabel: this.app.i18n._('Color'),
                    name: 'color'
                }]]
            }, 
            	this.initGrantsGrid()
           	]            
        };
    }
});

/**
 * Container Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Admin.ContainerEditDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 600,
        height: 400,
        name: Tine.Admin.ContainerEditDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Admin.ContainerEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
