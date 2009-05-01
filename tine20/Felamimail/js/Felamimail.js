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
 * default message backend
 * 
 * @type Tine.Tinebase.widgets.app.JsonBackend
 */
Tine.Felamimail.recordBackend = new Tine.Tinebase.widgets.app.JsonBackend({
    appName: 'Felamimail',
    modelName: 'Message',
    recordClass: Tine.Felamimail.Model.Message
});

/**
 * get flag icon
 * 
 * @param {} flags
 * @return {}
 */
Tine.Felamimail.getFlagIcon = function(flags) {
    if (!flags) {
        return '';
    }
    
    var icon = '';
    if (flags.match(/Answered/)) {
        icon = 'images/oxygen/16x16/actions/mail-reply-sender.png';
    }   
    if (flags.match(/Passed/)) {
        icon = 'images/oxygen/16x16/actions/mail-forward.png';
    }   
    
    return '<img class="FelamimailFlagIcon" src="' + icon + '">'; // ext:qtip="' + status.data.status_name + '">';
};
