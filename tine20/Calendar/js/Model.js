/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
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
    { name: 'base_event_id' },
    // scheduleable interface fields with multiple appearance
    { name: 'exdate' },
    //{ name: 'exrule' },
    //{ name: 'rdate' },
    { name: 'rrule' },
    { name: 'poll_id' },
    { name: 'mute' },
    { name: 'is_all_day_event', type: 'bool'},
    { name: 'rrule_until', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'rrule_constraints' },
    { name: 'originator_tz' },
    // grant helper fields
    {name: 'addGrant'       , type: 'bool'},
    {name: 'readGrant'      , type: 'bool'},
    {name: 'editGrant'      , type: 'bool'},
    {name: 'deleteGrant'    , type: 'bool'},
    {name: 'exportGrant'    , type: 'bool'},
    {name: 'freebusyGrant'  , type: 'bool'},
    {name: 'privateGrant'   , type: 'bool'},
    {name: 'syncGrant'      , type: 'bool'},
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
    grantsPath: 'data',
    // ngettext('Calendar', 'Calendars', n); gettext('Calendars');
    containerName: 'Calendar',
    containersName: 'Calendars',
    copyOmitFields: ['uid', 'recurid'],
    allowBlankContainer: false,
    copyNoAppendTitle: true,
    
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
        return this.id && Ext.isFunction(this.id.match) && this.id.match(/^fakeid/);
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
    },

    getSchedulingData: function() {
        var _ = window.lodash,
            schedulingData = _.pick(this.data, ['uid', 'originator_tz', 'dtstart', 'dtend', 'is_all_day_event',
                'transp', 'recurid', 'base_event_id', 'rrule', 'rrule_until', 'exdate', 'rrule_constraints']);

        // NOTE: for transistent events id is not part of data but we need the transistent id e.g. for freeBusy info
        schedulingData.id = this.id;
        return schedulingData;
    },

    inPeriod: function(period) {
        return this.get('dtstart').between(period.from, period.until) ||
            this.get('dtend').between(period.from, period.until);
    },

    hasPoll: function() {
        var _ = window.lodash;
        return ! +_.get(this, 'data.poll_id.closed', true);
    },

    getPollUrl: function(pollId) {
        if (! pollId) {
            pollId = this.get('poll_id');
            if (pollId.id) {
                pollId = pollId.id;
            }
        }
        return Tine.Tinebase.common.getUrl() + 'Calendar/view/poll/' + pollId ;
    },

    getTitle: function() {
        return this.get('summary') + (this.hasPoll() ? '\u00A0\uFFFD' : '');
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
        interval = prefs.get('interval') || 15,
        mainScreen = app.getMainScreen(),
        centerPanel = mainScreen.getCenterPanel(),
        westPanel = mainScreen.getWestPanel(),
        container = westPanel.getContainerTreePanel().getDefaultContainer(),
        organizer = (defaultAttendeeStrategy != 'me' && container && container.ownerContact) ? container.ownerContact : Tine.Tinebase.registry.get('userContact'),
        dtstart = new Date().clearTime().add(Date.HOUR, (new Date().getHours() + 1)),
        makeEventsPrivate = prefs.get('defaultSetEventsToPrivat'),
        eventClass = null,
        period = centerPanel.getCalendarPanel(centerPanel.activeView).getView().getPeriod();
        
    // if dtstart is out of current period, take start of current period
    if (period.from.getTime() > dtstart.getTime() || period.until.getTime() < dtstart.getTime()) {
        dtstart = period.from.clearTime(true).add(Date.HOUR, 9);
    }

    if (makeEventsPrivate == 1) {
        eventClass =  'PRIVATE';
    }

    var data = {
        id: 'new-' + Ext.id(),
        summary: '',
        'class': eventClass,
        dtstart: dtstart,
        dtend: dtstart.add(Date.MINUTE, Tine.Calendar.Model.Event.getMeta('defaultEventDuration')),
        container_id: container,
        transp: 'OPAQUE',
        editGrant: true,
        // needed for action updater / save and close in edit dialog
        readGrant: true,
        organizer: organizer,
        attendee: Tine.Calendar.Model.Event.getDefaultAttendee(organizer, container)
    };
    
    if (prefs.get('defaultalarmenabled')) {
        data.alarms = [{minutes_before: parseInt(prefs.get('defaultalarmminutesbefore'), 10)}];
    }
    
    return data;
};

