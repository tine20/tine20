/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
Ext.namespace('Tine.widgets', 'Tine.widgets.grid');

/**
 * @class Tine.widgets.grid.FilterToolbar
 * @extends Ext.Panel
 * <br>Usage:<br>
     <pre><code>
     tb = new Tine.widgets.grid.FilterToolbar({
         filterModels: [
            {name: 'Full Name', field: 'n_fn', defaultOperator: 'contains'},
            {name: 'Container', field: 'owner', operatorRenderer: function() {...}, valueRenderer: function() {...}},
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
Tine.widgets.grid.FilterToolbar = function(config) {
    Ext.apply(this, config);
    Tine.widgets.grid.FilterToolbar.superclass.constructor.call(this);
    
    this.addEvents(
      /**
       * @event filtertrigger
       * is fired when user request to update list by filter
       * @param {Tine.widgets.grid.FilterToolbar}
       */
      'filtertrigger',
      /**
       * @event bodyresize
       * Fires after the FilterToolbar has been resized.
       * @param {Tine.widgets.grid.FilterToolbar} the FilterToolbar which has been resized.
       * @param {Number} width The Panel's new width.
       * @param {Number} height The Panel's new height.
       */
      'bodyresize'
    );
    
};

Ext.extend(Tine.widgets.grid.FilterToolbar, Ext.Panel, {
    
    /**
     * @cfg {Array} array of filter models (possible filters in this toolbar)
     */
    filterModels: null,
    
    /**
     * @cfg {String} fieldname of default filter
     */
    defaultFilter: null,
    
    border: false,
    
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
                    '<td width="110px" class="tw-ftb-frow-operator">{operator}</td>',
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
                    '<td align="right" class="tw-ftb-searchbutton">{searchbutton}</td>',
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
        Tine.widgets.grid.FilterToolbar.superclass.onRender.call(this, ct, position);
        
        this.renderTable();
        
        this.filterStore.each(function(filter) {
            this.renderFilterRow(filter);
        }, this);
        this.onFilterRowsChange();
        
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
                prefix: this.filterStore.indexOf(filter) == 0 ? _('Show') : _('and')
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
        var filterModel = this.getFilterModel(filter.get('field'));

        var fRow = this.el.child('tr[id='+ this.frowIdPrefix + filter.id + ']');
        // field
        filter.formFields.field = new Ext.form.ComboBox({
            filter: filter,
            width: 300,
            id: 'tw-ftb-frow-fieldcombo-' + filter.id,
            mode: 'local',
            lazyInit: false,
            emptyText: _('select a field'),
            forceSelection: true,
            typeAhead: true,
            triggerAction: 'all',
            store: this.fieldStore,
            displayField: 'label',
            valueField: 'field',
            value: filterModel.field,
            renderTo: fRow.child('td[class=tw-ftb-frow-field]'),
        });
        filter.formFields.field.on('select', function(combo, newRecord, newKey) {
            if (combo.value != combo.filter.get('field')) {
                this.onFieldChange(combo.filter, combo.value);
            }
        }, this);
        
        // operator
        filter.formFields.operator = filterModel.operatorRenderer(filter, fRow.child('td[class=tw-ftb-frow-operator]'));
        
        // value
        filter.formFields.value = filterModel.valueRenderer(filter, fRow.child('td[class=tw-ftb-frow-value]'));
        
        new Ext.Button({
            id: 'tw-ftb-frow-deletebutton-' + filter.id,
            tooltip: _('Delete this filter'),
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
            text: _('add new filter'),
            iconCls: 'action_addFilter',
            renderTo: this.el.child('td[class=tw-ftb-newfilterbutton]'),
            scope: this,
            handler: this.addFilter
        });
        new Ext.Button({
            disabled: true,
            text: _('save filter'),
            iconCls: 'action_saveFilter',
            renderTo: this.el.child('td[class=tw-ftb-savefilterbutton]')
        });
        new Ext.Button({
            ctCls: 'x-btn-over',
            text: _('start search'),
            iconCls: 'action_startFilter',
            scope: this,
            handler: function() {
                this.onFiltertrigger();
            },
            renderTo: this.el.child('td[class=tw-ftb-searchbutton]')
        });
        new Ext.Button({
            tooltip: _('Delete all filters'),
            iconCls: 'action_delAllFilter',
            scope: this,
            handler: this.deleteAllFilters,
            renderTo: this.el.child('td[class=tw-ftb-deletebutton]')
        });
        
    },
    /**
     * @private
     */
    onBodyresize: function() {
        if (! this.supressEvents) {
            var size = this.getSize();
            this.fireEvent('bodyresize', this, size.width, size.height);
        }
    },
    /**
     * called  when a filter action is to be triggered (start new search)
     * @private
     */
    onFiltertrigger: function() {
        if (! this.supressEvents) {
            this.fireEvent('filtertrigger', this);
        }
    },
    /**
     * called on field change of a filter row
     * @private
     */
    onFieldChange: function(filter, newField) {
        filter.set('field', newField);
        
        filter.formFields.operator.destroy();
        filter.formFields.value.destroy();
        
        var filterModel = this.getFilterModel(filter.get('field'));
        var fRow = this.el.child('tr[id='+ this.frowIdPrefix + filter.id + ']');
        
        var opEl = fRow.child('td[class=tw-ftb-frow-operator]');
        var valEl = fRow.child('td[class=tw-ftb-frow-value]');
        
        filter.formFields.operator = filterModel.operatorRenderer(filter, opEl);
        filter.formFields.value = filterModel.valueRenderer(filter, valEl);
    },
    
    /**
     * @private
     */
    initComponent: function() {
        this.initTemplates();
        
        // init filters
        if (this.filters.length < 1) {
            this.filters = [{field: this.defaultFilter}]
        }
        this.filterStore = new Ext.data.JsonStore({
            fields: this.record,
            data: this.filters
        });

        
        // init filter models
        this.filterModelMap = {};
        for (var i=0; i<this.filterModels.length; i++) {
            var fm = this.filterModels[i];
            if (! fm.isFilterModel) {
                var modelConfig = fm;
                fm = new Tine.widgets.grid.FilterModel(modelConfig);
            }
            // store reference in internal map
            this.filterModelMap[fm.field] = fm;
            
            // register trigger events
            fm.on('filtertrigger', this.onFiltertrigger, this);
        }
        
        // init filter selection
        this.fieldStore = new Ext.data.JsonStore({
            fields: ['field', 'label'],
            data: this.filterModels
        });
    },
    /**
     * called when a filter row gets added/deleted
     * @private
     */
    onFilterRowsChange: function() {
        var firstFilter = this.filterStore.getAt(0);
        if (firstFilter) {
            var firstDeleteButton = Ext.getCmp('tw-ftb-frow-deletebutton-' + firstFilter.id);
            if (this.filterStore.getCount() == 1) {
                firstDeleteButton.disable();
            } else {
                firstDeleteButton.enable();
            }
        }
    },
    
    /**
     * returns filterModel
     * 
     * @param {String} fieldName
     * @return {Tine.widgets.grid.FilterModel}
     */
    getFilterModel: function(fieldName) {
        return this.filterModelMap[fieldName];   
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
            prefix: this.filterStore.indexOf(filter) == 0 ? _('Show') : _('and')
        }, true);
        this.renderFilterRow(filter);
        this.onFilterRowsChange();
        this.onBodyresize();
    },
    /**
     * resets a filter
     * @param {Ext.Record} filter to reset
     */
    resetFilter: function(filter) {
        
    },
    /**
     * deletes a filter
     * @param {Ext.Record} filter to delete
     */
    deleteFilter: function(filter) {
        var fRow = this.el.child('tr[id=tw-ftb-frowid-' + filter.id + ']');
        // update prefix text
        if (this.filterStore.indexOf(filter) == 0 && this.filterStore.getCount() > 1) {
            fRow.next().child('td[class=tw-ftb-frow-prefix]').update(_('Show'));
        }
        fRow.remove();
        this.filterStore.remove(this.filterStore.getById(filter.id));

        this.onFiltertrigger();
        this.onFilterRowsChange();
        this.onBodyresize();
    },
    /**
     * deletes all filters
     */
    deleteAllFilters: function() {
        this.supressEvents = true;
        this.filterStore.each(function(filter) {
            this.deleteFilter(filter);
        },this);
        this.supressEvents = false;
        this.addFilter();
        this.onFilterRowsChange();
        this.onFiltertrigger();
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

Ext.reg('tinewidgetsfiltertoolbar', Tine.widgets.grid.FilterToolbar);