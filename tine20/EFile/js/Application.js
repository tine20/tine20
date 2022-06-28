/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.namespace('Tine.EFile');

Tine.EFile.Application = Ext.extend(Tine.Tinebase.Application, {

    hasMainScreen: false,

    /**
     * Get translated application title
     *
     * @return {String}
     */
    getTitle: function() {
        return this.i18n._('Electronic File');
    },

    init: function() {
    },


});
