/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
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
    { name: 'status' },
    { name: 'summary' },
    { name: 'url' },
    { name: 'uid' },
    // ical common fields with multiple appearance
    //{ name: 'attach' },
    { name: 'attendee' },
    { name: 'alarms'},
    { name: 'tags' },
    { name: 'notes'},
    { name: 'attachments'},
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
    { name: 'mute' },
    { name: 'is_all_day_event', type: 'bool'},
    { name: 'rrule_until', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'originator_tz' },
    // grant helper fields
    {name: 'readGrant'   , type: 'bool'},
    {name: 'editGrant'   , type: 'bool'},
    {name: 'deleteGrant' , type: 'bool'},
    {name: 'editGrant'   , type: 'bool'},
    // relations
    { name: 'relations',   omitDuplicateResolving: true},
    { name: 'customfields', omitDuplicateResolving: true}
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
    copyOmitFields: ['uid', 'recurid'],
    
    /**
     * mark record out of current filter
     * 
     * @type Boolean
     */
    outOfFilter: false,

    /**
     * default duration for new events
     */
    defaultEventDuration: 60,
    
    /**
     * returns displaycontainer with orignialcontainer as fallback
     * 
     * @return {Array}
     */
    getDisplayContainer: function() {
        var displayContainer = this.get('container_id');
        var currentAccountId = Tine.Tinebase.registry.get('currentAccount').accountId;
        
        var attendeeStore = this.getAttendeeStore();
        
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
        return !! this.get('recurid') && ! this.isRecurInstance();
    },
    
    /**
     * is this event an recuring event instance?
     * 
     * @return {Boolean}
     */
    isRecurInstance: function() {
        return this.id && this.id.match(/^fakeid/);
    },
    
    /**
     * returns store of attender objects
     * 
     * @param  {Array} attendeeData
     * @return {Ext.data.Store}
     */
    getAttendeeStore: function() {
        return Tine.Calendar.Model.Attender.getAttendeeStore(this.get('attendee'));
    },
    
    /**
     * returns attender record of current account if exists, else false
     */
    getMyAttenderRecord: function() {
        var attendeeStore = this.getAttendeeStore();
        return Tine.Calendar.Model.Attender.getAttendeeStore.getMyAttenderRecord(attendeeStore);
    }
});


/**
 * get default data for a new event
 *  
 * @return {Object} default data
 * @static
 */ 
Tine.Calendar.Model.Event.getDefaultData = function() {
    var app = Tine.Tinebase.appMgr.get('Calendar'),
        prefs = app.getRegistry().get('preferences'),
        defaultAttendeeStrategy = prefs.get('defaultAttendeeStrategy') || 'me',
        mainScreen = app.getMainScreen(),
        centerPanel = mainScreen.getCenterPanel(),
        westPanel = mainScreen.getWestPanel(),
        container = westPanel.getContainerTreePanel().getDefaultContainer(),
        organizer = (defaultAttendeeStrategy != 'me' && container.ownerContact) ? container.ownerContact : Tine.Tinebase.registry.get('userContact'),
        dtstart = new Date().clearTime().add(Date.HOUR, (new Date().getHours() + 1)),
        period = centerPanel.getCalendarPanel(centerPanel.activeView).getView().getPeriod();
        
    // if dtstart is out of current period, take start of current period
    if (period.from.getTime() > dtstart.getTime() || period.until.getTime() < dtstart.getTime()) {
        dtstart = period.from.clearTime(true).add(Date.HOUR, 9);
    }
    
    var data = {
        summary: '',
        dtstart: dtstart,
        dtend: dtstart.add(Date.MINUTE, Tine.Calendar.Model.Event.getMeta('defaultEventDuration')),
        container_id: container,
        transp: 'OPAQUE',
        editGrant: true,
        organizer: organizer,
        attendee: Tine.Calendar.Model.Event.getDefaultAttendee(organizer) /*[
            Ext.apply(Tine.Calendar.Model.Attender.getDefaultData(), {
                user_type: 'user',
                user_id: Tine.Tinebase.registry.get('userContact'),
                status: 'ACCEPTED'
            })
        ]*/
    };
    
    if (prefs.get('defaultalarmenabled')) {
        data.alarms = [{minutes_before: parseInt(prefs.get('defaultalarmminutesbefore'), 10)}];
    }
    
    return data;
};

