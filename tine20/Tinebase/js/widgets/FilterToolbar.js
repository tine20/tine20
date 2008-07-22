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
    
    /**
     * @private
     */
    initTemplates : function() {
        var ts = this.templates || {};
        if(!ts.master) {
            ts.master = new Ext.Template(
                '<div class="filterGrid" hidefocus="true">',
                    '<table style="width: auto;" border="1" cellpadding="0" cellspacing="0">',
                         '{tbody}', 
                     '</table>',
                '</div>'
            );
        }
        if(!ts.filterrow){
            ts.filterrow = new Ext.Template(
                '<tr id="{id}" class="fw-fgrid-frow">',
                    '<td class="tw-fgrid-frow-prefix">{prefix}</td>',
                    '<td class="tw-fgrid-frow-field">{field}</td>',
                    '<td class="tw-fgrid-frow-operator">{operator}</td>',
                    '<td class="tw-fgrid-frow-value">{value}</td>',
                    '<td class="tw-fgrid-frow-deleterow"></td>',
                '</tr>'
            );
        }
        if(!ts.actionrow){
            ts.actionrow = new Ext.Template(
                '<tr>',
                    '<td></td>',
                    '<td colspan="2" class="tw-fgrid-actionsbuttons">{actionsbuttons}</td>',
                    '<td class="tw-fgrid-searchbutton">{searchbutton}</td>',
                    '<td class="tw-fgrid-deletebutton">{deletebutton}</td>',
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
        this.delRowSelector = 'td[class=tw-fgrid-deleterow]';
    },
    /**
     * @private
     */
    onRender: function(ct, position) {
        Tine.widgets.FilterToolbar.superclass.onRender.call(this, ct, position);
        this.renderTable();
        this.renderFilters();
        //this.renderActionsRow();
    },
    /**
     * renders static table
     * @private
     */
    renderTable: function() {
        var ts = this.templates;
        var tbody = '';
        var cnt = 0;
        
        this.store.each(function(filter){
            tbody += ts.filterrow.apply({
                id: 'tw-fgrid-frowid-' + filter.id,
                prefix: cnt == 0 ? 'Show' : 'and'
            });
            cnt++;
        }, this);
        
        tbody += ts.actionrow.apply({
            actionsbuttons: 'actionsbuttons',
            searchbutton: 'searchbutton',
            deletebutton: 'deletebutton'
        });
        ts.master.insertFirst(this.el, {tbody: tbody}, true);
    },
    /**
     * @private
     */
    renderFilters: function() {
        this.el.select('tr[class=fw-fgrid-frow]').each(function(fRow){
            this.renderFilterRow(fRow);
        },this);
    },
    /**
     * renders a single filter row
     * 
     * @param {Ext.Element} el representing a filter tr tag
     * @private
     */
    renderFilterRow: function(fRow) {
        new Ext.Button({
            text: 'delete',
            renderTo: fRow.child('td[class=tw-fgrid-frow-deleterow]')
        });
    },
    /**
     * @private
     */
    initComponent: function() {
        this.initTemplates();
        this.store = new Ext.data.JsonStore({
            fields: ['field', 'operator', 'value'],
            data: [
                {field: 'Name', operator: 'contains', value: 'Smith'},
                {field: 'Country', operator: 'equals', value: 'Germany'}
            ]
        });
    },
    /**
     * 
     */
    addFilter: function() {
        var fRow = this.templates.filterrow.insertBefore(this.el.child('tr:last'),{}, true)
        //var actionRow = this.el.child('tr:last');
        
        //console.log(actionRow);
    }
    
});
Ext.reg('tinewidgetsfiltertoolbar', Tine.widgets.FilterToolbar);