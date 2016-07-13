/*!
 * Expresso Lite
 * Provides infrastructure services to all modules.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

define([
    'common-js/jQuery',
    'common-js/Cordova'
],
function($, Cordova) {
    var isUnloadingPage = false;
    var App = {};
    var ajaxUrl = null; // to be cached on first getAjaxUrl() call

    var numberOfPendingAjax = 0;

    var moduleUrlRegEx = /\b(\/mail|\/addressbook|\/calendar|\/debugger)(\/index.html)?\b/gi;
    //a regex used to determine in which module we are on

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
        if (App.getUserInfo('mailAddress') === null) { // can happen when storage expires and session is still valid
            App.post('getUserInfo').done(function(response) {
                for (var i in response) {
                    App.setUserInfo(i, response[i]);
                }
                defer.resolve();
            }).fail(function (error) {
                // Probably means we are offline. The user will have to reload
                // the page and this will restore our user info, so we resolve
                // our deferred anyway
                defer.resolve();
            });
        } else {
            defer.resolve();
        }
        return defer.promise();
    }

    App.loadCss = function(cssFiles) { // pass any number of files as arguments
        var head = document.getElementsByTagName('head')[0];
        for (var i = 0; i < arguments.length; ++i) {
            var link = document.createElement('link');
            link.type = 'text/css';
            link.rel = 'stylesheet';
            link.href = require.toUrl(arguments[i]); // follows require.config() baseUrl
            document.getElementsByTagName('head')[0].appendChild(link);
        }
    };

    App.getAjaxUrl = function() {
        if (ajaxUrl === null) {
            ajaxUrl = Cordova ?
                Cordova.getLiteBackendUrl() :
                require.toUrl('.'); // follows require.config() baseUrl
            if (ajaxUrl.charAt(ajaxUrl.length - 1) !== '/') {
                ajaxUrl += '/';
            }
            ajaxUrl += 'api/ajax.php';
        }
        return ajaxUrl;
    }

    App.post = function(requestName, params) {
        // Usage: App.post('searchFolders', { parentFolder:'1234' });
        // Returns a promise object.

        var defer = $.Deferred();

        if (Cordova && !Cordova.HasInternetConnection()) {
            defer.reject({error: 'nointernet', responseText: 'Sem conexão à Internet.'});
            return defer;
        }

        numberOfPendingAjax++;
        $.post(
            App.getAjaxUrl(),
            $.extend({r:requestName}, params)
        ).always(function() {
            numberOfPendingAjax--;
        }).done(function (data) {
            defer.resolve(data);
        }).fail(function (data) {
            if (isUnloadingPage) {
                // we are already going to a new page, so there is no sense
                // in failing whatever went wrong
                return;
            } else if (data.status === 401) { //session timeout
                isUnloadingPage = true; //this avoids duplicated session expired alerts

                window.alert('Sua sessão expirou, é necessário realizar o login novamente.');
                App.returnToLoginScreen();
                // as this will leave the current screen, we
                // won't neither resolve or reject
            } else if (data.status === 500 && data.responseText == 'UserMismatchException') {
                window.alert('Ocorreu um problema durante a execução desta operação. É necessário realizar o login novamente.');
                App.returnToLoginScreen();
                // as this will leave the current screen, we
                // won't neither resolve or reject
            } else if (data.status === 500 && data.responseText == 'CurlNotInstalledException') {
                window.alert('O pacote PHP cURL (php5-curl) não está instalado no servidor');
            } else if (data.status === 500 && data.responseText == 'ConfPhpNotFound') {
                window.alert('Não foi possível encontrar o arquivo conf.php no servidor.\n' +
                             'Utilize o arquivo conf.php.dist como modelo para criá-lo e recarregue a página.');
            } else {
                defer.reject(data);
            }
        });

        return defer.promise();
     };

    App.loadTemplate = function(htmlFileName) {
        // HTML file can be a relative path.
        // Pure HTML files are cached by the browser.
        var defer = $.Deferred();
        $.get(htmlFileName).done(function(elems) {
            $(document.body).append(elems);
            defer.resolve();
        });
        return defer.promise();
    };

    App.isPhone = function() {
        return $(window).width() <= 1024; // should be consistent with all CSS media queries
    };

    App.setUserInfo = function(entryIndex, entryValue) {
        localStorage.setItem('user_info_'+entryIndex, entryValue);
    };

    App.getUserInfo = function(entryIndex) {
        return localStorage.getItem('user_info_'+entryIndex);
    };

    App.setCookie = function(cookieName, cookieValue, expireDays) {
        var d = new Date();
        d.setTime(d.getTime() + (expireDays * 24 * 60 * 60 * 1000));
        var expires = 'expires='+d.toUTCString();
        document.cookie = cookieName+'='+cookieValue+'; '+expires;
    };

    App.getCookie = function(cookieName) {
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

    App.getCurrentModuleContext = function() {
        var currHref = document.location.href.split('#')[0];
        var res = moduleUrlRegEx.exec(currHref);

        if (res === null) {
            return '/'; // no module found, means we are at root context
        } else {
            return res[1]; // regex group with index 1 has the module
        }
    }

    App.returnToLoginScreen = function() {
        var currHref = document.location.href.split('#')[0]; //uses only the part before the first # (it there is one)
        var destHref = currHref.replace(moduleUrlRegEx, ''); //removes /module from the URL address

        if (destHref.slice(-1) != '/') { //checks last char
            destHref += '/';
        }

        if (Cordova) {
            destHref += 'index.html';
        }

        document.location.href = destHref;
    };

    App.ready = function(callback) {
        _CheckUserInfoAvailability().done(function() {
            if (Cordova) {
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

    App.goToFolder = function(folderName) {
        document.location.href = folderName + (Cordova ? '/index.html' : '/');
    };

    App.getNumberOfPendingAjax = function() {
        return numberOfPendingAjax;
    };

    App.errorMessage = function (msg, resp) {
        if (!Cordova) {
            msg += '\nSua interface está inconsistente, pressione F5.';
        }
        msg += '\n' + resp.responseText;

        window.alert(msg);
    };

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

    return App;
});
