/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */
import { template } from './explainer'
Ext.ns('Tine.Tinebase');

Tine.Tinebase.CommunityIdentNrPicker = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {
    
    allowBlank: true,
    itemSelector: 'div.search-item',
    minListWidth: 450,
    resizable: true,
    listWidth: 650,

    // private
    initComponent: function(){
        this.app = Tine.Tinebase.appMgr.get('Tinebase');
        this.recordClass = Tine.Tinebase.Model.CommunityIdentNr;
        this.emptyText = this.app.i18n._('Search for Community Identification Numbers ...');

        this.initTemplate();
        Tine.Tinebase.CommunityIdentNrPicker.superclass.initComponent.call(this);
    },

    /**
     * use beforequery to set query filter
     *
     * @param {Event} qevent
     */
    onBeforeQuery: function(qevent){
        Tine.Tinebase.CommunityIdentNrPicker.superclass.onBeforeQuery.apply(this, arguments);

        const filter = this.store.baseParams.filter;
        const queryFilter = _.find(filter, {field: 'query'});
        const nameFilter = {field: 'gemeindenamen', operator: 'startswith', value: qevent.query};
        const numberFilter = {field: 'arsCombined', operator: 'startswith', value: qevent.query};

        _.remove(filter, queryFilter);

        filter.push({
            condition: "OR", filters: [
                nameFilter,
                numberFilter
            ]
        });
    },

    setValue : function(v){
        const ret = Tine.Tinebase.CommunityIdentNrPicker.superclass.setValue.apply(this, arguments);
        var el = this.getEl();
        if (el && this.selectedRecord) {
            el.set({qtip: Ext.util.Format.htmlEncode(`${this.selectedRecord.data.gemeindenamen} (${this.recordClass.satzArt2Text(this.selectedRecord.data.satzArt)})`) +
                    '<br /><br />' + template.apply(this.selectedRecord.data), hide: 'user'});
        }
    },

    /**
     * init template
     * @private
     */
    initTemplate: function() {
        // Custom rendering Template
        const satzArt2Text = this.recordClass.satzArt2Text;

        if (! this.tpl) {
            this.tpl = new Ext.XTemplate(
                '<tpl for="."><div class="search-item x-ars-xplain-search-item">',
                    '<table cellspacing="0" cellpadding="2" border="0" style="font-size: 11px;" width="100%">',
                        '<tr>',
                            '<td style="width:30%">{[this.explainer(values)]}</td>',
                            '<td class="x-ars-xplain-search-item-text" style="width:40%">{[this.encode(values.gemeindenamen)]}</td>',
                            '<td class="x-ars-xplain-search-item-text" style="width:30%">{[this.getType(values.satzArt)]}</td>',
                        '</tr>',
                    '</table>',
                '</div></tpl>',
                {
                    explainer: function(values) { return template.apply(values) },
                    encode: function(values) { return Ext.util.Format.htmlEncode(values) },
                    getType: function(satzArt) {
                        return satzArt2Text(satzArt)
                    }
                }
            );
        }
    }
});

Ext.reg('communityidentnrpicker', Tine.Tinebase.CommunityIdentNrPicker);
Tine.widgets.form.RecordPickerManager.register('Tinebase', 'CommunityIdentNr', Tine.Tinebase.CommunityIdentNrPicker);
