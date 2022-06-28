/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.namespace('Tine.OnlyOfficeIntegrator');

Tine.OnlyOfficeIntegrator.Application = Ext.extend(Tine.Tinebase.Application, {

    hasMainScreen: false,

    /**
     * Get translated application title
     *
     * @return {String}
     */
    getTitle: function() {
        return this.i18n._('Only Office Integrator');
    },

    init: function() {
    },

    
});
