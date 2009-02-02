/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:$
 *
 */
 
Ext.namespace('Tine.Voipmanager');

/**
 * Snom Software Edit Dialog
 */
Tine.Voipmanager.SnomSoftwareEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    
    /**
     * @private
     */
    windowNamePrefix: 'SnomSoftwareEditWindow_',
    appName: 'Voipmanager',
    recordClass: Tine.Voipmanager.Model.SnomSoftware,
    recordProxy: Tine.Voipmanager.SnomSoftwareBackend,
    loadRecord: false,
    tbarItems: [{xtype: 'widget-activitiesaddbutton'}],
    evalGrants: false,
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     */
    updateToolbars: function(record) {
    	Tine.Voipmanager.SnomSoftwareEditDialog.superclass.updateToolbars.call(this, record, 'id');
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     */
    getSoftwareVersion: function () {
      	
      	var softwareVersion = new Array();
         _phoneModels = Tine.Voipmanager.Data.loadPhoneModelData();
      
            _phoneModels.each(function(_rec) {
                softwareVersion.push(new Ext.form.TextField({
                    fieldLabel: _rec.data.model,
                    name: 'softwareimage_' + _rec.data.id,
                    id: 'softwareimage_' + _rec.data.id,
                    anchor:'100%',
                    maxLength: 128,                    
                    hideLabel: false
                }));      
            });
     
     	return softwareVersion;
     },
     
     
    getFormItems: function() { 
        return {
            layout:'form',
            //frame: true,
            border:false,
            width: 440,
            autoHeight: true,
            items:[{
                    xtype:'textfield',
                    name: 'name',
                    fieldLabel: this.app.i18n._('name'),
                    anchor:'100%'
                }, {
                    //labelSeparator: '',
                    xtype:'textarea',
                    name: 'description',
                    fieldLabel: this.app.i18n._('Description'),
                    grow: false,
                    preventScrollbars:false,
                    anchor:'100%',
                    height: 60
                }, {
                    layout: 'form',
                    border: false,
                    anchor: '100%',
                    items: this.getSoftwareVersion()
                }]
        };
    }
});

/**
 * Snom Software Edit Popup
 */
Tine.Voipmanager.SnomSoftwareEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 500,
        height: 300,
        name: Tine.Voipmanager.SnomSoftwareEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Voipmanager.SnomSoftwareEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
