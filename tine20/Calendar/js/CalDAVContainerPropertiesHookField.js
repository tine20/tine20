/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Calendar');

/**
 * render the CalDAV Url into property panel of contianers
 * 
 * @class   Tine.Calendar.CalDAVContainerPropertiesHookField
 * @extends Ext.form.TextField
 */
Tine.Calendar.CalDAVContainerPropertiesHookField = Ext.extend(Ext.form.TextField, {

    anchor: '100%',
    readOnly: true,
    
    /**
     * @private
     */
    initComponent: function() {
        this.on('added', this.onContainerAdded, this);

        Tine.Calendar.CalDAVContainerPropertiesHookField.superclass.initComponent.call(this);
    },
    
    /**
     * @private
     */
    onContainerAdded: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        this.fieldLabel = this.app.i18n._('CalDAV URL');
        
        this.propertiesDialog = this.findParentBy(function(p) {return !! p.grantContainer});
        this.grantContainer = this.propertiesDialog.grantContainer;
        
        if (this.grantContainer.application_id && this.grantContainer.application_id.name) {
            this.isCalendar = (this.grantContainer.application_id.name == 'Calendar');
        } else {
            this.isCalendar = this.grantContainer.application_id === this.app.id;
        }
        
        this.hidden = ! this.isCalendar;
        // calDAV URL
        this.value = [
            window.location.href.replace(/\/?(index\.php.*)?$/, ''),
            '/calendars/',
            Tine.Tinebase.registry.get('currentAccount').contact_id,
            '/',
            this.grantContainer.id
        ].join('');
    }
    
});

Ext.ux.ItemRegistry.registerItem('Tine.widgets.container.PropertiesDialog.FormItems.Properties', Tine.Calendar.CalDAVContainerPropertiesHookField, 100);