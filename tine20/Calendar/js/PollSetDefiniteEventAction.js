/*
 * Tine 2.0
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Calendar.eventActions');

/**
 * set current alternative as definite event
 */
Tine.Calendar.eventActions.setDefiniteEventAction = {
    app: 'Calendar',
    requiredGrant: 'editGrant',
    allowMultiple: false,
    text: 'Set as definite event', // _('Set as definite event')
    disabled: true,
    iconCls: 'cal-polls-set-definite-action',
    scope: this,

    confirm: function() {
        return new Promise(function(fulfill, reject) {
            var app = Tine.Tinebase.appMgr.get('Calendar');

            Ext.Msg.confirm(
                app.i18n._('Set This Date as Definite Event?'),
                app.i18n._('Do you want to close the poll and remove all other alternatives?'),
                function(button) {
                    if (button == 'yes') {
                        fulfill();
                    } else {
                        reject('canceled');
                    }
                });
        });
    }
};
