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

Tine.widgets.FilterToolbar = function(config) {
    Ext.apply(this, config);
    Tine.widgets.FilterToolbar.superclass.constructor.call(this);
};

Ext.extend(Tine.widgets.FilterToolbar, Ext.Panel, {
    
    frowIdPrefix: 'tw-ftb-frowid-',
    /**
     * @private
     */
    initTemplates : function() {
        var ts = this.templates || {};
        if(!ts.master) {
            ts.master = new Ext.Template(
                '<div class="tw-filtertoolbar x-toolbar x-small-editor" hidefocus="true">',
                    '<table style="width: auto;" border="1" cellpadding="0" cellspacing="0">',
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
        
        this.store.each(function(filter) {
            this.renderFilterRow(filter);
        }, this);
        
        this.renderActionsRow();
    },
    /**
     * renders static table
     * @private
     */
    renderTable: function() {
        var ts = this.templates;
        var tbody = '';
        
        this.store.each(function(filter){
            tbody += ts.filterrow.apply({
                id: this.frowIdPrefix + filter.id,
                prefix: this.store.indexOf(filter) == 0 ? 'Show' : 'and'
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
        var fRow = this.el.child('tr[id='+ this.frowIdPrefix + filter.id + ']')
        new Ext.Button({
            id: 'tw-ftb-frow-deletebutton-' + filter.id,
            tooltip: 'Delete this filter',
            filter: filter,
            iconCls: 'action_delThisFilter',
            renderTo: fRow.child('td[class=tw-ftb-frow-deleterow]'),
            scope: this,
            handler: function(button) {
                this.deleteFilter(button.filter);
            }
        });
    },
    renderActionsRow: function() {
        new Ext.Button({
            text: 'add new filter',
            iconCls: 'action_addFilter',
            renderTo: this.el.child('td[class=tw-ftb-newfilterbutton]'),
            scope: this,
            handler: this.addFilter
        });
        new Ext.Button({
            text: 'save filter',
            iconCls: 'action_saveFilter',
            renderTo: this.el.child('td[class=tw-ftb-savefilterbutton]')
        });
        new Ext.Button({
            text: 'start search',
            iconCls: 'action_startFilter',
            renderTo: this.el.child('td[class=tw-ftb-searchbutton]')
        });
        new Ext.Button({
            //text: 'save filter',
            iconCls: 'action_delAllFilter',
            renderTo: this.el.child('td[class=tw-ftb-deletebutton]')
        });
        
    },
    /**
     * @private
     */
    initComponent: function() {
        this.initTemplates();
        this.record = Ext.data.Record.create([
            {name: 'field'},
            {name: 'operator'},
            {name: 'value'}
        ]);
        this.store = new Ext.data.JsonStore({
            fields: this.record,
            data: [
                {field: 'Name', operator: 'contains', value: 'Smith'},
                {field: 'Country', operator: 'equals', value: 'Germany'}
            ]
        });
    },
    /**
     * adds a new filer row
     */
    addFilter: function() {
        var filter = new this.record({});
        this.store.add(filter);
        var fRow = this.templates.filterrow.insertBefore(this.el.child('tr[class=fw-ftb-actionrow]'),{
            id: 'tw-ftb-frowid-' + filter.id,
            prefix: this.store.indexOf(filter) == 0 ? 'Show' : 'and'
        }, true);
        this.renderFilterRow(filter);
        Ext.getCmp('tw-ftb-frow-deletebutton-' + this.store.getAt(0).id).enable();
    },
    /**
     * deletes a filter
     * @param {String} id
     */
    deleteFilter: function(filter) {
        var fRow = this.el.child('tr[id=tw-ftb-frowid-' + filter.id + ']');
        // update prefix text
        if (this.store.indexOf(filter) == 0 && this.store.getCount() > 1) {
            fRow.next().child('td[class=tw-ftb-frow-prefix]').update('Show');
        }
        fRow.remove();
        this.store.remove(this.store.getById(filter.id));
        // single row is not deletable
        if (this.store.getCount() == 1) {
            Ext.getCmp('tw-ftb-frow-deletebutton-' + this.store.getAt(0).id).disable();
        }
    }
    
});
Ext.reg('tinewidgetsfiltertoolbar', Tine.widgets.FilterToolbar);