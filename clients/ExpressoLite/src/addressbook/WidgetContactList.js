/*!
 * Expresso Lite
 * Widget that shows a list of contacts.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

define([
    'common-js/jQuery',
    'common-js/App',
    'addressbook/WidgetLetterIndex'
],
function($, App, WidgetLetterIndex) {
    App.loadCss('addressbook/WidgetContactList.css');

    return function(options) {
        var userOpts = $.extend({
            $parentContainer: null
        }, options);

        var THIS = this;
        var catalogs = null;

        var currentSearchFilter = {
                catalog: null,
                query: '',
                pageStart: 0
        };

        var $mainSection;
        var $itemTemplate;
        var $pagingFooter;
        var $throbber;

        var lastInitialLetter = ''; //the last initial letter that was displayed

        var onItemClickCB = function () {};

        function createWidgetLetterIndex() {
            var $letterIndexAside =
                $(document.createElement('aside'))
                .attr('id', 'letterIndexAside')
                .appendTo(userOpts.$parentContainer);

            var widget = new WidgetLetterIndex({$parentContainer: $letterIndexAside});

            widget.onLetterSelect(function(letter) {
                THIS.goToLetter(letter);
            });

            return widget;
        }

        function builLetterSeparatorDiv(initialLetter) {
            var $div = $(document.createElement('div'))
                .attr('id', 'letterSeparator_' + (initialLetter == '#' ? 'number' : initialLetter))
                .addClass('WidgetContactList_letterSeparator')
                .html(initialLetter);
            return $div;
        }

        function buildContactDiv(contact) {
            var $div = $itemTemplate.clone();
            $div.find('.WidgetContactList_name').html(contact.name);
            $div.find('.WidgetContactList_email').html(contact.email);

            if (contact.phone || contact.mobile) {
                $div.find('.WidgetContactList_phone').html(contact.phone);
                $div.find('.WidgetContactList_mobile').html(contact.mobile);
            } else {
                $div.find('.WidgetContactList_phonesDiv').hide();
            }

            $div.data('contact', contact);
            return $div;
        }

        function addContactsToList(contacts) {
            var divs = [];

            for (var i=0; i<contacts.length; i++) {
                var contact = contacts[i];
                var initialLetter = contact.name.charAt(0).toUpperCase();
                if (initialLetter < 'A' || initialLetter > 'Z') { //TODO: this does not handle accents
                    initialLetter = '#';
                }

                if (initialLetter !== lastInitialLetter) {
                    divs.push(builLetterSeparatorDiv(initialLetter));
                }

                var $div = buildContactDiv(contact);
                $div.on('click', function () {
                    THIS.unselectCurrentItem();
                    $(this).addClass('WidgetContactList_itemSelected');
                    onItemClickCB($(this).data('contact'));
                });

                divs.push($div);

                lastInitialLetter = initialLetter;
            }

            $mainSection.append(divs);
        }

        function showError(message) {
            alert(message);
        }

        function loadMoreContacts() {

            if (currentSearchFilter.query.length < 3 && !currentSearchFilter.catalog.autoload) {
                showMessage('Digite parte do nome do contato<br>(pelo menos 3 caracteres)');
                return;
            }

            showLoadingThrobber();

            App.post('getContactsByFilter', {
                containerPath: currentSearchFilter.catalog.containerPath,
                query:         currentSearchFilter.query,
                start:         currentSearchFilter.pageStart,
                limit:         currentSearchFilter.catalog.pageLimit
            }).done( function (result) {
                if (result.contacts.length > 0) {
                    currentSearchFilter.pageStart += result.contacts.length;
                    hideMessageWithAnimation()
                    .done(function() {
                        $mainSection.hide();
                        addContactsToList(result.contacts);

                        if (currentSearchFilter.pageStart < result.totalCount) {
                            showPagingFooter(result.totalCount);
                        }
                        $mainSection.velocity('fadeIn', { duration:150 });
                    });
                } else {
                    if (currentSearchFilter.pageStart == 0) {
                        showMessage('Nenhum contato encontrado');
                    }
                }
            }).fail(function (data) {
                hideMessage();
                showError('Ocorreu um erro ao consultar os contatos');
            });
        }

        function showPagingFooter(totalCount) {

            var howManyMore = totalCount - currentSearchFilter.pageStart;

            if (howManyMore > currentSearchFilter.catalog.pageLimit) {
                howManyMore = currentSearchFilter.catalog.pageLimit;
            }

            $pagingFooter.find('#WidgetContactList_loadedCountSpan')
                .html(currentSearchFilter.pageStart + ' de ' + totalCount + ' carregados');

            $pagingFooter.find('#WidgetContactList_loadMoreButton')
                .val('carregar +' + howManyMore);

            $pagingFooter
                .detach() // remove it...
                .appendTo($mainSection) // ... and then re-append it at the end
                .on('click', function () {
                    $mainSection.find('#WidgetContactList_footer').hide();
                    loadMoreContacts();
                })
                .show();
        }

        function clearList() {
            $mainSection.empty();
            currentSearchFilter.pageStart = 0;
            lastInitialLetter = '';
        }

        function showLoadingThrobber() {
            showMessage('Carregando contatos... ', true);
        }

        function showMessage(msg, showThrobber) {
            var $messageDiv = $mainSection.find('.message');

            if ($messageDiv.length > 0) {
                $messageDiv.empty();
            } else {
                $messageDiv =
                    $(document.createElement('div'))
                    .addClass('message')
                    .appendTo($mainSection);
            }

            $messageDiv.html(msg);

            if (showThrobber) {
                $throbber.clone().appendTo($messageDiv);
            }
        }

        function hideMessage() {
            $mainSection.children('.message').remove();
        }

        function hideMessageWithAnimation() {
            var defer = $.Deferred();

            var $message = $mainSection.find('.message');

            $message.velocity(
                {'margin-top': (userOpts.$parentContainer.height() - 20)+'px'},
                200,
                function() {
                    $message.remove();
                    defer.resolve();
                }
            );

            return defer;
        }

        function changeCatalog(newCatalog) {
            clearList();
            currentSearchFilter.catalog = newCatalog;
            currentSearchFilter.query='';
            showTitle(newCatalog.title);
            return loadMoreContacts();
        }

        function loadHtmlTemplates() {
            var defer = $.Deferred();
            $.get('WidgetContactList.html', function(elems) {
                $(document.body).append($(elems));
                defer.resolve();
            });
            return defer.promise();
        }

        function loadCatalogs() {
            var defer = $.Deferred();

            App.post('getContactCatalogsCategories')
            .done(function(result) {
                catalogs = result;
                defer.resolve();
            });

            return defer.promise();
        }

        function showTitle(catalogLabel) {
            document.title = catalogLabel + ' - '+App.getUserInfo('mailAddress')+' - ExpressoBr';
            Cache.layout.setTitle(catalogLabel);
        }

        THIS.changeToPersonalCatalog = function() {
            changeCatalog(catalogs.personal);
        }

        THIS.changeToCorporateCatalog = function() {
            changeCatalog(catalogs.corporate);
        }

        THIS.changeQuery = function (newQuery) {
            if (currentSearchFilter.query != newQuery) {
                clearList();
                showTitle('Busca no ' + currentSearchFilter.catalog.title);
                currentSearchFilter.query = newQuery;
                return loadMoreContacts();
            }
        }

        THIS.goToLetter = function(letter) {
            if (lastInitialLetter != '' && letter > lastInitialLetter) {
                window.location.hash = '#contactListFooter';
            } else {
                window.location.hash = '#letterSeparator_' + (letter == '#' ? 'number' : letter);
            }
        }

        THIS.unselectCurrentItem = function() {
            $('.WidgetContactList_itemSelected').removeClass('WidgetContactList_itemSelected');
        }

        THIS.scrollToCurrentItem = function() {
            var $item = $('.WidgetContactList_itemSelected');
            userOpts.$parentContainer.scrollTop(
                    $item.offset().top -
                    $mainSection.offset().top -
                    (userOpts.$parentContainer.height() / 2) +
                    $item.height() + 20);
        }

        THIS.onItemClick = function(callback) {
            onItemClickCB = callback;
        }

        THIS.load = function () {
            var defer = $.Deferred();
            createWidgetLetterIndex();

            loadHtmlTemplates()
            .done(function() {
                $mainSection = $('#WidgetContactList_mainSection')
                    .appendTo(userOpts.$parentContainer);

                $itemTemplate = $mainSection.find('.WidgetContactList_item')
                    .detach();

                $pagingFooter = $('#WidgetContactList_footer').hide();

                $throbber = $('#WidgetContactList_throbber');

                loadCatalogs()
                .done(function() {
                    defer.resolve();
                });
            });
            return defer.promise();
        }
    }
});
