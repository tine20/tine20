/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Tinebase');

Tine.Tinebase.CommunityIdentNrPicker = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {
    
    allowBlank: true,
    itemSelector: 'div.search-item',
    minListWidth: 450,

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

    /**
     * init template
     * @private
     */
    initTemplate: function() {
        // Custom rendering Template
        if (! this.tpl) {
            this.tpl = new Ext.XTemplate(
                '<tpl for="."><div class="search-item">',
                    '<table cellspacing="0" cellpadding="2" border="0" style="font-size: 11px;" width="100%">',
                        '<tr>',
                            '<td style="width:30%">{[this.encode(values.arsCombined)]}</td>',
                            '<td style="width:40%">{[this.encode(values.gemeindenamen)]}</td>',
                            '<td style="width:30%">{[this.getType(values.satzArt)]}</td>',
                        '</tr>',
                    '</table>',
                '</div></tpl>',
                {
                    encode: function(values) { return Ext.util.Format.htmlEncode(values) },
                    getType: function(values) {
                        switch (values) {
                            case 10: return 'Land';
                                break;
                            case 20: return 'Regierungsbezirk';
                                break;
                            case 30: return 'Region (nur in Baden-Württemberg)';
                                break;
                            case 40: return 'Kreis';
                                break;
                            case 50: return 'Gemeindeverband';
                                break;
                            case 60: return 'Gemeinde';
                                break;
                            default: return '';
                        }
                    }
                }
            );
        }
    }
});

Ext.reg('communityidentnrpicker', Tine.Tinebase.CommunityIdentNrPicker);
Tine.widgets.form.RecordPickerManager.register('Tinebase', 'CommunityIdentNr', Tine.Tinebase.CommunityIdentNrPicker);
