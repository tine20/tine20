/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2011-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets', 'Tine.widgets.container');

/**
 * render the CalDAV Url into property panel of containers
 * 
 * @class   Tine.widgets.container.CalDAVContainerPropertiesHookField
 * @extends Ext.form.TextField
 */
Tine.widgets.container.CalDAVContainerPropertiesHookField = Ext.extend(Ext.form.TextField, {

    anchor: '100%',
    readOnly: true,
    
    /**
     * @private
     */
    initComponent: function() {
        this.on('added', this.onContainerAdded, this);

        Tine.widgets.container.CalDAVContainerPropertiesHookField.superclass.initComponent.call(this);
    },
    
    /**
     * @private
     */
    onContainerAdded: function() {
        this.app = Tine.Tinebase.appMgr.get(this.appName);
        this.fieldLabel = _('CalDAV URL');
        
        this.propertiesDialog = this.findParentBy(function(p) {return !! p.grantContainer});
        this.grantContainer = this.propertiesDialog.grantContainer;
        
        if (this.grantContainer.application_id && this.grantContainer.application_id.name) {
            this.hasCalDAVSupport = (this.grantContainer.application_id.name == this.appName);
        } else {
            this.hasCalDAVSupport = this.grantContainer.application_id === this.app.id;
        }
        
        this.hidden = ! this.hasCalDAVSupport;
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