Tine.Calendar.Model.Event.getDefaultAttendee = function(organizer, container) {
    var app = Tine.Tinebase.appMgr.get('Calendar'),
        mainScreen = app.getMainScreen(),
        centerPanel = mainScreen.getCenterPanel(),
        westPanel = mainScreen.getWestPanel(),
        filteredAttendee = westPanel.getAttendeeFilter().getValue() || [],
        defaultAttendeeData = Tine.Calendar.Model.Attender.getDefaultData(),
        defaultResourceData = Tine.Calendar.Model.Attender.getDefaultResourceData(),
        filteredContainers = westPanel.getContainerTreePanel().getFilterPlugin().getFilter().value || [],
        prefs = app.getRegistry().get('preferences'),
        defaultAttendeeStrategy = prefs.get('defaultAttendeeStrategy') || 'me',// one of['me', 'intelligent', 'calendarOwner', 'filteredAttendee', 'none']
        defaultAttendee = [],
        calendarResources = app.getRegistry().get('calendarResources');
        
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
        case 'none':
            break;
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
                var attendeeData = Ext.applyIf(Ext.decode(Ext.encode(attendee.data)), defaultAttendeeData);

                switch (attendeeData.user_type.toLowerCase()) {
                    case 'memberof':
                        attendeeData.user_type = 'group';
                        break;
                    case 'resource':
                        Ext.apply(attendeeData, defaultResourceData);
                        break;
                    default:
                        break;
                }

                if (attendee == ownAttendee) {
                    attendeeData.status = 'ACCEPTED';
                }
                defaultAttendee.push(attendeeData);
            }, this);
            break;
            
        case 'calendarOwner':
            var addedOwnerIds = [];
            
            Ext.each(filteredContainers, function(filteredContainer){
                if (filteredContainer.ownerContact && filteredContainer.type && filteredContainer.type == 'personal') {
                    var attendeeData = Ext.apply(Tine.Calendar.Model.Attender.getDefaultData(), {
                        user_type: 'user',
                        user_id: filteredContainer.ownerContact
                    });
                    
                    if (attendeeData.user_id.id == organizer.id){
                        attendeeData.status = 'ACCEPTED';
                    }
                    
                    if (addedOwnerIds.indexOf(filteredContainer.ownerContact.id) < 0) {
                        defaultAttendee.push(attendeeData);
                        addedOwnerIds.push(filteredContainer.ownerContact.id);
                    }
                } else if (filteredContainer.type && filteredContainer.type == 'shared' && calendarResources) {
                    Ext.each(calendarResources, function(calendarResource) {
                        if (calendarResource.container_id == filteredContainer.id) {
                            var attendeeData = Ext.apply(Tine.Calendar.Model.Attender.getDefaultData(), {
                                user_type: 'resource',
                                user_id: calendarResource,
                                status: calendarResource.status
                            });
                            defaultAttendee.push(attendeeData);
                        }
                    }, this);
                }
            }, this);
            
            if (container && container.ownerContact && addedOwnerIds.indexOf(container.ownerContact.id) < 0) {
                var attendeeData = Ext.apply(Tine.Calendar.Model.Attender.getDefaultData(), {
                    user_type: 'user',
                    user_id: container.ownerContact
                });
                
                if (container.ownerContact.id == organizer.id){
                    attendeeData.status = 'ACCEPTED';
                }
                
                defaultAttendee.push(attendeeData);
                addedOwnerIds.push(container.ownerContact.id);
            }
            break;
    }
    
    return defaultAttendee;
};

