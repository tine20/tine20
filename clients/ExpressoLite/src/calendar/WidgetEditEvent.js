/*!
 * Expresso Lite
 * Widget to render the edit event fields.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */

define([
    'common-js/jQuery',
    'common-js/App',
    'common-js/Dialog',
    'common-js/TextBadges',
    'common-js/ContactsAutocomplete',
    'calendar/DateCalc',
    'calendar/WidgetChooseDate'
],
function($, App, Dialog, TextBadges, ContactsAutocomplete, DateCalc, WidgetChooseDate) {
App.loadCss('calendar/WidgetEditEvent.css');
return function(options) {
    var userOpts = $.extend({
    }, options);

    var THIS            = this;
    var $tpl            = null; // jQuery object with our HTML template
    var popup           = null; // Dialog object, created on show()
    var txtBadges       = null; // TextBadges object
    var autocomp        = null; // ContactsAutocomplete object
    var datePickerStart = null; // WidgetChooseDate objects
    var datePickerEnd   = null;
    var curCalendar     = null; // calendar object to save event into
    var isSaving        = false; // a "save" async request is running
    var onEventSavedCB  = $.noop;

    function _NameFromAddr(addr) {
        var name = addr.substr(0, addr.indexOf('@')).toLowerCase();
        var parts = name.split(/[\.-]+/);
        for (var i = 0; i < parts.length; ++i) {
            parts[i] = parts[i][0].toUpperCase() + parts[i].slice(1);
        }
        return parts.join(' ');
    }

    function _GetFieldValues() {
        function tineDateTime(datePicker, suffix) { // Tine format datetime: '2016-02-29 06:30:00'
            var dt = datePicker.getCurDate();
            if ($tpl.find('.EditEvent_wholeDay > :checkbox').prop('checked')) {
                dt.setHours(suffix === 'Start' ? 0 : 23);
                dt.setMinutes(suffix === 'Start' ? 0 : 59);
            } else {
                dt.setHours($tpl.find('.EditEvent_hour'+suffix+' :selected').text());
                dt.setMinutes($tpl.find('.EditEvent_min'+suffix+' :selected').text());
            }
            return dt; // DateTime object
        }

        var vals = {
            calendarId: curCalendar.id,
            title: $tpl.find('.EditEvent_title').val(),
            peopleEmails: txtBadges.getBadgeValues().join(','),
            location: $tpl.find('.EditEvent_location').val(),
            isWholeDay: $tpl.find('.EditEvent_wholeDay > :checkbox').prop('checked') ? 1 : 0,
            isBlocking: $tpl.find('.EditEvent_noBlock > :checkbox').prop('checked') ? 0 : 1,
            notifyPeople: $tpl.find('.EditEvent_notifyPeople > :checkbox').prop('checked') ? 1 : 0,
            dtStart: tineDateTime(datePickerStart, 'Start'),
            dtEnd: tineDateTime(datePickerEnd, 'End'),
            description: $tpl.find('.EditEvent_description').val()
        };

        return vals;
    }

    function _ValidateFieldValues(fields) {
        if (!$.trim(fields.title).length) {
            alert('É necessário dar um título ao evento.');
            $tpl.find('.EditEvent_title').focus();
            return false;
        }

        if (fields.dtStart > fields.dtEnd) {
            alert('A data e/ou hora de término deve ser posterior à data e à hora de início.');
            return false;
        }

        return true;
    }

    function _SetEvents() {
        popup.onUserClose(function() { // when user clicked X button
            popup.close();
        });

        popup.onClose(function() { // when dialog is being dismissed
            autocomp = null;
            txtBadges = null;
            popup = null;
            $tpl = null;
            curCalendar = null;
        });

        autocomp.onSelect(function(addrs) {
            for (var i = 0; i < addrs.length; ++i) {
                txtBadges.addBadge( // a new address was selected, make it a badge
                    _NameFromAddr(addrs[i]), addrs[i].toLowerCase() );
            }
        });

        autocomp.onBackspace(function() {
            txtBadges.removeLastBadge();
        });

        datePickerStart.onSelect(function(dt) {
            if (dt > datePickerEnd.getCurDate()) {
                datePickerEnd.setCurDate(dt);
            }
        });

        $tpl.find('.EditEvent_wholeDay > :checkbox').on('change', function() {
            var isWholeDay = $(this).prop('checked');
            $tpl.find('.EditEvent_hmStart').toggle(!isWholeDay);
            $tpl.find('.EditEvent_hmEnd').toggle(!isWholeDay);
        });

        $tpl.find('.EditEvent_save').on('click', function() {
            $(this).blur();

            if (txtBadges.getInputField().val().length) { // all addresses must be in badges
                window.alert('Endereço de email inválido.');
                txtBadges.getInputField().focus();
                return;
            }

            var fields = _GetFieldValues();
            if (_ValidateFieldValues(fields)) {
                fields.dtStart = DateCalc.makeQueryStrWithHourMinute(fields.dtStart); // from DateTime to string
                fields.dtEnd = DateCalc.makeQueryStrWithHourMinute(fields.dtEnd);

                isSaving = true;
                popup.removeCloseButton();
                popup.setCaption('Salvando evento...');
                $tpl.empty();
                $tpl.append($('#EditEvent_template .EditEvent_savingThrobber').clone());

                App.post('saveEvent',
                    fields
                ).fail(function(resp) {
                    App.errorMessage('Erro ao salvar o evento.', resp);
                }).always(function() {
                    isSaving = false;
                    $tpl.find('.EditEvent_savingThrobber').remove();
                    popup.close();
                }).done(function(resp) {
                    onEventSavedCB(); // invoke user callback
                });
            }
        });
    }

    THIS.load = function() {
        var defer = $.Deferred();
        ( $('#EditEvent_template').length ? // load once
            $.Deferred().resolve().promise() :
            App.loadTemplate('WidgetEditEvent.html')
        ).done(function() {
            $.when(
                Dialog.Load(),
                TextBadges.Load(),
                ContactsAutocomplete.Load(),
                WidgetChooseDate.Load()
            ).done(function() {
                defer.resolve();
            });
        });
        return defer.promise();
    };

    THIS.show = function(curCalendarObj) {
        var defer = $.Deferred();
        if ($tpl !== null) { // dialog is already open
            alert('Um evento já está sendo criado ou editado.');
            defer.reject();
        } else {
            curCalendar = curCalendarObj;
            $tpl = $('#EditEvent_template .EditEvent_panel').clone(); // create new HTML template object

            popup = new Dialog({ // create new modeless dialog object
                $elem: $tpl,
                caption: 'Criar evento',
                width: 500,
                height: 500,
                minWidth: 300,
                minHeight: 500,
                modal: false
            });

            txtBadges = new TextBadges({
                $target: $tpl.find('.EditEvent_people'),
                inputType: 'email'
            });

            autocomp = new ContactsAutocomplete({
                $txtField: txtBadges.getInputField(),
                $anchorElem: $tpl.find('.EditEvent_people'),
                $contentPanel: $tpl
            });

            datePickerStart = new WidgetChooseDate({
                $elem: $tpl.find('.EditEvent_ymdStart')
            });

            datePickerEnd = new WidgetChooseDate({
                $elem: $tpl.find('.EditEvent_ymdEnd')
            });

            for (var i = 6; i <= 21; ++i) {
                $tpl.find('.EditEvent_hourStart,.EditEvent_hourEnd').append(
                    $('<option>'+DateCalc.pad2(i)+'</option>'));
            }
            $tpl.find('.EditEvent_hourStart').val('08');
            $tpl.find('.EditEvent_hourEnd').val('09');
            ['00','15','30','45'].forEach(function(val) {
                $tpl.find('.EditEvent_minStart,.EditEvent_minEnd').append(
                    $('<option>'+val+'</option>'));
            });

            _SetEvents();

            popup.show().done(function() {
                $tpl.find('.EditEvent_notifyPeople > :checkbox').prop('checked', true);
                $tpl.find('.EditEvent_title').focus();
                defer.resolve();
            });
        }
        return defer.promise();
    };

    THIS.onEventSaved = function(callback) {
        onEventSavedCB = callback; // onEventSaved()
        return THIS;
    };
};
});
