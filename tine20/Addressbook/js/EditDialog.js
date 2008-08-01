
/**
 * Addressbook Edit Dialog
 * 
 * @todo make country selection a widget
 */
Tine.Addressbook.ContactEditDialog.getEditForm = function(_contact) {
    var translation = new Locale.Gettext();
    translation.textdomain('Addressbook');

    var savePath = {
        layout: 'column',
        autoHeight: true,
        border: false,
        bodyStyle: 'margin-top: 5px;',
        defaults: {
            border: false,
            layout: 'form'
            //bodyStyle:'margin:10px',
        },
        items: [{
            columnWidth: .5,
            layout: 'column',
            border: false,
            items:[{
                layout: 'form',
                labelWidth: 70,
                border: false,
                items:
                    new Tine.widgets.container.selectionComboBox({
                        fieldLabel: translation._('Saved in'),
                        width: 150,
                        name: 'owner',
                        itemName: translation._('contacts'),
                        appName: 'Addressbook'
                    })
                }/*,{
                    hideLabels: true,
                    bodyStyle: 'margin-left: 3px;',
                    //border: true,
                    items: {
                        xtype: 'checkbox',
                        boxLabel: 'Private',
                        disabled: true
                    }
                }*/]
            },{
                columnWidth: .5,
                labelWidth: 150,
                items: {
                    xtype: 'combo',
                    fieldLabel: translation._('Display Name'),
                    name: 'n_fn',
                    disabled: true,
                    anchor: '100% r'
                }
            }
        ]
    };
    
    var personalInformationExpandArea = new Ext.ux.form.ColumnFormPanel({
        //xtype: 'columnform',
        items:[
            [
                {
                    columnWidth: .4,
                    fieldLabel: translation._('Job Role'), 
                    name:'role'
                },
                {
                    columnWidth: .4,
                    fieldLabel: translation._('Unit'), 
                    name:'org_unit'
                },
                {
                    columnWidth: .2,
                    fieldLabel: translation._('Room'), 
                    name:'room'
                }
            ],
            [
                {
                    columnWidth: .4,
                    fieldLabel: translation._('Middle Name'), 
                    name:'n_middle'
                },
                {
                    columnWidth: .4,
                    fieldLabel: translation._('Suffix'), 
                    name:'n_suffix'
                },
                {
                    columnWidth: .2,
                    xtype: 'datefield',
                    fieldLabel: translation._('Birthday'),
                    name: 'bday'
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
        items:[
            {
                layout: 'column',
                items:[
                    {
                        columnWidth: 1,
                        xtype: 'columnform',
                        items: [
                            [
                                {
                                    columnWidth: .2,
                                    fieldLabel: translation._('Title'), 
                                    name:'n_prefix',
                                    id: 'n_prefix'
                                },
                                {
                                    columnWidth: .4,
                                    fieldLabel: translation._('First Name'), 
                                    name:'n_given'
                                },
                                {
                                    columnWidth: .4,
                                    fieldLabel: translation._('Last Name'), 
                                    name:'n_family',
                                    allowBlank: false
                                }
                            ],
                            [
                                {
                                    columnWidth: .6,
                                    xtype: 'mirrortextfield',
                                    fieldLabel: translation._('Company'), 
                                    name:'org_name'
                                },
                                {
                                    columnWidth: .4,
                                    fieldLabel: translation._('Job Title'),
                                    name: 'title'
                                }
                            ]
                        ]
                    },
                    {
                        //layout: 'form',
                        hideLabels: true,
                        width: 92,
                        items: new Ext.ux.form.ImageField({
                            id: 'addressbookeditdialog-jpegimage',
                            name: 'jpegimage',
                            width: 90,
                            height: 80
                        })
                    }
                ]
            },
            personalInformationExpandArea
        ]
    };
 
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
                    vtype:'url'
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
        height: 180,
        activeTab: 0,
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
                            fieldLabel: translation._('Region'),
                            name:'adr_one_region'
                        }
                    ],
                    [
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
                            fieldLabel: translation._('Region'),
                            name:'adr_two_region'
                        }
                    ],
                    [
                        {
                            xtype: 'widget-countrycombo',
                            fieldLabel: translation._('Country'),
                            name: 'adr_two_countryname'
                        }
                    ]
                ]
            },
            {
                title: translation._('Custom Fields'),
                html: '',
                disabled: true
            }
        ]
    };

    var contactTabPanel = {
        title: translation._('Contact'),
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
            	//layout: 'hfit',
            	layout: 'accordion',
            	animate: true,
                region: 'east',
                width: 210,
                split: true,
                collapsible: true,
                collapseMode: 'mini',
                margins: '0 5 0 5',
                //bodyStyle: 'border:1px solid #B5B8C8;',
                bodyStyle: 'padding-left: 5px;',
                items: [
                    new Ext.Panel({
                    	// @todo generalise!
                        //xtype: 'panel',
                        title: translation._('Description'),
                        iconCls: 'descriptionIcon',
                        layout: 'form',
                        labelAlign: 'top',
                        items: [{
                            labelSeparator: '',
                            xtype:'textarea',
                            name: 'note',
                            hideLabel: true,
                            grow: false,
                            preventScrollbars:false,
                            anchor:'100% 100%'
                        }]
                    }),
                    new Tine.widgets.tags.TagPanel({
                        border: false,
                        style: 'border:1px solid #B5B8C8;'
                    }),
                    new Tine.widgets.activities.ActivitiesPanel({
                    	app: 'Addressbook',
                        border: false,
                        style: 'border:1px solid #B5B8C8;'
                    })                    
                ]
            }
        ]
    };
    
    // check if google api and address info is available 
    if (window.GSmallMapControl && _contact.adr_one_street && _contact.adr_one_locality) {
        // @todo set center/markers when tab is clicked 
    	// @todo add country and other address info
        // google maps tab panel
        var gmapPanel = new Ext.ux.GMapPanel({
        	id: 'googleMapsPanel',
        	title: translation._('Map'),
            region: 'center',
            zoomLevel: 14,
            gmapType: 'map',
            addControl: new GSmallMapControl(),
            setCenter: {
                //geoCodeAddr: '4 Yawkey Way, Boston, MA, 02215-3409, USA',
                //marker: {title: 'Fenway Park'}
            	geoCodeAddr: _contact.adr_one_street + ', ' + _contact.adr_one_locality,
                marker: {title: translation._('Business Address')}
            }
            // @todo make other addresses work
            /*,
            markers: [{
                geoCodeAddr: _contact.adr_two_street + ', ' + _contact.adr_two_locality,
                marker: {title: translation._('Private Address')}
            }]
            */        
        });
    } else {
    	var gmapPanel = {
            title: translation._('Map'),
            disabled: true
        };
    }
    
    var tabPanel = new Ext.TabPanel({
        id: 'adbEditDialogTabPanel',
        xtype:'tabpanel',
        defaults: {
            frame: true
        },
        height: 500,
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
            gmapPanel,
            {
                title: sprintf(translation.ngettext('Link', 'Links [%d]', 1), 1),
                disabled: true
            },
            {
                title: translation._('History'),
                disabled: true
            }
        ]
    });
    
    // resize tab panel when window gets resised, and let space for savePath
    tabPanel.on('bodyresize', function(panel, w, h){
        panel.setHeight(Ext.getCmp('contactDialog').getSize().height-100);
    });
    
    return [
        tabPanel,
        savePath
    ];
};

/**
 * Addressbook Edit Popup
 * 
 * @todo use that in Addressbook.js
 */
Tine.Addressbook.EditPopup = Ext.extend(Ext.ux.PopupWindow, {
   contactId: '',
   //containerId: -1,
	
   name: 'AddressbookEditWindow',
   width: 800,
   height: 600,
   initComponent: function(){
       //this.url = 'index.php?method=Addressbook.editContact&_contactId=' + this.id + '&containerId=' + this.containerId;
   	   this.url = 'index.php?method=Addressbook.editContact&_contactId=' + this.contactId;
       Tine.Addressbook.EditPopup.superclass.initComponent.call(this);
   }
});