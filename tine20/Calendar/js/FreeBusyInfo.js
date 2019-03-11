/*
 * Tine 2.0
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Calendar');

/**
 *
 * @param freeBusyInfo
 * @constructor
 */
Tine.Calendar.FreeBusyInfo = function(freeBusyInfo) {
    this.app = Tine.Tinebase.appMgr.get('Calendar');
    this.freeBusyInfo = freeBusyInfo;

    var _ = window.lodash;
    this.byAttendee = _.groupBy(this.freeBusyInfo, function(fbInfo) {
        var type = fbInfo.user_type == 'groupmember' ? 'user' : fbInfo.user_type;
        return [type, '-', fbInfo.user_id].join('');
    });
    this.statusMap = _.mapValues(this.byAttendee, function(fbInfos) {
        var state = 0;
        _.each(fbInfos, function(fbInfo) {
            state = Math.max(state, Tine.Calendar.FreeBusyInfo.states[fbInfo.type]);
        });
        return state;
    });
    this.attendeeCount = _.keys(this.statusMap).length;
};

Tine.Calendar.FreeBusyInfo.states = {'FREE':0, 'BUSY_TENTATIVE':1, 'BUSY':2, 'BUSY_UNAVAILABLE':3};

Tine.Calendar.FreeBusyInfo.prototype = {
    /**
     * @property {Object} byAttendee
     * fbInfo grouped by attendee
     */
    byAttendee: null,

    /**
     * @property {Object} statusMap
     * status per attendee
     */
    statusMap: null,

    /**
     * get detailed info (HTML) grouped by attendee
     *
     * @param {Store} attendees
     */
    getInfoByAttendee: function(attendees, event) {
        var _ = window.lodash,
            html = '<div class="cal-conflict-eventinfos">';

        _.each(_.get(attendees, 'data.items', []), _.bind(function(attendee) {
            if (! this.byAttendee[attendee.getCompoundId(true)]) return;

            html += ['<div class="cal-conflict-attendername ', attendee.getIconCls(), '">',
                attendee.getTitle(), '</div>'].join('');

            html += this.getInfoForAttendee(attendee, event);
        }, this));

        return html + '</div>';
    },

    /**
     * get detailed info (HTML) for a single attendee
     *
     * @param {Record} attendee
     */
    getInfoForAttendee: function(attendee, event) {
        var _ = window.lodash,
            eventInfos = [];

        _.each(_.get(this.byAttendee, attendee.getCompoundId(true), []), _.bind(function(fbInfo) {
            var format = 'H:i';
            var eventInfo;
            var dateFormat = Ext.form.DateField.prototype.format;
            if (event.get('dtstart').format(dateFormat) != event.get('dtend').format(dateFormat) ||
                Date.parseDate(fbInfo.dtstart, Date.patterns.ISO8601Long).format(dateFormat) != Date.parseDate(fbInfo.dtend, Date.patterns.ISO8601Long).format(dateFormat))
            {
                eventInfo = Date.parseDate(fbInfo.dtstart, Date.patterns.ISO8601Long).format(dateFormat + ' ' + format) + ' - ' + Date.parseDate(fbInfo.dtend, Date.patterns.ISO8601Long).format(dateFormat + ' ' + format);
            } else {
                eventInfo = Date.parseDate(fbInfo.dtstart, Date.patterns.ISO8601Long).format(dateFormat + ' ' + format) + ' - ' + Date.parseDate(fbInfo.dtend, Date.patterns.ISO8601Long).format(format);
            }
            if (fbInfo.event && fbInfo.event.summary) {
                eventInfo += ' : ' + Ext.util.Format.htmlEncode(fbInfo.event.summary);
            }
            if (fbInfo.type == 'BUSY_UNAVAILABLE') {
                eventInfo += '<span class="cal-conflict-eventinfos-unavailable">' + this.app.i18n._('Unavailable') + '</span>';
            }
            eventInfos.push(eventInfo);

        }, this));

        return '<div class="cal-conflict-eventinfos">' + eventInfos.join(', <br />') + '</div>';
    },

    /**
     * get short info (HTML with hover) for a single attendee
     *
     * @param {Record} attendee
     * @param {Record} event
     * @return {Number} one of Tine.Calendar.FreeBusyInfo.states
     */
    getStateOfAttendee: function(attendee, event) {
        var _ = window.lodash,
            id = attendee.getCompoundId(true),
            stateId = _.get(this.statusMap, id, 0),
            state = _.invert(Tine.Calendar.FreeBusyInfo.states)[stateId],
            cls = _.lowerCase(_.replace(state, /^BUSY_/, '')),
            info = Ext.util.Format.htmlEncode(this.getInfoForAttendee(attendee, event)),
            qtip = stateId ? ('ext:qtip="' + info + '" ') : '';

        return ['<div class="cal-fbinfo-state cal-fbinfo-state-', cls, '"',
            ' tine:calendar-event-id="', event.get('id'), '" ',
            ' tine:calendar-freebusy-state-id="', stateId, '" ',
            qtip, ' ></div>'].join('');
    },

    /**
     * get max state of all attendee
     *
     * @return {Number} one of Tine.Calendar.FreeBusyInfo.states
     */
    getStateOfAllAttendees: function() {
        var _ = window.lodash;

        return _.reduce(this.statusMap, function(result, value, key) {
            return Math.max(result, value);
        }, 0);
    }
};
