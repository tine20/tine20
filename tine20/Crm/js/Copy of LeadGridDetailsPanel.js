/**
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
 */
 
Ext.namespace('Tine.Crm');

/**
 * the details panel (shows lead details)
 * 
 * @class Tine.Felamimail.GridDetailsPanel
 * @extends Tine.Tinebase.widgets.grid.DetailsPanel
 */
Tine.Crm.ContactGridDetailsPanel = Ext.extend(Tine.Tinebase.widgets.grid.DetailsPanel, {
    
    il8n: null,
    felamimail: false,
    
    /**
     * init
     */
    initComponent: function() {

        // check if felamimail is installed and user has run right
        if (Tine.Felamimail && Tine.Tinebase.common.hasRight('run', 'Felamimail')) {
            this.felamimail = true;
        }

        // init templates
        this.initTemplate();
        this.initDefaultTemplate();
        
        this.supr().initComponent.call(this);
    },

    /**
     * add on click event after render
     */
    afterRender: function() {
        this.supr().afterRender.apply(this, arguments);
        
        if (this.felamimail) {
            this.body.on('click', this.onClick, this);
        }
    },
    
    /**
     * update template
     * 
     * @param {Tine.Tinebase.data.Record} record
     * @param {Mixed} body
     */
    updateDetails: function(record, body) {
        // don't mess up record
        var data = {};
        Ext.apply(data, record.data);
        
        data.customer = this.getContactData(record, 'CUSTOMER') || {};
        data.partner = this.getContactData(record, 'PARTNER') || {};
        
        var leadtype = Tine.Crm.LeadType.getStore().getById(data.leadtype_id);
        data.leadtype = leadtype ? leadtype.get('leadtype') : '';
        
        var leadsource = Tine.Crm.LeadSource.getStore().getById(data.leadsource_id);
        data.leadsource = leadsource ? leadsource.get('leadsource') : '';
        
        this.tpl.overwrite(body, data);
    },
    
    /**
     * 
     * @param       {Record} lead
     * @param       {String} type (CUSTOMER|PARTNER)
     */
    getContactData: function(lead, type) {
        var data = lead.get('relations');
        
        if( Ext.isArray(data) && data.length > 0) {
            var index = 0;
            
            // get correct relation type from data (contact) array
            while (index < data.length && data[index].type != type) {
                index++;
            }
            if (data[index]) {
                return data[index].related_record;
            }
        }
    },
    
    /**
     * init default template
     */
    initDefaultTemplate: function() {
        
        this.defaultTpl = new Ext.XTemplate(
            '<div class="preview-panel-timesheet-nobreak">',    
                '<!-- Preview leads -->',
                '<div class="preview-panel preview-panel-timesheet-left">',
                    '<div class="bordercorner_1"></div>',
                    '<div class="bordercorner_2"></div>',
                    '<div class="bordercorner_3"></div>',
                    '<div class="bordercorner_4"></div>',
                    '<div class="preview-panel-declaration">' + this.il8n.n_('Lead', 'Leads', 50) + '</div>',
                    '<div class="preview-panel-timesheet-leftside preview-panel-left">',
                        '<span class="preview-panel-bold">',
                            this.il8n._('Select lead') + '<br/>',
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
     * init single lead template (this.tpl)
     */
    initTemplate: function() {
        this.tpl = new Ext.XTemplate(
            '<div class="crm-leadgrid-detailspanel">',
                '<!-- status details -->',
                '<div class="preview-panel preview-panel-left crm-leadgrid-detailspanel-status">',
                    '<div class="bordercorner_1"></div>',
                    '<div class="bordercorner_2"></div>',
                    '<div class="bordercorner_3"></div>',
                    '<div class="bordercorner_4"></div>',
                    '<div class="crm-leadgrid-detailspanel-status-inner preview-panel-left">',
                        '<span class="preview-panel-symbolcompare">', this.il8n._('Start'), '</span>{[this.encode(values.start, "date")]}<br/>',
                        '<span class="preview-panel-symbolcompare">', this.il8n._('Estimated end'), '</span>{[this.encode(values.end_scheduled, "date")]}<br/>',
                        '<span class="preview-panel-symbolcompare">', this.il8n._('Leadtype'), '</span>{[this.encode(values.leadtype)]}<br/>',
                        '<span class="preview-panel-symbolcompare">', this.il8n._('Leadsource'), '</span>{[this.encode(values.leadsource)]}<br/>',
                        '<!-- ',
                        '<br />',
                        '<span class="preview-panel-symbolcompare">', this.il8n._('Open tasks'), '</span>{values.numtasks}<br/>',
                        '<span class="preview-panel-symbolcompare">', this.il8n._('Tasks status'), '</span>{values.tasksstatushtml}<br/>',
                        '-->',
                    '</div>',
                '</div>',
            
                '<!-- contact details -->',
                '<div class="preview-panel preview-panel-left crm-leadgrid-detailspanel-contacts">',                
                    '<div class="bordercorner_1"></div>',
                    '<div class="bordercorner_2"></div>',
                    '<div class="bordercorner_3"></div>',
                    '<div class="bordercorner_4"></div>',
                    '<div class="crm-leadgrid-detailspanel-contact">',
                        '<div class="preview-panel-declaration">', this.il8n._('Customer'), '</div>',
                        '<span class="preview-panel-symbolcompare">', this.il8n._('Phone'), '</span>{[this.encode(values.customer.tel_work)]}<br/>',
                        '<span class="preview-panel-symbolcompare">', this.il8n._('Mobile'), '</span>{[this.encode(values.customer.tel_cell)]}<br/>',
                        '<span class="preview-panel-symbolcompare">', this.il8n._('Fax'), '</span>{[this.encode(values.customer.tel_fax)]}<br/>',
                        '<span class="preview-panel-symbolcompare">', this.il8n._('E-Mail'), 
                            '</span>{[this.getMailLink(values.customer.email, ', this.felamimail, ')]}<br/>',
                        '<span class="preview-panel-symbolcompare">', this.il8n._('Web') + '</span><a href="{[this.encode(values.customer.url)]}" target="_blank">{[this.encode(values.customer.url, "shorttext")]}</a><br/>',
                    '</div>',
                    '<div class="crm-leadgrid-detailspanel-contact">',
                        '<div class="preview-panel-declaration">' + this.il8n._('Partner') + '</div>',
                        '<span class="preview-panel-symbolcompare">' + this.il8n._('Phone') + '</span>{[this.encode(values.partner.tel_work)]}<br/>',
                        '<span class="preview-panel-symbolcompare">' + this.il8n._('Mobile') + '</span>{[this.encode(values.partner.tel_cell)]}<br/>',
                        '<span class="preview-panel-symbolcompare">' + this.il8n._('Fax') + '</span>{[this.encode(values.partner.tel_fax)]}<br/>',
                        '<span class="preview-panel-symbolcompare">' + this.il8n._('E-Mail'), 
                            '</span>{[this.getMailLink(values.partner.email, ' + this.felamimail + ')]}<br/>',
                        '<span class="preview-panel-symbolcompare">' + this.il8n._('Web') + '</span><a href="{[this.encode(values.partner.url)]}" target="_blank">{[this.encode(values.partner.url, "shorttext")]}</a><br/>',
                    '</div>',
                '</div>',

                '<!-- description -->',
                '<div class="preview-panel-description preview-panel-left" ext:qtip="{[this.encode(values.description)]}">',
                    '<div class="bordercorner_gray_1"></div>',
                    '<div class="bordercorner_gray_2"></div>',
                    '<div class="bordercorner_gray_3"></div>',
                    '<div class="bordercorner_gray_4"></div>',
                    '<div class="preview-panel-declaration">' + this.il8n._('Description') + '</div>',
                    '{[this.encode(values.description)]}',
                '</div>',
                '</div>',
                //  '{[this.getTags(values.tags)]}',
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
                                case 'date' :
                                    value = Tine.Tinebase.common.dateRenderer(value);
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
                 * get email link
                 */
                getMailLink: function(email, felamimail) {
                    if (! email) {
                        return '';
                    }
                    
                    var link = (felamimail) ? '#' : 'mailto:' + email;
                    var id = Ext.id() + ':' + email;
                    
                    return '<a href="' + link + '" class="tinebase-email-link" id="' + id + '">'
                        + Ext.util.Format.ellipsis(email, 18); + '</a>';
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