Tine.Calendar.Model.Event.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('Calendar');
    
    return [
        {label: i18n._('Quick Search'), field: 'query', operators: ['contains']},
        {label: app.i18n._('Summary'), field: 'summary'},
        {label: app.i18n._('Location'), field: 'location'},
        {label: app.i18n._('Description'), field: 'description', operators: ['contains', 'notcontains']},
        // _('GENDER_Calendar')
        {filtertype: 'tine.widget.container.filtermodel', app: app, recordClass: Tine.Calendar.Model.Event, /*defaultOperator: 'in',*/ defaultValue: {path: Tine.Tinebase.container.getMyNodePath()}},
        {filtertype: 'calendar.attendee'},
        {
            label: app.i18n._('Attendee Status'),
            gender: app.i18n._('GENDER_Attendee Status'),
            field: 'attender_status',
            filtertype: 'tine.widget.keyfield.filter', 
            app: app, 
            keyfieldName: 'attendeeStatus', 
            defaultOperator: 'notin',
            defaultValue: ['DECLINED']
        },
        {
            label: app.i18n._('Attendee Role'),
            gender: app.i18n._('GENDER_Attendee Role'),
            field: 'attender_role',
            filtertype: 'tine.widget.keyfield.filter', 
            app: app, 
            keyfieldName: 'attendeeRoles'
        },
        {filtertype: 'addressbook.contact', field: 'organizer', label: app.i18n._('Organizer')},
        {filtertype: 'tinebase.tag', app: app},
        {
            label: app.i18n._('Status'),
            gender: app.i18n._('GENDER_Status'),
            field: 'status',
            filtertype: 'tine.widget.keyfield.filter',
            app: { name: 'Calendar' },
            keyfieldName: 'eventStatus',
            defaultAll: true
        },
        {
            label: app.i18n._('Blocking'),
            gender: app.i18n._('GENDER_Blocking'),
            field: 'transp',
            filtertype: 'tine.widget.keyfield.filter',
            app: { name: 'Calendar' },
            keyfieldName: 'eventTransparencies',
            defaultAll: true
        },
        {
            label: app.i18n._('Classification'),
            gender: app.i18n._('GENDER_Classification'),
            field: 'class',
            filtertype: 'tine.widget.keyfield.filter',
            app: { name: 'Calendar' },
            keyfieldName: 'eventClasses',
            defaultAll: true
        },
        {label: i18n._('Last Modified Time'), field: 'last_modified_time', valueType: 'date'},
        //{label: i18n._('Last Modified By'),                                                  field: 'last_modified_by',   valueType: 'user'},
        {label: i18n._('Creation Time'), field: 'creation_time', valueType: 'date'},
        //{label: i18n._('Created By'),                                                        field: 'created_by',         valueType: 'user'},
        {
            filtertype: 'calendar.rrule',
            app: app
        }
    ];
};

Tine.Calendar.Model.Event.datetimeRenderer = function(dt) {
    var app = Tine.Tinebase.appMgr.get('Calendar');

    if (! dt) {
        return app.i18n._('Unknown date');
    }

    return String.format(app.i18n._("{0} {1} o'clock"), dt.format('l') + ', ' + Tine.Tinebase.common.dateRenderer(dt), dt.format('H:i'));
};

// register calendar filters in addressbook
Tine.widgets.grid.ForeignRecordFilter.OperatorRegistry.register('Addressbook', 'Contact', {
    foreignRecordClass: 'Calendar.Event',
    linkType: 'foreignId', 
    filterName: 'ContactAttendeeFilter',
    // i18n._('Event (as attendee)')
    label: 'Event (as attendee)'
});
Tine.widgets.grid.ForeignRecordFilter.OperatorRegistry.register('Addressbook', 'Contact', {
    foreignRecordClass: 'Calendar.Event',
    linkType: 'foreignId', 
    filterName: 'ContactOrganizerFilter',
    // i18n._('Event (as organizer)')
    label: 'Event (as organizer)'
});

