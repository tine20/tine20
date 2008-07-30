/**
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/****************** lead edit dialog layout ************************/

/**
 * Lead Edit Dialog
 * separate layout from logic
 * 
 * @todo    add more components/panels
 * @todo    add history
 */
Tine.Crm.LeadEditDialog.getEditForm = function(_linkTabpanels, _lead) {

	var translation = new Locale.Gettext();
    translation.textdomain('Crm');

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
        displayField:'leadstate',
        valueField:'id',
        mode: 'local',
        triggerAction: 'all',
        editable: false,
        allowBlank: false,
//        listWidth: '25%',        IE does not like it
        forceSelection: true,
        anchor:'95%',
        lazyInit: false
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
        displayField:'leadtype',
        valueField:'id',
        typeAhead: true,
        triggerAction: 'all',
//        listWidth: '25%',                
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
            displayField:'leadsource',
            valueField:'id',
            typeAhead: true,
//            listWidth: '25%',                
            mode: 'local',
            triggerAction: 'all',
            editable: false,
            allowBlank: false,
            forceSelection: true,
            anchor:'95%'    
    });

    var combo_probability = new Ext.ux.PercentCombo({
        fieldLabel: translation._('Probability'), 
        id: 'combo_probability',
        anchor:'95%',            
//        listWidth: '25%',            
        name:'probability'
    });

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
                new Tine.widgets.activities.ActivitiesPanel({
                    app: 'Crm',
                    height: 400,
                    customHeight: 400,
                    border: false,
                    style: 'border:1px solid #B5B8C8;'
                })
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
                    columnWidth: 0.33,
                    items:[{
                        layout: 'form',
                        items: [
                            combo_leadstatus, 
                            combo_leadtyp,
                            combo_leadsource
                        ]
                    }]                          
                },{
                    columnWidth: 0.33,
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
                    columnWidth: 0.33,
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
                height: 350,
                items: _linkTabpanels
            }
            ]
        }]
    };        
    
    /*********** HISTORY tab panel ************/

    // @todo    add implemented histoy tab panel
    /*
    var tabPanelHistory = {
        title: translation._('History'),
        disabled: true,
        layout:'border',
        layoutOnTabChange:true,
        defaults: {
            border: true,
            frame: true            
        }
    };
    */
    var tabPanelActivities = new Tine.widgets.activities.ActivitiesTabPanel({
        app: 'Crm',
        record_id: _lead.id,
        record_model: 'Crm_Model_Lead'
    });

    /*********** MAIN tab panel ************/
    
    var tabPanel = new Ext.TabPanel({
        plain:true,
        activeTab: 0,
        id: 'editMainTabPanel',
        layoutOnTabChange:true,  
        items:[
            tabPanelOverview,
            tabPanelActivities                    
        ]
    });
    
    // @todo add savePath (container) to the bottom and remove it from form in the middle
    return [
        tabPanel
        //savePath
    ];
};

/*********************** crm widgets ************************/

Ext.namespace('Tine.Crm', 'Tine.Crm.contactType');

/**
 * contact type select combo box
 * 
 */
Tine.Crm.contactType.ComboBox = Ext.extend(Ext.form.ComboBox, {	
	/**
     * @cfg {bool} autoExpand Autoexpand comboBox on focus.
     */
    autoExpand: false,
    /**
     * @cfg {bool} blurOnSelect blurs combobox when item gets selected
     */
    blurOnSelect: false,
    
    displayField: 'label',
    valueField: 'relation_type',
    mode: 'local',
    triggerAction: 'all',
    lazyInit: false,
    
    //private
    initComponent: function() {
    	
        var translation = new Locale.Gettext();
        translation.textdomain('Crm');
    	
        Tine.Crm.contactType.ComboBox.superclass.initComponent.call(this);
        // allways set a default
        if(!this.value) {
            this.value = 'responsible';
        }
            
        this.store = new Ext.data.SimpleStore({
            fields: ['label', 'relation_type'],
            data: [
                    [translation._('Responsible'), 'responsible'],
                    [translation._('Customer'), 'customer'],
                    [translation._('Partner'), 'partner']
                ]
        });
        
        if (this.autoExpand) {
            this.on('focus', function(){
                this.lazyInit = false;
                this.selectByValue(this.getValue());
                this.expand();
            });
        }
        
        if (this.blurOnSelect){
            this.on('select', function(){
                this.fireEvent('blur', this);
            }, this);
        }
    }
});
Ext.reg('leadcontacttypecombo', Tine.Crm.contactType.ComboBox);

/**
 * contact type renderer
 * 
 * @param   string type
 * @return  contact type icon
 */
Tine.Crm.contactType.Renderer = function(type)
{
    switch ( type ) {
        case 'responsible':
            var iconClass = 'contactIconResponsible';
            var qTip = Tine.Crm.LeadEditDialog.translation._('Responsible');
            break;
        case 'customer':
            var iconClass = 'contactIconCustomer';
            var qTip = Tine.Crm.LeadEditDialog.translation._('Customer');
            break;
        case 'partner':
            var iconClass = 'contactIconPartner';
            var qTip = Tine.Crm.LeadEditDialog.translation._('Partner');
            break;
    }
    
    var icon = '<img class="x-menu-item-icon ' + iconClass + '" src="ExtJS/resources/images/default/s.gif" ext:qtip="' + qTip + '"/>';
    
    return icon;
};
