/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.widgets');

/**
 * Widget for country selection
 *
 * @namespace   Tine.widgets
 * @class       Tine.widgets.CountryCombo
 * @extends     Ext.form.ComboBox
 */
Tine.widgets.CountryCombo = Ext.extend(Ext.form.ComboBox, {
    fieldLabel: 'Country',
    displayField:'translatedName',
    valueField:'shortName',
    typeAhead: true,
    forceSelection: true,
    mode: 'local',
    triggerAction: 'all',
    selectOnFocus:true,
    
    /**
     * @private
     */
    initComponent: function() {
        this.store = this.getCountryStore();
        this.emptyText = i18n._('Select a country...');
        
        Tine.widgets.CountryCombo.superclass.initComponent.call(this);
    },
    /**
     * @private store has static content
     */
    getCountryStore: function(){
        var store = Ext.StoreMgr.get('Countries');
        if (!store) {
            store = new Ext.data.JsonStore({
                baseParams: {
                    method: 'Tinebase.getCountryList'
                },
                root: 'results',
                id: 'shortName',
                fields: Tine.Tinebase.Model.Country,
                remoteSort: false,
                sortInfo: {
                    field: 'translatedName',
                    direction: 'ASC'
                }
            });
            Ext.StoreMgr.add('Countries', store);
        }
        
        var countryList = Locale.getTranslationList('CountryList');
        if (countryList) {
            var storeData = {results: []};
            for (var shortName in countryList) {
                storeData.results.push({shortName: shortName, translatedName: countryList[shortName]});
            }
            store.loadData(storeData);
        }
        return store;
    }
});

Ext.reg('widget-countrycombo', Tine.widgets.CountryCombo);
