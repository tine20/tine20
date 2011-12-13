/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Addressbook');

/**
 * render the CardDAV Url into property panel of contianers
 * 
 * @class   Tine.Addressbook.CardDAVContainerPropertiesHookField
 * @extends Ext.form.TextField
 */
Tine.Addressbook.CardDAVContainerPropertiesHookField = Ext.extend(Ext.form.TextField, {

    anchor: '100%',
    readOnly: true,
    
    /**
     * @private
     */
    initComponent: function() {
        this.on('added', this.onContainerAdded, this);

        Tine.Addressbook.CardDAVContainerPropertiesHookField.superclass.initComponent.call(this);
    },
    
    /**
     * @private
     */
    onContainerAdded: function() {
        this.app = Tine.Tinebase.appMgr.get('Addressbook');
        this.fieldLabel = this.app.i18n._('CardDAV URL');
        
        this.propertiesDialog = this.findParentBy(function(p) {return !! p.grantContainer});
        this.grantContainer = this.propertiesDialog.grantContainer;
        
        if (this.grantContainer.application_id && this.grantContainer.application_id.name) {
            this.isAddressbook = (this.grantContainer.application_id.name == 'Addressbook');
        } else {
            this.isAddressbook = this.grantContainer.application_id === this.app.id;
        }
        
        this.hidden = ! this.isAddressbook;
        // cardDAV URL
        this.value = [
            window.location.href.replace(/\/?(index\.php.*)?$/, ''),
            '/addressbooks/',
            Tine.Tinebase.registry.get('currentAccount').contact_id,
            '/',
            this.grantContainer.id
        ].join('');
    }
    
});

Ext.ux.ItemRegistry.registerItem('Tine.widgets.container.PropertiesDialog.FormItems.Properties', Tine.Addressbook.CardDAVContainerPropertiesHookField, 100);