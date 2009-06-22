/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

Ext.ns('Tine.Calendar', 'Tine.Calendar.Model');

// Event model
Tine.Calendar.Model.EventArray = Tine.Tinebase.Model.genericFields.concat([
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
    { name: 'is_all_day_event', type: 'bool'},
    { name: 'rrule_until', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'originator_tz' },
    // grant helper fields
    {name: 'readGrant'   , type: 'bool'},
    {name: 'editGrant'   , type: 'bool'},
    {name: 'deleteGrant' , type: 'bool'},
    {name: 'editGrant'   , type: 'bool'}
    
]);

/**
 * Event record definition
 */
Tine.Calendar.Model.Event = Tine.Tinebase.data.Record.create(Tine.Calendar.Model.EventArray, {
    appName: 'Calendar',
    modelName: 'Event',
    idProperty: 'id',
    titleProperty: 'summary',
    // ngettext('Event', 'Events', n); gettext('Events');
    recordName: 'Event',
    recordsName: 'Events',
    containerProperty: 'container_id',
    // ngettext('Calendar', 'Calendars', n); gettext('Calendars');
    containerName: 'Calendar',
    containersName: 'Calendars'
});

/**
 * @todo:
 *  - set attendee according to calendar selection
 *  
 * @return {Object}
 */ 
Tine.Calendar.Model.Event.getDefaultData = function() {
    var app = Tine.Tinebase.appMgr.get('Calendar');
    
    var dtstart = new Date().clearTime().add(Date.HOUR, (new Date().getHours() + 1));
    
    // if dtstart is out of current period, take start of current period
    var mainPanel = app.getMainScreen().getContentPanel();
    var period = mainPanel.getCalendarPanel(mainPanel.activeView).getView().getPeriod();
    if (period.from.getTime() > dtstart.getTime() || period.until.getTime() < dtstart.getTime()) {
        dtstart = period.from.clearTime(true).add(Date.HOUR, 9);
    }
    
    var data = {
        summary: '',
        dtstart: dtstart,
        dtend: dtstart.add(Date.HOUR, 1),
        container_id: app.getMainScreen().getTreePanel().getAddCalendar(),
        transp: 'OPAQUE',
        editGrant: true,
        attendee: [
            Ext.apply(Tine.Calendar.Model.Attender.getDefaultData(), {
                user_type: 'user',
                user_id: Tine.Tinebase.registry.get('currentAccount'),
                status: 'ACCEPTED'
            })
        ]
    };
    
    return data;
};

/**
 * default tasks backend
 */
if (Tine.Tinebase.widgets) {
    Tine.Calendar.backend = new Tine.Tinebase.widgets.app.JsonBackend({
        appName: 'Calendar',
        modelName: 'Event',
        recordClass: Tine.Calendar.Model.Event
    });
} else {
    Tine.Calendar.backend = new Tine.Tinebase.data.MemoryBackend({
        appName: 'Calendar',
        modelName: 'Event',
        recordClass: Tine.Calendar.Model.Event
    });
}

Tine.Calendar.Model.AttenderArray = [
    {name: 'id'},
    {name: 'cal_event_id'},
    {name: 'user_id'},
    {name: 'user_type'},
    {name: 'role'},
    {name: 'quantity'},
    {name: 'status'},
    {name: 'displaycontainer_id'}
];

Tine.Calendar.Model.Attender = Tine.Tinebase.data.Record.create(Tine.Calendar.Model.AttenderArray, {
    appName: 'Calendar',
    modelName: 'Attender',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('Attender', 'Attendee', n); gettext('Attendee');
    recordName: 'Attender',
    recordsName: 'Attendee',
    containerProperty: 'cal_event_id',
    // ngettext('Event', 'Events', n); gettext('Events');
    containerName: 'Event',
    containersName: 'Events'
});

Tine.Calendar.Model.Attender.getDefaultData = function() {
    return {
        user_type: 'user',
        role: 'REQ',
        quantity: 1,
        status: 'NEEDS-ACTION'
    };
};

Tine.Calendar.Model.Attender.prototype.getUserId = function() {
    var user_id = this.get('user_id');
    if (user_id) {
        user_id = user_id.data? user_id.data : user_id;
        user_id = user_id.accountId ? user_id.accountId : user_id;
        user_id = user_id.account_id ? user_id.account_id : user_id;
    }
    
    return user_id;
};