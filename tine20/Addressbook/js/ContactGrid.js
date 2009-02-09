/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Addressbook');

/**
 * Contact grid panel
 */
Tine.Addressbook.ContactGridPanel = Ext.extend(Tine.Tinebase.widgets.app.GridPanel, {
    // model generics
    recordClass: Tine.Addressbook.Model.Contact,
    
    // grid specific
    defaultSortInfo: {field: 'n_fileas', direction: 'ASC'},
    gridConfig: {
        loadMask: true,
        autoExpandColumn: 'n_fileas'
    },
    
    /**
     * inits this cmp
     * @privagte
     */
    initComponent: function() {
        this.recordProxy = Tine.Addressbook.contactBackend;
        
        this.actionToolbarItems = this.getToolbarItems();
        this.gridConfig.columns = this.getColumns();
        this.filterToolbar = this.getFilterToolbar();
        this.detailsPanel = this.getDetailsPanel();
        
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
        
        Tine.Addressbook.ContactGridPanel.superclass.initComponent.call(this);
        
        this.grid.getSelectionModel().on('selectionchange', this.onSelectionchange, this);
    },
    
    /**
     * returns filter toolbar
     * @private
     */
    getFilterToolbar: function() {
        return new Tine.widgets.grid.FilterToolbar({
            filterModels: [
                {label: this.app.i18n._('Contact'),    field: 'query',    operators: ['contains']},
                {label: this.app.i18n._('First Name'), field: 'n_given' },
                {label: this.app.i18n._('Last Name'),  field: 'n_family'},
                {label: this.app.i18n._('Company'),    field: 'org_name'},
                {label: this.app.i18n._('Job Title'),    field: 'title'},
                {label: this.app.i18n._('Job Role'),    field: 'role'},
                new Tine.widgets.tags.TagFilter({app: this.app}),
                //{label: this.app.i18n._('Birthday'),    field: 'bday', valueType: 'date'},
                {label: this.app.i18n._('Street') + ' (' + this.app.i18n._('Company Address') + ')',      field: 'adr_one_street', defaultOperator: 'equals'},
                {label: this.app.i18n._('Postal Code') + ' (' + this.app.i18n._('Company Address') + ')', field: 'adr_one_postalcode', defaultOperator: 'equals'},
                {label: this.app.i18n._('City') + '  (' + this.app.i18n._('Company Address') + ')',       field: 'adr_one_locality'},
                {label: this.app.i18n._('Street') + ' (' + this.app.i18n._('Private Address') + ')',      field: 'adr_two_street', defaultOperator: 'equals'},
                {label: this.app.i18n._('Postal Code') + ' (' + this.app.i18n._('Private Address') + ')', field: 'adr_two_postalcode', defaultOperator: 'equals'},
                {label: this.app.i18n._('City') + '  (' + this.app.i18n._('Private Address') + ')',       field: 'adr_two_locality'}
             ],
             defaultFilter: 'query',
             filters: []
        });
    },    
    
    /**
     * returns columns
     * @private
     */
    getColumns: function(){
        return [
            { resizable: true, sortable: true, id: 'tid', header: this.app.i18n._('Type'), dataIndex: 'tid', width: 30, renderer: this.contactTidRenderer.createDelegate(this) },
            { resizable: true, sortable: true, id: 'n_family', header: this.app.i18n._('Last Name'), dataIndex: 'n_family', hidden: true },
            { resizable: true, sortable: true, id: 'n_given', header: this.app.i18n._('First Name'), dataIndex: 'n_given', width: 80, hidden: true },
            { resizable: true, sortable: true, id: 'n_fn', header: this.app.i18n._('Full Name'), dataIndex: 'n_fn', hidden: true },
            { resizable: true, sortable: true, id: 'n_fileas', header: this.app.i18n._('Display Name'), dataIndex: 'n_fileas'},
            { resizable: true, sortable: true, id: 'org_name', header: this.app.i18n._('Company'), dataIndex: 'org_name', width: 200 },
            { resizable: true, sortable: true, id: 'org_unit', header: this.app.i18n._('Unit'), dataIndex: 'org_unit' , hidden: true },
            { resizable: true, sortable: true, id: 'title', header: this.app.i18n._('Job Title'), dataIndex: 'title', hidden: true },
            { resizable: true, sortable: true, id: 'role', header: this.app.i18n._('Job Role'), dataIndex: 'role', hidden: true },
            { resizable: true, sortable: true, id: 'room', header: this.app.i18n._('Room'), dataIndex: 'room', hidden: true },
            { resizable: true, sortable: true, id: 'adr_one_street', header: this.app.i18n._('Street'), dataIndex: 'adr_one_street', hidden: true },
            { resizable: true, sortable: true, id: 'adr_one_locality', header: this.app.i18n._('City'), dataIndex: 'adr_one_locality', width: 150, hidden: false },
            { resizable: true, sortable: true, id: 'adr_one_region', header: this.app.i18n._('Region'), dataIndex: 'adr_one_region', hidden: true },
            { resizable: true, sortable: true, id: 'adr_one_postalcode', header: this.app.i18n._('Postalcode'), dataIndex: 'adr_one_postalcode', hidden: true },
            { resizable: true, sortable: true, id: 'adr_one_countryname', header: this.app.i18n._('Country'), dataIndex: 'adr_one_countryname', hidden: true },
            { resizable: true, sortable: true, id: 'adr_two_street', header: this.app.i18n._('Street (private)'), dataIndex: 'adr_two_street', hidden: true },
            { resizable: true, sortable: true, id: 'adr_two_locality', header: this.app.i18n._('City (private)'), dataIndex: 'adr_two_locality', hidden: true },
            { resizable: true, sortable: true, id: 'adr_two_region', header: this.app.i18n._('Region (private)'), dataIndex: 'adr_two_region', hidden: true },
            { resizable: true, sortable: true, id: 'adr_two_postalcode', header: this.app.i18n._('Postalcode (private)'), dataIndex: 'adr_two_postalcode', hidden: true },
            { resizable: true, sortable: true, id: 'adr_two_countryname', header: this.app.i18n._('Country (private)'), dataIndex: 'adr_two_countryname', hidden: true },
            { resizable: true, sortable: true, id: 'email', header: this.app.i18n._('Email'), dataIndex: 'email', width: 150},
            { resizable: true, sortable: true, id: 'tel_work', header: this.app.i18n._('Phone'), dataIndex: 'tel_work', hidden: false },
            { resizable: true, sortable: true, id: 'tel_cell', header: this.app.i18n._('Mobile'), dataIndex: 'tel_cell', hidden: false },
            { resizable: true, sortable: true, id: 'tel_fax', header: this.app.i18n._('Fax'), dataIndex: 'tel_fax', hidden: true },
            { resizable: true, sortable: true, id: 'tel_car', header: this.app.i18n._('Car phone'), dataIndex: 'tel_car', hidden: true },
            { resizable: true, sortable: true, id: 'tel_pager', header: this.app.i18n._('Pager'), dataIndex: 'tel_pager', hidden: true },
            { resizable: true, sortable: true, id: 'tel_home', header: this.app.i18n._('Phone (private)'), dataIndex: 'tel_home', hidden: true },
            { resizable: true, sortable: true, id: 'tel_fax_home', header: this.app.i18n._('Fax (private)'), dataIndex: 'tel_fax_home', hidden: true },
            { resizable: true, sortable: true, id: 'tel_cell_private', header: this.app.i18n._('Mobile (private)'), dataIndex: 'tel_cell_private', hidden: true },
            { resizable: true, sortable: true, id: 'email_home', header: this.app.i18n._('Email (private)'), dataIndex: 'email_home', hidden: true },
            { resizable: true, sortable: true, id: 'url', header: this.app.i18n._('Web'), dataIndex: 'url', hidden: true },
            { resizable: true, sortable: true, id: 'url_home', header: this.app.i18n._('URL (private)'), dataIndex: 'url_home', hidden: true },
            { resizable: true, sortable: true, id: 'note', header: this.app.i18n._('Note'), dataIndex: 'note', hidden: true },
            { resizable: true, sortable: true, id: 'tz', header: this.app.i18n._('Timezone'), dataIndex: 'tz', hidden: true },
            { resizable: true, sortable: true, id: 'geo', header: this.app.i18n._('Geo'), dataIndex: 'geo', hidden: true },
            { resizable: true, sortable: true, id: 'bday', header: this.app.i18n._('Birthday'), dataIndex: 'bday', hidden: true }
        ];
    },
    
    /**
     * return additional tb items
     */
    getToolbarItems: function(){
        this.actions_exportContact = new Ext.Action({
            requiredGrant: 'readGrant',
            allowMultiple: true,
            text: this.app.i18n._('export as pdf'),
            disabled: true,
            handler: this.onExportPdf,
            iconCls: 'action_exportAsPdf',
            scope: this
        });

        this.actions_callContact = new Ext.Action({
            requiredGrant: 'readGrant',
            id: 'Addressbook_Contacts_CallContact',
            text: this.app.i18n._('call contact'),
            disabled: true,
            handler: this.onCallContact,
            iconCls: 'PhoneIconCls',
            menu: new Ext.menu.Menu({
                id: 'Addressbook_Contacts_CallContact_Menu'
            }),
            scope: this
        });
        
        var items = [
            new Ext.Toolbar.Separator(),
            this.actions_exportContact
        ];
        
        if (Tine.Phone && Tine.Tinebase.common.hasRight('run', 'Phone')) {
            items.push(new Ext.Toolbar.MenuButton(this.actions_callContact));
        }
        
        return items;
    },
    
    /**
     * updates call menu
     * @param {} sm
     */
    onSelectionchange: function(sm) {
        var rowCount = sm.getCount();
        if (rowCount == 1 && Tine.Phone && Tine.Tinebase.common.hasRight('run', 'Phone')) {
            var callMenu = Ext.menu.MenuMgr.get('Addressbook_Contacts_CallContact_Menu');
            callMenu.removeAll();
            
            this.actions_callContact.setDisabled(true);
            
            var contact = sm.getSelected();
            if(!Ext.isEmpty(contact.data.tel_work)) {
                callMenu.add({
                   id: 'Addressbook_Contacts_CallContact_Work', 
                   text: this.app.i18n._('Work') + ' ' + contact.data.tel_work + '',
                   scope: this,
                   handler: this.onCallContact
                });
                this.actions_callContact.setDisabled(false);
            }
            if(!Ext.isEmpty(contact.data.tel_home)) {
                callMenu.add({
                   id: 'Addressbook_Contacts_CallContact_Home', 
                   text: this.app.i18n._('Home') + ' ' + contact.data.tel_home + '',
                   scope: this,
                   handler: this.onCallContact
                });
                this.actions_callContact.setDisabled(false);
            }
            if(!Ext.isEmpty(contact.data.tel_cell)) {
                callMenu.add({
                   id: 'Addressbook_Contacts_CallContact_Cell', 
                   text: this.app.i18n._('Cell') + ' ' + contact.data.tel_cell + '',
                   scope: this,
                   handler: this.onCallContact
                });
                this.actions_callContact.setDisabled(false);
            }
            if(!Ext.isEmpty(contact.data.tel_cell_private)) {
                callMenu.add({
                   id: 'Addressbook_Contacts_CallContact_CellPrivate', 
                   text: this.app.i18n._('Cell private') + ' ' + contact.data.tel_cell_private + '',
                   scope: this,
                   handler: this.onCallContact
                });
                this.actions_callContact.setDisabled(false);
            }
        }
    },
        
    /**
     * calls a contact
     */
    onCallContact: function(btn) {
        var number;

        var contact = this.grid.getSelectionModel().getSelected();

        switch(btn.getId()) {
            case 'Addressbook_Contacts_CallContact_Work':
                number = contact.data.tel_work;
                break;
            case 'Addressbook_Contacts_CallContact_Home':
                number = contact.data.tel_home;
                break;
            case 'Addressbook_Contacts_CallContact_Cell':
                number = contact.data.tel_cell;
                break;
            case 'Addressbook_Contacts_CallContact_CellPrivate':
                number = contact.data.tel_cell_private;
                break;
            default:
                if(!Ext.isEmpty(contact.data.tel_work)) {
                    number = contact.data.tel_work;
                } else if (!Ext.isEmpty(contact.data.tel_cell)) {
                    number = contact.data.tel_cell;
                } else if (!Ext.isEmpty(contact.data.tel_cell_private)) {
                    number = contact.data.tel_cell_private;
                } else if (!Ext.isEmpty(contact.data.tel_home)) {
                    number = contact.data.tel_work;
                }
                break;
        }

        Tine.Phone.dialNumber(number);
    },
    
    /**
     * tid renderer
     * 
     * @private
     * @return {String} HTML
     */
    contactTidRenderer: function(data, cell, record) {
        switch(record.get('container_id').type) {
            case 'internal':
                return "<img src='images/oxygen/16x16/actions/user-female.png' width='12' height='12' alt='contact' ext:qtip='" + this.app.i18n._("Internal Contacts") + "'/>";
            default:
                return "<img src='images/oxygen/16x16/actions/user.png' width='12' height='12' alt='contact'/>";
        }
    },
    
    /**
     * returns details panel
     * 
     * @private
     * @return {Tine.widgets.grid.DetailsPanel}
     */
    getDetailsPanel: function() {
        return new Tine.widgets.grid.DetailsPanel({
            gridpanel: this,
            
            defaultTpl: new Ext.XTemplate(
                '<div class="preview-panel-timesheet-nobreak">',    
                    '<!-- Preview contacts -->',
                    '<div class="preview-panel preview-panel-timesheet-left">',
                        '<div class="bordercorner_1"></div>',
                        '<div class="bordercorner_2"></div>',
                        '<div class="bordercorner_3"></div>',
                        '<div class="bordercorner_4"></div>',
                        '<div class="preview-panel-declaration">contacts</div>',
                        '<div class="preview-panel-timesheet-leftside preview-panel-left">',
                            '<span class="preview-panel-bold">',
                                this.app.i18n._('Select contact') + '<br/>',
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
            ),
            
            tpl: new Ext.XTemplate(
            '<tpl for=".">',
                '<div class="preview-panel-adressbook-nobreak">',
                '<div class="preview-panel-left">',                
                    '<!-- Preview image -->',
                    '<div class="preview-panel preview-panel-left preview-panel-image">',
                        '<div class="bordercorner_1"></div>',
                        '<div class="bordercorner_2"></div>',
                        '<div class="bordercorner_3"></div>',
                        '<div class="bordercorner_4"></div>',
                        '<img src="{jpegphoto}"/>',
                    '</div>',
                
                    '<!-- Preview office -->',
                    '<div class="preview-panel preview-panel-office preview-panel-left">',                
                        '<div class="bordercorner_1"></div>',
                        '<div class="bordercorner_2"></div>',
                        '<div class="bordercorner_3"></div>',
                        '<div class="bordercorner_4"></div>',
                        '<div class="preview-panel-declaration">buero</div>',
                        '<div class="preview-panel-address preview-panel-left">',
                            '<span class="preview-panel-bold">{[this.encode(values.org_name, "mediumtext")]}{[this.encode(values.org_unit, "prefix", " / ")]}</span><br/>',
                            '{[this.encode(values.adr_one_street)]}<br/>',
                            '{[this.encode(values.adr_one_postalcode, " ")]}{[this.encode(values.adr_one_locality)]}<br/>',
                            '{[this.encode(values.adr_one_region, " / ")]}{[this.encode(values.adr_one_countryname, "country")]}<br/>',
                        '</div>',
                        '<div class="preview-panel-contact preview-panel-right">',
                            '<span class="preview-panel-symbolcompare">' + this.app.i18n._('Phone') + '</span>{[this.encode(values.tel_work)]}<br/>',
                            '<span class="preview-panel-symbolcompare">' + this.app.i18n._('Mobile') + '</span>{[this.encode(values.tel_cell)]}<br/>',
                            '<span class="preview-panel-symbolcompare">' + this.app.i18n._('Fax') + '</span>{[this.encode(values.tel_fax)]}<br/>',
                            '<span class="preview-panel-symbolcompare">' + this.app.i18n._('E-Mail') + '</span><a href="mailto:{[this.encode(values.email)]}">{[this.encode(values.email, "shorttext")]}</a><br/>',
                            '<span class="preview-panel-symbolcompare">' + this.app.i18n._('Web') + '</span><a href="{[this.encode(values.url)]}" target="_blank">{[this.encode(values.url, "shorttext")]}</a><br/>',
                        /*
                            '<img src="images/oxygen/16x16/apps/kcall.png"/> 
                            '<img src="images/oxygen/16x16/apps/phone.png"/> 
                            '<img src="images/oxygen/16x16/apps/printer.png"/>
                            '<img src="images/oxygen/16x16/apps/kontact-mail.png"/>
                            '<img src="images/oxygen/16x16/apps/network.png"/>
                        */
                        '</div>',
                    '</div>',
                '</div>',

                '<!-- Preview privat -->',
                '<div class="preview-panel preview-panel-privat preview-panel-left">',                
                    '<div class="bordercorner_1"></div>',
                    '<div class="bordercorner_2"></div>',
                    '<div class="bordercorner_3"></div>',
                    '<div class="bordercorner_4"></div>',
                    '<div class="preview-panel-declaration">privat</div>',
                    '<div class="preview-panel-address preview-panel-left">',
                        '<span class="preview-panel-bold">{[this.encode(values.n_fn)]}</span><br/>',
                        '{[this.encode(values.adr_two_street)]}<br/>',
                        '{[this.encode(values.adr_two_postalcode, " ")]}{[this.encode(values.adr_two_locality)]}<br/>',
                        '{[this.encode(values.adr_two_region, " / ")]}{[this.encode(values.adr_two_countryname, "country")]}<br/>',
                    '</div>',
                    '<div class="preview-panel-contact preview-panel-right">',
                        '<span class="preview-panel-symbolcompare">' + this.app.i18n._('Phone') + '</span>{[this.encode(values.tel_home)]}<br/>',
                        '<span class="preview-panel-symbolcompare">' + this.app.i18n._('Mobile') + '</span>{[this.encode(values.tel_cell_private)]}<br/>',
                        '<span class="preview-panel-symbolcompare">' + this.app.i18n._('Fax') + '</span>{[this.encode(values.tel_fax_home)]}<br/>',
                        '<span class="preview-panel-symbolcompare">' + this.app.i18n._('E-Mail') + '</span><a href="mailto:{[this.encode(values.email)]}">{[this.encode(values.email_home, "shorttext")]}</a><br/>',
                        '<span class="preview-panel-symbolcompare">' + this.app.i18n._('Web') + '</span><a href="{[this.encode(values.url)]}" target="_blank">{[this.encode(values.url_home, "shorttext")]}</a><br/>',
                        /*
                        '<!-- <img src="images/oxygen/16x16/apps/kcall.png"/>--> <span class="preview-panel-symbolcompare">phone</span>0404040<br/>',
                        '<!-- <img src="images/oxygen/16x16/apps/phone.png"/>--> <span class="preview-panel-symbolcompare">mobil</span>040404<br/>',
                        '<!-- <img src="images/oxygen/16x16/apps/printer.png"/>--> <span class="preview-panel-symbolcompare">fax</span>04040<br/>',
                        '<!-- <img src="images/oxygen/16x16/apps/kontact-mail.png"/>--> <span class="preview-panel-symbolcompare">mail</span><a href="mailto:mai@me.dd">mai@me.dd</a><br/>',
                        '<!-- <img src="images/oxygen/16x16/apps/network.png"/>--> <span class="preview-panel-symbolcompare">web</span><a href="{[this.encode(values.url_home)]}" target="_blank">{[this.encode(values.url_home, "shorttext")]}</a><br/>',
                        */
                    '</div>',                
                '</div>',
                
                '<!-- Preview info -->',
                '<div class="preview-panel-description preview-panel-left" ext:qtip="{[this.encode(values.note)]}">',
                    '<div class="bordercorner_gray_1"></div>',
                    '<div class="bordercorner_gray_2"></div>',
                    '<div class="bordercorner_gray_3"></div>',
                    '<div class="bordercorner_gray_4"></div>',
                    '<div class="preview-panel-declaration">info</div>',
                    '{[this.encode(values.note, "longtext")]}',
                '</div>',
                '</div>',
                //  '{[this.getTags(values.tags)]}',
            '</tpl>',
            {
                encode: function(value, type, prefix) {
                    //var metrics = Ext.util.TextMetrics.createInstance('previewPanel');
                    if (value) {
                        if (type) {
                            switch (type) {
                                case 'country':
                                    value = Locale.getTranslationData('Territory', value);
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
                getTags: function(value) {
                    var result = '';
                    for (var i=0; i<value.length; i++) {
                        result += value[i].name + ' ';
                    }
                    return result;
                }
            })
        });
    }
});
