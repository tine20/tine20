/*!
 * Expresso Lite
 * Popup to autocomplete contacts when searching.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

define(['jquery',
    'common-js/App',
    'common-js/Contacts',
    'common-js/UrlStack'
],
function($, App, Contacts, UrlStack) {
App.LoadCss('common-js/ContactsAutocomplete.css');
var ContactsAutocomplete = function(options) {
    var userOpts = $.extend({
        $txtField: null, // field to which the popup grabs the text events
        $anchorElem: null, // element where the popup will be anchored to
        $contentPanel: null, // parent DIV to which the text field belongs, may not be immediate parent
        defaultHeight: 320
    }, options);

    var THIS          = this;
    var $tpl          = null; // ContactsAutocomplete_frame DIV
    var isMouseDown   = false;
    var isCacheSearch = true; // current search is within cached contacts?
    var onSelectCB    = $.noop; // user callbacks
    var onBackspaceCB = $.noop;

    (function _Constructor() {
        $tpl = $('#ContactsAutocomplete_template > .ContactsAutocomplete_frame').clone();
        _SetEvents();
    })();

    function _SetEvents() {
        userOpts.$txtField.on('blur', function() {
            if (!isMouseDown) {
                _MakeBadgeIfTextLooksLikeEmail();
                THIS.hide();
            }
        });

        userOpts.$txtField.on('keypress', function(ev) {
            if (ev.keyCode === 13) { // enter, new line disabled
                ev.stopImmediatePropagation();
                return false;
            }
        });

        userOpts.$txtField.on('keydown', function(ev) {
            if(ev.which === 8) { // backspace
                if (!userOpts.$txtField.val().length) {
                    ev.stopImmediatePropagation();
                    onBackspaceCB(); // invoke user callback
                    return false;
                }
            } else if (ev.which === 9) { // tab
                _MakeBadgeIfTextLooksLikeEmail();
            }

            if ($tpl.is(':visible') && [0, 27, 13, 38, 40, 188, 59, 191].indexOf(ev.which) !== -1) { // esc, enter, up, dn, comma, semicolon (firefox & chrome)
                ev.stopImmediatePropagation();
                var $outp = $tpl.find('.ContactsAutocomplete_results');
                if (ev.which === 27) { // esc
                    THIS.hide();
                    userOpts.$txtField.focus();
                } else if ([13, 188, 59, 191].indexOf(ev.which) !== -1) { // enter, comma, semicolon (firefox & chrome)
                    var $curSel = $outp.find('.ContactsAutocomplete_oneResultSel');
                    if ($curSel.length) {
                        $curSel.trigger('click');
                    } else {
                        _MakeBadgeIfTextLooksLikeEmail();
                        THIS.hide();
                        userOpts.$txtField.focus();
                    }
                } else if ([40, 38].indexOf(ev.which) !== -1 && $outp.find('.ContactsAutocomplete_oneResult').length) { // dn, up
                    var $curSel = $outp.find('.ContactsAutocomplete_oneResultSel');
                    $curSel.removeClass('ContactsAutocomplete_oneResultSel'); // if any
                    if (ev.which === 40) { // down arrow
                        $curSel = ($curSel.length && !$curSel.is(':last-child')) ?
                            $curSel.next() :
                            $outp.find('.ContactsAutocomplete_oneResult:first');
                        var yBottom = $curSel.offset().top + $curSel.outerHeight() - $outp.offset().top;
                        if (yBottom > $outp.outerHeight() || $curSel.is(':first-child')) {
                            $outp.scrollTop($curSel.offset().top
                                - $outp.offset().top
                                + $outp.scrollTop()
                                - $outp.outerHeight() * .3);
                        }
                    } else if (ev.which === 38) { // up arrow
                        $curSel = ($curSel.length && !$curSel.is(':first-child')) ?
                            $curSel.prev() :
                            $outp.find('.ContactsAutocomplete_oneResult:last');
                        var selTop = $curSel.offset().top - $outp.offset().top;
                        if (selTop < 0 || $curSel.is(':last-child')) {
                            $outp.scrollTop(selTop
                                + $outp.scrollTop()
                                - $outp.outerHeight() * .4);
                        }
                    }
                    $curSel.addClass('ContactsAutocomplete_oneResultSel');
                }
                return false;
            } else if (ev.which === 27) {
                ev.preventDefault(); // on Firefox, ESC key weirdly puts back previous input value
            }
        });

        userOpts.$txtField.on('keyup', function(ev) {
            if ([0, 27, 13, 38, 40].indexOf(ev.which) !== -1) { // esc, enter, up, dn
                ev.stopImmediatePropagation();
            } else {
                var token = userOpts.$txtField.val();
                var $outp = $tpl.find('.ContactsAutocomplete_results');
                $outp.empty();
                if (token.length >= 2) {
                    var contacts = Contacts.searchByToken(token);
                    $outp.append(_FormatResults(contacts)).scrollTop(0);
                    _Show();
                } else {
                    THIS.hide();
                    userOpts.$txtField.focus();
                }
            }
        });

        $tpl.find('.ContactsAutocomplete_results').on('click', '.ContactsAutocomplete_oneResult', function() {
            onSelectCB($(this).data('contact').emails); // invoke user callback, send array
            $tpl.find('.ContactsAutocomplete_results').empty();
            userOpts.$txtField.val('');
            THIS.hide();
            userOpts.$txtField.focus();
        });

        $tpl.find('.ContactsAutocomplete_searchBeyond').on('click', function() {
            userOpts.$txtField.focus(); // if this button holds the focus, mobile keyboard flashes and alignment is lost
            var $btn = $(this);
            var $field = $tpl.find('.ContactsAutocomplete_field');
            var token = userOpts.$txtField.val();
            if (token.length >= 2) {
                var $outp = $tpl.find('.ContactsAutocomplete_results');
                var start = 0;
                $field.prop('disabled', true);
                $tpl.find('.ContactsAutocomplete_count').hide();
                $btn.hide();
                $tpl.find('.ContactsAutocomplete_throbber').show();

                if (!isCacheSearch) { // subsequent "load more" contacts, requesting more pages
                    start = $outp.find('.ContactsAutocomplete_oneResult').length;
                } else { // first "load more" request
                    $outp.empty();
                }

                App.Post('searchContactsByToken', { token:token, start:start })
                .always(function() {
                    $field.prop('disabled', false);
                    $tpl.find('.ContactsAutocomplete_count').show();
                    $btn.show();
                    $tpl.find('.ContactsAutocomplete_throbber').hide();
                }).fail(function(resp) {
                    window.alert('Erro na pesquisa de contatos no catálogo do Expresso.\n'+resp.responseText);
                }).done(function(resp) {
                    var entries = _FormatMoreResults(resp.contacts);
                    $outp.empty().append(entries.entries);
                    var numLoaded = $outp.find('.ContactsAutocomplete_oneResult').length + entries.duplicates;
                    $tpl.find('.ContactsAutocomplete_count').text(numLoaded+'/'+resp.totalCount);
                    $btn.val('Carregar +'+Math.min(50, resp.totalCount - numLoaded));
                    if (numLoaded >= resp.totalCount) {
                        $btn.hide(); // no more pages left
                    }
                });
            } else {
                window.alert('Não há caracteres suficientes para efetuar a busca.');
            }
            isCacheSearch = false;
        });

        $tpl.on('mousedown', function() {
            isMouseDown = true;
        });

        $tpl.on('mouseup', function() {
            isMouseDown = false;
        });
    }

    function _Show() {
        _ScrollPageToPutTextAtTop();
        var txtOff = userOpts.$anchorElem.offset();
        var yPop = txtOff.top + userOpts.$anchorElem.outerHeight() + 1;
        var cyPop = (yPop + userOpts.defaultHeight) > $(window).height() ?
            $(window).height() - yPop : // height is bigger than window, shrink it down to fit
            userOpts.defaultHeight;

        $tpl.appendTo(document.body).css({
            left: txtOff.left+'px',
            top: yPop+'px',
            width: userOpts.$anchorElem.outerWidth()+'px',
            height: cyPop+'px'
        });

        $tpl.find('.ContactsAutocomplete_searchBeyond')
            .val('Mais resultados...').show(); // maybe hidden after a search with no more results
        $tpl.find('.ContactsAutocomplete_count').text('');

        if (App.IsPhone()) {
            UrlStack.push('#AutocompAddr', function() {
                THIS.hide();
                userOpts.$txtField.focus();
            });
        }
    }

    function _FormatResults(contacts) {
        var elems = [];
        for (var i = 0; i < contacts.length; ++i) {
            var $elem = $('#ContactsAutocomplete_template > .ContactsAutocomplete_oneResult').clone();
            $elem.find('.ContactsAutocomplete_name').text(contacts[i].name);
            if (contacts[i].org !== undefined && contacts[i].org !== null && contacts[i].org.length) {
                $elem.find('.ContactsAutocomplete_orgUnit').text(contacts[i].org+', ');
            }
            $elem.find('.ContactsAutocomplete_mail').text(contacts[i].emails.join(', '));
            $elem.data('contact', contacts[i]);
            elems.push($elem);
        }
        return elems;
    }

    function _FormatMoreResults(newContacts) {
        if (!newContacts.length) return;
        var people = [];
        var duplicates = 0;
        $tpl.find('.ContactsAutocomplete_oneResult').each(function(idx, opt) {
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
            if (alreadyExists) {
                ++duplicates;
            } else {
                people.push({ // append new one
                    name: newContacts[c].name,
                    emails: [newContacts[c].email],
                    org: newContacts[c].org
                });
            }
        }
        people.sort(function(a, b) { return a.name.localeCompare(b.name); });
        return {
            entries: _FormatResults(people),
            duplicates: duplicates
        };
    }

    function _MakeBadgeIfTextLooksLikeEmail() {
        var token = $.trim(userOpts.$txtField.val());
        var looksLikeEmail = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/.test(token); // holy s**t!

        if (looksLikeEmail) { // then accept written text as a valid address
            onSelectCB([ token ]); // invoke user callback
            userOpts.$txtField.val('');
        }
    }

    function _ScrollPageToPutTextAtTop() {
        if (App.IsPhone()) { // scroll page to text field to save vertical screen space
            userOpts.$contentPanel.scrollTop(0);
            userOpts.$contentPanel.scrollTop(userOpts.$txtField.offset().top -
                userOpts.$contentPanel.offset().top);
        }
    }

    THIS.hide = function() {
        if ($.contains(document.documentElement, $tpl[0])) {
            $tpl.detach();
            if (App.IsPhone()) {
                UrlStack.pop('#AutocompAddr');
            }
        }
    };

    THIS.onSelect = function(callback) {
        onSelectCB = callback; // onSelect(emailAddrs)
        return THIS;
    };

    THIS.onBackspace = function(callback) {
        onBackspaceCB = callback; // onBackspace()
        return THIS;
    };
};

ContactsAutocomplete.Load = function() {
    // Static method, since this class can be instantiated ad-hoc.
    return $('#ContactsAutocomplete_template').length ?
        $.Deferred().resolve().promise() :
        App.LoadTemplate('../common-js/ContactsAutocomplete.html');
};

return ContactsAutocomplete;
});
