
/**
 * Lead Edit Dialog
 * separate layout from logic
 * 
 * @todo    add more components/panels
 * @todo    add history
 */
Tine.Crm.LeadEditDialog.getEditForm = function(_linkTabpanels) {

	var translation = new Locale.Gettext();
    translation.textdomain('Crm');

    /*********** OVERVIEW form static stores ************/
    // @todo    get stores via Tine.Crm.XXX.getStore
    /*
    var storeLeadStates = new Ext.data.JsonStore({
        data: formData.comboData.leadstates,
        autoLoad: true,         
        id: 'key',
        fields: //Tine.Crm.LeadState.Model 
        [
            {name: 'key', mapping: 'id'},
            {name: 'value', mapping: 'leadstate'},
            {name: 'probability', mapping: 'probability'},
            {name: 'endslead', mapping: 'endslead'}
        ]
    });
    
    var storeLeadSource = new Ext.data.JsonStore({
        data: formData.comboData.leadsources,
        autoLoad: true,
        id: 'key',
        fields: [
            {name: 'key', mapping: 'id'},
            {name: 'value', mapping: 'leadsource'}

        ]
    });     

    var storeLeadTypes = new Ext.data.JsonStore({
        data: formData.comboData.leadtypes,
        autoLoad: true,
        id: 'key',
        fields: [
            {name: 'key', mapping: 'id'},
            {name: 'value', mapping: 'leadtype'}

        ]
    });
    */     
    
    // @todo make generic, this is used multiple times
    var storeProbability = new Ext.data.SimpleStore({
            fields: ['key','value'],
            data: [
                    ['0','0%'],
                    ['10','10%'],
                    ['20','20%'],
                    ['30','30%'],
                    ['40','40%'],
                    ['50','50%'],
                    ['60','60%'],
                    ['70','70%'],
                    ['80','80%'],
                    ['90','90%'],
                    ['100','100%']
                ]
    });
       
    /*********** OVERVIEW form fields ************/

    var txtfld_leadName = new Ext.form.TextField({
        hideLabel: true,
        id: 'lead_name',
        //fieldLabel:'Projektname', 
        emptyText: translation._('Enter short name'),
        name:'lead_name',
        allowBlank: false,
        selectOnFocus: true,
        anchor:'100%'
        //selectOnFocus:true            
        }); 
 
    var combo_leadstatus = new Ext.form.ComboBox({
        fieldLabel: translation._('Leadstate'), 
        id:'leadstatus',
        name:'leadstate_id',
        store: Tine.Crm.LeadState.getStore(),
        //displayField:'value',
        //valueField:'key',
        displayField:'leadstate',
        valueField:'id',
        mode: 'local',
        triggerAction: 'all',
        editable: false,
        allowBlank: false,
        listWidth: '25%',
        forceSelection: true,
        anchor:'95%'    
    });
    
    combo_leadstatus.on('select', function(combo, record, index) {
        if (record.data.probability !== null) {
            var combo_probability = Ext.getCmp('combo_probability');
            combo_probability.setValue(record.data.probability);
        }

        if (record.data.endslead == '1') {
            var combo_endDate = Ext.getCmp('end');
            combo_endDate.setValue(new Date());
        }
    });
    
    var combo_leadtyp = new Ext.form.ComboBox({
        fieldLabel: translation._('Leadtype'), 
        id:'leadtype',
        name:'leadtype_id',
        store: Tine.Crm.LeadType.getStore(),
        mode: 'local',
        //displayField:'value',
        //valueField:'key',
        displayField:'leadtype',
        valueField:'id',
        typeAhead: true,
        triggerAction: 'all',
        listWidth: '25%',                
        editable: false,
        allowBlank: false,
        forceSelection: true,
        anchor:'95%'    
    });

    var combo_leadsource = new Ext.form.ComboBox({
            fieldLabel: translation._('Leadsource'), 
            id:'leadsource',
            name:'leadsource_id',
            store: Tine.Crm.LeadSource.getStore(),
            //displayField:'value',
            //valueField:'key',
            displayField:'leadsource',
            valueField:'id',
            typeAhead: true,
            listWidth: '25%',                
            mode: 'local',
            triggerAction: 'all',
            editable: false,
            allowBlank: false,
            forceSelection: true,
            anchor:'95%'    
    });

    var combo_probability =  new Ext.form.ComboBox({
        fieldLabel: translation._('Probability'), 
        id: 'combo_probability',
        name:'probability',
        store: storeProbability,
        displayField:'value',
        valueField:'key',
        typeAhead: true,
        mode: 'local',
        listWidth: '25%',            
        triggerAction: 'all',
        emptyText:'',
        selectOnFocus:true,
        editable: false,
        renderer: Ext.util.Format.percentage,
        anchor:'95%'            
    });
    combo_probability.setValue('0');
         
    var date_start = new Ext.form.DateField({
        fieldLabel: translation._('Start'), 
        allowBlank: false,
        id: 'start',             
        anchor: '95%'
    });

    
    var date_scheduledEnd = new Ext.form.DateField({
        fieldLabel: translation._('Estimated end'), 
        id: 'end_scheduled',
        anchor: '95%'
    });
    
    var date_end = new Ext.form.DateField({
        xtype:'datefield',
        fieldLabel: translation._('End'), 
        id: 'end',
        anchor: '95%'
    });

    var folderTrigger = new Tine.widgets.container.selectionComboBox({
        fieldLabel: translation._('folder'),
        name: 'container',
        itemName: 'Leads',
        appName: 'crm',
        anchor:'95%'
    });
 
    /*********** OVERVIEW tab panel ************/

    var tabPanelOverview = {
        title: translation._('Overview'),
        layout:'border',
        layoutOnTabChange:true,
        defaults: {
            border: true,
            frame: true            
        },
        items: [{
            region: 'east',
            autoScroll: true,
            width: 300,
            items: [
                new Tine.widgets.tags.TagPanel({
                    height: 230,
                    customHeight: 230,
                    border: false,
                    style: 'border:1px solid #B5B8C8;'
                }),
                new Ext.Panel({
                    title: translation._('History'),
                    height: 200
                })
                /*
              new Ext.DataView({
                tpl: ActivitiesTpl,       
                autoHeight:true,                    
                id: 'grid_activities_limited',
                store: st_activities,
                overClass: 'x-view-over',
                itemSelector: 'activities-item-small'
              })
              */
            ]
        },{
            region:'center',
            layout: 'form',
            autoHeight: true,
            id: 'editCenterPanel',
            items: [
                txtfld_leadName, 
            {
                xtype:'textarea',
                //fieldLabel:'Notizen',
                id: 'lead_notes',
                hideLabel: true,
                name: 'description',
                height: 120,
                anchor: '100%',
                emptyText: translation._('Enter description')
            }, {
                layout:'column',
                height: 140,
                id: 'lead_combos',
                anchor:'100%',                        
                items: [{
                    columnWidth: .33,
                    items:[{
                        layout: 'form',
                        items: [
                            combo_leadstatus, 
                            combo_leadtyp,
                            combo_leadsource
                        ]
                    }]                          
                },{
                    columnWidth: .33,
                    items:[{
                        layout: 'form',
                        border:false,
                        items: [
                        {
                            xtype:'numberfield',
                            fieldLabel: translation._('Expected turnover'), 
                            name: 'turnover',
                            selectOnFocus: true,
                            anchor: '95%'
                        },  
                            combo_probability,
                            folderTrigger 
                        ]
                    }]              
                },{
                    columnWidth: .33,
                    items:[{
                        layout: 'form',
                        border:false,
                        items: [
                            date_start,
                            date_scheduledEnd,
                            date_end   
                        ]
                    }]
                }]
            }, {
                xtype: 'tabpanel',
                style: 'margin-top: 10px;',
                id: 'linkPanel',
                //title: 'contacts panel',
                activeTab: 0,
                height: 273,
                items: _linkTabpanels,
            }
            ]
        }]
    };        
    
    /*********** HISTORY tab panel ************/

    // @todo    add implemented histoy tab panel
    var tabPanelHistory = {
        title: translation._('History'),
        disabled: true,
        layout:'border',
        layoutOnTabChange:true,
        defaults: {
            border: true,
            frame: true            
        },
    };

    /*********** MAIN tab panel ************/
    
    var tabPanel = new Ext.TabPanel({
        plain:true,
        activeTab: 0,
        id: 'editMainTabPanel',
        layoutOnTabChange:true,  
        items:[
            tabPanelOverview,
            tabPanelHistory                    
        ]
    });
    
    return [
        tabPanel
        //savePath
    ];
};