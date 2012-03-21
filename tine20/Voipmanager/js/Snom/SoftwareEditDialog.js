/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Voipmanager');

/**
 * Snom Software Edit Dialog
 * @todo in the model the snom models are hard coded, but in the dialog not... check it
 */
Tine.Voipmanager.SnomSoftwareEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'SnomSoftwareEditWindow_',
    appName: 'Voipmanager',
    recordClass: Tine.Voipmanager.Model.SnomSoftware,
    recordProxy: Tine.Voipmanager.SnomSoftwareBackend,
    evalGrants: false,
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     */
    getSoftwareVersion: function () {
          
          var softwareVersion = [];
        var phoneModels = Tine.Voipmanager.Data.loadPhoneModelData();
      
            phoneModels.each(function(rec) {
                softwareVersion.push(new Ext.form.TextField({
                    fieldLabel: rec.data.model,
                    name: 'softwareimage_' + rec.data.id,
                    id: 'softwareimage_' + rec.data.id,
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
            border:false,
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
