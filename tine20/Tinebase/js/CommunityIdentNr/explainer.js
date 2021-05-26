/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */
require('../../css/communityIdentExplainer.less');

const template = new Ext.XTemplate(
    '<table class="x-ars-xplain">' +
    '   <tr>' +
    '       <td class="x-ars-xplain-col x-ars-xplain-label-satzart" rowSpan="2">Satzart</td>' +
    '       <td class="x-ars-xplain-col x-ars-xplain-label-textkennzeichen" rowSpan="2">Textkennzeichen</td>' +
    '       <td class="x-ars-xplain-regionalschluessel" colspan="5">' +
    '           <table class="x-ars-xplain-group">' +
    '               <tr>' +
    '                   <td>&nbsp;</td>' +
    '                   <td rowspan="2">Amtlicher Regionalschlüssel</td>' +
    '                   <td>&nbsp;</td>' +
    '               </tr>' +
    '               <tr>' +
    '                   <td class="x-ars-xplain-leftcorner">&nbsp;</td>' +
    '                   <td class="x-ars-xplain-rightcorner">&nbsp;</td>' +
    '               </tr>' +
    '           </table>' + 
    '       </td>' +
    '       <td class="x-ars-xplain-leer" rowspan="2"></td>' +
    '   </tr>' +
    '   <tr>' +
    '       <td class="x-ars-xplain-col x-ars-xplain-label-land">Land</td>' +
    '       <td class="x-ars-xplain-col x-ars-xplain-label-rb">RB</td>' +
    '       <td class="x-ars-xplain-col x-ars-xplain-label-kreis">Kreis</td>' +
    '       <td class="x-ars-xplain-col x-ars-xplain-label-vb">VB</td>' +
    '       <td class="x-ars-xplain-col x-ars-xplain-label-gem">Gem</td>' +
    '   </tr>' +
    '   <tr>' +
    '       <td class="x-ars-xplain-data x-ars-xplain-value-satzart"><input type="text" class="x-form-text x-form-field" value="{satzArt}"></td>' +
    '       <td class="x-ars-xplain-data x-ars-xplain-value-textkennzeichen"><input type="text" class="x-form-text x-form-field" value="{textkenzeichen}"></td>' +
    '       <td class="x-ars-xplain-data x-ars-xplain-value-land"><input type="text" class="x-form-text x-form-field" value="{arsLand}"></td>' +
    '       <td class="x-ars-xplain-data x-ars-xplain-value-rb"><input type="text" class="x-form-text x-form-field" value="{arsRB}"></td>' +
    '       <td class="x-ars-xplain-data x-ars-xplain-value-kreis"><input type="text" class="x-form-text x-form-field" value="{arsKreis}"></td>' +
    '       <td class="x-ars-xplain-data x-ars-xplain-value-vb"><input type="text" class="x-form-text x-form-field" value="{arsVB}"></td>' +
    '       <td class="x-ars-xplain-data x-ars-xplain-value-gem"><input type="text" class="x-form-text x-form-field" value="{arsGem}"></td>' +
    '       <td class="x-ars-xplain-data x-ars-xplain-col x-ars-xplain-value-stand">stand: {stand}</td>' +
    '   </tr>' +
    '</table>'
).compile();

const ExplainForm = Ext.extend(Ext.form.Field, {
    // fieldLabel: 'Amtlicher Regionalschlüssel',
    hideLabel: true,
    defaultAutoCreate: { tag: 'div' },
    
    onRender() {
        this.supr().onRender.apply(this, arguments);
        template.overwrite(this.el, {});
    },
    
    setValue(value, record) {
        const stand = record.data.last_modified_time || record.data.creation_time;
        template.overwrite(this.el, _.assign(_.mapValues(record.data, Ext.util.Format.htmlEncode), {
            stand: stand.format('d.m.Y')
        }));
    }
});
Ext.reg('ars-explain-field', ExplainForm)

Tine.widgets.form.FieldManager.register('Tinebase', 'CommunityIdentNr', 'arsCombined', {
    xtype: 'ars-explain-field',
    height: 60
}, Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG);
