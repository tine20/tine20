/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.namespace('Tine.GDPR');

Tine.GDPR.Application = Ext.extend(Tine.Tinebase.Application, {

    hasMainScreen: false,

    /**
     * Get translated application title of the GDPR App
     *
     * @return {String}
     */
    getTitle: function() {
        return this.i18n._('GDPR');
    },

    // @TODO this should really autoregister
    registerCoreData: function() {
        Tine.log.info('Tine.GDPR.Application - registering core data ... ');
        Tine.CoreData.Manager.registerGrid('GDPR_Model_DataProvenance', Tine.GDPR.DataProvenanceGridPanel);
        Tine.CoreData.Manager.registerGrid('GDPR_Model_DataIntendedPurpose', Tine.GDPR.DataIntendedPurposeGridPanel);
    }
});