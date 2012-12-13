/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Date.msSECOND = 1000;
Date.msMINUTE = 60 * Date.msSECOND;
Date.msHOUR   = 60 * Date.msMINUTE;
Date.msDAY    = 24 * Date.msHOUR;
Date.msWEEK   =  7 * Date.msDAY;

Ext.ns('Tine.Calendar');

/**
 * @class Tine.Calendar.CalendarPanel
 * @namespace Tine.Calendar
 * @extends Ext.Panel
 * @author Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Calendar.CalendarPanel = Ext.extend(Ext.Panel, {
    /**
     * @cfg {Tine.Calendar.someView} view
     */
    view: null,
    
    border: false,
    layout: 'fit',
    autoScroll: false,
    autoWidth: false,
    
    /**
     * @private
     */
    initComponent: function() {
        this.items = this.view;
        
        // init plugins
        this.plugins = Ext.isString(this.plugins) ? Ext.decode(this.plugins) : Ext.isArray(this.plugins) ? this.plugins.concat(Ext.decode(this.initialConfig.plugins)) : [];
        
        Tine.Calendar.CalendarPanel.superclass.initComponent.call(this);
        
        this.relayEvents(this.view, ['changeView', 'changePeriod', 'click', 'dblclick', 'contextmenu', 'keydown']);
    },
    
    /**
     * Returns selection model
     * 
     * @deprecated
     * @return {Tine.Calendar.EventSelectionModel}
     */
    getSelectionModel: function() {
        return this.getView().getSelectionModel();
    },
    
    /**
     * Returns data store
     * 
     * @deprecated
     * @return {Ext.data.Store}
     */
    getStore: function() {
        return this.getView().store;
    },
    
    /**
     * Retruns calendar View
     * 
     * @return {Tine.Calendar.View}
     */
    getView: function() {
        return this.view;
    }
});
