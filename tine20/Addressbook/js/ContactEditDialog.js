/*
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/*global Ext, Tine*/

Ext.ns('Tine.Addressbook');

/**
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.ContactEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * Addressbook Edit Dialog <br>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Addressbook.ContactEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * parse address button
     * @type Ext.Button 
     */
    parseAddressButton: null,
    
    windowNamePrefix: 'ContactEditWindow_',
    appName: 'Addressbook',
    recordClass: Tine.Addressbook.Model.Contact,
    showContainerSelector: true,
    multipleEdit: true,
    
    getFormItems: function () {
        if (Tine.Tinebase.registry.get('mapPanel') && Tine.widgets.MapPanel) {
            this.mapPanel = new Tine.Addressbook.MapPanel({
                listeners: {
                    'add': this.addToDisableOnEditMultiple,
                    scope: this
                },
                layout: 'fit',
                title: this.app.i18n._('Map'),
                disabled: (Ext.isEmpty(this.record.get('adr_one_lon')) || Ext.isEmpty(this.record.get('adr_one_lat'))) && (Ext.isEmpty(this.record.get('adr_two_lon')) || Ext.isEmpty(this.record.get('adr_two_lat')))
            });
        } else {
            this.mapPanel = new Ext.Panel({
                layout: 'fit',
                title: this.app.i18n._('Map'),
                disabled: true,
                html: ''
            });
        }
        
        return {
            xtype: 'tabpanel',
            border: false,
            plain: true,
            activeTab: 0,
            items: [{
                title: this.app.i18n.n_('Contact', 'Contacts', 1),
                border: false,
                frame: true,
                layout: 'border',
                items: [{
                    region: 'center',
                    layout: 'border',
                    items: [{
                        xtype: 'fieldset',
                        region: 'north',
                        autoHeight: true,
                        title: this.app.i18n._('Personal Information'),
                        items: [{
                            xtype: 'panel',
                            layout: 'fit',
                            width: 90,
                            height: 120,
                            style: {
                                position: 'absolute',
                                right: '10px',
                                top: Ext.isGecko ? '7px' : '19px',
                                'z-index': 100
                            },
                            items: [new Ext.ux.form.ImageField({
                                name: 'jpegphoto',
                                width: 90,
                                height: 120
                            })]
                        }, {
                            xtype: 'columnform',
                            items: [[
                                new Tine.Tinebase.widgets.keyfield.ComboBox({
                                fieldLabel: this.app.i18n._('Salutation'),
                                name: 'salutation',
                                app: 'Addressbook',
                                keyFieldName: 'contactSalutation',
                                value: '',
                                columnWidth: 0.35,
                                listeners: {
                                    scope: this,
                                    'select': function (combo, record, index) {
                                        var jpegphoto = this.getForm().findField('jpegphoto');
                                        // set new empty photo depending on chosen salutation only if user doesn't have own image
                                        if (Ext.isEmpty(jpegphoto.getValue()) && ! Ext.isEmpty(record.json.image)) {
                                            jpegphoto.setDefaultImage(record.json.image);
                                        }
                                    }
                                }
                            }), {
                                columnWidth: 0.65,
                                fieldLabel: this.app.i18n._('Title'), 
                                name: 'n_prefix',
                                maxLength: 64
                            }, {
                                width: 100,
                                hidden: true
                            }], [{
                                columnWidth: 0.35,
                                fieldLabel: this.app.i18n._('First Name'), 
                                name: 'n_given',
                                maxLength: 64
                            }, {
                                columnWidth: 0.30,
                                fieldLabel: this.app.i18n._('Middle Name'), 
                                name: 'n_middle',
                                maxLength: 64
                            }, {
                                columnWidth: 0.35,
                                fieldLabel: this.app.i18n._('Last Name'), 
                                name: 'n_family',
                                maxLength: 255
                            }, {
                                width: 100,
                                hidden: true
                            }], [{
                                columnWidth: 0.65,
                                xtype: 'mirrortextfield',
                                fieldLabel: this.app.i18n._('Company'), 
                                name: 'org_name',
                                maxLength: 255
                            }, {
                                columnWidth: 0.35,
                                fieldLabel: this.app.i18n._('Unit'), 
                                name: 'org_unit',
                                maxLength: 64
                            }, {
                                width: 100,
                                hidden: true
                            }], [{
                                columnWidth: 0.65,
                                xtype: 'combo',
                                fieldLabel: this.app.i18n._('Display Name'),
                                name: 'n_fn',
                                disabled: true
                            }, {
                                columnWidth: 0.35,
                                fieldLabel: this.app.i18n._('Job Title'),
                                name: 'title',
                                maxLength: 64
                            }, {
                                width: 100,
                                xtype: 'extuxclearabledatefield',
                                fieldLabel: this.app.i18n._('Birthday'),
                                name: 'bday'
                            }]/* move to seperate tab, [{
                                columnWidth: .4,
                                fieldLabel: this.app.i18n._('Suffix'), 
                                name:'n_suffix'
                            }, {
                                columnWidth: .4,
                                fieldLabel: this.app.i18n._('Job Role'), 
                                name:'role'
                            }, {
                                columnWidth: .2,
                                fieldLabel: this.app.i18n._('Room'), 
                                name:'room'
                            }]*/]
                        }]
                    }, {
                        xtype: 'fieldset',
                        region: 'center',
                        title: this.app.i18n._('Contact Information'),
                        autoScroll: true,
                        items: [{
                            xtype: 'columnform',
                            items: [[{
                                fieldLabel: this.app.i18n._('Phone'), 
                                labelIcon: 'images/oxygen/16x16/apps/kcall.png',
                                name: 'tel_work',
                                maxLength: 40
                            }, {
                                fieldLabel: this.app.i18n._('Mobile'),
                                labelIcon: 'images/oxygen/16x16/devices/phone.png',
                                name: 'tel_cell',
                                maxLength: 40
                            }, {
                                fieldLabel: this.app.i18n._('Fax'), 
                                labelIcon: 'images/oxygen/16x16/devices/printer.png',
                                name: 'tel_fax',
                                maxLength: 40
                            }], [{
                                fieldLabel: this.app.i18n._('Phone (private)'),
                                labelIcon: 'images/oxygen/16x16/apps/kcall.png',
                                name: 'tel_home',
                                maxLength: 40
                            }, {
                                fieldLabel: this.app.i18n._('Mobile (private)'),
                                labelIcon: 'images/oxygen/16x16/devices/phone.png',
                                name: 'tel_cell_private',
                                maxLength: 40
                            }, {
                                fieldLabel: this.app.i18n._('Fax (private)'), 
                                labelIcon: 'images/oxygen/16x16/devices/printer.png',
                                name: 'tel_fax_home',
                                maxLength: 40
                            }], [{
                                fieldLabel: this.app.i18n._('E-Mail'), 
                                labelIcon: 'images/oxygen/16x16/actions/kontact-mail.png',
                                name: 'email',
                                vtype: 'email',
                                maxLength: 64
                            }, {
                                fieldLabel: this.app.i18n._('E-Mail (private)'), 
                                labelIcon: 'images/oxygen/16x16/actions/kontact-mail.png',
                                name: 'email_home',
                                vtype: 'email',
                                maxLength: 64
                            }, {
                                xtype: 'mirrortextfield',
                                fieldLabel: this.app.i18n._('Web'),
                                labelIcon: 'images/oxygen/16x16/actions/network.png',
                                name: 'url',
                                maxLength: 128,
                                listeners: {
                                    scope: this,
                                    focus: function (field) {
                                        if (! field.getValue()) {
                                            field.setValue('http://www.');
                                            field.selectText.defer(100, field, [7, 11]);
                                        }
                                    },
                                    blur: function (field) {
                                        if (field.getValue() === 'http://www.') {
                                            field.setValue(null);
                                            field.validate();
                                        }
                                    }
                                }
                            }]]
                        }]
                    }, {
                        xtype: 'tabpanel',
                        region: 'south',
                        border: false,
                        deferredRender: false,
                        height: 124,
                        split: true,
                        activeTab: 0,
                        defaults: {
                            frame: true
                        },
                        items: [{
                            title: this.app.i18n._('Company Address'),
                            xtype: 'columnform',
                            items: [[{
                                fieldLabel: this.app.i18n._('Street'), 
                                name: 'adr_one_street',
                                maxLength: 64
                            }, {
                                fieldLabel: this.app.i18n._('Street 2'), 
                                name: 'adr_one_street2',
                                maxLength: 64
                            }, {
                                fieldLabel: this.app.i18n._('Region'),
                                name: 'adr_one_region',
                                maxLength: 64
                            }], [{
                                fieldLabel: this.app.i18n._('Postal Code'), 
                                name: 'adr_one_postalcode',
                                maxLength: 64
                            }, {
                                fieldLabel: this.app.i18n._('City'),
                                name: 'adr_one_locality',
                                maxLength: 64
                            }, {
                                xtype: 'widget-countrycombo',
                                fieldLabel: this.app.i18n._('Country'),
                                name: 'adr_one_countryname',
                                maxLength: 64
                            }]]
                        }, {
                            title: this.app.i18n._('Private Address'),
                            xtype: 'columnform',
                            items: [[{
                                fieldLabel: this.app.i18n._('Street'), 
                                name: 'adr_two_street',
                                maxLength: 64
                            }, {
                                fieldLabel: this.app.i18n._('Street 2'), 
                                name: 'adr_two_street2',
                                maxLength: 64
                            }, {
                                fieldLabel: this.app.i18n._('Region'),
                                name: 'adr_two_region',
                                maxLength: 64
                            }], [{
                                fieldLabel: this.app.i18n._('Postal Code'), 
                                name: 'adr_two_postalcode',
                                maxLength: 64
                            }, {
                                fieldLabel: this.app.i18n._('City'),
                                name: 'adr_two_locality',
                                maxLength: 64
                            }, {
                                xtype: 'widget-countrycombo',
                                fieldLabel: this.app.i18n._('Country'),
                                name: 'adr_two_countryname',
                                maxLength: 64
                            }]]
                        }]
                    }]
                }, {
                    // activities and tags
                    region: 'east',
                    layout: 'accordion',
                    animate: true,
                    width: 210,
                    split: true,
                    collapsible: true,
                    collapseMode: 'mini',
                    header: false,
                    margins: '0 5 0 5',
                    border: true,
                    items: [
                        new Ext.Panel({
                            // @todo generalise!
                            title: this.app.i18n._('Description'),
                            iconCls: 'descriptionIcon',
                            layout: 'form',
                            labelAlign: 'top',
                            border: false,
                            items: [{
                                style: 'margin-top: -4px; border 0px;',
                                labelSeparator: '',
                                xtype: 'textarea',
                                name: 'note',
                                hideLabel: true,
                                grow: false,
                                preventScrollbars: false,
                                anchor: '100% 100%',
                                emptyText: this.app.i18n._('Enter description'),
                                requiredGrant: 'editGrant'                           
                            }]
                        }),
                        new Tine.widgets.activities.ActivitiesPanel({
                            app: 'Addressbook',
                            showAddNoteForm: false,
                            border: false,
                            bodyStyle: 'border:1px solid #B5B8C8;'
                        }),
                        new Tine.widgets.tags.TagPanel({
                            app: 'Addressbook',
                            border: false,
                            bodyStyle: 'border:1px solid #B5B8C8;'
                        })
                    ]
                }]
            }, this.mapPanel,
            new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: (this.record && ! this.copyRecord) ? this.record.id : '',
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            }), this.linkPanel
            ]
        };
    },
    
    /**
     * init component
     */
    initComponent: function () {
        
        this.linkPanel = new Tine.widgets.dialog.LinkPanel({
            relatedRecords: (Tine.Crm && Tine.Tinebase.common.hasRight('run', 'Crm')) ? {
                Crm_Model_Lead: {
                    recordClass: Tine.Crm.Model.Lead,
                    dlgOpener: Tine.Crm.LeadEditDialog.openWindow
                }
            } : {}
        });
        
        this.initToolbar();
        
        this.supr().initComponent.apply(this, arguments);    
    },
    
    /**
     * initToolbar
     */
    initToolbar: function() {
        var exportContactButton = new Ext.Action({
            id: 'exportButton',
            text: Tine.Tinebase.appMgr.get('Addressbook').i18n._('Export as pdf'),
            handler: this.onExportContact,
            iconCls: 'action_exportAsPdf',
            disabled: false,
            scope: this
        });
        var addNoteButton = new Tine.widgets.activities.ActivitiesAddButton({});
        this.parseAddressButton = new Ext.Action({
            text: Tine.Tinebase.appMgr.get('Addressbook').i18n._('Parse address'),
            handler: this.onParseAddress,
            iconCls: 'action_parseAddress',
            disabled: false,
            scope: this,
            enableToggle: true
        });
        
        this.tbarItems = [exportContactButton, addNoteButton, this.parseAddressButton];
    },
    
    /**
     * checks if form data is valid
     * 
     * @return {Boolean}
     */
    isValid: function () {
        var form = this.getForm();
        var isValid = true;
        
        // you need to fill in one of: n_given n_family org_name
        // @todo required fields should depend on salutation ('company' -> org_name, etc.) 
        //       and not required fields should be disabled (n_given, n_family, etc.) 
        if (form.findField('n_family').getValue() === '' && form.findField('org_name').getValue() === '') {
            var invalidString = String.format(this.app.i18n._('Either {0} or {1} must be given'), this.app.i18n._('Last Name'), this.app.i18n._('Company'));
            
            form.findField('n_family').markInvalid(invalidString);
            form.findField('org_name').markInvalid(invalidString);
            
            isValid = false;
        }
        
        return isValid && Tine.Addressbook.ContactEditDialog.superclass.isValid.apply(this, arguments);
    },
    
    /**
     * export pdf handler
     */
    onExportContact: function () {
        var downloader = new Ext.ux.file.Download({
            params: {
                method: 'Addressbook.exportContacts',
                filter: Ext.encode([{field: 'id', operator: 'in', value: this.record.id}]),
                options: Ext.util.JSON.encode({
                    format: 'pdf'
                })
            }
        });
        downloader.start();
    },
    
    /**
     * parse address handler
     * 
     * opens message box where user can paste address
     * 
     * @param {Ext.Button} button
     */
    onParseAddress: function (button) {
        if (button.pressed) {
            Ext.Msg.prompt(this.app.i18n._('Paste address'), this.app.i18n._('Please paste an address that should be parsed:'), function(btn, text) {
                if (btn == 'ok'){
                    this.parseAddress(text);
                } else if (btn == 'cancel') {
                    button.toggle();
                }
            }, this, 100);
        } else {
            button.setText(this.app.i18n._('Parse address'));
            this.tokenModePlugin.endTokenMode();
        }
    },
    
    /**
     * send address to server + fills record/form with parsed data + adds unrecognizedTokens to description box
     * 
     * @param {String} address
     */
    parseAddress: function(address) {
        Tine.log.debug('parsing address ... ');
        
        Tine.Addressbook.parseAddressData(address, function(result, response) {
            Tine.log.debug('parsed address:');
            Tine.log.debug(result);
            
            // only set the fields that could be detected
            Ext.iterate(result.contact, function(key, value) {
                this.record.set(key, value);
            }, this);
            
            var oldNote = (this.record.get('note')) ? this.record.get('note') : '';
            this.record.set('note', result.unrecognizedTokens.join(' ') + oldNote);
            this.onRecordLoad();
            
            this.parseAddressButton.setText(this.app.i18n._('End token mode'));
            this.tokenModePlugin.startTokenMode();
        }, this);
    },
    
    /**
     * onRecordLoad
     */
    onRecordLoad: function () {
        // NOTE: it comes again and again till 
        if (this.rendered) {
            var container;
                        
            // handle default container
            if (! this.record.id) {
                if (this.forceContainer) {
                    container = this.forceContainer;
                    // only force initially!
                    this.forceContainer = null;
                } else {
                    container = Tine.Addressbook.registry.get('defaultAddressbook');
                }
                
                this.record.set('container_id', '');
                this.record.set('container_id', container);
            }
            
            if (this.mapPanel instanceof Tine.Addressbook.MapPanel) {
                this.mapPanel.onRecordLoad(this.record);
            }
        }
        
        this.supr().onRecordLoad.apply(this, arguments);
        
        this.linkPanel.onRecordLoad(this.record);
    }
});

/**
 * Opens a new contact edit dialog window
 * 
 * @return {Ext.ux.Window}
 */
Tine.Addressbook.ContactEditDialog.openWindow = function (config) {
    
    // if a container is selected in the tree, take this as default container
    var treeNode = Ext.getCmp('Addressbook_Tree') ? Ext.getCmp('Addressbook_Tree').getSelectionModel().getSelectedNode() : null;
    if (treeNode && treeNode.attributes && treeNode.attributes.container.type) {
        config.forceContainer = treeNode.attributes.container;
    } else {
        config.forceContainer = null;
    }
    
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 600,
        name: Tine.Addressbook.ContactEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Addressbook.ContactEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
