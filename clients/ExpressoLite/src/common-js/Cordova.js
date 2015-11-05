/*!
 * Expresso Lite
 * This module provides all cordova functionality.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

define(['jquery',
    'common-js/CordovaConfig',
    'common-js/App'
],
function($, CordovaConfig, App) {
    var Cordova = {};
    // We could make the application get CordovaConfig values directly,
    // but it is better to create functions in this file. This way, the
    // rest of the application will have only one single point of access
    // to Cordova functionality
    Cordova.getLiteBackendUrl = function() {
        return CordovaConfig.liteBackendUrl;
    };

    Cordova.isEnabled = function() {
        return CordovaConfig.isEnabled;
    };

    function _OnCordovaResume() {
        App.Post('checkSessionStatus')
        .done(function(result) {
            if (result.status !== 'active') {
                App.ReturnToLoginScreen();
            }
        });
    };

    var isCordovaListenersRegistered = false;
    Cordova.RegisterCordovaListeners = function() {
        if (!isCordovaListenersRegistered) {
            document.addEventListener('resume', _OnCordovaResume, false);
            isCordovaListenersRegistered = true;
        }
    }

    return Cordova;
});
