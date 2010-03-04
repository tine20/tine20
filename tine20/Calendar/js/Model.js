/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

Ext.ns('Tine.Calendar', 'Tine.Calendar.Model');

/**
 * @namespace Tine.Calendar.Model
 * @class Tine.Calendar.Model.Event
 * @extends Tine.Tinebase.data.Record
 * Event record definition
 */
Tine.Calendar.Model.Event = Tine.Tinebase.data.Record.create(Tine.Tinebase.Model.genericFields.concat([
    { name: 'id' },
    { name: 'dtend', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'transp' },
    // ical common fields
    { name: 'class' },
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
    { name: 'alarms'},
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
]), {
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
    
    /**
     * returns displaycontainer with orignialcontainer as fallback
     * 
     * @return {Array}
     */
    getDisplayContainer: function() {
        var displayContainer = this.get('container_id');
        var currentAccountId = Tine.Tinebase.registry.get('currentAccount').accountId;
        
        var attendeeStore = Tine.Calendar.Model.Attender.getAttendeeStore(this.get('attendee'));
        
        attendeeStore.each(function(attender) {
            var userAccountId = attender.getUserAccountId();
            if (userAccountId == currentAccountId) {
                var container = attender.get('displaycontainer_id');
                if (container) {
                    displayContainer = container;
                }
                return false;
            }
        }, this);
        
        return displayContainer;
    },
    
    /**
     * is this event a recuring base event?
     * 
     * @return {Boolean}
     */
    isRecurBase: function() {
        return !!this.get('rrule') && !this.get('recurid');
    },
    
    /**
     * is this event a recuring exception?
     * 
     * @return {Boolean}
     */
    isRecurException: function() {
        return !!this.get('recurid') && !( this.idProperty && this.id.match(/^fakeid/));
    },
    
    /**
     * is this event an recuring event instance?
     * 
     * @return {Boolean}
     */
    isRecurInstance: function() {
        return this.id && this.id.match(/^fakeid/);
    }
});


/**
 * @namespace Tine.Calendar.Model
 * 
 * get default data for a new event
 * @todo: attendee according to calendar selection
 *  
 * @return {Object} default data
 * @static
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
        organizer: Tine.Tinebase.registry.get('userContact'),
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

Tine.Calendar.Model.Event.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('Calendar');
    
    return [
        {label: _('Quick search'), field: 'query', operators: ['contains']},
        {filtertype: 'calendar.attendeestatus'},
        {filtertype: 'tinebase.tag', app: app}
    ];
};

/**
 * @namespace Tine.Calendar.Model
 * @class Tine.Calendar.Model.EventJsonBackend
 * @extends Tine.Tinebase.data.RecordProxy
 * 
 * JSON backend for events
 */
Tine.Calendar.Model.EventJsonBackend = Ext.extend(Tine.Tinebase.data.RecordProxy, {
    
    /**
     * Creates a recuring event exception
     * 
     * @param {Tine.Calendar.Model.Event} event
     * @param {Boolean} deleteInstance
     * @param {Boolean} deleteAllFollowing
     * @param {Object} options
     * @return {String} transaction id
     */
    createRecurException: function(event, deleteInstance, deleteAllFollowing, options) {
        options = options || {};
        options.params = options.params || {};
        options.beforeSuccess = function(response) {
            return [this.recordReader(response)];
        };
        
        var p = options.params;
        p.method = this.appName + '.createRecurException';
        p.recordData = event.data;
        p.deleteInstance = deleteInstance ? 1 : 0;
        p.deleteAllFollowing = deleteAllFollowing ? 1 : 0;
        
        return this.doXHTTPRequest(options);
    },
    
    /**
     * delete a recuring event series
     * 
     * @param {Tine.Calendar.Model.Event} event
     * @param {Object} options
     * @return {String} transaction id
     */
    deleteRecurSeries: function(event, options) {
        options = options || {};
        options.params = options.params || {};
        
        var p = options.params;
        p.method = this.appName + '.deleteRecurSeries';
        p.recordData = event.data;
        
        return this.doXHTTPRequest(options);
    },
    
    /**
     * updates a recuring event series
     * 
     * @param {Tine.Calendar.Model.Event} event
     * @param {Object} options
     * @return {String} transaction id
     */
    updateRecurSeries: function(event, options) {
        options = options || {};
        options.params = options.params || {};
        options.beforeSuccess = function(response) {
            return [this.recordReader(response)];
        };
        
        var p = options.params;
        p.method = this.appName + '.updateRecurSeries';
        p.recordData = event.data;
        
        return this.doXHTTPRequest(options);
    }
});

/*
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

/**
 * @namespace Tine.Calendar.Model
 * @class Tine.Calendar.Model.Attender
 * @extends Tine.Tinebase.data.Record
 * Attender Record Definition
 */
