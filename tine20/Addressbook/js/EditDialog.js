
/**
 * Addressbook Edit Dialog
 * 
 * @todo make country selection a widget
 */
Tine.Addressbook.ContactEditDialog.getEditForm = function() {
    var uploadImage = function(bb) {
        //var inp = bb.detachInputFile();
        //console.log(bb);
    };
    
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
                        fieldLabel:'Saved in',
                        width: 150,
                        name: 'owner',
                        itemName: 'Addressbook',
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
                labelWidth: 100,
                items: {
                    xtype: 'combo',
                    fieldLabel: 'Display Name',
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
                    fieldLabel:'Job Role', 
                    name:'role'
                },
                {
                    columnWidth: .4,
                    fieldLabel:'Unit', 
                    name:'org_unit'
                },
                {
                    columnWidth: .2,
                    fieldLabel:'Room', 
                    name:'room'
                }
            ],
            [
                {
                    columnWidth: .4,
                    fieldLabel:'Middle Name(s)', 
                    name:'n_middle'
                },
                {
                    columnWidth: .4,
                    fieldLabel:'Suffix', 
                    name:'n_suffix'
                },
                {
                    columnWidth: .2,
                    xtype: 'datefield',
                    format: 'd.m.Y',
                    fieldLabel: 'Birthday',
                    name: 'bday'
                }
            ]
        ]
    });
    
    var personalInformation = {
        title: 'Personal Information',
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
                                    fieldLabel:'Title', 
                                    name:'n_prefix',
                                    id: 'n_prefix'
                                },
                                {
                                    columnWidth: .4,
                                    fieldLabel:'First Name', 
                                    name:'n_given'
                                },
                                {
                                    columnWidth: .4,
                                    fieldLabel:'Last Name', 
                                    name:'n_family'
                                }
                            ],
                            [
                                {
                                    columnWidth: .6,
                                    xtype: 'mirrortextfield',
                                    fieldLabel:'Company', 
                                    name:'org_name'
                                },
                                {
                                    columnWidth: .4,
                                    fieldLabel: 'Job Title',
                                    name: 'title'
                                }
                            ]
                        ]
                    },
                    {
                        width: 90,
                        items: new Ext.ux.form.ImageField({
                            name: 'jpegimage',
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
                    fieldLabel:'Phone', 
                    labelIcon: 'images/oxygen/16x16/apps/kcall.png',
                    name:'tel_work'
                },
                {
                    fieldLabel:'Mobile',
                    labelIcon: 'images/oxygen/16x16/devices/phone.png',
                    name:'tel_cell'
                },
                {
                    fieldLabel:'Fax', 
                    labelIcon: 'images/oxygen/16x16/devices/printer.png',
                    name:'tel_fax'
                }
            ],
            [
                {
                    fieldLabel:'Phone (private)',
                    labelIcon: 'images/oxygen/16x16/apps/kcall.png',
                    name:'tel_home'
                },
                {
                    fieldLabel:'Mobile (private)',
                    labelIcon: 'images/oxygen/16x16/devices/phone.png',
                    name:'tel_cell_private'
                },
                {
                    fieldLabel:'Fax (private)', 
                    labelIcon: 'images/oxygen/16x16/devices/printer.png',
                    name:'tel_fax_home'
                }
            ],
            [
                {
                    fieldLabel:'E-Mail', 
                    labelIcon: 'images/oxygen/16x16/actions/kontact-mail.png',
                    name:'email',
                    vtype: 'email'
                },
                {
                    fieldLabel:'E-Mail (private)', 
                    labelIcon: 'images/oxygen/16x16/actions/kontact-mail.png',
                    name:'email_home',
                    vtype: 'email'
                },
                {
                    xtype: 'mirrortextfield',
                    fieldLabel:'Web',
                    labelIcon: 'images/oxygen/16x16/actions/network.png',
                    name:'url',
                    vtype:'url'
                }
            ]
        ]
    };
    
    var contactInformation = {
        title: 'Contact Information',
        xtype: 'fieldset',
        layout: 'hfit',
        //collapsible: true,
        autoHeight:true,
        items: [
            contactInformationBasePanel,
            //contactInformationExpandArea
        ]
    };
    
    var companyInformation = {
        xtype: 'tabpanel',
        deferredRender:false,
        height: 160,
        activeTab: 0,
        border: false,
        defaults: {
            frame: true
        },
        items: [
            {
                xtype: 'columnform',
                title: 'Company Address',
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
                            fieldLabel:'Street', 
                            name:'adr_one_street'
                        },
                        {
                            fieldLabel:'Street 2', 
                            name:'adr_one_street2'
                        }
                    ],
                    [
                        {
                            fieldLabel:'Postal Code', 
                            name:'adr_one_postalcode'
                        },
                        {
                            fieldLabel:'City',
                            name:'adr_one_locality'
                        },
                        {
                            fieldLabel:'Region',
                            name:'adr_one_region'
                        }
                    ],
                    [
                        {
                            xtype: 'widget-countrycombo',
                            fieldLabel: 'Country',
                            name: 'adr_one_countryname',
                        }
                    ]
                ]
            },
            {
                xtype: 'columnform',
                title: 'Private Address',
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
                            fieldLabel:'Street', 
                            name:'adr_two_street'
                        },
                        {
                            fieldLabel:'Street 2', 
                            name:'adr_two_street2'
                        }
                    ],
                    [
                        {
                            fieldLabel:'Postal Code', 
                            name:'adr_two_postalcode'
                        },
                        {
                            fieldLabel:'City',
                            name:'adr_two_locality'
                        },
                        {
                            fieldLabel:'Region',
                            name:'adr_two_region'
                        }
                    ],
                    [
                        {
                            xtype: 'widget-countrycombo',
                            fieldLabel: 'Country',
                            name: 'adr_two_countryname',
                        }
                    ]
                ]
            },
            {
                title: 'Custom Fields',
                html: '',
                disabled: true
            }
        ]
    };

    var contactTabPanel = {
        title: 'Contact',
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
                layout: 'hfit',
                region: 'east',
                width: 200,
                split: true,
                collapsible: true,
                collapseMode: 'mini',
                margins: '0 5 0 5',
                //bodyStyle: 'border:1px solid #B5B8C8;',
                bodyStyle: 'padding-left: 5px;',
                items: [
                    new Tine.widgets.tags.TagPanel({
                        height: 230,
                        customHeight: 230,
                        border: false,
                        style: 'border:1px solid #B5B8C8;',
                    }),
                    {
                        xtype: 'panel',
                        layout: 'form',
                        labelAlign: 'top',
                        items: [{
                            labelSeparator: '',
                            xtype:'textarea',
                            name: 'note',
                            fieldLabel: 'Notes',
                            grow: false,
                            preventScrollbars:false,
                            anchor:'100%',
                            height: 205
                        }]
                    }
                ]
            }
        ]
    };
    
    var tabPanel = new Ext.TabPanel({
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
            {
                title: 'Links [2]',
                disabled: true
            },
            {
                title: 'Activity [5/+2]',
                disabled: true
            },
            {
                title: 'History',
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
}