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
    
    getSubFilters: function() {
        
        this.subFilterModels = Tine.Projects.ProjectAttendeeFilter.superclass.getSubFilters.call(this);
        
        return this.subFilterModels;
    }
});
Tine.widgets.grid.FilterToolbar.FILTERS['tine.projects.attendee'] = Tine.Projects.ProjectAttendeeFilter;
