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
    containersName: 'Calendars',
    isRecurInstance: function() {
        return this.id && this.id.match(/^fakeid/);
    },
    isRecurBase: function() {
        return !!this.get('rrule') && !this.get('recurid');
    },
    isRecurException: function() {
        return !!this.get('recurid') && !( this.idProperty && this.id.match(/^fakeid/));
    },
    /**
     * returns displaycontainer with orignialcontainer as fallback
     * @return {Array}
     */
    getDisplayContainer: function() {
        var displayContainer = this.get('container_id');
        var currentAccountId = Tine.Tinebase.registry.get('currentAccount').accountId;
        
        Ext.each(this.get('attendee'), function(attender) {
            var user_id = attender.user_id ? attender.user_id.accountId ? attender.user_id.accountId : attender.user_id : null;
            if (attender.user_type && attender.user_type == 'user' && user_id == currentAccountId) {
                if (attender.displaycontainer_id) {
                    displayContainer = attender.displaycontainer_id;
                }
                return false;
            }
        }, this);
        
        return displayContainer;
    }
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

Tine.Calendar.Model.EventJsonBackend = Ext.extend(Tine.Tinebase.widgets.app.JsonBackend, {
    
    createRecurException: function(event, deleteInstance, deleteAllFollowing, options) {
        options = options || {};
        options.params = options.params || {};
        options.beforeSuccess = function(response) {
            return [this.recordReader(response)];
        };
        
        var p = options.params;
        p.method = this.appName + '.createRecurException';
        p.recordData = Ext.util.JSON.encode(event.data);
        p.deleteInstance = deleteInstance ? 1 : 0;
        p.deleteAllFollowing = deleteAllFollowing ? 1 : 0;
        
        return this.request(options);
    },
    
    deleteRecurSeries: function(event, options) {
        options = options || {};
        options.params = options.params || {};
        
        var p = options.params;
        p.method = this.appName + '.deleteRecurSeries';
        p.recordData = Ext.util.JSON.encode(event.data);
        
        return this.request(options);
    },
    
    updateRecurSeries: function(event, options) {
        options = options || {};
        options.params = options.params || {};
        options.beforeSuccess = function(response) {
            return [this.recordReader(response)];
        };
        
        var p = options.params;
        p.method = this.appName + '.updateRecurSeries';
        p.recordData = Ext.util.JSON.encode(event.data);
        
        return this.request(options);
    }
});

/**
 * default event backend
 */
if (Tine.Tinebase.widgets) {
    Tine.Calendar.backend = new Tine.Calendar.Model.EventJsonBackend({
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