/*!
 * Expresso Lite
 * This module provides all cordova functionality.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

define([
    'common-js/jQuery',
    'common-js/CordovaConfig',
    'common-js/AccountManager'
],
function($, CordovaConfig, AccountManager, App) {
    if (!CordovaConfig.isEnabled) {
        return null;
    }

    var isCordovaListenersRegistered = false;
    var Cordova = {};
    var accountManager = new AccountManager();

    function _OnCordovaResume() {
        var App = require('common-js/App');
        //this is needed to avoid a circular dependence in requireJs

        App.post('checkSessionStatus')
        .done(function(result) {
            if (result.status !== 'active') {
                App.returnToLoginScreen();
            }
        });
    };

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

    Cordova.RegisterCordovaListeners = function() {
        if (!isCordovaListenersRegistered) {
            document.addEventListener('resume', _OnCordovaResume, false);
            isCordovaListenersRegistered = true;
        }
    };

    Cordova.ClearAllCredentials = function() {
        var defer = $.Deferred();

        accountManager.GetAccounts()
        .done(function(accounts) {
            if (accounts.length == 0) {
                defer.resolve();
            } else {
                var removeAccountPromises = [];
                for (var i=0; i< accounts.length; i++) {
                    removeAccountPromises[i] = accountManager.RemoveAccount(accounts[i]);
                }

                $.when.apply($, removeAccountPromises)
                .done(function() {
                    defer.resolve();
                })
                .fail(function() {
                    defer.reject();
                });
            }
        });

        return defer.promise();
    };

    Cordova.GetCurrentAccount = function() {
        var defer = $.Deferred();

        accountManager.GetAccounts()
        .done(function(accounts) {
            if (accounts.length == 0) {
                defer.resolve(null);
            } else if (accounts.length == 1) {
                var curAccount = accounts[0];
                accountManager.GetPassword(curAccount)
                .done(function(password) {
                    defer.resolve({
                        login: curAccount.name,
                        password: password
                    });
                }).fail(function(error) {
                    defer.reject(error);
                })
            } else  {
                // We should never have more than one account,
                // but if it this does happen, we delete all
                // accounts to return to a valid state
                Cordova.ClearAllCredentials()
                .always(function() {
                    defer.reject('Error: multiple accounts found for Expresso');
                });
            }
        }).fail(function(error) {
            defer.reject(error);
        });

        return defer.promise();
    };

    Cordova.SaveAccount = function(login, password) {
        var defer = $.Deferred();

        //first clear all credentials to make this replace any old version
        Cordova.ClearAllCredentials()
        .done(function() {
            accountManager.AddAccountExplicitly(login, password, null)
            .done(function(account) {
                defer.resolve(account)
            }).fail(function(error) {
                defer.reject(error);
            });
        })
        .fail(function() {
            defer.reject(error);
        });

        return defer.promise();
    };

    Cordova.HasInternetConnection = function() {
        return navigator.connection.type !== Connection.NONE;
    };

    return Cordova;
});
