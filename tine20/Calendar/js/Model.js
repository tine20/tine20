/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

Ext.ns('Tine.Calendar');

// Event model
Tine.Calendar.EventArray = Tine.Tinebase.Model.genericFields.concat([
    { name: 'id' },
    { name: 'dtend', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'transp' },
    // ical common fields
    { name: 'class_id' },
    { name: 'description' },
    { name: 'geo' },
    { name: 'location' },
    { name: 'organizer' },
    { name: 'priority' },
    { name: 'status_id' },
    { name: 'summary' },
    { name: 'url' },
    { name: 'uid' },
    // ical common fields with multiple appearance
    //{ name: 'attach' },
    { name: 'attendee' },
    { name: 'tags' },
    { name: 'notes'},
    //{ name: 'contact' },
    //{ name: 'related' },
    //{ name: 'resources' },
    //{ name: 'rstatus' },
    // scheduleable interface fields
    { name: 'dtstart', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'recurid' },
    // scheduleable interface fields with multiple appearance
    { name: 'exdate' },
    //{ name: 'exrule' },
    //{ name: 'rdate' },
    { name: 'rrule' },
    { name: 'is_all_day_event' },
    { name: 'rrule_until', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'originator_tz' }
]);

/**
 * Event record definition
 */
Tine.Calendar.Event = Tine.Tinebase.data.Record.create(Tine.Calendar.EventArray, {
    appName: 'Calendar',
    modelName: 'Event',
    idProperty: 'id',
    titleProperty: 'summary',
    // ngettext('Event', 'Events', n); gettext('Event');
    recordName: 'Event',
    recordsName: 'Events',
    containerProperty: 'container_id',
    // ngettext('calendar', n); gettext('calendars');
    containerName: 'calendar',
    containersName: 'calendars'
});

/**
 * default tasks backend
 *
Tine.Calendar.JsonBackend = new Tine.Tinebase.widgets.app.JsonBackend({
    appName: 'Calendar',
    modelName: 'Event',
    recordClass: Tine.Calendar.Event
});*/

Tine.Calendar.backend = new Tine.Tinebase.data.AbstractBackend({
    appName: 'Calendar',
    modelName: 'Event',
    recordClass: Tine.Calendar.Event
});