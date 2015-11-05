/*!
 * Expresso Lite
 * Handles all event-related operations.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

define(['jquery',
    'common-js/App',
    'calendar/DateCalc'
],
function($, App, DateCalc) {
return function() {
    var THIS     = this;
    var allWeeks = { }; // buckets of weeks, hash is a string like '20150805'

    THIS.loadWeek = function(start) {
        // Assumes 'start' as sunday of week.
        if (start === null) {
            start = DateCalc.sundayOfWeek(DateCalc.today());
        }
        return THIS.loadEvents(start, DateCalc.nextWeek(start));
    };

    THIS.loadMonth = function(start) {
        // Assumes 'start' as 1st day of month.
        if (start === null) {
            start = DateCalc.firstOfMonth(DateCalc.today());
        }

        var pastEndOfWeek = DateCalc.nextMonth(start);
        if (!DateCalc.monthEndsInSaturday(start)) {
            pastEndOfWeek = DateCalc.nextWeek(pastEndOfWeek);
        }

        return THIS.loadEvents(DateCalc.sundayOfWeek(start), pastEndOfWeek);
    };

    THIS.loadEvents = function(from, pastUntil) {
        // 'pastUntil' is 1st day after the period we'll want to retrieve.
        var defer = $.Deferred();
        var period = _FilterPeriodToRetrieve(from, pastUntil);
        _ReserveCache(from, pastUntil);
        if (period === null) {
            defer.resolve(); // period already cached
        } else {
            App.Post('searchEvents', {
                from: DateCalc.makeQueryStr(period.from),
                until: DateCalc.makeQueryStr(period.pastUntil)
            }).fail(function(resp) {
                window.alert('Erro na consulta dos eventos.\n' +
                    resp.responseText);
                defer.reject();
            }).done(function(resp) {
                _StoreEvents(resp.events);
                defer.resolve();
            });
        }
        return defer.promise();
    };

    THIS.clearWeekCache = function(start) {
        // Assumes 'start' as sunday of week.
        delete allWeeks[_HashFromDate(start)];
        return THIS;
    };

    THIS.clearMonthCache = function(start) {
        // Assumes 'start' as 1st day of month.
        delete allWeeks[_HashFromDate(DateCalc.sundayOfWeek(start))];
        var hashYearMonth = start.getFullYear() + DateCalc.pad2(start.getMonth());
        for (var hashKey in allWeeks) {
            if (hashKey.substr(0, 6) === hashYearMonth) {
                delete allWeeks[hashKey];
            }
        }
        return THIS;
    };

    THIS.inDay = function(when) {
        var events = [];
        var sunday = DateCalc.sundayOfWeek(when);
        var week = allWeeks[_HashFromDate(sunday)];
        if (week !== undefined) {
            for (var i = 0; i < week.length; ++i) {
                if (DateCalc.isSameDay(when, week[i].from)) {
                    events.push(week[i]);
                }
            }
        }
        return events;
    };

    THIS.setConfirmation = function(eventId, confirmation) {
        var defer = $.Deferred();
        App.Post('setEventConfirmation', {
            id: eventId,
            confirmation: confirmation
        }).fail(function(resp) {
            window.alert('Erro na consulta dos eventos.\n' +
                resp.responseText);
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

    function _FilterPeriodToRetrieve(from, pastUntil) {
        var weeksToRetrieve = [];
        var sunday = DateCalc.sundayOfWeek(from);
        while (sunday < pastUntil) {
            if (allWeeks[_HashFromDate(sunday)] === undefined) {
                weeksToRetrieve.push(sunday);
            }
            sunday = DateCalc.nextWeek(sunday);
        }

        if (!weeksToRetrieve.length) {
            return null; // period already cached, nothing to retrieve
        }

        return { // a single period with smallest amount of week blocks between 'from' and 'until'
            from: weeksToRetrieve[0],
            pastUntil: DateCalc.nextWeek(weeksToRetrieve[weeksToRetrieve.length - 1])
        };
    }

    function _ReserveCache(from, pastUntil) {
        var sunday = DateCalc.sundayOfWeek(from);
        do {
            var weekHash = _HashFromDate(sunday);
            if (allWeeks[weekHash] === undefined) {
                allWeeks[weekHash] = []; // create new week bucket
            }
            sunday = DateCalc.nextWeek(sunday);
        } while (sunday < pastUntil);
    }

    function _StoreEvents(rawEvents) {
        for (var i = 0; i < rawEvents.length; ++i) { // parse strings to Date objects
            rawEvents[i].from = DateCalc.strToDate(rawEvents[i].from);
            rawEvents[i].until = DateCalc.strToDate(rawEvents[i].until);
            _OrganizeAttendees(rawEvents[i]);
        }
        rawEvents.sort(function(a, b) { // oldest first
            return a.from - b.from;
        });
        for (var i = 0; i < rawEvents.length; ++i) {
            var sunday = DateCalc.sundayOfWeek(rawEvents[i].from);
            var weekHash = _HashFromDate(sunday);
            if (!_EventAlreadyAddedToWeek(weekHash, rawEvents[i])) {
                allWeeks[weekHash].push(rawEvents[i]);
            }
        }
    }

    function _OrganizeAttendees(event) {
        event.attendees.sort(function(a, b) { // alphabetically
            return a.name.localeCompare(b.name);
        });
        var ourMail = App.GetUserInfo('mailAddress');
        var curUserAttendee = null;
        var bucket = {
            'NEEDS-ACTION': [],
            'ACCEPTED': [],
            'DECLINED': [],
            'TENTATIVE': []
        };
        for (var i = 0; i < event.attendees.length; ++i) {
            if (event.attendees[i].email === ourMail) {
                curUserAttendee = event.attendees[i];
            } else {
                bucket[ event.attendees[i].confirmation ].push(event.attendees[i]);
            }
        }

        // Attendees are sorted by confirmation status, name; user himself is always first.
        event.attendees = [ curUserAttendee ].concat(
            bucket['ACCEPTED'],
            bucket['TENTATIVE'],
            bucket['NEEDS-ACTION'],
            bucket['DECLINED']
        );
    }

    function _EventAlreadyAddedToWeek(weekHash, event) {
        var weekBucket = allWeeks[weekHash];
        for (var i = 0; i < weekBucket.length; ++i) {
            if (weekBucket[i].id === event.id) {
                return true;
            }
        }
        return false;
    }
};
});
