/*!
 * Expresso Lite
 * Main SplashScreen object code.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */

define([
    'common-js/jQuery',
    'common-js/App',
    'common-js/Cordova',
],
function($, App, Cordova) {
App.loadCss('common-js/SplashScreen.css');
return function(options) {
    var userOpts = $.extend({
    }, options);

    var THIS = this;

    function _MakeVisible() {
        if ($('#SplashScreen_template > #SplashScreen_screenDiv').length > 0) {
            $('#SplashScreen_template > #SplashScreen_screenDiv').appendTo($(document.body));
            // if the splash screen is still in templates section,
            // move it to the body to make it visible
        }
    }

    THIS.load = function() {
        var defer = $.Deferred();

        if (Cordova) {
            var basePath = (App.getCurrentModuleContext() === '/') ?
                './' : // we are at root context (login screen)
                '../'; // we are inside some module, so we have to go back a level

            App.loadTemplate(basePath + 'common-js/SplashScreen.html').done(function() {
                $('#SplashScreen_screenDiv img').each(function () {
                    //adjust all image paths
                    var imgSrc = $(this).attr('src');
                    $(this).attr('src', basePath + imgSrc);
                });
                defer.resolve();
            }).fail(function(error) {
                defer.reject(error);
            });
        } else {
            //don't bother loading anything if not in cordova mode
            defer.resolve();
        }

        return defer;
    };

    THIS.showThrobber = function() {
        _MakeVisible();
        $('#SplashScreen_throbber').show();
        $('#SplashScreen_noInternetDiv').hide();
    };

    THIS.showNoInternetMessage = function() {
        _MakeVisible();
        $('#SplashScreen_throbber').hide();
        $('#SplashScreen_noInternetDiv').show();
        $('#SplashScreen_btnReloadPage').on('click', function() {
            window.location.reload(true);
        });
    };

    THIS.moveUpAndClose = function() {
        //this function should only be called by the login module

        var defer = $.Deferred();

        $('#SplashScreen_throbber').hide();
        $('#SplashScreen_screenDiv')
        .velocity({ top: '16px' }, {
            duration: 300,
            queue: false,
            complete: function() {
                defer.resolve();
            }
        });

        return defer;
    };

    return THIS;
};
});
