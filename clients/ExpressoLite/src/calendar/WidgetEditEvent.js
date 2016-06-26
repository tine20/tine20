/*!
 * Expresso Lite
 * Widget to render the edit event fields.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */

define(['jquery',
    'common-js/App',
    'common-js/Dialog',
    'common-js/TextBadges',
    'common-js/ContactsAutocomplete',
    'calendar/DateCalc'
],
function($, App, Dialog, TextBadges, ContactsAutocomplete, DateCalc) {
App.LoadCss('calendar/WidgetEditEvent.css');
return function(options) {
    var userOpts = $.extend({
    }, options);

    var THIS           = this;
    var $tpl           = null; // jQuery object with our HTML template
    var popup          = null; // Dialog object, created on show()
    var txtBadges      = null; // TextBadges object
    var autocomp       = null; // ContactsAutocomplete object
    var curCalendar    = null; // calendar object to save event into
    var isSaving       = false; // a "save" async request is running
    var onEventSavedCB = $.noop;

    function _NameFromAddr(addr) {
        var name = addr.substr(0, addr.indexOf('@')).toLowerCase();
        var parts = name.split(/[\.-]+/);
        for (var i = 0; i < parts.length; ++i) {
            parts[i] = parts[i][0].toUpperCase() + parts[i].slice(1);
        }
        return parts.join(' ');
    }

    function _GetFieldValues() {
        var vals = {
            calendarId: curCalendar.id,
            title: $tpl.find('.EditEvent_title').val(),
            peopleEmails: txtBadges.getBadgeValues().join(','),
            location: $tpl.find('.EditEvent_location').val(),
            isWholeDay: $tpl.find('.EditEvent_wholeDay > :checkbox').prop('checked') ? 1 : 0,
            isBlocking: $tpl.find('.EditEvent_noBlock > :checkbox').prop('checked') ? 0 : 1,
            notifyPeople: $tpl.find('.EditEvent_notifyPeople > :checkbox').prop('checked') ? 1 : 0,
            ymdStart: $tpl.find('.EditEvent_ymdStart').val(),
            hmStart: $tpl.find('.EditEvent_hmStart').val(),
            ymdEnd: $tpl.find('.EditEvent_ymdEnd').val(),
            hmEnd: $tpl.find('.EditEvent_hmEnd').val(),
            description: $tpl.find('.EditEvent_description').val()
        };

        vals.dtStart = vals.ymdStart+' ' + // Tine format datetime: '2016-02-29 06:30:00'
            (vals.isWholeDay ? '00:00' : vals.hmStart) +
            ':00';
        vals.dtEnd = vals.ymdEnd+' ' +
            (vals.isWholeDay ? '23:59' : vals.hmEnd) +
            ':00';

        return vals;
    }

    function _ValidateFieldValues(fields) {
        if (!$.trim(fields.title).length) {
            alert('É necessário dar um título ao evento.');
            $tpl.find('.EditEvent_title').focus();
            return false;
        }

        function goodYearMonthDay(ymd, what, objSuffix) { // temporary: should go away with a decent datetime widget
            if (ymd.match(/\d{4}-\d{2}-\d{2}/) === null) {
                alert('Data de '+what+' possui formato inválido.\n' +
                    'O formato aceito é: 2000-12-31');
                $tpl.find('.EditEvent_'+objSuffix).focus();
                return false;
            }
            return true;
        }
        function goodHourMinute(hm, what, objSuffix) {
            if (hm.match(/\d{2}:\d{2}/) === null) {
                alert('Hora de '+what+' possui formato inválido.\n' +
                    'O formato aceito é: 06:30');
                $tpl.find('.EditEvent_'+objSuffix).focus();
                return false;
            }
            return true;
        }

        if (!goodYearMonthDay(fields.ymdStart, 'início', 'ymdStart') ||
            !goodYearMonthDay(fields.ymdEnd, 'término', 'ymdEnd') ||
            (!fields.isWholeDay && !goodHourMinute(fields.hmStart, 'início', 'hmStart')) ||
            (!fields.isWholeDay && !goodHourMinute(fields.hmEnd, 'término', 'hmEnd')) )
        {
            return false;
        }

        if ((fields.isWholeDay && DateCalc.createFromYmd(fields.ymdStart) > DateCalc.createFromYmd(fields.ymdEnd)) ||
            (!fields.isWholeDay && DateCalc.strToDate(fields.dtStart) >= DateCalc.strToDate(fields.dtEnd)) )
        {
            alert('A data de término deve ser posterior à data de início.');
            $tpl.find('.EditEvent_ymdEnd').focus();
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

        $tpl.find('.EditEvent_wholeDay > :checkbox').on('change', function() {
            var isWholeDay = $(this).prop('checked');
            $tpl.find('.EditEvent_hmStart').prev().toggle(!isWholeDay);
            $tpl.find('.EditEvent_hmStart').toggle(!isWholeDay);
            $tpl.find('.EditEvent_hmEnd').prev().toggle(!isWholeDay);
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
                isSaving = true;
                popup.removeCloseButton();
                popup.setCaption('Salvando evento...');
                $tpl.empty();
                $tpl.append($('#EditEvent_template .EditEvent_savingThrobber').clone());

                App.Post('saveEvent',
                    fields
                ).fail(function(resp) {
                    window.alert('Erro ao salvar o evento.\n' +
                        resp.responseText);
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
            App.LoadTemplate('WidgetEditEvent.html')
        ).done(function() {
            $.when(
                Dialog.Load(),
                TextBadges.Load(),
                ContactsAutocomplete.Load()
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
