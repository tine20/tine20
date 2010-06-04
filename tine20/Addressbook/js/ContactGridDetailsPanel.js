/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
 * TODO         add preference for sending mails with felamimail or mailto?
 */
 
Ext.ns('Tine.Addressbook');

/**
 * the details panel (shows contact details)
 * 
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.ContactGridDetailsPanel
 * @extends     Tine.widgets.grid.DetailsPanel
 */
Tine.Addressbook.ContactGridDetailsPanel = Ext.extend(Tine.widgets.grid.DetailsPanel, {
    
    il8n: null,
    felamimail: false,
    
    /**
     * init
     */
    initComponent: function() {

        // init templates
        this.initTemplate();
        this.initDefaultTemplate();
        
        Tine.Felamimail.GridDetailsPanel.superclass.initComponent.call(this);
    },

    /**
     * add on click event after render
     */
    afterRender: function() {
        Tine.Felamimail.GridDetailsPanel.superclass.afterRender.apply(this, arguments);
        
        if (this.felamimail === true) {
            this.body.on('click', this.onClick, this);
        }
    },
    
    /**
     * init default template
     */
    initDefaultTemplate: function() {
        
        this.defaultTpl = new Ext.XTemplate(
            '<div class="preview-panel-timesheet-nobreak">',    
                '<!-- Preview contacts -->',
                '<div class="preview-panel preview-panel-timesheet-left">',
                    '<div class="bordercorner_1"></div>',
                    '<div class="bordercorner_2"></div>',
                    '<div class="bordercorner_3"></div>',
                    '<div class="bordercorner_4"></div>',
                    '<div class="preview-panel-declaration">' + this.il8n._('Contacts') + '</div>',
                    '<div class="preview-panel-timesheet-leftside preview-panel-left">',
                        '<span class="preview-panel-bold">',
                            this.il8n._('Select contact') + '<br/>',
                            '<br/>',
                            '<br/>',
                            '<br/>',
                        '</span>',
                    '</div>',
                    '<div class="preview-panel-timesheet-rightside preview-panel-left">',
                        '<span class="preview-panel-nonbold">',
                            '<br/>',
                            '<br/>',
                            '<br/>',
                            '<br/>',
                        '</span>',
                    '</div>',
                '</div>',
                '<!-- Preview xxx -->',
                '<div class="preview-panel-timesheet-right">',
                    '<div class="bordercorner_gray_1"></div>',
                    '<div class="bordercorner_gray_2"></div>',
                    '<div class="bordercorner_gray_3"></div>',
                    '<div class="bordercorner_gray_4"></div>',
                    '<div class="preview-panel-declaration"></div>',
                    '<div class="preview-panel-timesheet-leftside preview-panel-left">',
                        '<span class="preview-panel-bold">',
                            '<br/>',
                            '<br/>',
                            '<br/>',
                            '<br/>',
                        '</span>',
                    '</div>',
                    '<div class="preview-panel-timesheet-rightside preview-panel-left">',
                        '<span class="preview-panel-nonbold">',
                            '<br/>',
                            '<br/>',
                            '<br/>',
                            '<br/>',
                        '</span>',
                    '</div>',
                '</div>',
            '</div>'        
        );
    },
    
    /**
     * init single contact template (this.tpl)
     */
    initTemplate: function() {
        this.tpl = new Ext.XTemplate(
            '<tpl for=".">',
                '<div class="preview-panel-adressbook-nobreak">',
                '<div class="preview-panel-left">',                
                    '<!-- Preview image -->',
                    '<div class="preview-panel preview-panel-left preview-panel-image">',
                        '<div class="bordercorner_1"></div>',
                        '<div class="bordercorner_2"></div>',
                        '<div class="bordercorner_3"></div>',
                        '<div class="bordercorner_4"></div>',
                        '<img src="{[this.getImageUrl(values.jpegphoto, 90, 113)]}"/>',
                    '</div>',
                
                    '<!-- Preview office -->',
                    '<div class="preview-panel preview-panel-office preview-panel-left">',                
                        '<div class="bordercorner_1"></div>',
                        '<div class="bordercorner_2"></div>',
                        '<div class="bordercorner_3"></div>',
                        '<div class="bordercorner_4"></div>',
                        '<div class="preview-panel-declaration">' + this.il8n._('Company') + '</div>',
                        '<div class="preview-panel-address preview-panel-left">',
                            '<span class="preview-panel-bold">{[this.encode(values.org_name, "mediumtext")]}{[this.encode(values.org_unit, "prefix", " / ")]}</span><br/>',
                            '{[this.encode(values.adr_one_street)]}<br/>',
                            '{[this.encode(values.adr_one_postalcode, " ")]}{[this.encode(values.adr_one_locality)]}<br/>',
                            '{[this.encode(values.adr_one_region, " / ")]}{[this.encode(values.adr_one_countryname, "country")]}<br/>',
                        '</div>',
                        '<div class="preview-panel-contact preview-panel-right">',
                            '<span class="preview-panel-symbolcompare">' + this.il8n._('Phone') + '</span>{[this.encode(values.tel_work)]}<br/>',
                            '<span class="preview-panel-symbolcompare">' + this.il8n._('Mobile') + '</span>{[this.encode(values.tel_cell)]}<br/>',
                            '<span class="preview-panel-symbolcompare">' + this.il8n._('Fax') + '</span>{[this.encode(values.tel_fax)]}<br/>',
                            '<span class="preview-panel-symbolcompare">' + this.il8n._('E-Mail') 
                                + '</span>{[this.getMailLink(values.email, ' + this.felamimail + ')]}<br/>',
                            '<span class="preview-panel-symbolcompare">' + this.il8n._('Web') + '</span><a href="{[this.encode(values.url)]}" target="_blank">{[this.encode(values.url, "shorttext")]}</a><br/>',
                        '</div>',
                    '</div>',
                '</div>',

                '<!-- Preview privat -->',
                '<div class="preview-panel preview-panel-privat preview-panel-left">',                
                    '<div class="bordercorner_1"></div>',
                    '<div class="bordercorner_2"></div>',
                    '<div class="bordercorner_3"></div>',
                    '<div class="bordercorner_4"></div>',
                    '<div class="preview-panel-declaration">' + this.il8n._('Private') + '</div>',
                    '<div class="preview-panel-address preview-panel-left">',
                        '<span class="preview-panel-bold">{[this.encode(values.n_fn)]}</span><br/>',
                        '{[this.encode(values.adr_two_street)]}<br/>',
                        '{[this.encode(values.adr_two_postalcode, " ")]}{[this.encode(values.adr_two_locality)]}<br/>',
                        '{[this.encode(values.adr_two_region, " / ")]}{[this.encode(values.adr_two_countryname, "country")]}<br/>',
                    '</div>',
                    '<div class="preview-panel-contact preview-panel-right">',
                        '<span class="preview-panel-symbolcompare">' + this.il8n._('Phone') + '</span>{[this.encode(values.tel_home)]}<br/>',
                        '<span class="preview-panel-symbolcompare">' + this.il8n._('Mobile') + '</span>{[this.encode(values.tel_cell_private)]}<br/>',
                        '<span class="preview-panel-symbolcompare">' + this.il8n._('Fax') + '</span>{[this.encode(values.tel_fax_home)]}<br/>',
                        '<span class="preview-panel-symbolcompare">' + this.il8n._('E-Mail') 
                            + '</span>{[this.getMailLink(values.email_home, ' + this.felamimail + ')]}<br/>',
                        '<span class="preview-panel-symbolcompare">' + this.il8n._('Web') + '</span><a href="{[this.encode(values.url)]}" target="_blank">{[this.encode(values.url_home, "shorttext")]}</a><br/>',
                    '</div>',                
                '</div>',
                
                '<!-- Preview info -->',
                '<div class="preview-panel-description preview-panel-left" ext:qtip="{[this.encode(values.note)]}">',
                    '<div class="bordercorner_gray_1"></div>',
                    '<div class="bordercorner_gray_2"></div>',
                    '<div class="bordercorner_gray_3"></div>',
                    '<div class="bordercorner_gray_4"></div>',
                    '<div class="preview-panel-declaration">' + this.il8n._('Info') + '</div>',
                    '{[this.encode(values.note, "longtext")]}',
                '</div>',
                '</div>',
                //  '{[this.getTags(values.tags)]}',
            '</tpl>',
            {
                /**
                 * encode
                 */
                encode: function(value, type, prefix) {
                    //var metrics = Ext.util.TextMetrics.createInstance('previewPanel');
                    if (value) {
                        if (type) {
                            switch (type) {
                                case 'country':
                                    value = Locale.getTranslationData('CountryList', value);
                                    break;
                                case 'longtext':
                                    value = Ext.util.Format.ellipsis(value, 135);
                                    break;
                                case 'mediumtext':
                                    value = Ext.util.Format.ellipsis(value, 30);
                                    break;
                                case 'shorttext':
                                    //console.log(metrics.getWidth(value));
                                    value = Ext.util.Format.ellipsis(value, 18);
                                    break;
                                case 'prefix':
                                    if (prefix) {
                                        value = prefix + value;
                                    }
                                    break;
                                default:
                                    value += type;
                            }                           
                        }
                        value = Ext.util.Format.htmlEncode(value);
                        return Ext.util.Format.nl2br(value);
                    } else {
                        return '';
                    }
                },
                
                /**
                 * get tags
                 * 
                 * TODO make it work
                 */
                getTags: function(value) {
                    var result = '';
                    for (var i=0; i<value.length; i++) {
                        result += value[i].name + ' ';
                    }
                    return result;
                },
                
                /**
                 * get image url
                 */
                getImageUrl: function(url, width, height) {
                    if (url.match(/&/)) {
                        url = Ext.ux.util.ImageURL.prototype.parseURL(url);
                        url.width = width;
                        url.height = height;
                        url.ratiomode = 0;
                    }
                    return url;
                },

                /**
                 * get email link
                 */
                getMailLink: function(email, felamimail) {
                    if (! email) {
                        return '';
                    }
                    
                    var link = (felamimail === true) ? '#' : 'mailto:' + email;
                    var id = Ext.id() + ':' + email;
                    
                    return '<a href="' + link + '" class="tinebase-email-link" id="' + id + '">'
                        + Ext.util.Format.ellipsis(email, 18) + '</a>';
                }
            }
        );
    },
    
    /**
     * on click for compose mail
     * 
     * @param {} e
     * 
     * TODO check if account is configured?
     * TODO generalize that
     */
    onClick: function(e) {
        var target = e.getTarget('a[class=tinebase-email-link]');
        if (target) {
            var email = target.id.split(':')[1];
            var defaults = Tine.Felamimail.Model.Message.getDefaultData();
            defaults.to = [email];
            defaults.body = Tine.Felamimail.getSignature();
            
            var record = new Tine.Felamimail.Model.Message(defaults, 0);
            var popupWindow = Tine.Felamimail.MessageEditDialog.openWindow({
                record: record
            });
        }
    }
});
