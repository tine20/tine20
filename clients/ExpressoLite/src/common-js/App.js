/*!
 * Expresso Lite
 * Provides infrastructure services to all modules.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

define(['jquery',
    'common-js/Cordova'
],
function($, Cordova) {
    var isUnloadingPage = false;
    var App = {};
    var ajaxUrl = null; // to be cached on first GetAjaxUrl() call

    function _DisableRefreshOnPullDown() {
        var isFirefoxAndroid =
            navigator.userAgent.indexOf('Mozilla') !== -1 &&
            navigator.userAgent.indexOf('Android') !== -1 &&
            navigator.userAgent.indexOf('Firefox') !== -1;

        if (!isFirefoxAndroid) {
            var lastTouchY = 0;
            var preventPullToRefresh = false;

            $('body').on('touchstart', function(e) {
                if (e.originalEvent.touches.length != 1) return;
                lastTouchY = e.originalEvent.touches[0].clientY;
                preventPullToRefresh = window.pageYOffset == 0;
            });

            $('body').on('touchmove', function(e) {
                var touchY = e.originalEvent.touches[0].clientY;
                var touchYDelta = touchY - lastTouchY;
                lastTouchY = touchY;
                if (preventPullToRefresh) {
                    preventPullToRefresh = false;
                    if (touchYDelta > 0) {
                        e.preventDefault();
                        return;
                    }
                }
            });
        }
    }

    function _CheckUserInfoAvailability() {
        var defer = $.Deferred();
        if (App.GetUserInfo('mailAddress') === null) { // can happen when storage expires and session is still valid
            App.Post('GetUserInfo').done(function(response) {
                for (var i in response) {
                    App.SetUserInfo(i, response[i]);
                }
                defer.resolve();
            });
        } else {
            defer.resolve();
        }
        return defer.promise();
    }

    (function _Constructor() {
        $.ajaxSetup({ // iOS devices may cache POST requests, so make no-cache explicit
            type: 'POST',
            headers: { 'cache-control':'no-cache' }
        });

        _DisableRefreshOnPullDown();

        $(window).on('beforeunload', function() {
            isUnloadingPage = true;
        });
    })();

    App.LoadCss = function(cssFiles) { // pass any number of files as arguments
        var head = document.getElementsByTagName('head')[0];
        for (var i = 0; i < arguments.length; ++i) {
            var link = document.createElement('link');
            link.type = 'text/css';
            link.rel = 'stylesheet';
            link.href = require.toUrl(arguments[i]); // follows require.config() baseUrl
            document.getElementsByTagName('head')[0].appendChild(link);
        }
    };

    App.GetAjaxUrl = function() {
        if (ajaxUrl === null) {
            ajaxUrl = Cordova.isEnabled() ?
                Cordova.getLiteBackendUrl() :
                require.toUrl('.'); // follows require.config() baseUrl
            if (ajaxUrl.charAt(ajaxUrl.length - 1) !== '/') {
                ajaxUrl += '/';
            }
            ajaxUrl += 'api/ajax.php';
        }
        return ajaxUrl;
    }

    App.Post = function(requestName, params) {
        // Usage: App.Post('searchFolders', { parentFolder:'1234' });
        // Returns a promise object.

        var defer = $.Deferred();

        $.post(
            App.GetAjaxUrl(),
            $.extend({r:requestName}, params)
        ).done(function (data) {
            defer.resolve(data);
        }).fail(function (data) {
            if (isUnloadingPage) {
                // we are already going to a new page, so there is no sense
                // in failing whatever went wrong
                return;
            } else if (data.status === 401) { //session timeout
                isUnloadingPage = true; //this avoids duplicated session expired alerts

                window.alert('Sua sessão expirou, é necessário realizar o login novamente.');
                App.ReturnToLoginScreen();
                // as this will leave the current screen, we
                // won't neither resolve or reject
            } else if (data.status === 500 && data.responseText == 'UserMismatchException') {
                window.alert('Ocorreu um problema durante a execução desta operação. É necessário realizar o login novamente.');
                App.ReturnToLoginScreen();
                // as this will leave the current screen, we
                // won't neither resolve or reject
            } else if (data.status === 500 && data.responseText == 'CurlNotInstalledException') {
                window.alert('O pacote PHP cURL (php5-curl) não está instalado no servidor');
            } else {
                defer.reject(data);
            }
        });

        return defer.promise();
     };

    App.LoadTemplate = function(htmlFileName) {
        // HTML file can be a relative path.
        // Pure HTML files are cached by the browser.
        var defer = $.Deferred();
        $.get(htmlFileName).done(function(elems) {
            $(document.body).append(elems);
            defer.resolve();
        });
        return defer.promise();
    };

    App.IsPhone = function() {
        return $(window).width() <= 1024; // should be consistent with all CSS media queries
    };

    App.SetUserInfo = function(entryIndex, entryValue) {
        localStorage.setItem('user_info_'+entryIndex, entryValue);
    };

    App.GetUserInfo = function(entryIndex) {
        return localStorage.getItem('user_info_'+entryIndex);
    };

    App.SetCookie = function(cookieName, cookieValue, expireDays) {
        var d = new Date();
        d.setTime(d.getTime() + (expireDays * 24 * 60 * 60 * 1000));
        var expires = 'expires='+d.toUTCString();
        document.cookie = cookieName+'='+cookieValue+'; '+expires;
    };

    App.GetCookie = function(cookieName) {
        var name = cookieName+'=';
        var allCookies = document.cookie.split(';');
        for(var i=0; i < allCookies.length; i++) {
            var cookie = allCookies[i].replace(/^\s+/,''); //this trims spaces in the left of the string;
            if (cookie.indexOf(name) === 0) {
                return cookie.substring(name.length,cookie.length);
            }
        }
        return null;
    };

    App.ReturnToLoginScreen = function() {
        var currHref = document.location.href.split('#')[0]; //uses only the part before the first # (it there is one)
        var destHref = currHref.replace(/\b(\/mail|\/addressbook|\/calendar|\/debugger)\b/gi, ''); //removes /module from the URL address

        if (destHref.slice(-1) != '/') { //checks last char
            destHref += '/';
        }

        if (Cordova.isEnabled()) {
            destHref += 'index.html';
        }

        document.location.href = destHref;
    };

    App.Ready = function(callback) {
        _CheckUserInfoAvailability().done(function() {
            if (Cordova.isEnabled()) {
                $(document).ready(function() {
                    document.addEventListener('deviceready', function() {
                        Cordova.RegisterCordovaListeners();
                        callback();
                    }, false);
                });
            } else {
                $(document).ready(callback);
            }
        });
    };

    App.GoToFolder = function(folderName) {
        document.location.href = folderName + (Cordova.isEnabled() ? '/index.html' : '/');
    };

    return App;
});