Tine.Calendar.Model.Attender = Tine.Tinebase.data.Record.create([
    {name: 'id'},
    {name: 'cal_event_id'},
    {name: 'user_id', sortType:  function(user_id) {
        if (user_id && user_id.n_fn) {
            return user_id.n_fn;
        } else if (user_id && user_id.name) {
            return user_id.name;
        } else {
            return user_id;
        }
    }},
    {name: 'user_type'},
    {name: 'role'},
    {name: 'quantity'},
    {name: 'status'},
    {name: 'displaycontainer_id'}
], {
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
    containersName: 'Events',
    
    /**
     * gets name of attender
     * 
     * @return {String}
     *
    getName: function() {
        var user_id = this.get('user_id');
        if (! user_id) {
            return Tine.Tinebase.appMgr.get('Calendar').i18n._('No Information');
        }
        
        var userData = (typeof user_id.get == 'function') ? user_id.data : user_id;
    },
    */
    
    /**
     * returns account_id if attender is/has a user account
     * 
     * @return {String}
     */
    getUserAccountId: function() {
        var user_type = this.get('user_type');
        if (user_type == 'user' || user_type == 'groupmember') {
            var user_id = this.get('user_id');
            if (! user_id) {
                return null;
            }
            
            // we expect user_id to be a user or contact object or record
            if (typeof user_id.get == 'function') {
                if (user_id.get('contact_id')) {
                    // user_id is a account record
                    return user_id.get('accountId');
                } else {
                    // user_id is a contact record
                    return user_id.get('account_id');
                }
            } else if (user_id.hasOwnProperty('contact_id')) {
                // user_id contains account data
                return user_id.accountId;
            } else if (user_id.hasOwnProperty('account_id')) {
                // user_id contains contact data
                return user_id.account_id;
            }
            
            // this might happen if contact resolved, due to right restrictions
            return user_id;
            
        }
        return null;
    },
    
    /**
     * returns id of attender of any kind
     */
    getUserId: function() {
        var user_id = this.get('user_id');
        if (! user_id) {
            return null;
        }
        
        var userData = (typeof user_id.get == 'function') ? user_id.data : user_id;
        
        if (!userData) {
            return null;
        }
        
        if (typeof userData != 'object') {
            return userData;
        }
        
        switch (this.get('user_type')) {
            case 'user':
                if (userData.hasOwnProperty('contact_id')) {
                    // userData contains account
                    return userData.contact_id;
                } else if (userData.hasOwnProperty('account_id')) {
                    // userData contains contact
                    return userData.id;
                }
                break;
            default:
                return userData.id
                break;
        }
    }
});

/**
 * @namespace Tine.Calendar.Model
 * 
 * get default data for a new attender
 *  
 * @return {Object} default data
 * @static
 */ 
Tine.Calendar.Model.Attender.getDefaultData = function() {
    return {
        user_type: 'user',
        role: 'REQ',
        quantity: 1,
        status: 'NEEDS-ACTION'
    };
};

/**
 * @namespace Tine.Calendar.Model
 * 
 * creates store of attender objects
 * 
 * @param  {Array} attendeeData
 * @return {Ext.data.Store}
 * @static
 */ 
Tine.Calendar.Model.Attender.getAttendeeStore = function(attendeeData) {
    var attendeeStore = new Ext.data.SimpleStore({
        fields: Tine.Calendar.Model.Attender.getFieldDefinitions(),
        sortInfo: {field: 'user_id', direction: 'ASC'}
    });
    
    Ext.each(attendeeData, function(attender) {
        var record = new Tine.Calendar.Model.Attender(attender, attender.id);
        attendeeStore.addSorted(record);
    });
    
    return attendeeStore;
};

Tine.Calendar.Model.Attender.getAttendeeStatusStore = function() {
    if (! Tine.Calendar.Model.Attender.attendeeStatusStore) {
        var app = Tine.Tinebase.appMgr.get('Calendar');
        Tine.Calendar.Model.Attender.attendeeStatusStore = new Ext.data.ArrayStore({
            storeId: 'calendar.attener.status',
            idIndex: 0,  
            fields: ['id', 'status_name'],
            data: [
                ['NEEDS-ACTION', app.i18n._('No response')],
                ['ACCEPTED',     app.i18n._('Accepted')   ],
                ['DECLINED',     app.i18n._('Declined')   ],
                ['TENTATIVE',    app.i18n._('Tentative')  ]
            ]
        });
    }
    
    return Tine.Calendar.Model.Attender.attendeeStatusStore;
}

/**
 * @namespace Tine.Calendar.Model
 * @class Tine.Calendar.Model.Resource
 * @extends Tine.Tinebase.data.Record
 * Resource Record Definition
 */
Tine.Calendar.Model.Resource = Tine.Tinebase.data.Record.create(Tine.Tinebase.Model.genericFields.concat([
    {name: 'id'},
    {name: 'name'},
    {name: 'description'},
    {name: 'email'},
    {name: 'is_location', type: 'bool'},
    {name: 'tags'},
    {name: 'notes'}
]), {
    appName: 'Calendar',
    modelName: 'Resource',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('Resource', 'Resources', n); gettext('Resources');
    recordName: 'Resource',
    recordsName: 'Resources',
    containerProperty: null
});

/* lets try it with Ext.Direct
Tine.Calendar.backend = new Tine.Calendar.Model.EventJsonBackend({
    appName: 'Calendar',
    modelName: 'Resource',
    recordClass: Tine.Calendar.Model.Resource
});
*/
