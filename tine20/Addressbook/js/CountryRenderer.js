/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Addressbook');

/**
 * Render country name by it's iso code
 *
 * @namespace   Tine.Addressbook
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @singleton
 */
Tine.Addressbook.countryRenderer = function (v) {
    
    return Tine.__translationData.TranslationLists['CountryList'][v];
};

Tine.widgets.grid.RendererManager.register('Addressbook', 'Addressbook_Model_Contact', 'country', Tine.Addressbook.countryRenderer, 'displayPanel');