/*!
 * Expresso Lite
 * Handles all event-related operations.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2015-2016 Serpro (http://www.serpro.gov.br)
 */

define([
    'common-js/jQuery',
    'common-js/App',
    'calendar/DateCalc'
],
function($, App, DateCalc) {
return function() {
    var THIS  = this;
    var cache = { };
    // Cache goes like this:
    //  cache = {
    //    47910: { // calendar ID; int
    //      20151029: [ // week ID; year, month & day as int (month is zero-based)
    //        event_object,
    //        event_object
    //      ],
    //      20151106: [
    //      ]
    //    }
    //  };

    THIS.loadWeek = function(calendarId, start) {
        // Assumes 'start' as sunday of week.
        if (start === null) {
            start = DateCalc.sundayOfWeek(DateCalc.today());
        }
        return THIS.loadEvents(calendarId,
            start, DateCalc.nextWeek(start));
    };

    THIS.loadMonth = function(calendarId, start) {
        // Assumes 'start' as 1st day of month.
        if (start === null) {
            start = DateCalc.firstOfMonth(DateCalc.today());
        }

        var pastEndOfWeek = DateCalc.nextMonth(start);
        if (!DateCalc.monthEndsInSaturday(start)) {
            pastEndOfWeek = DateCalc.nextWeek(pastEndOfWeek);
        }

        return THIS.loadEvents(calendarId,
            DateCalc.sundayOfWeek(start), pastEndOfWeek);
    };

    THIS.loadEvents = function(calendarId, from, pastUntil) {
        // 'pastUntil' is 1st day after the period we'll want to retrieve.
        var defer = $.Deferred();
        var period = _FilterPeriodToRetrieve(calendarId, from, pastUntil);
        _ReserveCache(calendarId, from, pastUntil);
        if (period === null) {
            defer.resolve(); // period already cached
        } else {
            App.post('searchEvents', {
                from: DateCalc.makeQueryStr(period.from),
                until: DateCalc.makeQueryStr(period.pastUntil),
                calendarId: calendarId
            }).fail(function(resp) {
                App.errorMessage('Erro na consulta dos eventos.', resp);
                THIS.clearMonthCache(calendarId, DateCalc.firstOfMonth(from));
                defer.reject();
            }).done(function(resp) {
                _StoreEvents(calendarId, resp.events);
                defer.resolve();
            });
        }
        return defer.promise();
    };

    THIS.clearWeekCache = function(calendarId, start) {
        // Assumes 'start' as sunday of week.
        if (cache[calendarId] !== undefined) {
            delete cache[calendarId][_HashFromDate(start)];
        }
        return THIS;
    };

    THIS.clearMonthCache = function(calendarId, start) {
        // Assumes 'start' as 1st day of month.
        if (cache[calendarId] !== undefined) {
            delete cache[calendarId][_HashFromDate(DateCalc.sundayOfWeek(start))];
            var hashYearMonth = start.getFullYear() + DateCalc.pad2(start.getMonth());
            for (var hashKey in cache[calendarId]) {
                if (hashKey.substr(0, 6) === hashYearMonth) {
                    delete cache[calendarId][hashKey];
                }
            }
        }
        return THIS;
    };

    THIS.inDay = function(calendarId, when) {
        var events = [];
        if (cache[calendarId] !== undefined) {
            var sunday = DateCalc.sundayOfWeek(when);
            var week = cache[calendarId][_HashFromDate(sunday)];
            if (week !== undefined) {
                for (var i = 0; i < week.length; ++i) {
                    if (DateCalc.isSameDay(when, week[i].from)) {
                        events.push(week[i]);
                    }
                }
            }
        }
        events.sort(function(a, b) { // non-echo first
            if (a.isEcho !== b.isEcho) {
                return a.isEcho ? 1 : -1;
            } else {
                return a.from.getDate() - b.from.getDate();
            }
        });
        return events;
    };

    THIS.setConfirmation = function(eventId, confirmation) {
        var defer = $.Deferred();
        App.post('setEventConfirmation', {
            id: eventId,
            confirmation: confirmation
        }).fail(function(resp) {
            App.errorMessage('Erro na consulta dos eventos.', resp);
            defer.reject();
        }).done(function(resp) {
            defer.resolve();
        });
        return defer.promise();
    };

    THIS.remove = function(eventId) {
        var defer = $.Deferred();
        App.post('deleteEvent', {
            id: eventId
        }).fail(function(resp) {
            App.errorMessage('Erro ao remover evento.', resp);
            defer.reject();
        }).done(function(resp) {
            defer.resolve();
        });
        return defer.promise();
    };

    function _HashFromDate(when) {
        return '' + when.getFullYear() +
            DateCalc.pad2(when.getMonth()) +
            DateCalc.pad2(when.getDate()); // string, '20150805'
    }

    function _FilterPeriodToRetrieve(calendarId, from, pastUntil) {
        var weeksToRetrieve = []; // IDs of weeks to be retrieved, the ones not cached yet
        var sunday = DateCalc.sundayOfWeek(from);
        while (sunday < pastUntil) {
            var weekNotCached = (cache[calendarId] === undefined) ||
                (cache[calendarId][_HashFromDate(sunday)] === undefined);
            if (weekNotCached) {
                weeksToRetrieve.push(sunday);
            }
            sunday = DateCalc.nextWeek(sunday); // advance to next week
        }

        if (!weeksToRetrieve.length) {
            return null; // period already cached, nothing to retrieve
        }

        return { // a single period with smallest amount of week blocks between 'from' and 'until'
            from: weeksToRetrieve[0],
            pastUntil: DateCalc.nextWeek(weeksToRetrieve[weeksToRetrieve.length - 1])
        };
    }

    function _ReserveCache(calendarId, from, pastUntil) {
        var sunday = DateCalc.sundayOfWeek(from);
        if (cache[calendarId] === undefined) {
            cache[calendarId] = { }; // create new calendar bucket
        }
        do {
            var weekHash = _HashFromDate(sunday);
            if (cache[calendarId][weekHash] === undefined) {
                cache[calendarId][weekHash] = []; // create new week bucket
            }
            sunday = DateCalc.nextWeek(sunday);
        } while (sunday < pastUntil);
    }

    function _StoreEvents(calendarId, rawEvents) {
        for (var i = 0; i < rawEvents.length; ++i) { // parse strings to Date objects
            rawEvents[i].from = DateCalc.strToDate(rawEvents[i].from);
            rawEvents[i].until = DateCalc.strToDate(rawEvents[i].until);
            if (rawEvents[i].attendees.length) {
                _OrganizeAttendees(rawEvents[i]);
            }
        }
        rawEvents.sort(function(a, b) { // oldest first
            return a.from - b.from;
        });
        for (var i = 0; i < rawEvents.length; ++i) {
            var sunday = DateCalc.sundayOfWeek(rawEvents[i].from);
            var weekHash = _HashFromDate(sunday);
            if (!_EventAlreadyAddedToWeek(calendarId, weekHash, rawEvents[i])) {
                cache[calendarId][weekHash].push(rawEvents[i]);
                _ExplodeEventMoreThanOneDay(calendarId, rawEvents[i]);
            }
        }
    }

    function _OrganizeAttendees(event) {
        event.attendees.sort(function(a, b) { // alphabetically
            return a.name.localeCompare(b.name);
        });
        var ourMail = App.getUserInfo('mailAddress');
        var curUserAttendee = null;
        var bucket = {
            'ACCEPTED': [],
            'TENTATIVE': [],
            'NEEDS-ACTION': [],
            'DECLINED': []
        };
        for (var i = 0; i < event.attendees.length; ++i) {
            if (event.attendees[i].email === ourMail) {
                curUserAttendee = event.attendees[i];
            } else {
                bucket[ event.attendees[i].confirmation ].push(event.attendees[i]);
            }
        }

        // Attendees are sorted by confirmation status, name.
        // User himself (if present) is always first.
        event.attendees = (curUserAttendee !== null) ? [ curUserAttendee ] : [];
        event.attendees = event.attendees.concat(
            bucket['ACCEPTED'],
            bucket['TENTATIVE'],
            bucket['NEEDS-ACTION'],
            bucket['DECLINED']
        );
    }

    function _EventAlreadyAddedToWeek(calendarId, weekHash, event) {
        if (cache[calendarId] === undefined || cache[calendarId][weekHash] === undefined) {
            var sunday = DateCalc.sundayOfWeek(event.from);
            _ReserveCache(calendarId, sunday, DateCalc.nextWeek(sunday));
        }
        var weekBucket = cache[calendarId][weekHash];
        for (var i = 0; i < weekBucket.length; ++i) {
            if (weekBucket[i].id === event.id) {
                return true;
            }
        }
        return false;
    }

    function _ExplodeEventMoreThanOneDay(calendarId, event) {
        // If an event spans over more than one single day, other "echo"
        // events will be created on each of these days.
        event.isEcho = false;
        if (event.from.getDate() !== event.until.getDate()) { // event spans over more than 1 day?
            var nextDay = new Date(event.from);
            do {
                nextDay.setDate(nextDay.getDate() + 1); // advance to next day
                var echo = { // create a new echo event object, which is a cheap object
                    isEcho: true,
                    from: new Date(nextDay),
                    origEvent: event
                };
                var sunday = DateCalc.sundayOfWeek(nextDay);
                _ReserveCache(calendarId, sunday, sunday);
                cache[calendarId][_HashFromDate(sunday)].push(echo);
            } while (nextDay.getDate() !== event.until.getDate());
        }
    }
};
});
