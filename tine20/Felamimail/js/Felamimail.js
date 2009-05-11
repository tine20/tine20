/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Felamimail.js 7176 2009-03-05 12:26:08Z p.schuele@metaways.de $
 *
 */
 
Ext.namespace('Tine.Felamimail');

/**
 * default mainscreen
 * 
 * @type Tine.Tinebase.widgets.app.MainScreen
 */
Tine.Felamimail.MainScreen = Tine.Tinebase.widgets.app.MainScreen;

/**
 * message backend
 * 
 * @type Tine.Tinebase.widgets.app.JsonBackend
 */
Tine.Felamimail.messageBackend = new Tine.Tinebase.widgets.app.JsonBackend({
    appName: 'Felamimail',
    modelName: 'Message',
    recordClass: Tine.Felamimail.Model.Message
});

/**
 * account backend
 * 
 * @type Tine.Tinebase.widgets.app.JsonBackend
 */
Tine.Felamimail.accountBackend = new Tine.Tinebase.widgets.app.JsonBackend({
    appName: 'Felamimail',
    modelName: 'Account',
    recordClass: Tine.Felamimail.Model.Account
});
