/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.widgets');

/**
 * @namespace   Tine.widgets
 * @class       Tine.widgets.CountryFilter
 * @extends     Tine.widgets.grid.PickerFilter
 */
Tine.widgets.CountryFilter = Ext.extend(Tine.widgets.grid.PickerFilter, {
    
    defaultOperator: 'equals',
    operators: ['equals', 'not', 'in', 'notin', 'contains'],
    
    /**
     * @private
     */
    initComponent: function() {
        this.picker = Tine.widgets.CountryCombo;
        this.recordClass = Tine.Tinebase.Model.Country;
        this.gridLayerComboLabelField = 'translatedName';

        Tine.widgets.CountryFilter.superclass.initComponent.call(this);

        if (this.label === '') {
            this.label = i18n._('Country');
        }
    }
});
Tine.widgets.grid.FilterToolbar.FILTERS['tinebase.country'] = Tine.widgets.CountryFilter;
