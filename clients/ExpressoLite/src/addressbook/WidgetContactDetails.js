/*!
 * Expresso Lite
 * Widget that shows a div with all contact details.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

define(['jquery',
    'common-js/App',
    'common-js/Contacts'
],
function($, App,Contacts) {
    App.LoadCss('addressbook/WidgetContactDetails.css');
    return function(options) {
        var userOpts = $.extend({
            $parentContainer: null
        }, options);

        var THIS = this;

        var $mainDiv;
        var $fieldTemplate;
        var $throbber;
        //all these will be set by loadHtmlTemplates

        var minimizedFrame = null;
        var $contactListItemDiv = null;
        //these two will be set by showDetails

        var maximizedFrame = null;

        var otherFields = {
            'salutation': 'Saudação',
            'n_prefix': 'Título',
            'org_name': 'Empresa',
            'org_unit': 'Unidade',
            'bday': 'Aniversário',
            'adr_one_street': 'Rua',
            'adr_one_street2': 'Rua 2',
            'adr_one_region': 'Estado',
            'adr_one_postalcode': 'Código Postal',
            'adr_one_locality': 'Cidade',
            'adr_one_country': 'Páis'
        };

        function hex2bin(hex) {
            var bytes = [];
            for(var i = 0; i < hex.length - 1; i += 2) {
                bytes.push(parseInt(hex.substr(i, 2), 16));
            }
            return String.fromCharCode.apply(String, bytes);
        }

        function showContactDetailsFormAdditionalInfo(fullContact) {
            if (fullContact.mugshot == '') {
                $mainDiv.find('#WidgetContactDetails_mugshot').attr('src', '../img/person-generic.gif');
            } else {
                $mainDiv.find('#WidgetContactDetails_mugshot').attr('src', 'data:image/jpeg;base64,' + hex2bin(fullContact.mugshot));
            }
            $mainDiv.find('#WidgetContactDetails_mugshotThrobber').hide();
            $mainDiv.find('#WidgetContactDetails_mugshot').css('visibility', '').hide().fadeIn(200);

            $throbber.hide();

            $otherFieldsDiv.hide();
            for (var fieldId in otherFields) {
                var fieldIsNotEmpty =
                    fullContact.otherFields[fieldId] != undefined &&
                    fullContact.otherFields[fieldId].trim() != '';

                if (fieldIsNotEmpty) {
                    $newField = $fieldTemplate.clone();
                    $newField.find('.WidgetContactDetails_otherFieldLabel').html(otherFields[fieldId] + ':');
                    $newField.find('.WidgetContactDetails_otherFieldValue').val(fullContact.otherFields[fieldId]);
                    $newField.appendTo($otherFieldsDiv);
                }
            }
            $otherFieldsDiv.fadeIn(200);
        }

        function createTelLink(number) {
            if (!number) {
                return '';
            } else if (App.IsPhone()) {
                return number ? '<a class="phoneNumber" href="tel:' + number + '">' + number + '</a>' : '';
            } else {
                return number;
            }
        }

        function showContactDetailsFormTopInfo(contact) {
            $mainDiv.find('.WidgetContactDetails_name').html(contact.name);
            $mainDiv.find('.WidgetContactDetails_email').html(contact.email);
            $mainDiv.find('.WidgetContactDetails_phone').html(createTelLink(contact.phone));
            $mainDiv.find('.WidgetContactDetails_mobile').html(createTelLink(contact.mobile));

            $mainDiv.find('#WidgetContactDetails_mugshot').css('visibility', 'hidden');
            $mainDiv.find('#WidgetContactDetails_mugshotThrobber').show();

            $otherFieldsDiv = $('#WidgetContactDetails_otherFieldsDiv').empty();
            $throbber.show();
            $mainDiv.show();
        }

        function loadHtmlTemplates() {
            var defer = $.Deferred();
            $.get('WidgetContactDetails.html', function(elems) {
                $(document.body).append($(elems));
                defer.resolve();
            });
            return defer.promise();
        }

        THIS.showDetails = function (contact) {
            $mainDiv.show();
            showContactDetailsFormTopInfo(contact);
            App.Post('getContact', {id: contact.id})
            .done(function(fullContact) {
                showContactDetailsFormAdditionalInfo(fullContact);
            });
        }

        THIS.load = function() {
            return loadHtmlTemplates()
                .done(function () {
                    $mainDiv =
                        $('#WidgetContactDetails_mainDiv')
                        .hide()
                        .appendTo(userOpts.$parentContainer);

                    $throbber =
                        $('#WidgetContactDetails_throbberDiv')
                        .hide();

                    $fieldTemplate =
                        $mainDiv.find('.WidgetContactDetails_otherFieldRow')
                        .detach();
                });
        }
    }
});