// example for explicit definition
//Tine.widgets.grid.FilterRegistry.register('Addressbook', 'Contact', {
//    filtertype: 'foreignrecord',
//    foreignRecordClass: 'Calendar.Event',
//    linkType: 'foreignId', 
//    filterName: 'ContactAttendeeFilter',
//    // i18n._('Event attendee')
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
    appName: 'Calendar',
    modelName: 'Event',
    recordClass: Tine.Calendar.Model.Event,

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

    promiseCreateRecurException: function(event, deleteInstance, deleteAllFollowing, checkBusyConflicts, options) {
        var me = this;
        return new Promise(function (fulfill, reject) {
            try {
                me.createRecurException(event, deleteInstance, deleteAllFollowing, checkBusyConflicts, Ext.apply(options || {}, {
                    success: function (r) {
                        fulfill(r);
                    },
                    failure: function (error) {
                        reject(new Error(error));
                    }
                }));
            } catch (error) {
                if (Ext.isFunction(reject)) {
                    reject(new Error(options));
                }
            }
        });
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
    Tine.Calendar.backend = new Tine.Calendar.Model.EventJsonBackend({});
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
    {name: 'checked'}, // filter grid helper field
    {name: 'fbInfo'}   // helper field
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
     */
    getTitle: function() {
        var p = Tine.Calendar.AttendeeGridPanel.prototype;
        return p.renderAttenderName.call(p, this.get('user_id'), false, this);
    },

    getCompoundId: function(mapGroupmember) {
        var type = this.get('user_type');
        type = mapGroupmember && type == 'groupmember' ? 'user' : type;

        return type + '-' + this.getUserId();
    },

    /**
     * returns true for external contacts
     */
    isExternal: function() {

        var isExternal = false,
            user_type = this.get('user_type');
        if (user_type == 'user' || user_type == 'groupmember') {
            isExternal = !this.getUserAccountId();
        }

        return isExternal;
    },

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
    },

    getIconCls: function() {
        var type = this.get('user_type'),
            cls = 'tine-grid-row-action-icon cal-attendee-type-';

        switch(type) {
            case 'user':
                cls = 'tine-grid-row-action-icon renderer_typeAccountIcon';
                break;
            case 'group':
                cls = 'tine-grid-row-action-icon renderer_accountGroupIcon';
                break;
            default:
                cls += type;
                break;
        }

        return cls;
    }
});

