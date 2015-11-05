/*!
 * Expresso Lite
 * Popup for search and autocomplete email addresses.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2015 Serpro (http://www.serpro.gov.br)
 */

define(['jquery',
    'common-js/App',
    'mail/Contacts'
],
function($, App, Contacts) {
App.LoadCss('mail/WidgetSearchAddr.css');
var WidgetSearchAddr = function(options) {
    var userOpts = $.extend({
        $elem: null, // jQuery object for the target DIV
        maxVisibleContacts: 10
    }, options);

    var THIS      = this;
    var $txtbox   = userOpts.$elem; // a textarea element
    var token     = '';
    var onClickCB = null; // user callback

    function _InsertNamesIntoList(contacts) {
        var $list = $('#SearchAddr_list');
        for (var c = 0; c < contacts.length; ++c) {
            var addr = (contacts[c].emails.length > 1) ?
                (contacts[c].emails.length)+' emails' : // more than 1 email, probably a group
                contacts[c].emails[0];
            var $opt = $(document.createElement('option'))
                .text(contacts[c].name+' ('+addr+')')
                .data('contact', contacts[c]) // keep contact object into entry
                .appendTo($list);
        }
    }

    function _InsertMoreNamesIntoList(newContacts) {
        if (!newContacts.length) return;
        var $list = $('#SearchAddr_list');
        var people = [];
        $list.children('option').each(function(idx, opt) {
            people.push($(opt).data('contact')); // array with currently displayed contacts
        });
        for (var c = 0; c < newContacts.length; ++c) {
            var alreadyExists = false;
            for (var p = 0; p < people.length; ++p) {
                if (people[p].emails.length !== 1) continue; // don't count groups
                if (people[p].emails[0] === newContacts[c].email) {
                    alreadyExists = true;
                    break;
                }
            }
            if (!alreadyExists)
                people.push({ name:newContacts[c].name, emails:[newContacts[c].email] }); // append new one
        }
        people.sort(function(a, b) { return a.name.localeCompare(b.name); });
        $list.empty();
        _InsertNamesIntoList(people);
        if (people.length > 2)
            $list.attr('size', Math.min(people.length, userOpts.maxVisibleContacts));
    }

    function _BuildPopup(numContacts) {
        var size = (numContacts <= 2) ? 2 :
            Math.min(numContacts, userOpts.maxVisibleContacts);
        var $popup = $('#SearchAddr_popup');
        $popup.find('#SearchAddr_list').attr('size', size);
        $popup.css({
            left: ($txtbox.offset().left + 3)+'px',
            top: ($txtbox.offset().top + $txtbox.outerHeight() - 2)+'px',
            width: $txtbox.width()+'px'
        });
        $popup.appendTo(document.body); // removed from template section, becomes visible
        return $popup;
    }

    THIS.close = function() {
        $('#SearchAddr_list').off('.SearchAddr').empty();
        $('#SearchAddr_more > a').off('.SearchAddr');
        $('#SearchAddr_popup').appendTo('#SearchAddr_template'); // becomes hidden
        return THIS;
    };

    THIS.processKey = function(key) {
        if (key === 0) {
            // dummy
        } else if (key === 27) { // ESC
            THIS.close();
        } else if (key === 13) { // enter
            var opt = $('#SearchAddr_list > option:selected');
            if (opt.length) {
                opt.trigger('mousedown');
                THIS.close();
            }
        } else if (key === 38) { // up arrow
            var $listbox = $('#SearchAddr_list');
            var $selopt = $listbox.children('option:selected');
            !$selopt.length ?
                $listbox.children('option:last').prop('selected', true) :
                $selopt.prev().prop('selected', true);
        } else if (key === 40) { // down arrow
            var $listbox = $('#SearchAddr_list');
            var $selopt = $listbox.children('option:selected');
            !$selopt.length ?
                $listbox.children('option:first').prop('selected', true) :
                $selopt.next().prop('selected', true);
        }
    };

    THIS.onClick = function(callback) {
        onClickCB = callback; // onClick(token, contact)
        return THIS;
    };

    (function _Ctor() {
        THIS.close(); // there can be only one
        token = $txtbox.val(); // string to be searched among contacts
        var lastComma = token.lastIndexOf(',');
        if (lastComma > 0) {
            token = $.trim(token.substr(lastComma + 1));
        }
        if (token.length >= 2) {
            var contacts = Contacts.searchByToken(token);
            _BuildPopup(contacts.length);
            _InsertNamesIntoList(contacts);
        }
    })();

    $('#SearchAddr_list').on('mousedown.SearchAddr', 'option', function(ev) {
        if (onClickCB !== null) {
            var $opt = $(this);
            onClickCB(token, $opt.data('contact')); // invoke user callback
        }
    });

    $('#SearchAddr_more > a').on('mousedown.SearchAddr', function(ev) {
        ev.preventDefault();
        ev.stopImmediatePropagation();
        $('#SearchAddr_more > a').css('display', 'none');
        $('#SearchAddr_more').append($('#icons .throbber').clone());

        App.Post('searchContactsByToken', { token:token })
        .always(function() {
            $('#SearchAddr_more').find('.throbber').remove();
            $('#SearchAddr_more > a').css('display', ''); // restore from "display:none"
        })
        .fail(function(resp) {
            window.alert('Erro na pesquisa de contatos no catálogo do Expresso.\n' + resp.responseText);
        }).done(function(contacts) {
            if (contacts.length > 45) {
                _InsertMoreNamesIntoList([]);
                window.alert('Muitos contatos com "'+token+'" foram encontrados.\nUse um termo mais específico.');
            } else {
                _InsertMoreNamesIntoList(contacts);
            }
        });
    });
};

WidgetSearchAddr.Load = function() {
    // Static method, since this class can be instantied ad-hoc.
    return App.LoadTemplate('WidgetSearchAddr.html');
};

return WidgetSearchAddr;
});
