/*
 * Tine 2.0
 * contacts combo box and store
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Addressbook');

Tine.Addressbook.ListSearchCombo = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {

    /**
     * @cfg {Boolean} groupOnly
     */
    groupOnly: false,

    departmentOnly: false,
    addPathFilter: true,

    //private
    initComponent: function(){
        this.app = Tine.Tinebase.appMgr.get('Addressbook');
        this.recordClass = Tine.Addressbook.Model.List;

        this.emptyText = this.emptyText || (this.groupOnly ?
            this.app.i18n._('Search for system groups ...') :
            this.app.i18n._('Search for groups ...')
        );

        this.supr().initComponent.call(this);
    },

    /**
     * use beforequery to set query filter
     *
     * @param {Event} qevent
     */
    onBeforeQuery: function(qevent){
        Tine.Addressbook.SearchCombo.superclass.onBeforeQuery.apply(this, arguments);

        const filter = this.store.baseParams.filter;

        if (this.addPathFilter) {

            const queryFilter = _.find(filter, {field: 'query'});
            const pathFilter = {field: 'path', operator: 'contains', value: queryFilter.value};

            _.remove(filter, queryFilter);

            filter.push({
                condition: "OR", filters: [
                    queryFilter,
                    pathFilter
                ]
            });
        }

        if (this.groupOnly) {
            filter.push({field: 'type', operator: 'equals', value: 'group'});
        }
        
        if (this.departmentOnly) {
            filter.push({field: 'list_type', operator: 'equals', value: 'department'});
        }
    },

    /**
     * init template
     * @private
     */
    initTemplate: function() {
        if (! this.tpl) {
            this.tpl = new Ext.XTemplate('<tpl for=".">',
                    '<div class="x-combo-list-item">',
                        '<table>',
                            '<tr>',
                                '<td style="min-width: 20px;">{[Tine.Addressbook.ListGridPanel.listTypeRenderer(null, null, values)]}</td>',
                                '<td width="100%">{[Tine.Tinebase.EncodingHelper.encode(values.name)]}</td>',
                            '</tr>',
                        '</table>',
                        '{[Tine.widgets.path.pathsRenderer(values.paths, this.getLastQuery())]}',
                    '</div>',
                '</tpl>', {
                    getLastQuery: this.getLastQuery.createDelegate(this)
                }
            );
        }
    }
});

Tine.widgets.form.RecordPickerManager.register('Addressbook', 'List', Tine.Addressbook.ListSearchCombo);
