/*!
 * Expresso Lite
 * Widget to render a sequence of events with details.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2015-2016 Serpro (http://www.serpro.gov.br)
 */

define([
    'common-js/jQuery',
    'common-js/App',
    'common-js/ContextMenu',
    'calendar/DateCalc'
],
function($, App, ContextMenu, DateCalc) {
App.loadCss('calendar/WidgetEvents.css');
return function(options) {
    var userOpts = $.extend({
        events: null, // Events cache object
        $elem: null // jQuery object for the target DIV
    }, options);

    var THIS        = this;
    var $panel      = null;
    var onRemovedCB = $.noop; // user callback

    THIS.load = function() {
        return $('#Events_template').length ? // load once
            $.Deferred().resolve().promise() :
            App.loadTemplate('WidgetEvents.html');
    };

    THIS.clear = function() {
        $panel.find('.Events_dayTitle').text('');
        $panel.find('.Events_canvas .Events_unit').remove();
        return THIS;
    };

    THIS.render = function(eventsOfDay, eventClicked) {
        var defer = $.Deferred();
        if ($panel === null) {
            $panel = $('#Events_template .Events_panel').clone();
            $panel.appendTo(userOpts.$elem);
            _SetEvents();
        }
        THIS.clear();
        _FormatHeader(eventsOfDay);
        var units = [];
        for (var i = 0; i < eventsOfDay.length; ++i) {
            units.push(_BuildUnit(eventsOfDay[i]));
        }
        $panel.find('.Events_canvas').append(units);
        if (eventClicked !== undefined) {
            var evClk = eventClicked.isEcho ? eventClicked.origEvent : eventClicked;
            for (var i = 0; i < units.length; ++i) {
                var evDay = eventsOfDay[i].isEcho ? eventsOfDay[i].origEvent : eventsOfDay[i];
                if (evDay.id !== evClk.id) {
                    units[i].find('.Events_content')
                        .hide(); // if there's a clicked event, all others will be hidden
                }
            }
        }
        $panel.velocity('fadeIn', {
            duration: 250,
            complete: function() {
                defer.resolve();
            }
        });
        return defer.promise();
    };

    THIS.onRemoved = function(callback) {
        onRemovedCB = callback; // onRemoved(event)
        return THIS;
    };

    function _SetEvents() {
        $panel.on('click', '.Events_showPeople', function() {
            var $ppl = $(this).parent().nextAll('.Events_peopleList');
            $ppl.velocity($ppl.is(':visible') ? 'slideUp' : 'slideDown', { duration:200 });
        });

        $panel.on('click', '.Events_topContainer', function() {
            var $evContent = $(this).next();
            $evContent.velocity($evContent.is(':visible') ? 'slideUp' : 'slideDown', {
                duration: 200,
                complete: function() {
                    $evContent.find('.Events_peopleList').hide();
                }
            });
        });
    }

    function _FormatHeader(events) {
        var evCount = events.length > 1 ? ' ('+events.length+')' : '';
        $panel.find('.Events_dayTitle').text(
            DateCalc.makeWeekDayMonthYearStr(events[0].from) + evCount
        );
    }

    function _BuildUnit(event) {
        var $unit = $('#Events_template .Events_unit').clone();
        var objEv = event.isEcho ? event.origEvent : event;
        $unit.find('.Events_summary').css('color', objEv.color);
        if (!objEv.wholeDay) {
            $unit.find('.Events_summary .Events_time').text(DateCalc.makeHourMinuteStr(event.from));
        }
        $unit.find('.Events_summary .Events_text').text(objEv.summary);
        $unit.find('.Events_summary .Events_userFlag').append(_BuildConfirmationIcon(objEv));
        if (event.isEcho) {
            $unit.find('.Events_summary')
                .find('.Events_time,.Events_text,.Events_userFlag')
                .addClass('Events_headerEcho');
        }

        if (objEv.description) {
            $unit.find('.Events_description .Events_text').html(objEv.description.replace(/\n/g, '<br/>'));
        } else {
            $unit.find('.Events_description .Events_text').append($('.Events_noDescription').clone());
        }

        $unit.find('.Events_when .Events_text').text(
            DateCalc.makeHourMinuteStr(event.from) +
            ' - ' +
            DateCalc.makeHourMinuteStr(objEv.until)
        );

        if (objEv.location) {
            $unit.find('.Events_location .Events_text').text(objEv.location);
        } else {
            $unit.find('.Events_location .Events_text').append($('.Events_noLocation').clone());
        }

        $unit.find('.Events_organizer .Events_text').text(objEv.organizer.name);
        $unit.find('.Events_personOrg').text(_FormatUserOrg(objEv.organizer));

        var $btnShowPeople = $unit.find('.Events_people .Events_showPeople');
        $btnShowPeople.val($btnShowPeople.val() +' ('+objEv.attendees.length+')');

        $unit.find('.Events_people .Events_peopleList').append(_BuildPeople(objEv)).hide();
        $unit.data('event', event);

        var menu = new ContextMenu({ $btn:$unit.find('.Events_dropdown') });
        $unit.data('dropdown', menu);
        _RefreshDropdown($unit);

        return $unit;
    }

    function _RefreshDropdown($unit) {
        var menu = $unit.data('dropdown');
        var ev = $unit.data('event');
        menu.purge();

        menu.addOption('Apagar', function() { _Remove($unit); });
        menu.addHeader('Assinalar resposta...');

        if (ev.confirmation !== 'ACCEPTED') {
            menu.addOption('Confirmar participação', function() { _SetConfirmation($unit, 'ACCEPTED'); });
        }
        if (ev.confirmation !== 'TENTATIVE') {
            menu.addOption('Tentar comparecer', function() { _SetConfirmation($unit, 'TENTATIVE'); });
        }
        if (ev.confirmation !== 'DECLINED') {
            menu.addOption('Rejeitar', function() { _SetConfirmation($unit, 'DECLINED'); });
        }
        if (ev.confirmation !== 'NEEDS-ACTION') {
            menu.addOption('Desfazer', function() { _SetConfirmation($unit, 'NEEDS-ACTION'); });
        }
    }

    function _BuildPeople(event) {
        var $pps = [];
        for (var i = 0; i < event.attendees.length; ++i) {
            $pp = $('#Events_template .Events_person').clone();
            $pp.find('.Events_personFlag').append(_BuildConfirmationIcon(event.attendees[i]));
            $pp.find('.Events_personName').text(event.attendees[i].name);
            $pp.find('.Events_personOrg').text(_FormatUserOrg(event.attendees[i]));
            $pps.push($pp);
        }
        return $pps;
    }

    function _BuildConfirmationIcon(attendee) {
        var icoSt = null;

        switch (attendee.confirmation) {
            case 'NEEDS-ACTION': icoSt = '.Events_icoWaiting'; break;
            case 'ACCEPTED':     icoSt = '.Events_icoAccepted'; break;
            case 'DECLINED':     icoSt = '.Events_icoDeclined'; break;
            case 'TENTATIVE':    icoSt = '.Events_icoTentative';
        }

        return $('#Events_template '+icoSt).clone();
    }

    function _FormatUserOrg(user) {
        if (!user.orgUnit && !user.region) {
            return '';
        } else if (!user.orgUnit) {
            return user.region;
        } else if (!user.region) {
            return user.orgUnit;
        } else {
            return user.orgUnit+', '+user.region;
        }
    }

    function _SetConfirmation($unit, confirmation) {
        var event = $unit.data('event');

        if (DateCalc.isBeforeCurrentTime(event.from)) {
            window.alert('Não é possível alterar a confirmação deste evento, pois ele já ocorreu.');
        } else {
            event.confirmation = confirmation; // update object
            event.attendees[0].confirmation = confirmation; // user himself is always 1st in attendee list

            $unit.find('.Events_summary .Events_userFlag')
                .empty()
                .append($('#Events_template .Events_throbber').clone());

            userOpts.events.setConfirmation(event.id, confirmation).done(function() {
                _RefreshDropdown($unit);
                $unit.find('.Events_summary .Events_userFlag, .Events_person .Events_personFlag:first')
                    .empty()
                    .append(_BuildConfirmationIcon(event)); // update confirmation icons
            });
        }
    }

    function _Remove($unit) {
        var event = $unit.data('event');
        if (window.confirm('Deseja excluir o evento\n"'+event.summary+'"?')) {
            $unit.find('.Events_dropdown').remove();
            $('#Events_template .Events_throbber').clone()
                .appendTo($unit.find('.Events_summary'));
            userOpts.events.remove(event.id).done(function() {
                onRemovedCB(event);
            });
        }
    }
};
});
