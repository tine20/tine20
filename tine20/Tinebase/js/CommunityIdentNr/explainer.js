/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */
require('../../css/communityIdentExplainer.less');

const qtipSatzart = Ext.util.Format.nl2br(`10=Land
20=Regierungsbezirk
30=Region (nur in Baden-Württemberg)
40=Kreis
50=Gemeindeverband
&nbsp;Erläuterung der Kürzel:
&nbsp;&nbsp;Land 08 Baden-Württemberg:
&nbsp;&nbsp;&nbsp;VVG=Vereinbarte Verwaltungsgemeinschaft
&nbsp;&nbsp;&nbsp;GVV=Gemeinde Verwaltungsverband
&nbsp;&nbsp;Land 09 Bayern:
&nbsp;&nbsp;&nbsp;Vgem=Verwaltungsgemeinschaft
60=Gemeinde `);

const qtipTextkennzeichen = Ext.util.Format.nl2br(`41=Kreisfreie Stadt
42=Stadtkreis (nur in Baden-Württemberg)
43=Kreis
44=Landkreis
45=Regionalverband (nur im Saarland)
50=Verbandsfreie Gemeinde
51=Amt
52=Samtgemeinde
53=Verbandsgemeinde
54=Verwaltungsgemeinschaft 
55=Kirchspielslandgemeinde
56=Verwaltungsverband 
58=Erfüllende Gemeinde
60=Markt
61=Kreisfreie Stadt
62=Stadtkreis (nur in Baden-Württemberg)
63=Stadt
64=Kreisangehörige Gemeinde
65=gemeindefreies Gebiet-bewohnt
66=gemeindefreies Gebiet-unbewohnt
67=Große Kreisstadt`);

const qtipLand = Ext.util.Format.nl2br(`01 Schleswig-Holstein
02 Hamburg
03 Niedersachsen
04 Bremen
05 Nordrhein-Westfalen
06 Hessen
07 Rheinland-Pfalz
08 Baden-Württemberg
09 Bayern
10 Saarland
11 Berlin
12 Brandenburg
13 Mecklenburg-Vorpommern
14 Sachsen
15 Sachsen-Anhalt
16 Thüringen`);

const template = new Ext.XTemplate(
    '<table class="x-ars-xplain">' +
    '   <tr>' +
    '       <td class="x-ars-xplain-col x-ars-xplain-label-satzart" rowSpan="2" ext:qtip="' + qtipSatzart + '">Satzart</td>' +
    '       <td class="x-ars-xplain-col x-ars-xplain-label-textkennzeichen" rowSpan="2" ext:qtip="' + qtipTextkennzeichen + '">Textkennzeichen</td>' +
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
    '       <td class="x-ars-xplain-leer" rowspan="2"></td>' +
    '   </tr>' +
    '   <tr>' +
    '       <td class="x-ars-xplain-col x-ars-xplain-label-land" ext:qtip="' + qtipLand + '">Land</td>' +
    '       <td class="x-ars-xplain-col x-ars-xplain-label-rb" ext:qtip="Regierungsbezirk">RB</td>' +
    '       <td class="x-ars-xplain-col x-ars-xplain-label-kreis">Kreis</td>' +
    '       <td class="x-ars-xplain-col x-ars-xplain-label-vb" ext:qtip="Gemeindeverband">VB</td>' +
    '       <td class="x-ars-xplain-col x-ars-xplain-label-gem" ext:qtip="Gemeinde">Gem</td>' +
    '   </tr>' +
    '   <tr>' +
    '       <td class="x-ars-xplain-data x-ars-xplain-value-satzart"><input type="text" class="x-form-text x-form-field" value="{[this.encode(values.satzArt)]}"></td>' +
    '       <td class="x-ars-xplain-data x-ars-xplain-value-textkennzeichen"><input type="text" class="x-form-text x-form-field" value="{[this.encode(values.textkenzeichen)]}"></td>' +
    '       <td class="x-ars-xplain-data x-ars-xplain-value-land"><input type="text" class="x-form-text x-form-field" value="{[this.encode(values.arsLand)]}"></td>' +
    '       <td class="x-ars-xplain-data x-ars-xplain-value-rb"><input type="text" class="x-form-text x-form-field" value="{[this.encode(values.arsRB)]}"></td>' +
    '       <td class="x-ars-xplain-data x-ars-xplain-value-kreis"><input type="text" class="x-form-text x-form-field" value="{[this.encode(values.arsKreis)]}"></td>' +
    '       <td class="x-ars-xplain-data x-ars-xplain-value-vb"><input type="text" class="x-form-text x-form-field" value="{[this.encode(values.arsVB)]}"></td>' +
    '       <td class="x-ars-xplain-data x-ars-xplain-value-gem"><input type="text" class="x-form-text x-form-field" value="{[this.encode(values.arsGem)]}"></td>' +
    '       <td class="x-ars-xplain-data x-ars-xplain-col x-ars-xplain-value-stand" ext:qtip="Letzte Aktuallisierung der Daten">stand: {[this.stand(values)]}</td>' +
    '       <td class="x-ars-xplain-data x-ars-xplain-col x-ars-xplain-show-original-btn" data-attachment="{[this.path(values)]}" data-id="{[this.getId(values)]}" ext:qtip="Zeige original Datensatz"></td>' +
    '   </tr>' +
    '</table>', {
        encode: function (v) {
            return v ? Ext.util.Format.htmlEncode(v) : '';
        },
        path(v) {
            return _.get(v, 'relations[0].related_record.path');
        },
        getId(v) {
            return _.get(v, 'relations[0].related_record.id');
        },
        stand(v) {
            const stand = v.last_modified_time || v.creation_time;
            return stand ? stand.format('d.m.Y') : '-';
        }
    }
).compile();

Ext.onReady(() => {
    Ext.getBody().on('click', function(e) {
        const target = e.getTarget('.x-ars-xplain-show-original-btn', 1, true);
        if (target) {
            const path = target.dom.dataset.attachment;
            const id = target.dom.dataset.id;
            Tine.OnlyOfficeIntegrator.OnlyOfficeEditDialog.openWindow({ recordData: {
                path: path,
                id: id
            }, id: id });
        }
    });
    
});

const ExplainForm = Ext.extend(Ext.form.Field, {
    // fieldLabel: 'Amtlicher Regionalschlüssel',
    hideLabel: true,
    defaultAutoCreate: { tag: 'div' },
    
    onRender() {
        this.supr().onRender.apply(this, arguments);
        template.overwrite(this.el, {});
    },
    
    setValue(value, record) {
        template.overwrite(this.el, record.data);
    }
});
Ext.reg('ars-explain-field', ExplainForm)

Tine.widgets.form.FieldManager.register('Tinebase', 'CommunityIdentNr', 'arsCombined', {
    xtype: 'ars-explain-field',
    height: 75
}, Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG);

export {
    template
}
