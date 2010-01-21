/**************************** edit dialog **********************************/

/**
 * The edit dialog
 * @constructor
 * @class Tine.Addressbook.ContactEditDialog
 * 
 * @todo move this to ContactEditDialog.js
 * @todo use generic Tine.widgets.EditDialog
 */  
Tine.Addressbook.ContactEditDialog = Ext.extend(Tine.widgets.dialog.EditRecord, {
    /**
     * @cfg {Tine.Addressbook.Model.Contact}
     */
    contact: null,
    /**
     * @cfg {Object} container
     */
    forceContainer: null,    
    /**
     * @private
     */
    windowNamePrefix: 'AddressbookEditWindow_',
    
    /**
     * @private!
     */
    id: 'contactDialog',
    //layout: 'hfit',
    appName: 'Addressbook',
    containerProperty: 'container_id',
    showContainerSelector: true,
    
    initComponent: function() {
        if (! this.contact) {
            this.contact = new Tine.Addressbook.Model.Contact({}, 0);
        }
        
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Addressbook');
        
        Ext.Ajax.request({
            scope: this,
            success: this.onContactLoad,
            params: {
                method: 'Addressbook.getContact',
                contactId: this.contact.id
            }
        });
        
        //this.containerItemName = this.translation._('contacts');
        this.containerName = this.translation._('addressbook');
        this.containersName = this.translation._('contacts');
        
        // export lead handler for edit contact dialog
        var exportContactButton = new Ext.Action({
            id: 'exportButton',
            text: this.translation.gettext('export as pdf'),
            handler: this.handlerExport,
            iconCls: 'action_exportAsPdf',
            disabled: false,
            scope: this
        });
        
        var addNoteButton = new Tine.widgets.activities.ActivitiesAddButton({});  

        this.tbarItems = [exportContactButton, addNoteButton];
        this.items = Tine.Addressbook.ContactEditDialog.getEditForm(this.contact);
        
        Tine.Addressbook.ContactEditDialog.superclass.initComponent.call(this);
        
        this.addEvents(
            /**
             * @event load
             * Fired when record is loaded
             */
            'load'
        )
    },
    
    onRender: function(ct, position) {
        Tine.Addressbook.ContactEditDialog.superclass.onRender.call(this, ct, position);
        Ext.MessageBox.wait(this.translation._('Loading Contact...'), _('Please Wait'));
    },
    
    onResize: function() {
        Tine.Addressbook.ContactEditDialog.superclass.onResize.apply(this, arguments);
        this.setTabHeight.defer(100, this);
    },
    
    setTabHeight: function() {
        console.log('resize');
        var tabPanel = Ext.get('adbEditDialogTabPanel');
        console.log(this.footer.getTop() - tabPanel.getTop());
        //tabPanel.setHeight(this.footer.getTop() - tabPanel.getTop());
        /*
        var eventTab = this.items.first().items.first();
        var centerPanel = eventTab.items.first();
        var tabPanel = centerPanel.items.last();
        tabPanel.setHeight(centerPanel.getEl().getBottom() - tabPanel.getEl().getTop());
        */
    },
    
    onContactLoad: function(response) {
        // interrupt process flow until dialog is rendered
        if (! this.rendered) {
            this.onContactLoad.defer(250, this);
            return;
        }

        this.getForm().findField('n_prefix').focus(false, 250);
        var contactData = Ext.util.JSON.decode(response.responseText);
        if (this.forceContainer) {
            contactData.container_id = this.forceContainer;
            // only force initially!
            this.forceContainer = null;
        }
        this.updateContactRecord(contactData);
        
        if (! this.contact.id) {
            this.window.setTitle(this.translation.gettext('Add new contact'));
        } else {
            this.window.setTitle(String.format(this.translation._('Edit Contact "{0}"'), this.contact.get('n_fn') + 
                (this.contact.get('org_name') ? ' (' + this.contact.get('org_name') + ')' : '')));
        }
        
        if (this.fireEvent('load', this) !== false) {
            this.getForm().loadRecord(this.contact);
            
            // quickhack continues ... :( this should not be needed when adb uses generic edit dialog
            // update cf panel record
            Ext.getCmp('adbEditDialogCfPanel').quickHack.record = this.contact;
            
            this.updateToolbars(this.contact, 'container_id');
            Ext.getCmp('addressbookeditdialog-jpegimage').setValue(this.contact.get('jpegphoto'));
    
            Ext.MessageBox.hide();
        }
    },
    
    handlerApplyChanges: function(_button, _event, _closeWindow) {
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Addressbook');
        
        var form = this.getForm();

        // you need to fill in one of: n_given n_family org_name
        // @todo required fields should depend on salutation ('company' -> org_name, etc.) 
        //       and not required fields should be disabled (n_given, n_family, etc.) 
        if(form.isValid() 
            && (form.findField('n_family').getValue() !== ''
                || form.findField('org_name').getValue() !== '') ) {
            Ext.MessageBox.wait(this.translation.gettext('Please wait a moment...'), this.translation.gettext('Saving Contact'));
            form.updateRecord(this.contact);
            this.contact.set('jpegphoto', Ext.getCmp('addressbookeditdialog-jpegimage').getValue());
    
            Ext.Ajax.request({
                scope: this,
                params: {
                    method: 'Addressbook.saveContact', 
                    contactData: Ext.util.JSON.encode(this.contact.data)
                },
                success: function(response) {
                    this.onContactLoad(response);

                    this.fireEvent('update', Ext.util.JSON.encode(this.contact.data)); 
                    
                    if(_closeWindow === true) {
                        this.purgeListeners();
                        this.window.close();
                    } else {
                        this.updateToolbarButtons(this.contact);
                        
                        Ext.MessageBox.hide();
                    }
                },
                failure: function ( result, request) { 
                    Ext.MessageBox.alert(this.translation.gettext('Failed'), this.translation.gettext('Could not save contact.')); 
                } 
            });
        } else {
            form.findField('n_family').markInvalid(this.app.i18n._('Either '));
            form.findField('org_name').markInvalid(this.app.i18n._('Start date is not valid'));
            Ext.MessageBox.alert(this.translation.gettext('Errors'), this.translation.gettext('Please fix the errors noted.'));         
        }
    },

    handlerDelete: function(_button, _event) {
        var contactIds = Ext.util.JSON.encode([this.contact.data.id]);
            
        Ext.Ajax.request({
            url: 'index.php',
            params: {
                method: 'Addressbook.deleteContacts', 
                _contactIds: contactIds
            },
            text: this.translation.gettext('Deleting contact...'),
            success: function(_result, _request) {
                this.fireEvent('update', Ext.util.JSON.encode(this.contact.data)); 
                this.window.close();
            },
            failure: function ( result, request) { 
                Ext.MessageBox.alert(this.translation.gettext('Failed'), this.translation.gettext('Some error occured while trying to delete the contact.')); 
            } 
        });                           
    },

    /**
     * export pdf handler
     * 
     * @todo think about using the generic export button here
     * 
     * @param _button
     * @param _event
     */
    handlerExport: function(_button, _event) {
        
        var contactId = Ext.util.JSON.encode(this.contact.id);

        //Tine.Tinebase.common.openWindow('contactWindow', 'index.php?method=Addressbook.exportContacts&_format=pdf&_filter=' + contactId, 200, 150);
        
        var form = Ext.getBody().createChild({
            tag:'form',
            method:'post',
            cls:'x-hidden'
        });
        
        Ext.Ajax.request({
            isUpload: true,
            form: form,
            params: {
                method: 'Addressbook.exportContacts',
                requestType: 'HTTP',
                _filter: contactId,
                _format: 'pdf'
            },
            success: function() {
                form.remove();
            },
            failure: function() {
                form.remove();
            }
        });
    },
    
    updateContactRecord: function(_contactData) {
        if(_contactData.bday && _contactData.bday !== null) {
            _contactData.bday = Date.parseDate(_contactData.bday, Date.patterns.ISO8601Long);
        }

        this.contact = new Tine.Addressbook.Model.Contact(_contactData, _contactData.id ? _contactData.id : 0);
    },
    
    updateToolbarButtons: function(contact) {
        this.updateToolbars.defer(10, this, [contact, 'container_id']);
        
        // add contact id to export button and enable it if id is set
        var contactId = contact.get('id');
        if (contactId) {
            Ext.getCmp('exportButton').contactId = contactId;
            Ext.getCmp('exportButton').setDisabled(false);
        } else {
            Ext.getCmp('exportButton').setDisabled(true);
        }
    }
});

