/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2011-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Tasks');

/**
 * render the CalDAV Url into property panel of containers
 * 
 * @class   Tine.Calendar.CalDAVContainerPropertiesHookField
 * @extends Tine.widgets.container.CalDAVContainerPropertiesHookField
 */
Tine.Tasks.CalDAVContainerPropertiesHookField = Ext.extend(Tine.widgets.container.CalDAVContainerPropertiesHookField, {
    appName: 'Tasks'
});

Ext.ux.ItemRegistry.registerItem('Tine.widgets.container.PropertiesDialog.FormItems.Properties', Tine.Tasks.CalDAVContainerPropertiesHookField, 100);