Tine.Calendar.Model.Event.getDefaultAttendee = function(organizer) {
    var app = Tine.Tinebase.appMgr.get('Calendar'),
        mainScreen = app.getMainScreen(),
        centerPanel = mainScreen.getCenterPanel(),
        westPanel = mainScreen.getWestPanel(),
        filteredAttendee = westPanel.getAttendeeFilter().getValue() || [],
        defaultAttendeeData = Tine.Calendar.Model.Attender.getDefaultData(),
        defaultResourceData = Tine.Calendar.Model.Attender.getDefaultResourceData(),
        filteredContainers = westPanel.getContainerTreePanel().getFilterPlugin().getFilter().value || [],
        prefs = app.getRegistry().get('preferences'),
        defaultAttendeeStrategy = prefs.get('defaultAttendeeStrategy') || 'me', // one of['me', 'intelligent', 'calendarOwner', 'filteredAttendee']
        defaultAttendee = [];
        
    // shift -> change intelligent <-> me
    if (Ext.EventObject.shiftKey) {
        defaultAttendeeStrategy = defaultAttendeeStrategy == 'intelligent' ? 'me' :
                                  defaultAttendeeStrategy == 'me' ? 'intelligent' :
                                  defaultAttendeeStrategy;
    }
    
    // alt -> prefer calendarOwner in intelligent mode
    if (defaultAttendeeStrategy == 'intelligent') {
        defaultAttendeeStrategy = filteredAttendee.length && !Ext.EventObject.altKey > 0 ? 'filteredAttendee' :
                                  filteredContainers.length > 0 ? 'calendarOwner' :
                                  'me';
    }
    
    switch(defaultAttendeeStrategy) {
        case 'me':
            defaultAttendee.push(Ext.apply(Tine.Calendar.Model.Attender.getDefaultData(), {
                user_type: 'user',
                user_id: Tine.Tinebase.registry.get('userContact'),
                status: 'ACCEPTED'
            }));
            break;
            
        case 'filteredAttendee':
            var attendeeStore = Tine.Calendar.Model.Attender.getAttendeeStore(filteredAttendee),
                ownAttendee = Tine.Calendar.Model.Attender.getAttendeeStore.getMyAttenderRecord(attendeeStore);
                
            attendeeStore.each(function(attendee){
                var attendeeData = attendee.data.user_type == 'user' ? Ext.apply(attendee.data, defaultAttendeeData) : Ext.apply(attendee.data, defaultResourceData);
                if (attendee == ownAttendee) {
                    attendeeData.status = 'ACCEPTED';
                }
                defaultAttendee.push(attendeeData);
            }, this);
            break;
            
        case 'calendarOwner':
            var addedOwnerIds = [];
            Ext.each(filteredContainers, function(container){
                if (container.ownerContact) {
                    var attendeeData = Ext.apply(Tine.Calendar.Model.Attender.getDefaultData(), {
                        user_type: 'user',
                        user_id: container.ownerContact
                    });
                    
                    if (attendeeData.user_id.id == organizer.id){
                        attendeeData.status = 'ACCEPTED';
                    }

                    if (addedOwnerIds.indexOf(container.ownerContact.id) < 0) {
                        defaultAttendee.push(attendeeData);
                        addedOwnerIds.push(container.ownerContact.id);
                    }
                }
            }, this);
            
            break;
    }
    
    return defaultAttendee;
};

Tine.Calendar.Model.Event.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('Calendar');
    
    return [
        {label: _('Quick Search'), field: 'query', operators: ['contains']},
        {label: app.i18n._('Summary'), field: 'summary'},
        {label: app.i18n._('Location'), field: 'location'},
        {label: app.i18n._('Description'), field: 'description'},
        {filtertype: 'tine.widget.container.filtermodel', app: app, recordClass: Tine.Calendar.Model.Event, /*defaultOperator: 'in',*/ defaultValue: {path: Tine.Tinebase.container.getMyNodePath()}},
        {filtertype: 'calendar.attendee'},
        {
            label: app.i18n._('Attendee Status'),
            field: 'attender_status',
            filtertype: 'tine.widget.keyfield.filter', 
            app: app, 
            keyfieldName: 'attendeeStatus', 
            defaultOperator: 'notin',
            defaultValue: ['DECLINED']
        },
        {
            label: app.i18n._('Attendee Role'),
            field: 'attender_role',
            filtertype: 'tine.widget.keyfield.filter', 
            app: app, 
            keyfieldName: 'attendeeRoles'
        },
        {filtertype: 'addressbook.contact', field: 'organizer', label: app.i18n._('Organizer')},
        {filtertype: 'tinebase.tag', app: app}
    ];
};

// register calendar filters in addressbook
Tine.widgets.grid.ForeignRecordFilter.OperatorRegistry.register('Addressbook', 'Contact', {
    foreignRecordClass: 'Calendar.Event',
    linkType: 'foreignId', 
    filterName: 'ContactAttendeeFilter',
    // _('Event (as attendee)')
    label: 'Event (as attendee)'
});
Tine.widgets.grid.ForeignRecordFilter.OperatorRegistry.register('Addressbook', 'Contact', {
    foreignRecordClass: 'Calendar.Event',
    linkType: 'foreignId', 
    filterName: 'ContactOrganizerFilter',
    // _('Event (as organizer)')
    label: 'Event (as organizer)'
});

