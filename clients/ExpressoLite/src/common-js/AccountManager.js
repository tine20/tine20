/*!
 * Expresso Lite
 * Wrapper for the AccountManager plugin that provides access to its
 * functions in the 'deferred' (done/fail) style, rather than using
 * the (error, result) params. This allows the rest of the application
 * to be more consistent with a single programming style.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */

define([
    'common-js/jQuery',
    'common-js/CordovaConfig'
],
function($, CordovaConfig) {
    return function() {
        if (!CordovaConfig.isEnabled) {
            throw 'AccountManager can only be used when Cordova is enables';
        }

        var THIS = this;
        var rawaccountmanager = window.plugins.accountmanager;

        var ACCOUNT_TYPE = 'br.gov.serpro.expressobr.Login';
        //account type used by account manager as defined in res/xml/authenticator.xml

        function _ResolveOrReject(defer, error, result) {
            if (error === undefined) {
                defer.resolve(result);
            } else {
                defer.reject(error);
            }
        }

        THIS.GetAccounts = function() {
            var defer = $.Deferred();
            rawaccountmanager.getAccountsByType(ACCOUNT_TYPE, function(error, accounts) {
                _ResolveOrReject(defer, error, accounts);
            });
            return defer;
        };

        THIS.AddAccountExplicitly = function(name, password, userData) {
            var defer = $.Deferred();
            rawaccountmanager.addAccountExplicitly(ACCOUNT_TYPE, name, password, userData, function(error, account) {
                _ResolveOrReject(defer, error, account);
            });
            return defer.promise();
        };

        THIS.RemoveAccount = function(account) {
            var defer = $.Deferred();
            rawaccountmanager.removeAccount(account, function(error) {
                _ResolveOrReject(defer, error);
            });
            return defer.promise();
        };

        THIS.GetPassword = function(account) {
            var defer = $.Deferred();
            rawaccountmanager.getPassword(account, function(error, password) {
                _ResolveOrReject(defer, error, password);
            });
            return defer.promise();
        };
    }
});
