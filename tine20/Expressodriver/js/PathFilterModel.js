/*
 * Tine 2.0
 *
 * @package     Expressodriver
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 * @author      Edgar de Lucca <edgar.lucca@serpro.gov.br>
 *
 */
Ext.ns('Tine.Expressodriver');

/**
 * @namespace   Tine.widgets.container
 * @class       Tine.Expressodriver.PathFilterModel
 * @extends     Tine.widgets.grid.FilterModel
 *
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 *
 * @TODO make valueRenderer a path picker widget
 */
Tine.Expressodriver.PathFilterModel = Ext.extend(Tine.widgets.grid.FilterModel, {
    /**
     * @cfg {Tine.Tinebase.Application} app
     */
    app: null,

    /**
     * @cfg {Array} operators allowed operators
     */
    operators: ['equals'],

    /**
     * @cfg {String} field path
     */
    field: 'path',

    /**
     * @cfg {String} defaultOperator default operator, one of <tt>{@link #operators} (defaults to equals)
     */
    defaultOperator: 'equals',

    /**
     * @cfg {String} defaultValue default value (defaults to all)
     */
    defaultValue: '/',

    /**
     * @private
     */
    initComponent: function() {
        this.label = this.app.i18n._('path');

        Tine.Expressodriver.PathFilterModel.superclass.initComponent.call(this);
    },

    /**
     * value renderer
     *
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to
     */
    valueRenderer: function(filter, el) {

        var filterValue = this.defaultValue;
        if(filter.data.value) {
            if(typeof filter.data.value == 'object') {
                filterValue = filter.data.value.path;
            } else {
                filterValue = filter.data.value;
            }
        }

        var value = new Ext.ux.form.ClearableTextField({
            filter: filter,
            width: this.filterValueWidth,
            id: 'tw-ftb-frow-valuefield-' + filter.id,
            renderTo: el,
            value: filterValue,
            emptyText: this.emptyText
        });

        value.on('specialkey', function(field, e){
            if(e.getKey() == e.ENTER){
                this.onFiltertrigger();
            }
        }, this);

        value.origSetValue = value.setValue.createDelegate(value);
        value.setValue = function(value) {
            if (value && value.path) {
                value = value.path;
            }
            else if(Ext.isString(value) && (!value.charAt(0) || value.charAt(0) != '/')) {
                value = '/' + value;
            }

            return this.origSetValue(value);
        };

        return value;
    }
});

Tine.widgets.grid.FilterToolbar.FILTERS['tine.expressodriver.pathfiltermodel'] = Tine.Expressodriver.PathFilterModel;
