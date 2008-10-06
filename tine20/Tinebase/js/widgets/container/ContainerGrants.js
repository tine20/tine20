/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Tine.widgets', 'Tine.widgets.container');

Tine.widgets.container.grantDialog = Ext.extend(Tine.widgets.dialog.EditRecord, {
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
	handlerApplyChanges: function(button, event, closeWindow) {
		Ext.MessageBox.wait(_('Please wait'), _('Updating Grants'));
		
		var grants = [];
		this.grantsStore.each(function(_record){
			grants.push(_record.data);
		});
		
		Ext.Ajax.request({
			params: {
				method: 'Tinebase_Container.setContainerGrants',
				containerId: this.grantContainer.id,
				grants: Ext.util.JSON.encode(grants)
			},
            scope: this,
			success: function(_result, _request){
				var grants = Ext.util.JSON.decode(_result.responseText);
				this.grantsStore.loadData(grants, false);
				
				Ext.MessageBox.hide();
                if (closeWindow) {
                    this.handlerCancle();
                }
			}
		});
	},
    handlerCancle: function() {
        window.close();
    },
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
            id: 'id',
            fields: Tine.Tinebase.Model.Grant
        });
        this.grantsStore.load();
        
        var columns = [
            new Ext.ux.grid.CheckColumn({
                header: _('Read'),
                dataIndex: 'readGrant',
                width: 55
            }),
            new Ext.ux.grid.CheckColumn({
                header: _('Add'),
                dataIndex: 'addGrant',
                width: 55
            }),
            new Ext.ux.grid.CheckColumn({
                header: _('Edit'),
                dataIndex: 'editGrant',
                width: 55
            }),
            new Ext.ux.grid.CheckColumn({
                header: _('Delete'),
                dataIndex: 'deleteGrant',
                width: 55
            })
        ];
        
        if (this.grantContainer.type == 'shared') {
            columns.push(new Ext.ux.grid.CheckColumn({
                header: _('Admin'),
                dataIndex: 'adminGrant',
                width: 55
            }));
        }
        
        this.items = new Tine.widgets.account.ConfigGrid({
            accountPickerType: 'both',
            accountListTitle: _('Permissions'),
            configStore: this.grantsStore,
            hasAccountPrefix: true,
            configColumns: columns
        });
        
		Tine.widgets.container.grantDialog.superclass.initComponent.call(this);
    }
});