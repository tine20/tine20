/*
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.ns('Tine.Addressbook');

/**
 * @namespace Tine.Addressbook
 * @class Tine.Addressbook.EventEditDialog
 * @extends Tine.widgets.dialog.EditDialog
 * Addressbook Edit Dialog <br>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */
Tine.Addressbook.ContactEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    windowNamePrefix: 'ContactEditWindow_',
    appName: 'Addressbook',
    recordClass: Tine.Addressbook.Model.Contact,
    showContainerSelector: true,
    //recordProxy: Tine.Calendar.backend,
    
    getFormItems: function() { 
        return {
            xtype: 'tabpanel',
            border: false,
            plain:true,
            activeTab: 0,
            items:[{
                title: this.app.i18n.n_('Contact', 'Contacts', 1),
                border: false,
                frame: true,
                layout: 'border',
                items: [{
                    region: 'center',
                    layout: 'hfit',
                    items: [{
                        xtype: 'expanderfieldset',
                        collapsible: true,
                        title: this.app.i18n._('Personal Information'),
                        items: [{
                            items: [{
                                xtype: 'panel',
                                layout: 'fit',
                                style: {
                                    position: 'absolute',
                                    right: '10px',
                                    top: Ext.isGecko ? '6px' : '0px',
                                    'z-index': 100
                                },
                                items: new Ext.ux.form.ImageField({
                                    name: 'jpegphoto',
                                    width: 90,
                                    height: 120
                                })
                            }, {
                                xtype: 'columnform',
                                items: [[{
                                    columnWidth: .35,
                                    fieldLabel: this.app.i18n._('Salutation'),
                                    xtype: 'combo',
                                    store: Tine.Addressbook.getSalutationStore(),
                                    name: 'salutation_id',
                                    mode: 'local',
                                    displayField: 'name',
                                    valueField: 'id',
                                    triggerAction: 'all'
                                }, {
                                    columnWidth: .65,
                                    fieldLabel: this.app.i18n._('Title'), 
                                    name:'n_prefix'
                                }, {
                                    width: 100,
                                    hidden: true
                                }], [{
                                    columnWidth: .35,
                                    fieldLabel: this.app.i18n._('First Name'), 
                                    name:'n_given'
                                }, {
                                    columnWidth: .30,
                                    fieldLabel: this.app.i18n._('Middle Name'), 
                                    name:'n_middle'
                                }, {
                                    columnWidth: .35,
                                    fieldLabel: this.app.i18n._('Last Name'), 
                                    name:'n_family'
                                }, {
                                    width: 100,
                                    hidden: true
                                }], [{
                                    columnWidth: .65,
                                    xtype: 'mirrortextfield',
                                    fieldLabel: this.app.i18n._('Company'), 
                                    name:'org_name',
                                    maxLength: 64
                                }, {
                                    columnWidth: .35,
                                    fieldLabel: this.app.i18n._('Unit'), 
                                    name:'org_unit',
                                    maxLength: 64
                                }, {
                                    width: 100,
                                    hidden: true
                                }], [{
                                    columnWidth: .65,
                                    xtype: 'combo',
                                    fieldLabel: this.app.i18n._('Display Name'),
                                    name: 'n_fn',
                                    disabled: true
                                }, {
                                    columnWidth: .35,
                                    fieldLabel: this.app.i18n._('Job Title'),
                                    name: 'title'
                                }, {
                                    width: 100,
                                    xtype: 'datefield',
                                    fieldLabel: this.app.i18n._('Birthday'),
                                    name: 'bday'
                                }]]
                            }
                        ]}, {
                            xtype: 'columnform',
                            items:[[{
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
                            }]]
                        }]
                    }, {
                        xtype: 'fieldset',
                        title: this.app.i18n._('Contact Information'),
                        items: [{
                            xtype: 'columnform',
                            items: [[{
                                fieldLabel: this.app.i18n._('Phone'), 
                                labelIcon: 'images/oxygen/16x16/apps/kcall.png',
                                name:'tel_work'
                            }, {
                                fieldLabel: this.app.i18n._('Mobile'),
                                labelIcon: 'images/oxygen/16x16/devices/phone.png',
                                name:'tel_cell'
                            }, {
                                fieldLabel: this.app.i18n._('Fax'), 
                                labelIcon: 'images/oxygen/16x16/devices/printer.png',
                                name:'tel_fax'
                            }], [{
                                fieldLabel: this.app.i18n._('Phone (private)'),
                                labelIcon: 'images/oxygen/16x16/apps/kcall.png',
                                name:'tel_home'
                            }, {
                                fieldLabel: this.app.i18n._('Mobile (private)'),
                                labelIcon: 'images/oxygen/16x16/devices/phone.png',
                                name:'tel_cell_private'
                            }, {
                                fieldLabel: this.app.i18n._('Fax (private)'), 
                                labelIcon: 'images/oxygen/16x16/devices/printer.png',
                                name:'tel_fax_home'
                            }], [{
                                fieldLabel: this.app.i18n._('E-Mail'), 
                                labelIcon: 'images/oxygen/16x16/actions/kontact-mail.png',
                                name:'email',
                                vtype: 'email'
                            }, {
                                fieldLabel: this.app.i18n._('E-Mail (private)'), 
                                labelIcon: 'images/oxygen/16x16/actions/kontact-mail.png',
                                name:'email_home',
                                vtype: 'email'
                            }, {
                                xtype: 'mirrortextfield',
                                fieldLabel: this.app.i18n._('Web'),
                                labelIcon: 'images/oxygen/16x16/actions/network.png',
                                name:'url',
                                vtype:'url',
                                listeners: {
                                    scope: this,
                                    focus: function(field) {
                                        if (! field.getValue()) {
                                            field.setValue('http://www.');
                                        }
                                    },
                                    blur: function(field) {
                                        if (field.getValue() == 'http://www.') {
                                            field.setValue(null);
                                            field.validate();
                                        }
                                    }
                                }
                            }]]
                        }]
                    }, {
                        xtype: 'tabpanel',
                        border: false,
                        deferredRender:false,
                        height: 124,
                        activeTab: 0,
                        defaults: {
                            frame: true
                        },
                        items: [{
                            title: this.app.i18n._('Company Address'),
                            xtype: 'columnform',
                            items: [[{
                                fieldLabel: this.app.i18n._('Street'), 
                                name:'adr_one_street'
                            }, {
                                fieldLabel: this.app.i18n._('Street 2'), 
                                name:'adr_one_street2'
                            }, {
                                fieldLabel: this.app.i18n._('Region'),
                                name:'adr_one_region'
                            }], [{
                                fieldLabel: this.app.i18n._('Postal Code'), 
                                name:'adr_one_postalcode'
                            }, {
                                fieldLabel: this.app.i18n._('City'),
                                name:'adr_one_locality'
                            }, {
                                xtype: 'widget-countrycombo',
                                fieldLabel: this.app.i18n._('Country'),
                                name: 'adr_one_countryname'
                            }]]
                        }, {
                            title: this.app.i18n._('Private Address'),
                            xtype: 'columnform',
                            items: [[{
                                fieldLabel: this.app.i18n._('Street'), 
                                name:'adr_two_street'
                            }, {
                                fieldLabel: this.app.i18n._('Street 2'), 
                                name:'adr_two_street2'
                            }, {
                                fieldLabel: this.app.i18n._('Region'),
                                name:'adr_two_region'
                            }], [{
                                fieldLabel: this.app.i18n._('Postal Code'), 
                                name:'adr_two_postalcode'
                            }, {
                                fieldLabel: this.app.i18n._('City'),
                                name:'adr_two_locality'
                            }, {
                                xtype: 'widget-countrycombo',
                                fieldLabel: this.app.i18n._('Country'),
                                name: 'adr_two_countryname'
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
                                xtype:'textarea',
                                name: 'note',
                                hideLabel: true,
                                grow: false,
                                preventScrollbars:false,
                                anchor:'100% 100%',
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
            },
            {
				layout: 'fit',
				id: 'addressbook-map',
				title: this.app.i18n._('Map'),
				disabled: (this.record.get('lon') === null) && (this.record.get('lat') === null),
				xtype: "widget-mappanel",
				zoom: 14
            }, 
            new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: (this.record) ? this.record.id : '',
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            }),
            new Tine.Tinebase.widgets.customfields.CustomfieldsPanel({
                //id: 'adbEditDialogCfPanel',
                recordClass: Tine.Addressbook.Model.Contact,
                disabled: (Tine.Addressbook.registry.get('customfields').length === 0),
                quickHack: {record: this.record}
            }), this.linkPanel
            ]
        };
    },
    
    initComponent: function() {
        
        this.linkPanel = new Tine.widgets.dialog.LinkPanel({
            relatedRecords: {
                Crm_Model_Lead: {
                    recordClass: Tine.Crm.Model.Lead,
                    dlgOpener: Tine.Crm.LeadEditDialog.openWindow
                }
            }
        });
        
        // export lead handler for edit contact dialog
        var exportContactButton = new Ext.Action({
            id: 'exportButton',
            text: Tine.Tinebase.appMgr.get('Addressbook').i18n._('Export as pdf'),
            handler: this.onExportContact,
            iconCls: 'action_exportAsPdf',
            disabled: false,
            scope: this
        });
        var addNoteButton = new Tine.widgets.activities.ActivitiesAddButton({});  
        this.tbarItems = [exportContactButton, addNoteButton];
        
        this.supr().initComponent.apply(this, arguments);    
    },
    
    /**
     * checks if form data is valid
     * 
     * @return {Boolean}
     */
    isValid: function() {
        var form = this.getForm();
        var isValid = true;
        
        // you need to fill in one of: n_given n_family org_name
        // @todo required fields should depend on salutation ('company' -> org_name, etc.) 
        //       and not required fields should be disabled (n_given, n_family, etc.) 
        if (form.findField('n_family').getValue() == '' && form.findField('org_name').getValue() == '') {
            var invalidString = String.format(this.app.i18n._('Either {0} or {1} must be given'), this.app.i18n._('Last Name'), this.app.i18n._('Company'));
            
            form.findField('n_family').markInvalid(invalidString);
            form.findField('org_name').markInvalid(invalidString);
            
            isValid = false;
        }
        
        return isValid && Tine.Calendar.EventEditDialog.superclass.isValid.apply(this, arguments);
    },
    
    /**
     * export pdf handler
     */
    onExportContact: function() {
        var downloader = new Ext.ux.file.Download({
            params: {
                method: 'Addressbook.exportContacts',
                _filter: this.record.id,
                _format: 'pdf'
            }
        });
        downloader.start();
    },
    
    onRecordLoad: function() {
        // NOTE: it comes again and again till 
        if (this.rendered) {
            
            // handle default container
            if (! this.record.id) {
                if (this.forceContainer) {
                    var container = this.forceContainer;
                    // only force initially!
                    this.forceContainer = null;
                } else {
                    var container = Tine.Addressbook.registry.get('defaultAddressbook');
                }
                
                this.record.set('container_id', '');
                this.record.set('container_id', container);
            }
        }
        
        this.supr().onRecordLoad.apply(this, arguments);
        
        this.linkPanel.onRecordLoad(this.record);

        if(this.record.get('lon') !== null && this.record.get('lat') !== null) {
        	Ext.getCmp('addressbook-map').setCenter(this.record.get('lon'),this.record.get('lat'));
        }
    }
});

/**
 * Opens a new contact edit dialog window
 * 
 * @return {Ext.ux.Window}
 */
Tine.Addressbook.ContactEditDialog.openWindow = function (config) {
    // if a concreate container is selected in the tree, take this as default container
    var treeNode = Ext.getCmp('Addressbook_Tree') ? Ext.getCmp('Addressbook_Tree').getSelectionModel().getSelectedNode() : null;
    if (treeNode && treeNode.attributes && treeNode.attributes.containerType == 'singleContainer') {
        config.forceContainer = treeNode.attributes.container;
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