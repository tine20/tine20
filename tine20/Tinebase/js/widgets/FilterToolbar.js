/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
Ext.namespace('Tine', 'Tine.widgets');

/**
 * @class Tine.widgets.FilterToolbar
 * @extends Ext.Panel
 * <br>Usage:<br>
     <pre><code>
     tb = new Tine.widgets.FilterToolbar({
         filterModel: [
            {name: 'Full Name', field: 'n_fn', opdefault: 'contains'},
            {name: 'Container', field: 'owner', oprenderer: function() {...}, valrenderer: function() {...}},
            {name: 'Contact', field: 'quicksearch'}
         ],
         defaultFilter: 'quicksearch',
         filters: [
            {field: 'n_fn', operator: 'contains', value: 'Smith'},
            {field: 'owner', operator: 'equals', value: 4}
        ]
     });
    </code></pre>
 * @constructor
 * @param {Object} config
 */
Tine.widgets.FilterToolbar = function(config) {
    Ext.apply(this, config);
    Tine.widgets.FilterToolbar.superclass.constructor.call(this);
    this.addEvents(
      /**
       * @event filtertrigger
       * is fired when user request to update list by filter
       * @param {Tine.widgets.FilterToolbar}
       */
      "filtertrigger"
    );
    
};

Ext.extend(Tine.widgets.FilterToolbar, Ext.Panel, {
    
    /**
     * @cfg {Array} array of possible filters
     */
    filterModel: null,
    /**
     * @cfg {String} fieldname of default filter
     */
    defaultFilter: null,
    
    labels: {
        show: 'Show',                            // _('Show')
        and: 'and',                              // _('and')
        addFilter: 'add new filter',             // _('add new filter')
        saveFilter: 'save filter',               // _('save filter')
        startFilter: 'start search',             // _('start search')
        deleteFilterTip: 'Delete this filter',   // _('Delete this filter')
        resetFiltersTip: 'Reset all filters',    // _('Reset all filters')
        selectField: 'select a field',
        selectOperator: 'select a operator',
        opEquals: 'is equal to',
        opContains: 'contains',
        opGreater: 'is greater than',
        opLess: 'is less than',
        opNot: 'is not',
        opIn: 'is in'
    },
    
    record: Ext.data.Record.create([
        {name: 'field'},
        {name: 'operator'},
        {name: 'value'}
    ]),
    
    frowIdPrefix: 'tw-ftb-frowid-',
    
    /**
     * @private
     */
    initTemplates : function() {
        var ts = this.templates || {};
        if(!ts.master) {
            ts.master = new Ext.Template(
                '<div class="tw-filtertoolbar x-toolbar x-small-editor" hidefocus="true">',
                    '<table style="width: auto;" border="0" cellpadding="0" cellspacing="0">',
                         '{tbody}', 
                     '</table>',
                '</div>'
            );
        }
        if(!ts.filterrow){
            ts.filterrow = new Ext.Template(
                '<tr id="{id}" class="fw-ftb-frow">',
                    '<td class="tw-ftb-frow-prefix">{prefix}</td>',
                    '<td class="tw-ftb-frow-field">{field}</td>',
                    '<td class="tw-ftb-frow-operator">{operator}</td>',
                    '<td class="tw-ftb-frow-value">{value}</td>',
                    '<td class="tw-ftb-frow-deleterow"></td>',
                '</tr>'
            );
        }
        if(!ts.actionrow){
            ts.actionrow = new Ext.Template(
                '<tr class="fw-ftb-actionrow">',
                    '<td></td>',
                    '<td colspan="2" class="tw-ftb-actionsbuttons">' +
                        '<table style="width: auto;" border="0" cellpadding="0" cellspacing="0"><tr>',
                            '<td class="tw-ftb-newfilterbutton"></td>',
                            '<td class="tw-ftb-savefilterbutton"></td>',
                        '</tr></table>',
                    '</td>',
                    '<td class="tw-ftb-searchbutton">{searchbutton}</td>',
                    '<td class="tw-ftb-deletebutton">{deletebutton}</td>',
                '</tr>'
            );
        }

        for(var k in ts){
            var t = ts[k];
            if(t && typeof t.compile == 'function' && !t.compiled){
                t.disableFormats = true;
                t.compile();
            }
        }

        this.templates = ts;
        this.delRowSelector = 'td[class=tw-ftb-deleterow]';
    },
    /**
     * @private
     */
    onRender: function(ct, position) {
        Tine.widgets.FilterToolbar.superclass.onRender.call(this, ct, position);
        
        this.renderTable();
        
        this.filterStore.each(function(filter) {
            this.renderFilterRow(filter);
        }, this);
        if (this.filterStore.getCount() == 1) {
            Ext.getCmp('tw-ftb-frow-deletebutton-' + this.filterStore.getAt(0).id).disable();
        }
        
        this.renderActionsRow();
    },
    /**
     * renders static table
     * @private
     */
    renderTable: function() {
        var ts = this.templates;
        var tbody = '';
        
        this.filterStore.each(function(filter){
            tbody += ts.filterrow.apply({
                id: this.frowIdPrefix + filter.id,
                prefix: this.filterStore.indexOf(filter) == 0 ? this.labels.show : this.labels.and
            });
        }, this);
        
        tbody += ts.actionrow.apply({});
        ts.master.insertFirst(this.el, {tbody: tbody}, true);
    },
    /**
     * renders a single filter row
     * 
     * @param {Ext.data.Record} el representing a filter tr tag
     * @private
     */
    renderFilterRow: function(filter) {
        filter.formFields = {};
        var filterModel = this.fieldStore.getAt(this.fieldStore.find('field', filter.data.field));
        
        var fRow = this.el.child('tr[id='+ this.frowIdPrefix + filter.id + ']');
        // field
        filter.formFields.field = new Ext.form.ComboBox({
            id: 'tw-ftb-frow-fieldcombo-' + filter.id,
            mode: 'local',
            lazyInit: false,
            emptyText: this.labels.selectField,
            forceSelection: true,
            typeAhead: true,
            triggerAction: 'all',
            store: this.fieldStore,
            displayField: 'label',
            valueField: 'field',
            value: filterModel.data.field,
            renderTo: fRow.child('td[class=tw-ftb-frow-field]'),
        });
        // operator
        filter.formFields.operator = new Ext.form.ComboBox({
            id: 'tw-ftb-frow-operatorcombo-' + filter.id,
            mode: 'local',
            lazyInit: false,
            emptyText: this.labels.selectOperator,
            forceSelection: true,
            typeAhead: true,
            triggerAction: 'all',
            store: this.operatorStore,
            displayField: 'label',
            valueField: 'operator',
            value: filter.data.operator ? filter.data.operator : filterModel.data.opdefault,
            renderTo: fRow.child('td[class=tw-ftb-frow-operator]'),
        });
        // value
        filter.formFields.value = new Ext.form.TextField({
            id: 'tw-ftb-frow-valuefield-' + filter.id,
            value: filter.data.value ? filter.data.value : filterModel.data.valdefault,
            renderTo: fRow.child('td[class=tw-ftb-frow-value]'),
        });
        filter.formFields.value.on('specialkey', function(field, e){
             if(e.getKey() == e.ENTER){
                 this.fireEvent('filtertrigger', this);
             }
        }, this);
        
        new Ext.Button({
            id: 'tw-ftb-frow-deletebutton-' + filter.id,
            tooltip: this.labels.deleteFilterTip,
            filter: filter,
            iconCls: 'action_delThisFilter',
            renderTo: fRow.child('td[class=tw-ftb-frow-deleterow]'),
            scope: this,
            handler: function(button) {
                this.deleteFilter(button.filter);
            }
        });
    },
    /**
     * renders the bottom action row (toolbar like)
     * @private
     */
    renderActionsRow: function() {
        new Ext.Button({
            text: this.labels.addFilter,
            iconCls: 'action_addFilter',
            renderTo: this.el.child('td[class=tw-ftb-newfilterbutton]'),
            scope: this,
            handler: this.addFilter
        });
        new Ext.Button({
            disabled: true,
            text: this.labels.saveFilter,
            iconCls: 'action_saveFilter',
            renderTo: this.el.child('td[class=tw-ftb-savefilterbutton]')
        });
        new Ext.Button({
            text: this.labels.startFilter,
            iconCls: 'action_startFilter',
            scope: this,
            handler: function() {
                this.fireEvent('filtertrigger', this);
            },
            renderTo: this.el.child('td[class=tw-ftb-searchbutton]')
        });
        new Ext.Button({
            tooltip: this.labels.resetFiltersTip,
            iconCls: 'action_delAllFilter',
            scope: this,
            handler: this.deleteAllFilters,
            renderTo: this.el.child('td[class=tw-ftb-deletebutton]')
        });
        
    },
    /**
     * @private
     */
    initComponent: function() {
        this.initTemplates();

        // init i18n
        if (typeof _ == 'function') {
            for (text in this.labels) {
                this.labels[text] = _(this.labels[text])
            }
        }
        
        // init filters
        if (this.filters.length < 1) {
            this.filters = [{field: this.defaultFilter}]
        }
        this.filterStore = new Ext.data.JsonStore({
            fields: this.record,
            data: this.filters
        });
        this.fieldStore = new Ext.data.JsonStore({
            fields: ['field', 'label', 'opdefault', 'oprenderer', 'valrenderer'],
            data: this.filterModel
        });
        this.operatorStore = new Ext.data.JsonStore({
            fields: ['operator', 'label'],
            data: [
                {operator: 'contains', label: this.labels.opContains},
                {operator: 'equals',   label: this.labels.opEquals},
                {operator: 'greater',  label: this.labels.opGreater},
                {operator: 'less',     label: this.labels.opLess},
                {operator: 'not',      label: this.labels.opNot},
                //{operator: 'in',       label: this.labels.opIn}
            ]
        });
    },
    /**
     * adds a new filer row
     */
    addFilter: function() {
        var filter = new this.record({
            field: this.defaultFilter
        });
        this.filterStore.add(filter);
        var fRow = this.templates.filterrow.insertBefore(this.el.child('tr[class=fw-ftb-actionrow]'),{
            id: 'tw-ftb-frowid-' + filter.id,
            prefix: this.filterStore.indexOf(filter) == 0 ? this.labels.show : this.labels.and
        }, true);
        this.renderFilterRow(filter);
        Ext.getCmp('tw-ftb-frow-deletebutton-' + this.filterStore.getAt(0).id).enable();
    },
    /**
     * deletes a filter
     * @param {String} id
     */
    deleteFilter: function(filter) {
        var fRow = this.el.child('tr[id=tw-ftb-frowid-' + filter.id + ']');
        // update prefix text
        if (this.filterStore.indexOf(filter) == 0 && this.filterStore.getCount() > 1) {
            fRow.next().child('td[class=tw-ftb-frow-prefix]').update(this.labels.show);
        }
        fRow.remove();
        this.filterStore.remove(this.filterStore.getById(filter.id));
        // single row is not deletable
        if (this.filterStore.getCount() == 1) {
            Ext.getCmp('tw-ftb-frow-deletebutton-' + this.filterStore.getAt(0).id).disable();
        }
        this.fireEvent('filtertrigger', this);
    },
    /**
     * deletes all filters
     */
    deleteAllFilters: function() {
        this.filterStore.each(function(filter) {
            this.deleteFilter(filter);
        },this);
        this.addFilter();
        Ext.getCmp('tw-ftb-frow-deletebutton-' + this.filterStore.getAt(0).id).disable();
        this.fireEvent('filtertrigger', this);
    },
    
    getFilter: function() {
        var filters = [];
        this.filterStore.each(function(filter) {
            var line = {};
            for (formfield in filter.formFields) {
                line[formfield] = filter.formFields[formfield].getValue();
            }
            filters.push(line);
        }, this);
        return filters;
    }
    
});

Ext.reg('tinewidgetsfiltertoolbar', Tine.widgets.FilterToolbar);