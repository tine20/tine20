/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Calendar');

/**
 * render event container_id
 */
Tine.Calendar.calendarRenderer = function(value, metaData, record, rowIndex, colIndex, store) {
    var app = Tine.Tinebase.appMgr.get('Calendar');
    
    if(record) {   // no record after delete
        var originContainer = record.get('container_id'),
            displayContainer = record.getDisplayContainer(),
            containerHtml = '',
            tip = '';
        
        // show origin (if available)
        if (! Ext.isPrimitive(originContainer)) {
            containerHtml = Tine.Tinebase.common.containerRenderer(originContainer);
        } else {
            containerHtml = Tine.Tinebase.common.containerRenderer(displayContainer);
        }
        
        tip += Ext.isPrimitive(originContainer) ? 
                Ext.util.Format.htmlEncode(app.i18n._("This event is originally stored in a calendar you don't have access to.")) :
                String.format(Ext.util.Format.htmlEncode(app.i18n._("This event is originally stored in {0}")), Ext.util.Format.htmlEncode(Tine.Tinebase.common.containerRenderer(originContainer)));
        tip += displayContainer && ! Tine.Tinebase.container.pathIsMyPersonalContainer(originContainer.path) ? 
                String.format(Ext.util.Format.htmlEncode(app.i18n._("This event is additionally displayed in your personal calendar {0}")), Ext.util.Format.htmlEncode(Tine.Tinebase.common.containerRenderer(displayContainer))) :
                    '';
        return containerHtml.replace('<div ', '<div ext:qtip="' + tip + '" ');
        
    } else {
        return Tine.Tinebase.common.containerRenderer(value);
    }
};

Tine.widgets.grid.RendererManager.register('Calendar', 'Event', 'container_id', Tine.Calendar.calendarRenderer);