Tine.Calendar.Model.Attender.getSortOrder = function(user_type) {
    var sortOrders = {
        'user': 1,
        'groupmemeber': 1,
        'group': 2,
        'resource': 3
    };

    return sortOrders[user_type] || 4;
};

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
        // @TODO have some config here? user vs. default?
        user_type: 'any',
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

    if (Ext.isString(attendeeData)) {
        attendeeData = Ext.decode(attendeeData || null);
    }

    Ext.each(attendeeData, function(attender) {
        if (attender) {
            var record = new Tine.Calendar.Model.Attender(attender, attender.id && Ext.isString(attender.id) ? attender.id : Ext.id());
            if (record.get('user_id') == "currentContact") {
                record.set('user_id', Tine.Tinebase.registry.get('userContact'));
            }
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
};
    
/**
 * returns attendee record of given attendee if exists, else false
 * @static
 */
Tine.Calendar.Model.Attender.getAttendeeStore.getAttenderRecord = function(attendeeStore, attendee) {
    var attendeeRecord = false;

    if (! Ext.isFunction(attendee.beginEdit)) {
        attendee = new Tine.Calendar.Model.Attender(attendee, attendee.id);
    }

    attendeeStore.each(function(r) {
        var attendeeType = [attendee.get('user_type')];

        // add groupmember for user
        if (attendeeType[0] == 'user') {
            attendeeType.push('groupmember');
        }
        if (attendeeType[0] == 'groupmember') {
            attendeeType.push('user');
        }

        if (attendeeType.indexOf(r.get('user_type')) >= 0 && r.getUserId() == attendee.getUserId()) {
            attendeeRecord = r;
            return false;
        }
    }, this);
    
    return attendeeRecord;
};

Tine.Calendar.Model.Attender.getAttendeeStore.signatureDelimiter = ';';

Tine.Calendar.Model.Attender.getAttendeeStore.getSignature = function(attendee) {
    var _ = window.lodash;

    attendee = _.isFunction(attendee.beginEdit) ? attendee.data : attendee;
    return [attendee.cal_event_id, attendee.user_type, attendee.user_id.id || attendee.user_id, attendee.role]
        .join(Tine.Calendar.Model.Attender.getAttendeeStore.signatureDelimiter);
};

Tine.Calendar.Model.Attender.getAttendeeStore.fromSignature = function(signatureId) {
    var ids = signatureId.split(Tine.Calendar.Model.Attender.getAttendeeStore.signatureDelimiter);

    return new Tine.Calendar.Model.Attender({
        cal_event_id: ids[0],
        user_type: ids[1],
        user_id: ids[2],
        role: ids[3]
    });
}

/**
 * returns attendee data
 * optinally fills into event record
 */
Tine.Calendar.Model.Attender.getAttendeeStore.getData = function(attendeeStore, event) {
    var attendeeData = [];

    Tine.Tinebase.common.assertComparable(attendeeData);

    attendeeStore.each(function (attender) {
        var user_id = attender.get('user_id');
        if (user_id/* && user_id.id*/) {
            if (typeof user_id.get == 'function') {
                attender.data.user_id = user_id.data;
            }

            attendeeData.push(attender.data);
        }
    }, this);

    if (event) {
        event.set('attendee', attendeeData);
    }

    return attendeeData;
};

// PROXY
Tine.Calendar.Model.AttenderProxy = function(config) {
    Tine.Calendar.Model.AttenderProxy.superclass.constructor.call(this, config);
    this.jsonReader.readRecords = this.readRecords.createDelegate(this);
};
Ext.extend(Tine.Calendar.Model.AttenderProxy, Tine.Tinebase.data.RecordProxy, {
    /**
     * provide events to do an freeBusy info checkup for when searching attendee
     *
     * @cfg {Function} freeBusyEventsProvider
     */
    freeBusyEventsProvider: Ext.emptyFn,

    recordClass: Tine.Calendar.Model.Attender,

    searchRecords: function(filter, paging, options) {
        var _ = window.lodash,
            fbEvents = _.union([].concat(this.freeBusyEventsProvider()));

        _.set(options, 'params.ignoreUIDs', _.union(_.map(fbEvents, 'data.uid')));
        _.set(options, 'params.events', _.map(fbEvents, function(event) {
            return event.getSchedulingData();
        }));

        return Tine.Calendar.Model.AttenderProxy.superclass.searchRecords.apply(this, arguments);
    },

    readRecords : function(resultData){
        var _ = window.lodash,
            totalcount = 0,
            fbEvents = _.compact([].concat(this.freeBusyEventsProvider())),
            records = [],
            fbInfos = _.map(fbEvents, function(fbEvent) {
                return new Tine.Calendar.FreeBusyInfo(resultData.freeBusyInfo[fbEvent.id]);
            });

        _.each(['user', 'group', 'resource'], function(type) {
            var typeResult = _.get(resultData, type, {}),
                typeCount = _.get(typeResult, 'totalcount', 0),
                typeData = _.get(typeResult, 'results', []);

            totalcount += +typeCount;
            _.each(typeData, function(userData) {
                var id = type + '-' + userData.id,
                    attendeeData = _.assign(Tine.Calendar.Model.Attender.getDefaultData(), {
                        id: id,
                        user_type: type,
                        user_id: userData
                    }),
                    attendee = new Tine.Calendar.Model.Attender(attendeeData, id);

                if (fbEvents.length) {
                    attendee.set('fbInfo', _.map(fbInfos, function(fbInfo, idx) {
                        return fbInfo.getStateOfAttendee(attendee, fbEvents[idx]);
                    }).join('<br >'));
                }
                records.push(attendee);
            });
        });

        return {
            success : true,
            records: records,
            totalRecords: totalcount
        };
    }
});

/**
 * @namespace Tine.Calendar.Model
 * @class Tine.Calendar.Model.Resource
 * @extends Tine.Tinebase.data.Record
 * Resource Record Definition
 */
Tine.Calendar.Model.Resource = Tine.Tinebase.data.Record.create(Tine.Tinebase.Model.genericFields.concat([
    {name: 'id'},
    {name: 'name'},
    {name: 'hierarchy'},
    {name: 'description'},
    {name: 'email'},
    {name: 'max_number_of_people', type: 'int'},
    {name: 'type', type: 'keyField', keyFieldConfigName: 'resourceTypes'},
    {name: 'status', type: 'keyField', keyFieldConfigName: 'attendeeStatus'},
    {name: 'busy_type', type: 'keyField', keyFieldConfigName: 'freebusyTypes'},
    {name: 'suppress_notification', type: 'bool'},
    {name: 'tags'},
    {name: 'notes'},
    {name: 'grants'},
    { name: 'attachments'},
    { name: 'relations',   omitDuplicateResolving: true},
    { name: 'customfields', omitDuplicateResolving: true}
]), {
    appName: 'Calendar',
    modelName: 'Resource',
    idProperty: 'id',
    titleProperty: 'name',
    containerProperty: 'container_id',
    // ngettext('Resource', 'Resources', n); gettext('Resources');
    recordName: 'Resource',
    recordsName: 'Resources',

    initData: function() {
        if (Tine.Tinebase.common.hasRight('manage', 'Calendar', 'resources')) {
            var _ = window.lodash
            account_grants = _.get(this, this.grantsPath, {});

            _.assign(account_grants, {
                'resourceInviteGrant': true,
                'resourceReadGrant': true,
                'resourceEditGrant': true,
                'resourceExportGrant': true,
                'resourceSyncGrant': true,
                'resourceAdminGrant': true
            });
            _.set(this, this.grantsPath, account_grants);
        }
    }
});

/**
 * get default data for a new resource
 *
 * @return {Object} default data
 * @static
 */
Tine.Calendar.Model.Resource.getDefaultData = function() {
    // add admin (and other) grant for resource managers
    var grants = Tine.Tinebase.common.hasRight('manage', 'Calendar', 'resources') ? [{
        account_id: Tine.Tinebase.registry.get('currentAccount').accountId,
        account_type: "user",
        account_name: Tine.Tinebase.registry.get('currentAccount').accountDisplayName,
        'resourceInviteGrant': true,
        'resourceReadGrant': true,
        'resourceEditGrant': true,
        'resourceExportGrant': true,
        'resourceSyncGrant': true,
        'resourceAdminGrant': true
    }]: []

    grants.push({
        account_id: "0",
        account_type: "anyone",
        account_name: i18n._('Anyone'),
        resourceInviteGrant: true,
        eventsFreebusyGrant: true
    });

    var data = {
        grants: grants
    };

    return data;
};

Tine.Calendar.Model.Resource.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('Calendar');

    return [
        {label: i18n._('Quick Search'), field: 'query', operators: ['contains']},
        {label: app.i18n._('Name'), field: 'name'},
        {label: app.i18n._('Calendar Hierarchy/Name'), field: 'hierarchy'},
        {label: app.i18n._('Email'), field: 'email'},
        {label: app.i18n._('Description'), field: 'description', operators: ['contains', 'notcontains']},
        {label: app.i18n._('Maximum number of attendee'), field: 'max_number_of_people'},
        {
            label: app.i18n._('Type'),
            field: 'type',
            filtertype: 'tine.widget.keyfield.filter',
            app: app,
            keyfieldName: 'resourceTypes'
        },
        {
            label: app.i18n._('Default attendee status'),
            field: 'status',
            filtertype: 'tine.widget.keyfield.filter',
            app: app,
            keyfieldName: 'attendeeStatus'
        },
        {
            label: app.i18n._('Busy Type'),
            field: 'type',
            filtertype: 'tine.widget.keyfield.filter',
            app: app,
            keyfieldName: 'freebusyTypes'
        },
        {filtertype: 'tinebase.tag', app: app}
    ];
};

/**
 * @namespace   Tine.Calendar.Model
 * @class       Tine.Calendar.Model.ResourceType
 * @extends     Tine.Tinebase.data.Record
 * ResourceType Record Definition
 */
Tine.Calendar.Model.ResourceType = Tine.Tinebase.data.Record.create([
    {name: 'id'},
    {name: 'is_location'},
    {name: 'value'},
    {name: 'icon'},
    {name: 'color'},
    {name: 'system'},
], {
    appName: 'Calendar',
    modelName: 'ResourceType',
    idProperty: 'id'
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
