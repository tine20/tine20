/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Projects');

/**
 * Foreign Record Filter
 * 
 * @namespace   Tine.Projects
 * @class       Tine.Projects.ProjectAttendeeFilter
 * @extends     Tine.widgets.grid.ForeignRecordFilter
 * 
 * <p>Filter for project attendee</p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 */
Tine.Projects.ProjectAttendeeFilter = Ext.extend(Tine.widgets.grid.ForeignRecordFilter, {

    /**
     * @cfg {Record} foreignRecordClass (required)
     */
    foreignRecordClass: Tine.Addressbook.Model.Contact,
    
    /**
     * @cfg {String} ownField (required)
     */
    ownField: 'contact',
    
    initComponent: function() {
        this.label = this.app.i18n._('Attendee');
        
        Tine.Projects.ProjectAttendeeFilter.superclass.initComponent.call(this);
    },
    
    /**
     * get subfilter models
     * @return Array of filter models
     */
    getSubFilters: function() {
        var attendeeRoleFilter = new Tine.Tinebase.widgets.keyfield.Filter({
            label: this.app.i18n._('Attendee Role'),
            field: 'relation_type',
            app: this.app, 
            keyfieldName: 'projectAttendeeRole'
        });
        
        return [attendeeRoleFilter];
    }
});

Tine.widgets.grid.FilterToolbar.FILTERS['tine.projects.attendee'] = Tine.Projects.ProjectAttendeeFilter;
