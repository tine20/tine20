/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2016-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * webpack entry
 */
// NOTE: outdated browsers are not able to load the code dynamically so we need
//       to notify them at this early stage
// NOTE: it would be better to use browserslist-useragent, but there is no cliet side support atm:
//       https://github.com/browserslist/browserslist-useragent/issues/9
if (!Promise) {
    // wait for translation to load
    window.setTimeout(function() {
        var msgs = Tine && Tine.__translationData ? Tine.__translationData.msgs['./LC_MESSAGES/Tinebase'] : {};
        var _ = function(msg) {
            return msgs[msg] || msg;
        };

        Ext.Msg.show({
            title:_("Outdated Browser"),
            msg: _("You need a current Browser to use this program."),
            buttons: '',
            closable:false,
            icon: Ext.MessageBox.ERROR
        });
    }, 2000);
} else {
    require.ensure(['Tinebase.js'], function () {
        var libs = require('Tinebase.js');

        libs.lodash.assign(window, libs);
        require('tineInit');
    }, 'Tinebase/js/Tinebase');
}

