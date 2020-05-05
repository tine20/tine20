/*
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Calendar');

/**
 * @namespace Tine.Calendar
 * @class     Tine.Calendar.ResourceGridPanel
 * @extends   Tine.widgets.grid.GridPanel
 * Resources Grid Panel <br>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Calendar.ResourceGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    // model generics
    recordClass: Tine.Calendar.Model.Resource,
    
    // grid specific
    defaultSortInfo: {field: 'name', dir: 'ASC'},
    
    // not yet
    evalGrants: false,
    
    newRecordIcon: 'cal-resource',
    
    ownActionToolbar: true,

    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        
        this.gridConfig = {
            autoExpandColumn: 'name'
        };
        
        this.gridConfig.columns = [{
            id: 'name',
            header: this.app.i18n._("Name"),
            width: 150,
            sortable: true,
            dataIndex: 'name'
        }, {
            id: 'hierarchy',
            header: this.app.i18n._("Calendar Hierarchy/Name"),
            width: 150,
            sortable: true,
            dataIndex: 'hierarchy'
        },{
            id: 'email',
            header: this.app.i18n._("Email"),
            width: 150,
            sortable: true,
            dataIndex: 'email'
        }, {
            id: 'type',
            header: this.app.i18n._("Type"),
            width: 150,
            sortable: true,
            dataIndex: 'type',
            renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('Calendar', 'resourceTypes')
        }, {
            id: 'max_number_of_people',
            header: this.app.i18n._("Maximum number of attendee"),
            width: 150,
            sortable: true,
            dataIndex: 'max_number_of_people'
        }, {
            id: 'status',
            dataIndex: 'status',
            width: 140,
            header: this.app.i18n._('Default Status'),
            renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('Calendar', 'attendeeStatus')
        }, {
            id: 'status_with_grant',
            dataIndex: 'status_with_grant',
            width: 140,
            header: this.app.i18n._('Default Status with status grant'),
            renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('Calendar', 'attendeeStatus')
        }, {
            id: 'busy_type',
            dataIndex: 'busy_type',
            width: 140,
            header: this.app.i18n._('Busy Type'),
            renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('Calendar', 'freebusyTypes')
        }, {
            id: 'site',
            header: this.app.i18n._('Site'),
            width: 150,
            dataIndex: 'relations',
            renderer: Tine.Calendar.ResourceGridPanel.siteRenderer,
            sortable: false
        }, {
            id: 'location',
            header: this.app.i18n._('Location'),
            width: 150,
            dataIndex: 'relations',
            renderer: Tine.Calendar.ResourceGridPanel.locationRenderer,
            sortable: false
        }, {
            id: 'color',
            header: this.app.i18n._('Color'),
            width: 30,
            sortable: false,
            renderer: Tine.Tinebase.common.colorRenderer,
            dataIndex: 'color'
        }];

        Tine.Calendar.ResourceGridPanel.superclass.initComponent.call(this);
    },

    /**
     * preform the initial load of grid data
     */
    initialLoad: function() {
        this.store.load.defer(10, this.store, [
            typeof this.autoLoad == 'object' ?
                this.autoLoad : undefined]);
    }
});

Ext.reg('calendar.resourcegridpanel', Tine.Calendar.ResourceGridPanel);

/**
 * render site relation
 *
 * @param data
 * @param cell
 * @param record
 * @returns {*|String}
 */
Tine.Calendar.ResourceGridPanel.siteRenderer = function(data, cell, record) {
    var _ = window.lodash;

    if (Ext.isArray(data) && data.length > 0) {
        var index = 0;

        while (index < data.length && data[index].type != 'SITE') {
            index++;
        }
        if (data[index]) {
            return Ext.util.Format.htmlEncode(_.get(data[index], 'related_record.n_fileas', i18n._('No Access')));
        }
    }
};

/**
 * render location relation
 *
 * @param data
 * @param cell
 * @param record
 * @returns {*|String}
 */
Tine.Calendar.ResourceGridPanel.locationRenderer = function(data, cell, record) {
    var _ = window.lodash;

    if (Ext.isArray(data) && data.length > 0) {
        var index = 0;

        while (index < data.length && data[index].type != 'LOCATION') {
            index++;
        }
        if (data[index]) {
            return Ext.util.Format.htmlEncode(_.get(data[index], 'related_record.n_fileas', i18n._('No Access')));
        }
    }
};
