/*!
 * Expresso Lite
 * Utility widget that manages the cookies used in the session
 * between ExpressoLite and Tine servers.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

define([
    'common-js/jQuery',
    'common-js/App'
],
function($, App) {
App.loadCss('debugger/WidgetTineCookies.css');
return function(options) {
    var userOpts = $.extend({
        $parentContainer: null
    }, options);

    var THIS = this;

    function initEventListeners() {
        $('#btnRefresh').on('click', function() {
            THIS.Refresh();
        });
    }

    function updateCookieValue() {
        var $throbber = $('#WidgetTineCookies_templates > .WidgetTineCookies_throbberImg').clone();
        var $button = $(this);

        $(this).parent().append($throbber);
        $button.attr('disabled','disabled');

        App.post('replaceCookieValue', {
            cookieName: $(this).siblings('.WidgetTineCookies_cookieName').text(),
            newValue: $(this).siblings('.WidgetTineCookies_cookieValue').val()
        }).done(function(result) {
            alert('Alterado com sucesso');
        }).fail(function () {
            alert('Erro ao alterar cookie');
        }).always(function () {
            $throbber.remove();
            $button.removeAttr('disabled');
        });
    }

    function appendCookie(cookie) {
        var $cookieLine = $('#WidgetTineCookies_templates > .WidgetTineCookies_cookieLine').clone();
        $cookieLine.find('.WidgetTineCookies_cookieName').text(cookie.name);
        $cookieLine.find('.WidgetTineCookies_cookieValue').val(cookie.value);
        var additionalInfo = '';
        for (var i in cookie) {
            if (i !== 'name' && i !== 'value') {
                additionalInfo += ';' + i + '=' + cookie[i];
            }
        }
        $cookieLine.find('.WidgetTineCookies_additionalInfo').text(additionalInfo);
        $cookieLine.find('.WidgetTineCookies_btnUpdateValue').on('click', updateCookieValue);

        $('#WidgetTineCookies_cookieList').append($cookieLine);
    }

    THIS.Hide = function () {
        userOpts.$parentContainer.hide();
        return THIS;
    };

    THIS.Show = function () {
        userOpts.$parentContainer.show();
        return THIS;
    };

    THIS.Refresh = function () {
        $('#WidgetTineCookies_cookieList').empty();
        $('#WidgetTineCookies_throbberDiv').show();

        App.post('getTineCookies').
        done(function(result) {
            $('#WidgetTineCookies_throbberDiv').hide();
            for (var i in result.cookies) {
                appendCookie(result.cookies[i]);
            }
        });
        return THIS;
    };

    THIS.GetTitle = function () {
        return 'Tine Cookies';
    };
       THIS.Load = function () {
        var defer = $.Deferred();

        App.loadTemplate('WidgetTineCookies.html')
        .done(function () {
            userOpts.$parentContainer.append($('#WidgetTineCookies_div'));
            initEventListeners();
            defer.resolve();
        }).fail(function () {
            defer.reject();
        });

        return defer.promise();
    }
}
});