// example for explicit definition
//Tine.widgets.grid.FilterRegistry.register('Addressbook', 'Contact', {
//    filtertype: 'foreignrecord',
//    foreignRecordClass: 'Calendar.Event',
//    linkType: 'foreignId', 
//    filterName: 'ContactAttendeeFilter',
//    // _('Event attendee')
//    label: 'Event attendee'
//});

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
    createRecurException: function(event, deleteInstance, deleteAllFollowing, checkBusyConflicts, options) {
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
        p.checkBusyConflicts = checkBusyConflicts ? 1 : 0;
        
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
    updateRecurSeries: function(event, checkBusyConflicts, options) {
        options = options || {};
        options.params = options.params || {};
        options.beforeSuccess = function(response) {
            return [this.recordReader(response)];
        };
        
        var p = options.params;
        p.method = this.appName + '.updateRecurSeries';
        p.recordData = event.data;
        p.checkBusyConflicts = checkBusyConflicts ? 1 : 0;
        
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
    {name: 'user_id', sortType: Tine.Tinebase.common.accountSortType },
    {name: 'user_type'},
    {name: 'role', type: 'keyField', keyFieldConfigName: 'attendeeRoles'},
    {name: 'quantity'},
    {name: 'status', type: 'keyField', keyFieldConfigName: 'attendeeStatus'},
    {name: 'status_authkey'},
    {name: 'displaycontainer_id'},
    {name: 'transp'},
    {name: 'checked'} // filter grid helper field
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
            case 'groupmember':
            case 'memberOf':
                if (userData.hasOwnProperty('contact_id')) {
                    // userData contains account
                    return userData.contact_id;
                } else if (userData.hasOwnProperty('account_id')) {
                    // userData contains contact
                    return userData.id;
                } else if (userData.group_id) {
                    // userData contains list
                    return userData.id;
                } else if (userData.list_id) {
                    // userData contains group
                    return userData.list_id;
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
 * get default data for a new resource
 *  
 * @return {Object} default data
 * @static
 */ 
Tine.Calendar.Model.Attender.getDefaultResourceData = function() {
    return {
        user_type: 'resource',
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
        if (attender) {
            var record = new Tine.Calendar.Model.Attender(attender, attender.id && Ext.isString(attender.id) ? attender.id : Ext.id());
            attendeeStore.addSorted(record);
        }
    });
    
    return attendeeStore;
};

/**
 * returns attender record of current account if exists, else false
 * @static
 */
Tine.Calendar.Model.Attender.getAttendeeStore.getMyAttenderRecord = function(attendeeStore) {
        var currentAccountId = Tine.Tinebase.registry.get('currentAccount').accountId;
        var myRecord = false;
        
        attendeeStore.each(function(attender) {
            var userAccountId = attender.getUserAccountId();
            if (userAccountId == currentAccountId) {
                myRecord = attender;
                return false;
            }
        }, this);
        
        return myRecord;
    }
    
/**
 * returns attendee record of given attendee if exists, else false
 * @static
 */
Tine.Calendar.Model.Attender.getAttendeeStore.getAttenderRecord = function(attendeeStore, attendee) {
    var attendeeRecord = false;
    
    attendeeStore.each(function(r) {
        var attendeeType = [attendee.get('user_type')];

        // add groupmember for user
        if (attendeeType[0] == 'user') {
            attendeeType.push('groupmember');
        }

        if (attendeeType.indexOf(r.get('user_type') >= 0) && r.getUserId() == attendee.getUserId()) {
            attendeeRecord = r;
            return false;
        }
    }, this);
    
    return attendeeRecord;
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
    {name: 'notes'},
    {name: 'grants'}
]), {
    appName: 'Calendar',
    modelName: 'Resource',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('Resource', 'Resources', n); gettext('Resources');
    recordName: 'Resource',
    recordsName: 'Resources'
});

/**
 * @namespace   Tine.Calendar.Model
 * @class       Tine.Calendar.Model.iMIP
 * @extends     Tine.Tinebase.data.Record
 * iMIP Record Definition
 */
Tine.Calendar.Model.iMIP = Tine.Tinebase.data.Record.create([
    {name: 'id'},
    {name: 'ics'},
    {name: 'method'},
    {name: 'originator'},
    {name: 'userAgent'},
    {name: 'event'},
    {name: 'existing_event'},
    {name: 'preconditions'}
], {
    appName: 'Calendar',
    modelName: 'iMIP',
    idProperty: 'id'
});