/**
 * Addressbook Edit Popup
 */
Tine.Addressbook.ContactEditDialog.openWindow = function (config) {
    
    // if a concreate container is selected in the tree, take this as default container
    var treeNode = Ext.getCmp('Addressbook_Tree') ? Ext.getCmp('Addressbook_Tree').getSelectionModel().getSelectedNode() : null;
    if (treeNode && treeNode.attributes && treeNode.attributes.containerType == 'singleContainer') {
        config.forceContainer = treeNode.attributes.container;
    }
    
    config.contact = config.record ? config.record : new Tine.Addressbook.Model.Contact({}, 0);
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 600,
        //layout: Tine.Addressbook.ContactEditDialog.prototype.windowLayout,
        name: Tine.Addressbook.ContactEditDialog.prototype.windowNamePrefix + config.contact.id,
        contentPanelConstructor: 'Tine.Addressbook.ContactEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

/**
 * Addressbook Edit Dialog
 * 
 * @todo make country selection a widget
 */
Tine.Addressbook.ContactEditDialog.getEditForm = function(_contact) {
    var translation = new Locale.Gettext();
    translation.textdomain('Addressbook');

    
    /*
        columnWidth: .5,
        labelWidth: 150,
        items: {
            xtype: 'combo',
            fieldLabel: translation._('Display Name'),
            name: 'n_fn',
            disabled: true,
            anchor: '100% r'
        }
    */
    
    var personalInformationExpandArea = new Ext.ux.form.ColumnFormPanel({
        //xtype: 'columnform',
        items:[
            [
                {
                    columnWidth: .4,
                    fieldLabel: translation._('Suffix'), 
                    name:'n_suffix'
                },
                {
                    columnWidth: .4,
                    fieldLabel: translation._('Job Role'), 
                    name:'role'
                },
                {
                    columnWidth: .2,
                    fieldLabel: translation._('Room'), 
                    name:'room'
                }
            ]
        ]
    });
    
    var personalInformation = {
        title: translation._('Personal Information'),
        xtype: 'expanderfieldset',
        layout: 'hfit',
        collapsible: true,
        autoHeight: true,
        items:[{
            xtype: 'panel',
            layout: 'fit',
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
                    id: 'addressbookeditdialog-jpegimage',
                    name: 'jpegimage',
                    width: 90,
                    height: 120
                }),
                listeners: {
                    scope: this,
                    'resize': function(panel) {
                    	panel.setSize(90, 120);
                    }
                }
            }, {
                xtype: 'columnform',
                items: [[{
                    columnWidth: .35,
                    fieldLabel: translation._('Salutation'),
                    xtype: 'combo',
                    store: Tine.Addressbook.getSalutationStore(),
                    id: 'salutation_id',
                    name: 'salutation_id',
                    mode: 'local',
                    displayField: 'name',
                    valueField: 'id',
                    triggerAction: 'all'
                }, {
                    columnWidth: .65,
                    fieldLabel: translation._('Title'), 
                    name:'n_prefix',
                    id: 'n_prefix'
                }, {
                    width: 100,
                    hidden: true
                }], [{
                    columnWidth: .35,
                    fieldLabel: translation._('First Name'), 
                    name:'n_given'
                }, {
                    columnWidth: .30,
                    fieldLabel: translation._('Middle Name'), 
                    name:'n_middle'
                }, {
                    columnWidth: .35,
                    fieldLabel: translation._('Last Name'), 
                    name:'n_family'
                }, {
                    width: 100,
                    hidden: true
                }], [{
                    columnWidth: .65,
                    xtype: 'mirrortextfield',
                    fieldLabel: translation._('Company'), 
                    name:'org_name',
                    maxLength: 64
                }, {
                    columnWidth: .35,
                    fieldLabel: translation._('Unit'), 
                    name:'org_unit',
                    maxLength: 64
                }, {
                    width: 100,
                    hidden: true
                }], [{
                    columnWidth: .65,
                    xtype: 'combo',
                    fieldLabel: translation._('Display Name'),
                    name: 'n_fn',
                    disabled: true
                }, {
                    columnWidth: .35,
                    fieldLabel: translation._('Job Title'),
                    name: 'title'
                }, {
                    width: 100,
                    xtype: 'datefield',
                    fieldLabel: translation._('Birthday'),
                    name: 'bday'
                }]]
            }
        ]}, {
            xtype: 'columnform',
            items:[[{
                columnWidth: .4,
                fieldLabel: translation._('Suffix'), 
                name:'n_suffix'
            },
            {
                columnWidth: .4,
                fieldLabel: translation._('Job Role'), 
                name:'role'
            },
            {
                columnWidth: .2,
                fieldLabel: translation._('Room'), 
                name:'room'
            }]]
        }
    ]};
 
    var contactInformationBasePanel = {
        xtype: 'columnform',
        labelAlign: 'top',
        formDefaults: {
            xtype:'icontextfield',
            anchor: '100%',
            labelSeparator: '',
            columnWidth: .333
        },
        items: [
            [
                {
                    fieldLabel: translation._('Phone'), 
                    labelIcon: 'images/oxygen/16x16/apps/kcall.png',
                    name:'tel_work'
                },
                {
                    fieldLabel: translation._('Mobile'),
                    labelIcon: 'images/oxygen/16x16/devices/phone.png',
                    name:'tel_cell'
                },
                {
                    fieldLabel: translation._('Fax'), 
                    labelIcon: 'images/oxygen/16x16/devices/printer.png',
                    name:'tel_fax'
                }
            ],
            [
                {
                    fieldLabel: translation._('Phone (private)'),
                    labelIcon: 'images/oxygen/16x16/apps/kcall.png',
                    name:'tel_home'
                },
                {
                    fieldLabel: translation._('Mobile (private)'),
                    labelIcon: 'images/oxygen/16x16/devices/phone.png',
                    name:'tel_cell_private'
                },
                {
                    fieldLabel: translation._('Fax (private)'), 
                    labelIcon: 'images/oxygen/16x16/devices/printer.png',
                    name:'tel_fax_home'
                }
            ],
            [
                {
                    fieldLabel: translation._('E-Mail'), 
                    labelIcon: 'images/oxygen/16x16/actions/kontact-mail.png',
                    name:'email',
                    vtype: 'email'
                },
                {
                    fieldLabel: translation._('E-Mail (private)'), 
                    labelIcon: 'images/oxygen/16x16/actions/kontact-mail.png',
                    name:'email_home',
                    vtype: 'email'
                },
                {
                    xtype: 'mirrortextfield',
                    fieldLabel: translation._('Web'),
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
                }
            ]
        ]
    };
    
    var contactInformation = {
        title: translation._('Contact Information'),
        xtype: 'fieldset',
        layout: 'hfit',
        //collapsible: true,
        autoHeight:true,
        items: [
            contactInformationBasePanel
            //contactInformationExpandArea
        ]
    };
    
    var companyInformation = {
        xtype: 'tabpanel',
        deferredRender:false,
        height: 124,
        activeTab: 0,
        // use special item template without tabindex (=-1)
        itemTpl: new Ext.Template(
                 '<li class="{cls}" id="{id}"><a class="x-tab-strip-close" onclick="return false;"></a>',
                 '<a class="x-tab-right" href="#" onclick="return false;" tabindex="-1"><em class="x-tab-left">',
                 '<span class="x-tab-strip-inner"><span class="x-tab-strip-text {iconCls}">{text}</span></span>',
                 '</em></a></li>'
            ),
        border: false,
        defaults: {
            frame: true
        },
        items: [
            {
                xtype: 'columnform',
                title: translation._('Company Address'),
                labelAlign: 'top',
                formDefaults: {
                    xtype:'textfield',
                    anchor:'100%',
                    labelSeparator: '',
                    columnWidth: .333
                },
                items: [
                    [
                        /*{
                            xtype: 'mirrortextfield',
                            fieldLabel:'Company Name', 
                            name:'org_name'
                        },*/
                        {
                            fieldLabel: translation._('Street'), 
                            name:'adr_one_street'
                        },
                        {
                            fieldLabel: translation._('Street 2'), 
                            name:'adr_one_street2'
                        },
                        {
                            fieldLabel: translation._('Region'),
                            name:'adr_one_region'
                        }
                    ],
                    [
                        {
                            fieldLabel: translation._('Postal Code'), 
                            name:'adr_one_postalcode'
                        },
                        {
                            fieldLabel: translation._('City'),
                            name:'adr_one_locality'
                        },
                        {
                            xtype: 'widget-countrycombo',
                            fieldLabel: translation._('Country'),
                            name: 'adr_one_countryname'
                        }
                    ]
                ]
            },
            {
                xtype: 'columnform',
                title: translation._('Private Address'),
                labelAlign: 'top',
                formDefaults: {
                    xtype:'textfield',
                    anchor:'100%',
                    labelSeparator: '',
                    columnWidth: .333
                },
                items: [
                    [

                        {
                            fieldLabel: translation._('Street'), 
                            name:'adr_two_street'
                        },
                        {
                            fieldLabel: translation._('Street 2'), 
                            name:'adr_two_street2'
                        },
                        {
                            fieldLabel: translation._('Region'),
                            name:'adr_two_region'
                        }
                    ],
                    [
                        {
                            fieldLabel: translation._('Postal Code'), 
                            name:'adr_two_postalcode'
                        },
                        {
                            fieldLabel: translation._('City'),
                            name:'adr_two_locality'
                        },
                        {
                            xtype: 'widget-countrycombo',
                            fieldLabel: translation._('Country'),
                            name: 'adr_two_countryname'
                        }
                    ]
                ]
            }
            /*,
            {
                title: translation._('Custom Fields'),
                html: '',
                disabled: true
            }*/
        ]
    };

    var contactTabPanel = {
        title: translation.n_('Contact', 'Contacts', 1),
        autoScroll: true,
        layout: 'border',
        border: false,
        items: [
            {
                layout: 'hfit',
                containsScrollbar: true,
                //margins: '0 18 0 5',
                autoScroll: true,
                id: 'adbEditDialogContactLeft',
                region: 'center',
                items: [
                    personalInformation,
                    contactInformation,
                    companyInformation
                ]
            },
            {
                // tags & notes
            	layout: 'accordion',
            	animate: true,
                region: 'east',
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
                        title: translation._('Description'),
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
                            emptyText: translation._('Enter description')                            
                        }]
                    }),
                    new Tine.widgets.tags.TagPanel({
                        app: 'Addressbook',
                        border: false,
                        bodyStyle: 'border:1px solid #B5B8C8;'
                    }),
                    new Tine.widgets.activities.ActivitiesPanel({
                    	app: 'Addressbook',
                    	showAddNoteForm: false,
                        border: false,
                        bodyStyle: 'border:1px solid #B5B8C8;'
                    })                    
                ]
            }
        ]
    };

    var tabPanel = new Ext.TabPanel({
        id: 'adbEditDialogTabPanel',
        defaults: {
            frame: true
        },
        //height: 520,
        plain:true,
        activeTab: 0,
        border: false,
        items:[
            contactTabPanel,
            new Tine.widgets.activities.ActivitiesTabPanel({
                app: 'Addressbook',
                record_id: _contact.id,
                record_model: 'Addressbook_Model_Contact'
            }),
            new Tine.Tinebase.widgets.customfields.CustomfieldsPanel({
                id: 'adbEditDialogCfPanel',
                recordClass: Tine.Addressbook.Model.Contact,
                disabled: (Tine.Addressbook.registry.get('customfields').length == 0),
                quickHack: {record: _contact}
            }),{
                title: String.format(translation.ngettext('Link', 'Links [%d]', 1), 1),
                disabled: true
            }
        ]
    });
    
    /*
    // resize tab panel when window gets resised, and let space for savePath
    tabPanel.on('bodyresize', function(panel, w, h){
        console.log('bodyresize');
        panel.suspendEvents();
        panel.setHeight(Ext.getCmp('contactDialog').getSize().height-75);
        panel.resumeEvents();
    });
    */
    
    return [
        tabPanel
    ];
};
