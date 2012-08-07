/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets', 'Tine.widgets.container');

/**
 * Container Grants dialog
 * 
 * @namespace   Tine.widgets.container
 * @class       Tine.widgets.container.GrantsDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @constructor
 * @param {Object} config The configuration options.
 */
Tine.widgets.container.GrantsDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @cfg {Tine.Tinebase.container.models.container}
     * Container to manage grants for
     */
    grantContainer: null,
    
    /**
     * @cfg {string}
     * Name of container folders, e.g. Addressbook
     */
    containerName: null,
    
    /**
     * @private {Ext.data.JsonStore}
     */
    grantsStore: null,
    
    /**
     * @private
     */
    windowNamePrefix: 'ContainerGrantsWindow_',
    loadRecord: false,
    tbarItems: [],
    evalGrants: false,
    
    /**
     * @private
     */
    initComponent: function() {
        this.containerName = this.containerName ? this.containerName : _('Folder');

        this.grantsStore =  new Ext.data.JsonStore({
            baseParams: {
                method: 'Tinebase_Container.getContainerGrants',
                containerId: this.grantContainer.id
            },
            root: 'results',
            totalProperty: 'totalcount',
            //id: 'id',
            // use account_id here because that simplifies the adding of new records with the search comboboxes
            id: 'account_id',
            fields: Tine.Tinebase.Model.Grant
        });
        this.grantsStore.load();
        
        Tine.widgets.container.GrantsDialog.superclass.initComponent.call(this);
    },
    
    /**
     * init record to edit
     * 
     * - overwritten: we don't have a record here 
     */
    initRecord: function() {
    },
    
    /**
     * returns dialog
     */
    getFormItems: function() {
        this.grantsGrid = new Tine.widgets.container.GrantsGrid({
            store: this.grantsStore,
            grantContainer: this.grantContainer
        });
        
        return this.grantsGrid;
    },
    
    /**
     * @private
     */
    onApplyChanges: function(closeWindow) {
        Ext.MessageBox.wait(_('Please wait'), _('Updating Grants'));
        
        var grants = [];
        this.grantsStore.each(function(_record){
            grants.push(_record.data);
        });
        
              
        Ext.Ajax.request({
            params: {
                method: 'Tinebase_Container.setContainerGrants',
                containerId: this.grantContainer.id,
                grants: grants
            },
            scope: this,
            success: function(_result, _request){
                Ext.MessageBox.hide();
                if (closeWindow) {
                    this.purgeListeners();
                    this.window.close();
                    return;
                }
                var grants = Ext.util.JSON.decode(_result.responseText);
                this.grantsStore.loadData(grants, false);
            },
            failure: function(response, options) {
                var responseText = Ext.util.JSON.decode(response.responseText);
                
                if (responseText.data.code == 505) {
                    Ext.Msg.show({
                       title:   _('Error'),
                       msg:     _('You are not allowed to remove all admins for this container!'),
                       icon:    Ext.MessageBox.ERROR,
                       buttons: Ext.Msg.OK
                    });
                    
                } else {
                    // call default exception handler
                    var exception = responseText.data ? responseText.data : responseText;
                    Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
                }                
            }
        });
    }
});

/**
 * grants dialog popup / window
 */
Tine.widgets.container.GrantsDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 700,
        height: 450,
        name: Tine.widgets.container.GrantsDialog.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.widgets.container.GrantsDialog',
        contentPanelConstructorConfig: config,
        modal: true
    });
    return window;
};
