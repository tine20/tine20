
/**
 * Addressbook Edit Dialog
 * 
 * @todo make country selection a widget
 */
Tine.Addressbook.ContactEditDialog.getEditForm = function() {
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
                labelWidth: 45,
                border: false,
                items:
                    new Tine.widgets.container.selectionComboBox({
                        fieldLabel:'Saved in',
                        width: 150,
                        name: 'owner',
                        itemName: 'Addressbook',
                        appName: 'Addressbook'
                    })
                },{
                    hideLabels: true,
                    bodyStyle: 'margin-left: 3px;',
                    //border: true,
                    items: {
                        xtype: 'checkbox',
                        boxLabel: 'Private',
                        disabled: true
                    }
                }]
            },{
                columnWidth: .5,
                labelWidth: 70,
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
    
    var personalInformationExpandArea = new Ext.Panel({
        layout: 'form',
        itemCls: 'x-form-item-float-left',
        border: false,
        labelAlign: 'top',
        defaults: { labelSeparator: '', border: false },
        items: [{
            xtype: 'textfield',
            fieldLabel: 'Middle Name(s)',
            name: 'n_middle',
            width: 175
        },{
            xtype:'textfield',
            fieldLabel:'Suffix', 
            name:'n_suffix',
            width: 120
        },{
            xtype:'combo',
            fieldLabel:'Timezone', 
            name:'tz',
            width: 100
        },{
            xtype: 'textfield',
            fieldLabel: 'Job Title',
            name: 'title',
            width: 165
        },{
            xtype:'textfield',
            fieldLabel:'Room', 
            name:'room',
            width: 80
        },{
            xtype:'textfield',
            fieldLabel:'Assistent', 
            name:'assistent',
            width: 165
        }/*,{
            xtype:'textarea',
            fieldLabel:'Public Key', 
            name:'pubkey',
            grow: true,
            growMax: 200,
            width: 420
        },{
            xtype:'textfield',
            fieldLabel:'Calendar Url', 
            name:'calendar_uri',
            width: 420
        },{
            xtype:'textfield',
            fieldLabel:'Free/Busy Url', 
            name:'freebusy_uri',
            width: 420
        }*/]
    });
    
    var personalInformation = {
        title: 'Personal Information',
        width: 450,
        xtype: 'expanderfieldset',
        collapsible: true,
        autoHeight: true,
        items: [{
            layout: 'form',
            //bodyStyle: 'margin-top: 15px;',
            itemCls: 'x-form-item-float-left',
            border: false,
            labelAlign: 'top',
            defaults: { 
                xtype:'textfield',
                labelSeparator: '', 
                border: false 
            },
            items: [{
                fieldLabel:'Title', 
                name:'n_prefix',
                width: 65
            },{
                fieldLabel:'First Name', 
                name:'n_given',
                width: 130
            },{
                fieldLabel:'Last Name', 
                name:'n_family',
                width: 130
            },{
                xtype: 'datefield',
                fieldLabel:'Birthday', 
                name:'bday', 
                format:'d.m.Y', 
                width: 80
            },{
                fieldLabel:'Role', 
                name:'role',
                width: 125
            },{
                xtype: 'mirrortextfield',
                fieldLabel:'Company', 
                name:'org_name',
                width: 160
            },{
                fieldLabel:'Unit', 
                name:'org_unit',
                width: 125
            }]
        },
        personalInformationExpandArea]
    };
    
    var picture = {
        layout: 'fit',
        border: false,
        width: 100,
        bodyStyle: 'padding-top: 7px; padding-left: 5px;',
        html: '<img src="images/empty_photo.jpg" width="90px">'
    };
    
    var contactInformationExpandArea = new Ext.Panel({
        layout: 'column',
        border: false,
        defaults: { border: false },
        items: [
            {
                columnWidth: .33,
                layout: 'form',
                labelAlign: 'top',
                defaults: {
                    xtype:'icontextfield',
                    anchor:'95%',
                    labelSeparator: ''
                },
                items: [
                    {
                        fieldLabel:'Pager', 
                        labelIcon: 'images/oxygen/16x16/devices/pda.png',
                        name:'tel_pager'
                    },
                    {
                        fieldLabel:'Phone Car',
                        //labelIcon: 'images/oxygen/16x16/devices/phone.png',
                        name:'tel_car'
                    }
                    
                ]
            },
            {
                columnWidth: .33,
                layout: 'form',
                labelAlign: 'top',
                defaults: {
                    xtype:'icontextfield',
                    anchor:'95%',
                    labelSeparator: ''
                },
                items: [
                    {
                        fieldLabel:'Phone Assistent', 
                        labelIcon: 'images/oxygen/16x16/apps/kcall.png',
                        name:'tel_assistent'
                    }
                ]
            },
            {
                columnWidth: .33,
                layout: 'form',
                labelAlign: 'top',
                defaults: {
                    xtype:'icontextfield',
                    anchor:'95%',
                    labelSeparator: ''
                },
                items: [
                    {
                        fieldLabel:'Web (private)',
                        labelIcon: 'images/oxygen/16x16/actions/network.png',
                        name:'url_home'
                    }
                ]
            }
        ]
    });
    
    var contactInformationBasePanel = new Ext.Panel(
    {
        layout: 'column',
        height: 127,
        customHeight: 127,
        border: false,
        defaults: { border: false },
        items: [
            {
                columnWidth: .33,
                layout: 'form',
                labelAlign: 'top',
                defaults: {
                    xtype:'icontextfield',
                    anchor:'95%',
                    labelSeparator: ''
                },
                items: [
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
                ]
            },
            {
                columnWidth: .33,
                layout: 'form',
                labelAlign: 'top',
                defaults: {
                    xtype:'icontextfield',
                    anchor:'95%',
                    labelSeparator: ''
                },
                items: [
                    {
                        fieldLabel:'E-Mail', 
                        labelIcon: 'images/oxygen/16x16/actions/kontact-mail.png',
                        name:'email'
                    },
                    {
                        fieldLabel:'Instant Messaging', 
                        name:'',
                        disabled: true
                    },
                    {
                        fieldLabel:'Web',
                        labelIcon: 'images/oxygen/16x16/actions/network.png',
                        name:'',
                        disabled: true
                    }
                ]
            },
            {
                columnWidth: .33,
                layout: 'form',
                labelAlign: 'top',
                //bodyStyle: 'background-color: #FFFFFF;',
                defaults: {
                    xtype:'icontextfield',
                    anchor:'95%',
                    labelSeparator: ''
                },
                items: [
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
                        fieldLabel:'E-Mail (private)', 
                        labelIcon: 'images/oxygen/16x16/actions/kontact-mail.png',
                        name:'email_home'
                    }
                ]
            }
        ]
    });
    
    var contactInformation = new Ext.ux.ExpandFieldSet({
        //xtype: 'expanderfieldset',
        collapsible: true,
        layout: 'fit',
        //collapsed: true,
        autoHeight:true,
        title: 'Contact Information',
        items: [
            contactInformationBasePanel,
            contactInformationExpandArea
        ]
    });
    
    var companyInformation = {
        xtype: 'tabpanel',
        deferredRender:false,
        //autoHeight: true,
        height: 160,
        activeTab: 0,
        border: false,
        defaults: {
            frame: true
        },
        items: [
            {
                title: 'Company Information',
                layout: 'column',
                border: false,
                items:[
                    {
                        columnWidth: .33,
                        layout: 'form',
                        labelAlign: 'top',
                        defaults: {
                            xtype:'textfield',
                            anchor:'95%',
                            labelSeparator: ''
                        },
                        items: [
                            {
                                xtype: 'mirrortextfield',
                                fieldLabel:'Company Name', 
                                name:'org_name'
                            },
                            {
                                fieldLabel:'Region',
                                name:'adr_one_region'
                            },
                            new Ext.form.ComboBox({
                                fieldLabel: 'Country',
                                name: 'adr_one_countryname',
                                hiddenName:'adr_one_countryname',
                                store: new Ext.data.JsonStore({
                                    baseParams: {
                                        method:'Tinebase.getCountryList'
                                    },
                                    root: 'results',
                                    id: 'shortName',
                                    fields: ['shortName', 'translatedName'],
                                    remoteSort: false
                                }),
                                displayField:'translatedName',
                                valueField:'shortName',
                                typeAhead: true,
                                mode: 'remote',
                                triggerAction: 'all',
                                emptyText:'Select a state...',
                                selectOnFocus:true,
                                anchor:'95%'
                            })
                        ]
                    },
                    {
                        columnWidth: .33,
                        layout: 'form',
                        labelAlign: 'top',
                        defaults: {
                            xtype:'textfield',
                            anchor:'95%',
                            labelSeparator: ''
                        },
                        items: [
                            {
                                fieldLabel:'Street', 
                                name:'adr_one_street'
                            },
                            {
                                fieldLabel:'Postal Code', 
                                name:'adr_one_postalcode'
                            },
                            {
                                fieldLabel:'City',
                                name:'adr_one_locality'
                            }
                        ]
                    },
                    {
                        columnWidth: .33,
                        layout: 'form',
                        labelAlign: 'top',
                        defaults: {
                            xtype:'icontextfield',
                            anchor:'95%',
                            labelSeparator: ''
                        },
                        items: [
                            {
                                fieldLabel:'Phone General',
                                labelIcon: 'images/oxygen/16x16/apps/kcall.png',
                                name:'',
                                disabled: true
                            },
                            {
                                fieldLabel:'Fax General',
                                labelIcon: 'images/oxygen/16x16/devices/printer.png',
                                name:'',
                                disabled: true
                            },
                            {
                                fieldLabel:'URL', 
                                labelIcon: 'images/oxygen/16x16/actions/network.png',
                                name:'url'
                            }
                        ]
                    }
                ]
            },
            {
                title: 'Private Information',
                layout: 'column',
                border: false,
                items:[
                    {
                        columnWidth: .33,
                        layout: 'form',
                        labelAlign: 'top',
                        defaults: {
                            xtype:'textfield',
                            anchor:'95%',
                            labelSeparator: ''
                        },
                        items: [
                            {
                                fieldLabel:'Region',
                                name:'adr_two_region'
                            },
                            new Ext.form.ComboBox({
                                fieldLabel: 'Country',
                                name: 'adr_two_countryname',
                                hiddenName:'adr_two_countryname',
                                store: new Ext.data.JsonStore({
                                    baseParams: {
                                        method:'Tinebase.getCountryList'
                                    },
                                    root: 'results',
                                    id: 'shortName',
                                    fields: ['shortName', 'translatedName'],
                                    remoteSort: false
                                }),
                                displayField:'translatedName',
                                valueField:'shortName',
                                typeAhead: true,
                                mode: 'remote',
                                triggerAction: 'all',
                                emptyText:'Select a state...',
                                selectOnFocus:true,
                                anchor:'95%'
                            })
                        ]
                    },
                    {
                        columnWidth: .33,
                        layout: 'form',
                        labelAlign: 'top',
                        defaults: {
                            xtype:'textfield',
                            anchor:'95%',
                            labelSeparator: ''
                        },
                        items: [
                            {
                                fieldLabel:'Street', 
                                name:'adr_two_street'
                            },
                            {
                                fieldLabel:'Postal Code', 
                                name:'adr_two_postalcode'
                            },
                            {
                                fieldLabel:'City',
                                name:'adr_two_locality'
                            }
                        ]
                    }
                ]
            },
            {
                title: 'Custom Fields',
                html: '',
                disabled: true
            }
        ]
    };
    
    var tags = new Tine.widgets.tags.TagPanel({
        width: 205,
        height: 230,
        customHeight: 230,
        border: false,
        style: 'border:1px solid #B5B8C8;',
        //labelAlign: 'top'
    });
    
    
    var contactTabPanel = {
        title: 'Contact',
        autoScroll: true,
        layout: 'column',
        border: false,
        items: [
            {
                layout: 'fit',
                id: 'adbEditDialogContactLeft',
                border: false,
                width: 550,
                items: [
                    savePath,
                    {
                        layout: 'column',
                        border: false,
                        items: [
                            personalInformation,
                            picture
                        ]
                    },
                    contactInformation,
                    companyInformation
                ]
            },
            {
                // tags & notes
                layout: 'fit',
                //bodyStyle: 'border:1px solid #B5B8C8;',
                bodyStyle: 'margin-left: 5px;',
                items: [
                    tags,
                    {
                        //xtype: 'panel',
                        layout: 'form',
                        labelAlign: 'top',
                        items: [{
                            labelSeparator: '',
                            xtype:'textarea',
                            name: 'note',
                            fieldLabel: 'Notes',
                            grow: false,
                            preventScrollbars:false,
                            anchor:'95%',
                            width: 215,
                            height: 230
                        }]
                    }
                ]
            }
        ]
    };
    
    var tabPanel = {
        xtype:'tabpanel',
        defaults: {
            frame: true
        },
        height: 525,
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
    };
    
    // hacks to supress the bottom scrollbar when the side scollbar apreas
    personalInformationExpandArea.on('expand', function(panel){
        var wrap = Ext.getCmp('adbEditDialogContactLeft').getEl().up('div.x-column-inner');
        wrap.setWidth(wrap.up('div').getWidth()-16);
    });
    contactInformationExpandArea.on('expand', function(panel){
        var wrap = Ext.getCmp('adbEditDialogContactLeft').getEl().up('div.x-column-inner');
        wrap.setWidth(wrap.up('div').getWidth()-16);
    });
    //workarround Extjs layout bugs
    contactInformationBasePanel.on('resize', function(cmp){
        cmp.setHeight(cmp.customHeight);
    });
    tags.on('resize', function(cmp){
        cmp.setHeight(cmp.customHeight);
    });
    return tabPanel;
}